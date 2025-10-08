# 📁 Essential API Files for ATTRAL E-commerce

## 🎯 **Core System Files (CRITICAL)**

### **Order Management**
- ✅ `order_manager.php` - Main order processing system
- ✅ `firestore_order_manager.php` - Firestore-based order management
- ✅ `firestore_order_manager_fallback.php` - SQLite fallback system
- ✅ `create_order.php` - Order creation endpoint
- ✅ `reconcile_orders.php` - Order reconciliation system

### **Email System**
- ✅ `send_order_email.php` - Order confirmation emails
- ✅ `generate_invoice.php` - Invoice generation and email
- ✅ `brevo_email_service.php` - Main email service
- ✅ `brevo_newsletter.php` - Newsletter functionality
- ✅ `trigger_order_emails.php` - Email triggers

### **Payment Processing**
- ✅ `verify.php` - Razorpay payment verification
- ✅ `webhook.php` - Razorpay webhook handler

### **Configuration**
- ✅ `config.php` - Main configuration file
- ✅ `composer.json` - PHP dependencies
- ✅ `firebase-service-account.json` - Firebase credentials
- ✅ `orders.db` - SQLite database

## 🔧 **Admin System Files**

### **Admin APIs**
- ✅ `admin-api.php` - Unified admin API
- ✅ `admin_auth.php` - Admin authentication
- ✅ `admin_analytics.php` - Analytics dashboard
- ✅ `admin_messages.php` - Message management
- ✅ `admin_orders.php` - Order management
- ✅ `admin_stats.php` - Statistics
- ✅ `admin_users.php` - User management

### **Admin Services**
- ✅ `firestore_admin_service.php` - Firestore admin operations

## 🤝 **Affiliate System**

- ✅ `affiliate_email.php` - Affiliate email system
- ✅ `sync_affiliates_to_brevo.php` - Brevo integration
- ✅ `sync_affiliates_cli.php` - CLI affiliate sync
- ✅ `AFFILIATE_SYNC_README.md` - Documentation

## 📧 **Email & Communication**

### **Contact System**
- ✅ `contact_handler.php` - Contact form handler
- ✅ `send_email.php` - General email sending
- ✅ `send_test_email.php` - Email testing
- ✅ `simple_email_test.php` - Simple email tests

### **Newsletter System**
- ✅ `brevo_newsletter_js.php` - Newsletter JavaScript integration

## 🧪 **Testing & Development**

### **Essential Test Files (Production-Ready)**
- ✅ `test_affiliate_emails.php` - Affiliate email testing
- ✅ `test_firestore_admin.php` - Firestore admin testing
- ✅ `send_test_email.php` - Email functionality testing

### **Monitoring**
- ✅ `check-database.php` - Database health check
- ✅ `check-webhook-status.php` - Webhook status check
- ✅ `monitor-webhook.php` - Webhook monitoring

## 📚 **Documentation & Tools**

### **Documentation**
- ✅ `FIRESTORE_SETUP.md` - Firestore setup guide
- ✅ `TRANSFER_SUMMARY.md` - System transfer summary

### **Tools**
- ✅ `tools/backfill_invoices.php` - Invoice backfill tool
- ✅ `save_product.php` - Product management
- ✅ `lib/fpdf/fpdf.php` - PDF generation library

## 🛡️ **Security & Configuration**

- ✅ `.htaccess` - Apache configuration
- ✅ `composer.phar` - Composer executable

## ✅ **File Status: COMPLETE**

All essential files have been successfully transferred from the external `api/` folder to `static-site/api/`. The system is now self-contained and ready for deployment.

### **Total Files: 40+ (Optimized for Production)**
- **Core System**: 15 files
- **Admin System**: 8 files  
- **Email System**: 10 files
- **Testing**: 3 essential files (production-ready)
- **Documentation**: 5+ files
- **Tools & Utilities**: 5+ files

### **🧹 Cleanup Completed:**
- ❌ Removed 4 duplicate `_original.php` files
- ❌ Removed 7 unnecessary test files for production
- ❌ Removed 1 empty/corrupted file
- ✅ Kept only essential production-ready files

## 🚀 **Ready for Hostinger Deployment**

The `static-site/` folder now contains all necessary files for a complete e-commerce system deployment.
