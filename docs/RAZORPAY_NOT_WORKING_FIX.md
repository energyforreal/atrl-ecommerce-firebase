# 🚨 Razorpay Payment Not Working - Quick Fix Guide

## Problem: Razorpay is not initiating payment

This means the credentials aren't being loaded properly on the backend.

---

## ✅ INSTANT FIX (Takes 30 seconds!)

### Option 1: Automatic Fix (Easiest!)

1. **Visit this URL in your browser:**
   ```
   https://attral.in/api/fix_credentials.php
   ```

2. **Click the button** to create config.local.php automatically

3. **Verify it worked:**
   ```
   https://attral.in/api/check_credentials.php
   ```

4. **Test a payment** on your website

5. **Delete both files** after verification

**Done!** ✅

---

### Option 2: Manual Fix (30 seconds)

**Via SSH/Terminal:**
```bash
cd static-site/api/
cp config.local.php.template config.local.php
```

**Via FTP/File Manager:**
1. Open `static-site/api/` folder
2. Copy `config.local.php.template`
3. Rename the copy to `config.local.php`

**Done!** ✅

---

## 🔍 Diagnostic Steps

### Step 1: Check Backend Credentials

Visit: `https://attral.in/api/check_credentials.php`

This will show you:
- ✅ If credentials are loaded
- ✅ If they're the correct ones
- ✅ If Razorpay API connection works
- ❌ What's wrong if it's not working

### Step 2: Check Frontend Configuration

The frontend JavaScript already has the correct key:
```javascript
RAZORPAY_KEY_ID: 'rzp_live_RKD5kwFAOZ05UD'
```

This is in `static-site/js/config.js` - already correct! ✅

### Step 3: Test Payment Flow

1. Add product to cart
2. Go to checkout
3. Fill in details
4. Click "Pay with Razorpay"
5. Razorpay popup should appear

---

## 🐛 Common Issues & Fixes

### Issue 1: "config.local.php not found"

**Fix:**
```bash
cd static-site/api/
cp config.local.php.template config.local.php
```

### Issue 2: "Credentials are default values"

**Symptom:** Check shows `rzp_test_xxxxxxxxxxxx`

**Fix:** config.local.php wasn't created or has wrong content

**Solution:**
1. Delete config.local.php if it exists
2. Run: `cp config.local.php.template config.local.php`

### Issue 3: "401 Authentication Error from Razorpay"

**Symptom:** API test fails with HTTP 401

**Fix:** Credentials are wrong or expired

**Solution:**
1. Check Razorpay dashboard for correct keys
2. Update config.local.php with correct keys

### Issue 4: "File permissions error"

**Symptom:** Can't create config.local.php

**Fix:**
```bash
chmod 755 static-site/api/
cd static-site/api/
cp config.local.php.template config.local.php
chmod 644 config.local.php
```

### Issue 5: "Razorpay popup doesn't appear"

**Check Frontend:**
- Open browser console (F12)
- Look for errors
- Check if `window.ATTRAL_PUBLIC.RAZORPAY_KEY_ID` is set
- Should be: `rzp_live_RKD5kwFAOZ05UD`

**Check Backend:**
- Visit `check_credentials.php`
- Should show API connection successful

---

## 📋 Verification Checklist

After fixing, verify everything:

- [ ] Visit `check_credentials.php`
- [ ] See "✅ Everything is working perfectly!"
- [ ] See "API connection successful"
- [ ] See your Key ID: `rzp_live_RKD5kwFAOZ05UD`
- [ ] Test payment on website
- [ ] Razorpay popup appears
- [ ] Can complete test payment
- [ ] Delete `check_credentials.php`
- [ ] Delete `fix_credentials.php`

---

## 🎯 What's Happening Behind the Scenes

### Backend Flow:
1. `create_order.php` loads `config.php`
2. `config.php` checks for `config.local.php`
3. If found, loads credentials from there
4. Uses credentials to create Razorpay order
5. Returns order ID to frontend

### Frontend Flow:
1. `order.html` uses `js/config.js`
2. Gets Razorpay Key ID: `rzp_live_RKD5kwFAOZ05UD`
3. Calls `create_order.php` to create order
4. Opens Razorpay popup with Key ID
5. User completes payment

### Where It Can Fail:
- ❌ `config.local.php` doesn't exist → Backend has no credentials
- ❌ `config.local.php` has wrong values → API calls fail
- ❌ Frontend has wrong Key ID → Wrong account or test mode

---

## 🔧 Advanced Debugging

### Check Exact Backend Configuration:

Create `test_backend.php`:
```php
<?php
require_once 'config.php';
$cfg = include 'config.php';
header('Content-Type: application/json');
echo json_encode([
    'key_id_first_15_chars' => substr($cfg['RAZORPAY_KEY_ID'], 0, 15),
    'secret_first_10_chars' => substr($cfg['RAZORPAY_KEY_SECRET'], 0, 10),
    'webhook_set' => !empty($cfg['RAZORPAY_WEBHOOK_SECRET']),
    'is_live_key' => strpos($cfg['RAZORPAY_KEY_ID'], 'rzp_live_') === 0,
]);
?>
```

Visit: `https://attral.in/api/test_backend.php`

Should show:
```json
{
  "key_id_first_15_chars": "rzp_live_RKD5kw",
  "secret_first_10_chars": "msl2Tx9q0D",
  "webhook_set": true,
  "is_live_key": true
}
```

**Delete test file after checking!**

### Check Frontend Console:

Open browser console (F12) and run:
```javascript
console.log(window.ATTRAL_PUBLIC.RAZORPAY_KEY_ID);
```

Should output: `rzp_live_RKD5kwFAOZ05UD`

### Check Create Order API:

```bash
curl -X POST https://attral.in/api/create_order.php \
  -H "Content-Type: application/json" \
  -d '{"amount":100,"currency":"INR","receipt":"test"}'
```

Should return a Razorpay order object with `id` field.

---

## ✅ Quick Summary

**Most likely issue:** `config.local.php` doesn't exist

**Quickest fix:**
1. Visit `https://attral.in/api/fix_credentials.php`
2. Click the button
3. Done!

**Verification:**
1. Visit `https://attral.in/api/check_credentials.php`
2. Should see all ✅ green checkmarks
3. Test a payment

**Cleanup:**
- Delete `fix_credentials.php`
- Delete `check_credentials.php`
- Delete any test files you created

---

## 🆘 Still Not Working?

If after all this it's still not working:

1. **Check Razorpay Dashboard:**
   - Are the keys correct?
   - Is the account active?
   - Are there any restrictions?

2. **Check Server Logs:**
   ```bash
   tail -f /path/to/error_log
   ```
   Look for Razorpay-related errors

3. **Check Browser Console:**
   - F12 → Console tab
   - Look for JavaScript errors
   - Check Network tab for failed requests

4. **Contact Razorpay Support:**
   - They can verify if API calls are reaching them
   - They can check if keys are active

---

**Most Common Solution:** Just run this command and you're done!

```bash
cd static-site/api/
cp config.local.php.template config.local.php
```

Then test! ✅

