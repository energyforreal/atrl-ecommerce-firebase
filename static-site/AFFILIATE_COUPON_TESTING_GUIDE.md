# 🧪 Affiliate Coupon Testing Guide

## Overview
This guide explains how to use the enhanced affiliate coupon usage tester and troubleshoot common issues.

## 🎯 What Was Fixed

### 1. **Duplicate Order Errors** ✅
**Problem:** "Order already exists for this payment" error when running multiple tests
**Solution:** 
- Improved unique ID generation with microsecond precision
- Added iteration counter to ensure uniqueness
- Increased delay between requests (500ms → 800ms)
- Auto-generate random order prefix on page load
- Added "🎲 Random" button to manually generate new prefixes

### 2. **NetworkError on Debug Endpoint** ✅
**Problem:** `debug_coupon_increment.php` endpoint throwing network errors
**Solution:**
- Added graceful error handling (the endpoint is now optional)
- Clear warning messages explaining it's for advanced debugging only
- Won't block the main testing functionality

### 3. **Better Error Reporting** ✅
**Problem:** Unclear error messages and no guidance for users
**Solution:**
- Added detailed error logging with color-coded messages
- Special handling for duplicate order errors with helpful tips
- Success/failure summary alerts after each test run
- Added troubleshooting tips section in the UI

## 🚀 How to Use

### Step 1: Access the Tester
1. Open `test-affiliate-coupon-usage.html` in your browser
2. Wait for Firebase connection (status will turn green)
3. A unique order prefix is automatically generated on page load

### Step 2: Select a Coupon
1. **Option A:** Choose from the dropdown (loads affiliate coupons automatically)
2. **Option B:** Manually enter a coupon code

### Step 3: Configure Test
- **Order Total:** Amount to test with (default: ₹1499)
- **Order Number Prefix:** Auto-generated unique prefix (or click 🎲 Random for new one)
- **Test Mode:** 
  - Single Test (1x)
  - Batch Test (5x)
  - Stress Test (10x)

### Step 4: Run Test
1. Click **"➕ Simulate Usage"** to create test orders
2. Watch the debug log for real-time progress
3. Check the "Current Status" section for updated counters
4. Review the summary alert when complete

## 🎨 Available Actions

| Button | Purpose |
|--------|---------|
| 🔍 Load Coupon | Fetch current usage data from Firestore |
| ➕ Simulate Usage | Create test orders to increment counters |
| 🔄 Refresh Counters | Reload usage data from Firestore |
| ♻️ Reset Cycle | Reset `payoutUsage` counter (for payout cycles) |
| 🗑️ Clear Log | Clear the debug log panel |
| 🔧 Debug Coupon | Test coupon increment directly (optional) |
| 🔎 Inspect API | Check API endpoint headers and response |
| 🎲 Random | Generate new unique order prefix |
| 🧹 Cleanup Test Orders | **NEW!** Delete all old test orders from Firestore |

## 💡 Troubleshooting

### ❌ "Order already exists for this payment"
**Cause:** Old test orders with the same payment IDs exist in Firestore from previous test runs

**Solutions (in order of preference):**
1. **🧹 Click "Cleanup Test Orders"** - This deletes all old test orders from Firestore (RECOMMENDED)
2. Click the **🎲 Random** button to generate a new order prefix
3. Refresh the page (auto-generates new prefix on load)

**Why this happens:** The backend checks if a payment ID already exists to prevent duplicate orders. Even with unique IDs, old test data can accumulate and block new tests.

### ❌ "NetworkError when attempting to fetch resource" (on debug endpoint)
**Cause:** The `debug_coupon_increment.php` endpoint is not accessible or has CORS issues

**Solution:**
- **This is normal and expected** - the debug endpoint is optional
- The main simulation will still work perfectly
- Only used for advanced low-level debugging

### ❌ Test succeeds but counters don't update
**Cause:** May need to refresh or there's a caching issue

**Solutions:**
1. Click **🔄 Refresh Counters** button
2. Click **🔍 Load Coupon** to reload from Firestore
3. Check browser console for any errors

### ⚠️ Batch tests partially fail
**Cause:** Rate limiting or temporary server issues

**Solutions:**
1. Use smaller batch sizes (Single Test instead of Batch/Stress)
2. Increase delay between requests (already set to 800ms)
3. Check the debug log for specific error messages

## 📊 Understanding the Counters

### Total Usage (`usageCount`)
- **What it tracks:** Total number of times the coupon has been used (lifetime)
- **When it increments:** Every time an order with this coupon is created
- **Cannot be reset:** This is a permanent counter

### Cycle Usage (`payoutUsage`)
- **What it tracks:** Usage within the current payout cycle
- **When it increments:** Every time an order with this coupon is created
- **Can be reset:** Use the "♻️ Reset Cycle" button to start a new payout period

### Last Updated
- Shows the timestamp of the last modification to the coupon document

## 🔧 Advanced Features

### Cleanup Test Orders (NEW!)
The cleanup feature helps you maintain a clean test environment by removing old test data.

**What it does:**
- Scans the last 500 orders in Firestore
- Identifies test orders by:
  - Customer email: `tester@attral.in`
  - Order notes: `affiliate-usage-tester`
  - Order ID pattern: Contains `ATRL-TEST`
- Safely deletes only test orders, preserving real customer orders
- Shows summary of processed/deleted/kept orders

**When to use:**
- Before starting a new test session
- When you see "Order already exists" errors repeatedly
- Periodically to keep Firestore clean

**Safety:**
- Only deletes orders matching test criteria
- Shows confirmation dialog before proceeding
- Displays detailed log of what's being deleted
- Does NOT affect real customer orders

### Batch Testing
Use batch mode to simulate multiple orders at once:
- **Batch Test (5x):** Creates 5 test orders with 800ms delay between each
- **Stress Test (10x):** Creates 10 test orders for load testing

### Debug Logging
The debug panel shows:
- ✅ Success messages (green)
- ⚠️ Warnings (orange)
- ❌ Errors (red)
- ℹ️ Info messages (blue)

Each entry includes:
- Timestamp
- Descriptive message
- Full request/response data (expandable JSON)

### API Source Tracking
The tester automatically logs the `api_source` header from responses to help identify which backend is processing requests.

## 🎯 Best Practices

1. **Run cleanup before testing** - Click "🧹 Cleanup Test Orders" to remove old test data
2. **Generate unique order prefixes** - The system does this automatically on page load
3. **Use Single Test mode first** - Verify everything works before running batch tests
4. **Monitor the debug log** - Watch for errors and warnings in real-time
5. **Refresh counters after tests** - Verify that usage counts updated correctly
6. **Reset cycle usage monthly** - Use "♻️ Reset Cycle" at the start of each payout period
7. **Clean up regularly** - Don't let test orders accumulate in Firestore

## 🔗 Related Pages

- **Coupon Admin:** `/coupon-admin.html` - Create and manage affiliate coupons
- **Affiliate Dashboard:** `/affiliate-dashboard.html` - View affiliate statistics
- **Admin Dashboard:** `/admin-dashboard.html` - Overall system management

## 📝 Technical Details

### Unique ID Generation
```javascript
const timestamp = Date.now();
const randomPart = Math.random().toString(36).substr(2, 9);
const iterationPart = i.toString().padStart(3, '0');
const uniqueId = `${timestamp}_${iterationPart}_${randomPart}`;
```

This generates IDs like: `1759824840202_000_jg4fafcta`
- Timestamp ensures chronological ordering
- Iteration counter prevents collisions in batch tests
- Random suffix adds extra uniqueness

### Request Payload Structure
```json
{
  "order_id": "rzp_order_<uniqueId>",
  "payment_id": "pay_<uniqueId>",
  "customer": { "email": "tester@attral.in" },
  "product": { "title": "Test Product" },
  "pricing": { "total": 1499 },
  "payment": { 
    "method": "razorpay",
    "url_params": { "ref": "affiliate_code" }
  },
  "coupons": [{
    "code": "COUPON_CODE",
    "type": "percentage",
    "value": 5,
    "isAffiliateCoupon": true,
    "affiliateCode": "affiliate_code"
  }]
}
```

## 🆘 Getting Help

If you encounter issues not covered in this guide:
1. Check the debug log for detailed error messages
2. Use the "🔎 Inspect API" button to verify endpoint connectivity
3. Verify the coupon exists in Firestore via `/coupon-admin.html`
4. Check browser console for JavaScript errors
5. Review server logs for backend issues

---

**Last Updated:** October 7, 2025  
**Version:** 2.1 (Added Cleanup Feature + Enhanced Error Handling & Unique ID Generation)

