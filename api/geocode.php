<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/importers.php';

try {
    $query = request_string('q');
    $pdo = registers_db();
    $result = import_geocode_from_ban($pdo, $query);
    json_response(['ok' => true, 'result' => $result]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 400);
}
