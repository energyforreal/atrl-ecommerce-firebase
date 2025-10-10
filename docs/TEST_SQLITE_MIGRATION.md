# 🧪 Testing Guide: SQLite Migration

**Purpose:** Test the new SQLite-primary order management system

---

## 🚀 Quick Test

### **1. Check Database File**
```bash
# Navigate to API directory
cd static-site/api

# Check if database exists
ls -la orders.db

# If exists, check contents
sqlite3 orders.db "SELECT COUNT(*) as total FROM orders;"
```

### **2. Test Payment Flow**

1. **Open your eCommerce site**
   ```
   http://localhost:8000/shop.html
   ```

2. **Add product to cart and checkout**

3. **Complete test payment**
   - Use Razorpay test card: `4111 1111 1111 1111`
   - Any future CVV and expiry

4. **Check order created**
   ```bash
   sqlite3 static-site/api/orders.db "SELECT order_number, status, created_at FROM orders ORDER BY created_at DESC LIMIT 1;"
   ```

---

## 🔍 Detailed Verification

### **Step 1: Verify Webhook Endpoint**

```bash
# Check webhook configuration
curl -X GET https://attral.in/api/webhook.php
```

Expected response:
```json
{
  "error": "Missing signature"
}
```
*(This is good - it means webhook is active and expecting Razorpay signature)*

---

### **Step 2: Verify Order Manager Endpoints**

#### **A. Create Order (should fail without data):**
```bash
curl -X POST https://attral.in/api/order_manager.php/create \
  -H "Content-Type: application/json" \
  -d '{}'
```

Expected:
```json
{
  "error": "Invalid JSON input"
}
```

#### **B. List Orders:**
```bash
curl -X GET https://attral.in/api/order_manager.php/list?limit=5
```

Expected:
```json
{
  "success": true,
  "orders": [...]
}
```

#### **C. Check Database:**
```bash
curl -X GET https://attral.in/api/check-database.php
```

---

### **Step 3: Test Full Payment Flow**

#### **Create Test Order via Order Page:**

1. Open: `http://localhost:8000/order.html?productId=1`
2. Fill in customer information
3. Click "Pay with Razorpay"
4. Use test card details
5. Complete payment

#### **Verify Order Created:**

```bash
# Check SQLite database
sqlite3 static-site/api/orders.db "
  SELECT 
    order_number,
    razorpay_payment_id,
    status,
    json_extract(customer_data, '$.email') as email,
    json_extract(pricing_data, '$.total') as total,
    json_extract(notes, '$.coupons') as coupons,
    created_at
  FROM orders 
  ORDER BY created_at DESC 
  LIMIT 1;
"
```

Expected output:
```
ATRL-0001|pay_xxxxx|confirmed|user@example.com|2999|[...]|2025-10-09 12:34:56
```

---

### **Step 4: Test Coupon Processing**

#### **Apply Coupon During Checkout:**

1. Add product to cart
2. On order page, enter coupon: `WELCOME10`
3. Click "Apply"
4. Complete payment

#### **Verify Coupon Tracked:**

```bash
# Check order has coupon in notes
sqlite3 static-site/api/orders.db "
  SELECT 
    order_number,
    json_extract(notes, '$.coupons') as coupons
  FROM orders 
  WHERE json_extract(notes, '$.coupons') IS NOT NULL
  ORDER BY created_at DESC 
  LIMIT 1;
"
```

Expected: Coupon code appears in notes field

---

### **Step 5: Test Affiliate Commission**

#### **Order with Affiliate Link:**

1. Visit: `http://localhost:8000/shop.html?ref=AFFILIATE001`
2. Add product to cart
3. Complete purchase

#### **Verify Commission Created:**

Check server logs for:
```
AFFILIATE: Commission processed - ₹299.90 for affiliate user@example.com on order ATRL-0001
```

Or check Firestore Console → `affiliate_commissions` collection

---

### **Step 6: Test Idempotent Behavior**

#### **Simulate Duplicate Order Creation:**

```bash
# Get existing order details from database
sqlite3 static-site/api/orders.db "SELECT razorpay_order_id, razorpay_payment_id FROM orders ORDER BY created_at DESC LIMIT 1;"

# Try to create order again with same payment_id
curl -X POST https://attral.in/api/order_manager.php/create \
  -H "Content-Type: application/json" \
  -H "X-Webhook-Source: razorpay" \
  -d '{
    "order_id": "order_xxxxx",
    "payment_id": "pay_xxxxx",
    "customer": {"firstName": "Test", "lastName": "User", "email": "test@example.com", "phone": "1234567890"},
    "product": {"id": "1", "title": "Test Product", "price": 100},
    "pricing": {"total": 100, "currency": "INR"},
    "shipping": {"address": "Test", "city": "Test", "state": "Test", "pincode": "123456", "country": "India"},
    "payment": {"method": "razorpay", "transaction_id": "pay_xxxxx"}
  }'
```

Expected response:
```json
{
  "success": true,
  "message": "Order already exists (idempotent)",
  "orderNumber": "ATRL-0001"
}
```

---

### **Step 7: Test Email Sending**

After order creation, check:

1. **Customer receives email** at registered address
2. **Email contains** correct order details
3. **Invoice attached** (HTML format)

Check server logs for:
```
✅ Order confirmation email sent successfully
✅ Invoice email sent successfully
```

---

## 📊 Database Inspection Commands

### **View All Orders:**
```bash
sqlite3 static-site/api/orders.db "
  SELECT 
    order_number,
    status,
    json_extract(customer_data, '$.email') as email,
    json_extract(pricing_data, '$.total') as total,
    created_at
  FROM orders 
  ORDER BY created_at DESC;
"
```

### **View Order Details:**
```bash
sqlite3 static-site/api/orders.db "
  SELECT 
    order_number,
    razorpay_order_id,
    razorpay_payment_id,
    customer_data,
    product_data,
    pricing_data,
    notes
  FROM orders 
  WHERE order_number = 'ATRL-0001';
"
```

### **Check Order Status History:**
```bash
sqlite3 static-site/api/orders.db "
  SELECT 
    o.order_number,
    h.status,
    h.message,
    h.created_at
  FROM order_status_history h
  JOIN orders o ON h.order_id = o.id
  ORDER BY h.created_at DESC;
"
```

### **Export All Orders to JSON:**
```bash
sqlite3 static-site/api/orders.db <<EOF
.mode json
SELECT * FROM orders;
EOF
```

---

## 🛠️ Troubleshooting

### **Issue: Orders not appearing in database**

**Check:**
```bash
# 1. Database file permissions
ls -la static-site/api/orders.db

# 2. Web server has write permission
chmod 664 static-site/api/orders.db
chmod 775 static-site/api/

# 3. Check server error logs
tail -f /var/log/apache2/error.log
# OR
tail -f /var/log/nginx/error.log
```

### **Issue: "Order already exists" error**

**Solution:** This is actually correct behavior (idempotent)!

Check response:
- If `success: true` and `message: "Order already exists"` → ✅ Working correctly
- If `success: false` → 🔴 Bug in code

### **Issue: Coupons not being tracked**

**Check:**
1. Firestore service account exists: `static-site/api/firebase-service-account.json`
2. Coupon tracking service exists: `static-site/api/coupon_tracking_service.php`
3. Check server logs for "COUPON PROCESSING:" entries

### **Issue: Firestore backup not working**

**This is OK!** Firestore is now optional. As long as orders are in SQLite, your system works.

To enable Firestore backup:
1. Ensure `firebase-service-account.json` exists
2. Run `composer install` in `static-site/api/`
3. Check logs for "FIRESTORE SUCCESS:" messages

---

## ✅ Success Indicators

Your migration is successful if:

- ✅ Orders appear in `orders.db` after payment
- ✅ Order success page shows correct details
- ✅ Customers receive confirmation emails
- ✅ Coupons are tracked (check Firestore)
- ✅ Affiliates get commission emails
- ✅ No duplicate orders created
- ✅ Idempotent check works (returns existing order)

---

## 📞 Quick Diagnostics

**Run this single command to check everything:**

```bash
cd static-site/api
echo "=== Database Status ==="
sqlite3 orders.db "SELECT COUNT(*) as total_orders FROM orders;"
echo ""
echo "=== Recent Orders ==="
sqlite3 orders.db "SELECT order_number, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5;"
echo ""
echo "=== Files Check ==="
ls -la orders.db config.php webhook.php order_manager.php
echo ""
echo "=== Composer Status ==="
composer show | grep -E "google|firebase" || echo "No Firestore packages (OK - optional)"
```

---

## 🎯 Summary

Your system now uses **SQLite as primary** with **Firestore as optional backup**. This makes it:
- ✅ Simpler to deploy
- ✅ Cheaper to run
- ✅ Easier to debug
- ✅ More resilient (no cloud dependency)

**Next:** Test a payment and verify everything works! 🚀


