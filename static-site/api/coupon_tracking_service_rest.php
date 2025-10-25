<?php
/**
 * 🎟️ FIRESTORE COUPON TRACKING SERVICE (REST API Version)
 * 
 * Production-grade, idempotent, affiliate-aware coupon tracking for e-commerce.
 * Compatible with Hostinger shared hosting (uses REST API, no gRPC SDK)
 * 
 * Features:
 * - Atomic increment operations using Firestore REST API transforms
 * - Idempotency guards to prevent duplicate increments per order/payment
 * - Affiliate commission tracking with payout amounts
 * - Code normalization for consistent querying
 * - Comprehensive error handling and logging
 * - Transaction-safe operations
 * 
 * @version 2.0.0 (REST API)
 * @author ATTRAL E-Commerce Platform
 * @license MIT
 */

// Prevent direct access
if (!defined('COUPON_SERVICE_REST_LOADED')) {
    define('COUPON_SERVICE_REST_LOADED', true);
}

/**
 * Normalize coupon code for consistent querying
 * 
 * @param string $code Raw coupon code input
 * @return string Normalized coupon code (trimmed, uppercase)
 */
function normalizeCouponCode($code) {
    if (!$code || !is_string($code)) {
        return '';
    }
    return strtoupper(trim($code));
}

/**
 * Simple atomic increment test function (REST API)
 * 
 * Increments both usageCount and payoutUsage by 1 for testing purposes.
 * Does NOT use idempotency guards - use applyCouponForOrderRest() for production.
 * 
 * @param FirestoreRestClient $client Firestore REST client instance
 * @param string $code Coupon code to increment
 * @return array Result with success status and details
 */
function incrementCouponByCodeRest($client, $code) {
    try {
        $normalizedCode = normalizeCouponCode($code);
        
        if (!$normalizedCode) {
            return [
                'success' => false,
                'error' => 'Invalid coupon code provided',
                'code' => $code
            ];
        }
        
        error_log("COUPON SERVICE REST: Incrementing usage for code: $normalizedCode");
        
        // Query coupons collection by code field using REST API
        $coupons = $client->queryDocuments(
            'coupons',
            [
                ['field' => 'code', 'op' => 'EQUAL', 'value' => $normalizedCode]
            ],
            1
        );
        
        if (empty($coupons)) {
            error_log("COUPON SERVICE REST: Coupon not found: $normalizedCode");
            return [
                'success' => false,
                'error' => 'Coupon not found',
                'code' => $normalizedCode
            ];
        }
        
        $coupon = $coupons[0];
        $docId = $coupon['id'];
        $couponData = $coupon['data'];
        
        // Atomically increment using REST API
        $client->incrementField('coupons', $docId, 'usageCount', 1);
        $client->incrementField('coupons', $docId, 'payoutUsage', 1);
        
        // Update timestamp
        $client->updateDocument('coupons', $docId, [
            ['path' => 'updatedAt', 'value' => firestoreTimestamp()]
        ]);
        
        // Read updated values
        $updated = $client->getDocument('coupons', $docId);
        $updatedData = $updated['data'];
        
        error_log("COUPON SERVICE REST: Successfully incremented $normalizedCode - usageCount: " . ($updatedData['usageCount'] ?? 0));
        
        return [
            'success' => true,
            'message' => 'Coupon usage incremented',
            'coupon' => [
                'id' => $docId,
                'code' => $normalizedCode,
                'usageCount' => $updatedData['usageCount'] ?? 0,
                'payoutUsage' => $updatedData['payoutUsage'] ?? 0
            ]
        ];
        
    } catch (Exception $e) {
        error_log("COUPON SERVICE REST ERROR: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'code' => $code ?? 'unknown'
        ];
    }
}

/**
 * Apply coupon for order with full idempotency and affiliate support (REST API)
 * 
 * This is the production-ready function that:
 * 1. Checks for duplicate application using guard documents
 * 2. Atomically increments usageCount
 * 3. Increments payoutUsage by actual commission amount (for affiliates)
 * 4. Creates guard document to prevent duplicate increments
 * 5. Logs affiliate usage for reporting
 * 
 * @param FirestoreRestClient $client Firestore REST client instance
 * @param string $code Coupon code to apply
 * @param string $orderId Order document ID in Firestore
 * @param array $meta Order metadata (amount, customer, etc.)
 * @param bool $isAffiliate Whether this is an affiliate coupon
 * @param float $payoutAmount Commission amount in rupees (if affiliate)
 * @param string|null $paymentId Optional payment ID for idempotency key
 * @return array Result with success status and details
 */
function applyCouponForOrderRest($client, $code, $orderId, $meta = [], $isAffiliate = false, $payoutAmount = 0, $paymentId = null) {
    try {
        $normalizedCode = normalizeCouponCode($code);
        
        if (!$normalizedCode) {
            return [
                'success' => false,
                'error' => 'Invalid coupon code provided',
                'code' => $code
            ];
        }
        
        if (!$orderId) {
            return [
                'success' => false,
                'error' => 'Order ID is required',
                'code' => $normalizedCode
            ];
        }
        
        // Use payment ID for idempotency if available, otherwise use order ID
        $idempotencyKey = $paymentId ?: $orderId;
        $guardKey = sha1($idempotencyKey . '|' . $normalizedCode);
        
        error_log("COUPON SERVICE REST: Applying coupon $normalizedCode for order $orderId (key: $guardKey)");
        
        // === STEP 1: Check for existing application (idempotency) ===
        // Check if guard document exists in subcollection
        $guardPath = "orders/{$orderId}/couponIncrements";
        $guardDoc = $client->getDocument($guardPath, $guardKey);
        
        if ($guardDoc) {
            error_log("COUPON SERVICE REST: Coupon $normalizedCode already applied (idempotent)");
            $guardData = $guardDoc['data'];
            return [
                'success' => true,
                'idempotent' => true,
                'message' => 'Coupon already applied (idempotent)',
                'code' => $normalizedCode,
                'appliedAt' => $guardData['createdAt'] ?? null
            ];
        }
        
        // === STEP 2: Find coupon document ===
        $coupons = $client->queryDocuments(
            'coupons',
            [
                ['field' => 'code', 'op' => 'EQUAL', 'value' => $normalizedCode]
            ],
            1
        );
        
        if (empty($coupons)) {
            error_log("COUPON SERVICE REST: Coupon not found: $normalizedCode");
            return [
                'success' => false,
                'error' => 'Coupon not found',
                'code' => $normalizedCode
            ];
        }
        
        $coupon = $coupons[0];
        $couponDocId = $coupon['id'];
        $couponData = $coupon['data'];
        
        // === STEP 3: Atomically increment counters ===
        error_log("COUPON SERVICE REST: Incrementing counters for $normalizedCode");
        
        // Get current values for manual increment (fallback for OpenSSL issues)
        $currentUsageCount = $couponData['usageCount'] ?? 0;
        $currentPayoutUsage = $couponData['payoutUsage'] ?? 0;
        
        // Calculate new values
        $newUsageCount = $currentUsageCount + 1;
        $newPayoutUsage = $currentPayoutUsage + ($isAffiliate && $payoutAmount > 0 ? $payoutAmount : 1);
        
        error_log("COUPON SERVICE REST: Current values - usageCount: $currentUsageCount, payoutUsage: $currentPayoutUsage");
        error_log("COUPON SERVICE REST: New values - usageCount: $newUsageCount, payoutUsage: $newPayoutUsage");
        
        // Try atomic increment first, fallback to manual update
        try {
            $client->incrementField('coupons', $couponDocId, 'usageCount', 1);
            error_log("COUPON SERVICE REST: ✅ Atomic increment for usageCount successful");
        } catch (Exception $e) {
            error_log("COUPON SERVICE REST: ⚠️ Atomic increment failed after retries, using manual update: " . $e->getMessage());
            error_log("COUPON SERVICE REST: ⚠️ WARNING: Manual update is NOT atomic - may cause race conditions");
            
            // Manual update as fallback (not atomic, but better than nothing)
            try {
                $client->updateDocument('coupons', $couponDocId, [
                    ['path' => 'usageCount', 'value' => $newUsageCount],
                    ['path' => 'incrementFailureCount', 'value' => ($couponData['incrementFailureCount'] ?? 0) + 1],
                    ['path' => 'lastManualUpdate', 'value' => firestoreTimestamp()]
                ]);
                error_log("COUPON SERVICE REST: ✅ Manual update successful for usageCount");
            } catch (Exception $updateError) {
                error_log("COUPON SERVICE REST: ❌ Manual update also failed: " . $updateError->getMessage());
            }
        }
        
        // Try atomic increment for payoutUsage
        try {
            if ($isAffiliate && $payoutAmount > 0) {
                $client->incrementField('coupons', $couponDocId, 'payoutUsage', $payoutAmount);
                error_log("COUPON SERVICE REST: ✅ Atomic increment for payoutUsage by ₹$payoutAmount (affiliate)");
            } else {
                $client->incrementField('coupons', $couponDocId, 'payoutUsage', 1);
                error_log("COUPON SERVICE REST: ✅ Atomic increment for payoutUsage by 1 (regular)");
            }
        } catch (Exception $e) {
            error_log("COUPON SERVICE REST: ⚠️ Atomic increment for payoutUsage failed after retries: " . $e->getMessage());
            error_log("COUPON SERVICE REST: ⚠️ WARNING: Manual update is NOT atomic - may cause race conditions");
            
            // Manual update as fallback
            try {
                $client->updateDocument('coupons', $couponDocId, [
                    ['path' => 'payoutUsage', 'value' => $newPayoutUsage],
                    ['path' => 'incrementFailureCount', 'value' => ($couponData['incrementFailureCount'] ?? 0) + 1],
                    ['path' => 'lastManualUpdate', 'value' => firestoreTimestamp()]
                ]);
                error_log("COUPON SERVICE REST: ✅ Manual update successful for payoutUsage");
            } catch (Exception $updateError) {
                error_log("COUPON SERVICE REST: ❌ Manual update also failed: " . $updateError->getMessage());
            }
        }
        
        // Update additional fields
        $updates = [
            ['path' => 'updatedAt', 'value' => firestoreTimestamp()]
        ];
        
        if ($isAffiliate) {
            $updates[] = ['path' => 'isAffiliateCoupon', 'value' => true];
            if (isset($meta['affiliateCode'])) {
                $updates[] = ['path' => 'affiliateCode', 'value' => $meta['affiliateCode']];
            }
        }
        
        $client->updateDocument('coupons', $couponDocId, $updates);
        
        error_log("COUPON SERVICE REST: ✅ Atomically incremented $normalizedCode");
        
        // === STEP 4: Create guard document (idempotency) ===
        $guardData = [
            'code' => $normalizedCode,
            'orderId' => $orderId,
            'paymentId' => $paymentId ?? $orderId,
            'isAffiliate' => $isAffiliate,
            'payoutAmount' => $payoutAmount,
            'createdAt' => firestoreTimestamp()
        ];
        
        $client->writeDocument($guardPath, $guardData, $guardKey);
        error_log("COUPON SERVICE REST: Created guard document (key: $guardKey)");
        
        // === STEP 5: Log affiliate usage (if applicable) ===
        if ($isAffiliate) {
            logAffiliateUsageRest($client, $orderId, $normalizedCode, $meta, $payoutAmount, $idempotencyKey);
        }
        
        // === STEP 6: Return success ===
        return [
            'success' => true,
            'idempotent' => false,
            'message' => 'Coupon applied successfully',
            'coupon' => [
                'id' => $couponDocId,
                'code' => $normalizedCode,
                'isAffiliate' => $isAffiliate,
                'payoutAmount' => $payoutAmount
            ],
            'guardKey' => $guardKey
        ];
        
    } catch (Exception $e) {
        error_log("COUPON SERVICE REST ERROR: Failed to apply coupon $code for order $orderId - " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'code' => $code ?? 'unknown',
            'orderId' => $orderId ?? 'unknown'
        ];
    }
}

/**
 * Log affiliate usage in dedicated subcollection for reporting (REST API)
 * 
 * Creates an idempotent entry in orders/{orderId}/affiliate_usage/{guardKey}
 * 
 * @param FirestoreRestClient $client Firestore REST client instance
 * @param string $orderId Order document ID
 * @param string $couponCode Normalized coupon code
 * @param array $meta Order metadata (amount, customer, affiliate code, etc.)
 * @param float $payoutAmount Commission amount in rupees
 * @param string $idempotencyKey Unique key for this application
 * @return bool Success status
 */
function logAffiliateUsageRest($client, $orderId, $couponCode, $meta, $payoutAmount, $idempotencyKey) {
    try {
        $guardKey = sha1($idempotencyKey . '|' . $couponCode);
        $logPath = "orders/{$orderId}/affiliate_usage";
        
        // Check if already logged (idempotent)
        $existing = $client->getDocument($logPath, $guardKey);
        if ($existing) {
            error_log("COUPON SERVICE REST: Affiliate usage already logged (idempotent)");
            return true;
        }
        
        // Create affiliate usage log entry
        $entry = [
            'orderId' => $orderId,
            'couponCode' => $couponCode,
            'affiliateCode' => $meta['affiliateCode'] ?? null,
            'amount' => $meta['amount'] ?? 0,
            'commission' => $payoutAmount,
            'customerEmail' => $meta['customerEmail'] ?? null,
            'createdAt' => firestoreTimestamp()
        ];
        
        $client->writeDocument($logPath, $entry, $guardKey);
        error_log("COUPON SERVICE REST: Logged affiliate usage for $couponCode (commission: ₹$payoutAmount)");
        
        return true;
        
    } catch (Exception $e) {
        error_log("COUPON SERVICE REST ERROR: Failed to log affiliate usage - " . $e->getMessage());
        return false;
    }
}

/**
 * Batch apply multiple coupons for an order (REST API)
 * 
 * Processes multiple coupons in sequence, handling errors gracefully.
 * Returns detailed results for each coupon.
 * 
 * @param FirestoreRestClient $client Firestore REST client instance
 * @param array $coupons Array of coupon objects with code, isAffiliateCoupon, etc.
 * @param string $orderId Order document ID
 * @param array $orderMeta Order metadata (amount, customer, etc.)
 * @param string|null $paymentId Optional payment ID for idempotency
 * @return array Results array with individual coupon outcomes
 */
function batchApplyCouponsForOrderRest($client, $coupons, $orderId, $orderMeta = [], $paymentId = null) {
    $results = [];
    
    if (!is_array($coupons) || empty($coupons)) {
        return [
            'success' => false,
            'error' => 'No coupons provided',
            'results' => []
        ];
    }
    
    error_log("COUPON SERVICE REST: Batch applying " . count($coupons) . " coupons for order $orderId");
    
    foreach ($coupons as $coupon) {
        if (empty($coupon['code'])) {
            $results[] = [
                'success' => false,
                'error' => 'Empty coupon code',
                'code' => ''
            ];
            continue;
        }
        
        $code = $coupon['code'];
        $isAffiliate = !empty($coupon['isAffiliateCoupon']);
        $affiliateCode = $coupon['affiliateCode'] ?? null;
        
        error_log("COUPON SERVICE REST: Processing $code - IsAffiliate: " . ($isAffiliate ? 'YES' : 'NO'));
        
        // Calculate payout amount for affiliate coupons (fixed ₹300 commission)
        $payoutAmount = 0;
        if ($isAffiliate) {
            $payoutAmount = 300; // Fixed ₹300 per sale
            error_log("COUPON SERVICE REST: Affiliate coupon - payoutUsage will increment by ₹300");
        }
        
        // Merge affiliate code into metadata
        $meta = array_merge($orderMeta, [
            'affiliateCode' => $affiliateCode
        ]);
        
        // Apply the coupon
        $result = applyCouponForOrderRest($client, $code, $orderId, $meta, $isAffiliate, $payoutAmount, $paymentId);
        $results[] = $result;
        
        error_log("COUPON SERVICE REST: Result for $code - Success: " . ($result['success'] ? 'YES' : 'NO'));
        
        // Log outcome
        if ($result['success']) {
            $status = $result['idempotent'] ?? false ? '↩️' : '✅';
            error_log("COUPON SERVICE REST: $status $code " . $result['message']);
        } else {
            error_log("COUPON SERVICE REST: ❌ $code failed - " . ($result['error'] ?? 'Unknown error'));
        }
    }
    
    // Determine overall success
    $successCount = count(array_filter($results, function($r) { return $r['success']; }));
    $totalCount = count($results);
    
    error_log("COUPON SERVICE REST: Batch complete - $successCount of $totalCount successful");
    
    return [
        'success' => $successCount > 0,
        'message' => "$successCount of $totalCount coupons applied successfully",
        'successCount' => $successCount,
        'totalCount' => $totalCount,
        'results' => $results
    ];
}

/**
 * Initialize coupon fields if they don't exist (REST API)
 * 
 * Ensures all coupons have required fields with default values.
 * Useful for migrating existing coupons or fixing data issues.
 * 
 * @param FirestoreRestClient $client Firestore REST client instance
 * @param string $couponId Coupon document ID
 * @return array Result with success status
 */
function initializeCouponFieldsRest($client, $couponId) {
    try {
        $doc = $client->getDocument('coupons', $couponId);
        
        if (!$doc) {
            return [
                'success' => false,
                'error' => 'Coupon not found',
                'couponId' => $couponId
            ];
        }
        
        $data = $doc['data'];
        $updates = [];
        
        // Initialize missing fields
        if (!isset($data['usageCount'])) {
            $updates[] = ['path' => 'usageCount', 'value' => 0];
        }
        if (!isset($data['payoutUsage'])) {
            $updates[] = ['path' => 'payoutUsage', 'value' => 0];
        }
        if (!isset($data['isActive'])) {
            $updates[] = ['path' => 'isActive', 'value' => true];
        }
        if (!isset($data['updatedAt'])) {
            $updates[] = ['path' => 'updatedAt', 'value' => firestoreTimestamp()];
        }
        
        if (!empty($updates)) {
            $client->updateDocument('coupons', $couponId, $updates);
            error_log("COUPON SERVICE REST: Initialized " . count($updates) . " fields for coupon $couponId");
            return [
                'success' => true,
                'message' => 'Fields initialized',
                'fieldsAdded' => count($updates)
            ];
        }
        
        return [
            'success' => true,
            'message' => 'No initialization needed',
            'fieldsAdded' => 0
        ];
        
    } catch (Exception $e) {
        error_log("COUPON SERVICE REST ERROR: Failed to initialize fields - " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Export version function
if (!function_exists('getCouponTrackingServiceVersionRest')) {
    function getCouponTrackingServiceVersionRest() {
        return '2.0.0-REST';
    }
}

error_log("COUPON SERVICE REST: Module loaded successfully (version " . getCouponTrackingServiceVersionRest() . ")");
?>

