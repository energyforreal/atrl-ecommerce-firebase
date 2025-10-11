<?php
/**
 * Debug script to check affiliate database structure
 * Access via: /api/debug_affiliate.php?code=attral-71hlzssgan
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/firestore_admin_service.php';

try {
    $code = $_GET['code'] ?? '';
    
    if (empty($code)) {
        echo json_encode(['error' => 'Please provide code parameter']);
        exit;
    }
    
    $firestore = initFirestoreAdmin();
    
    // Check affiliates collection
    echo json_encode(['message' => 'Checking database...', 'code' => $code]) . "\n\n";
    
    // 1. Check by code field
    echo "--- Checking affiliates by 'code' field ---\n";
    $affiliatesQuery = $firestore->collection('affiliates')
        ->where('code', '=', $code)
        ->limit(1)
        ->documents();
    
    $found = false;
    foreach ($affiliatesQuery as $doc) {
        if ($doc->exists()) {
            $found = true;
            $data = $doc->data();
            echo json_encode([
                'found' => true,
                'documentId' => $doc->id(),
                'data' => $data
            ], JSON_PRETTY_PRINT);
        }
    }
    
    if (!$found) {
        echo json_encode(['found' => false, 'message' => 'No affiliate found with code field']) . "\n";
    }
    
    echo "\n\n";
    
    // 2. Check coupons collection
    echo "--- Checking coupons collection ---\n";
    $couponsQuery = $firestore->collection('coupons')
        ->where('code', '=', $code)
        ->limit(1)
        ->documents();
    
    $couponFound = false;
    foreach ($couponsQuery as $doc) {
        if ($doc->exists()) {
            $couponFound = true;
            $data = $doc->data();
            echo json_encode([
                'found' => true,
                'documentId' => $doc->id(),
                'usageCount' => $data['usageCount'] ?? 0,
                'payoutUsage' => $data['payoutUsage'] ?? 0,
                'fullData' => $data
            ], JSON_PRETTY_PRINT);
        }
    }
    
    if (!$couponFound) {
        echo json_encode(['found' => false, 'message' => 'No coupon found']) . "\n";
    }
    
    echo "\n\n";
    
    // 3. List all affiliates (first 5)
    echo "--- First 5 affiliates in database ---\n";
    $allAffiliates = $firestore->collection('affiliates')
        ->limit(5)
        ->documents();
    
    $count = 0;
    foreach ($allAffiliates as $doc) {
        if ($doc->exists()) {
            $count++;
            $data = $doc->data();
            echo json_encode([
                'documentId' => $doc->id(),
                'code' => $data['code'] ?? 'NO CODE FIELD',
                'email' => $data['email'] ?? 'NO EMAIL',
                'uid' => $data['uid'] ?? 'NO UID'
            ], JSON_PRETTY_PRINT) . "\n";
        }
    }
    
    echo "\nTotal affiliates found: $count\n";
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

