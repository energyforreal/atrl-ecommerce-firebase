# 🔑 How to Get Your Firebase Service Account Key

## ⚡ Quick Start (5 Minutes)

Follow these exact steps to get your Firebase service account key:

### Step 1: Open Firebase Console

Click this link (or copy-paste into your browser):
```
https://console.firebase.google.com/project/e-commerce-1d40f/settings/serviceaccounts/adminsdk
```

**Alternative path:**
1. Go to https://console.firebase.google.com
2. Click on your project: **e-commerce-1d40f**
3. Click the **gear icon** (⚙️) next to "Project Overview"
4. Select **Project settings**
5. Click the **Service accounts** tab

---

### Step 2: Generate New Private Key

1. You'll see a page titled **"Service accounts"**
2. Make sure **"Firebase Admin SDK"** is selected in the left sidebar
3. Scroll down to the **"Firebase service account"** section
4. Click the button: **"Generate new private key"**
5. A popup will appear asking for confirmation
6. Click **"Generate key"** in the popup

**Screenshot guide:**
```
┌────────────────────────────────────────────┐
│  Firebase Admin SDK                        │
│  ┌──────────────────────────────────────┐  │
│  │ Firebase service account             │  │
│  │                                      │  │
│  │ Your service account:                │  │
│  │ firebase-adminsdk-xxxxx@...          │  │
│  │                                      │  │
│  │ [Generate new private key]  ←────────┼─ Click this!
│  └──────────────────────────────────────┘  │
└────────────────────────────────────────────┘
```

---

### Step 3: Download & Save the File

1. A JSON file will automatically download (e.g., `e-commerce-1d40f-firebase-adminsdk-xxxxx.json`)
2. Find the downloaded file (usually in your Downloads folder)
3. **RENAME** the file to exactly: `firebase-service-account.json`
4. **MOVE** it to your project folder:
   ```
   C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\api\firebase-service-account.json
   ```

**Exact path:**
```
eCommerce/
└── static-site/
    └── api/
        └── firebase-service-account.json  ← Put it here!
```

---

### Step 4: Verify the File

Open PowerShell in your project directory and run:

```powershell
php validate-firebase-setup.php
```

This will check if:
- ✅ File exists in the correct location
- ✅ File contains valid JSON
- ✅ All required fields are present
- ✅ Project ID matches
- ✅ Private key format is correct
- ✅ SMTP credentials are configured

**Expected output:**
```
🔍 Validating Firebase Service Account Setup...

1️⃣ Checking if firebase-service-account.json exists...
   ✅ PASSED: File exists

2️⃣ Checking if file contains valid JSON...
   ✅ PASSED: Valid JSON format

... (more checks)

═══════════════════════════════════════════════════════════
✅ ALL CHECKS PASSED!
═══════════════════════════════════════════════════════════
```

---

## 🔒 Security Important Notes

### ⚠️ NEVER:
- ❌ Commit this file to Git/GitHub
- ❌ Share it publicly anywhere
- ❌ Email it to anyone
- ❌ Upload it to any website
- ❌ Post it in forums or Discord

### ✅ ALWAYS:
- ✅ Keep it in a secure location
- ✅ Add it to `.gitignore`
- ✅ Keep a backup in a safe place
- ✅ Regenerate if compromised

**The file contains:**
- Private keys that give full access to your Firebase project
- Ability to read/write all Firestore data
- Ability to modify Firebase configuration
- **Full admin privileges** to your Firebase project

---

## 🐛 Troubleshooting

### Problem: "Permission denied" error
**Solution:** You may not have admin access to the Firebase project
- Ask the project owner to add you as an **Owner** or **Editor**
- Or ask them to generate the key and send it to you securely

### Problem: Downloaded file is empty or corrupted
**Solution:** Try again
1. Refresh the Firebase Console page
2. Click "Generate new private key" again
3. If it keeps failing, try a different browser

### Problem: Can't find the downloaded file
**Solution:** Check your browser's downloads
- Chrome: Click the ⋮ menu → Downloads (or Ctrl+J)
- Edge: Click the ⋯ menu → Downloads (or Ctrl+J)
- Look for a file like `e-commerce-1d40f-firebase-adminsdk-xxxxx.json`

### Problem: File structure looks wrong
**Solution:** Compare with the example
- Open `static-site/api/firebase-service-account.json.example`
- Your file should have similar fields
- It should NOT be an array, it should be a single JSON object

---

## ✅ After Adding the File

Run these commands to test everything works:

```powershell
# Validate the Firebase setup
php validate-firebase-setup.php

# If validation passes, test with a real order
# 1. Go to your website
# 2. Add a product to cart
# 3. Complete checkout with Razorpay test card
# 4. Check your email for confirmation
# 5. Check Firebase Console → Firestore → orders collection
```

---

## 📞 Need Help?

If you're stuck:

1. **Check the validation script output** - it will tell you exactly what's wrong
2. **Look at the example file** - `firebase-service-account.json.example`
3. **Check Firebase Console** - make sure you're logged in with the right account
4. **Verify project ID** - make sure you're in the `e-commerce-1d40f` project

---

**You're almost done! Just follow the steps above and you'll have email sending and Firestore orders working in 5 minutes! 🚀**

