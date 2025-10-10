# 🚀 Hostinger Deployment - Quick Start

## TL;DR - Fastest Way to Deploy

**What:** Upload `static-site/` folder to Hostinger `public_html/`
**Size:** ~40 MB total (~21 MB is vendor/ dependencies)
**Time:** 15-20 minutes
**Can you skip vendor/?** NO, but you can clean it to save 30-40%

---

## ⚡ 3-Step Quick Deploy

### 1. Prepare (5 minutes)

```powershell
# Clean vendor to reduce size (OPTIONAL)
.\clean-vendor-for-production.ps1

# Verify these files:
# ✅ static-site/api/config.php (production credentials)
# ✅ static-site/api/firebase-service-account.json (exists)
```

### 2. Modify 2 Files (2 minutes)

**File 1: `static-site/.htaccess`**
- Remove lines 12-30 (IP restrictions)
- Keep security headers

**File 2: `static-site/robots.txt`**
- Change `Disallow: /` to `Allow: /`
- Add sitemap URL

### 3. Upload via FTP (10 minutes)

**Upload contents of `static-site/` to `public_html/`**

```
static-site/*  →  public_html/
```

**Done!** Visit https://attral.in

---

## 📦 Vendor Directory - What to Know

### Size Breakdown:

| Package | Size | Purpose |
|---------|------|---------|
| google/ | 11.94 MB | Firestore/Firebase |
| guzzlehttp/ | 1.75 MB | HTTP client |
| monolog/ | 1.65 MB | Logging |
| phpmailer/ | 1.25 MB | Email sending |
| kreait/ | 1.06 MB | Firebase SDK |
| Others | 3.02 MB | Dependencies |
| **TOTAL** | **~21 MB** | **All required** |

### Can You Skip vendor/?

**❌ NO** - Your site needs these to work

**But you have options:**

**Option A: Upload as-is (Easiest)**
- 21 MB upload
- Guaranteed to work
- 10-15 minute upload

**Option B: Clean first (Recommended)**
```powershell
.\clean-vendor-for-production.ps1
```
- Reduces to ~13-15 MB (saves 30-40%)
- Removes tests, docs, examples
- Still 100% functional

**Option C: Use Composer on Hostinger (Advanced)**
- Upload only `composer.json` and `composer.lock`
- SSH: `composer install --no-dev`
- Requires SSH access

---

## 📊 What vendor/ Contains

### Critical Packages (MUST HAVE):

✅ **google/** (11.94 MB)
- cloud-firestore - Database
- cloud-core - Core functions
- auth - Authentication
- protobuf - Data encoding

✅ **phpmailer/** (1.25 MB)
- Email sending (order confirmations, invoices)

✅ **kreait/firebase-php** (1.06 MB)
- Firebase integration

✅ **guzzlehttp/** (1.75 MB)
- HTTP requests to APIs

✅ **monolog/** (1.65 MB)
- Error logging

### What Gets Removed When Cleaning:

❌ Test directories (~30% of vendor)
❌ Documentation (.md files)
❌ Example code
❌ Git files
❌ Configuration templates

**Result:** Same functionality, smaller size

---

## 🎯 Upload Summary

### Files to Upload:

```
✅ All HTML files (~35 files, ~2 MB)
✅ All PHP files in api/ (~45 files, ~1 MB)
✅ All JS files (~15 files, ~1 MB)
✅ All CSS files (~6 files, ~0.5 MB)
✅ All assets/ (images/videos, ~20 MB)
✅ api/vendor/ (dependencies, ~21 MB or ~15 MB cleaned)
✅ api/config.php (VERIFY production credentials!)
✅ api/firebase-service-account.json (MUST exist!)
✅ .htaccess files (3 files, modified)
✅ robots.txt (modified)
✅ sitemap.xml
✅ data/ (JSON files)
✅ config/ (access-control.json)
```

### Files to EXCLUDE:

```
❌ All *.md files (documentation)
❌ All *.txt files (except robots.txt)
❌ All *.bat and *.ps1 scripts
❌ test-*.php files
❌ validate-*.php files
❌ check-*.php files
❌ composer.phar
❌ *.zip archives
❌ node_modules/
❌ Development/backup PHP files
```

**Total Upload:** ~40-45 MB (or ~35 MB if vendor cleaned)

---

## ⚙️ After Upload

### 1. Set Permissions

```bash
# Writable directories
chmod 775 invoices
chmod 775 logs
chmod 775 temp

# Protect sensitive files
chmod 644 api/config.php
chmod 644 api/firebase-service-account.json
```

### 2. Test Your Site

Visit:
- https://attral.in (homepage)
- https://attral.in/shop.html (products)
- Place test order
- Check email arrives
- Login to admin dashboard

### 3. Verify Security

These should return 403 Forbidden:
- https://attral.in/api/config.php
- https://attral.in/api/firebase-service-account.json

---

## 🔧 Troubleshooting

### Site shows white screen?
- Check file permissions (755 for directories, 644 for files)
- Verify .htaccess uploaded
- Check Hostinger error logs

### Orders not saving?
- Verify `firebase-service-account.json` exists
- Check file permissions (644)
- Test Firebase connection

### Emails not sending?
- Verify SMTP credentials in `config.php`
- Ensure PHP openssl extension enabled
- Check Hostinger allows SMTP on port 587

### Images not loading?
- Check file paths in HTML
- Verify file permissions (644)
- Clear browser cache

---

## 📚 Full Documentation

For detailed information, see:

1. **`HOSTINGER_DEPLOYMENT_COMPLETE_GUIDE.md`** - Complete step-by-step guide
2. **`HOSTINGER_VENDOR_ANALYSIS.md`** - Detailed vendor/ analysis
3. **`PHPMAILER_IMPLEMENTATION_COMPLETE.md`** - Email system details
4. **`IMPLEMENTATION_COMPLETE.md`** - Overall system summary

---

## ✅ Pre-Flight Checklist

Before uploading:

- [ ] Modified `static-site/.htaccess` (removed IP restrictions)
- [ ] Modified `static-site/robots.txt` (allow crawlers)
- [ ] Verified `api/config.php` has production credentials
- [ ] Confirmed `api/firebase-service-account.json` exists
- [ ] (Optional) Ran `clean-vendor-for-production.ps1`
- [ ] Ready to upload ~40 MB to Hostinger

---

## 🎉 Quick Answer to Your Question

**Q: Can we omit vendor/ when uploading to Hostinger?**

**A: NO** - Your website needs vendor/ to function. It contains:
- PHPMailer (sends emails)
- Google Cloud libraries (Firestore database)
- Firebase SDK (authentication)
- HTTP client (API calls)
- All other dependencies

**But you can:**
1. Clean it (reduce from 21 MB to 13-15 MB) ⭐ **Recommended**
2. Upload as-is (21 MB is fine)
3. Use Composer on Hostinger (requires SSH)

**Best approach:** Run `clean-vendor-for-production.ps1` then upload.

---

**Total upload time: 15-20 minutes**
**Your site will be live immediately after upload!** 🚀


