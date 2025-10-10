# 📋 AFFILIATE TRACKING - COMPLETE IMPLEMENTATION PLAN

## Overview
Fix affiliate coupon tracking system by correcting database query mismatches and adding comprehensive debugging.

---

## Phase 1: Fix Critical Database Queries

### Task 1.1: Fix Affiliate Profile Lookup in getAffiliateStats()
**File**: `static-site/api/affiliate_functions.php`  
**Line**: 222

**Current Code**:
```php
$affiliatesQuery = $firestore->collection('affiliates')
    ->where('affiliateCode', '=', $code)  // ❌ WRONG FIELD
```

**Fixed Code**:
```php
$affiliatesQuery = $firestore->collection('affiliates')
    ->where('code', '=', $code)  // ✅ CORRECT FIELD
```

**Why**: Affiliates are stored with field `code`, not `affiliateCode` (verified in firestore_order_manager.php line 684, sync_affiliates_to_brevo.php line 103)

---

### Task 1.2: Completely Rewrite getAffiliateOrders()  
**File**: `static-site/api/affiliate_functions.php`  
**Lines**: 139-193

**Problem**: Queries non-existent `couponCode` field

**Solution**: Query ALL confirmed orders, manually filter by coupons array

**New Implementation**:
```php
function getAffiliateOrders($firestore) {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    
    try {
        // Support both GET and POST
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $code = $_GET['code'] ?? '';
            $status = $_GET['status'] ?? 'all';
            $pageSize = (int)($_GET['pageSize'] ?? 100);
        } else {
            $input = json_decode(file_get_contents('php://input'), true);
            $code = $input['code'] ?? '';
            $status = $input['status'] ?? 'all';
            $pageSize = (int)($input['pageSize'] ?? 100);
        }
        
        if (empty($code)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Affiliate code is required']);
            return;
        }
        
        // 🔍 DEBUG: Log query parameters
        error_log("AFFILIATE ORDERS: Querying orders for code=$code, status=$status, pageSize=$pageSize");
        
        // Query ALL confirmed orders (cannot filter by array field directly)
        $query = $firestore->collection('orders')
            ->where('status', '=', 'confirmed')
            ->orderBy('createdAt', 'DESC')
            ->limit($pageSize);
        
        $documents = $query->documents();
        $orders = [];
        $totalAmount = 0;
        $processedCount = 0;
        $matchedOrderIds = [];
        
        foreach ($documents as $doc) {
            if ($doc->exists()) {
                $processedCount++;
                $orderData = $doc->data();
                $coupons = $orderData['coupons'] ?? [];
                
                // 🔍 DEBUG: Log coupon check
                if ($processedCount <= 5) {  // Only log first 5 for brevity
                    error_log("AFFILIATE ORDERS: Order " . ($orderData['orderId'] ?? $doc->id()) . " has " . count($coupons) . " coupons: " . json_encode(array_column($coupons, 'code')));
                }
                
                // Manually check if this order used the affiliate's coupon
                $hasAffiliateCoupon = false;
                foreach ($coupons as $coupon) {
                    if (isset($coupon['code']) && $coupon['code'] === $code) {
                        $hasAffiliateCoupon = true;
                        $matchedOrderIds[] = $orderData['orderId'] ?? $doc->id();
                        break;
                    }
                }
                
                if ($hasAffiliateCoupon) {
                    $orderData['id'] = $doc->id();
                    $orders[] = $orderData;
                    $totalAmount += ($orderData['amount'] ?? 0);
                }
            }
        }
        
        // 🔍 DEBUG: Log results
        error_log("AFFILIATE ORDERS: Processed $processedCount total orders, found " . count($orders) . " matching orders");
        error_log("AFFILIATE ORDERS: Matched order IDs: " . implode(', ', $matchedOrderIds));
        error_log("AFFILIATE ORDERS: Total amount: ₹$totalAmount");
        
        echo json_encode([
            'success' => true,
            'orders' => $orders,
            'total' => count($orders),          // ✅ Add required field
            'totalAmount' => $totalAmount,      // ✅ Add required field
            'nextPageToken' => null
        ]);
        
    } catch (Exception $e) {
        error_log("AFFILIATE ORDERS ERROR: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
```

---

## Phase 2: Add Debug Logging

### Task 2.1: Frontend Logging - order.html
**File**: `static-site/order.html`

Add before line where coupons are sent:
```javascript
console.log('📦 [ORDER] Coupons being sent:', {
  count: orderData.coupons.length,
  coupons: orderData.coupons.map(c => ({
    code: c.code,
    isAffiliate: c.isAffiliateCoupon,
    affiliateCode: c.affiliateCode
  }))
});
```

---

### Task 2.2: Backend Logging - getAffiliateStats()
**File**: `static-site/api/affiliate_functions.php`

Add comprehensive logging:
```php
error_log("AFFILIATE STATS: Querying for code=$code");
error_log("AFFILIATE STATS: Processed $processedOrders orders, matched $totalReferrals");
error_log("AFFILIATE STATS: Results - Earnings=₹$totalEarnings, Referrals=$totalReferrals, Monthly=₹$monthlyEarnings");
```

---

### Task 2.3: Admin Panel Logging - coupon-admin.html
**File**: `static-site/coupon-admin.html`

Add in `updateCouponStatsRealtime()`:
```javascript
console.log('📊 [ADMIN] Updating stats from Firestore...');
couponsSnapshot.forEach(doc => {
  const data = doc.data();
  console.log('🎫 [ADMIN] Coupon:', data.code, 'Usage:', data.usageCount, 'Payout:', data.payoutUsage);
});
console.log('✅ [ADMIN] Stats updated:', { totalCoupons, activeCoupons, totalUsage, totalCommissions });
```

---

### Task 2.4: Dashboard Logging - affiliate-dashboard.html
**File**: `static-site/affiliate-dashboard.html`

Add in `updateStats()` and `renderReferredOrders()`:
```javascript
console.log('📊 [DASHBOARD] Loading stats for code:', code);
console.log('✅ [DASHBOARD] Stats loaded:', stats);

console.log('📋 [DASHBOARD] Loading orders for code:', code);
console.log('✅ [DASHBOARD] Orders loaded:', { count: orders.length, total, totalAmount });
```

---

## Phase 3: Verification Steps

### Step 1: Check Backend Logs
After creating an order with affiliate code, check logs for:
```
COUPON SERVICE: Batch applying 1 coupons for order ORDER-ID
COUPON SERVICE: Processing coupon - Code: AFFILIATE-CODE, IsAffiliate: YES
COUPON SERVICE: Affiliate coupon detected - will increment payoutUsage by ₹300
COUPON SERVICE: ✅ AFFILIATE-CODE Coupon applied successfully
```

### Step 2: Check Firestore Console
Verify in Firebase Console:
1. `coupons/{docId}`:
   - `usageCount` incremented
   - `payoutUsage` = 300 (or multiple of 300)
   
2. `orders/{docId}`:
   - `coupons` array exists
   - Contains affiliate code with proper metadata

3. `affiliates/{docId}`:
   - `code` field exists (not `affiliateCode`)

### Step 3: Check Affiliate Dashboard
Should display:
- Total Earnings: ₹300+
- Total Referrals: 1+
- Orders list populated
- Console shows successful API calls

### Step 4: Check Admin Panel
Should display:
- Coupon usage: "1 uses (₹300 earned)"
- Stats auto-update every 30 seconds
- Console shows successful Firestore queries

---

## Files to Modify

1. ✅ `static-site/api/affiliate_functions.php` - Fix queries (2 locations)
2. ✅ `static-site/order.html` - Add debug logging
3. ✅ `static-site/affiliate-dashboard.html` - Add debug logging
4. ✅ `static-site/coupon-admin.html` - Add debug logging

**Total Changes**: 4 files, ~150 lines of code

---

## Success Criteria

✅ Affiliate dashboard shows correct earnings  
✅ Affiliate dashboard shows referred orders  
✅ Admin panel shows coupon usage with ₹ amounts  
✅ Console logs show complete data flow  
✅ No "Affiliate not found" errors  
✅ No empty orders list when orders exist  

---

## Risk Assessment

### Low Risk
- Changes are isolated to affiliate tracking
- No changes to payment or order creation logic
- Logging adds visibility without changing behavior

### Medium Risk
- Query performance (filtering ALL confirmed orders)
- Solution: Add index on `status` field in Firestore

### Mitigation
- Add error handling for all queries
- Keep pageSize limit (100 orders max)
- Add timeout handling in frontend

---

## Estimated Time

- **Phase 1** (Fixes): 15 minutes
- **Phase 2** (Logging): 20 minutes
- **Phase 3** (Testing): 25 minutes
- **Total**: ~60 minutes

---

## Next Steps

1. Review this plan
2. Approve for implementation
3. I'll apply all fixes in sequence
4. Test with real data
5. Verify console logs show correct flow
6. Provide final verification report


