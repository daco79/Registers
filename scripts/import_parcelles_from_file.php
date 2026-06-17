<?php
declare(strict_types=1);

/**
 * Importe des parcelles depuis un fichier JSON Pappers local.
 *
 * Usage :
 *   php scripts/import_parcelles_from_file.php --file=Export/RetourPappers_parcelles_75016.json [--dry_run]
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/importers.php';

$opts = getopt('', ['file:', 'dry_run']);

if (empty($opts['file'])) {
    echo "Usage : php scripts/import_parcelles_from_file.php --file=Export/RetourPappers_parcelles_75016.json [--dry_run]\n";
    exit(1);
}

$filePath = __DIR__ . '/../' . ltrim($opts['file'], '/');
if (!is_readable($filePath)) {
    fwrite(STDERR, "Fichier illisible : {$filePath}\n");
    exit(1);
}

$dryRun = isset($opts['dry_run']);
$data   = json_decode(file_get_contents($filePath), true);

if (!is_array($data)) {
    fwrite(STDERR, "JSON invalide.\n");
    exit(1);
}

$parcelles      = $data['resultats'] ?? $data['parcelles'] ?? [];
$totalFichier   = (int) ($data['total'] ?? count($parcelles));

echo ($dryRun ? "[DRY RUN] " : "") . "Import depuis : " . basename($filePath) . "\n";
echo "Parcelles dans le fichier : " . count($parcelles) . " (total declare : {$totalFichier})\n\n";

if ($dryRun) {
    $avecProps = count(array_filter($parcelles, fn ($p) => !empty($p['proprietaires'])));
    echo "Parcelles avec proprietaires : {$avecProps}\n";
    echo "Exemple parcelle[0] : " . ($parcelles[0]['numero'] ?? '?') . " — " . (is_string($parcelles[0]['adresse'] ?? null) ? $parcelles[0]['adresse'] : json_encode($parcelles[0]['adresse'] ?? null)) . "\n";
    exit(0);
}

$pdo       = registers_db();
$logId     = insert_import_log($pdo, [
    'domain'      => 'immobilier',
    'source_tool' => 'pappers-immobilier-file',
    'source_file' => $filePath,
    'query_json'  => ['file' => basename($filePath)],
    'rows_seen'   => count($parcelles),
    'notes'       => 'Import depuis fichier JSON local Pappers',
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

    insert_raw_payload($pdo, $logId, 'immobilier', 'parcelle', $numero, $filePath, $p);

    foreach (($p['proprietaires'] ?? []) as $prop) {
        if (empty($prop)) continue;
        $propId = upsert_parcelle_proprietaire($pdo, $numero, $prop);
        if (!empty($prop['locaux'])) {
            import_proprietaire_locaux($pdo, $propId, $numero, $prop['locaux']);
        }
    }
}

$pdo->prepare('UPDATE import_logs SET rows_inserted = ?, rows_updated = ? WHERE id = ?')
    ->execute([$inserted, $updated, $logId]);

echo "=== Termine ===\n";
printf("Inseres : %d | MAJ : %d\n", $inserted, $updated);
