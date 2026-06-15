<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/importers.php';

try {
    $identifier = request_string('identifier');
    $pdo = registers_db();
    $result = import_company_from_annuaire($pdo, $identifier);
    json_response(['ok' => true, 'result' => $result]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 400);
}
