# 🔥 PRIMARY Webhook Setup Guide

## ✅ Cloud Function is Now PRIMARY Webhook Handler

Your Cloud Function at `https://asia-south1-e-commerce-1d40f.cloudfunctions.net/razorpayWebhook` is now **production-ready** and serves as your **PRIMARY** Razorpay webhook handler.

---

## 🎯 What This Webhook Does

### 1. **Receives Razorpay Events**
- ✅ `payment.captured` - Successful payments
- ✅ `payment.failed` - Failed payments  
- ✅ `payment.authorized` - Authorized payments
- ✅ `order.paid` - Paid orders

### 2. **Verifies Security**
- ✅ Validates webhook signature using HMAC SHA256
- ✅ Rejects unauthorized requests
- ✅ Prevents replay attacks

### 3. **Processes Orders**
- ✅ Extracts customer data from Razorpay payment notes
- ✅ Calls your PHP order manager (`firestore_order_manager.php`)
- ✅ Handles affiliate tracking, coupon processing, emails
- ✅ Has built-in fallback to save directly to Firestore

### 4. **Updates Firestore**
- ✅ Marks orders as `captured` or `failed`
- ✅ Stores payment IDs and timestamps
- ✅ Tracks webhook processing status

---

## 🛠️ Razorpay Dashboard Setup

### Step 1: Login to Razorpay
1. Go to [Razorpay Dashboard](https://dashboard.razorpay.com/)
2. Login with your credentials

### Step 2: Navigate to Webhooks
1. Click **Settings** (gear icon in left sidebar)
2. Click **Webhooks** 
3. You'll see any existing webhooks here

### Step 3: Configure Primary Webhook

#### Option A: Update Existing Webhook (Recommended)
If you already have a webhook configured:
1. Click on the existing webhook
2. Click **Edit**
3. Update the **Webhook URL** to:
   ```
   https://asia-south1-e-commerce-1d40f.cloudfunctions.net/razorpayWebhook
   ```
4. Verify **Secret** is set to: `Rakeshmurali@10`
5. Click **Save**

#### Option B: Create New Webhook
If you don't have a webhook or want a fresh start:
1. Click **+ Create New Webhook**
2. **Webhook URL:**
   ```
   https://asia-south1-e-commerce-1d40f.cloudfunctions.net/razorpayWebhook
   ```
3. **Secret:** `Rakeshmurali@10`
4. **Active Events** - Select these:
   - ✅ `payment.authorized`
   - ✅ `payment.captured` ⭐ **MOST IMPORTANT**
   - ✅ `payment.failed`
   - ✅ `order.paid`
5. Click **Create Webhook**

---

## 🔐 Configuration Details

### Webhook URL
```
https://asia-south1-e-commerce-1d40f.cloudfunctions.net/razorpayWebhook
```

### Webhook Secret
```
Rakeshmurali@10
```

### Region
```
asia-south1 (Mumbai, India)
```

### Active Events
```
✅ payment.authorized
✅ payment.captured (CRITICAL - handles successful payments)
✅ payment.failed
✅ order.paid
```

---

## 🔄 How It Works (Flow Diagram)

```
1. Customer completes payment on your website
   ↓
2. Razorpay captures payment
   ↓
3. Razorpay sends webhook to Cloud Function
   ↓
4. Cloud Function verifies signature ✅
   ↓
5. Cloud Function extracts customer/order data
   ↓
6. Cloud Function calls PHP Order Manager
   ↓
7. PHP Order Manager:
   - Creates order in Firestore
   - Processes affiliate tracking
   - Increments coupon usage (via onOrderCreated trigger)
   - Sends confirmation emails
   ↓
8. Cloud Function updates order status
   ↓
9. Customer sees order-success page
```

---

## 📊 What Happens to PHP Webhook?

### Current Situation
You have **TWO** webhook handlers:

1. **Cloud Function (PRIMARY)** ⭐
   - URL: `https://asia-south1-e-commerce-1d40f.cloudfunctions.net/razorpayWebhook`
   - Status: Active, Enhanced, Production-Ready
   - Role: **Main webhook receiver**

2. **PHP Webhook (Legacy/Backup)**
   - URL: `https://attral.in/api/webhook.php`
   - Status: Still functional
   - Role: Backup (if configured)

### Recommendations

**Option 1: Single Primary (Recommended)**
- Configure **only** the Cloud Function in Razorpay
- Disable/remove the PHP webhook from Razorpay dashboard
- Keep the PHP file for manual testing if needed

**Option 2: Dual Webhooks (Redundancy)**
- Keep **both** webhooks configured in Razorpay
- Both will receive and process payments
- Provides failover redundancy
- May result in duplicate processing (but idempotent)

---

## 🧪 Testing Your Webhook

### Method 1: Test Payment (Recommended)
1. Go to your website: `https://attral.in`
2. Add a product to cart
3. Proceed to checkout
4. Use Razorpay test card:
   - **Card:** `4111 1111 1111 1111`
   - **Expiry:** Any future date
   - **CVV:** Any 3 digits
5. Complete payment
6. Check Firebase Functions logs:
   ```bash
   firebase functions:log --only razorpayWebhook
   ```

### Method 2: Razorpay Dashboard Test
1. Go to Razorpay Dashboard → Webhooks
2. Click on your webhook
3. Click **Send Test Webhook**
4. Select `payment.captured` event
5. Click **Send**
6. Check response and logs

### Method 3: View Real-time Logs
```bash
# In your terminal
cd static-site/functions
firebase functions:log --only razorpayWebhook --lines 50
```

---

## ✅ Success Indicators

You'll know it's working when you see:

### In Firebase Console Logs
```
🔔 PRIMARY WEBHOOK: Received webhook request
✅ Signature verified
📨 Event type: payment.captured
💰 Processing payment.captured: Order order_xxx, Payment pay_xxx
👤 Customer: John Doe (john@example.com)
📝 Order data prepared, calling PHP order manager...
✅ Order created via PHP manager: ATTRAL-001234
📧 Emails and affiliate tracking handled by PHP manager
✅ Updated existing order in Firestore
✅ Webhook processed successfully
```

### In Firestore Console
1. Go to Firebase Console → Firestore
2. Open `orders` collection
3. Find your order
4. Check these fields:
   ```json
   {
     "paymentStatus": "captured",
     "webhookProcessed": true,
     "webhookProcessedAt": "[timestamp]",
     "webhookSource": "cloud-function-primary"
   }
   ```

### In Your Email
- Order confirmation email sent to customer
- Order notification email sent to admin

---

## 🚨 Troubleshooting

### Webhook Not Receiving Events
1. **Check Razorpay Dashboard**
   - Go to Webhooks → Click your webhook
   - Check "Recent Deliveries" tab
   - Look for failed deliveries (red)

2. **Verify URL is Correct**
   ```
   https://asia-south1-e-commerce-1d40f.cloudfunctions.net/razorpayWebhook
   ```
   
3. **Check Secret Matches**
   - Razorpay secret: `Rakeshmurali@10`
   - Cloud Function secret: `Rakeshmurali@10` (in code)

### Signature Verification Failing
- Error: `Invalid signature`
- **Solution:** Verify webhook secret in Razorpay matches `Rakeshmurali@10`

### Orders Not Creating
- Check Firebase Functions logs for errors
- Verify PHP order manager is accessible at:
  ```
  https://attral.in/api/firestore_order_manager.php/create
  ```
- Check if fallback saved to Firestore (look for `source: "cloud-function-fallback"`)

### Coupons Not Incrementing
- The `onOrderCreated` Cloud Function handles this
- It triggers automatically when order is added to Firestore
- Check logs: `firebase functions:log --only onOrderCreated`

---

## 📞 Support

### View All Logs
```bash
# All functions
firebase functions:log

# Just webhook
firebase functions:log --only razorpayWebhook

# Just coupon tracking
firebase functions:log --only onOrderCreated

# Live tail (real-time)
firebase functions:log --only razorpayWebhook --tail
```

### Check Function Status
```bash
firebase functions:list
```

### Redeploy if Needed
```bash
cd static-site/functions
firebase deploy --only functions:razorpayWebhook
```

---

## 🎉 Benefits of Cloud Function as Primary

1. **Scalability** - Auto-scales with Firebase infrastructure
2. **Reliability** - 99.95% uptime SLA
3. **Security** - Built-in DDoS protection, secure by default
4. **Monitoring** - Full logging and error tracking
5. **Global** - Low latency from Razorpay servers
6. **Maintenance-Free** - No server management needed

---

## 📝 Important Notes

- ✅ Cloud Function **calls** your PHP order manager
- ✅ All business logic (affiliates, emails, coupons) **still runs in PHP**
- ✅ Cloud Function is just the **entry point**
- ✅ This architecture provides best of both worlds:
  - Reliable webhook receiver (Cloud Function)
  - Existing business logic (PHP APIs)
  - Easy maintenance and updates

---

## ✅ Final Checklist

Before marking this as complete, verify:

- [ ] Cloud Function deployed successfully
- [ ] Razorpay webhook configured with Cloud Function URL
- [ ] Webhook secret set to `Rakeshmurali@10`
- [ ] Active events selected: `payment.captured`, `payment.failed`, `payment.authorized`, `order.paid`
- [ ] Test payment processed successfully
- [ ] Order created in Firestore
- [ ] Confirmation email received
- [ ] Coupon usage incremented (if coupon used)
- [ ] Firebase logs show successful processing

---

**Your PRIMARY webhook is ready for production!** 🚀

