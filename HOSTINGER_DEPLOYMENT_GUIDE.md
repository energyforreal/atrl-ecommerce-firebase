# 🚀 Complete Guide: Uploading ATTRAL eCommerce Site to Hostinger

## 📋 Overview
This guide will walk you through uploading your ATTRAL eCommerce site to Hostinger and setting up private testing so your site is not visible to the public during development.

## 🎯 What You'll Learn
- How to upload your site to Hostinger
- How to configure private testing environment
- How to test your site without public access
- How to go live when ready

---

## 📁 Step 1: Prepare Your Files for Upload

### 1.1 Organize Your Project Structure
Your project has two main parts:
- **Frontend**: `static-site/` folder (HTML, CSS, JS, images)
- **Backend**: `api/` folder (PHP files for payments, emails, etc.)

### 1.2 Files You Need to Upload
```
📁 public_html/ (Hostinger root directory)
├── 📁 api/ (Backend PHP files)
│   ├── config.php
│   ├── create_order.php
│   ├── verify.php
│   ├── webhook.php
│   ├── send_email.php
│   └── ... (all other PHP files)
├── 📁 assets/ (Images, videos)
├── 📁 css/ (Stylesheets)
├── 📁 js/ (JavaScript files)
├── 📁 data/ (Product data)
├── 📁 vendor/ (PHP dependencies)
├── index.html (Homepage)
├── shop.html
├── cart.html
├── admin-dashboard.html
├── .htaccess (Access control)
├── site-access-control.php (Testing control)
├── maintenance.html (Maintenance page)
├── admin-login.html (Admin access)
├── admin-access.html (Control panel)
└── robots.txt (SEO blocking)
```

---

## 🔧 Step 2: Upload to Hostinger

### 2.1 Access Hostinger File Manager
1. **Login to Hostinger**: Go to [hpanel.hostinger.com](https://hpanel.hostinger.com)
2. **Navigate to File Manager**: Click "File Manager" in your control panel
3. **Open public_html**: This is your website's root directory

### 2.2 Upload Your Files

#### Method A: Upload via File Manager (Recommended for beginners)
1. **Create folders** in public_html:
   - Right-click → "New Folder" → Name it "api"
   - Create other folders as needed

2. **Upload files**:
   - Navigate to each folder
   - Click "Upload Files"
   - Select all files from your local `static-site/` folder
   - Upload all files from your local `api/` folder to the `api/` folder on Hostinger

#### Method B: Upload via FTP (For advanced users)
1. **Get FTP credentials** from Hostinger control panel
2. **Use FTP client** like FileZilla
3. **Connect** using your FTP details
4. **Upload** all files to public_html directory

### 2.3 Set File Permissions
After uploading, set these permissions:
- **Files**: 644 (readable by everyone, writable by owner)
- **Folders**: 755 (readable/executable by everyone, writable by owner)
- **PHP files**: 644
- **Config files**: 600 (readable/writable by owner only)

---

## 🛡️ Step 3: Configure Private Testing Environment

### 3.1 Set Up Access Control
Your site comes with built-in testing protection. Here's how to configure it:

#### 3.1.1 Find Your IP Address
1. Go to [whatismyipaddress.com](https://whatismyipaddress.com)
2. **Copy your IP address** (e.g., 203.45.67.89)

#### 3.1.2 Configure .htaccess File
1. **Open .htaccess** in Hostinger File Manager
2. **Find this line**:
   ```apache
   RewriteCond %{REMOTE_ADDR} !^YOUR_IP_ADDRESS$
   ```
3. **Replace `YOUR_IP_ADDRESS`** with your actual IP:
   ```apache
   RewriteCond %{REMOTE_ADDR} !^203.45.67.89$
   ```
4. **Save the file**

### 3.2 Configure PHP Settings
1. **Open** `api/config.php` in File Manager
2. **Update these settings**:
   ```php
   'LOCAL_MODE' => false,  // Set to false for production
   'RAZORPAY_KEY_ID' => 'your_live_key_id',
   'RAZORPAY_KEY_SECRET' => 'your_live_secret_key',
   'SMTP_HOST' => 'smtp.hostinger.com',
   'SMTP_USERNAME' => 'your_email@yourdomain.com',
   'SMTP_PASSWORD' => 'your_email_password',
   ```

---

## 🔐 Step 4: Test Your Private Access

### 4.1 Access the Admin Control Panel
1. **Go to**: `https://yourdomain.com/admin-login.html`
2. **Login with**:
   - Password: `admin123` (change this later!)
3. **Add your IP** to the whitelist
4. **Toggle maintenance mode** as needed

### 4.2 Test Your Site
1. **Visit your domain**: `https://yourdomain.com`
2. **You should see**:
   - ✅ **If maintenance is ON**: Maintenance page (for unauthorized visitors)
   - ✅ **If maintenance is OFF**: Your full website
3. **Test all features**:
   - Browse products
   - Add to cart
   - Test checkout
   - Check admin dashboard

---

## 🧪 Step 5: Testing Checklist

### 5.1 Basic Functionality Tests
- [ ] **Homepage loads** correctly
- [ ] **Product pages** display properly
- [ ] **Shopping cart** works
- [ ] **Checkout process** functions
- [ ] **Payment integration** works (test mode)
- [ ] **Email notifications** send
- [ ] **Admin dashboard** accessible
- [ ] **Mobile responsive** design

### 5.2 Security Tests
- [ ] **Unauthorized users** see maintenance page
- [ ] **Your IP** can access the site
- [ ] **Admin panel** requires password
- [ ] **Sensitive files** are protected

### 5.3 Performance Tests
- [ ] **Page load speed** is acceptable
- [ ] **Images load** properly
- [ ] **CSS/JS files** load correctly
- [ ] **Database connections** work

---

## 🚀 Step 6: Going Live (When Ready)

### 6.1 Pre-Launch Checklist
- [ ] **Test everything** thoroughly
- [ ] **Update admin password** (change from `admin123`)
- [ ] **Configure real payment keys** (Razorpay live keys)
- [ ] **Set up email** with your domain
- [ ] **Test email sending**
- [ ] **Verify all links** work

### 6.2 Make Site Public
1. **Login to admin panel**: `https://yourdomain.com/admin-login.html`
2. **Turn OFF maintenance mode**
3. **Remove IP restrictions** (optional)
4. **Update robots.txt** for SEO:
   ```txt
   User-agent: *
   Allow: /
   ```
5. **Remove maintenance meta tags** from HTML files

### 6.3 Final Steps
1. **Test from different devices**
2. **Check mobile responsiveness**
3. **Verify payment processing**
4. **Monitor for any errors**

---

## 🛠️ Troubleshooting Common Issues

### Issue 1: Can't Access Site
**Problem**: Getting maintenance page even with correct IP
**Solutions**:
- Check your IP address again
- Clear browser cache
- Try incognito/private browsing
- Check if you're behind a VPN

### Issue 2: PHP Errors
**Problem**: PHP files not working
**Solutions**:
- Check file permissions (644 for files, 755 for folders)
- Verify PHP is enabled on Hostinger
- Check error logs in Hostinger control panel

### Issue 3: Email Not Sending
**Problem**: Contact forms not working
**Solutions**:
- Verify SMTP settings in `api/config.php`
- Check Hostinger email settings
- Test with a simple email first

### Issue 4: Payment Issues
**Problem**: Razorpay integration not working
**Solutions**:
- Verify API keys are correct
- Check if you're using test/live keys appropriately
- Ensure HTTPS is enabled

---

## 📞 Getting Help

### Hostinger Support
- **Live Chat**: Available 24/7 in your control panel
- **Knowledge Base**: [help.hostinger.com](https://help.hostinger.com)
- **Email Support**: support@hostinger.com

### Technical Issues
1. **Check error logs** in Hostinger control panel
2. **Verify file permissions**
3. **Test with different browsers**
4. **Clear browser cache**

---

## 🎉 Success!

Once you've completed all steps:
- ✅ Your site is uploaded to Hostinger
- ✅ Private testing is configured
- ✅ You can test without public access
- ✅ Ready to go live when you're satisfied

### Next Steps
1. **Continue testing** your site thoroughly
2. **Make any necessary changes**
3. **When ready, follow Step 6** to go live
4. **Monitor your site** after going live

---

## 📝 Important Notes

### Security Reminders
- **Change default passwords** before going live
- **Keep your API keys secure**
- **Regularly update your site**
- **Monitor for security issues**

### Backup Strategy
- **Download backups** regularly from Hostinger
- **Keep local copies** of your files
- **Test backup restoration** process

### Performance Tips
- **Optimize images** before uploading
- **Use CDN** if available
- **Monitor site speed**
- **Regular maintenance**

---

**🎯 You're all set! Your ATTRAL eCommerce site is now safely uploaded to Hostinger with private testing enabled. Test thoroughly, make improvements, and when you're ready, follow the steps to go live!**
