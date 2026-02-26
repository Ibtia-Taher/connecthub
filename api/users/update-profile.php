<?php
/**
 * Update Profile API
 * Updates user profile information
 */

require_once __DIR__ . '/../../config/debug.php';
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/../../config/database.php';

// Require login
if (!isLoggedIn()) {
    jsonResponse(false, 'You must be logged in');
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$userId = getCurrentUserId();
$conn = getDBConnection();

// Fields that can be updated
$allowedFields = ['bio', 'location', 'latitude', 'longitude', 'date_of_birth', 'phone'];
$updateFields = [];
$params = [];
$types = '';

foreach ($allowedFields as $field) {
    if (isset($input[$field])) {
        $updateFields[] = "$field = ?";
        $params[] = $input[$field];
        
        // Determine parameter type
        if ($field === 'latitude' || $field === 'longitude') {
            $types .= 'd'; // decimal
        } else {
            $types .= 's'; // string
        }
    }
}

// If nothing to update
if (empty($updateFields)) {
    jsonResponse(false, 'No fields to update');
}

// Add user_id to params
$params[] = $userId;
$types .= 'i';

// Build query
$query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE user_id = ?";

// Prepare and execute
$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    jsonResponse(false, 'Database error');
}

$stmt->bind_param($types, ...$params);

if (!$stmt->execute()) {
    error_log("Execute failed: " . $stmt->error);
    $stmt->close();
    closeDBConnection($conn);
    jsonResponse(false, 'Failed to update profile');
}

$stmt->close();
closeDBConnection($conn);

jsonResponse(true, 'Profile updated successfully');
?>