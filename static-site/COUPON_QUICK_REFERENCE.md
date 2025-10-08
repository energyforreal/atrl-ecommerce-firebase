# 🎫 Coupon Tracking - Quick Reference Guide

## 🚀 Quick Start

### Deploy Cloud Functions
```bash
cd static-site/fulfillment-functions
npm install
firebase deploy --only functions
```

### Test the System
1. Create order with coupon
2. Check logs: `firebase functions:log --only onOrderCreated`
3. Verify coupon `usageCount` incremented

---

## 📊 How It Works

```
Order Created → Cloud Function Triggered → Coupon Usage Incremented
```

**Automatic:**
- ✅ Coupons transfer from order.html to order-success.html
- ✅ Coupons saved to Firestore orders collection
- ✅ Cloud Function auto-increments usageCount
- ✅ Processing results logged to order document

---

## 🔧 Manual Operations

### Increment Single Coupon
```bash
curl -X POST https://REGION-PROJECT.cloudfunctions.net/incrementCouponUsageHttp \
  -H "Content-Type: application/json" \
  -d '{"couponCode": "WELCOME10"}'
```

### Reprocess Order
```bash
curl -X POST https://REGION-PROJECT.cloudfunctions.net/reprocessOrderCouponsHttp \
  -H "Content-Type: application/json" \
  -d '{"orderId": "order_xxx"}'
```

### Rebuild All Counts
```bash
curl -X POST https://REGION-PROJECT.cloudfunctions.net/rebuildCouponUsageHttp \
  -H "Content-Type: application/json" \
  -d '{}'
```

---

## 🔍 Monitoring

### View Logs
```bash
# Real-time
firebase functions:log --only onOrderCreated --tail

# Last 50 entries
firebase functions:log --only onOrderCreated --limit 50
```

### Check Errors
```bash
# In Firestore console
# Collection: coupon_processing_errors
```

---

## 📈 Data Structure

### Order Document (orders collection)
```json
{
  "orderId": "order_xxx",
  "coupons": [
    {"code": "WELCOME10", "name": "Welcome", "type": "percentage", "value": 10}
  ],
  "couponUsageProcessed": true,
  "couponUsageResults": [
    {"success": true, "couponCode": "WELCOME10", "newCount": 43}
  ]
}
```

### Coupon Document (coupons collection)
```json
{
  "code": "WELCOME10",
  "usageCount": 43,
  "updatedAt": "2025-10-07T10:30:00Z"
}
```

---

## ✅ What Changed

### ✅ Added
- Cloud Function: `onOrderCreated` (auto-increment)
- HTTP Endpoint: `incrementCouponUsageHttp` (manual)
- HTTP Endpoint: `reprocessOrderCouponsHttp` (reprocess)
- Field: `couponUsageProcessed` in orders
- Field: `couponUsageResults` in orders

### ❌ Removed
- Cart clearing logic from `order-success.html`
- Cart clearing on successful payment

---

## 🐛 Troubleshooting

### Coupon Not Incrementing?

1. **Check if function deployed:**
   ```bash
   firebase functions:list
   ```

2. **Check logs:**
   ```bash
   firebase functions:log --only onOrderCreated
   ```

3. **Verify order has coupons:**
   - Check Firestore orders collection
   - Ensure `coupons` array exists

4. **Verify coupon exists:**
   - Check Firestore coupons collection
   - Code must match exactly

### Duplicate Increments?

- Cloud Function uses `onCreate` (runs once per order)
- Check for manual increments
- Review `couponUsageResults` in order

### Wrong Count?

- Run rebuild function to recalculate:
  ```bash
  curl -X POST .../rebuildCouponUsageHttp -d '{}'
  ```

---

## 📞 Support

- **Detailed Guide:** `COUPON_TRACKING_SYSTEM.md`
- **Deployment:** `COUPON_SYSTEM_DEPLOYMENT_CHECKLIST.md`
- **Logs:** `firebase functions:log`

---

**Last Updated:** October 7, 2025

