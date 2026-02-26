<?php

/**
 * Generate Default Avatar
 * Creates a simple default profile picture
 */

// Create 400x400 image
$image = imagecreatetruecolor(400, 400);

// Set background color (purple gradient)
$bgColor = imagecolorallocate($image, 102, 126, 234); // #667eea
imagefilledrectangle($image, 0, 0, 400, 400, $bgColor);

// Set text color (white)
$textColor = imagecolorallocate($image, 255, 255, 255);

// Add text "USER"
$font = 5; // Built-in font
$text = "USER";
$textWidth = imagefontwidth($font) * strlen($text);
$textHeight = imagefontheight($font);
$x = (400 - $textWidth) / 2;
$y = (400 - $textHeight) / 2;

imagestring($image, $font, $x, $y, $text, $textColor);

// Save image
$savePath = __DIR__ . '/../assets/images/default-avatar.png';
imagepng($image, $savePath);
imagedestroy($image);

echo "✅ Default avatar created at: " . $savePath;
echo "<br><img src='../assets/images/default-avatar.png' width='200'>";
?>