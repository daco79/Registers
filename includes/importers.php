<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function http_json(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_USERAGENT => 'Registers-local/0.1',
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === false || $status >= 400) {
        throw new RuntimeException($error ?: "HTTP {$status}");
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Reponse JSON invalide.');
    }

    return $decoded;
}

function http_json_safe(string $url): ?array
{
    try {
        return http_json($url);
    } catch (Throwable) {
        return null;
    }
}

function iso_date(mixed $value): ?string
{
    if (!is_string($value) || trim($value) === '') {
        return null;
    }
    return substr($value, 0, 10);
}

function json_value(mixed $value): ?string
{
    if ($value === null) {
        return null;
    }
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function bool_value(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }
    if (is_bool($value)) {
        return $value ? 1 : 0;
    }
    return in_array(strtoupper((string) $value), ['1', 'O', 'Y', 'YES', 'TRUE', 'A'], true) ? 1 : 0;
}

function decimal_value(mixed $value): mixed
{
    if ($value === null || $value === '') {
        return null;
    }
    return is_numeric($value) ? $value : null;
}

function insert_import_log(PDO $pdo, array $data): int
{
    $stmt = $pdo->prepare("
        INSERT INTO import_logs
            (domain, source_tool, source_file, query_json, return_fields, response_hash, rows_seen, rows_inserted, rows_updated, notes)
        VALUES
            (:domain, :source_tool, :source_file, :query_json, :return_fields, :response_hash, :rows_seen, :rows_inserted, :rows_updated, :notes)
    ");
    $stmt->execute([
        'domain' => $data['domain'],
        'source_tool' => $data['source_tool'] ?? null,
        'source_file' => $data['source_file'] ?? null,
        'query_json' => json_value($data['query_json'] ?? null),
        'return_fields' => json_value($data['return_fields'] ?? null),
        'response_hash' => $data['response_hash'] ?? null,
        'rows_seen' => $data['rows_seen'] ?? null,
        'rows_inserted' => $data['rows_inserted'] ?? null,
        'rows_updated' => $data['rows_updated'] ?? null,
        'notes' => $data['notes'] ?? null,
    ]);
    return (int) $pdo->lastInsertId();
}

function insert_raw_payload(PDO $pdo, int $importLogId, string $domain, string $entityType, ?string $entityKey, string $source, array $payload): void
{
    $raw = json_value($payload);
    $stmt = $pdo->prepare("
        INSERT INTO raw_payloads
            (import_log_id, domain, entity_type, entity_key, source_file, payload_hash, raw_json)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$importLogId, $domain, $entityType, $entityKey, $source, hash('sha256', (string) $raw), $raw]);
}

function upsert_row(PDO $pdo, string $table, array $row, array $updateColumns): void
{
    $columns = array_keys($row);
    $quoted = array_map(fn ($col) => "`{$col}`", $columns);
    $placeholders = array_map(fn ($col) => ":{$col}", $columns);
    $updates = array_map(fn ($col) => "`{$col}` = VALUES(`{$col}`)", $updateColumns);

    $sql = sprintf(
        'INSERT INTO `%s` (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s',
        $table,
        implode(', ', $quoted),
        implode(', ', $placeholders),
        implode(', ', $updates)
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute($row);
}

function import_company_from_annuaire(PDO $pdo, string $identifier): array
{
    $identifier = preg_replace('/\D+/', '', $identifier);
    if (!in_array(strlen($identifier), [9, 14], true)) {
        throw new InvalidArgumentException('Identifiant SIREN ou SIRET invalide.');
    }

    $url = 'https://recherche-entreprises.api.gouv.fr/search?' . http_build_query([
        'q' => $identifier,
        'per_page' => 1,
    ]);
    $payload = http_json($url);
    $company = $payload['results'][0] ?? null;
    if (!is_array($company) || empty($company['siren'])) {
        throw new RuntimeException('Aucune entreprise trouvee.');
    }

    $pdo->beginTransaction();
    try {
        $logId = insert_import_log($pdo, [
            'domain' => 'entreprise',
            'source_tool' => 'api_recherche_entreprises',
            'source_file' => $url,
            'query_json' => ['identifier' => $identifier],
            'response_hash' => hash('sha256', json_value($payload) ?? ''),
            'rows_seen' => (int) ($payload['total_results'] ?? 1),
            'notes' => 'Import cible depuis recherche-entreprises.api.gouv.fr',
        ]);

        upsert_company($pdo, $company);
        $siren = (string) $company['siren'];
        insert_raw_payload($pdo, $logId, 'entreprise', 'company', $siren, $url, $company);

        $establishments = collect_establishments($company);
        foreach ($establishments as $establishment) {
            upsert_establishment($pdo, $siren, $establishment);
            insert_raw_payload($pdo, $logId, 'entreprise', 'establishment', $establishment['siret'] ?? null, $url, $establishment);
        }

        import_representatives($pdo, $siren, $company['dirigeants'] ?? []);
        import_finances($pdo, $siren, $company['finances'] ?? []);

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return [
        'siren' => $siren,
        'title' => $company['nom_complet'] ?? $company['nom_raison_sociale'] ?? $siren,
        'establishments' => count($establishments),
        'representatives' => count($company['dirigeants'] ?? []),
        'finances' => count($company['finances'] ?? []),
        'source' => $url,
    ];
}

function upsert_company(PDO $pdo, array $company): void
{
    $siren = (string) $company['siren'];
    $complements = is_array($company['complements'] ?? null) ? $company['complements'] : [];

    $row = [
        'siren' => $siren,
        'siren_formate' => substr($siren, 0, 3) . ' ' . substr($siren, 3, 3) . ' ' . substr($siren, 6, 3),
        'diffusable' => bool_value($company['statut_diffusion'] ?? null),
        'nom_entreprise' => $company['nom_complet'] ?? $company['nom_raison_sociale'] ?? null,
        'personne_morale' => empty($company['nom']) ? 1 : null,
        'denomination' => $company['nom_raison_sociale'] ?? null,
        'sigle' => $company['sigle'] ?? null,
        'code_naf' => $company['activite_principale'] ?? null,
        'nomenclature_code_naf' => $company['section_activite_principale'] ?? null,
        'categorie_juridique' => $company['nature_juridique'] ?? null,
        'forme_juridique' => $company['nature_juridique'] ?? null,
        'entreprise_cessee' => ($company['etat_administratif'] ?? null) === 'C' ? 1 : 0,
        'date_creation' => iso_date($company['date_creation'] ?? null),
        'date_cessation' => iso_date($company['date_fermeture'] ?? null),
        'entreprise_employeuse' => bool_value($company['caractere_employeur'] ?? null),
        'societe_a_mission' => bool_value($complements['est_societe_mission'] ?? null),
        'economie_sociale_solidaire' => bool_value($complements['est_ess'] ?? null),
        'tranche_effectif' => $company['tranche_effectif_salarie'] ?? null,
        'annee_effectif' => $company['annee_tranche_effectif_salarie'] ?? null,
        'statut_consolide' => $company['etat_administratif'] ?? null,
        'date_debut_activite' => iso_date($company['siege']['date_debut_activite'] ?? null),
        'derniere_mise_a_jour_sirene' => iso_date($company['date_mise_a_jour_insee'] ?? null),
        'derniere_mise_a_jour_rcs' => iso_date($company['date_mise_a_jour_rne'] ?? null),
        'siege_json' => json_value($company['siege'] ?? null),
        'labels' => json_value($complements),
        'raw_json' => json_value($company),
    ];

    upsert_row($pdo, 'entreprises', $row, array_diff(array_keys($row), ['siren']));
}

function collect_establishments(array $company): array
{
    $bySiret = [];
    foreach ([$company['siege'] ?? null, ...($company['matching_etablissements'] ?? [])] as $establishment) {
        if (is_array($establishment) && !empty($establishment['siret'])) {
            $bySiret[(string) $establishment['siret']] = $establishment;
        }
    }
    return array_values($bySiret);
}

function upsert_establishment(PDO $pdo, string $siren, array $establishment): void
{
    $siret = (string) ($establishment['siret'] ?? '');
    if ($siret === '') {
        return;
    }

    $row = [
        'siret' => $siret,
        'siren' => $siren,
        'siret_formate' => substr($siret, 0, 3) . ' ' . substr($siret, 3, 3) . ' ' . substr($siret, 6, 3) . ' ' . substr($siret, 9, 5),
        'nic' => substr($siret, 9, 5),
        'siege' => bool_value($establishment['est_siege'] ?? null),
        'numero_voie' => $establishment['numero_voie'] ?? null,
        'indice_repetition' => $establishment['indice_repetition'] ?? null,
        'type_voie' => $establishment['type_voie'] ?? null,
        'libelle_voie' => $establishment['libelle_voie'] ?? null,
        'complement_adresse' => $establishment['complement_adresse'] ?? null,
        'adresse_ligne_1' => $establishment['adresse'] ?? null,
        'code_postal' => $establishment['code_postal'] ?? null,
        'code_commune' => $establishment['commune'] ?? null,
        'ville' => $establishment['libelle_commune'] ?? null,
        'pays' => $establishment['libelle_pays_etranger'] ?? 'France',
        'code_pays' => $establishment['code_pays_etranger'] ?? 'FR',
        'latitude' => decimal_value($establishment['latitude'] ?? null),
        'longitude' => decimal_value($establishment['longitude'] ?? null),
        'code_naf' => $establishment['activite_principale'] ?? null,
        'code_naf_2025' => $establishment['activite_principale_naf25'] ?? null,
        'etablissement_employeur' => bool_value($establishment['caractere_employeur'] ?? null),
        'tranche_effectif' => $establishment['tranche_effectif_salarie'] ?? null,
        'annee_effectif' => $establishment['annee_tranche_effectif_salarie'] ?? null,
        'date_de_creation' => iso_date($establishment['date_creation'] ?? null),
        'date_debut_activite' => iso_date($establishment['date_debut_activite'] ?? null),
        'etablissement_cesse' => ($establishment['etat_administratif'] ?? null) === 'F' ? 1 : 0,
        'date_cessation' => iso_date($establishment['date_fermeture'] ?? null),
        'enseigne' => is_array($establishment['liste_enseignes'] ?? null) ? implode(' / ', $establishment['liste_enseignes']) : null,
        'nom_commercial' => $establishment['nom_commercial'] ?? null,
        'departement' => $establishment['departement'] ?? null,
        'code_departement' => $establishment['departement'] ?? null,
        'region' => $establishment['region'] ?? null,
        'code_region' => $establishment['region'] ?? null,
        'labels' => json_value([
            'finess' => $establishment['liste_finess'] ?? null,
            'rge' => $establishment['liste_rge'] ?? null,
            'idcc' => $establishment['liste_idcc'] ?? null,
        ]),
        'raw_json' => json_value($establishment),
    ];

    upsert_row($pdo, 'etablissements', $row, array_diff(array_keys($row), ['siret']));
}

function import_representatives(PDO $pdo, string $siren, array $representatives): void
{
    $pdo->prepare('DELETE FROM representants WHERE siren_entreprise = ?')->execute([$siren]);
    $stmt = $pdo->prepare("
        INSERT INTO representants
            (siren_entreprise, personne_morale, qualite, actuel, nom, prenom, nom_complet, date_de_naissance,
             nationalite, siren_representant, denomination_representant, raw_json)
        VALUES
            (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($representatives as $representative) {
        if (!is_array($representative)) {
            continue;
        }
        $nom = $representative['nom'] ?? null;
        $prenom = $representative['prenoms'] ?? null;
        $denomination = $representative['denomination'] ?? null;
        $stmt->execute([
            $siren,
            ($representative['type_dirigeant'] ?? null) === 'personne morale' ? 1 : 0,
            $representative['qualite'] ?? null,
            $nom,
            $prenom,
            trim((string) ($prenom ? "{$prenom} " : '') . (string) ($nom ?? $denomination ?? '')),
            $representative['date_de_naissance'] ?? null,
            $representative['nationalite'] ?? null,
            $representative['siren'] ?? null,
            $denomination,
            json_value($representative),
        ]);
    }
}

function import_finances(PDO $pdo, string $siren, array $finances): void
{
    $pdo->prepare('DELETE FROM finances_annuelles WHERE siren = ?')->execute([$siren]);
    $stmt = $pdo->prepare("
        INSERT INTO finances_annuelles
            (siren, annee, chiffre_affaires, resultat, raw_json)
        VALUES
            (?, ?, ?, ?, ?)
    ");

    foreach ($finances as $year => $row) {
        if (!is_array($row) || !ctype_digit((string) $year)) {
            continue;
        }
        $stmt->execute([
            $siren,
            (int) $year,
            decimal_value($row['ca'] ?? null),
            decimal_value($row['resultat_net'] ?? null),
            json_value($row),
        ]);
    }
}

function import_geocode_from_ban(PDO $pdo, string $query): array
{
    $query = trim($query);
    if ($query === '') {
        throw new InvalidArgumentException('Adresse manquante.');
    }

    $url = 'https://api-adresse.data.gouv.fr/search/?' . http_build_query([
        'q' => $query,
        'limit' => 1,
    ]);
    $payload = http_json($url);
    $feature = $payload['features'][0] ?? null;
    if (!is_array($feature)) {
        throw new RuntimeException('Adresse introuvable.');
    }

    $props = $feature['properties'] ?? [];
    $coords = $feature['geometry']['coordinates'] ?? [null, null];

    $pdo->beginTransaction();
    try {
        $logId = insert_import_log($pdo, [
            'domain' => 'immobilier',
            'source_tool' => 'api_adresse_ban',
            'source_file' => $url,
            'query_json' => ['q' => $query],
            'response_hash' => hash('sha256', json_value($payload) ?? ''),
            'rows_seen' => count($payload['features'] ?? []),
            'rows_inserted' => 1,
            'notes' => 'Geocodage cible depuis api-adresse.data.gouv.fr',
        ]);

        $stmt = $pdo->prepare("
            INSERT INTO lieux_geocodes
                (query_text, label, type_lieu, numero, rue, ville, code_postal, code_commune, contexte,
                 latitude, longitude, municipalite, raw_json)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $query,
            $props['label'] ?? null,
            $props['type'] ?? null,
            $props['housenumber'] ?? null,
            $props['street'] ?? $props['name'] ?? null,
            $props['city'] ?? null,
            $props['postcode'] ?? null,
            $props['citycode'] ?? null,
            $props['context'] ?? null,
            decimal_value($coords[1] ?? null),
            decimal_value($coords[0] ?? null),
            $props['district'] ?? null,
            json_value($feature),
        ]);
        insert_raw_payload($pdo, $logId, 'immobilier', 'geocode', $query, $url, $feature);
        $id = (int) $pdo->lastInsertId();

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return [
        'id' => $id,
        'label' => $props['label'] ?? null,
        'latitude' => $coords[1] ?? null,
        'longitude' => $coords[0] ?? null,
        'source' => $url,
    ];
}

// ── Pappers Immobilier ────────────────────────────────────────────────────────

function http_json_bearer(string $url, string $token): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_TIMEOUT         => 30,
        CURLOPT_CONNECTTIMEOUT  => 8,
        CURLOPT_FOLLOWLOCATION  => true,
        CURLOPT_HTTPHEADER      => [
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ],
        CURLOPT_USERAGENT       => 'Registers-local/0.1',
    ]);

    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error  = curl_error($ch);
    curl_close($ch);

    if ($body === false || $status >= 400) {
        throw new RuntimeException($error ?: "HTTP {$status} — {$url}");
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        throw new RuntimeException('Reponse JSON invalide.');
    }

    return $decoded;
}

function upsert_parcelle(PDO $pdo, array $p): string
{
    $numero = (string) ($p['numero'] ?? '');
    if ($numero === '') {
        throw new InvalidArgumentException('Parcelle sans numero.');
    }

    $adresse = is_array($p['adresse'] ?? null)
        ? json_value($p['adresse'])
        : ($p['adresse'] ?? null);

    $proprietaires = $p['proprietaires'] ?? [];
    $nbIdentifies  = count(array_filter($proprietaires, fn ($pr) => !empty($pr['siren'])));
    $profilProp    = match (true) {
        !isset($p['proprietaires'])          => null,
        $nbIdentifies === 0 && empty(array_filter($proprietaires, fn ($pr) => !empty($pr))) => 'particulier',
        $nbIdentifies === 0                  => 'particulier',
        $nbIdentifies === 1                  => 'societe',
        default                              => 'indivision',
    };

    upsert_row($pdo, 'parcelles', [
        'numero'              => $numero,
        'parcelle_cadastrale' => $p['parcelle_cadastrale'] ?? null,
        'geometrie'           => json_value($p['geometrie'] ?? null),
        'prefixe'             => $p['prefixe'] ?? null,
        'section'             => $p['section'] ?? null,
        'numero_plan'         => $p['numero_plan'] ?? null,
        'adresse'             => $adresse,
        'code_commune'        => $p['code_commune'] ?? null,
        'commune'             => $p['commune'] ?? null,
        'code_departement'    => $p['code_departement'] ?? null,
        'departement'         => $p['departement'] ?? null,
        'code_region'         => $p['code_region'] ?? null,
        'region'              => $p['region'] ?? null,
        'codes_postaux'       => json_value($p['codes_postaux'] ?? null),
        'contenance'          => decimal_value($p['contenance'] ?? null),
        'arpente'             => bool_value($p['arpente'] ?? null),
        'bounding_box'        => json_value($p['bounding_box'] ?? null),
        'surface_batie'                => decimal_value($p['surface_batie'] ?? null),
        'surface_disponible'           => decimal_value($p['surface_disponible'] ?? null),
        'nb_proprietaires_identifies'  => isset($p['proprietaires']) ? $nbIdentifies : null,
        'profil_proprietaire'          => $profilProp,
        'statistiques'                 => json_value($p['statistiques'] ?? null),
        'raw_json'                     => json_value($p),
    ], [
        'parcelle_cadastrale', 'geometrie', 'prefixe', 'section', 'numero_plan', 'adresse',
        'code_commune', 'commune', 'code_departement', 'departement',
        'code_region', 'region', 'codes_postaux', 'contenance', 'arpente',
        'bounding_box', 'surface_batie', 'surface_disponible',
        'nb_proprietaires_identifies', 'profil_proprietaire', 'statistiques', 'raw_json',
    ]);

    return $numero;
}

function upsert_parcelle_proprietaire(PDO $pdo, string $parcelle_numero, array $prop): int
{
    $siren = (isset($prop['siren']) && $prop['siren'] !== '') ? (string) $prop['siren'] : null;

    if ($siren !== null) {
        $chk = $pdo->prepare('SELECT id FROM parcelle_proprietaires WHERE parcelle_numero = ? AND siren = ? LIMIT 1');
        $chk->execute([$parcelle_numero, $siren]);
    } else {
        $nom = $prop['nom_entreprise'] ?? $prop['denomination'] ?? null;
        $chk = $pdo->prepare('SELECT id FROM parcelle_proprietaires WHERE parcelle_numero = ? AND nom_entreprise = ? AND siren IS NULL LIMIT 1');
        $chk->execute([$parcelle_numero, $nom]);
    }

    $existing = $chk->fetchColumn();

    $row = [
        'parcelle_numero'                 => $parcelle_numero,
        'siren'                           => $siren,
        'nom_entreprise'                  => $prop['nom_entreprise'] ?? null,
        'denomination'                    => $prop['denomination'] ?? null,
        'personne_physique'               => bool_value(empty($siren) || !empty($prop['personnes_physiques'])),
        'date_creation'                   => iso_date($prop['date_creation'] ?? null),
        'tranche_effectifs'               => $prop['tranche_effectifs'] ?? null,
        'annee_effectifs'                 => isset($prop['annee_effectifs']) ? (int) $prop['annee_effectifs'] : null,
        'categorie_juridique'             => $prop['categorie_juridique'] ?? null,
        'activite_principale'             => $prop['activite_principale'] ?? null,
        'employeur'                       => bool_value($prop['employeur'] ?? null),
        'cessation_activite'              => bool_value($prop['cessation_activite'] ?? null),
        'monoproprietaire'                => bool_value($prop['monoproprietaire'] ?? null),
        'proprietaire_occupant'           => bool_value($prop['proprietaire_occupant'] ?? null),
        'lmnp'                            => bool_value($prop['lmnp'] ?? null),
        'siege'                           => json_value($prop['siege'] ?? null),
        'personnes_physiques'             => json_value($prop['personnes_physiques'] ?? null),
        'representants_personnes_morales' => json_value($prop['representants_personnes_morales'] ?? null),
        'emails'                          => json_value($prop['emails'] ?? null),
        'telephones'                      => json_value($prop['telephones'] ?? null),
        'sites_internet'                  => json_value($prop['sites_internet'] ?? null),
        'lien_linkedin'                   => $prop['lien_linkedin'] ?? null,
        'raw_json'                        => json_value($prop),
    ];

    $updateCols = array_keys(array_diff_key($row, ['parcelle_numero' => 1]));

    if ($existing !== false) {
        $sets = implode(', ', array_map(fn ($c) => "`{$c}` = :{$c}", $updateCols));
        $stmt = $pdo->prepare("UPDATE parcelle_proprietaires SET {$sets}, updated_at = CURRENT_TIMESTAMP WHERE id = :__id");
        $stmt->execute(array_merge($row, ['__id' => $existing]));
        return (int) $existing;
    }

    $cols = implode(', ', array_map(fn ($c) => "`{$c}`", array_keys($row)));
    $phs  = implode(', ', array_map(fn ($c) => ":{$c}", array_keys($row)));
    $pdo->prepare("INSERT INTO parcelle_proprietaires ({$cols}) VALUES ({$phs})")->execute($row);
    return (int) $pdo->lastInsertId();
}

function import_proprietaire_locaux(PDO $pdo, int $prop_id, string $parcelle_numero, array $locaux): void
{
    $pdo->prepare('DELETE FROM parcelle_proprietaire_locaux WHERE parcelle_proprietaire_id = ?')->execute([$prop_id]);

    $stmt = $pdo->prepare('
        INSERT INTO parcelle_proprietaire_locaux
          (parcelle_proprietaire_id, parcelle_numero, code_droit, batiment, entree, niveau, porte,
           numero_voie, nature_voie, nom_voie, departement, raw_json)
        VALUES
          (:prop_id, :parcelle_numero, :code_droit, :batiment, :entree, :niveau, :porte,
           :numero_voie, :nature_voie, :nom_voie, :departement, :raw_json)
    ');

    foreach ($locaux as $local) {
        $stmt->execute([
            ':prop_id'         => $prop_id,
            ':parcelle_numero' => $parcelle_numero,
            ':code_droit'      => $local['code_droit'] ?? null,
            ':batiment'        => $local['batiment'] ?? null,
            ':entree'          => $local['entree'] ?? null,
            ':niveau'          => $local['niveau'] ?? null,
            ':porte'           => $local['porte'] ?? null,
            ':numero_voie'     => $local['numero_voie'] ?? null,
            ':nature_voie'     => $local['nature_voie'] ?? null,
            ':nom_voie'        => $local['nom_voie'] ?? null,
            ':departement'     => $local['departement'] ?? null,
            ':raw_json'        => json_value($local),
        ]);
    }
}
