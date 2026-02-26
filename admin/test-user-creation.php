<?php
/**
 * Test User Creation Directly
 * This bypasses the API to test database insertion
 */

require_once __DIR__ . '/../config/debug.php';
require_once __DIR__ . '/../config/database.php';

echo "<h2>Testing User Creation</h2>";

// Test data
$testData = [
    'username' => 'testuser_' . time(), // Unique username
    'email' => 'test_' . time() . '@example.com', // Unique email
    'phone' => '+1234567890',
    'password' => 'TestPassword123',
    'dob' => '2001-01-01'
];

echo "<h3>Test Data:</h3>";
echo "<pre>" . print_r($testData, true) . "</pre>";

// Get database connection
$conn = getDBConnection();

if (!$conn) {
    die("❌ Database connection failed!");
}

echo "✅ Database connected<br><br>";

// Hash password
$passwordHash = password_hash($testData['password'], PASSWORD_BCRYPT);
echo "✅ Password hashed: " . substr($passwordHash, 0, 20) . "...<br><br>";

// Prepare query
$query = "INSERT INTO users (username, email, phone, password_hash, date_of_birth) 
          VALUES (?, ?, ?, ?, ?)";

echo "📝 Query: " . $query . "<br><br>";

$stmt = $conn->prepare($query);

if (!$stmt) {
    echo "❌ PREPARE FAILED: " . $conn->error . "<br>";
    echo "Error Number: " . $conn->errno . "<br>";
    die();
}

echo "✅ Statement prepared<br><br>";

// Bind parameters
$bindResult = $stmt->bind_param(
    "sssss",
    $testData['username'],
    $testData['email'],
    $testData['phone'],
    $passwordHash,
    $testData['dob']
);

if (!$bindResult) {
    echo "❌ BIND FAILED: " . $stmt->error . "<br>";
    die();
}

echo "✅ Parameters bound<br><br>";

// Execute
$executeResult = $stmt->execute();

if (!$executeResult) {
    echo "❌ EXECUTE FAILED: " . $stmt->error . "<br>";
    echo "Error Number: " . $stmt->errno . "<br>";
    
    // Common error codes
    if ($stmt->errno === 1062) {
        echo "<br>💡 This is a DUPLICATE ENTRY error. Username or email already exists.<br>";
    } elseif ($stmt->errno === 1452) {
        echo "<br>💡 This is a FOREIGN KEY CONSTRAINT error.<br>";
    } elseif ($stmt->errno === 1366) {
        echo "<br>💡 This is an INCORRECT DATA TYPE error.<br>";
    }
    
    $stmt->close();
    die();
}

$userId = $conn->insert_id;

echo "✅ EXECUTE SUCCESS!<br>";
echo "✅ New User ID: <strong>" . $userId . "</strong><br><br>";

echo "<h3>✅ User Created Successfully!</h3>";
echo "<p>Username: <strong>" . htmlspecialchars($testData['username']) . "</strong></p>";
echo "<p>Email: <strong>" . htmlspecialchars($testData['email']) . "</strong></p>";

$stmt->close();
closeDBConnection($conn);
?>