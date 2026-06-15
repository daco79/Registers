<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/live_sources.php';

try {
    $q = request_string('q');
    $type = request_string('type', 'all');
    $limit = (int) request_string('limit', '10');

    json_response([
        'ok' => true,
        'query' => $q,
        'type' => $type,
        'results' => live_search($q, $type, $limit),
    ]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}
