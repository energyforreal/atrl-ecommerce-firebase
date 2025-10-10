# 🔧 Fix PHP OpenSSL Extension

## Issue Identified
Your email test failed because PHP is missing the `openssl` extension, which is required for SMTP authentication with Brevo.

**Error:** `[SMTP] Extension missing: openssl`

## Quick Fix Options

### Option 1: Enable OpenSSL in php.ini (Recommended)

1. **Find your php.ini file:**
   ```powershell
   php --ini
   ```
   Look for "Loaded Configuration File" path

2. **Open php.ini in a text editor:**
   ```powershell
   notepad "C:\path\to\your\php.ini"
   ```

3. **Find this line:**
   ```ini
   ;extension=openssl
   ```

4. **Remove the semicolon (uncomment):**
   ```ini
   extension=openssl
   ```

5. **Save the file and restart your web server**

### Option 2: Use XAMPP/WAMP (Easiest)

If you're using XAMPP or WAMP:

1. **Open XAMPP Control Panel**
2. **Click "Config" next to Apache**
3. **Select "PHP (php.ini)"**
4. **Find and uncomment:**
   ```ini
   extension=openssl
   ```
5. **Save and restart Apache**

### Option 3: Check if OpenSSL is Available

Run this to check your PHP extensions:
```powershell
php -m | findstr openssl
```

If you see `openssl` in the output, it's enabled. If not, you need to enable it.

## Alternative: Use Different SMTP Method

If you can't enable OpenSSL, I can modify the email code to use a different authentication method that doesn't require OpenSSL.

## Test After Fix

Once you've enabled OpenSSL, run:
```powershell
php test-email-sending.php
```

Expected result:
```
✅ SUCCESS! Test email sent successfully!
```

## Need Help?

If you're still having issues:
1. Tell me what web server you're using (XAMPP, WAMP, IIS, etc.)
2. Run `php --ini` and share the output
3. I'll provide specific instructions for your setup
