<?php
declare(strict_types=1);

require_once __DIR__ . '/importers.php';

function live_search(string $query, string $type = 'all', int $limit = 10): array
{
    $query = trim($query);
    $limit = max(1, min(25, $limit));
    if ($query === '') {
        return [];
    }

    if ($type === 'all') {
        $sourceLimit = max($limit, 10);
        $looksLikeLocation = preg_match('/\d/', $query) === 1;
        $companies = live_search_companies($query, $sourceLimit);
        $locations = live_search_locations($query, $sourceLimit);
        $results = $looksLikeLocation ? array_merge($locations, $companies) : array_merge($companies, $locations);
        return array_slice($results, 0, $limit);
    }

    if (in_array($type, ['company', 'person'], true)) {
        return live_search_companies($query, $limit);
    }

    if (in_array($type, ['address', 'parcel'], true)) {
        return live_search_locations($query, $limit);
    }

    return [];
}

function live_search_companies(string $query, int $limit): array
{
    $url = 'https://recherche-entreprises.api.gouv.fr/search?' . http_build_query([
        'q' => $query,
        'per_page' => $limit,
    ]);
    $payload = http_json($url);
    $rows = [];

    foreach (($payload['results'] ?? []) as $company) {
        if (!is_array($company) || empty($company['siren'])) {
            continue;
        }

        $matchingDirectors = array_values(array_filter($company['dirigeants'] ?? [], function ($director) use ($query) {
            if (!is_array($director)) {
                return false;
            }
            $needle = mb_strtolower($query);
            $haystack = mb_strtolower(trim(($director['prenoms'] ?? '') . ' ' . ($director['nom'] ?? '') . ' ' . ($director['denomination'] ?? '')));
            return $needle !== '' && str_contains($haystack, $needle);
        }));

        $rows[] = [
            'type' => 'company',
            'id' => (string) $company['siren'],
            'title' => $company['nom_complet'] ?? $company['nom_raison_sociale'] ?? $company['siren'],
            'subtitle' => trim(implode(' · ', array_filter([
                'SIREN ' . $company['siren'],
                $company['activite_principale'] ?? null,
                ($company['etat_administratif'] ?? null) === 'A' ? 'active' : (($company['etat_administratif'] ?? null) === 'C' ? 'cessee' : null),
                $company['siege']['adresse'] ?? null,
            ]))),
            'source' => 'api_recherche_entreprises',
            'match' => $matchingDirectors ? 'dirigeant' : 'entreprise',
        ];
    }

    return $rows;
}

function live_search_locations(string $query, int $limit): array
{
    $rows = [];
    $isParcel = preg_match('/^\d{5}\d{3}[A-Z0-9]{2}\d{4}$/i', preg_replace('/\s+/', '', $query)) === 1;

    if (!$isParcel) {
        $addressUrl = 'https://data.geopf.fr/geocodage/search?' . http_build_query([
            'q' => $query,
            'index' => 'address',
            'limit' => min(5, $limit),
        ]);
        $addressPayload = http_json($addressUrl);

        foreach (($addressPayload['features'] ?? []) as $feature) {
            $props = $feature['properties'] ?? [];
            $coords = $feature['geometry']['coordinates'] ?? [];
            $id = $props['id'] ?? ($props['label'] ?? null);
            if (!$id) {
                continue;
            }
            $rows[] = [
                'type' => 'address',
                'id' => (string) $id,
                'detail_id' => (string) ($props['label'] ?? $props['name'] ?? $id),
                'title' => $props['label'] ?? $props['name'] ?? $id,
                'subtitle' => trim(implode(' · ', array_filter([
                    $props['postcode'] ?? null,
                    $props['city'] ?? null,
                    isset($coords[1], $coords[0]) ? $coords[1] . ', ' . $coords[0] : null,
                ]))),
                'source' => 'geoplateforme_geocodage',
                'lon' => $coords[0] ?? null,
                'lat' => $coords[1] ?? null,
            ];
        }

        $first = $addressPayload['features'][0] ?? null;
        $coords = $first['geometry']['coordinates'] ?? null;
        if (is_array($coords) && isset($coords[0], $coords[1])) {
            $rows = array_merge($rows, live_reverse_parcels((float) $coords[0], (float) $coords[1], min(5, $limit)));
        }
    }

    if ($isParcel) {
        $parcelUrl = 'https://data.geopf.fr/geocodage/search?' . http_build_query([
            'q' => preg_replace('/\s+/', '', strtoupper($query)),
            'index' => 'parcel',
            'limit' => min(5, $limit),
        ]);
        $parcelPayload = http_json($parcelUrl);
        foreach (($parcelPayload['features'] ?? []) as $feature) {
            $rows[] = live_parcel_result($feature);
        }
    }

    return array_values(array_filter($rows));
}

function live_reverse_parcels(float $lon, float $lat, int $limit): array
{
    $url = 'https://data.geopf.fr/geocodage/reverse?' . http_build_query([
        'lon' => $lon,
        'lat' => $lat,
        'index' => 'parcel',
        'limit' => $limit,
    ]);
    $payload = http_json($url);
    $rows = [];

    foreach (($payload['features'] ?? []) as $feature) {
        $result = live_parcel_result($feature);
        if ($result) {
            $rows[] = $result;
        }
    }

    return $rows;
}

function live_parcel_result(array $feature): ?array
{
    $props = $feature['properties'] ?? [];
    if (empty($props['id'])) {
        return null;
    }

    return [
        'type' => 'parcel',
        'id' => (string) $props['id'],
        'detail_id' => (string) $props['id'],
        'title' => 'Parcelle ' . $props['id'],
        'subtitle' => trim(implode(' · ', array_filter([
            $props['city'] ?? null,
            isset($props['section']) ? 'section ' . $props['section'] : null,
            isset($props['number']) ? 'numero ' . $props['number'] : null,
            isset($props['distance']) ? $props['distance'] . ' m' : null,
        ]))),
        'source' => 'geoplateforme_parcellaire',
    ];
}

function live_detail(string $type, string $id): ?array
{
    return match ($type) {
        'company' => live_company_detail($id),
        'address' => live_address_detail($id),
        'parcel' => live_parcel_detail($id),
        default => null,
    };
}

function live_company_detail(string $siren): ?array
{
    $siren = preg_replace('/\D+/', '', $siren);
    if (!in_array(strlen($siren), [9, 14], true)) {
        return null;
    }

    $url = 'https://recherche-entreprises.api.gouv.fr/search?' . http_build_query([
        'q' => $siren,
        'per_page' => 1,
    ]);
    $payload = http_json($url);
    $company = $payload['results'][0] ?? null;
    if (!is_array($company)) {
        return null;
    }

    return [
        'type' => 'company',
        'source' => [
            'code' => 'api_recherche_entreprises',
            'url' => $url,
        ],
        'company' => map_live_company($company),
        'establishments' => array_map(fn ($row) => map_live_establishment((string) $company['siren'], $row), collect_establishments($company)),
        'representatives' => array_values($company['dirigeants'] ?? []),
        'finances' => map_live_finances($company['finances'] ?? [], (string) $company['siren']),
        'bodacc' => live_bodacc_by_siren($siren),
        'raw' => $company,
    ];
}

function live_address_detail(string $idOrQuery): ?array
{
    $url = 'https://data.geopf.fr/geocodage/search?' . http_build_query([
        'q' => $idOrQuery,
        'index' => 'address',
        'limit' => 1,
    ]);
    $payload = http_json($url);
    $feature = $payload['features'][0] ?? null;
    if (!is_array($feature)) {
        return null;
    }

    $props = $feature['properties'] ?? [];
    $coords = $feature['geometry']['coordinates'] ?? [];
    $parcels = isset($coords[0], $coords[1]) ? live_reverse_parcels((float) $coords[0], (float) $coords[1], 5) : [];

    $address = [
        'id' => $props['id'] ?? null,
        'label' => $props['label'] ?? null,
        'numero' => $props['housenumber'] ?? null,
        'rue' => $props['street'] ?? $props['name'] ?? null,
        'ville' => $props['city'] ?? null,
        'code_postal' => $props['postcode'] ?? null,
        'code_commune' => $props['citycode'] ?? null,
        'latitude' => $coords[1] ?? null,
        'longitude' => $coords[0] ?? null,
        'raw_json' => $feature,
    ];

    return [
        'type' => 'address',
        'source' => [
            'code' => 'geoplateforme_geocodage',
            'url' => $url,
        ],
        'address' => $address,
        'nearby_parcels' => $parcels,
        'copros' => live_rnic_by_address((string) ($address['label'] ?? $idOrQuery)),
        'dpe' => live_dpe_by_address((string) ($address['label'] ?? $idOrQuery)),
        'raw' => $payload,
    ];
}

function live_parcel_detail(string $parcelId): ?array
{
    $parcelId = preg_replace('/\s+/', '', strtoupper($parcelId));
    $url = 'https://data.geopf.fr/geocodage/search?' . http_build_query([
        'q' => $parcelId,
        'index' => 'parcel',
        'limit' => 1,
    ]);
    $payload = http_json($url);
    $feature = $payload['features'][0] ?? null;

    if (!is_array($feature)) {
        return [
            'type' => 'parcel',
            'source' => [
                'code' => 'geoplateforme_parcellaire',
                'url' => $url,
            ],
            'parcel' => [
                'numero' => $parcelId,
                'raw_json' => null,
            ],
            'raw' => $payload,
        ];
    }

    $props = $feature['properties'] ?? [];
    $coords = $feature['geometry']['coordinates'] ?? [];

    $parcel = [
        'numero' => $props['id'] ?? $parcelId,
        'parcelle_cadastrale' => $props['id'] ?? $parcelId,
        'section' => $props['section'] ?? null,
        'numero_plan' => $props['number'] ?? null,
        'code_commune' => ($props['departmentcode'] ?? '') . ($props['districtcode'] ?? $props['municipalitycode'] ?? ''),
        'commune' => $props['city'] ?? null,
        'latitude' => $coords[1] ?? null,
        'longitude' => $coords[0] ?? null,
        'raw_json' => $feature,
    ];

    return [
        'type' => 'parcel',
        'source' => [
            'code' => 'geoplateforme_parcellaire',
            'url' => $url,
        ],
        'parcel' => $parcel,
        'sales' => live_dvf_by_parcel((string) $parcel['numero']),
        'copros' => live_rnic_by_parcel((string) $parcel['numero']),
        'raw' => $payload,
    ];
}

function tabular_resource_data(string $resourceId, array $params = []): array
{
    $url = 'https://tabular-api.data.gouv.fr/api/resources/' . rawurlencode($resourceId) . '/data/?' . http_build_query($params);
    $payload = http_json($url);
    return is_array($payload['data'] ?? null) ? $payload['data'] : [];
}

function live_bodacc_by_siren(string $siren, int $limit = 100): array
{
    $siren = preg_replace('/\D+/', '', $siren);
    if (strlen($siren) !== 9) {
        return [];
    }

    $url = 'https://bodacc-datadila.opendatasoft.com/api/explore/v2.1/catalog/datasets/annonces-commerciales/records?' . http_build_query([
        'limit' => $limit,
        'where' => 'registre like "' . $siren . '"',
        'order_by' => 'dateparution desc',
    ]);
    $payload = http_json($url);
    $rows = [];

    foreach (($payload['results'] ?? []) as $row) {
        if (!is_array($row)) {
            continue;
        }
        $rows[] = [
            'numero_parution' => $row['parution'] ?? null,
            'date_publication' => $row['dateparution'] ?? null,
            'numero_annonce' => $row['numeroannonce'] ?? null,
            'bodacc' => $row['publicationavis'] ?? null,
            'type_publication' => $row['familleavis_lib'] ?? $row['familleavis'] ?? null,
            'greffe' => $row['tribunal'] ?? null,
            'nom_entreprise' => $row['commercant'] ?? null,
            'adresse' => trim(implode(' ', array_filter([$row['cp'] ?? null, $row['ville'] ?? null]))),
            'description' => $row['modificationsgenerales'] ?? $row['depot'] ?? $row['jugement'] ?? null,
            'url' => $row['url_complete'] ?? null,
            'raw_json' => $row,
        ];
    }

    return $rows;
}

function live_rnic_by_address(string $address, int $limit = 50): array
{
    $address = trim($address);
    if ($address === '') {
        return [];
    }

    $rows = tabular_resource_data('3ea8e2c3-0038-464a-b17e-cd5c91f65ce2', [
        'page_size' => $limit,
        'adresse_reference__contains' => $address,
    ]);

    if (!$rows && preg_match('/^(\d+)\s+(.+?)\s+(\d{5})/u', $address, $m)) {
        $rows = tabular_resource_data('3ea8e2c3-0038-464a-b17e-cd5c91f65ce2', [
            'page_size' => $limit,
            'adresse_reference__contains' => trim($m[1] . ' ' . $m[2]),
        ]);
    }

    return array_map('map_live_copro', $rows);
}

function live_rnic_by_parcel(string $parcelId, int $limit = 50): array
{
    $parcelId = preg_replace('/\s+/', '', strtoupper($parcelId));
    if ($parcelId === '') {
        return [];
    }

    $rows = [];
    foreach (['reference_cadastrale_1', 'reference_cadastrale_2', 'reference_cadastrale_3'] as $column) {
        $rows = array_merge($rows, tabular_resource_data('3ea8e2c3-0038-464a-b17e-cd5c91f65ce2', [
            'page_size' => $limit,
            $column . '__exact' => $parcelId,
        ]));
        if (count($rows) >= $limit) {
            break;
        }
    }

    return array_map('map_live_copro', array_slice($rows, 0, $limit));
}

function map_live_copro(array $row): array
{
    return [
        'numero_immatriculation' => $row['numero_immatriculation'] ?? null,
        'parcelle_numero' => $row['reference_cadastrale_1'] ?? null,
        'nom' => $row['nom_usage_copropriete'] ?? null,
        'mandat_en_cours' => ($row['mandat_en_cours'] ?? '') === 'Mandat en cours',
        'nombre_total_lots' => decimal_value($row['nombre_total_lots'] ?? null),
        'nombre_lots_a_usage_habitation' => decimal_value($row['nombre_lots_habitation'] ?? null),
        'nombre_lots_stationnement' => decimal_value($row['nombre_lots_stationnement'] ?? null),
        'periode_construction' => $row['periode_construction'] ?? null,
        'type_syndic' => $row['type_syndic'] ?? null,
        'syndic_siret' => $row['siret_representant_legal'] ?? null,
        'syndic_nom' => $row['raison_sociale_representant_legal'] ?? null,
        'date_immatriculation' => $row['date_immatriculation'] ?? null,
        'date_reglement_copropriete' => $row['date_reglement_copropriete'] ?? null,
        'adresse' => $row['adresse_reference'] ?? null,
        'latitude' => decimal_value($row['latitude'] ?? null),
        'longitude' => decimal_value($row['longitude'] ?? null),
        'raw_json' => $row,
    ];
}

function live_dpe_by_address(string $address, int $limit = 50): array
{
    $address = trim($address);
    if ($address === '') {
        return [];
    }

    $url = 'https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines?' . http_build_query([
        'size' => min(50, max($limit * 3, 10)),
        'count' => 'false',
        'q' => $address,
        'select' => implode(',', [
            'numero_dpe',
            'adresse_ban',
            'code_postal_ban',
            'etiquette_dpe',
            'etiquette_ges',
            'date_etablissement_dpe',
            'date_reception_dpe',
            'type_batiment',
            'surface_habitable_logement',
            'surface_habitable_immeuble',
            'conso_5_usages_par_m2_ep',
            'emission_ges_5_usages_par_m2',
            'type_energie_principale_chauffage',
            'type_installation_chauffage',
            'periode_construction',
        ]),
    ]);
    $payload = http_json($url);
    $wanted = normalize_search_text($address);
    $rows = [];

    foreach (($payload['results'] ?? []) as $row) {
        if (!is_array($row)) {
            continue;
        }
        $rowAddress = normalize_search_text((string) ($row['adresse_ban'] ?? ''));
        if ($wanted !== '' && $rowAddress !== '' && !str_contains($rowAddress, $wanted) && !str_contains($wanted, $rowAddress)) {
            continue;
        }
        $rows[] = [
            'identifiant_dpe' => $row['numero_dpe'] ?? null,
            'source' => 'dpe_logements_existants_ademe',
            'date_etablissement_dpe' => $row['date_etablissement_dpe'] ?? null,
            'date_reception_dpe' => $row['date_reception_dpe'] ?? null,
            'classe_bilan_dpe' => $row['etiquette_dpe'] ?? null,
            'classe_emission_ges' => $row['etiquette_ges'] ?? null,
            'type_batiment_dpe' => $row['type_batiment'] ?? null,
            'surface_habitable_logement' => decimal_value($row['surface_habitable_logement'] ?? null),
            'surface_habitable_immeuble' => decimal_value($row['surface_habitable_immeuble'] ?? null),
            'conso_5_usages_ep_m2' => decimal_value($row['conso_5_usages_par_m2_ep'] ?? null),
            'emission_ges_5_usages_m2' => decimal_value($row['emission_ges_5_usages_par_m2'] ?? null),
            'type_installation_chauffage' => $row['type_installation_chauffage'] ?? null,
            'periode_construction_dpe' => $row['periode_construction'] ?? null,
            'adresse' => $row['adresse_ban'] ?? null,
            'raw_json' => $row,
        ];
        if (count($rows) >= $limit) {
            break;
        }
    }

    return $rows;
}

function normalize_search_text(string $value): string
{
    $value = mb_strtolower(trim($value));
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = preg_replace('/\b(750[0-9]{2}|[0-9]{5})\b/', '', $value);
    $value = preg_replace('/\s+/', ' ', preg_replace('/[^a-z0-9]+/', ' ', $value));
    return trim((string) $value);
}

function live_dvf_by_parcel(string $parcelId, int $limit = 50, int $yearsBack = 5): array
{
    $parcelId = preg_replace('/\s+/', '', strtoupper($parcelId));
    $department = substr($parcelId, 0, 2);
    if ($parcelId === '' || $department === '') {
        return [];
    }

    $rows = [];
    $latestYear = (int) date('Y') - 1;
    $oldestYear = max(2021, $latestYear - max(0, $yearsBack - 1));
    for ($year = $latestYear; $year >= $oldestYear; $year--) {
        $url = sprintf('https://files.data.gouv.fr/geo-dvf/latest/csv/%d/departements/%s.csv.gz', $year, rawurlencode($department));
        foreach (stream_dvf_rows($url, $parcelId, $limit - count($rows)) as $row) {
            $rows[] = map_live_dvf_sale($row);
        }
        if (count($rows) >= $limit) {
            break;
        }
    }

    return $rows;
}

function stream_dvf_rows(string $url, string $parcelId, int $limit): array
{
    if ($limit <= 0) {
        return [];
    }

    $cmd = 'curl -L -sS --max-time 20 ' . escapeshellarg($url) . ' | gzip -dc 2>/dev/null';
    $handle = popen($cmd, 'r');
    if (!$handle) {
        return [];
    }

    $rows = [];
    $header = null;
    while (($line = fgets($handle)) !== false) {
        $csv = str_getcsv($line);
        if ($header === null) {
            $header = $csv;
            continue;
        }
        if (!in_array($parcelId, $csv, true)) {
            continue;
        }
        $row = array_combine($header, array_pad($csv, count($header), null));
        if (is_array($row) && ($row['id_parcelle'] ?? null) === $parcelId) {
            $rows[] = $row;
        }
        if (count($rows) >= $limit) {
            break;
        }
    }
    pclose($handle);

    return $rows;
}

function map_live_dvf_sale(array $row): array
{
    return [
        'vente_source_id' => $row['id_mutation'] ?? null,
        'parcelle_numero' => $row['id_parcelle'] ?? null,
        'date_vente' => $row['date_mutation'] ?? null,
        'nature' => $row['nature_mutation'] ?? null,
        'valeur_fonciere' => decimal_value($row['valeur_fonciere'] ?? null),
        'type_local' => $row['type_local'] ?? null,
        'code_type_local' => $row['code_type_local'] ?? null,
        'surface_reelle_bati' => decimal_value($row['surface_reelle_bati'] ?? null),
        'surface_terrain' => decimal_value($row['surface_terrain'] ?? null),
        'nombre_pieces' => decimal_value($row['nombre_pieces_principales'] ?? null),
        'nombre_lots' => decimal_value($row['nombre_lots'] ?? null),
        'adresse' => trim(implode(' ', array_filter([
            $row['adresse_numero'] ?? null,
            $row['adresse_suffixe'] ?? null,
            $row['adresse_nom_voie'] ?? null,
            $row['code_postal'] ?? null,
            $row['nom_commune'] ?? null,
        ]))),
        'raw_json' => $row,
    ];
}

function map_live_company(array $company): array
{
    $siren = (string) ($company['siren'] ?? '');
    return [
        'siren' => $siren,
        'siren_formate' => strlen($siren) === 9 ? substr($siren, 0, 3) . ' ' . substr($siren, 3, 3) . ' ' . substr($siren, 6, 3) : $siren,
        'nom_entreprise' => $company['nom_complet'] ?? $company['nom_raison_sociale'] ?? null,
        'denomination' => $company['nom_raison_sociale'] ?? null,
        'sigle' => $company['sigle'] ?? null,
        'code_naf' => $company['activite_principale'] ?? null,
        'categorie_juridique' => $company['nature_juridique'] ?? null,
        'entreprise_cessee' => ($company['etat_administratif'] ?? null) === 'C',
        'date_creation' => iso_date($company['date_creation'] ?? null),
        'date_cessation' => iso_date($company['date_fermeture'] ?? null),
        'tranche_effectif' => $company['tranche_effectif_salarie'] ?? null,
        'annee_effectif' => $company['annee_tranche_effectif_salarie'] ?? null,
        'statut_consolide' => $company['etat_administratif'] ?? null,
        'siege_json' => $company['siege'] ?? null,
        'raw_json' => $company,
    ];
}

function map_live_establishment(string $siren, array $establishment): array
{
    return [
        'siret' => $establishment['siret'] ?? null,
        'siren' => $siren,
        'siege' => $establishment['est_siege'] ?? null,
        'adresse_ligne_1' => $establishment['adresse'] ?? null,
        'code_postal' => $establishment['code_postal'] ?? null,
        'code_commune' => $establishment['commune'] ?? null,
        'ville' => $establishment['libelle_commune'] ?? null,
        'latitude' => $establishment['latitude'] ?? null,
        'longitude' => $establishment['longitude'] ?? null,
        'code_naf' => $establishment['activite_principale'] ?? null,
        'date_de_creation' => iso_date($establishment['date_creation'] ?? null),
        'date_debut_activite' => iso_date($establishment['date_debut_activite'] ?? null),
        'etablissement_cesse' => ($establishment['etat_administratif'] ?? null) === 'F',
        'date_cessation' => iso_date($establishment['date_fermeture'] ?? null),
        'nom_commercial' => $establishment['nom_commercial'] ?? null,
        'raw_json' => $establishment,
    ];
}

function map_live_finances(array $finances, string $siren): array
{
    $rows = [];
    foreach ($finances as $year => $finance) {
        if (!is_array($finance)) {
            continue;
        }
        $rows[] = [
            'siren' => $siren,
            'annee' => (int) $year,
            'chiffre_affaires' => $finance['ca'] ?? null,
            'resultat' => $finance['resultat_net'] ?? null,
            'raw_json' => $finance,
        ];
    }
    return $rows;
}
