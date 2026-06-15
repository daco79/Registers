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

    $payload = entity_payload(registers_db(), $type, $id, $mode);
    if ($payload === null) {
        json_response(['ok' => false, 'error' => 'Entity not found'], 404);
    }

    $filename = preg_replace('/[^a-zA-Z0-9_-]+/', '_', "{$type}_{$id}_{$mode}") . '.json';
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    echo json_encode(['ok' => true, 'mode' => $mode, 'data' => $payload], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}

