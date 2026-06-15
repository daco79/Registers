<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/entities.php';

try {
    $type = request_string('type');
    $id = request_string('id');
    $mode = request_string('mode', 'full');

    if ($type === '' || $id === '') {
        json_response(['ok' => false, 'error' => 'Missing type or id'], 400);
    }

    if (!in_array($mode, ['summary', 'full', 'raw'], true)) {
        $mode = 'full';
    }

    $payload = entity_payload(registers_db(), $type, $id, $mode);
    if ($payload === null) {
        json_response(['ok' => false, 'error' => 'Entity not found'], 404);
    }

    json_response(['ok' => true, 'mode' => $mode, 'data' => $payload]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}

