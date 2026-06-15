<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';

try {
    $pdo = registers_db();
    $tables = fetch_all($pdo, "
        SELECT table_name AS name, table_type AS type
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        ORDER BY table_type, table_name
    ");

    foreach ($tables as &$table) {
        $table['rows'] = $table['type'] === 'BASE TABLE'
            ? rows_count($pdo, $table['name'])
            : null;
    }

    json_response(['ok' => true, 'database' => 'Registers', 'objects' => $tables]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
