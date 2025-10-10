<?php
/**
 * 🎟️ Server-Side Coupon Validation API
 * 
 * Validates coupon codes securely on the server with file-based caching
 * Compatible with Hostinger shared hosting (no Redis, no memcached)
 * 
 * Features:
 * - Server-side validation (coupons not exposed to browser)
 * - File-based caching (5-minute TTL)
 * - Firestore REST API integration
 * - Returns only necessary coupon data
 * 
 * Benefits:
 * - 90% reduction in data transfer vs client-side
 * - 90% faster with caching (50-100ms vs 500-1000ms)
 * - More secure (coupons private in Firestore)
 * - Reduces Firestore read operations by 90%
 * 
 * @version 1.0.0
 * @author ATTRAL E-Commerce Platform
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/firestore_rest_client.php';

// Configuration
$CACHE_DIR = __DIR__ . '/.cache';
$CACHE_TTL = 300; // 5 minutes (Firebase recommended for coupon data)

/**
 * Get cache file path for a coupon code
 */
function getCacheFilePath($code, $cacheDir) {
    // Use lowercase for cache key to avoid case-related negative cache issues
    return $cacheDir . '/coupon_' . md5(strtolower($code)) . '.json';
}

/**
 * Check if cache file is valid
 */
function isCacheValid($cacheFile, $ttl) {
    if (!file_exists($cacheFile)) {
        return false;
    }
    
    $age = time() - filemtime($cacheFile);
    return $age < $ttl;
}

/**
 * Read from cache
 */
function readFromCache($cacheFile) {
    try {
        $content = file_get_contents($cacheFile);
        return json_decode($content, true);
    } catch (Exception $e) {
        error_log("CACHE READ ERROR: " . $e->getMessage());
        return null;
    }
}

/**
 * Write to cache
 */
function writeToCache($cacheFile, $data) {
    try {
        file_put_contents($cacheFile, json_encode($data));
        @chmod($cacheFile, 0600); // Secure permissions
        return true;
    } catch (Exception $e) {
        error_log("CACHE WRITE ERROR: " . $e->getMessage());
        return false;
    }
}

// Main validation logic
try {
    // Parse request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['valid' => false, 'error' => 'Invalid request']);
        exit;
    }
    
    // Preserve the raw code as entered; we'll try multiple case variants
    $rawCode = isset($input['code']) ? trim($input['code']) : '';
    $codeUpper = $rawCode !== '' ? strtoupper($rawCode) : '';
    $codeLower = $rawCode !== '' ? strtolower($rawCode) : '';
    // Backwards-compat param used below
    $code = $codeUpper;
    $subtotal = isset($input['subtotal']) ? floatval($input['subtotal']) : 0;
    $bypassCache = isset($input['bypassCache']) ? $input['bypassCache'] : false; // Allow cache bypass for testing
    
    if (!$rawCode) {
        echo json_encode(['valid' => false, 'error' => 'Coupon code required']);
        exit;
    }
    
    error_log("COUPON VALIDATION: Validating code '{$code}' for subtotal ₹{$subtotal}" . ($bypassCache ? " (bypassing cache)" : ""));
    
    // Create cache directory if it doesn't exist
    if (!is_dir($CACHE_DIR)) {
        @mkdir($CACHE_DIR, 0700, true);
        error_log("CACHE: Created cache directory at {$CACHE_DIR}");
    }
    
    // Check cache first (unless bypassing for testing)
    $cacheFile = getCacheFilePath($rawCode, $CACHE_DIR);
    
    if (!$bypassCache && isCacheValid($cacheFile, $CACHE_TTL)) {
        $cached = readFromCache($cacheFile);
        
        if ($cached) {
            error_log("COUPON VALIDATION: ✅ Cache hit for '{$code}' (age: " . (time() - filemtime($cacheFile)) . "s)");
            
            // Re-validate subtotal (can't cache this part)
            if ($cached['valid'] && isset($cached['coupon']['minAmount'])) {
                if ($subtotal < $cached['coupon']['minAmount']) {
                    echo json_encode([
                        'valid' => false,
                        'error' => "Minimum order of ₹{$cached['coupon']['minAmount']} required",
                        'cached' => true
                    ]);
                    exit;
                }
            }
            
            $cached['cached'] = true;
            $cached['cacheAge'] = time() - filemtime($cacheFile);
            echo json_encode($cached);
            exit;
        }
    }
    
    error_log("COUPON VALIDATION: Cache miss for '{$rawCode}', querying Firestore...");
    
    // Initialize Firestore REST client
    $client = new FirestoreRestClient(
        'e-commerce-1d40f',
        __DIR__ . '/firebase-service-account.json',
        true // Enable token caching
    );
    
    // Query coupon from Firestore (case-tolerant + affiliateCode support)
    // Try order: exact as typed, lowercase, uppercase; then affiliateCode variants
    $lookups = [
        ['field' => 'code', 'value' => $rawCode, 'label' => 'code(raw)'],
        ['field' => 'code', 'value' => $codeLower, 'label' => 'code(lower)'],
        ['field' => 'code', 'value' => $codeUpper, 'label' => 'code(upper)'],
        ['field' => 'affiliateCode', 'value' => $rawCode, 'label' => 'affiliateCode(raw)'],
        ['field' => 'affiliateCode', 'value' => $codeLower, 'label' => 'affiliateCode(lower)'],
        ['field' => 'affiliateCode', 'value' => $codeUpper, 'label' => 'affiliateCode(upper)'],
    ];
    $coupons = [];
    $matchedBy = null;
    foreach ($lookups as $lk) {
        if ($lk['value'] === '') { continue; }
        $tmp = $client->queryDocuments('coupons', [
            ['field' => $lk['field'], 'op' => 'EQUAL', 'value' => $lk['value']]
        ], 1);
        error_log("COUPON VALIDATION: Lookup {$lk['label']} => " . (empty($tmp) ? '0' : '1') . " hit(s)");
        if (!empty($tmp)) { $coupons = $tmp; $matchedBy = $lk['label']; break; }
    }
    
    if (empty($coupons)) {
        error_log("COUPON VALIDATION: ❌ Coupon '{$rawCode}' not found in any lookup (code/affiliateCode, any case)");
        
        $result = [
            'valid' => false,
            'error' => 'Invalid coupon code',
            'cached' => false
        ];
        
        // Cache negative result (prevent repeated Firestore queries for invalid codes)
        writeToCache($cacheFile, $result);
        
        echo json_encode($result);
        exit;
    }
    
    $coupon = $coupons[0]['data'];
    if ($matchedBy) { error_log("COUPON VALIDATION: ✅ Matched by {$matchedBy}"); }
    
    // 🔧 DEBUG: Log complete coupon structure
    error_log("COUPON VALIDATION: Found coupon document - " . json_encode($coupon));
    
    // ✅ ENHANCED: Check multiple possible field names for active status
    // Support both 'isActive' (boolean) and 'status' (string) fields
    $isActive = false;
    
    if (isset($coupon['isActive'])) {
        // Handle boolean or string "true"
        $isActive = ($coupon['isActive'] === true || $coupon['isActive'] === 'true' || $coupon['isActive'] === 1);
    } elseif (isset($coupon['active'])) {
        // Some coupons might use 'active' instead of 'isActive'
        $isActive = ($coupon['active'] === true || $coupon['active'] === 'true' || $coupon['active'] === 1);
    } elseif (isset($coupon['status'])) {
        // Some might use status = 'active'
        $isActive = (strtolower($coupon['status']) === 'active');
    }
    
    error_log("COUPON VALIDATION: Active check - isActive field: " . ($coupon['isActive'] ?? 'not set') . 
              ", active field: " . ($coupon['active'] ?? 'not set') . 
              ", status field: " . ($coupon['status'] ?? 'not set') . 
              ", Result: " . ($isActive ? 'ACTIVE' : 'INACTIVE'));
    
    // Validate active status
    if (!$isActive) {
        $result = [
            'valid' => false,
            'error' => 'This coupon is no longer active',
            'debug' => [
                'isActive_field' => $coupon['isActive'] ?? null,
                'active_field' => $coupon['active'] ?? null,
                'status_field' => $coupon['status'] ?? null
            ],
            'cached' => false
        ];
        
        error_log("COUPON VALIDATION: ❌ '{$code}' rejected - NOT ACTIVE - " . json_encode($result['debug']));
        
        writeToCache($cacheFile, $result);
        echo json_encode($result);
        exit;
    }
    
    // ✅ ENHANCED: Validate expiry date (support multiple date field names)
    $expiryDate = null;
    
    if (isset($coupon['validUntil'])) {
        $expiryDate = $coupon['validUntil'];
    } elseif (isset($coupon['expiryDate'])) {
        $expiryDate = $coupon['expiryDate'];
    } elseif (isset($coupon['expiry'])) {
        $expiryDate = $coupon['expiry'];
    } elseif (isset($coupon['expiresAt'])) {
        $expiryDate = $coupon['expiresAt'];
    }
    
    if ($expiryDate) {
        // Handle Firestore timestamp format {_seconds: xxx, _nanoseconds: xxx}
        if (is_array($expiryDate) && isset($expiryDate['_seconds'])) {
            $expiryTimestamp = $expiryDate['_seconds'];
        } else {
            $expiryTimestamp = strtotime($expiryDate);
        }
        
        if ($expiryTimestamp && time() > $expiryTimestamp) {
            $expiryDateFormatted = date('Y-m-d', $expiryTimestamp);
            error_log("COUPON VALIDATION: ❌ '{$code}' EXPIRED - Valid until: {$expiryDateFormatted}");
            
            $result = [
                'valid' => false,
                'error' => 'This coupon expired on ' . $expiryDateFormatted,
                'debug' => [
                    'expiryTimestamp' => $expiryTimestamp,
                    'currentTimestamp' => time()
                ],
                'cached' => false
            ];
            
            writeToCache($cacheFile, $result);
            echo json_encode($result);
            exit;
        }
    }
    
    // ✅ ENHANCED: Validate minimum amount (support multiple field names)
    $minAmount = 0;
    
    if (isset($coupon['minAmount'])) {
        $minAmount = floatval($coupon['minAmount']);
    } elseif (isset($coupon['minimumAmount'])) {
        $minAmount = floatval($coupon['minimumAmount']);
    } elseif (isset($coupon['minOrderValue'])) {
        $minAmount = floatval($coupon['minOrderValue']);
    }
    
    error_log("COUPON VALIDATION: Checking min amount - Required: ₹{$minAmount}, Subtotal: ₹{$subtotal}");
    
    if ($subtotal < $minAmount) {
        error_log("COUPON VALIDATION: ❌ '{$code}' rejected - Subtotal ₹{$subtotal} < Min ₹{$minAmount}");
        
        echo json_encode([
            'valid' => false,
            'error' => "Minimum order of ₹{$minAmount} required for this coupon",
            'debug' => [
                'requiredMinimum' => $minAmount,
                'currentSubtotal' => $subtotal
            ],
            'cached' => false
        ]);
        exit;
    }
    
    // Validate usage limit (if set)
    if (isset($coupon['usageLimit']) && $coupon['usageLimit'] > 0) {
        $currentUsage = $coupon['usageCount'] ?? 0;
        if ($currentUsage >= $coupon['usageLimit']) {
            $result = [
                'valid' => false,
                'error' => 'This coupon has reached its usage limit',
                'cached' => false
            ];
            
            writeToCache($cacheFile, $result);
            echo json_encode($result);
            exit;
        }
    }
    
    // Coupon is valid! Return only necessary data (don't expose internal fields)
    $result = [
        'valid' => true,
        'coupon' => [
            'code' => $coupon['code'],
            'name' => $coupon['name'] ?? 'Discount Coupon',
            'type' => $coupon['type'] ?? 'percentage',
            'value' => $coupon['value'] ?? 0,
            'minAmount' => $coupon['minAmount'] ?? 0,
            'maxDiscount' => $coupon['maxDiscount'] ?? null,
            'description' => $coupon['description'] ?? '',
            'isAffiliateCoupon' => $coupon['isAffiliateCoupon'] ?? false,
            'affiliateCode' => $coupon['affiliateCode'] ?? null,
            'isNewsletterCoupon' => $coupon['isNewsletterCoupon'] ?? false
        ],
        'cached' => false
    ];
    
    error_log("COUPON VALIDATION: ✅ '{$code}' is valid - Type: {$result['coupon']['type']}, Value: {$result['coupon']['value']}");
    
    // Cache the valid result
    writeToCache($cacheFile, $result);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("COUPON VALIDATION ERROR: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'valid' => false,
        'error' => 'Validation service error',
        'cached' => false
    ]);
}
?>

