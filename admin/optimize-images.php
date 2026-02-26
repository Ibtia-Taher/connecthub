<?php
/**
 * Image Optimization Script
 * Compress and optimize all uploaded images
 */

require_once __DIR__ . '/../config/config.php';

$uploadDir = __DIR__ . '/../assets/images/uploads/';
$images = glob($uploadDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

$optimized = 0;
$totalSaved = 0;

echo "<h2>Image Optimization Report</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>File</th><th>Original Size</th><th>New Size</th><th>Saved</th></tr>";

foreach ($images as $image) {
    $originalSize = filesize($image);
    
    // Get image info
    $info = getimagesize($image);
    if (!$info) continue;
    
    // Create image resource
    switch ($info['mime']) {
        case 'image/jpeg':
            $img = imagecreatefromjpeg($image);
            break;
        case 'image/png':
            $img = imagecreatefrompng($image);
            break;
        case 'image/gif':
            $img = imagecreatefromgif($image);
            break;
        default:
            continue 2;
    }
    
    // Save with compression
    imagejpeg($img, $image, 85); // 85% quality
    imagedestroy($img);
    
    $newSize = filesize($image);
    $saved = $originalSize - $newSize;
    $totalSaved += $saved;
    
    if ($saved > 0) {
        $optimized++;
        echo "<tr>";
        echo "<td>" . basename($image) . "</td>";
        echo "<td>" . round($originalSize / 1024, 2) . " KB</td>";
        echo "<td>" . round($newSize / 1024, 2) . " KB</td>";
        echo "<td style='color: green;'>" . round($saved / 1024, 2) . " KB</td>";
        echo "</tr>";
    }
}

echo "</table>";
echo "<h3>Summary</h3>";
echo "<p>✅ Optimized: {$optimized} images</p>";
echo "<p>💾 Total saved: " . round($totalSaved / 1024, 2) . " KB</p>";
?>