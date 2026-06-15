<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function search_entities(PDO $pdo, string $type, string $query, int $limit = 25): array
{
    $limit = max(1, min(100, $limit));
    $like = like_term($query);
    $digits = preg_replace('/\D+/', '', $query);
    $results = [];

    if (($type === 'all' || $type === 'company') && table_exists($pdo, 'entreprises')) {
        $rows = fetch_all($pdo, "
            SELECT 'company' AS type, siren AS id, nom_entreprise AS title,
                   CONCAT_WS(' · ', forme_juridique, code_naf, statut_rcs) AS subtitle
            FROM entreprises
            WHERE siren = :digits OR nom_entreprise LIKE :like_name OR denomination LIKE :like_denomination
            ORDER BY nom_entreprise
            LIMIT {$limit}
        ", ['digits' => $digits, 'like_name' => $like, 'like_denomination' => $like]);
        $results = array_merge($results, $rows);
    }

    if (($type === 'all' || $type === 'establishment') && table_exists($pdo, 'etablissements')) {
        $rows = fetch_all($pdo, "
            SELECT 'establishment' AS type, siret AS id,
                   COALESCE(nom_commercial, enseigne, adresse_ligne_1, siret) AS title,
                   CONCAT_WS(' · ', siret, code_postal, ville, IF(siege, 'siege', NULL)) AS subtitle
            FROM etablissements
            WHERE siret = :digits OR siren = :siren OR adresse_ligne_1 LIKE :like_address OR ville LIKE :like_city
            ORDER BY siege DESC, ville, adresse_ligne_1
            LIMIT {$limit}
        ", ['digits' => $digits, 'siren' => substr($digits, 0, 9), 'like_address' => $like, 'like_city' => $like]);
        $results = array_merge($results, $rows);
    }

    if (($type === 'all' || $type === 'parcel') && table_exists($pdo, 'parcelles')) {
        $rows = fetch_all($pdo, "
            SELECT 'parcel' AS type, numero AS id,
                   COALESCE(adresse, numero) AS title,
                   CONCAT_WS(' · ', numero, commune, CONCAT(COALESCE(contenance, 0), ' m2')) AS subtitle
            FROM parcelles
            WHERE numero = :query_numero OR adresse LIKE :like_address OR commune LIKE :like_city OR code_commune = :query_commune
            ORDER BY commune, numero
            LIMIT {$limit}
        ", ['query_numero' => $query, 'query_commune' => $query, 'like_address' => $like, 'like_city' => $like]);
        $results = array_merge($results, $rows);
    }

    if (($type === 'all' || $type === 'copro') && table_exists($pdo, 'coproprietes')) {
        $rows = fetch_all($pdo, "
            SELECT 'copro' AS type, numero_immatriculation AS id,
                   COALESCE(nom, numero_immatriculation) AS title,
                   CONCAT_WS(' · ', parcelle_numero, type_syndic, CONCAT(COALESCE(nombre_total_lots, 0), ' lots')) AS subtitle
            FROM coproprietes
            WHERE numero_immatriculation = :query OR nom LIKE :like_name OR adresse LIKE :like_address
            ORDER BY nom, numero_immatriculation
            LIMIT {$limit}
        ", ['query' => $query, 'like_name' => $like, 'like_address' => $like]);
        $results = array_merge($results, $rows);
    }

    if (($type === 'all' || $type === 'dpe') && table_exists($pdo, 'dpe')) {
        $rows = fetch_all($pdo, "
            SELECT 'dpe' AS type, COALESCE(identifiant_dpe, CAST(id AS CHAR)) AS id,
                   COALESCE(identifiant_dpe, CONCAT('DPE #', id)) AS title,
                   CONCAT_WS(' · ', parcelle_numero, classe_bilan_dpe, type_batiment_dpe) AS subtitle
            FROM dpe
            WHERE identifiant_dpe = :query_dpe OR parcelle_numero = :query_parcelle
            ORDER BY date_reception_dpe DESC
            LIMIT {$limit}
        ", ['query_dpe' => $query, 'query_parcelle' => $query]);
        $results = array_merge($results, $rows);
    }

    return array_slice($results, 0, $limit);
}

function entity_payload(PDO $pdo, string $type, string $id, string $mode = 'full'): ?array
{
    return match ($type) {
        'company' => company_payload($pdo, $id, $mode),
        'establishment' => establishment_payload($pdo, $id, $mode),
        'parcel' => parcel_payload($pdo, $id, $mode),
        'copro' => copro_payload($pdo, $id, $mode),
        'dpe' => dpe_payload($pdo, $id, $mode),
        default => null,
    };
}

function company_payload(PDO $pdo, string $siren, string $mode): ?array
{
    $company = fetch_one($pdo, 'SELECT * FROM entreprises WHERE siren = ?', [$siren]);
    if (!$company) {
        return null;
    }

    if ($mode === 'summary') {
        return ['type' => 'company', 'company' => $company];
    }

    return [
        'type' => 'company',
        'company' => $company,
        'establishments' => fetch_all($pdo, 'SELECT * FROM etablissements WHERE siren = ? ORDER BY siege DESC, siret', [$siren]),
        'representatives' => fetch_all($pdo, 'SELECT * FROM representants WHERE siren_entreprise = ? ORDER BY actuel DESC, date_prise_de_poste DESC', [$siren]),
        'beneficial_owners' => fetch_all($pdo, 'SELECT * FROM beneficiaires_effectifs WHERE siren_entreprise = ?', [$siren]),
        'finances' => fetch_all($pdo, 'SELECT * FROM finances_annuelles WHERE siren = ? ORDER BY annee DESC', [$siren]),
        'bodacc' => fetch_all($pdo, 'SELECT * FROM publications_bodacc WHERE siren = ? ORDER BY date_publication DESC LIMIT 100', [$siren]),
        'procedures' => fetch_all($pdo, 'SELECT * FROM procedures_collectives WHERE siren = ? ORDER BY date_debut DESC', [$siren]),
        'owned_parcels' => fetch_all($pdo, 'SELECT * FROM vue_parcelles_proprietaires WHERE proprietaire_siren = ? LIMIT 250', [$siren]),
        'occupied_parcels' => fetch_all($pdo, 'SELECT * FROM vue_parcelles_occupants WHERE occupant_siren = ? LIMIT 250', [$siren]),
    ];
}

function establishment_payload(PDO $pdo, string $siret, string $mode): ?array
{
    $establishment = fetch_one($pdo, 'SELECT * FROM etablissements WHERE siret = ?', [$siret]);
    if (!$establishment) {
        return null;
    }

    $payload = ['type' => 'establishment', 'establishment' => $establishment];
    if ($mode !== 'summary' && !empty($establishment['siren'])) {
        $payload['company'] = fetch_one($pdo, 'SELECT * FROM entreprises WHERE siren = ?', [$establishment['siren']]);
    }
    return $payload;
}

function parcel_payload(PDO $pdo, string $numero, string $mode): ?array
{
    $parcel = fetch_one($pdo, 'SELECT * FROM parcelles WHERE numero = ?', [$numero]);
    if (!$parcel) {
        return null;
    }

    if ($mode === 'summary') {
        return ['type' => 'parcel', 'parcel' => $parcel];
    }

    return [
        'type' => 'parcel',
        'parcel' => $parcel,
        'owners' => fetch_all($pdo, 'SELECT * FROM parcelle_proprietaires WHERE parcelle_numero = ? ORDER BY nom_entreprise', [$numero]),
        'owner_units' => fetch_all($pdo, 'SELECT l.* FROM parcelle_proprietaire_locaux l JOIN parcelle_proprietaires p ON p.id = l.parcelle_proprietaire_id WHERE p.parcelle_numero = ? ORDER BY l.batiment, l.entree, l.niveau, l.porte', [$numero]),
        'occupants' => fetch_all($pdo, 'SELECT * FROM parcelle_occupants WHERE parcelle_numero = ? ORDER BY nom_entreprise', [$numero]),
        'sales' => fetch_all($pdo, 'SELECT * FROM ventes_immobilieres WHERE parcelle_numero = ? ORDER BY date_vente DESC LIMIT 250', [$numero]),
        'buildings' => fetch_all($pdo, 'SELECT * FROM batiments WHERE parcelle_numero = ? ORDER BY annee_construction DESC', [$numero]),
        'dpe' => fetch_all($pdo, 'SELECT * FROM dpe WHERE parcelle_numero = ? ORDER BY date_reception_dpe DESC LIMIT 250', [$numero]),
        'copros' => fetch_all($pdo, 'SELECT * FROM coproprietes WHERE parcelle_numero = ? ORDER BY nombre_total_lots DESC', [$numero]),
        'permits' => fetch_all($pdo, 'SELECT * FROM permis_urbanisme WHERE parcelle_numero = ? ORDER BY date_autorisation DESC', [$numero]),
        'business_assets' => fetch_all($pdo, 'SELECT * FROM fonds_de_commerce WHERE parcelle_numero = ? ORDER BY date_debut_activite DESC', [$numero]),
        'urbanism_documents' => fetch_all($pdo, 'SELECT * FROM documents_urbanisme WHERE parcelle_numero = ?', [$numero]),
        'urbanism_zones' => fetch_all($pdo, 'SELECT * FROM zones_urbanisme WHERE parcelle_numero = ?', [$numero]),
        'amenities' => fetch_all($pdo, 'SELECT * FROM amenagements WHERE parcelle_numero = ?', [$numero]),
    ];
}

function copro_payload(PDO $pdo, string $numero, string $mode): ?array
{
    $copros = fetch_all($pdo, 'SELECT * FROM coproprietes WHERE numero_immatriculation = ?', [$numero]);
    if (!$copros) {
        return null;
    }

    $payload = ['type' => 'copro', 'copros' => $copros];
    if ($mode !== 'summary') {
        $payload['other_parcels'] = fetch_all($pdo, 'SELECT * FROM copropriete_autres_parcelles WHERE numero_immatriculation = ?', [$numero]);
    }
    return $payload;
}

function dpe_payload(PDO $pdo, string $id, string $mode): ?array
{
    $dpe = fetch_one($pdo, 'SELECT * FROM dpe WHERE identifiant_dpe = ? OR id = ?', [$id, ctype_digit($id) ? (int) $id : 0]);
    if (!$dpe) {
        return null;
    }

    $payload = ['type' => 'dpe', 'dpe' => $dpe];
    if ($mode !== 'summary' && !empty($dpe['parcelle_numero'])) {
        $payload['parcel'] = fetch_one($pdo, 'SELECT * FROM parcelles WHERE numero = ?', [$dpe['parcelle_numero']]);
    }
    return $payload;
}
