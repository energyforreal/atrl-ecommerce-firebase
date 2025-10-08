# 📧 ATTRAL Admin Email System - Complete Guide

## 🎯 Overview

The ATTRAL Admin Email System provides comprehensive email functionality for the admin dashboard, integrating with Brevo SMTP for reliable email delivery. The system allows you to send custom emails to Firestore users, affiliates, and Brevo contact lists.

## 🚀 Features Implemented

### ✅ **Core Email Functionality**
- **Custom Email Composer**: Rich email editor with live preview
- **Bulk Email Sending**: Send emails to multiple recipients simultaneously
- **Brevo SMTP Integration**: Uses your existing Brevo SMTP settings
- **Email Templates**: Professional ATTRAL-branded email templates
- **Real-time Preview**: See how emails will look before sending

### ✅ **Contact Management**
- **Firestore Integration**: Access users and affiliates from your Firestore database
- **Brevo Contact Lists**: Access and manage Brevo contact lists
- **Contact Sync**: Synchronize Firestore contacts with Brevo
- **Duplicate Prevention**: Smart duplicate detection and handling

### ✅ **Recipient Selection**
- **All Customers & Affiliates**: Send to all Firestore users
- **Customers Only**: Target only customer accounts
- **Affiliates Only**: Target only affiliate accounts
- **Brevo Lists**: Send to specific Brevo contact lists
- **Custom Recipients**: Manual email list entry

## 📁 Files Created/Modified

### **New Files Created:**
1. **`api/admin-email-system.php`** - Main email system API
2. **`api/contact-sync-utility.php`** - Contact synchronization utility
3. **`test-admin-email-system.html`** - Comprehensive test interface
4. **`ADMIN_EMAIL_SYSTEM_GUIDE.md`** - This documentation

### **Modified Files:**
1. **`dashboard-original.html`** - Added email composer UI and functionality

## 🔧 Configuration

### **Brevo SMTP Settings (Already Configured)**
```
SMTP Server: smtp-relay.brevo.com
Port: 587
Login: 8c9aee002@smtp-brevo.com
Password: FXr1TZ9mQ0aEVqjp
```

### **Brevo Contact Lists**
- **Customer List ID**: 3 (Attral Shopping - Customer contacts)
- **Affiliate List ID**: 10 (e-Commerce Affiliates - Affiliate contacts)

## 🎛️ How to Use

### **1. Access Email Composer**
1. Go to `dashboard-original.html`
2. Click the **"📧 Email Campaign"** quick action button
3. The email composer modal will open

### **2. Compose Email**
1. **Subject**: Enter your email subject
2. **Recipients**: Choose from:
   - All Customers & Affiliates
   - Customers Only
   - Affiliates Only
   - Brevo Customer List
   - Brevo Affiliate List
   - Custom Recipients
3. **Content**: Write your email content
4. **From Name/Email**: Customize sender information

### **3. Preview & Send**
1. **Preview**: Click "Preview" to see how the email will look
2. **Recipients Preview**: See who will receive the email
3. **Campaign Stats**: View recipient count and estimated delivery time
4. **Send**: Click "Send Email" to deliver to all recipients

### **4. Monitor Results**
- Success/failure counts are displayed after sending
- Individual recipient results are logged
- Failed emails are reported with error details

## 🔄 Contact Synchronization

### **Sync Firestore to Brevo**
1. Use the contact sync utility to sync users and affiliates
2. Access via `contact-sync-utility.php` API
3. Batch processing with configurable limits
4. Duplicate prevention and validation

### **Sync Status Monitoring**
- Track synchronization progress
- Compare Firestore vs Brevo contact counts
- Identify pending syncs
- Monitor sync health

## 🧪 Testing

### **Test Interface**
Access `test-admin-email-system.html` for comprehensive testing:

1. **Email System Tests**
   - Send single test email
   - Send bulk test emails
   - Verify SMTP connectivity

2. **Contact Management Tests**
   - Retrieve Firestore users
   - Access Brevo contact lists
   - Add contacts to Brevo
   - Get contact details

3. **Sync Tests**
   - Sync users to Brevo
   - Sync affiliates to Brevo
   - Check sync status
   - Validate email addresses

### **Test Commands**
```bash
# Test single email
curl -X POST http://your-domain/api/admin-email-system.php \
  -H "Content-Type: application/json" \
  -d '{"action":"send_custom_email","to":"test@example.com","subject":"Test","content":"Test content"}'

# Test bulk email
curl -X POST http://your-domain/api/admin-email-system.php \
  -H "Content-Type: application/json" \
  -d '{"action":"send_bulk_emails","recipients":[{"email":"test@example.com","name":"Test"}],"subject":"Bulk Test","content":"Bulk content"}'
```

## 📊 API Endpoints

### **Email System API (`admin-email-system.php`)**
- `send_custom_email` - Send email to single recipient
- `send_bulk_emails` - Send emails to multiple recipients
- `get_firestore_users` - Get users from Firestore
- `get_brevo_lists` - Get Brevo contact lists
- `get_brevo_contacts` - Get contacts from Brevo list
- `add_contact_to_brevo` - Add contact to Brevo list

### **Contact Sync API (`contact-sync-utility.php`)**
- `sync_users_to_brevo` - Sync Firestore users to Brevo
- `sync_affiliates_to_brevo` - Sync Firestore affiliates to Brevo
- `sync_all_contacts_to_brevo` - Sync all contacts
- `get_sync_status` - Get synchronization status
- `remove_duplicate_contacts` - Remove duplicate contacts
- `validate_emails` - Validate email addresses

## 🛡️ Security Features

### **Input Validation**
- Email address validation
- Content sanitization
- Rate limiting protection
- SQL injection prevention

### **Access Control**
- Admin authentication required
- Secure API endpoints
- Error message sanitization
- Request validation

## 📈 Performance Features

### **Optimization**
- Batch processing for large lists
- Rate limiting to prevent SMTP blocking
- Efficient Firestore queries
- Caching for repeated operations

### **Monitoring**
- Delivery status tracking
- Error logging and reporting
- Performance metrics
- System health monitoring

## 🔧 Troubleshooting

### **Common Issues**

1. **Emails Not Sending**
   - Check Brevo SMTP credentials
   - Verify network connectivity
   - Check email content for issues
   - Review error logs

2. **Contact Sync Failures**
   - Verify Firestore permissions
   - Check Brevo API limits
   - Validate email addresses
   - Review sync logs

3. **UI Not Loading**
   - Check JavaScript console for errors
   - Verify API endpoints are accessible
   - Check browser compatibility
   - Clear browser cache

### **Debug Mode**
Enable debug mode by setting `LOCAL_MODE` to `true` in config files for detailed logging.

## 📞 Support

For technical support or questions:
- Check the test interface for system status
- Review error logs in the browser console
- Verify API responses in the test interface
- Contact the development team for advanced issues

## 🚀 Future Enhancements

### **Planned Features**
- Email templates management
- Scheduled email campaigns
- Advanced analytics and reporting
- A/B testing capabilities
- Email automation workflows
- Advanced segmentation
- Performance optimization
- Mobile-responsive improvements

---

## 📝 Quick Start Checklist

- [ ] Access `dashboard-original.html`
- [ ] Click "📧 Email Campaign" button
- [ ] Test with a single email first
- [ ] Verify Brevo SMTP settings
- [ ] Test contact synchronization
- [ ] Send test campaign to small group
- [ ] Monitor delivery results
- [ ] Set up regular sync schedule

**🎉 Your ATTRAL Admin Email System is now ready to use!**
