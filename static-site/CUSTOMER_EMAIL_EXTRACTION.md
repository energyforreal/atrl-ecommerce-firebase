# 📧 Customer Email Extraction from Firestore

## 🎯 **Overview**

The Cloud Function correctly extracts the customer email from Firestore order documents. The email is **NOT hardcoded** - it's dynamically retrieved from the order data.

## 🔍 **How Customer Email is Extracted**

### **1. Cloud Function Logic**
```javascript
// Line 32 in fulfillment-status-trigger.js
const customerEmail = extractCustomerEmail(after);
if (!customerEmail) {
  console.warn(`No customer email found for order ${orderId}`);
  return null; // Skip if no email found
}
```

### **2. Email Extraction Function**
```javascript
function extractCustomerEmail(orderData) {
  // Try different possible locations for customer email
  if (orderData.email) return orderData.email;
  if (orderData.customer && orderData.customer.email) return orderData.customer.email;  // ✅ PRIMARY
  if (orderData.user && orderData.user.email) return orderData.user.email;
  if (orderData.shipping && orderData.shipping.email) return orderData.shipping.email;
  if (orderData.notes && orderData.notes.email) return orderData.notes.email;
  
  return null; // No email found
}
```

## 📊 **Firestore Order Document Structure**

Based on your webhook and order manager code, customer email is stored in:

```javascript
// Primary location (from webhook.php line 118)
{
  "customer": {
    "firstName": "Lokesh",
    "lastName": "Customer", 
    "email": "customer@example.com",  // ✅ THIS IS EXTRACTED
    "phone": "+91 9876543210"
  },
  "fulfillmentStatus": "ready-to-dispatch",  // ✅ TRIGGER FIELD
  // ... other order data
}
```

## 🔄 **Complete Flow**

### **1. Order Creation (webhook.php)**
```php
// Line 118: Customer email stored in Firestore
$orderData['customer'] = [
    'firstName' => $customerFirstName,
    'lastName' => $customerLastName,
    'email' => $customerEmail,  // ✅ Customer's actual email
    'phone' => $customerPhone
];
```

### **2. Fulfillment Status Update**
```javascript
// Admin updates fulfillmentStatus in Firebase Console
// Cloud Function triggers automatically
```

### **3. Email Extraction**
```javascript
// Cloud Function extracts customer email
const customerEmail = extractCustomerEmail(after);
// Returns: "customer@example.com" (actual customer email)
```

### **4. Email Sending**
```javascript
// Email sent to ACTUAL customer
const emailData = {
  orderId: orderId,
  customerEmail: customerEmail,  // ✅ Real customer email
  customerName: extractCustomerName(after),
  fulfillmentStatus: afterStatus,  // ✅ Current status
  // ...
};
```

## ✅ **Verification**

### **Test with Real Order**
1. **Create a test order** with customer email `test@example.com`
2. **Update fulfillment status** in Firebase Console
3. **Check Cloud Function logs**:
   ```
   Order ABC123 fulfillment status changed: yet-to-dispatch → ready-to-dispatch
   Fulfillment email sent for order ABC123: {success: true, recipient: "test@example.com"}
   ```
4. **Verify email received** at `test@example.com`

### **Check Firestore Document**
```javascript
// In Firebase Console → Firestore → orders → [orderId]
{
  "customer": {
    "email": "actual-customer@email.com",  // ✅ This email will be used
    "firstName": "John",
    "lastName": "Doe"
  },
  "fulfillmentStatus": "ready-to-dispatch"  // ✅ This triggers the email
}
```

## 🚨 **Important Notes**

### **No Hardcoding**
- ❌ Email is **NOT** hardcoded to `attralsolar@gmail.com`
- ✅ Email is **dynamically extracted** from Firestore
- ✅ Each order uses its **own customer email**

### **Fallback Locations**
The function checks multiple locations for customer email:
1. `orderData.email` (direct field)
2. `orderData.customer.email` (primary location)
3. `orderData.user.email` (user account)
4. `orderData.shipping.email` (shipping info)
5. `orderData.notes.email` (order notes)

### **Error Handling**
```javascript
if (!customerEmail) {
  console.warn(`No customer email found for order ${orderId}`);
  return null; // Skip email sending
}
```

## 🧪 **Testing**

### **1. Check Current Order**
```javascript
// In Firebase Console, check order 2CW3BovpeTqdWXQexJ4m
// Look for customer.email field
```

### **2. Update Status**
```javascript
// Change fulfillmentStatus from "ready-to-dispatch" to "out-for-delivery"
// Check function logs for email extraction
```

### **3. Verify Email**
```javascript
// Check if email was sent to the customer email in Firestore
// NOT to attralsolar@gmail.com
```

## 📝 **Summary**

✅ **Customer email is correctly extracted** from Firestore  
✅ **No hardcoding** - uses actual customer email  
✅ **Dynamic per order** - each order uses its own customer email  
✅ **Multiple fallbacks** - checks various locations for email  
✅ **Error handling** - skips if no email found  

The `attralsolar@gmail.com` references you see are only in **test files** and **documentation examples**, not in the actual Cloud Function code.
