# 🔧 Firebase Admin Token Generator - Setup Instructions

## 🚨 Current Issues Fixed:
1. **Missing Composer Dependencies** - Firebase SDK not installed
2. **Service Account Validation** - Better error checking
3. **Debug Information** - Enhanced error reporting
4. **Class Availability** - Proper dependency checking

## ✅ Setup Steps:

### **Step 1: Install Composer (if not already installed)**
```bash
# Download and install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### **Step 2: Install Firebase Dependencies**
```bash
# Navigate to your project directory
cd static-site/api

# Install Firebase SDK
composer require kreait/firebase-php

# This will create vendor/ directory and install dependencies
```

### **Step 3: Verify Service Account File**
Make sure `static-site/api/firebase-service-account.json` exists and contains:
```json
{
  "type": "service_account",
  "project_id": "e-commerce-1d40f",
  "private_key_id": "...",
  "private_key": "...",
  "client_email": "...",
  "client_id": "...",
  "auth_uri": "...",
  "token_uri": "...",
  "auth_provider_x509_cert_url": "...",
  "client_x509_cert_url": "..."
}
```

### **Step 4: Test the API**
```bash
# Test with curl
curl -X POST http://localhost:8000/api/generate-admin-token.php \
  -H "Content-Type: application/json" \
  -d '{"email": "attralsolar@gmail.com"}'
```

## 🧪 Expected Results:

### **Success Response:**
```json
{
  "success": true,
  "customToken": "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9...",
  "uid": "admin-...",
  "expiresAt": "2025-10-03T18:30:00+00:00",
  "debug": {
    "serviceAccountPath": "/path/to/firebase-service-account.json",
    "email": "attralsolar@gmail.com",
    "timestamp": "2025-10-03T17:30:00+00:00"
  }
}
```

### **Error Response (if dependencies missing):**
```json
{
  "error": "Failed to generate custom token",
  "message": "Composer dependencies not installed. Run: composer install",
  "file": "/path/to/generate-admin-token.php",
  "line": 44,
  "debug": {
    "serviceAccountExists": true,
    "vendorAutoloadExists": false,
    "phpVersion": "8.1.0"
  }
}
```

## 🔍 Troubleshooting:

### **Issue 1: Composer not found**
```bash
# Install Composer globally
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### **Issue 2: Firebase SDK not found**
```bash
cd static-site/api
composer require kreait/firebase-php
```

### **Issue 3: Service Account file missing**
1. Go to Firebase Console → Project Settings → Service Accounts
2. Generate new private key
3. Save as `firebase-service-account.json` in `static-site/api/`

### **Issue 4: Permission errors**
```bash
# Fix file permissions
chmod 644 firebase-service-account.json
chmod 755 generate-admin-token.php
```

## 🚀 Alternative: Skip Custom Token (Recommended)

Since you've already updated your Firestore rules, you don't actually need the custom token generator. Your email/password authentication should work directly with the updated rules.

### **Test Direct Authentication:**
1. Create admin user in Firebase Console:
   - Email: `attralsolar@gmail.com`
   - Password: `admin`
2. Test the admin dashboard login
3. Verify Firestore access works

## 📞 Need Help?

If you're still having issues:
1. Check the error response from the API
2. Verify all dependencies are installed
3. Ensure service account file is valid
4. Check PHP version compatibility (requires PHP 7.4+)

**The custom token generator is optional since your Firestore rules are already updated to allow your email directly!**
