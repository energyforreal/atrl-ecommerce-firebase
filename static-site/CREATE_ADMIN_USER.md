# 🔐 Create Firebase Admin User - Step by Step

## 🎯 Problem:
The admin dashboard shows `No user signed in` and `Missing or insufficient permissions` because we're using a mock user instead of a real Firebase authentication.

## ✅ Solution: Create Real Firebase Admin User

### **Step 1: Go to Firebase Console**
1. Open [Firebase Console](https://console.firebase.google.com/)
2. Select project: `e-commerce-1d40f`
3. Go to **Authentication** → **Users** tab

### **Step 2: Add Admin User**
1. Click **"Add User"** button
2. Enter:
   - **Email**: `attralsolar@gmail.com`
   - **Password**: `Rakeshmurali@10`
3. Click **"Add User"**

### **Step 3: Verify User Created**
- Check that `attralsolar@gmail.com` appears in the users list
- Ensure the user is marked as "Verified" (email verification)

### **Step 4: Test Admin Dashboard**
1. Refresh `admin-dashboard-unified.html`
2. Login with:
   - Username: `attral`
   - Password: `Rakeshmurali@10`
3. Verify real data loads from Firestore

## 🎯 Expected Results:
```javascript
✅ Firebase ready for unified admin dashboard
🔐 Admin authentication successful
📦 Loading orders from Firestore...
✅ Loaded X orders from Firestore
👥 Loading users from Firestore...
✅ Loaded X users from Firestore
💬 Loading messages from Firestore...
✅ Loaded X messages from Firestore
🎫 Loading coupons from Firestore...
✅ Loaded X coupons from Firestore
🤝 Loading affiliates from Firestore...
✅ Loaded X affiliates from Firestore
```

## 🔧 Alternative: Update Firestore Rules for Local Session

If you prefer to keep the local session approach, update your Firestore rules to:

```javascript
function isAdmin() { 
  return isSignedIn() && (
    request.auth.token.admin == true ||
    request.auth.token.email == 'attralsolar@gmail.com' ||
    request.auth.token.email == 'admin@attral.in' ||
    // Allow any authenticated user for development
    request.auth != null
  ); 
}
```

**But creating the real Firebase user is the recommended approach!**
