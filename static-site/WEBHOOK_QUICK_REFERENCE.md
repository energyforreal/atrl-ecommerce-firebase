# ⚡ Razorpay Webhook - Quick Reference Card

## 🔗 PRIMARY Webhook URL
```
https://asia-south1-e-commerce-1d40f.cloudfunctions.net/razorpayWebhook
```

## 🔐 Webhook Secret
```
Rakeshmurali@10
```

## ✅ Active Events (Check All 4)
```
✅ payment.authorized
✅ payment.captured
✅ payment.failed
✅ order.paid
```

---

## 📋 Razorpay Dashboard Quick Setup

1. **Login:** https://dashboard.razorpay.com/
2. **Navigate:** Settings → Webhooks
3. **Click:** Create New Webhook (or Edit existing)
4. **Paste URL:** `https://asia-south1-e-commerce-1d40f.cloudfunctions.net/razorpayWebhook`
5. **Enter Secret:** `Rakeshmurali@10`
6. **Select Events:** All 4 events listed above
7. **Click:** Save/Create

---

## 🧪 Quick Test

### Test Payment:
- **Card:** 4111 1111 1111 1111
- **Expiry:** Any future date
- **CVV:** Any 3 digits

### Check Logs:
```bash
cd static-site/functions
firebase functions:log --only razorpayWebhook
```

---

## 🔍 Verify It's Working

✅ Check Firebase Functions logs for:
```
🔔 PRIMARY WEBHOOK: Received webhook request
✅ Signature verified
✅ Order created via PHP manager
```

✅ Check Firestore for new order with:
```
paymentStatus: "captured"
webhookProcessed: true
webhookSource: "cloud-function-primary"
```

✅ Customer receives confirmation email

---

## 🆘 Emergency Commands

### View Logs
```bash
firebase functions:log --only razorpayWebhook --lines 50
```

### Redeploy
```bash
cd static-site/functions
firebase deploy --only functions:razorpayWebhook
```

### Check Status
```bash
firebase functions:list
```

---

**That's it! Keep this handy for quick reference.** 📌

