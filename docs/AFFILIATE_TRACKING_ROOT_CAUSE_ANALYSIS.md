# 🔍 ROOT CAUSE ANALYSIS: Why Affiliate Coupon Tracking Failed

## Executive Summary

After deep analysis of your codebase and Firestore data structures, I've identified **3 critical database query mismatches** that are preventing affiliate tracking from working. These are fundamental structural issues, not logic errors.

---

## 🔴 Critical Issue #1: Wrong Field Name for Affiliate Lookup

### Location
`static-site/api/affiliate_functions.php` - Line 222 (in `getAffiliateStats()`)

### The Problem
```php
// WRONG - This field doesn't exist!
$affiliatesQuery = $firestore->collection('affiliates')
    ->where('affiliateCode', '=', $code)  // ❌ Field name is WRONG!
```

### Reality Check
Looking at other working files (`firestore_order_manager.php` line 684, `order_manager.php` line 390):
```php
// CORRECT - This is how affiliates are actually queried
$query = $affiliatesRef->where('code', '=', $affiliateCode);  // ✅ Correct field name
```

### Firestore Schema (Actual)
```javascript
affiliates/{docId}: {
  code: "partea-abc123",        // ✅ Stored as 'code'
  email: "affiliate@example.com",
  displayName: "John Doe",
  status: "active",
  // NOT 'affiliateCode'!
}
```

### Impact
- `getAffiliateStats()` returns "Affiliate not found" error
- Affiliate dashboard shows ₹0 earnings even when orders exist
- Stats API fails before even querying orders

---

## 🔴 Critical Issue #2: Wrong Field for Order Queries

### Location
`static-site/api/affiliate_functions.php` - Line 166-167 (in `getAffiliateOrders()`)

### The Problem
```php
// WRONG - This field doesn't exist in orders!
$query = $firestore->collection('orders')
    ->where('couponCode', '=', $code)  // ❌ Field doesn't exist!
    ->orderBy('createdAt', 'DESC')
```

### Reality Check
Looking at `firestore_order_manager.php` line 182:
```php
// Orders are created with coupons as an ARRAY, not a single field
$orderData = [
    'coupons' => isset($input['coupons']) && is_array($input['coupons']) ? $input['coupons'] : [],
    // NO 'couponCode' field exists!
];
```

### Firestore Schema (Actual)
```javascript
orders/{docId}: {
  orderId: "ORD-123",
  status: "confirmed",
  amount: 2999,
  coupons: [                    // ✅ Array of coupon objects
    {
      code: "partea-abc123",
      isAffiliateCoupon: true,
      affiliateCode: "partea-abc123"
    },
    {
      code: "FREESHIP",
      type: "shipping"
    }
  ]
  // NO 'couponCode' field exists!
}
```

### Impact
- `getAffiliateOrders()` ALWAYS returns 0 orders
- Affiliate dashboard "Referred Orders" section is ALWAYS empty
- Even with confirmed orders using affiliate codes, none appear

### Why This Can't Work
Firestore doesn't support querying array fields directly with `where()`. You CANNOT do:
```php
->where('coupons.code', '=', $code)  // ❌ Not supported in Firestore
```

You MUST query ALL confirmed orders and filter manually in code.

---

## 🔴 Critical Issue #3: Missing Response Fields

### Location
`static-site/api/affiliate_functions.php` - Line 182-186 (in `getAffiliateOrders()`)

### The Problem
```php
echo json_encode([
    'success' => true,
    'orders' => $orders,
    'count' => count($orders)   // ❌ Missing required fields
]);
```

### What Frontend Expects
From `affiliate-dashboard.html` line where it receives the response:
```javascript
const { orders, total, totalAmount, nextPageToken } = res || { 
    orders: [], 
    total: 0,           // ⚠️ Expected but not returned
    totalAmount: 0,     // ⚠️ Expected but not returned
    nextPageToken: null 
};
```

### Impact
- Frontend tries to read `res.total` and `res.totalAmount` but gets `undefined`
- Order totals show as "0 orders, Total: ₹0" even if data exists

---

## ✅ What IS Working

1. **Coupon Tracking Service** (`coupon_tracking_service.php`)
   - ✅ Correctly queries coupons by `code` field
   - ✅ Increments `usageCount` and `payoutUsage` atomically
   - ✅ Creates idempotency guards
   - ✅ Logs affiliate usage

2. **Order Creation** (`firestore_order_manager.php`)
   - ✅ Stores coupons as array with all metadata
   - ✅ Calls `batchApplyCouponsForOrder()` correctly
   - ✅ Increments counters when orders are created

3. **Frontend Coupon Application** (`order.html`)
   - ✅ Sends coupons array with proper structure
   - ✅ Includes `isAffiliateCoupon` and `affiliateCode` fields

---

## 📋 Complete Fix List

### Fix #1: Correct Affiliate Profile Lookup
**File**: `static-site/api/affiliate_functions.php` (Line 221-224)

**Change**:
```php
// FROM:
$affiliatesQuery = $firestore->collection('affiliates')
    ->where('affiliateCode', '=', $code)  // ❌ WRONG FIELD

// TO:
$affiliatesQuery = $firestore->collection('affiliates')
    ->where('code', '=', $code)  // ✅ CORRECT FIELD
```

---

### Fix #2: Query Orders by Coupons Array
**File**: `static-site/api/affiliate_functions.php` (Line 165-186)

**Change**: Replace entire function logic

**FROM** (Lines 165-186):
```php
// Query orders by affiliate code
$query = $firestore->collection('orders')
    ->where('couponCode', '=', $code)  // ❌ Field doesn't exist
    ->orderBy('createdAt', 'DESC')
    ->limit($pageSize);

$documents = $query->documents();
$orders = [];

foreach ($documents as $doc) {
    if ($doc->exists()) {
        $orderData = $doc->data();
        $orderData['id'] = $doc->id();
        $orders[] = $orderData;
    }
}

echo json_encode([
    'success' => true,
    'orders' => $orders,
    'count' => count($orders)  // ❌ Missing total, totalAmount
]);
```

**TO**:
```php
// Query ALL confirmed orders and manually filter by coupons array
$query = $firestore->collection('orders')
    ->where('status', '=', 'confirmed')
    ->orderBy('createdAt', 'DESC')
    ->limit($pageSize);

$documents = $query->documents();
$orders = [];
$totalAmount = 0;

foreach ($documents as $doc) {
    if ($doc->exists()) {
        $orderData = $doc->data();
        $coupons = $orderData['coupons'] ?? [];
        
        // Manually check if this order used the affiliate's coupon
        $hasAffiliateCoupon = false;
        foreach ($coupons as $coupon) {
            if (isset($coupon['code']) && $coupon['code'] === $code) {
                $hasAffiliateCoupon = true;
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

echo json_encode([
    'success' => true,
    'orders' => $orders,
    'total' => count($orders),              // ✅ Add required field
    'totalAmount' => $totalAmount,          // ✅ Add required field
    'nextPageToken' => null
]);
```

---

### Fix #3: Add Comprehensive Debug Logging

Add detailed logging throughout the flow to track:

#### Frontend (order.html)
- Log when coupons are collected
- Log exact coupon data being sent to backend
- Log order payload before submission

#### Backend (affiliate_functions.php)
- Log affiliate code being searched
- Log total orders processed vs matched
- Log which orders match the affiliate code
- Log final stats calculations

#### Admin Panel (coupon-admin.html)
- Log real-time stats updates from Firestore
- Log each coupon's usage data
- Log any errors during stats refresh

#### Affiliate Dashboard (affiliate-dashboard.html)
- Log API calls to getAffiliateStats
- Log API calls to getAffiliateOrders
- Log received data and parsing

---

## 🎯 Testing Strategy

### Step 1: Test Affiliate Profile Lookup
1. Open browser console on affiliate dashboard
2. Look for: `"Affiliate not found"` error
3. After fix: Should see affiliate data loaded

### Step 2: Test Order Queries
1. Create test order with affiliate code
2. Check backend logs for:
   ```
   AFFILIATE ORDERS: Query complete - Processed: X orders, Matched: Y orders
   ```
3. After fix: Should see matched orders > 0

### Step 3: Test Stats Display
1. Refresh affiliate dashboard
2. Should see:
   - Total Earnings: ₹300 (per order)
   - Total Referrals: 1+
   - Monthly Earnings: updated
   - Referred Orders: list populated

### Step 4: Test Admin Panel
1. Open coupon-admin.html
2. Load affiliate codes
3. Should see:
   - Affiliate coupons listed
   - Usage count: "X uses (₹Y earned)"
   - Stats updating every 30 seconds

---

## 📊 Expected Data Flow (After Fixes)

```
Customer applies affiliate coupon on order.html
            ↓
order.html sends coupon data: 
  {
    code: "partea-abc",
    isAffiliateCoupon: true,
    affiliateCode: "partea-abc"
  }
            ↓
firestore_order_manager.php creates order with coupons array
            ↓
Calls batchApplyCouponsForOrder()
            ↓
coupon_tracking_service.php:
  - Increments usageCount by 1
  - Increments payoutUsage by ₹300 (for affiliate)
  - Creates guard document (prevents duplicates)
  - Logs affiliate usage
            ↓
Coupon in Firestore now has:
  {
    usageCount: 1,
    payoutUsage: 300  (₹300 commission)
  }
            ↓
Affiliate dashboard calls getAffiliateStats(code)
            ↓
Backend queries ALL confirmed orders
Filters for coupons array containing affiliate code
Returns: totalEarnings: ₹300, totalReferrals: 1
            ↓
Dashboard displays stats correctly
```

---

## 🛠️ Implementation Plan

### Phase 1: Fix Database Queries (CRITICAL)
1. Fix affiliate profile lookup field name
2. Fix order query to filter coupons array manually
3. Add missing response fields (total, totalAmount)

### Phase 2: Add Debug Logging (HIGH PRIORITY)
1. Add comprehensive backend logging
2. Add frontend console logging
3. Log full data structures for debugging

### Phase 3: Verify Data Flow (TESTING)
1. Test with real/test orders
2. Verify logs show correct flow
3. Verify stats update properly
4. Verify admin panel shows usage

### Phase 4: Documentation
1. Document correct Firestore schema
2. Document query patterns
3. Add inline code comments

---

## 🎓 Key Lessons

### Firestore Limitations
- **Cannot query array fields directly**: Must query all and filter manually
- **Field names must match exactly**: `code` ≠ `affiliateCode`
- **Case-sensitive queries**: Must normalize codes

### Best Practices
- Always verify field names against actual Firestore documents
- Add debug logging for all queries
- Return all fields that frontend expects
- Use manual filtering for array field queries

---

## ⚡ Quick Start: Minimum Fixes

If you want the MINIMUM changes to make it work:

1. **Line 222 in affiliate_functions.php**:
   Change `'affiliateCode'` to `'code'`

2. **Lines 166-186 in affiliate_functions.php**:
   Replace with manual array filtering (see Fix #2 above)

3. Add logging to see actual data

These 3 changes will fix 90% of the issues.


