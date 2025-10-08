# 🚀 ATTRAL eCommerce - Local Development Server

## 📋 Overview

**SIMPLE ONE-FILE SETUP** - This provides a complete local development environment for your ATTRAL eCommerce website with full admin functionality support. Perfect for testing, development, and offline work.

## 🎯 Features

- **🔧 Local HTTPS Server** - Full PHP server with SSL support
- **🛡️ Admin Functions** - Complete admin panel access
- **🔓 Admin Bypass** - Automatic admin access for development
- **📊 Analytics** - Full analytics dashboard
- **🛒 Order Management** - Test complete order flow
- **📧 Email Testing** - Test email functionality
- **🔐 Access Control** - Test maintenance mode and IP restrictions

## 📦 What's Included

### **Single Files (No Setup Required):**
- `start-local-server.bat` - **ONE FILE** - Setup + Start server (Windows)
- `start-local-server.ps1` - **ONE FILE** - Setup + Start server (PowerShell)

### **Auto-Created Files:**
- `static-site/router.php` - Custom PHP router (auto-created)
- `static-site/js/local-config.js` - Local development configuration (auto-created)
- `static-site/local-admin-bypass.php` - Admin access bypass (auto-created)

## 🚀 Quick Start (Super Simple!)

### **Step 1: Prerequisites**
1. **Install PHP** (if not already installed):
   - Download from: [windows.php.net](https://windows.php.net/download/)
   - Or install XAMPP: [apachefriends.org](https://www.apachefriends.org/download.html)
   - Add PHP to your system PATH

### **Step 2: Start Server (That's It!)**
**For Windows:**
```bash
# Just double-click this file:
start-local-server.bat
```

**For PowerShell:**
```powershell
# Right-click and "Run with PowerShell":
.\start-local-server.ps1
```

**The script will automatically:**
- ✅ Check PHP installation
- ✅ Create necessary directories
- ✅ Generate SSL certificates
- ✅ Create local configuration files
- ✅ Start the server
- ✅ Show you all the URLs

### **Step 3: Access Your Site**
- **Homepage**: http://localhost:8000
- **Admin Panel**: http://localhost:8000/admin-login.html
- **Admin Bypass**: http://localhost:8000/local-admin-bypass.php

## 🌐 Server URLs

| Function | URL |
|----------|-----|
| **Homepage** | http://localhost:8000/index.html |
| **Shop** | http://localhost:8000/shop.html |
| **Cart** | http://localhost:8000/cart.html |
| **Checkout** | http://localhost:8000/order.html |
| **Order Success** | http://localhost:8000/order-success.html |
| **User Dashboard** | http://localhost:8000/user-dashboard.html |
| **Admin Dashboard** | http://localhost:8000/admin-dashboard.html |
| **Admin Login** | http://localhost:8000/admin-login.html |
| **Access Control** | http://localhost:8000/admin-access.html |
| **Analytics** | http://localhost:8000/admin-dashboard.html |

## 🛡️ Admin Functions Available

### **✅ Site Management:**
- **Maintenance Mode Toggle** - Control site access
- **IP Management** - Whitelist/blacklist IPs
- **Access Control** - Manage user permissions

### **✅ Order Management:**
- **View Orders** - See all orders in the system
- **Order Status** - Update order status
- **Order Analytics** - Sales and performance metrics

### **✅ User Management:**
- **User Accounts** - Manage customer accounts
- **User Analytics** - User behavior and statistics
- **Affiliate Management** - Commission tracking

### **✅ Analytics Dashboard:**
- **Sales Metrics** - Revenue and conversion tracking
- **Product Analytics** - Best-selling products
- **User Analytics** - Customer behavior insights

## 🔧 Local Development Features

### **🔓 Admin Bypass:**
- Automatic admin access for development
- No need to login repeatedly
- Full admin panel access

### **🧪 Testing Tools:**
- Mock order data for testing
- Payment flow testing
- Email functionality testing
- Cart functionality testing

### **📊 Debug Information:**
- Console logging for debugging
- Local development indicators
- API endpoint testing

## 🚨 Important Notes

### **⚠️ Security:**
- **This is for DEVELOPMENT ONLY**
- **Do NOT use for production**
- **SSL certificate is self-signed**
- **Admin bypass is enabled by default**

### **🔧 Configuration:**
- **API Base URL**: Automatically set to `http://localhost:8000`
- **Firebase**: Uses production Firebase config
- **Email**: Uses production Brevo configuration
- **Database**: Uses Firestore (cloud database)

### **📱 Browser Warnings:**
- **HTTPS Warning**: Browser will show security warning for self-signed certificate
- **Click "Advanced" → "Proceed to localhost"** to continue

## 🛠️ Troubleshooting

### **❌ PHP Not Found:**
```bash
# Add PHP to PATH or use full path
C:\xampp\php\php.exe -S localhost:8000
```

### **❌ Port Already in Use:**
```bash
# Use different port
php -S localhost:8001
```

### **❌ SSL Certificate Issues:**
```bash
# Regenerate certificate
openssl req -x509 -newkey rsa:4096 -keyout ssl/server.key -out ssl/server.crt -days 365 -nodes
```

### **❌ Admin Access Issues:**
1. Visit: http://localhost:8000/local-admin-bypass.php
2. Check browser console for errors
3. Clear browser cache and cookies

## 📋 Development Commands

### **Available in Browser Console:**
```javascript
// Enable admin access
ATTRAL_LOCAL_HELPERS.enableAdminAccess();

// Test order flow
ATTRAL_LOCAL_HELPERS.testOrderFlow();

// Clear storage
ATTRAL_LOCAL_HELPERS.clearStorage();

// Show development info
ATTRAL_LOCAL_HELPERS.showInfo();
```

## 🔄 Updating

### **To Update Local Development Files:**
1. **Stop the server** (Ctrl+C)
2. **Run setup again**:
   ```bash
   setup-local-dev.bat
   ```
3. **Start the server**:
   ```bash
   start-local-server.bat
   ```

## 📞 Support

### **Common Issues:**
- **Check PHP version**: `php --version`
- **Check OpenSSL**: `php -m | grep openssl`
- **Check ports**: `netstat -an | grep :8000`
- **Check permissions**: Ensure write access to directories

### **Logs:**
- **Server logs**: Displayed in console
- **PHP errors**: Check browser console
- **Access logs**: Available in server output

---

## 🎉 Ready to Develop!

Your local development environment is now ready for testing all ATTRAL eCommerce functionality, especially admin functions, without affecting your live site!

**Happy Coding!** 🚀✨
