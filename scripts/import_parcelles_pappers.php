<?php
declare(strict_types=1);

/**
 * Import parcelles mono-proprietaire depuis Pappers Immobilier API.
 *
 * Usage :
 *   php scripts/import_parcelles_pappers.php --code_postal=75011 [--monoproprietaire] [--par_page=100] [--max_pages=20] [--dry_run]
 *   php scripts/import_parcelles_pappers.php --code_commune=75111 --monoproprietaire
 *
 * Prerequis : PAPPERS_IMMOBILIER_TOKEN dans .env
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/importers.php';

registers_load_env();

$token = getenv('PAPPERS_IMMOBILIER_TOKEN');
if (!$token) {
    fwrite(STDERR, "Erreur : PAPPERS_IMMOBILIER_TOKEN manquant dans .env\n");
    exit(1);
}

$opts = getopt('', ['code_postal:', 'code_commune:', 'monoproprietaire', 'par_page:', 'max_pages:', 'dry_run']);

if (empty($opts['code_postal']) && empty($opts['code_commune'])) {
    echo "Usage : php scripts/import_parcelles_pappers.php --code_postal=75011 [--monoproprietaire] [--par_page=100] [--max_pages=20] [--dry_run]\n";
    exit(1);
}

$parPage          = min((int) ($opts['par_page'] ?? 100), 100);
$maxPages         = (int) ($opts['max_pages'] ?? 20);
$dryRun           = isset($opts['dry_run']);
$monoProprietaire = isset($opts['monoproprietaire']);

$returnFields = implode(',', [
    'numero', 'section', 'prefixe', 'numero_plan', 'adresse',
    'code_commune', 'commune', 'code_departement', 'departement',
    'codes_postaux', 'contenance', 'bounding_box',
    'proprietaires_siren', 'proprietaires_nom_entreprise', 'proprietaires_denomination',
    'proprietaires_date_creation', 'proprietaires_categorie_juridique',
    'proprietaires_activite_principale', 'proprietaires_cessation_activite',
    'proprietaires_monoproprietaire', 'proprietaires_proprietaire_occupant',
    'proprietaires_lmnp', 'proprietaires_locaux',
]);

$baseParams = [
    'bases'         => 'proprietaires',
    'return_fields' => $returnFields,
    'par_page'      => $parPage,
];
if (!empty($opts['code_postal']))  $baseParams['code_postal']                  = $opts['code_postal'];
if (!empty($opts['code_commune'])) $baseParams['code_commune']                 = $opts['code_commune'];
if ($monoProprietaire)             $baseParams['monoproprietaire_proprietaire'] = 'true';

$pdo    = registers_db();
$rawDir = __DIR__ . '/../data/raw';
if (!is_dir($rawDir)) {
    mkdir($rawDir, 0755, true);
}

$label = implode('_', array_filter([
    $opts['code_postal'] ?? ($opts['code_commune'] ?? 'unknown'),
    $monoProprietaire ? 'mono' : null,
]));
$runTs = date('Ymd_His');

$totalSeen     = 0;
$totalInserted = 0;
$totalUpdated  = 0;
$totalResultats = null;
$page = 1;

echo ($dryRun ? "[DRY RUN] " : "") . "Import parcelles Pappers — {$label}\n\n";

do {
    $params      = $baseParams;
    $params['page'] = $page;
    $url         = 'https://api-immobilier.pappers.fr/v1/parcelles?' . http_build_query($params);

    echo "[Page {$page}] ";

    $data = http_json_bearer($url, $token);

    $rawFile = "{$rawDir}/pappers_parcelles_{$label}_{$runTs}_p{$page}.json";
    file_put_contents($rawFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    $parcelles = $data['resultats'] ?? $data['parcelles'] ?? [];
    if ($totalResultats === null) {
        $totalResultats = (int) ($data['total'] ?? count($parcelles));
    }
    $seen       = count($parcelles);
    $totalSeen += $seen;

    echo "{$seen} parcelles (total API : {$totalResultats}) — raw : " . basename($rawFile) . "\n";

    if ($dryRun || $seen === 0) {
        $page++;
        if ($page > $maxPages || $totalSeen >= $totalResultats) break;
        continue;
    }

    $logId = insert_import_log($pdo, [
        'domain'       => 'immobilier',
        'source_tool'  => 'pappers-immobilier-api',
        'source_file'  => $rawFile,
        'query_json'   => array_merge($baseParams, ['page' => $page]),
        'return_fields'=> explode(',', $returnFields),
        'response_hash'=> hash('sha256', (string) json_encode($data)),
        'rows_seen'    => $seen,
    ]);

    $inserted = 0;
    $updated  = 0;

    foreach ($parcelles as $p) {
        $numero = (string) ($p['numero'] ?? '');
        if ($numero === '') continue;

        $chk = $pdo->prepare('SELECT 1 FROM parcelles WHERE numero = ? LIMIT 1');
        $chk->execute([$numero]);
        $isNew = !$chk->fetch();

        upsert_parcelle($pdo, $p);
        $isNew ? $inserted++ : $updated++;

        insert_raw_payload($pdo, $logId, 'immobilier', 'parcelle', $numero, $rawFile, $p);

        foreach (($p['proprietaires'] ?? []) as $prop) {
            $propId = upsert_parcelle_proprietaire($pdo, $numero, $prop);
            if (!empty($prop['locaux'])) {
                import_proprietaire_locaux($pdo, $propId, $numero, $prop['locaux']);
            }
        }
    }

    $pdo->prepare('UPDATE import_logs SET rows_inserted = ?, rows_updated = ? WHERE id = ?')
        ->execute([$inserted, $updated, $logId]);

    $totalInserted += $inserted;
    $totalUpdated  += $updated;
    echo "  => inseres : {$inserted}, MAJ : {$updated}\n";

    $page++;
} while ($page <= $maxPages && $totalSeen < $totalResultats);

echo "\n=== Termine ===\n";
printf("Pages : %d | Parcelles vues : %d | Inseres : %d | MAJ : %d\n",
    $page - 1, $totalSeen, $totalInserted, $totalUpdated);
if ($dryRun) {
    echo "(dry-run : aucune insertion reelle)\n";
}
