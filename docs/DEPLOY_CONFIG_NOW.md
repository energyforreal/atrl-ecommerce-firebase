# 🚀 DEPLOY CONFIG.PHP - URGENT ACTION REQUIRED

## ⚠️ IMPORTANT: The config.php file must be uploaded to your production server!

You're testing on `localhost:8000`, but your API calls go to `https://attral.in`. The `config.php` file is currently only on your local machine and needs to be on the production server.

---

## 📤 STEP 1: Upload config.php to Production Server

### **Option A: Using FTP/SFTP (Recommended)**

1. **Connect to your server** using FileZilla, WinSCP, or your hosting control panel's file manager

2. **Navigate to:**
   ```
   /path/to/your/site/static-site/api/
   ```

3. **Upload this file:**
   ```
   config.php
   ```
   
   **Location on your computer:**
   ```
   C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\api\config.php
   ```

4. **Verify the upload:**
   - File should be in: `/static-site/api/config.php`
   - File size should be around 500-600 bytes

### **Option B: Using Hostinger File Manager**

1. Login to your Hostinger control panel
2. Click **File Manager**
3. Navigate to `public_html/static-site/api/` (or wherever your site is)
4. Click **Upload**
5. Select `config.php` from your local machine
6. Click **Upload**

### **Option C: Using cPanel**

1. Login to cPanel
2. Click **File Manager**
3. Navigate to `public_html/api/` or `public_html/static-site/api/`
4. Click **Upload**
5. Select and upload `config.php`

---

## 🧪 STEP 2: Test the Configuration

### **Test Script #1: Config Verification**

Open this URL in your browser:
```
https://attral.in/api/test_config.php
```

**Expected Successful Response:**
```json
{
  "timestamp": "2025-10-08 17:30:00",
  "config_file_exists": true,
  "config_loaded": true,
  "has_razorpay_key_id": true,
  "has_razorpay_key_secret": true,
  "key_id_preview": "rzp_live...05UD",
  "api_test_result": {
    "http_code": 200,
    "success": true
  },
  "status": "✅ SUCCESS - Razorpay credentials are valid!"
}
```

**If you see FAILED:**
- ❌ Config file doesn't exist → Upload config.php
- ❌ Config file not readable → Check file permissions
- ❌ Invalid credentials (401) → Verify credentials in config.php

### **Test Script #2: Direct Order Creation Test**

After config is working, test order creation:

**Using curl (Git Bash/Terminal):**
```bash
curl -X POST https://attral.in/api/create_order.php \
  -H "Content-Type: application/json" \
  -d '{"amount":10000,"currency":"INR","receipt":"test_123"}'
```

**Expected Response:**
```json
{
  "id": "order_xxxxxxxxxxxxx",
  "entity": "order",
  "amount": 10000,
  "currency": "INR",
  "status": "created"
}
```

---

## 🔐 STEP 3: Secure the config.php File

### **Set Proper Permissions (SSH Access)**

If you have SSH access:
```bash
chmod 600 static-site/api/config.php
```

### **Add .htaccess Protection**

Create/edit `static-site/api/.htaccess`:
```apache
# Deny access to config.php
<Files "config.php">
    Order allow,deny
    Deny from all
</Files>

# Allow access to other PHP files
<FilesMatch "\.(php)$">
    Order allow,deny
    Allow from all
</FilesMatch>
```

---

## 🧹 STEP 4: Clean Up Test Files

**After confirming everything works, DELETE:**
1. `https://attral.in/api/test_config.php`
2. Comment out debug logging in `create_order.php` (lines 15-31)

---

## 🔍 Troubleshooting

### **Problem: "Config file doesn't exist"**

**Check file path:**
```bash
# SSH into server
ls -la /path/to/site/static-site/api/config.php

# Should show:
# -rw------- 1 user user 567 Oct 8 17:30 config.php
```

**Check uploaded location:**
- ✅ Correct: `/public_html/static-site/api/config.php`
- ❌ Wrong: `/public_html/api/config.php`
- ❌ Wrong: `/static-site/api/config.php` (missing public_html)

### **Problem: "Config loaded: NO"**

**Possible causes:**
1. PHP syntax error in config.php
2. File permissions too restrictive
3. Wrong file encoding (should be UTF-8 without BOM)

**Fix:**
```bash
# Check PHP syntax
php -l static-site/api/config.php

# Should output: "No syntax errors detected"
```

### **Problem: Still getting 401 error**

**Verify credentials on Razorpay Dashboard:**
1. Login: https://dashboard.razorpay.com
2. Settings → API Keys
3. Regenerate if needed and update config.php

---

## 📋 Quick Checklist

- [ ] Upload `config.php` to production server
- [ ] Verify file exists at correct path
- [ ] Test with `test_config.php`
- [ ] Confirm "✅ SUCCESS" status
- [ ] Test payment flow on order.html
- [ ] Delete test_config.php
- [ ] Secure config.php with proper permissions
- [ ] Add .htaccess protection

---

## 🎯 What Should Happen Next

After uploading config.php and testing:

1. **Browser console should show:**
   ```
   ✅ 🔧 Order Creation Response: { status: 200, responseOk: true }
   ✅ Razorpay checkout modal opens
   ```

2. **Payment flow:**
   - Click "Pay with Razorpay"
   - Razorpay modal opens
   - Enter test/live card details
   - Payment processes successfully

---

## 📞 Still Having Issues?

If after uploading you still see 401 errors:

1. **Check PHP error logs** on your server:
   ```bash
   tail -f /path/to/error.log
   ```
   
   Look for lines starting with `=== RAZORPAY CONFIG DEBUG ===`

2. **Verify file contents** on server:
   ```bash
   cat static-site/api/config.php
   ```
   
   Should show your credentials

3. **Test API directly** from server:
   ```bash
   php -r "include 'static-site/api/config.php'; var_dump($cfg);"
   ```

---

## ⚡ Quick Fix Command (SSH)

If you have SSH access, run this one command:
```bash
# Upload and set permissions in one go
scp config.php user@attral.in:/path/to/public_html/static-site/api/ && \
ssh user@attral.in "chmod 600 /path/to/public_html/static-site/api/config.php"
```

---

**Status:** ⏳ WAITING FOR CONFIG.PHP UPLOAD  
**Priority:** 🔴 HIGH - Required for payments to work  
**ETA:** 2-5 minutes

Once uploaded, test immediately with the test URLs above! 🚀

