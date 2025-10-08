# Testing Razorpay Webhook

## 🔗 Webhook URL
```
https://asia-south1-e-commerce-1d40f.cloudfunctions.net/razorpayWebhook
```

## 🔐 Webhook Secret
```
Rakeshmurali@10
```

## 📋 How to Test

### Method 1: Real Payment Test
1. Go to your website
2. Add a product to cart
3. Proceed to checkout
4. Make a test payment (use Razorpay test cards)
5. Check Firebase Functions logs

### Method 2: View Logs
```bash
firebase functions:log --only razorpayWebhook
```

### Method 3: Razorpay Dashboard Test
1. Go to Razorpay Dashboard → Webhooks
2. Find your webhook
3. Click "Test Webhook"
4. Send a test `payment.captured` event

## ✅ Success Indicators

You'll know it's working when you see:
- Webhook processed successfully in Razorpay Dashboard
- Order status updated in Firestore
- Logs showing "Payment captured for order: XXX"

## 📊 Check Firestore

After webhook processes:
1. Go to Firebase Console → Firestore
2. Open `orders` collection
3. Find your test order
4. Check for these fields:
   - `paymentStatus: "captured"`
   - `webhookProcessed: true`
   - `webhookProcessedAt: [timestamp]`

## 🔄 Dual System Note

Remember: Both systems are active
- **PHP Webhook:** `your-domain.com/api/webhook.php`
- **Cloud Function:** `asia-south1-e-commerce-1d40f.cloudfunctions.net/razorpayWebhook`

Both will receive and process the same events. This is GOOD for redundancy!

