<?php
/**
 * Get Coupon Stats API
 * Returns real-time statistics for all coupons
 */

// Headers for CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Include Firestore service
if (!file_exists(__DIR__ . '/firestore_admin_service.php')) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Firestore admin service not found'
    ]);
    exit;
}
require_once __DIR__ . '/firestore_admin_service.php';

try {
    // Initialize Firestore
    $firestore = initFirestoreAdmin();
    
    // Get optional filter parameters
    $affiliateCode = $_GET['affiliateCode'] ?? '';
    
    // Query coupons collection
    $couponsRef = $firestore->collection('coupons');
    
    // Apply filter if affiliate code is provided
    if (!empty($affiliateCode)) {
        $query = $couponsRef->where('code', '=', $affiliateCode);
    } else {
        $query = $couponsRef;
    }
    
    $couponsSnapshot = $query->documents();
    
    $totalCoupons = 0;
    $activeCoupons = 0;
    $totalUsage = 0;
    $totalCommissions = 0;
    $coupons = [];
    
    foreach ($couponsSnapshot as $doc) {
        if ($doc->exists()) {
            $data = $doc->data();
            $totalCoupons++;
            
            $isActive = $data['isActive'] ?? true;
            if ($isActive) {
                $activeCoupons++;
            }
            
            $usageCount = $data['usageCount'] ?? 0;
            $payoutUsage = $data['payoutUsage'] ?? 0;
            
            $totalUsage += $usageCount;
            $totalCommissions += $payoutUsage;
            
            // Build coupon summary
            $coupons[] = [
                'id' => $doc->id(),
                'code' => $data['code'] ?? '',
                'name' => $data['name'] ?? '',
                'type' => $data['type'] ?? 'percentage',
                'isActive' => $isActive,
                'usageCount' => $usageCount,
                'payoutUsage' => $payoutUsage,
                'isAffiliateCoupon' => $data['isAffiliateCoupon'] ?? false,
                'affiliateCode' => $data['affiliateCode'] ?? null
            ];
        }
    }
    
    // Return statistics
    echo json_encode([
        'success' => true,
        'stats' => [
            'totalCoupons' => $totalCoupons,
            'activeCoupons' => $activeCoupons,
            'totalUsage' => $totalUsage,
            'totalCommissions' => $totalCommissions
        ],
        'coupons' => $coupons
    ]);
    
} catch (Exception $e) {
    error_log("Get Coupon Stats Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>

