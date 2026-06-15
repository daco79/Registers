<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/entities.php';

try {
    $q = request_string('q');
    $type = request_string('type', 'all');
    $limit = (int) request_string('limit', '25');

    if ($q === '') {
        json_response(['ok' => true, 'query' => $q, 'results' => []]);
    }

    $results = search_entities(registers_db(), $type, $q, $limit);
    json_response(['ok' => true, 'query' => $q, 'type' => $type, 'results' => $results]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => $e->getMessage()], 500);
}

