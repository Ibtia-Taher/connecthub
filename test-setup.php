<?php
require_once 'config/database.php';
require_once 'config/config.php';

echo "<h1>ConnectHub Setup Test</h1>";

// Test 1: Database connection
echo "<h2>1. Database Connection Test</h2>";
$conn = getDBConnection();
if ($conn) {
    echo "✅ Database connected successfully!<br>";
    echo "Server: " . $conn->host_info . "<br>";
    closeDBConnection($conn);
} else {
    echo "❌ Database connection failed!<br>";
}

// Test 2: Configuration
echo "<h2>2. Configuration Test</h2>";
echo "App Name: " . APP_NAME . "<br>";
echo "App URL: " . APP_URL . "<br>";
echo "✅ Configuration loaded successfully!<br>";

// Test 3: Session
echo "<h2>3. Session Test</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✅ Session is active!<br>";
    echo "Session ID: " . session_id() . "<br>";
} else {
    echo "❌ Session is not active!<br>";
}

// Test 4: Upload directory
echo "<h2>4. Upload Directory Test</h2>";
if (is_dir(UPLOAD_DIR)) {
    echo "✅ Upload directory exists!<br>";
    if (is_writable(UPLOAD_DIR)) {
        echo "✅ Upload directory is writable!<br>";
    } else {
        echo "⚠️ Upload directory is not writable! Check permissions.<br>";
    }
} else {
    echo "❌ Upload directory does not exist!<br>";
    if (mkdir(UPLOAD_DIR, 0777, true)) {
        echo "✅ Upload directory created successfully!<br>";
    }
}

echo "<h2>🎉 Setup test complete!</h2>";
?>