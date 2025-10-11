# Quick Deployment Guide

## 🚀 The Issue

Your local changes are NOT on the live server `attral.in`. You need to **upload 2 files** to fix the 400 errors.

## 📦 Files to Upload

### File 1: Backend API
- **Local**: `static-site/api/affiliate_functions.php`
- **Server**: `/api/affiliate_functions.php` (or `/public_html/api/affiliate_functions.php`)
- **Size**: ~20 KB
- **Purpose**: Fixes 400 errors, adds coupon stats

### File 2: Frontend Dashboard
- **Local**: `static-site/affiliate-dashboard.html`
- **Server**: `/affiliate-dashboard.html` (or `/public_html/affiliate-dashboard.html`)
- **Size**: ~90 KB
- **Purpose**: Displays new stats and customer info

### File 3: Debug Script (Optional)
- **Local**: `static-site/api/debug_affiliate.php`
- **Server**: `/api/debug_affiliate.php`
- **Purpose**: Check database structure

## 🖥️ Deployment Methods

### Method 1: FTP/SFTP (Recommended)
1. Open FileZilla or your FTP client
2. Connect to `attral.in`
3. Navigate to your web root (usually `public_html` or `www`)
4. Upload `affiliate_functions.php` to `/api/` folder
5. Upload `affiliate-dashboard.html` to root folder
6. Done! ✅

### Method 2: cPanel File Manager
1. Log in to your hosting control panel
2. Open File Manager
3. Navigate to `public_html` (or your web root)
4. Go to `api` folder
5. Upload `affiliate_functions.php` (replace existing)
6. Go back to root
7. Upload `affiliate-dashboard.html` (replace existing)
8. Done! ✅

### Method 3: SSH/SCP
```bash
# If you have SSH access
scp static-site/api/affiliate_functions.php user@attral.in:/path/to/public_html/api/
scp static-site/affiliate-dashboard.html user@attral.in:/path/to/public_html/
```

## ✅ Verification Steps

### 1. Check API Response
Visit in browser: `https://attral.in/api/affiliate_functions.php?action=test`

Should see JSON response (not 404)

### 2. Check Dashboard
Visit: `https://attral.in/affiliate-dashboard.html`

Should load without 400 errors

### 3. Check Console
Press `F12` → Console tab

Should see:
- ✅ `Initializing dashboard with user...`
- ✅ `Loading referred orders and stats...`
- ✅ No "API error: 400" messages

### 4. Check Stats Card
Look for new stat card with:
- 🎫 Icon
- "Coupon Uses" label
- Count and earnings

## 🔍 Debug Database (After Upload)

Visit: `https://attral.in/api/debug_affiliate.php?code=attral-71hlzssgan`

This will show:
- Whether affiliate profile exists
- Coupon usage data
- List of affiliates in database

## ⚠️ Important Notes

1. **Backup First**: Download current files before uploading
2. **File Permissions**: Ensure `.php` files have 644 permissions
3. **Cache**: Clear browser cache after upload (`Ctrl+F5`)
4. **Timing**: Changes are instant after upload

## 🎯 Expected Result

After uploading, the dashboard should:
- ✅ Load successfully
- ✅ Show stats (even if 0)
- ✅ Display "Coupon Uses" card
- ✅ Show customer info in orders
- ✅ No 400 errors

## 📞 If Still Not Working

1. **Check file was uploaded**: Download it back and verify
2. **Check file path**: Ensure correct folder structure
3. **Check PHP errors**: Enable `display_errors` temporarily
4. **Check server logs**: Look in hosting control panel
5. **Run debug script**: See what database returns

## 🚨 Common Mistakes

❌ Uploading to wrong folder
❌ Not replacing existing file
❌ File permissions issue
❌ Not clearing browser cache
❌ Wrong server/domain

✅ Upload to correct path
✅ Replace existing files
✅ Check permissions (644)
✅ Clear cache (Ctrl+F5)
✅ Verify domain is attral.in

---

**Bottom Line**: Just upload these 2 files to your server and the errors will be fixed immediately!

