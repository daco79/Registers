<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/live_sources.php';

try {
    $type = request_string('type');
    $id = request_string('id');

    if ($type === '' || $id === '') {
        json_response(['ok' => false, 'error' => 'Missing type or id'], 400);
    }

    $payload = live_detail($type, $id);
    if ($payload === null) {
        json_response(['ok' => false, 'error' => 'Entity not found'], 404);
    }

    json_response(['ok' => true, 'mode' => 'live', 'data' => $payload]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
