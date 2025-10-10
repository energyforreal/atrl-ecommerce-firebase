# 🏢 Hostinger Firebase/Firestore Deployment Guide

## 📋 Overview

This guide helps you deploy your eCommerce system with Firestore integration to Hostinger hosting. It covers testing, deployment, and fallback options.

---

## ✅ Prerequisites

Before deploying to Hostinger, ensure you have:

1. **Hostinger Hosting Account** with:
   - PHP 7.4 or higher
   - cURL extension enabled
   - OpenSSL extension enabled
   - At least 128MB memory limit

2. **Firebase Project**:
   - Project: `e-commerce-1d40f`
   - Firestore database created
   - Service account key downloaded

3. **Local Development**:
   - Project working locally
   - Composer dependencies installed

---

## 🧪 Phase 1: Test Hostinger Compatibility

### Step 1: Upload Test Files

Upload these files to your Hostinger account using FTP or File Manager:

```
your-domain.com/
├── test-hostinger-compatibility.php
├── test-hostinger-firestore-write.php
└── vendor/  (entire folder from static-site/api/vendor)
```

### Step 2: Run Compatibility Test

1. Open browser and navigate to:
   ```
   https://yourdomain.com/test-hostinger-compatibility.php
   ```

2. Review the test results:
   - ✅ **All green** = Proceed with PHP SDK deployment
   - ⚠️ **Some warnings** = May work but check specific issues
   - ❌ **Critical failures** = Use REST API fallback

### Step 3: Interpret Results

#### **Scenario A: All Tests Pass** ✅
- Your Hostinger supports Firebase PHP SDK
- Proceed with standard deployment (Phase 2)

#### **Scenario B: PHP Extensions Missing** ⚠️
- Contact Hostinger support to enable missing extensions
- Common fixes available in Hostinger cPanel

#### **Scenario C: SDK Won't Work** ❌
- Use REST API fallback (Phase 3)
- Still fully functional, just different implementation

---

## 📦 Phase 2: Standard Deployment (PHP SDK)

Use this if compatibility test passed.

### Step 1: Prepare Files Locally

```bash
# 1. Install dependencies locally
cd static-site/api
composer install --no-dev --optimize-autoloader
cd ../..

# 2. Verify vendor folder exists
ls static-site/api/vendor
```

### Step 2: Upload to Hostinger

Upload the following via FTP or Hostinger File Manager:

```
public_html/
├── static-site/
│   ├── index.html
│   ├── order.html
│   ├── assets/
│   ├── api/
│   │   ├── vendor/  ⭐ IMPORTANT: Upload entire folder
│   │   ├── firestore_order_manager.php
│   │   ├── config.php
│   │   ├── firebase-service-account.json  ⭐ CRITICAL
│   │   ├── coupon_tracking_service.php
│   │   └── ... (other API files)
│   └── ... (other files)
└── ... (root files)
```

### Step 3: Upload Service Account Securely

**⚠️ IMPORTANT: Keep service account secure!**

1. Upload `firebase-service-account.json` to:
   ```
   /public_html/static-site/api/firebase-service-account.json
   ```

2. Protect it with `.htaccess`:
   
   Create `/public_html/static-site/api/.htaccess`:
   ```apache
   <Files "firebase-service-account.json">
       Order Allow,Deny
       Deny from all
   </Files>
   ```

3. Set file permissions to `600` (read/write for owner only)

### Step 4: Configure API Endpoint

Update your `order.html` to point to Hostinger URL:

```javascript
// In order.html, update the API endpoint:
const API_BASE_URL = 'https://yourdomain.com/static-site/api';

// Example in completeOrder function:
fetch(`${API_BASE_URL}/firestore_order_manager.php/create`, {
    method: 'POST',
    // ... rest of code
});
```

### Step 5: Test on Hostinger

1. Navigate to:
   ```
   https://yourdomain.com/test-hostinger-firestore-write.php
   ```

2. Click "Run Test"

3. Expected result:
   ```
   ✅✅✅ SUCCESS! Order written to Firestore!
   Document ID: abc123xyz789
   ```

4. Verify in Firebase Console:
   - Go to Firestore Database
   - Check `orders` collection
   - Find the test document

### Step 6: Test Live Checkout

1. Go to your live site:
   ```
   https://yourdomain.com/static-site/order.html
   ```

2. Complete a test purchase

3. Verify order appears in Firestore

---

## 🔄 Phase 3: REST API Fallback Deployment

Use this if PHP SDK doesn't work on Hostinger.

### Why REST API Fallback?

**Advantages:**
- ✅ Works with just cURL (no special extensions)
- ✅ Lighter weight (no Composer dependencies)
- ✅ Compatible with almost any host
- ✅ Same functionality as PHP SDK

**Disadvantages:**
- ⚠️ Manual type conversion
- ⚠️ More code to maintain

### Step 1: Upload REST API Files

Upload to Hostinger:

```
public_html/static-site/api/
├── firestore_rest_api_fallback.php  ⭐ NEW
├── firebase-service-account.json
└── config.php
```

### Step 2: Update order.html

Change API endpoint in `order.html`:

```javascript
// OLD (PHP SDK):
const API_URL = '/api/firestore_order_manager.php/create';

// NEW (REST API):
const API_URL = '/api/firestore_rest_api_fallback.php/create';
```

### Step 3: Test REST API

Test with cURL or browser:

```bash
curl -X POST https://yourdomain.com/static-site/api/firestore_rest_api_fallback.php/create \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "test_order_123",
    "payment_id": "test_payment_123",
    "customer": { ... },
    "product": { ... },
    "pricing": { ... },
    "shipping": { ... },
    "payment": { ... }
  }'
```

Expected response:
```json
{
  "success": true,
  "orderId": "abc123",
  "orderNumber": "ATRL-0001",
  "api_source": "rest_api_fallback"
}
```

---

## 🔐 Security Best Practices

### 1. Protect Service Account

**Create `.htaccess` in `/api/` folder:**

```apache
# Deny access to sensitive files
<FilesMatch "\.(json|env)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Allow PHP execution
<FilesMatch "\.php$">
    Allow from all
</FilesMatch>
```

### 2. Set Proper File Permissions

```bash
# On Hostinger via SSH or File Manager:
chmod 600 firebase-service-account.json
chmod 644 *.php
chmod 755 api/
```

### 3. Enable HTTPS

1. Go to Hostinger Control Panel
2. Navigate to: Advanced → SSL
3. Enable free Let's Encrypt SSL
4. Force HTTPS redirect

### 4. Hide PHP Errors in Production

In `config.php` or `.htaccess`:

```php
// config.php
if ($_SERVER['HTTP_HOST'] === 'yourdomain.com') {
    error_reporting(0);
    ini_set('display_errors', 0);
}
```

---

## 🐛 Troubleshooting

### Issue 1: "Composer vendor not found"

**Problem**: Vendor folder not uploaded or incomplete

**Solution**:
```bash
# Locally:
cd static-site/api
composer install --no-dev
# Upload entire vendor/ folder via FTP
```

### Issue 2: "Service account file not found"

**Problem**: Wrong path or file not uploaded

**Solution**:
- Verify file is at: `/public_html/static-site/api/firebase-service-account.json`
- Check file permissions: `600`
- Ensure file is valid JSON

### Issue 3: "Failed to get access token"

**Problem**: Service account invalid or network issue

**Solution**:
- Re-download service account from Firebase Console
- Check if outbound HTTPS connections are allowed
- Contact Hostinger support about Google API access

### Issue 4: "cURL error: Couldn't resolve host"

**Problem**: Network connectivity issue

**Solution**:
- Check DNS settings
- Try REST API fallback
- Contact Hostinger support about outbound connections

### Issue 5: "Memory limit exceeded"

**Problem**: PHP memory limit too low

**Solution**:
1. Go to Hostinger Control Panel
2. Advanced → PHP Configuration
3. Increase `memory_limit` to 256M

### Issue 6: "Class 'Google\Cloud\Firestore\FirestoreClient' not found"

**Problem**: Autoloader not loaded or vendor missing

**Solution**:
```php
// At top of firestore_order_manager.php, ensure:
require_once __DIR__ . '/vendor/autoload.php';
```

---

## 📊 Performance Optimization

### 1. Enable OPcache

In Hostinger cPanel:
1. Go to: Advanced → PHP Configuration
2. Enable: `opcache.enable=1`
3. Set: `opcache.memory_consumption=128`

### 2. Use Persistent Connections

The Firestore client reuses connections automatically.

### 3. Cache Access Tokens

For REST API fallback, cache tokens for 1 hour:

```php
// Add to firestore_rest_api_fallback.php
$tokenCacheFile = sys_get_temp_dir() . '/firebase_token_cache.json';

if (file_exists($tokenCacheFile)) {
    $cache = json_decode(file_get_contents($tokenCacheFile), true);
    if ($cache['expires'] > time()) {
        return $cache['token'];
    }
}

// ... get new token ...

file_put_contents($tokenCacheFile, json_encode([
    'token' => $accessToken,
    'expires' => time() + 3600
]));
```

---

## 🧹 Cleanup Test Data

After successful deployment, clean up test orders:

### Option 1: Firebase Console

1. Go to: https://console.firebase.google.com
2. Select: `e-commerce-1d40f`
3. Navigate to: Firestore Database → orders
4. Filter: `testOrder == true`
5. Delete test documents manually

### Option 2: Script (Local)

```bash
php test-firestore-delete-dummy.php --all-test-orders
```

---

## ✅ Deployment Checklist

Use this checklist to ensure everything is deployed correctly:

### Pre-Deployment
- [ ] Run compatibility test locally
- [ ] Test locally with `php -S localhost:8000`
- [ ] Verify all Composer dependencies installed
- [ ] Download Firebase service account key
- [ ] Backup existing site

### Deployment
- [ ] Upload all static files (HTML, CSS, JS, images)
- [ ] Upload `/api/` folder with all PHP files
- [ ] Upload `/vendor/` folder (if using PHP SDK)
- [ ] Upload `firebase-service-account.json` securely
- [ ] Set proper file permissions (644 for PHP, 600 for JSON)
- [ ] Create `.htaccess` for security
- [ ] Enable HTTPS/SSL
- [ ] Update API endpoints in order.html

### Testing
- [ ] Run `test-hostinger-compatibility.php`
- [ ] Run `test-hostinger-firestore-write.php`
- [ ] Complete a test purchase on order.html
- [ ] Verify test order in Firebase Console
- [ ] Test order retrieval/status check
- [ ] Check browser console for errors
- [ ] Test on mobile devices

### Post-Deployment
- [ ] Clean up test orders
- [ ] Delete test files (test-hostinger-*.php)
- [ ] Monitor error logs
- [ ] Set up Firebase alerts
- [ ] Document any custom configuration

---

## 📈 Monitoring

### 1. Check PHP Error Logs

In Hostinger:
1. File Manager → `public_html/error_log`
2. Or via FTP: Download and review error_log

### 2. Monitor Firebase Console

- Go to: Firebase Console → Firestore Database
- Check: Usage tab for quota consumption
- Set up: Alerts for high usage

### 3. Test Regularly

- Weekly: Run test order
- Monthly: Review Firestore rules
- Quarterly: Update dependencies

---

## 🆘 Getting Help

### Hostinger Support

- **Control Panel**: Live chat available 24/7
- **Email**: support@hostinger.com
- **Knowledge Base**: https://support.hostinger.com

**Common Requests**:
- "Please enable cURL and OpenSSL extensions for PHP"
- "I need to allow outbound connections to googleapis.com"
- "Please increase PHP memory limit to 256M"

### Firebase Support

- **Console**: https://console.firebase.google.com
- **Documentation**: https://firebase.google.com/docs/firestore
- **Community**: Stack Overflow (tag: firebase)

---

## 🎯 Success Criteria

Your deployment is successful when:

✅ Compatibility test shows all green  
✅ Write test creates order in Firestore  
✅ Test checkout completes successfully  
✅ Order appears in Firebase Console  
✅ Customer receives order confirmation  
✅ No errors in browser console  
✅ No errors in PHP logs  

---

## 📚 File Reference

| File | Purpose | Required |
|------|---------|----------|
| `firestore_order_manager.php` | Main order API (PHP SDK) | Yes (SDK) |
| `firestore_rest_api_fallback.php` | Fallback API (REST) | Yes (fallback) |
| `firebase-service-account.json` | Authentication | **Critical** |
| `vendor/` | Composer dependencies | Yes (SDK) |
| `test-hostinger-compatibility.php` | Environment test | Testing only |
| `test-hostinger-firestore-write.php` | Write test | Testing only |
| `.htaccess` | Security rules | **Critical** |

---

## 🚀 Quick Start Commands

```bash
# 1. Prepare locally
cd static-site/api && composer install --no-dev

# 2. Test locally
php -S localhost:8000 -t static-site

# 3. Upload to Hostinger (use FTP or File Manager)
# - Upload entire static-site/ folder
# - Upload firebase-service-account.json
# - Set permissions

# 4. Test on Hostinger
# Visit: https://yourdomain.com/test-hostinger-compatibility.php

# 5. If successful, test write:
# Visit: https://yourdomain.com/test-hostinger-firestore-write.php

# 6. Go live!
# Visit: https://yourdomain.com/static-site/order.html
```

---

## 🎉 Conclusion

Following this guide, you should have:
1. ✅ Tested Hostinger compatibility
2. ✅ Deployed your eCommerce system
3. ✅ Verified Firestore integration works
4. ✅ Secured your service account
5. ✅ Set up monitoring

Your customers can now place orders that are automatically saved to Firestore! 🎊

---

**Need more help?** Refer to the troubleshooting section or contact support.

**Working perfectly?** Don't forget to clean up test orders and remove test files!

