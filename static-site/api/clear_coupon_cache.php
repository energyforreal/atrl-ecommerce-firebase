<?php
/**
 * Clear Coupon Cache Utility
 * Deletes all cached coupon validation results
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $cacheDir = __DIR__ . '/.cache';
    $deleted = 0;
    
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/coupon_*.json');
        
        foreach ($files as $file) {
            if (unlink($file)) {
                $deleted++;
            }
        }
        
        error_log("COUPON CACHE: Cleared {$deleted} cached coupon files");
        
        echo json_encode([
            'success' => true,
            'deleted' => $deleted,
            'message' => "Cleared {$deleted} cached coupon(s)"
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'deleted' => 0,
            'message' => 'Cache directory does not exist (no cache to clear)'
        ]);
    }
    
} catch (Exception $e) {
    error_log("COUPON CACHE CLEAR ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

