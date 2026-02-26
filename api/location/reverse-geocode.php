<?php
/**
 * Reverse Geocoding Proxy
 * Proxies requests to Nominatim to avoid CORS issues
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Get coordinates
$lat = $_GET['lat'] ?? '';
$lon = $_GET['lon'] ?? '';

if (empty($lat) || empty($lon)) {
    echo json_encode(['error' => 'Coordinates required']);
    exit;
}

// Make request to Nominatim
$url = 'https://nominatim.openstreetmap.org/reverse?' . http_build_query([
    'format' => 'json',
    'lat' => $lat,
    'lon' => $lon,
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
    echo json_encode(['error' => 'Reverse geocoding unavailable']);
    exit;
}

echo $response;
?>