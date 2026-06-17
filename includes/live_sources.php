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

    $lat  = isset($coords[1]) ? (float) $coords[1] : null;
    $lon  = isset($coords[0]) ? (float) $coords[0] : null;
    $codeCommune = (string) ($props['citycode'] ?? '');
    $label       = (string) ($props['label'] ?? $idOrQuery);

    $address = [
        'id'           => $props['id'] ?? null,
        'label'        => $props['label'] ?? null,
        'numero'       => $props['housenumber'] ?? null,
        'rue'          => $props['street'] ?? $props['name'] ?? null,
        'ville'        => $props['city'] ?? null,
        'code_postal'  => $props['postcode'] ?? null,
        'code_commune' => $codeCommune,
        'latitude'     => $lat,
        'longitude'    => $lon,
        'raw_json'     => $feature,
    ];

    return [
        'type'   => 'address',
        'source' => [
            'code' => 'geoplateforme_geocodage',
            'url'  => $url,
        ],
        'address'        => $address,
        'commune'        => ($lat !== null && $lon !== null) ? live_commune_by_location($lat, $lon) : null,
        'nearby_parcels' => $parcels,
        'georisques'     => $codeCommune !== '' ? live_georisques_by_commune($codeCommune) : [],
        'batiments'      => ($lat !== null && $lon !== null) ? live_rnb_by_location($lat, $lon) : [],
        'bdnb'           => live_bdnb_by_address($label, $codeCommune),
        'zonage_urba'    => ($lat !== null && $lon !== null) ? live_gpu_by_location($lat, $lon) : [],
        'copros'         => live_rnic_by_address($label),
        'dpe'            => live_dpe_by_address($label),
        'dpe_neufs'      => live_dpe_neufs_by_address($label),
        'dpe_tertiaire'  => live_dpe_tertiaire_by_address($label),
        'raw'            => $payload,
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

    $lat  = isset($coords[1]) ? (float) $coords[1] : null;
    $lon  = isset($coords[0]) ? (float) $coords[0] : null;
    $codeCommune = ($props['departmentcode'] ?? '') . ($props['districtcode'] ?? $props['municipalitycode'] ?? '');

    $parcel = [
        'numero'              => $props['id'] ?? $parcelId,
        'parcelle_cadastrale' => $props['id'] ?? $parcelId,
        'section'             => $props['section'] ?? null,
        'numero_plan'         => $props['number'] ?? null,
        'code_commune'        => $codeCommune,
        'commune'             => $props['city'] ?? null,
        'latitude'            => $lat,
        'longitude'           => $lon,
        'raw_json'            => $feature,
    ];

    return [
        'type'   => 'parcel',
        'source' => [
            'code' => 'geoplateforme_parcellaire',
            'url'  => $url,
        ],
        'parcel'     => $parcel,
        'georisques' => $codeCommune !== '' ? live_georisques_by_commune($codeCommune) : [],
        'batiments'  => ($lat !== null && $lon !== null) ? live_rnb_by_location($lat, $lon) : [],
        'bdnb'       => ($lat !== null && $lon !== null) ? live_bdnb_by_address('', $codeCommune) : [],
        'sales'      => live_dvf_by_parcel((string) $parcel['numero']),
        'copros'     => live_rnic_by_parcel((string) $parcel['numero']),
        'raw'        => $payload,
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

// ============================================================
// GEORISQUES — BRGM
// georisques.gouv.fr/api/v1/
// Trois endpoints confirmes : radon, mvt, zonage_sismique.
// Parametre : code_insee (sauf installations_classees : codeInsee).
// ============================================================

function live_georisques_by_commune(string $codeCommune): array
{
    $codeCommune = trim($codeCommune);
    if ($codeCommune === '') {
        return [];
    }

    $base = 'https://georisques.gouv.fr/api/v1/';

    $radon = http_json_safe($base . 'radon?' . http_build_query(['code_insee' => $codeCommune]));
    $radonData = is_array($radon) ? ($radon['data'][0] ?? null) : null;

    $sismique = http_json_safe($base . 'zonage_sismique?' . http_build_query(['code_insee' => $codeCommune]));
    $sismiqueData = is_array($sismique) ? ($sismique['data'][0] ?? null) : null;

    $mvt = http_json_safe($base . 'mvt?' . http_build_query([
        'code_insee' => $codeCommune,
        'page'       => 1,
        'pageSize'   => 10,
    ]));
    $mvtItems = (is_array($mvt) && isset($mvt['data'])) ? $mvt['data'] : [];
    $mouvements = [];
    foreach ($mvtItems as $m) {
        if (!is_array($m)) {
            continue;
        }
        $mouvements[] = [
            'type'       => $m['type'] ?? null,
            'lieu'       => $m['lieu'] ?? null,
            'date_debut' => $m['date_debut'] ?? null,
            'fiabilite'  => $m['fiabilite'] ?? null,
            'raw_json'   => $m,
        ];
    }

    return [
        'code_commune'    => $codeCommune,
        'radon'           => is_array($radonData) ? [
            'classe_potentiel' => $radonData['classe_potentiel'] ?? null,
            'raw_json'         => $radonData,
        ] : null,
        'zone_sismique'   => is_array($sismiqueData) ? [
            'code_zone'      => $sismiqueData['code_zone'] ?? null,
            'zone_sismicite' => $sismiqueData['zone_sismicite'] ?? null,
            'raw_json'       => $sismiqueData,
        ] : null,
        'mouvements_terrain' => $mouvements,
        'source'          => 'georisques_brgm',
    ];
}

// ============================================================
// RNB — Referentiel National des Batiments
// rnb-api.beta.gouv.fr/api/alpha/buildings/
// Parametre confirme : bbox=minLon,minLat,maxLon,maxLat
// delta de ~50m : +/-0.0005 lat, +/-0.0007 lon a Paris.
// ============================================================

function live_rnb_by_location(float $lat, float $lon, float $delta = 0.0006): array
{
    $bbox = implode(',', [
        round($lon - $delta, 7),
        round($lat - $delta, 7),
        round($lon + $delta, 7),
        round($lat + $delta, 7),
    ]);
    $url = 'https://rnb-api.beta.gouv.fr/api/alpha/buildings/?' . http_build_query([
        'bbox'  => $bbox,
        'limit' => 20,
    ]);
    $payload = http_json_safe($url);
    if (!is_array($payload)) {
        return [];
    }

    $rows = [];
    foreach (($payload['results'] ?? []) as $building) {
        if (!is_array($building)) {
            continue;
        }
        $addresses = is_array($building['addresses'] ?? null) ? $building['addresses'] : [];
        $addr      = is_array($addresses[0] ?? null) ? $addresses[0] : [];
        $point     = $building['point'] ?? $building['geometry'] ?? [];
        $coords    = $point['coordinates'] ?? [];

        $rows[] = [
            'rnb_id'    => $building['rnb_id'] ?? null,
            'statut'    => $building['status'] ?? null,
            'is_active' => $building['is_active'] ?? null,
            'latitude'  => isset($coords[1]) ? (float) $coords[1] : null,
            'longitude' => isset($coords[0]) ? (float) $coords[0] : null,
            'adresse'   => trim(implode(' ', array_filter([
                $addr['street_number'] ?? null,
                $addr['street_name']   ?? null,
                $addr['city']          ?? null,
            ]))),
            'source'    => 'rnb_beta_gouv',
            'raw_json'  => $building,
        ];
    }

    return $rows;
}

// ============================================================
// BDNB Open — Base de Donnees Nationale des Batiments (CSTB)
// api.bdnb.io — requiert une inscription/token (HTTP 403 sans).
// Fonction presente mais retourne [] si l'acces est refuse.
// Enregistrement gratuit sur https://www.bdnb.io/
// ============================================================

function live_bdnb_by_address(string $address, string $codeCommune = ''): array
{
    // L'API BDNB retourne 403 sans token - placeholder pour connexion future.
    // Pour activer : obtenir un token sur https://www.bdnb.io/ et l'ajouter
    // aux headers via curl_setopt(CURLOPT_HTTPHEADER, ['Authorization: Bearer <token>']).
    return [];
}

// ============================================================
// GPU — Geoportail de l'Urbanisme via APICarto IGN
// apicarto.ign.fr/api/gpu/zone-urba
// Le parametre geom doit etre passe sans encodage http_build_query
// (le serveur refuse les crochets/accolades encodes).
// ============================================================

function live_gpu_by_location(float $lat, float $lon): array
{
    $geom = json_encode(['type' => 'Point', 'coordinates' => [$lon, $lat]]);
    $url  = 'https://apicarto.ign.fr/api/gpu/zone-urba?geom=' . $geom;
    $payload = http_json_safe($url);
    if (!is_array($payload)) {
        return [];
    }

    $rows = [];
    foreach (($payload['features'] ?? []) as $feature) {
        if (!is_array($feature)) {
            continue;
        }
        $props  = $feature['properties'] ?? [];
        $rows[] = [
            'type_zone'             => $props['typezone'] ?? null,
            'libelle'               => $props['libelle'] ?? null,
            'libelle_long'          => $props['libelong'] ?? null,
            'destination_dominante' => $props['destdomi'] ?? null,
            'nom_reglement'         => $props['nomfic'] ?? null,
            'url_reglement'         => $props['urlfic'] ?? null,
            'id_urba'               => $props['idurba'] ?? null,
            'partition'             => $props['partition'] ?? null,
            'source'                => 'gpu_apicarto_ign',
            'raw_json'              => $feature,
        ];
    }

    return $rows;
}

// ============================================================
// DPE logements neufs — ADEME
// Dataset confirme : dpe02neuf (pas dpe-v2-logements-neufs)
// Champs identiques a dpe03existant.
// ============================================================

function live_dpe_neufs_by_address(string $address, int $limit = 20): array
{
    $address = trim($address);
    if ($address === '') {
        return [];
    }

    $url = 'https://data.ademe.fr/data-fair/api/v1/datasets/dpe02neuf/lines?' . http_build_query([
        'size'   => min(50, max($limit * 3, 10)),
        'count'  => 'false',
        'q'      => $address,
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
            'conso_5_usages_par_m2_ep',
            'emission_ges_5_usages_par_m2',
            'type_energie_principale_chauffage',
            'type_installation_chauffage',
            'periode_construction',
        ]),
    ]);
    $payload = http_json_safe($url);
    if (!is_array($payload)) {
        return [];
    }

    $wanted = normalize_search_text($address);
    $rows   = [];
    foreach (($payload['results'] ?? []) as $row) {
        if (!is_array($row)) {
            continue;
        }
        $rowAddr = normalize_search_text((string) ($row['adresse_ban'] ?? ''));
        if ($wanted !== '' && $rowAddr !== '' && !str_contains($rowAddr, $wanted) && !str_contains($wanted, $rowAddr)) {
            continue;
        }
        $rows[] = [
            'identifiant_dpe'             => $row['numero_dpe'] ?? null,
            'source'                      => 'dpe_logements_neufs_ademe',
            'date_etablissement_dpe'      => $row['date_etablissement_dpe'] ?? null,
            'date_reception_dpe'          => $row['date_reception_dpe'] ?? null,
            'classe_bilan_dpe'            => $row['etiquette_dpe'] ?? null,
            'classe_emission_ges'         => $row['etiquette_ges'] ?? null,
            'type_batiment_dpe'           => $row['type_batiment'] ?? null,
            'surface_habitable_logement'  => decimal_value($row['surface_habitable_logement'] ?? null),
            'conso_5_usages_ep_m2'        => decimal_value($row['conso_5_usages_par_m2_ep'] ?? null),
            'emission_ges_5_usages_m2'    => decimal_value($row['emission_ges_5_usages_par_m2'] ?? null),
            'type_energie_chauffage'      => $row['type_energie_principale_chauffage'] ?? null,
            'type_installation_chauffage' => $row['type_installation_chauffage'] ?? null,
            'periode_construction_dpe'    => $row['periode_construction'] ?? null,
            'adresse'                     => $row['adresse_ban'] ?? null,
            'raw_json'                    => $row,
        ];
        if (count($rows) >= $limit) {
            break;
        }
    }

    return $rows;
}

// ============================================================
// DPE batiments tertiaires — ADEME
// Dataset confirme : dpe-tertiaire
// Schema different de DPE logements :
//   - pas de numero_dpe → utilise id
//   - adresse dans geo_adresse (pas adresse_ban)
//   - classe energie : classe_consommation_energie / classe_estimation_ges
//   - consommation : consommation_energie (kWh/m2/an)
// ============================================================

function live_dpe_tertiaire_by_address(string $address, int $limit = 20): array
{
    $address = trim($address);
    if ($address === '') {
        return [];
    }

    $url = 'https://data.ademe.fr/data-fair/api/v1/datasets/dpe-tertiaire/lines?' . http_build_query([
        'size'   => min(50, max($limit * 3, 10)),
        'count'  => 'false',
        'q'      => $address,
        'select' => implode(',', [
            'id',
            'geo_adresse',
            'nom_rue',
            'date_etablissement_dpe',
            'date_reception_dpe',
            'tr002_type_batiment_description',
            'partie_batiment',
            'surface_utile',
            'surface_habitable',
            'classe_consommation_energie',
            'classe_estimation_ges',
            'consommation_energie',
            'latitude',
            'longitude',
        ]),
    ]);
    $payload = http_json_safe($url);
    if (!is_array($payload)) {
        return [];
    }

    $wanted = normalize_search_text($address);
    $rows   = [];
    foreach (($payload['results'] ?? []) as $row) {
        if (!is_array($row)) {
            continue;
        }
        $rowAddr = normalize_search_text((string) ($row['geo_adresse'] ?? $row['nom_rue'] ?? ''));
        if ($wanted !== '' && $rowAddr !== '' && !str_contains($rowAddr, $wanted) && !str_contains($wanted, $rowAddr)) {
            continue;
        }
        $rows[] = [
            'identifiant_dpe'           => $row['id'] ?? null,
            'source'                    => 'dpe_tertiaire_ademe',
            'date_etablissement_dpe'    => $row['date_etablissement_dpe'] ?? null,
            'date_reception_dpe'        => $row['date_reception_dpe'] ?? null,
            'classe_bilan_dpe'          => $row['classe_consommation_energie'] ?? null,
            'classe_emission_ges'       => $row['classe_estimation_ges'] ?? null,
            'type_batiment_dpe'         => $row['tr002_type_batiment_description'] ?? null,
            'partie_batiment'           => $row['partie_batiment'] ?? null,
            'surface_utile'             => decimal_value($row['surface_utile'] ?? null),
            'surface_habitable'         => decimal_value($row['surface_habitable'] ?? null),
            'consommation_energie_m2'   => decimal_value($row['consommation_energie'] ?? null),
            'latitude'                  => decimal_value($row['latitude'] ?? null),
            'longitude'                 => decimal_value($row['longitude'] ?? null),
            'adresse'                   => $row['geo_adresse'] ?? $row['nom_rue'] ?? null,
            'raw_json'                  => $row,
        ];
        if (count($rows) >= $limit) {
            break;
        }
    }

    return $rows;
}

// ============================================================
// API Decoupage Administratif — geo.api.gouv.fr
// Commune, departement, region par coordonnees.
// ============================================================

function live_commune_by_location(float $lat, float $lon): ?array
{
    $url = 'https://geo.api.gouv.fr/communes?' . http_build_query([
        'lat'    => $lat,
        'lon'    => $lon,
        'fields' => 'nom,code,codesPostaux,codeDepartement,codeRegion,population',
        'boost'  => 'population',
        'limit'  => 1,
    ]);
    $payload = http_json_safe($url);
    if (!is_array($payload) || !isset($payload[0]) || !is_array($payload[0])) {
        return null;
    }

    $commune = $payload[0];
    return [
        'code_commune'    => $commune['code'] ?? null,
        'nom_commune'     => $commune['nom'] ?? null,
        'codes_postaux'   => $commune['codesPostaux'] ?? [],
        'code_departement' => $commune['codeDepartement'] ?? null,
        'code_region'     => $commune['codeRegion'] ?? null,
        'population'      => $commune['population'] ?? null,
        'source'          => 'geo_api_gouv',
        'raw_json'        => $commune,
    ];
}

function map_live_company(array $company): array
{
    $siren = (string) ($company['siren'] ?? '');
    $siege = $company['siege'] ?? [];
    return [
        'siren'              => $siren,
        'siren_formate'      => strlen($siren) === 9 ? substr($siren, 0, 3) . ' ' . substr($siren, 3, 3) . ' ' . substr($siren, 6, 3) : $siren,
        'nom_entreprise'     => $company['nom_complet'] ?? $company['nom_raison_sociale'] ?? null,
        'denomination'       => $company['nom_raison_sociale'] ?? null,
        'sigle'              => $company['sigle'] ?? null,
        'code_naf'           => $company['activite_principale'] ?? null,
        'libelle_naf'        => $company['libelle_activite_principale'] ?? null,
        'categorie_juridique'=> $company['nature_juridique'] ?? null,
        'entreprise_cessee'  => ($company['etat_administratif'] ?? null) === 'C',
        'etat_administratif' => $company['etat_administratif'] ?? null,
        'date_creation'      => iso_date($company['date_creation'] ?? null),
        'date_cessation'     => iso_date($company['date_fermeture'] ?? null),
        'tranche_effectif'   => $company['tranche_effectif_salarie'] ?? null,
        'annee_effectif'     => $company['annee_tranche_effectif_salarie'] ?? null,
        'nombre_etablissements'        => $company['nombre_etablissements'] ?? null,
        'nombre_etablissements_ouverts'=> $company['nombre_etablissements_ouverts'] ?? null,
        'siege' => [
            'siret'       => $siege['siret'] ?? null,
            'adresse'     => $siege['adresse'] ?? null,
            'code_postal' => $siege['code_postal'] ?? null,
            'ville'       => $siege['libelle_commune'] ?? null,
            'latitude'    => isset($siege['latitude'])  ? (float) $siege['latitude']  : null,
            'longitude'   => isset($siege['longitude']) ? (float) $siege['longitude'] : null,
        ],
        'siege_json'         => $siege ?: null,
        'raw_json'           => $company,
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

// ── Recherche entreprises avec filtres ────────────────────────────────────────

// ============================================================
// IMMOBILIER — recherche par commune
// Sources : DVF (mutations), RNIC (copropriétés), DPE (ADEME)
// ============================================================

/**
 * Router principal — dispatche vers DVF, RNIC ou DPE selon $filters['source'].
 * Filtres communs :
 *   source          dvf (défaut) | rnic | dpe
 *   code_commune    code INSEE 5 chiffres (ex: 75111)
 *   code_postal     alternative à code_commune (ex: 75011)
 *   departement     code département (ex: 75) — déduit de code_commune si absent
 * Filtres DVF :
 *   type_bien       Appartement | Maison | Local | Dépendance
 *   surface_min / surface_max  (m²)
 *   prix_min / prix_max        (€)
 *   annee                      ex: 2023
 * Filtres DPE :
 *   classe_dpe      A-G
 *   type_batiment   appartement | maison | immeuble
 *   surface_min / surface_max
 */
function live_search_immobilier(array $filters = [], int $page = 1, int $perPage = 10): array
{
    $perPage = max(1, min(50, $perPage));
    $page    = max(1, $page);

    $source      = strtolower($filters['source'] ?? 'dvf');
    $codeCommune = preg_replace('/\D/', '', $filters['code_commune'] ?? '');
    $codePostal  = preg_replace('/\D/', '', $filters['code_postal'] ?? '');

    // Déduit le département depuis le code commune ou postal
    $dept = preg_replace('/\D/', '', $filters['departement'] ?? '');
    if ($dept === '' && $codeCommune !== '') {
        $dept = substr($codeCommune, 0, 2);
    } elseif ($dept === '' && $codePostal !== '') {
        $dept = substr($codePostal, 0, 2);
    }

    if ($codeCommune === '' && $codePostal === '' && $dept === '') {
        return ['total' => 0, 'page' => $page, 'par_page' => $perPage, 'resultats' => [], 'source' => null];
    }

    return match ($source) {
        'rnic' => live_immo_rnic($codeCommune, $codePostal, $perPage, $page),
        'dpe'  => live_immo_dpe($codePostal, $codeCommune, $filters, $perPage, $page),
        default => live_immo_dvf($dept, $codeCommune, $filters, $perPage, $page),
    };
}

/**
 * DVF — ventes foncières par commune.
 * Tente d'abord le fichier commune (non compressé, plus léger).
 * Fallback sur le fichier département compressé filtré par code_commune.
 */
function live_immo_dvf(string $dept, string $codeCommune, array $filters, int $perPage, int $page): array
{
    if ($dept === '') {
        return ['total' => 0, 'page' => $page, 'par_page' => $perPage, 'resultats' => [], 'source' => null];
    }

    $annee      = (int) ($filters['annee'] ?? (date('Y') - 1));
    $annee      = max(2019, min((int) date('Y') - 1, $annee));

    $offset     = ($page - 1) * $perPage;
    $need       = $offset + $perPage;

    // Essai fichier commune (non compressé, ~100–500 Ko vs ~50 Mo pour le dept)
    $communeUrl = sprintf(
        'https://files.data.gouv.fr/geo-dvf/latest/csv/%d/communes/%s.csv',
        $annee,
        rawurlencode($codeCommune !== '' ? $codeCommune : $dept)
    );
    $deptUrl = sprintf(
        'https://files.data.gouv.fr/geo-dvf/latest/csv/%d/departements/%s.csv.gz',
        $annee,
        rawurlencode($dept)
    );

    // Vérifie si le fichier commune existe (HEAD rapide)
    $useCommune = false;
    $communeFilter = '';
    if ($codeCommune !== '') {
        $head = @get_headers($communeUrl, true);
        $useCommune = $head && str_contains((string) ($head[0] ?? ''), '200');
    }

    $rows = [];
    if ($useCommune) {
        $rows = stream_dvf_filtered($communeUrl, '', $filters, $need, false);
        $sourceUrl = $communeUrl;
    } else {
        $rows = stream_dvf_filtered($deptUrl, $codeCommune, $filters, $need, true);
        $sourceUrl = $deptUrl;
    }

    $total     = count($rows);
    $resultats = array_slice($rows, $offset, $perPage);

    return [
        'total'     => $total,
        'page'      => $page,
        'par_page'  => $perPage,
        'resultats' => array_values(array_map('map_live_dvf_sale', $resultats)),
        'source'    => $sourceUrl,
    ];
}

/**
 * Stream DVF CSV (compressé ou non), filtre par commune et critères.
 * Collecte jusqu'à $maxRows résultats puis s'arrête.
 */
function stream_dvf_filtered(string $url, string $codeCommune, array $filters, int $maxRows, bool $gzipped): array
{
    $cmd = $gzipped
        ? 'curl -L -sS --max-time 30 ' . escapeshellarg($url) . ' | gzip -dc 2>/dev/null'
        : 'curl -L -sS --max-time 20 ' . escapeshellarg($url);

    $handle = popen($cmd, 'r');
    if (!$handle) {
        return [];
    }

    $typeMap = [
        'appartement' => 'Appartement',
        'maison'      => 'Maison',
        'local'       => 'Local industriel. commercial ou assimilé',
        'dependance'  => 'Dépendance',
    ];
    $typeBien   = !empty($filters['type_bien'])
        ? ($typeMap[strtolower($filters['type_bien'])] ?? $filters['type_bien'])
        : null;
    $surfMin    = isset($filters['surface_min'])  ? (float) $filters['surface_min']  : null;
    $surfMax    = isset($filters['surface_max'])  ? (float) $filters['surface_max']  : null;
    $prixMin    = isset($filters['prix_min'])     ? (float) $filters['prix_min']     : null;
    $prixMax    = isset($filters['prix_max'])     ? (float) $filters['prix_max']     : null;

    $header = null;
    $rows   = [];

    while (($line = fgets($handle)) !== false) {
        $csv = str_getcsv(rtrim($line));
        if ($header === null) {
            $header = $csv;
            continue;
        }
        $row = array_combine($header, array_pad($csv, count($header), null));
        if (!is_array($row)) {
            continue;
        }

        // Filtre commune (si fichier département)
        if ($codeCommune !== '' && ($row['code_commune'] ?? '') !== $codeCommune) {
            continue;
        }
        // Filtre type de bien
        if ($typeBien !== null && ($row['type_local'] ?? '') !== $typeBien) {
            continue;
        }
        // Filtres surface
        $surf = ($row['surface_reelle_bati'] ?? '') !== '' ? (float) $row['surface_reelle_bati'] : null;
        if ($surfMin !== null && ($surf === null || $surf < $surfMin)) {
            continue;
        }
        if ($surfMax !== null && ($surf === null || $surf > $surfMax)) {
            continue;
        }
        // Filtres prix
        $prix = ($row['valeur_fonciere'] ?? '') !== '' ? (float) str_replace(',', '.', $row['valeur_fonciere']) : null;
        if ($prixMin !== null && ($prix === null || $prix < $prixMin)) {
            continue;
        }
        if ($prixMax !== null && ($prix === null || $prix > $prixMax)) {
            continue;
        }

        $rows[] = $row;
        if (count($rows) >= $maxRows) {
            break;
        }
    }
    pclose($handle);

    return $rows;
}

/**
 * RNIC — copropriétés par commune ou code postal.
 */
function live_immo_rnic(string $codeCommune, string $codePostal, int $perPage, int $page): array
{
    $params = [
        'page_size' => $perPage,
        'page'      => $page,
    ];

    if ($codePostal !== '') {
        $params['adresse_reference__contains'] = $codePostal;
    } elseif ($codeCommune !== '') {
        // Colonne 'commune' dans RNIC contient le code INSEE (ex: 75111)
        $params['commune__exact'] = $codeCommune;
    } else {
        return ['total' => 0, 'page' => $page, 'par_page' => $perPage, 'resultats' => [], 'source' => null];
    }

    $rows = tabular_resource_data('3ea8e2c3-0038-464a-b17e-cd5c91f65ce2', $params);

    return [
        'total'     => count($rows),
        'page'      => $page,
        'par_page'  => $perPage,
        'resultats' => array_map('map_live_copro', $rows),
        'source'    => 'tabular-api.data.gouv.fr/rnic',
    ];
}

/**
 * DPE — diagnostics énergetiques par code postal ou commune.
 * Filtres optionnels : classe_dpe (A-G), type_batiment, surface_min/max.
 */
function live_immo_dpe(string $codePostal, string $codeCommune, array $filters, int $perPage, int $page): array
{
    $size   = min(50, $perPage * 3);   // over-fetch pour filtrage local
    $offset = ($page - 1) * $perPage;

    // Construction du filtre de champ ADEME
    $qs = '';
    if ($codePostal !== '') {
        $qs = 'code_postal_ban:"' . $codePostal . '"';
    }

    $q = $codePostal !== '' ? $codePostal : $codeCommune;

    $params = [
        'size'   => $size,
        'count'  => 'false',
        'q'      => $q,
        'select' => implode(',', [
            'numero_dpe', 'adresse_ban', 'code_postal_ban', 'nom_commune_ban',
            'etiquette_dpe', 'etiquette_ges', 'date_etablissement_dpe',
            'type_batiment', 'surface_habitable_logement', 'surface_habitable_immeuble',
            'conso_5_usages_par_m2_ep', 'emission_ges_5_usages_par_m2',
            'type_energie_principale_chauffage', 'periode_construction',
        ]),
    ];

    $url     = 'https://data.ademe.fr/data-fair/api/v1/datasets/dpe03existant/lines?' . http_build_query($params);
    $payload = http_json($url);

    $classeDpe  = strtoupper($filters['classe_dpe'] ?? '');
    $typeBat    = strtolower($filters['type_batiment'] ?? '');
    $surfMin    = isset($filters['surface_min']) ? (float) $filters['surface_min'] : null;
    $surfMax    = isset($filters['surface_max']) ? (float) $filters['surface_max'] : null;

    $all = [];
    foreach (($payload['results'] ?? []) as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($classeDpe !== '' && strtoupper($row['etiquette_dpe'] ?? '') !== $classeDpe) {
            continue;
        }
        if ($typeBat !== '' && strtolower($row['type_batiment'] ?? '') !== $typeBat) {
            continue;
        }
        $surf = ($row['surface_habitable_logement'] ?? '') !== '' ? (float) $row['surface_habitable_logement'] : null;
        if ($surfMin !== null && ($surf === null || $surf < $surfMin)) {
            continue;
        }
        if ($surfMax !== null && ($surf === null || $surf > $surfMax)) {
            continue;
        }
        $all[] = [
            'identifiant_dpe'           => $row['numero_dpe'] ?? null,
            'source'                    => 'dpe_logements_existants_ademe',
            'date_etablissement_dpe'    => $row['date_etablissement_dpe'] ?? null,
            'classe_bilan_dpe'          => $row['etiquette_dpe'] ?? null,
            'classe_emission_ges'       => $row['etiquette_ges'] ?? null,
            'type_batiment_dpe'         => $row['type_batiment'] ?? null,
            'surface_habitable_logement'=> decimal_value($row['surface_habitable_logement'] ?? null),
            'surface_habitable_immeuble'=> decimal_value($row['surface_habitable_immeuble'] ?? null),
            'conso_5_usages_ep_m2'      => decimal_value($row['conso_5_usages_par_m2_ep'] ?? null),
            'emission_ges_5_usages_m2'  => decimal_value($row['emission_ges_5_usages_par_m2'] ?? null),
            'periode_construction_dpe'  => $row['periode_construction'] ?? null,
            'adresse'                   => $row['adresse_ban'] ?? null,
            'code_postal'               => $row['code_postal_ban'] ?? null,
            'commune'                   => $row['nom_commune_ban'] ?? null,
            'raw_json'                  => $row,
        ];
    }

    return [
        'total'     => count($all),
        'page'      => $page,
        'par_page'  => $perPage,
        'resultats' => array_values(array_slice($all, $offset, $perPage)),
        'source'    => $url,
    ];
}

function live_search_entreprises(array $filters = [], int $page = 1, int $perPage = 10): array
{
    $perPage = max(1, min(25, $perPage));
    $page    = max(1, $page);

    $params = ['per_page' => $perPage, 'page' => $page];

    // Texte libre
    if (!empty($filters['q']))             $params['q']                            = $filters['q'];

    // Localisation
    if (!empty($filters['code_postal']))   $params['code_postal']                  = $filters['code_postal'];
    if (!empty($filters['departement']))   $params['departement']                  = $filters['departement'];
    if (!empty($filters['region']))        $params['region']                       = $filters['region'];

    // Activite / type — normalise 6820A → 68.20A si besoin
    if (!empty($filters['code_naf'])) {
        $naf = strtoupper(preg_replace('/\s+/', '', $filters['code_naf']));
        if (strlen($naf) === 5 && !str_contains($naf, '.')) {
            $naf = substr($naf, 0, 2) . '.' . substr($naf, 2);
        }
        $params['activite_principale'] = $naf;
    }
    if (!empty($filters['categorie_juridique'])) $params['categorie_juridique_sirene'] = $filters['categorie_juridique'];
    if (!empty($filters['tranche_effectif_salarie'])) $params['tranche_effectif_salarie'] = $filters['tranche_effectif_salarie'];

    // Etat administratif — accepte la valeur directe (A/C) ou le booleen entreprise_cessee
    if (!empty($filters['etat_administratif'])) {
        $params['etat_administratif'] = strtoupper($filters['etat_administratif']);
    } elseif (isset($filters['entreprise_cessee'])) {
        $params['etat_administratif'] = $filters['entreprise_cessee'] ? 'C' : 'A';
    }

    // Booleens
    if (isset($filters['est_entrepreneur_individuel'])) {
        $params['est_entrepreneur_individuel'] = $filters['est_entrepreneur_individuel'] ? 'true' : 'false';
    }
    if (isset($filters['est_association'])) {
        $params['est_association'] = $filters['est_association'] ? 'true' : 'false';
    }
    if (isset($filters['est_organisme_formation'])) {
        $params['est_organisme_formation'] = $filters['est_organisme_formation'] ? 'true' : 'false';
    }

    // Aucun critere renseigne → retour vide
    if (count($params) <= 2) {
        return ['total' => 0, 'page' => $page, 'par_page' => $perPage, 'resultats' => [], 'source' => null];
    }

    $url     = 'https://recherche-entreprises.api.gouv.fr/search?' . http_build_query($params);
    $payload = http_json($url);

    $resultats = [];
    foreach (($payload['results'] ?? []) as $company) {
        if (!is_array($company) || empty($company['siren'])) {
            continue;
        }
        $resultats[] = map_live_company($company);
    }

    return [
        'total'     => (int) ($payload['total_results'] ?? count($resultats)),
        'page'      => $page,
        'par_page'  => $perPage,
        'resultats' => $resultats,
        'source'    => $url,
    ];
}
