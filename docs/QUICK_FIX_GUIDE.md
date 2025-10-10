# 🚨 QUICK FIX: Upload Config File to Server

## The Problem
You're seeing this error:
```
❌ Order Creation Response: { status: 401, order: {…}, responseOk: false }
❌ Order creation failed: Authentication failed
```

**Why?** The `config.php` file with your Razorpay credentials is only on your LOCAL computer. It needs to be on your PRODUCTION server at `https://attral.in`.

---

## ⚡ FASTEST FIX (2 Options)

### **Option 1: Upload config.php (RECOMMENDED - More Secure)**

1. **Find the file on your computer:**
   ```
   C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\api\config.php
   ```

2. **Upload to your server at:**
   ```
   /public_html/static-site/api/config.php
   ```
   (Or wherever your site root is)

3. **Upload methods:**
   - 📁 FileZilla / WinSCP
   - 🌐 Hostinger File Manager
   - 📂 cPanel File Manager

4. **Test immediately:**
   ```
   https://attral.in/api/test_config.php
   ```
   
   Should show: ✅ SUCCESS

---

### **Option 2: Use Hardcoded Version (FASTER - Less Secure)**

If you can't upload config.php right now, use the hardcoded version:

1. **Upload this file to your server:**
   ```
   create_order_WITH_HARDCODED_CREDENTIALS.php
   ```
   
   **Upload as (rename it to):**
   ```
   create_order.php
   ```
   
   **Location:**
   ```
   /public_html/static-site/api/create_order.php
   ```

2. **This will IMMEDIATELY work** because credentials are hardcoded in the file

3. **⚠️ IMPORTANT:** This is less secure - replace with Option 1 after testing!

---

## 🧪 How to Test

After uploading, open your browser and try:

1. **Test config:**
   ```
   https://attral.in/api/test_config.php
   ```
   
   **Should show:**
   ```json
   {
     "status": "✅ SUCCESS - Razorpay credentials are valid!",
     "has_razorpay_key_id": true,
     "has_razorpay_key_secret": true
   }
   ```

2. **Test payment flow:**
   - Go to: `http://localhost:8000/order.html?type=cart`
   - Fill form and click "Pay with Razorpay"
   
   **Expected console output:**
   ```
   ✅ 🔧 Order Creation Response: { status: 200, responseOk: true }
   ✅ Razorpay modal opens!
   ```

---

## 📋 File Upload Checklist

**Before Upload:**
- [ ] File exists locally at: `static-site\api\config.php`
- [ ] File size is ~500-600 bytes
- [ ] File contains your Razorpay credentials

**After Upload:**
- [ ] File exists on server at: `/public_html/static-site/api/config.php`
- [ ] File permissions are 600 or 644
- [ ] Test URL shows SUCCESS: `https://attral.in/api/test_config.php`
- [ ] Payment flow works: Razorpay modal opens

**Clean Up:**
- [ ] Delete test file: `https://attral.in/api/test_config.php`
- [ ] Secure config.php with proper permissions
- [ ] If using hardcoded version, replace with config.php version

---

## 🖼️ Visual Guide

```
YOUR COMPUTER                           YOUR SERVER
━━━━━━━━━━━━━━━━                       ━━━━━━━━━━━━━━━━
                                       
📁 Projects/eCommerce/                 📁 public_html/
  📁 static-site/                        📁 static-site/
    📁 api/                                📁 api/
      📄 config.php   ─────────────────➤    📄 config.php  ✅
      📄 create_order.php                   📄 create_order.php
      📄 verify.php                         📄 verify.php
                                       
       (LOCAL ONLY)                    (NEEDS TO BE HERE!)
```

---

## 🔍 Verify Upload Success

**Method 1: Test Config Script**
```
https://attral.in/api/test_config.php
```

**Method 2: Check in File Manager**
- Login to Hostinger/cPanel
- Navigate to `public_html/static-site/api/`
- Verify `config.php` exists and is ~500 bytes

**Method 3: SSH (Advanced)**
```bash
ssh your-user@attral.in
ls -lah /path/to/public_html/static-site/api/config.php
```

---

## ❓ Common Issues

### **"File not found" after upload**

**Problem:** Uploaded to wrong location

**Fix:** Check these common paths:
- ✅ `/public_html/static-site/api/config.php`
- ✅ `/home/youruser/public_html/static-site/api/config.php`
- ❌ `/static-site/api/config.php` (missing public_html)
- ❌ `/public_html/api/config.php` (missing static-site)

### **Still getting 401 error**

**Problem:** File permissions or PHP can't read it

**Fix:**
```bash
# Set proper permissions
chmod 644 static-site/api/config.php

# Or more restrictive
chmod 600 static-site/api/config.php
```

### **"Config loaded: NO"**

**Problem:** PHP syntax error or wrong format

**Fix:** Verify config.php starts with:
```php
<?php
return [
    'RAZORPAY_KEY_ID' => 'rzp_live_RKD5kwFAOZ05UD',
    // ...
];
?>
```

---

## 🎯 Expected Timeline

- ⏱️ **Upload file:** 2 minutes
- ⏱️ **Test config:** 30 seconds  
- ⏱️ **Test payment:** 1 minute
- ⏱️ **Total:** ~4 minutes

---

## 🚀 Ready?

1. Open your FTP client or file manager
2. Upload `config.php` to `/public_html/static-site/api/`
3. Test at: `https://attral.in/api/test_config.php`
4. See SUCCESS? You're done! 🎉
5. Try payment at: `http://localhost:8000/order.html?type=cart`

---

**Status:** 🔴 ACTION REQUIRED  
**Priority:** URGENT  
**Estimated Fix Time:** 4 minutes

Let me know once you've uploaded the file and I'll help you test it! 🚀

