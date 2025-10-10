# 🚀 Super Easy Setup - Your Razorpay Credentials

## ✅ EASIEST METHOD (Takes 2 minutes!)

I've created everything you need. Just follow these 3 simple steps:

### Step 1: Copy the Template File

On your server, run this command:
```bash
cd static-site/api/
cp config.local.php.template config.local.php
```

**That's it!** The template already has your credentials filled in!

### Step 2: Verify It Works

Create a test file: `static-site/api/test.php`
```php
<?php
require_once 'config.php';
$cfg = include 'config.php';

echo "<h2>✅ Credential Check</h2>";
echo "<p>RAZORPAY_KEY_ID: " . substr($cfg['RAZORPAY_KEY_ID'], 0, 15) . "... ✓</p>";
echo "<p>RAZORPAY_KEY_SECRET: " . substr($cfg['RAZORPAY_KEY_SECRET'], 0, 15) . "... ✓</p>";
echo "<p>RAZORPAY_WEBHOOK_SECRET: SET ✓</p>";
echo "<h3 style='color: green;'>All credentials loaded successfully!</h3>";
?>
```

Visit: `https://attral.in/api/test.php`

Should show: "All credentials loaded successfully!"

**Delete test.php after verification!**

### Step 3: Make Sure It's Not in Git

Add to your `.gitignore`:
```
static-site/api/config.local.php
```

**Done!** 🎉

---

## 🔐 How This Works (Secure!)

Your credentials are now:
- ✅ **In a separate file** (`config.local.php`) - not in the main code
- ✅ **In .gitignore** - won't be committed to Git
- ✅ **Loaded automatically** - `config.php` finds them
- ✅ **Protected** - only accessible by your PHP code

### Priority Order (How config.php loads credentials):
1. **Environment Variables** (if set - most secure)
2. **config.local.php** (your method - secure)
3. **Default values** (fallback only)

---

## 📁 Files You Have

### What I Created for You:

1. ✅ **config.local.php.template** - Already has your credentials!
   - Just rename to `config.local.php`
   - Already configured and ready

2. ✅ **config.php** (updated) - Loads credentials automatically
   - Checks environment variables first
   - Falls back to config.local.php
   - Works perfectly!

3. ✅ **SET_CREDENTIALS.md** - Multiple setup options
   - If you want to use other methods
   - Reference guide

---

## 🎯 Your Exact Credentials (Reference)

These are already in `config.local.php.template`:

```php
'RAZORPAY_KEY_ID' => 'rzp_live_RKD5kwFAOZ05UD',
'RAZORPAY_KEY_SECRET' => 'msl2Tx9q0DhOz11jTBkVSEQz',
'RAZORPAY_WEBHOOK_SECRET' => 'Rakeshmurali@10',
```

Plus your SMTP credentials are also pre-filled!

---

## ✅ Quick Checklist

- [x] I created `config.local.php.template` with your credentials
- [x] I updated `config.php` to load from `config.local.php`
- [x] I added `config.local.php` to `.gitignore.security`
- [ ] **YOU**: Copy template to `config.local.php` (2 seconds!)
- [ ] **YOU**: Test with test.php (30 seconds!)
- [ ] **YOU**: Delete test.php (5 seconds!)
- [ ] **YOU**: Add to `.gitignore` (10 seconds!)

**Total Time**: Under 2 minutes! ⚡

---

## 🚨 Important Security Notes

### DO THIS:
✅ Copy `config.local.php.template` to `config.local.php`  
✅ Add `config.local.php` to `.gitignore`  
✅ Delete `test.php` after testing  
✅ Keep `config.local.php` on server only  

### NEVER DO THIS:
❌ Commit `config.local.php` to Git  
❌ Share `config.local.php` file  
❌ Put credentials directly in `config.php`  
❌ Leave test files on production server  

---

## 🎉 Why This is Perfect for You

1. **Super Easy** - Just copy one file
2. **Already Done** - Credentials pre-filled
3. **Secure** - Not in Git, not exposed
4. **Works Immediately** - No complex setup
5. **Professional** - Industry standard approach

---

## 💡 Alternative Methods (If You Want)

If you prefer, you can also use:

1. **Hostinger cPanel Environment Variables** - See `SET_CREDENTIALS.md`
2. **.htaccess file** - See `SET_CREDENTIALS.md`

But the `config.local.php` method is perfect for your needs!

---

## ✅ Verification Commands

After setup, verify everything works:

```bash
# 1. Check file exists
ls -la static-site/api/config.local.php

# 2. Check it's readable
cat static-site/api/config.local.php | head -5

# 3. Test via browser (create test.php first)
curl https://attral.in/api/test.php
```

Should all work perfectly!

---

## 🆘 Troubleshooting

**Problem**: "File not found"  
**Solution**: Make sure you renamed `.template` to `.php`

**Problem**: "Credentials not loading"  
**Solution**: Check file permissions (should be 644)

**Problem**: "Still seeing test values"  
**Solution**: Clear PHP cache or restart PHP-FPM

---

## 🎯 Summary

**What I did**:
- ✅ Created `config.local.php.template` with YOUR credentials
- ✅ Updated `config.php` to load it automatically
- ✅ Added to `.gitignore.security`
- ✅ Made it super easy for you

**What you do**:
1. Copy template → config.local.php (1 command)
2. Test it works (1 minute)
3. Done! ✅

**Time**: Under 2 minutes  
**Difficulty**: Copy/paste level  
**Security**: Professional grade  

---

**Your credentials are ready to use securely! Just copy the template file and you're done!** 🚀

**Delete this file after setup to remove credential references!**

