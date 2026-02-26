<?php
/**
 * Image Processing Helper
 * Handles image upload, validation, and resizing
 */

/**
 * Validate uploaded image
 * @param array $file $_FILES array element
 * @return array ['success' => bool, 'message' => string]
 */
function validateImage($file) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => 'No file uploaded'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        $message = $errorMessages[$file['error']] ?? 'Unknown upload error';
        return ['success' => false, 'message' => $message];
    }
    
    // Check file size (max 5MB)
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        $maxSizeMB = MAX_UPLOAD_SIZE / (1024 * 1024);
        return ['success' => false, 'message' => "File too large. Max size: {$maxSizeMB}MB"];
    }
    
    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, WEBP allowed'];
    }
    
    // Check if it's actually an image
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'message' => 'File is not a valid image'];
    }
    
    // Check image dimensions (min 100x100, max 5000x5000)
    list($width, $height) = $imageInfo;
    if ($width < 100 || $height < 100) {
        return ['success' => false, 'message' => 'Image too small. Minimum 100x100 pixels'];
    }
    if ($width > 5000 || $height > 5000) {
        return ['success' => false, 'message' => 'Image too large. Maximum 5000x5000 pixels'];
    }
    
    return ['success' => true, 'message' => 'Image is valid'];
}

/**
 * Resize and save image
 * @param string $sourcePath Path to original image
 * @param string $destinationPath Where to save resized image
 * @param int $maxWidth Maximum width
 * @param int $maxHeight Maximum height
 * @return bool Success status
 */
function resizeImage($sourcePath, $destinationPath, $maxWidth = 400, $maxHeight = 400) {
    // Get image info
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    list($origWidth, $origHeight, $imageType) = $imageInfo;
    
    // Calculate new dimensions (maintain aspect ratio)
    $ratio = min($maxWidth / $origWidth, $maxHeight / $origHeight);
    $newWidth = round($origWidth * $ratio);
    $newHeight = round($origHeight * $ratio);
    
    // Create image resource from source
    switch ($imageType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$sourceImage) {
        return false;
    }
    
    // Create new image with desired dimensions
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG and GIF
    if ($imageType === IMAGETYPE_PNG || $imageType === IMAGETYPE_GIF) {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize
    imagecopyresampled(
        $newImage, $sourceImage,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $origWidth, $origHeight
    );
    
    // Save resized image (always save as JPEG for consistency)
    $result = imagejpeg($newImage, $destinationPath, 85); // 85% quality
    
    // Free memory
    imagedestroy($sourceImage);
    imagedestroy($newImage);
    
    return $result;
}

/**
 * Generate unique filename
 * @param string $originalName Original filename
 * @param int $userId User ID
 * @return string Unique filename
 */
function generateUniqueFilename($originalName, $userId) {
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // For profile pictures, always use .jpg
    $extension = 'jpg';
    
    // Format: user_{userId}_{timestamp}.jpg
    return "user_{$userId}_" . time() . ".{$extension}";
}

/**
 * Delete old profile picture
 * @param string $filename Filename to delete
 * @return bool Success status
 */
function deleteOldAvatar($filename) {
    // Don't delete default avatar
    if ($filename === 'default-avatar.png') {
        return true;
    }
    
    $filepath = UPLOAD_DIR . $filename;
    
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    
    return true;
}
?>