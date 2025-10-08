# ✅ Coupon Tracking System - Testing Checklist

## Pre-Testing Setup

- [ ] Firestore SDK installed and working
- [ ] Service account JSON file in place
- [ ] At least one test coupon created in Firestore
- [ ] Test coupon has fields: `code`, `name`, `type`, `value`
- [ ] PHP 7.4+ available
- [ ] Command line access OR web browser access

---

## Test Suite 1: Basic Functionality

### 1.1 Code Normalization ✅

**Test**: Verify coupon codes are normalized consistently

```bash
php test_coupon_increment.php " save20 "
php test_coupon_increment.php "SAVE20"
php test_coupon_increment.php "save20"
```

**Expected**: All three should find the same coupon and increment it

**Pass Criteria**:
- [ ] All three inputs find the same coupon
- [ ] Normalized to uppercase
- [ ] Leading/trailing whitespace removed

---

### 1.2 Atomic Increment ✅

**Test**: Verify FieldValue::increment() works

```bash
php test_coupon_increment.php YOUR_COUPON_CODE
```

**Expected Output**:
```
✅ Coupon found!
✅ Increment successful!
✅ Increment verified correctly!
```

**Pass Criteria**:
- [ ] Coupon found by code
- [ ] usageCount incremented by exactly 1
- [ ] payoutUsage incremented by exactly 1
- [ ] No errors in logs

**Verify in Firestore**:
- [ ] Navigate to coupon document
- [ ] Check `usageCount` increased
- [ ] Check `updatedAt` timestamp updated

---

### 1.3 Missing Field Handling ✅

**Test**: Verify increment works even if fields don't exist

**Setup**: Create a coupon without `usageCount` or `payoutUsage`:
```javascript
// In Firestore Console
{
  code: "TEST_NEW",
  name: "Test Coupon",
  type: "percentage",
  value: 10
  // NO usageCount or payoutUsage
}
```

**Run**:
```bash
php test_coupon_increment.php TEST_NEW
```

**Pass Criteria**:
- [ ] No errors
- [ ] `usageCount` created and set to 1
- [ ] `payoutUsage` created and set to 1

---

## Test Suite 2: Idempotency

### 2.1 Basic Idempotency ✅

**Test**: Verify duplicate applications don't double-increment

```bash
php test_idempotency.php YOUR_COUPON_CODE test_order_abc pay_xyz123
```

**Expected Output**:
```
✅ First application: Coupon applied successfully
↩️ Second application: Coupon already applied (idempotent)
↩️ Third application: Coupon already applied (idempotent)
✅ IDEMPOTENCY TEST PASSED!
```

**Pass Criteria**:
- [ ] First application succeeds
- [ ] Second application returns idempotent=true
- [ ] Third application returns idempotent=true
- [ ] Total increment = 1 (not 3)
- [ ] Guard document created in `orders/{orderId}/couponIncrements/`

**Verify in Firestore**:
- [ ] Check `usageCount` only increased by 1
- [ ] Guard document exists with correct `paymentId`
- [ ] Guard document has `createdAt` timestamp

---

### 2.2 Different Payment IDs ⚠️

**Test**: Verify different payments can use same coupon on different orders

```bash
# First order
php test_idempotency.php SAVE20 order_001 payment_001

# Second order (different payment)
php test_idempotency.php SAVE20 order_002 payment_002
```

**Pass Criteria**:
- [ ] First order: coupon applied successfully
- [ ] Second order: coupon applied successfully (NOT idempotent)
- [ ] Total increment = 2 (one per order)
- [ ] Two different guard documents created

---

### 2.3 Same Payment, Different Coupons ✅

**Test**: Multiple coupons can be used on one order

```bash
# Create test script or use batchApplyCouponsForOrder
```

**Pass Criteria**:
- [ ] All coupons applied successfully
- [ ] Each coupon has separate guard document
- [ ] Each coupon incremented independently

---

## Test Suite 3: Affiliate Tracking

### 3.1 Affiliate Commission Tracking ✅

**Test**: Verify affiliate coupons track commission amounts

```bash
php test_affiliate_payout.php YOUR_AFFILIATE_CODE 999.00
```

**Expected Output**:
```
✅ Coupon applied: Coupon applied successfully
✅ Affiliate usage logged
✅ AFFILIATE PAYOUT TEST PASSED!
   ✓ usageCount incremented by 1
   ✓ payoutUsage incremented by commission amount
   ✓ Affiliate usage logged correctly
```

**Pass Criteria**:
- [ ] usageCount increased by 1
- [ ] payoutUsage increased by commission (e.g., 99.90 for 999.00 order)
- [ ] `isAffiliateCoupon` set to true
- [ ] `affiliateCode` field populated
- [ ] Affiliate usage log created in `orders/{orderId}/affiliate_usage/`

**Verify in Firestore**:
- [ ] Coupon document has correct `payoutUsage`
- [ ] Affiliate usage log has correct commission amount
- [ ] Affiliate usage log has customer email

---

### 3.2 Different Order Amounts 💰

**Test**: Commission scales with order amount

```bash
php test_affiliate_payout.php AFFILIATE_CODE 500.00   # ₹50 commission
php test_affiliate_payout.php AFFILIATE_CODE 1000.00  # ₹100 commission
php test_affiliate_payout.php AFFILIATE_CODE 2500.00  # ₹250 commission
```

**Pass Criteria**:
- [ ] Each order increments usageCount by 1
- [ ] payoutUsage increases by correct commission (10%)
- [ ] All affiliate logs created with correct amounts

---

### 3.3 Non-Affiliate Coupons 📊

**Test**: Regular coupons still work (increment by 1, not commission)

```bash
# Use regular coupon (not affiliate)
php test_coupon_increment.php REGULAR_COUPON
```

**Pass Criteria**:
- [ ] usageCount incremented by 1
- [ ] payoutUsage incremented by 1 (not commission amount)
- [ ] No affiliate usage log created

---

## Test Suite 4: Batch Operations

### 4.1 Multiple Coupons on One Order 🎫

**Test**: Apply 2+ coupons to single order

**Create test file** `test_batch.php`:
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/coupon_tracking_service.php';

$factory = (new \Kreait\Firebase\Factory())
    ->withServiceAccount(__DIR__ . '/firebase-service-account.json');
$firestore = $factory->createFirestore();

$coupons = [
    ['code' => 'SAVE20', 'isAffiliateCoupon' => false],
    ['code' => 'FREESHIP', 'isAffiliateCoupon' => false]
];

$result = batchApplyCouponsForOrder($firestore, $coupons, 'test_batch_order', [
    'amount' => 999.00,
    'customerEmail' => 'test@example.com'
], 'pay_batch_test');

print_r($result);
?>
```

**Run**:
```bash
php test_batch.php
```

**Pass Criteria**:
- [ ] `successCount` = 2
- [ ] `totalCount` = 2
- [ ] Both coupons incremented
- [ ] Two guard documents created
- [ ] No errors

---

### 4.2 Mixed Affiliate and Regular 🎭

**Test**: Batch with both affiliate and regular coupons

```php
$coupons = [
    ['code' => 'SAVE20', 'isAffiliateCoupon' => false],
    ['code' => 'JOHN-REF', 'isAffiliateCoupon' => true, 'affiliateCode' => 'john-123']
];
```

**Pass Criteria**:
- [ ] Regular coupon: payoutUsage +1
- [ ] Affiliate coupon: payoutUsage +commission
- [ ] Affiliate usage log only for affiliate coupon

---

### 4.3 Invalid Coupon in Batch ❌

**Test**: Batch continues even if one coupon fails

```php
$coupons = [
    ['code' => 'SAVE20', 'isAffiliateCoupon' => false],        // Valid
    ['code' => 'INVALID_CODE', 'isAffiliateCoupon' => false],  // Invalid
    ['code' => 'FREESHIP', 'isAffiliateCoupon' => false]       // Valid
];
```

**Pass Criteria**:
- [ ] `successCount` = 2
- [ ] `totalCount` = 3
- [ ] Valid coupons applied
- [ ] Invalid coupon in results with error message
- [ ] No exceptions thrown

---

## Test Suite 5: Integration Testing

### 5.1 Full Order Flow 🛒

**Test**: Complete order with coupon (via order manager)

**Create test order**:
```bash
curl -X POST http://localhost/api/firestore_order_manager.php/create \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "order_test_123",
    "payment_id": "pay_test_123",
    "customer": {"firstName": "Test", "lastName": "User", "email": "test@example.com", "phone": "1234567890"},
    "product": {"name": "Test Product", "price": 999},
    "pricing": {"subtotal": 999, "shipping": 0, "discount": 100, "total": 899, "currency": "INR"},
    "shipping": {"address": "Test Address", "city": "Test City", "state": "TS", "pincode": "123456", "country": "India"},
    "payment": {"method": "razorpay", "transaction_id": "pay_test_123"},
    "coupons": [{"code": "SAVE20", "name": "Save 20%", "type": "percentage", "value": 20, "isAffiliateCoupon": false}]
  }'
```

**Pass Criteria**:
- [ ] Order created successfully
- [ ] Coupon incremented
- [ ] Guard document created
- [ ] Response includes coupon results
- [ ] No errors in logs

---

### 5.2 Webhook Retry Scenario 🔁

**Test**: Same order submitted twice (simulates webhook retry)

**Run**: Submit same order payload twice

**Pass Criteria**:
- [ ] First submission: order created, coupon incremented
- [ ] Second submission: idempotent response, coupon NOT double-incremented
- [ ] Only one guard document exists
- [ ] Response indicates idempotent success

---

## Test Suite 6: Edge Cases

### 6.1 Empty Coupon Array 📭

**Test**: Order with empty coupons array

```json
{
  "coupons": []
}
```

**Pass Criteria**:
- [ ] Order succeeds
- [ ] No coupon processing
- [ ] No errors

---

### 6.2 Null/Missing Coupon Fields 🚫

**Test**: Coupon with missing code

```json
{
  "coupons": [
    {"name": "Test", "type": "percentage", "value": 10}  // No code!
  ]
}
```

**Pass Criteria**:
- [ ] Order succeeds
- [ ] Coupon skipped gracefully
- [ ] No crashes

---

### 6.3 Very Large Commission 💰💰💰

**Test**: High-value order with large commission

```bash
php test_affiliate_payout.php AFFILIATE_CODE 99999.00
```

**Pass Criteria**:
- [ ] Commission calculated correctly (₹9,999.90)
- [ ] payoutUsage increments by exact amount
- [ ] No overflow or precision errors

---

## Performance Testing

### P.1 Concurrent Orders 🏃‍♂️🏃‍♀️

**Test**: Multiple orders using same coupon simultaneously

**Method**: Use Apache Bench or similar tool

```bash
# Simulate 10 concurrent requests
ab -n 10 -c 10 -p order_payload.json -T application/json http://localhost/api/firestore_order_manager.php/create
```

**Pass Criteria**:
- [ ] All orders succeed
- [ ] Coupon usage count matches number of orders
- [ ] No race conditions
- [ ] Atomic increments work correctly

---

### P.2 Large Batch 📦

**Test**: Order with many coupons (5+)

**Pass Criteria**:
- [ ] All coupons processed
- [ ] Performance acceptable (<2 seconds)
- [ ] No memory issues

---

## Final Production Tests

### PROD.1 Real Coupon Test 🎟️

- [ ] Create real coupon in production Firestore
- [ ] Place actual test order using the coupon
- [ ] Verify coupon incremented correctly
- [ ] Check all guard documents created
- [ ] Verify affiliate tracking (if applicable)

### PROD.2 Monitoring 📊

- [ ] Set up log monitoring
- [ ] Watch for "COUPON SERVICE" log entries
- [ ] Monitor Firestore write operations
- [ ] Set up alerts for errors

### PROD.3 Rollback Plan 🔙

- [ ] Document rollback procedure
- [ ] Keep backup of old firestore_order_manager.php
- [ ] Test rollback in staging environment

---

## Sign-Off

### Developer Testing
- [ ] All Test Suite 1 tests passed
- [ ] All Test Suite 2 tests passed
- [ ] All Test Suite 3 tests passed
- [ ] All Test Suite 4 tests passed
- [ ] Edge cases handled
- [ ] Code reviewed

**Tested by**: ________________  
**Date**: ________________  

### QA Testing
- [ ] Integration tests passed
- [ ] Performance acceptable
- [ ] No critical bugs found
- [ ] Documentation complete

**Tested by**: ________________  
**Date**: ________________  

### Production Deployment
- [ ] All tests passed in staging
- [ ] Monitoring in place
- [ ] Rollback plan ready
- [ ] Deployed to production
- [ ] Post-deployment verification complete

**Deployed by**: ________________  
**Date**: ________________  

---

## Issues Log

| Date | Issue | Severity | Status | Resolution |
|------|-------|----------|--------|------------|
|      |       |          |        |            |
|      |       |          |        |            |

---

**Testing Complete** ✅ 

Ready for production deployment! 🚀

