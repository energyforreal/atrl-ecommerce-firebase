# Firestore Setup Guide for ATTRAL E-commerce

## 🔧 Server-side Setup (PHP)

### 1. Install Firebase Admin SDK
```bash
cd api/
composer install
```

### 2. Download Firebase Service Account Key
1. Go to [Firebase Console](https://console.firebase.google.com/)
2. Select project: `e-commerce-1d40f`
3. Go to Project Settings → Service Accounts
4. Click "Generate new private key"
5. Save as `firebase-service-account.json` in the `api/` folder
6. **IMPORTANT**: Add this file to `.gitignore` for security

### 3. Set Environment Variables (Optional)
```bash
export GOOGLE_APPLICATION_CREDENTIALS="/path/to/api/firebase-service-account.json"
```

## 🔐 Firestore Security Rules

Add these rules in Firebase Console → Firestore → Rules:

```javascript
rules_version = '2';
service cloud.firestore {
  match /databases/{database}/documents {
    // Orders collection - allow authenticated users to read/write
    match /orders/{orderId} {
      allow read, write: if request.auth != null;
      // Allow server-side writes (no auth required for server)
      allow write: if request.auth == null && 
        resource.data.source == 'server';
    }
    
    // Users collection - users can only access their own data
    match /users/{userId} {
      allow read, write: if request.auth != null && request.auth.uid == userId;
    }
    
    // Products collection - read-only for all authenticated users
    match /products/{productId} {
      allow read: if request.auth != null;
      allow write: if false; // Only admins can write (handled server-side)
    }
    
    // Coupons collection - read-only for all authenticated users
    match /coupons/{couponId} {
      allow read: if request.auth != null;
      allow write: if false; // Only admins can write (handled server-side)
    }
  }
}
```

## 🧪 Testing Firestore Integration

### 1. Test Server-side Write
```bash
curl -X POST https://attral.in/api/order_manager.php/create \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "test_order_123",
    "payment_id": "test_payment_456",
    "signature": "test_signature",
    "customer": {"firstName": "Test", "lastName": "User", "email": "test@example.com"},
    "product": {"title": "Test Product", "price": 100},
    "pricing": {"total": 100, "currency": "INR"},
    "shipping": {"address": "Test Address"},
    "payment": {"method": "razorpay"}
  }'
```

### 2. Check Firestore Console
- Go to Firebase Console → Firestore
- Look for new documents in `orders` collection
- Verify data structure and timestamps

### 3. Check Fallback Files
- Server fallback: `api/firestore_fallback.json`
- Client fallback: Browser localStorage `attral_firestore_fallback`

## 🚨 Troubleshooting

### Common Issues:

1. **"Firebase Admin SDK not available"**
   - Run `composer install` in the `api/` folder
   - Check if `vendor/` folder exists

2. **"Permission denied" errors**
   - Check Firestore security rules
   - Verify service account key is correct
   - Ensure project ID matches: `e-commerce-1d40f`

3. **Orders not appearing in Firestore**
   - Check server error logs for Firestore errors
   - Look for fallback files with order data
   - Verify Firebase project is active

4. **Client-side Firebase not loading**
   - Check browser console for Firebase errors
   - Verify Firebase config in `js/config.js`
   - Check network connectivity

## 📊 Monitoring

### Server Logs to Check:
- `FIRESTORE SUCCESS:` - Successful writes
- `FIRESTORE ERROR:` - Failed writes with error details
- `FIRESTORE WRITE:` - Fallback data logging

### Client Console Logs:
- `✅ Order saved to Firestore with ID:` - Successful client writes
- `❌ Firebase save error:` - Failed client writes
- `📝 Order saved to localStorage fallback` - Fallback saves

## 🔄 Data Reconciliation

If orders are missing from Firestore:

1. Check `api/firestore_fallback.json` for server-side fallbacks
2. Check browser localStorage for client-side fallbacks
3. Use the reconciliation script to manually import missing orders

```php
// Run this in a separate PHP script to reconcile missing orders
$fallbackFile = __DIR__ . '/firestore_fallback.json';
if (file_exists($fallbackFile)) {
    $orders = json_decode(file_get_contents($fallbackFile), true);
    foreach ($orders as $order) {
        writeToFirestore($order['orderId'], $order, $order['serverOrderId']);
    }
}
```
