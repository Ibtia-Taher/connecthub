<?php
/**
 * Geocoding Proxy
 * Proxies requests to Nominatim to avoid CORS issues
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get search query
$query = $_GET['q'] ?? '';

if (empty($query)) {
    echo json_encode(['error' => 'No query provided']);
    exit;
}

// Make request to Nominatim with proper User-Agent
$url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
    'format' => 'json',
    'q' => $query,
    'limit' => 5,
    'addressdetails' => 1
]);

$options = [
    'http' => [
        'header' => "User-Agent: ConnectHub/1.0 (Learning Project)\r\n"
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo json_encode(['error' => 'Geocoding service unavailable']);
    exit;
}

echo $response;
?>