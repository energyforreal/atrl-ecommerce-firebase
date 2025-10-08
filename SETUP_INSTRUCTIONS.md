# 🔧 Setup Instructions for ATTRAL eCommerce

## 📋 Prerequisites
- PHP 7.4 or higher
- Firebase project
- Razorpay account
- Brevo (Sendinblue) account for email services

## 🔐 Security Setup

### 1. Firebase Service Account
1. Go to your Firebase Console
2. Navigate to Project Settings > Service Accounts
3. Click "Generate New Private Key"
4. Download the JSON file
5. Rename it to `firebase-service-account.json`
6. Place it in `static-site/api/` directory

### 2. Configuration File
1. Copy `static-site/api/config.example.php` to `static-site/api/config.php`
2. Fill in your actual credentials:
   - Razorpay API keys
   - Brevo API key
   - SMTP credentials
   - Firebase project ID

### 3. API Keys in Files
Replace placeholder API keys in the following files:
- `static-site/api/admin-email-system.php` (line 53)
- `static-site/api/brevo_newsletter.php` (line 21)
- `static-site/api/brevo_newsletter_js.php` (line 8)
- `static-site/api/contact-sync-utility.php` (line 36)
- `static-site/index.html` (line 1797)

## 📁 Directory Setup
Create these directories if they don't exist:
```bash
mkdir -p static-site/invoices
mkdir -p static-site/logs
mkdir -p static-site/ssl
```

## 🔒 SSL Certificates (for local development)
Generate self-signed certificates:
```bash
cd static-site
openssl req -x509 -newkey rsa:4096 -keyout server-key.pem -out server-cert.pem -days 365 -nodes
```

## 🚀 Running Locally
```bash
# Windows
start-local-server.bat

# Or PowerShell
.\start-local-server.ps1
```

## ⚠️ Important Security Notes
- **NEVER** commit files containing actual API keys or credentials
- Keep `config.php` and `firebase-service-account.json` private
- These files are already in `.gitignore`
- Use environment variables in production

## 📝 Files to Configure
1. `static-site/api/firebase-service-account.json` (from example)
2. `static-site/api/config.php` (from config.example.php)
3. Update hardcoded API keys in the files mentioned above

## 🔍 Verification
1. Check that all API keys are replaced
2. Test Firebase connection
3. Test Razorpay integration
4. Test email sending functionality

## 📞 Support
If you encounter issues, check:
1. PHP error logs
2. Firebase console for authentication issues
3. Razorpay dashboard for payment issues
4. Brevo dashboard for email delivery

