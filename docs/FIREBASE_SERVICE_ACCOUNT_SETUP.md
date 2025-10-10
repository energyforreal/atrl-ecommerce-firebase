# Firebase Service Account Setup Guide

## ⚠️ CRITICAL: Firebase Service Account File Missing

Your Firestore order saving is currently **NOT WORKING** because the Firebase service account credentials file is missing.

## What You Need to Do

### Step 1: Get Your Firebase Service Account JSON

1. **Go to Firebase Console:**
   - Visit: https://console.firebase.google.com/project/e-commerce-1d40f/settings/serviceaccounts/adminsdk
   - Or navigate to: Firebase Console → Project Settings → Service Accounts

2. **Generate a New Private Key:**
   - Click the "Generate new private key" button
   - Confirm the action in the popup dialog
   - A JSON file will automatically download (e.g., `e-commerce-1d40f-firebase-adminsdk-xxxxx.json`)

### Step 2: Save the File to Your Project

1. **Rename the downloaded file** to: `firebase-service-account.json`

2. **Place it in:** `static-site/api/firebase-service-account.json`
   - Full path: `C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\api\firebase-service-account.json`

3. **Verify the file structure** - it should look like this:
   ```json
   {
     "type": "service_account",
     "project_id": "e-commerce-1d40f",
     "private_key_id": "...",
     "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
     "client_email": "firebase-adminsdk-xxxxx@e-commerce-1d40f.iam.gserviceaccount.com",
     "client_id": "...",
     "auth_uri": "https://accounts.google.com/o/oauth2/auth",
     "token_uri": "https://oauth2.googleapis.com/token",
     "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
     "client_x509_cert_url": "..."
   }
   ```

### Step 3: Security Considerations

⚠️ **IMPORTANT SECURITY NOTES:**

1. **Never commit this file to version control**
   - This file contains sensitive credentials
   - Make sure it's in your `.gitignore` file
   - Never share it publicly

2. **File Permissions:**
   - Ensure only your web server can read this file
   - On production servers, set appropriate file permissions (e.g., `chmod 600`)

3. **Backup:**
   - Keep a secure backup of this file
   - If lost, you'll need to generate a new one from Firebase Console

## What This File Enables

Once the file is in place, the following features will work:

✅ **Orders will save to Firestore database**
- Orders will appear in Firebase Console → Firestore → `orders` collection

✅ **Coupon tracking will work**
- Coupon usage counters will increment properly
- No more empty `{}` responses

✅ **Order queries will function**
- `order-success.html` will load order details from Firestore
- Admin dashboard will display orders correctly

✅ **Affiliate commission tracking**
- Affiliate sales will be recorded properly

## Testing After Setup

After placing the file, test by:

1. **Place a test order** on your website
2. **Check browser console** - should see:
   - `✅ [DEBUG] FIRESTORE_MGR: *** ORDER SAVED TO FIRESTORE SUCCESSFULLY ***`
   - No errors about missing service account file

3. **Check Firebase Console:**
   - Go to: https://console.firebase.google.com/project/e-commerce-1d40f/firestore
   - Navigate to `orders` collection
   - Your test order should appear there

4. **Check email inbox:**
   - You should receive order confirmation email (SMTP is now configured)
   - You should receive invoice email with attachment

## Current Status

- ✅ **SMTP Credentials Added** - Email sending is now configured
- ⏳ **Firebase Service Account** - **WAITING FOR YOU TO ADD THIS FILE**

## Need Help?

If you encounter any issues:

1. **File not found errors:** Double-check the file path and name
2. **Permission errors:** Check file permissions on your server
3. **Invalid credentials:** Regenerate the key from Firebase Console
4. **Still not working:** Check PHP error logs at `static-site/logs/` or server error logs

---

**Next Step:** Follow the instructions above to download and place the `firebase-service-account.json` file, then test your order flow.

