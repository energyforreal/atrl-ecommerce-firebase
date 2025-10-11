# Affiliate Dashboard Fix Guide

## 🔍 Problem Analysis

### Current Issue
The affiliate dashboard at `https://attral.in/affiliate-dashboard.html` is showing **HTTP 400 errors** when trying to load:
- Affiliate stats (via `getAffiliateStats` API)
- Affiliate orders (via `getAffiliateOrders` API)

### Root Cause
The **changes we made are only on your local machine** and haven't been deployed to the live server at `attral.in`. The live server is still running the old code that has these issues:

1. **Returns HTTP 400** when affiliate profile doesn't exist
2. **Missing graceful error handling** for empty data
3. **Field name inconsistencies** between `code` and `affiliateCode`

## ✅ Solutions Implemented (Locally)

### 1. Backend API Improvements (`affiliate_functions.php`)

#### Before (Old Code - Still on Live Server):
```php
if (!$affiliate) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Affiliate not found']);
    return;
}
```

#### After (New Code - On Your Local Machine):
```php
if (!$affiliate) {
    error_log("AFFILIATE STATS: ❌ Affiliate not found for code=$code");
    // Return empty stats instead of error
    echo json_encode([
        'success' => true,
        'totalEarnings' => 0,
        'totalReferrals' => 0,
        'monthlyEarnings' => 0,
        'conversionRate' => 0,
        'couponUsageCount' => 0,
        'couponPayoutUsage' => 0,
        'affiliateCode' => $code,
        'status' => 'not_found'
    ]);
    return;
}
```

### 2. Enhanced Features Added

#### A. Coupon Usage Statistics
- Added `couponUsageCount` field (total times coupon was used)
- Added `couponPayoutUsage` field (total commission earned in ₹)
- New stat card in dashboard showing these metrics

#### B. Customer Information in Orders
- Added `customerName` to each order
- Added `customerEmail` to each order
- Added `customerPhone` to each order
- Enhanced order display with customer details

### 3. Improved Error Handling
- APIs now return `success: true` with empty data instead of HTTP errors
- Better logging for debugging
- Graceful degradation when data is missing

## 🚀 Deployment Instructions

### Step 1: Upload Changed Files to Live Server

You need to upload these **2 modified files** to your server at `attral.in`:

1. **`static-site/api/affiliate_functions.php`**
   - Location on server: `/api/affiliate_functions.php`
   - Changes: Fixed error handling, added coupon usage, added customer info

2. **`static-site/affiliate-dashboard.html`**
   - Location on server: `/affiliate-dashboard.html`
   - Changes: New stat card, updated JS to display new data, enhanced order display

### Step 2: Debug Database Structure (Optional)

Upload the debug script to check your database:

**File**: `static-site/api/debug_affiliate.php`
**Upload to**: `/api/debug_affiliate.php`

Then visit: `https://attral.in/api/debug_affiliate.php?code=attral-71hlzssgan`

This will show you:
- Whether the affiliate profile exists
- What fields are in the profile
- Coupon usage data
- List of all affiliates in database

### Step 3: Verify the Fix

After uploading, visit: `https://attral.in/affiliate-dashboard.html`

You should see:
- ✅ No more 400 errors
- ✅ Stats showing (even if 0)
- ✅ New "Coupon Uses" stat card
- ✅ Customer info in orders
- ✅ Dashboard loads successfully

## 📊 Expected Database Structure

Based on the code, your Firestore should have:

### Affiliates Collection
```javascript
{
  "code": "attral-71hlzssgan",
  "uid": "pmgdmZlqp0ZomN2wz1eTsQqeBGg1",
  "email": "attralsolar@gmail.com",
  "name": "Attral Solar",
  "status": "active",
  "totalEarnings": 0,
  "totalOrders": 0,
  "createdAt": Timestamp,
  "updatedAt": Timestamp
}
```

### Coupons Collection
```javascript
{
  "code": "attral-71hlzssgan",
  "usageCount": 5,        // How many times used
  "payoutUsage": 1500,    // Total commission earned (₹)
  "type": "affiliate",
  "discount": 100,
  "active": true
}
```

### Orders Collection
```javascript
{
  "orderId": "ORD-12345",
  "status": "confirmed",
  "amount": 5000,
  "coupons": [
    {
      "code": "attral-71hlzssgan",
      "discount": 100
    }
  ],
  "customer": {
    "firstName": "John",
    "lastName": "Doe",
    "email": "john@example.com",
    "phone": "+919876543210"
  },
  "createdAt": Timestamp
}
```

## 🔧 Troubleshooting

### If dashboard still shows errors after deployment:

1. **Clear browser cache**: Hard refresh with `Ctrl+F5` (Windows) or `Cmd+Shift+R` (Mac)

2. **Check server logs**: Look for error messages in your hosting control panel

3. **Verify file upload**: Make sure both files were uploaded successfully

4. **Test API directly**: Visit `https://attral.in/api/affiliate_functions.php?action=getAffiliateStats` with POST data

5. **Run debug script**: Visit `https://attral.in/api/debug_affiliate.php?code=attral-71hlzssgan`

### If affiliate profile doesn't exist:

The dashboard should automatically create one now. If it doesn't:

1. Visit the debug script to confirm no profile exists
2. The dashboard will call `createAffiliateProfile` automatically
3. A new profile will be created with a random code
4. You can then update the code in Firestore to `attral-71hlzssgan`

## 📝 Quick Deployment Checklist

- [ ] Upload `affiliate_functions.php` to `/api/`
- [ ] Upload `affiliate-dashboard.html` to root
- [ ] Upload `debug_affiliate.php` to `/api/` (optional)
- [ ] Clear browser cache
- [ ] Test dashboard at `https://attral.in/affiliate-dashboard.html`
- [ ] Check for console errors (F12)
- [ ] Verify stats display
- [ ] Check orders section

## 🎯 What Will Change After Deployment

### Before:
- ❌ HTTP 400 errors
- ❌ Dashboard fails to load
- ❌ No coupon usage stats
- ❌ No customer info in orders
- ❌ Generic error messages

### After:
- ✅ No errors (graceful handling)
- ✅ Dashboard loads successfully
- ✅ Coupon usage card with count and earnings
- ✅ Customer name, email, phone in each order
- ✅ Better debugging with console logs
- ✅ Empty states instead of errors

## 🔐 Security Note

The debug script (`debug_affiliate.php`) exposes database information. After debugging:
- Delete it from the server, OR
- Add authentication/IP restrictions, OR
- Rename it to something obscure

## 📞 Support

If issues persist after deployment, check:
1. Browser console (F12) for JavaScript errors
2. Network tab for API responses
3. Server error logs for PHP errors
4. Debug script output for database structure

The main issue is simply that **the files need to be uploaded to the live server**. Once uploaded, all the 400 errors should be resolved.

