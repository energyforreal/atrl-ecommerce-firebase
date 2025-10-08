# 🔥 Firestore-Only System Documentation

## 📋 **Overview**

The ATTRAL eCommerce system has been completely migrated to use **Firestore as the primary and only database**. All local database dependencies have been removed, ensuring a scalable, cloud-native architecture.

## 🎯 **Key Changes Made**

### **✅ Removed Local Database Dependencies:**
- ❌ **SQLite database** (`orders.db`) - Completely removed
- ❌ **Local database tables** - No more `orders`, `affiliate_earnings`, etc.
- ❌ **Database initialization** - No more `initializeDatabase()` functions
- ❌ **PDO connections** - No more SQLite connections

### **✅ Firestore-Only Implementation:**
- ✅ **`firestore_order_manager.php`** - New Firestore-only order management
- ✅ **All data stored in Firestore** - Orders, commissions, status history
- ✅ **Real-time synchronization** - Automatic data sync across devices
- ✅ **Scalable architecture** - No database size limitations

## 🗄️ **Firestore Data Structure**

### **Collections:**

#### **1. Orders Collection (`orders`)**
```javascript
{
  orderId: "ATRL-0001",                    // Human-readable order number
  razorpayOrderId: "order_xyz123",         // Razorpay order ID
  razorpayPaymentId: "pay_abc456",         // Razorpay payment ID
  status: "confirmed",                     // Order status
  customer: {                              // Customer information
    firstName: "John",
    lastName: "Doe",
    email: "john@example.com",
    phone: "+1234567890"
  },
  product: {                               // Product details
    title: "ATTRAL 100W GaN Charger",
    price: 1500,
    items: [...]
  },
  pricing: {                               // Pricing breakdown
    subtotal: 1500,
    shipping: 0,
    discount: 0,
    total: 1500,
    currency: "INR"
  },
  shipping: {                              // Shipping address
    address: "123 Main St",
    city: "Mumbai",
    state: "Maharashtra",
    pincode: "400001",
    country: "India"
  },
  payment: {                               // Payment details
    provider: "razorpay",
    method: "card",
    url_params: {
      ref: "AFFILIATE123"                  // Affiliate code if present
    }
  },
  affiliate: {                             // Affiliate tracking (if applicable)
    code: "AFFILIATE123",
    trackedAt: "2024-01-15T10:30:00Z"
  },
  createdAt: "2024-01-15T10:30:00Z",      // Timestamp
  updatedAt: "2024-01-15T10:30:00Z"       // Last updated
}
```

#### **2. Affiliate Commissions Collection (`affiliate_commissions`)**
```javascript
{
  affiliateId: "affiliate_doc_id",        // Firestore document ID
  affiliateEmail: "affiliate@example.com",
  affiliateName: "John Affiliate",
  orderId: "ATRL-0001",
  orderNumber: "ATRL-0001",
  commissionAmount: 150.00,               // 10% of order total
  commissionRate: 10.0,
  status: "pending",                      // pending, paid, cancelled
  createdAt: "2024-01-15T10:30:00Z",
  paidAt: null                            // Set when commission is paid
}
```

#### **3. Order Status History Collection (`order_status_history`)**
```javascript
{
  orderId: "order_doc_id",                // Firestore document ID
  status: "confirmed",
  message: "Order created and payment verified",
  createdAt: "2024-01-15T10:30:00Z"
}
```

#### **4. Affiliates Collection (`affiliates`)**
```javascript
{
  email: "affiliate@example.com",
  displayName: "John Affiliate",
  name: "John Affiliate",
  code: "AFFILIATE123",
  status: "active",
  totalEarnings: 1500.00,
  totalReferrals: 10,
  createdAt: "2024-01-01T00:00:00Z"
}
```

## 🔧 **API Endpoints**

### **Firestore Order Manager (`/api/firestore_order_manager.php`)**

#### **Create Order:**
```http
POST /api/firestore_order_manager.php/create
Content-Type: application/json

{
  "order_id": "order_xyz123",
  "payment_id": "pay_abc456",
  "customer": { ... },
  "product": { ... },
  "pricing": { ... },
  "shipping": { ... },
  "payment": { ... },
  "affiliate_code": "AFFILIATE123"  // Optional
}
```

#### **Get Order Status:**
```http
GET /api/firestore_order_manager.php/status?order_id=ATRL-0001
```

#### **Update Order Status:**
```http
POST /api/firestore_order_manager.php/update
Content-Type: application/json

{
  "orderId": "order_doc_id",
  "status": "shipped",
  "message": "Order shipped via courier"
}
```

## 🚀 **System Flow**

### **1. Order Processing:**
```
Payment Success (Razorpay)
    ↓
Webhook Handler (webhook.php)
    ↓
Save to Firestore (orders collection)
    ↓
Process via Firestore Order Manager
    ↓
Generate Order Number (ATRL-0001)
    ↓
Check for Affiliate Code
    ↓
Calculate Commission (10%)
    ↓
Create Commission Record (affiliate_commissions)
    ↓
Send Commission Email to Affiliate
    ↓
Order Success Page
    ↓
Send Order Confirmation + Invoice to Customer
```

### **2. Data Flow:**
```
All Data Operations
    ↓
Firestore Database
    ↓
Real-time Sync
    ↓
Admin Dashboard
    ↓
Affiliate Dashboard
    ↓
Customer Dashboard
```

## 📊 **Benefits of Firestore-Only Architecture**

### **✅ Scalability:**
- **No database size limits** - Firestore scales automatically
- **Global distribution** - Data replicated across regions
- **Automatic scaling** - Handles traffic spikes seamlessly

### **✅ Real-time Features:**
- **Live updates** - Dashboard updates in real-time
- **Offline support** - Works without internet connection
- **Conflict resolution** - Automatic data synchronization

### **✅ Security:**
- **Firestore security rules** - Granular access control
- **Authentication integration** - Built-in user management
- **Data encryption** - All data encrypted in transit and at rest

### **✅ Performance:**
- **Fast queries** - Optimized for real-time applications
- **Caching** - Automatic data caching
- **CDN integration** - Global content delivery

## 🔍 **Migration Details**

### **Files Updated:**
1. **`firestore_order_manager.php`** - New Firestore-only order management
2. **`webhook.php`** - Updated to use Firestore order manager
3. **`order-success.html`** - Updated to use Firestore endpoints
4. **`send_order_email.php`** - Already using Firestore
5. **`generate_invoice.php`** - Updated Firestore queries

### **Files Removed:**
- ❌ **Local database dependencies** - All SQLite operations removed
- ❌ **Database initialization** - No more table creation
- ❌ **PDO connections** - No more database connections

## 🧪 **Testing**

### **Test Firestore Connection:**
```bash
php -r "
require_once 'firestore_order_manager.php';
try {
    \$manager = new FirestoreOrderManager();
    echo '✅ Firestore connection successful';
} catch (Exception \$e) {
    echo '❌ Firestore connection failed: ' . \$e->getMessage();
}
"
```

### **Test Order Creation:**
```bash
curl -X POST https://attral.in/api/firestore_order_manager.php/create \
  -H "Content-Type: application/json" \
  -d '{
    "order_id": "test_order_123",
    "payment_id": "test_payment_456",
    "customer": {"firstName": "Test", "lastName": "User", "email": "test@example.com"},
    "product": {"title": "Test Product", "price": 1000},
    "pricing": {"total": 1000, "currency": "INR"},
    "shipping": {"address": "Test Address"},
    "payment": {"provider": "razorpay"}
  }'
```

## 📈 **Monitoring**

### **Firestore Console:**
- **Real-time data** - View all collections and documents
- **Query performance** - Monitor query execution times
- **Usage metrics** - Track read/write operations
- **Security rules** - Manage access permissions

### **Logs:**
```
FIRESTORE ORDER: Order created successfully - ID: doc_id, Order Number: ATRL-0001
FIRESTORE AFFILIATE: Commission processed - ₹150.00 for affiliate email@example.com
FIRESTORE COMMISSION: Commission record created - Amount: ₹150.00
```

## 🎯 **Next Steps**

### **Immediate Actions:**
1. **Deploy Firestore-only system** - Replace old order manager
2. **Update admin dashboard** - Ensure Firestore integration
3. **Test affiliate system** - Verify commission tracking
4. **Monitor performance** - Check Firestore usage

### **Future Enhancements:**
1. **Real-time notifications** - Live order updates
2. **Advanced analytics** - Firestore-based reporting
3. **Mobile app integration** - Real-time sync
4. **Multi-region deployment** - Global performance

---

## 📞 **Support**

The system is now **100% Firestore-based** with no local database dependencies. All data operations go through Firestore, ensuring scalability, reliability, and real-time capabilities.

**Last Updated:** January 2024  
**Version:** 2.0.0 (Firestore-Only)
