# 🤝 Affiliate Email System Documentation

## 📋 **Overview**

The ATTRAL Affiliate Email System provides comprehensive email automation for affiliate partners, including commission notifications, welcome emails, payout alerts, and milestone celebrations.

## 🎯 **Features**

### **✅ Email Types:**
1. **Welcome Email** - Sent when affiliate joins the program
2. **Commission Email** - Sent when affiliate earns commission from referral
3. **Payout Email** - Sent when affiliate earnings are ready for payout
4. **Milestone Email** - Sent when affiliate reaches achievement milestones

### **✅ Automatic Triggers:**
- **Order Processing** - Automatically detects affiliate codes in orders
- **Commission Calculation** - 10% commission on all referred orders
- **Database Tracking** - All commissions stored in `affiliate_earnings` table
- **Email Delivery** - Instant notifications via Brevo

## 🔧 **Technical Implementation**

### **Files Structure:**
```
static-site/api/
├── affiliate_email.php          # Main affiliate email API
├── brevo_email_service.php      # Email templates & sending
├── order_manager.php            # Commission processing logic
└── test_affiliate_emails.php    # Test scripts
```

### **Database Schema:**
```sql
CREATE TABLE affiliate_earnings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    order_id INTEGER NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    commission_rate DECIMAL(5,2) DEFAULT 10.00,
    status VARCHAR(20) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME
);
```

## 🚀 **How It Works**

### **1. Order Processing Flow:**
```
Customer Places Order
    ↓
Order Manager Processes Order
    ↓
Extract Affiliate Code (if present)
    ↓
Lookup Affiliate in Firestore
    ↓
Calculate Commission (10% of order total)
    ↓
Create Commission Record in Database
    ↓
Send Commission Email to Affiliate
```

### **2. Affiliate Code Detection:**
The system checks for affiliate codes in multiple locations:
- `orderData['payment']['url_params']['ref']`
- `orderData['customer']['affiliate_code']`
- `orderData['notes']['affiliate_code']`

### **3. Commission Calculation:**
```php
$orderTotal = $orderData['pricing']['total'] ?? 0;
$commissionAmount = $orderTotal * 0.10; // 10% commission
```

## 📧 **Email Templates**

### **Welcome Email:**
- **Subject:** "Welcome to ATTRAL Affiliate Program! 🎉"
- **Content:** Welcome message with affiliate code and dashboard link
- **Trigger:** When affiliate joins the program

### **Commission Email:**
- **Subject:** "You earned ₹{commission}! 💰"
- **Content:** Commission details, order ID, and dashboard link
- **Trigger:** When affiliate earns commission from referral

### **Payout Email:**
- **Subject:** "💰 Your Payout of ₹{amount} is Ready!"
- **Content:** Payout details, date, and transfer status
- **Trigger:** When affiliate earnings are ready for payout

### **Milestone Email:**
- **Subject:** "🎉 Milestone Achieved: {milestone}!"
- **Content:** Achievement details and special rewards
- **Trigger:** When affiliate reaches milestones

## 🔌 **API Endpoints**

### **Affiliate Email API (`/api/affiliate_email.php`)**

#### **Welcome Email:**
```json
POST /api/affiliate_email.php
{
    "action": "welcome",
    "email": "affiliate@example.com",
    "name": "John Doe",
    "affiliateCode": "JOHN123"
}
```

#### **Commission Email:**
```json
POST /api/affiliate_email.php
{
    "action": "commission",
    "email": "affiliate@example.com",
    "name": "John Doe",
    "commission": 150.00,
    "orderId": "ATRL-0001"
}
```

#### **Payout Email:**
```json
POST /api/affiliate_email.php
{
    "action": "payout",
    "email": "affiliate@example.com",
    "name": "John Doe",
    "amount": 1500.00,
    "payoutDate": "2024-01-15"
}
```

#### **Milestone Email:**
```json
POST /api/affiliate_email.php
{
    "action": "milestone",
    "email": "affiliate@example.com",
    "name": "John Doe",
    "milestone": "First 10 Sales",
    "achievement": "You've successfully referred 10 customers!"
}
```

## 🧪 **Testing**

### **Run Test Script:**
```bash
php test_affiliate_emails.php
```

### **Test Coverage:**
- ✅ Welcome email sending
- ✅ Commission email sending
- ✅ Payout email sending
- ✅ Milestone email sending
- ✅ Commission processing logic
- ✅ Affiliate code extraction

## 📊 **Commission Tracking**

### **Commission Rate:**
- **Standard Rate:** 10% of order total
- **Minimum Payout:** ₹1,000
- **Payout Schedule:** Monthly

### **Commission Status:**
- **Pending** - Commission earned, awaiting payout
- **Paid** - Commission paid to affiliate
- **Cancelled** - Commission cancelled (refund, etc.)

## 🔍 **Monitoring & Logs**

### **Log Messages:**
```
AFFILIATE: Commission processed - ₹150.00 for affiliate john@example.com on order ATRL-0001
AFFILIATE EMAIL: Commission notification sent to john@example.com
AFFILIATE: Commission record created - ID: 123, Amount: ₹150.00
```

### **Error Handling:**
- Invalid affiliate codes are logged and skipped
- Email sending failures are logged but don't stop order processing
- Database errors are logged with full error details

## 🎯 **Integration Points**

### **Order Success Flow:**
1. Customer completes payment
2. Order processed by `order_manager.php`
3. Affiliate commission automatically calculated and processed
4. Commission email sent to affiliate
5. Order confirmation and invoice sent to customer

### **Firestore Integration:**
- Affiliate data stored in `affiliates` collection
- Order data includes affiliate tracking
- Commission records stored in local database

## 🚀 **Deployment**

### **Requirements:**
- PHP 7.4+
- Brevo API key configured
- Firebase service account file
- Database with `affiliate_earnings` table

### **Configuration:**
- Update Brevo API key in `brevo_email_service.php`
- Ensure Firebase service account file is present
- Test email sending with test script

## 📈 **Performance**

### **Optimizations:**
- Commission processing runs asynchronously
- Email sending doesn't block order processing
- Database queries are optimized
- Error handling prevents system failures

### **Scalability:**
- System can handle high order volumes
- Email sending is rate-limited by Brevo
- Database operations are efficient

## 🎉 **Success Metrics**

### **Key Indicators:**
- Commission emails sent successfully
- Affiliate engagement rates
- Payout processing times
- System uptime and reliability

---

## 📞 **Support**

For technical support or questions about the affiliate email system, contact the development team or check the logs for detailed error information.

**Last Updated:** January 2024  
**Version:** 1.0.0
