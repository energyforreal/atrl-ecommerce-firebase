# 🧪 Firestore HTML Test Tools Guide

## 📋 Overview

I've created **2 comprehensive HTML test tools** to test your Firestore integration directly in the browser!

---

## 🎯 Test Tools Created

### **1. test-firestore-rest-api.html** - REST API Testing
- **Purpose:** Test the REST API fallback endpoint
- **Best for:** Quick testing, lightweight checks
- **Features:**
  - Simple form for test orders
  - Custom JSON input
  - Read orders by ID
  - Clean, focused interface

### **2. test-firestore-comprehensive.html** - Full Testing Suite ⭐
- **Purpose:** Complete testing for both SDK and REST API
- **Best for:** Production testing, performance analysis
- **Features:**
  - Switch between PHP SDK and REST API
  - Quick test order creation
  - Read/search orders
  - Batch write testing
  - Stress testing
  - Idempotency testing
  - Statistics tracking
  - Connection testing

---

## 🚀 Quick Start

### **Step 1: Choose Your Test Tool**

**For simple testing:**
```
Use: test-firestore-rest-api.html
```

**For comprehensive testing:**
```
Use: test-firestore-comprehensive.html (Recommended ⭐)
```

### **Step 2: Upload Files**

Upload these files to your web server:

```
your-website/
├── test-firestore-rest-api.html
├── test-firestore-comprehensive.html
└── api/
    ├── firestore_order_manager.php
    ├── firestore_rest_api_fallback.php
    └── firebase-service-account.json
```

### **Step 3: Access in Browser**

**Local testing:**
```
http://localhost:8000/test-firestore-comprehensive.html
```

**On Hostinger:**
```
https://yourdomain.com/test-firestore-comprehensive.html
```

---

## 🎨 Test Tool #1: REST API Tester

### **File:** `test-firestore-rest-api.html`

### **Features:**

#### **✍️ Write Test Orders**
- **Simple Tab:** Fill a form with basic order details
- **Custom JSON Tab:** Paste complete order JSON

#### **📖 Read Orders**
- Enter Order ID to search
- View order details
- See full JSON response

### **How to Use:**

1. **Write a Test Order:**
   ```
   1. Fill in customer name, email, phone
   2. Select product and amount
   3. Enter shipping address
   4. Click "Write Order to Firestore"
   ```

2. **Read an Order:**
   ```
   1. Enter order ID in the search box
   2. Click "Read Orders from Firestore"
   3. View the order details
   ```

3. **Verify in Firebase:**
   ```
   1. Go to console.firebase.google.com
   2. Select: e-commerce-1d40f
   3. Open: Firestore Database → orders
   4. Find your test order
   ```

### **Screenshot Preview:**
```
┌─────────────────────────────────────┐
│  🔥 Firestore REST API Test Tool   │
│  Project: e-commerce-1d40f          │
├─────────────────┬───────────────────┤
│ ✍️ Write Test   │  📖 Read Orders   │
│                 │                   │
│ [Form Fields]   │  [Search Box]     │
│                 │                   │
│ [Write Button]  │  [Read Button]    │
│                 │                   │
│ [Results Box]   │  [Results Box]    │
└─────────────────┴───────────────────┘
```

---

## 🎨 Test Tool #2: Comprehensive Tester ⭐

### **File:** `test-firestore-comprehensive.html`

### **Features:**

#### **🔄 API Selector**
- Switch between PHP SDK and REST API
- Real-time endpoint display
- Compare performance

#### **📊 Statistics Dashboard**
- Total tests run
- Successful tests
- Error count
- Live tracking

#### **✍️ Write Test Section**
- Quick order form
- Auto-generated test data
- Full order structure
- Instant validation

#### **📖 Read Test Section**
- Search by Order ID
- Search by Order Number
- Connection testing
- Detailed order display

#### **🔬 Advanced Testing**
- **Batch Write:** Create multiple orders at once
- **Stress Test:** Test with concurrent requests
- **Idempotency Test:** Verify duplicate prevention

### **How to Use:**

#### **Basic Testing:**

1. **Select API Type:**
   ```
   Click: PHP SDK or REST API button
   (Shows which endpoint will be used)
   ```

2. **Create Test Order:**
   ```
   1. Fill form (pre-filled with test data)
   2. Click "Write to Firestore"
   3. See success message with document ID
   ```

3. **Search Order:**
   ```
   1. Enter order ID in search box
   2. Click "Search Order"
   3. View complete order details
   ```

4. **Test Connection:**
   ```
   Click "Test Connection" to verify API is working
   ```

#### **Advanced Testing:**

5. **Batch Write Test:**
   ```
   1. Set number of orders (e.g., 5)
   2. Click "Create Batch Orders"
   3. Wait for completion
   4. See batch statistics
   ```

6. **Stress Test:**
   ```
   1. Set number of requests (e.g., 10)
   2. Click "Run Stress Test"
   3. See performance metrics
   4. Check requests/second
   ```

7. **Idempotency Test:**
   ```
   1. Click "Test Idempotency"
   2. Creates same order twice
   3. Verifies no duplicate created
   4. See pass/fail result
   ```

### **Screenshot Preview:**
```
┌─────────────────────────────────────────────────────┐
│     🔥 Firestore Comprehensive API Test Tool       │
│  [🚀 PHP SDK] [🔄 REST API]  ← API Selector        │
│                                                     │
│  📊 Stats: Total: 5 | Success: 4 | Errors: 1       │
├──────────────────────────┬──────────────────────────┤
│  ✍️ Write Test Order     │  📖 Read Orders          │
│                          │                          │
│  Name: [John Doe____]    │  Search: [___________]   │
│  Email: [test@attral.in] │                          │
│  Amount: [2999______]    │  [🔎 Search Order]       │
│                          │  [🔌 Test Connection]    │
│  [🚀 Write to Firestore] │                          │
│                          │                          │
│  Results:                │  Results:                │
│  ✅ Success!             │  [Order Details...]      │
├──────────────────────────┴──────────────────────────┤
│  🔬 Advanced Testing                                │
│  [⚡ Batch Test] [🧪 Stress Test] [🔄 Idempotency] │
│                                                     │
│  Results: [Statistics and performance metrics...]  │
└─────────────────────────────────────────────────────┘
```

---

## 📊 Comparison: Which Tool to Use?

| Feature | REST API Tester | Comprehensive Tester ⭐ |
|---------|----------------|------------------------|
| **Write Orders** | ✅ Simple form | ✅ Quick form |
| **Read Orders** | ✅ Basic search | ✅ Advanced search |
| **API Selection** | ❌ REST only | ✅ SDK + REST |
| **Statistics** | ❌ None | ✅ Live tracking |
| **Batch Testing** | ❌ No | ✅ Yes |
| **Stress Testing** | ❌ No | ✅ Yes |
| **Idempotency Test** | ❌ No | ✅ Yes |
| **Connection Test** | ❌ No | ✅ Yes |
| **UI Complexity** | 🟢 Simple | 🟡 Advanced |
| **Best For** | Quick checks | Production testing |

**Recommendation:** Use **test-firestore-comprehensive.html** for complete testing! ⭐

---

## 🎯 What Each Test Does

### **Write Order Test:**
```
1. Creates order data structure
2. Sends POST request to API
3. Receives document ID
4. Displays success/error
5. Shows full response JSON
```

**Tests:**
- ✅ API endpoint accessibility
- ✅ Data serialization
- ✅ Firestore write permissions
- ✅ Service account authentication
- ✅ Order number generation
- ✅ Response handling

### **Read Order Test:**
```
1. Takes order ID input
2. Sends GET request to API
3. Retrieves order document
4. Parses and displays data
5. Shows formatted details
```

**Tests:**
- ✅ Query functionality
- ✅ Document retrieval
- ✅ Data deserialization
- ✅ Error handling

### **Batch Write Test:**
```
1. Creates N orders sequentially
2. Tracks success/failure
3. Measures total time
4. Calculates average time per order
5. Shows completion statistics
```

**Tests:**
- ✅ API stability
- ✅ Sequential performance
- ✅ Error rate under load

### **Stress Test:**
```
1. Creates N orders concurrently
2. Sends all requests simultaneously
3. Waits for all to complete
4. Measures total duration
5. Calculates requests/second
```

**Tests:**
- ✅ Concurrent request handling
- ✅ API performance under load
- ✅ Database write speed
- ✅ Connection pooling

### **Idempotency Test:**
```
1. Creates one order
2. Sends same order again
3. Checks if duplicate created
4. Verifies both return same document
5. Pass/fail determination
```

**Tests:**
- ✅ Duplicate prevention
- ✅ Payment ID uniqueness check
- ✅ Idempotent behavior
- ✅ Data consistency

---

## ✅ Expected Results

### **Successful Write:**
```json
{
  "success": true,
  "orderId": "abc123xyz789",
  "orderNumber": "ATRL-0001",
  "message": "Order created successfully",
  "api_source": "firestore_order_manager",
  "timestamp": "2025-10-10T12:00:00Z"
}
```

### **Successful Read:**
```json
{
  "success": true,
  "order": {
    "orderId": "ATRL-0001",
    "status": "confirmed",
    "customer": {
      "firstName": "John",
      "lastName": "Doe",
      "email": "john@example.com"
    },
    "amount": 2999,
    "currency": "INR"
  }
}
```

### **Error Response:**
```json
{
  "success": false,
  "error": "Service account file not found",
  "api_source": "firestore_order_manager"
}
```

---

## 🐛 Troubleshooting

### **Error: "Failed to fetch"**
**Cause:** API endpoint not accessible

**Solutions:**
1. Check if server is running
2. Verify file upload (firestore_rest_api_fallback.php)
3. Check browser console for CORS errors
4. Try accessing API directly: `/api/firestore_rest_api_fallback.php`

### **Error: "Service account file not found"**
**Cause:** Missing firebase-service-account.json

**Solutions:**
1. Upload service account to: `/api/firebase-service-account.json`
2. Verify file permissions (644)
3. Check file path in PHP code

### **Error: "Firestore SDK not available"**
**Cause:** Missing Composer dependencies

**Solutions:**
1. Run: `cd api && composer install`
2. Upload `/vendor/` folder to server
3. Try REST API option instead

### **Error: "Order not found"**
**Cause:** Invalid order ID or order doesn't exist

**Solutions:**
1. Check order ID spelling
2. Verify order was created successfully
3. Check Firebase Console for actual order IDs
4. Try creating a new order first

---

## 📈 Performance Benchmarks

### **Expected Performance:**

| Test Type | Expected Time | Good Result |
|-----------|---------------|-------------|
| **Single Write** | < 1 second | ✅ 0.3-0.8s |
| **Single Read** | < 1 second | ✅ 0.2-0.5s |
| **Batch (5 orders)** | < 5 seconds | ✅ 2-4s |
| **Stress (10 concurrent)** | < 3 seconds | ✅ 1-2s |
| **Idempotency** | < 2 seconds | ✅ 1-1.5s |

### **Performance Tips:**

1. **PHP SDK is faster** than REST API (uses connection pooling)
2. **Batch writes** are slower (sequential) but more reliable
3. **Stress tests** show peak performance (concurrent)
4. **First request** may be slower (cold start)

---

## 🎯 Testing Checklist

### **Before Deployment:**

- [ ] Run connection test
- [ ] Create 1 test order (write test)
- [ ] Search for test order (read test)
- [ ] Verify in Firebase Console
- [ ] Run batch test (5 orders)
- [ ] Run stress test (10 requests)
- [ ] Run idempotency test
- [ ] Check all passed
- [ ] Delete test orders

### **On Hostinger:**

- [ ] Upload both HTML files
- [ ] Upload API files
- [ ] Upload service account
- [ ] Test connection
- [ ] Run compatibility test first
- [ ] Try both SDK and REST API
- [ ] Compare performance
- [ ] Choose best option
- [ ] Clean up test data

---

## 🔐 Security Notes

### **⚠️ Important:**

1. **Delete test files** after deployment
   ```bash
   # Remove these from production:
   - test-firestore-rest-api.html
   - test-firestore-comprehensive.html
   ```

2. **Protect API endpoints** with authentication
   ```php
   // Add to PHP files for production:
   if (!isset($_SESSION['admin'])) {
       die('Unauthorized');
   }
   ```

3. **Service account security**
   ```
   - Keep firebase-service-account.json secure
   - Use .htaccess to block direct access
   - Never commit to git
   ```

4. **Test data cleanup**
   ```
   - Delete all test orders from Firestore
   - Use testOrder: true flag to identify them
   - Run cleanup script periodically
   ```

---

## 📚 Additional Resources

### **Related Files:**
- `firestore_order_manager.php` - Main PHP SDK API
- `firestore_rest_api_fallback.php` - REST API fallback
- `test-firestore-write-dummy.php` - CLI testing tool
- `test-hostinger-compatibility.php` - Server compatibility check

### **Documentation:**
- `FIRESTORE_DATA_WRITING_GUIDE.md` - Complete writing guide
- `FIREBASE_DOCS_VS_YOUR_CODE.md` - Implementation comparison
- `HOSTINGER_FIRESTORE_DEPLOYMENT_GUIDE.md` - Deployment guide

### **Firebase Console:**
- **URL:** https://console.firebase.google.com
- **Project:** e-commerce-1d40f
- **Database:** Firestore Database → orders

---

## 🎉 Quick Start Commands

### **Local Testing:**
```bash
# Start local server
php -S localhost:8000 -t .

# Access test tool
http://localhost:8000/test-firestore-comprehensive.html
```

### **On Hostinger:**
```bash
# Upload files via FTP or File Manager
# Then access:
https://yourdomain.com/test-firestore-comprehensive.html
```

---

## ✅ Summary

**You now have:**
- ✅ **2 HTML test tools** (simple + comprehensive)
- ✅ **Browser-based testing** (no CLI needed)
- ✅ **Full CRUD testing** (create, read)
- ✅ **Performance testing** (batch, stress)
- ✅ **Reliability testing** (idempotency)
- ✅ **API comparison** (SDK vs REST)
- ✅ **Live statistics** (success/error tracking)
- ✅ **Beautiful UI** (professional design)

**Just upload and test!** 🚀

---

**🎯 Recommended Testing Flow:**

1. Upload files
2. Open: `test-firestore-comprehensive.html`
3. Click "Test Connection"
4. Create test order
5. Search for order
6. Run batch test
7. Run stress test
8. Verify in Firebase Console
9. Delete test data
10. Deploy to production!

**You're ready to test!** 🎊

