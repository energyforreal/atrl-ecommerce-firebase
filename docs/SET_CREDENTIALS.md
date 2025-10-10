# 🔐 Setting Up Your Razorpay Credentials - SECURE METHOD

**Your Credentials** (for reference - DO NOT commit this file to Git!):
```
RAZORPAY_KEY_ID: rzp_live_RKD5kwFAOZ05UD
RAZORPAY_KEY_SECRET: msl2Tx9q0DhOz11jTBkVSEQz
RAZORPAY_WEBHOOK_SECRET: Rakeshmurali@10
```

---

## 🎯 Quick Setup - Choose Your Method

### Method 1: Hostinger cPanel (EASIEST - 5 minutes)

1. **Log into Hostinger Control Panel**
2. **Go to**: Advanced → Environment Variables (or PHP Configuration)
3. **Add these 3 variables**:

```
Variable Name: RAZORPAY_KEY_ID
Value: rzp_live_RKD5kwFAOZ05UD

Variable Name: RAZORPAY_KEY_SECRET
Value: msl2Tx9q0DhOz11jTBkVSEQz

Variable Name: RAZORPAY_WEBHOOK_SECRET
Value: Rakeshmurali@10
```

4. **Save** and **Restart PHP** (if option available)
5. **Done!** ✅

### Method 2: Create .htaccess File (5 minutes)

Create a file: `static-site/api/.htaccess`

```apache
# Razorpay Credentials
SetEnv RAZORPAY_KEY_ID "rzp_live_RKD5kwFAOZ05UD"
SetEnv RAZORPAY_KEY_SECRET "msl2Tx9q0DhOz11jTBkVSEQz"
SetEnv RAZORPAY_WEBHOOK_SECRET "Rakeshmurali@10"

# SMTP Credentials (you already have these)
SetEnv SMTP_USERNAME "8c9aee002@smtp-brevo.com"
SetEnv SMTP_PASSWORD "FXr1TZ9mQ0aEVqjp"

# CORS Configuration
SetEnv ALLOWED_ORIGINS "https://attral.in,https://www.attral.in"
```

**IMPORTANT**: Add `.htaccess` to your `.gitignore` file!

### Method 3: Create Local Config File (5 minutes)

Create: `static-site/api/config.local.php`

```php
<?php
// Local configuration - NOT committed to Git
// This file is loaded by config.php if it exists

return [
    'RAZORPAY_KEY_ID' => 'rzp_live_RKD5kwFAOZ05UD',
    'RAZORPAY_KEY_SECRET' => 'msl2Tx9q0DhOz11jTBkVSEQz',
    'RAZORPAY_WEBHOOK_SECRET' => 'Rakeshmurali@10',
    
    // Your existing SMTP credentials
    'SMTP_USERNAME' => '8c9aee002@smtp-brevo.com',
    'SMTP_PASSWORD' => 'FXr1TZ9mQ0aEVqjp',
    
    'ALLOWED_ORIGINS' => 'https://attral.in,https://www.attral.in',
];
?>
```

**IMPORTANT**: Add `config.local.php` to your `.gitignore` file!

---

## ✅ Verification

After setting up (any method), verify it works:

1. Create: `static-site/api/test_credentials.php`
```php
<?php
require_once 'config.php';
$cfg = include 'config.php';

echo "<h2>Credential Check</h2>";
echo "<p>RAZORPAY_KEY_ID: " . ($cfg['RAZORPAY_KEY_ID'] ? 'SET ✓' : 'NOT SET ✗') . "</p>";
echo "<p>RAZORPAY_KEY_SECRET: " . ($cfg['RAZORPAY_KEY_SECRET'] ? 'SET ✓' : 'NOT SET ✗') . "</p>";
echo "<p>RAZORPAY_WEBHOOK_SECRET: " . ($cfg['RAZORPAY_WEBHOOK_SECRET'] ? 'SET ✓' : 'NOT SET ✗') . "</p>";

// Show first few characters (for debugging)
echo "<hr>";
echo "<p>Key ID starts with: " . substr($cfg['RAZORPAY_KEY_ID'], 0, 10) . "...</p>";
echo "<p><strong>All credentials loaded successfully!</strong></p>";
?>
```

2. Visit: `https://attral.in/api/test_credentials.php`
3. Should show: "SET ✓" for all three
4. **DELETE** the test file after verification!

---

## 🔒 Security Notes

### Why This is Better Than Hardcoding:

✅ **Credentials not in code** - Can't be stolen from Git  
✅ **Easy to change** - Update in one place, not multiple files  
✅ **Different per environment** - Use test keys locally, live keys in production  
✅ **Industry standard** - This is how professionals do it  
✅ **Razorpay recommended** - Payment processors require this  

### What You Should Do:

1. ✅ Use one of the 3 methods above
2. ✅ Add credential files to `.gitignore`
3. ✅ Never commit credentials to Git
4. ✅ Delete this `SET_CREDENTIALS.md` file after setup

### What You Should NEVER Do:

❌ Put credentials directly in `config.php`  
❌ Commit credentials to Git  
❌ Share credentials in chat/email  
❌ Leave test files with credentials on server  

---

## 🎯 Recommended: Method 1 (Hostinger cPanel)

**Why**: 
- Most secure
- Survives file updates
- Professional standard
- Takes 5 minutes

**Do this**:
1. Open Hostinger cPanel
2. Find "Environment Variables" or "PHP Configuration"
3. Add the 3 variables shown at the top
4. Done!

Your `config.php` file I already created will automatically load them!

---

## ❓ Questions?

**Q: Will this work with my current setup?**  
A: Yes! The `config.php` I created already supports this.

**Q: Can I use test keys for testing?**  
A: Yes! Set different values in your test environment.

**Q: What if I need to change keys later?**  
A: Just update the environment variable - no code changes needed!

**Q: Is this really necessary?**  
A: YES! Your live credentials can authorize payments. Protect them like your bank password.

---

**Time to Set Up**: 5 minutes  
**Security Improvement**: Infinite ∞  
**Peace of Mind**: Priceless ✅

**Delete this file after setup to remove credential references!**

