<?php
/**
 * Debug endpoint for testing coupon increment functionality
 */

// Suppress warnings and errors to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering
ob_start();

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

// Composer autoloader for Firestore SDK
@include_once __DIR__ . '/vendor/autoload.php';

// Load configuration
$cfg = @include __DIR__.'/config.php';
$RAZORPAY_KEY_SECRET = ($cfg['RAZORPAY_KEY_SECRET'] ?? null) ?: getenv('RAZORPAY_KEY_SECRET') ?: '';

try {
    if (!class_exists('\Kreait\Firebase\Factory')) {
        throw new Exception('Firebase SDK not available');
    }

    // Initialize Firebase
    $factory = \Kreait\Firebase\Factory::withServiceAccount(__DIR__ . '/firebase-service-account.json');
    $firestore = $factory->createFirestore();
    
    $result = [
        'success' => true,
        'message' => 'Debug endpoint ready',
        'firestore' => $firestore ? 'connected' : 'failed',
        'tests' => []
    ];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $couponCode = $input['coupon_code'] ?? 'lokesh-8f954s5x9b';
        
        error_log("DEBUG COUPON: Testing increment for code: $couponCode");
        
        // Test 1: Check if coupon exists
        $couponsRef = $firestore->collection('coupons');
        $query = $couponsRef->where('code', '=', $couponCode)->limit(1);
        $documents = $query->documents();
        
        $found = false;
        $couponData = null;
        $docId = null;
        
        foreach ($documents as $doc) {
            if ($doc->exists()) {
                $found = true;
                $docId = $doc->id();
                $couponData = $doc->data();
                break;
            }
        }
        
        $result['tests']['coupon_exists'] = [
            'found' => $found,
            'doc_id' => $docId,
            'data' => $couponData
        ];
        
        if (!$found) {
            $result['success'] = false;
            $result['error'] = "Coupon not found: $couponCode";
        } else {
            // Test 2: Try to increment
            try {
                $docRef = $firestore->collection('coupons')->document($docId);
                
                $beforeData = $couponData;
                $beforeUsageCount = $beforeData['usageCount'] ?? 0;
                $beforePayoutUsage = $beforeData['payoutUsage'] ?? 0;
                
                error_log("DEBUG COUPON: Before increment - usageCount: $beforeUsageCount, payoutUsage: $beforePayoutUsage");
                
                // Try atomic increment
                $updates = [
                    ['path' => 'usageCount', 'value' => \Google\Cloud\Firestore\FieldValue::increment(1)],
                    ['path' => 'payoutUsage', 'value' => \Google\Cloud\Firestore\FieldValue::increment(1)],
                    ['path' => 'updatedAt', 'value' => new \Google\Cloud\Core\Timestamp(new DateTime())]
                ];
                
                $docRef->update($updates);
                
                // Read back the data
                $afterSnap = $docRef->snapshot();
                $afterData = $afterSnap->exists() ? $afterSnap->data() : null;
                $afterUsageCount = $afterData['usageCount'] ?? 0;
                $afterPayoutUsage = $afterData['payoutUsage'] ?? 0;
                
                error_log("DEBUG COUPON: After increment - usageCount: $afterUsageCount, payoutUsage: $afterPayoutUsage");
                
                $result['tests']['increment'] = [
                    'success' => true,
                    'before' => [
                        'usageCount' => $beforeUsageCount,
                        'payoutUsage' => $beforePayoutUsage
                    ],
                    'after' => [
                        'usageCount' => $afterUsageCount,
                        'payoutUsage' => $afterPayoutUsage
                    ],
                    'incremented' => [
                        'usageCount' => $afterUsageCount - $beforeUsageCount,
                        'payoutUsage' => $afterPayoutUsage - $beforePayoutUsage
                    ]
                ];
                
            } catch (Exception $e) {
                error_log("DEBUG COUPON ERROR: " . $e->getMessage());
                $result['tests']['increment'] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $result['success'] = false;
                $result['error'] = $e->getMessage();
            }
        }
    }

} catch (Exception $e) {
    error_log("DEBUG COUPON FATAL ERROR: " . $e->getMessage());
    $result = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Clean up any unexpected output
$unexpectedOutput = ob_get_clean();
if (!empty($unexpectedOutput)) {
    error_log("DEBUG COUPON: Unexpected output detected: " . $unexpectedOutput);
}

echo json_encode($result);
?>
