<?php
/**
 * Simple Caching Helper
 * Cache frequently accessed data
 */

/**
 * Get cached data
 */
function getCached($key) {
    $cacheFile = __DIR__ . '/../cache/' . md5($key) . '.cache';
    
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    $data = file_get_contents($cacheFile);
    $data = unserialize($data);
    
    // Check if expired
    if ($data['expires'] < time()) {
        unlink($cacheFile);
        return null;
    }
    
    return $data['value'];
}

/**
 * Set cached data
 */
function setCached($key, $value, $ttl = 3600) {
    $cacheDir = __DIR__ . '/../cache/';
    
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }
    
    $cacheFile = $cacheDir . md5($key) . '.cache';
    
    $data = [
        'value' => $value,
        'expires' => time() + $ttl
    ];
    
    file_put_contents($cacheFile, serialize($data));
}

/**
 * Clear cache
 */
function clearCache($key = null) {
    $cacheDir = __DIR__ . '/../cache/';
    
    if ($key === null) {
        // Clear all cache
        $files = glob($cacheDir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    } else {
        // Clear specific key
        $cacheFile = $cacheDir . md5($key) . '.cache';
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
}
?>