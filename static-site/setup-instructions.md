# 🛡️ ATTRAL Testing Environment Setup

## Overview

This setup provides comprehensive access control for testing your ATTRAL website on Hostinger without public access.

## 🚀 Quick Setup

### 1. Upload Files to Hostinger
Upload these files to your Hostinger public_html directory:
- `.htaccess` (main access control)
- `maintenance.html` (maintenance page)
- `admin-login.html` (admin login)
- `admin-access.html` (control panel)
- `site-access-control.php` (backend logic)
- `robots.txt` (block search engines)

### 2. Configure Your IP Address
1. Find your IP address: https://whatismyipaddress.com/
2. Edit `.htaccess` file
3. Replace `YOUR_IP_ADDRESS` with your actual IP
4. Save and upload

### 3. Access the Control Panel
1. Go to: `https://yourdomain.com/admin-login.html`
2. Login with password: `admin123`
3. Add your IP to whitelist
4. Toggle maintenance mode as needed

## 🔧 Configuration Options

### Default Settings
- **Maintenance Mode**: ON (blocks all visitors)
- **Admin Password**: `admin123` (change this!)
- **Allowed IPs**: None (add your IP)
- **Bypass Paths**: Admin pages, maintenance page

### Customization
Edit `static-site/site-access-control.php` to change:
- Default password
- Maintenance message
- Bypass paths
- Session timeout

## 📱 How It Works

### For Visitors
- **Maintenance ON**: Only whitelisted IPs can access
- **Maintenance OFF**: Everyone can access normally
- **Unauthorized**: Redirected to maintenance page

### For Admins
- Login via `/admin-login.html`
- Control maintenance mode
- Manage IP whitelist
- Real-time status updates

## 🛡️ Security Features

### IP Protection
- Whitelist specific IP addresses
- Support for CIDR ranges (192.168.1.0/24)
- Automatic current IP detection

### Access Control
- Password-protected admin panel
- Session-based authentication
- Automatic logout on inactivity

### SEO Protection
- `robots.txt` blocks all crawlers
- Meta tags prevent indexing
- No sitemap provided

### Security Headers
- X-Frame-Options: DENY
- X-Content-Type-Options: nosniff
- Referrer-Policy: strict
- Attack pattern blocking

## 🎯 Testing Workflow

### Initial Setup
1. Upload all files
2. Configure your IP in `.htaccess`
3. Login to admin panel
4. Add your IP to whitelist
5. Test site access

### During Testing
1. Keep maintenance mode ON
2. Add team member IPs as needed
3. Use admin panel to manage access
4. Monitor site functionality

### Before Going Live
1. Turn maintenance mode OFF
2. Remove IP restrictions
3. Update robots.txt
4. Remove maintenance meta tags
5. Deploy production version

## 🔍 Troubleshooting

### Can't Access Site
- Check your IP in `.htaccess`
- Verify IP is in whitelist
- Check maintenance mode status
- Clear browser cache

### Admin Panel Issues
- Verify PHP is enabled
- Check file permissions (644 for files, 755 for directories)
- Ensure `site-access-control.php` is accessible
- Check error logs

### Email Functionality
- Set `LOCAL_MODE=false` in production
- Verify SMTP credentials
- Test with real email addresses
- Check spam folders

## 📞 Support

For issues with this setup:
1. Check Hostinger error logs
2. Verify file permissions
3. Test with different browsers
4. Contact support if needed

## 🔄 Going Live Checklist

- [ ] Turn off maintenance mode
- [ ] Remove IP restrictions
- [ ] Update robots.txt for SEO
- [ ] Remove noindex meta tags
- [ ] Test all functionality
- [ ] Verify email sending
- [ ] Check payment processing
- [ ] Test on mobile devices

---

**Remember**: Change the default password `admin123` to something secure before going live!
