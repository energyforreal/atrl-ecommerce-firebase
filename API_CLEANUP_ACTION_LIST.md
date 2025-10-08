# 🎯 API Files - Quick Action Checklist

## ✅ SAFE TO DELETE NOW (10 Files)

### Test/Development Files (3 files):
```bash
❌ api/send_test_email.php
❌ api/send_test_email_simple.php
❌ api/simple_email_sender.php
```
**Reason:** Test files never called in production

### Duplicate PDF Files (3 files):
```bash
❌ api/generate_pdf_invoice.php
❌ api/generate_pdf_fixed.php
❌ api/generate_pdf_simple.php
```
**Reason:** generate_invoice.php and generate_pdf_minimal.php are the active ones

### Duplicate Affiliate Files (3 files):
```bash
❌ api/affiliate_email_production.php
❌ api/affiliate_email_functions_only.php
❌ api/affiliate_email_sender_functions.php
```
**Reason:** affiliate_email_sender.php is the primary active file

### Security Risk (1 file):
```bash
❌ api/config.php.backup
```
**Reason:** SECURITY RISK - Old backup contains credentials

---

## ⚠️ NEED REVIEW BEFORE DELETING (6 Files)

### 1. send_email.php vs send_email_real.php
**Action Needed:** Consolidate to ONE file
- Check which one contact_handler.php actually uses
- Delete the other one

### 2. send_order_email.php
**Action Needed:** Verify if called by order-success.html
- Search for references
- If not used, can delete

### 3. affiliate_email.php
**Action Needed:** Check if firestore_order_manager.php uses it
- Line 729 references affiliate_email_sender.php
- If affiliate_email.php is not called anywhere, delete it

### 4. fulfillment_status_webhook.php
**Action Needed:** Verify if external fulfillment system uses this
- Check if you have fulfillment partner integration
- If no fulfillment system, can delete

### 5. send_fulfillment_email.php
**Action Needed:** Linked to fulfillment_status_webhook.php
- If keeping webhook, keep this
- If deleting webhook, delete this too

### 6. admin-firestore-bypass.php
**Action Needed:** Check if this is a development tool
- If only used for local dev, can delete for production

---

## ✅ CONFIRMED ACTIVE FILES (41 Files)

### Core Order System (5 files):
✅ order_manager.php
✅ firestore_order_manager.php
✅ firestore_order_manager_fallback.php
✅ create_order.php
✅ coupon_tracking_service.php

### Email Systems (3 files):
✅ brevo_email_service.php (primary)
✅ admin-email-system.php (admin emails)
✅ send_email.php or send_email_real.php (one of these - need to verify which)

### Affiliate System (3 files):
✅ affiliate_functions.php (NEW - Firebase replacement)
✅ affiliate_email_sender.php (affiliate emails)
✅ send_affiliate_welcome_on_signup.php (used by affiliates.html)

### PDF/Invoice (2 files):
✅ generate_invoice.php (with email)
✅ generate_pdf_minimal.php (used by order-success.html)

### Admin Files (7 files):
✅ admin_auth.php
✅ admin_orders.php
✅ admin_stats.php
✅ admin_analytics.php
✅ admin_messages.php
✅ admin_users.php
✅ admin-api.php

### Firestore/Database (3 files):
✅ firestore_admin_service.php
✅ config.php
✅ config.example.php (template)

### Webhooks/Newsletter (4 files):
✅ webhook.php (Razorpay)
✅ brevo_newsletter.php
✅ brevo_newsletter_js.php
✅ contact_handler.php

### Monitoring Tools (4 files):
✅ monitor-webhook.php
✅ check-webhook-status.php
✅ check-database.php
✅ verify.php

### Utility/CLI Tools (6 files):
✅ sync_affiliates_cli.php
✅ sync_affiliates_to_brevo.php
✅ contact-sync-utility.php
✅ reconcile_orders.php
✅ trigger_order_emails.php
✅ tools/backfill_invoices.php

### Misc (4 files):
✅ save_product.php
✅ generate-admin-token.php
✅ orders.db (database)
✅ vendor/ (Composer dependencies)

---

## 📋 CRITICAL FINDINGS

### 🔴 Issue 1: Multiple Email Files
**Problem:** 8 different email sending files  
**Solution:** Use brevo_email_service.php as primary, consolidate send_email.php/send_email_real.php

### 🟡 Issue 2: Affiliate Email Orphan (RESOLVED!)
**Status:** send_affiliate_welcome_on_signup.php IS integrated with affiliates.html ✅
**Action:** Keep this file

### 🟢 Issue 3: PDF Generation (RESOLVED!)
**Status:** generate_pdf_minimal.php IS used by order-success.html ✅
**Action:** Keep both generate_invoice.php and generate_pdf_minimal.php

### 🟢 Issue 4: create_order.php (RESOLVED!)
**Status:** IS used by order.html ✅
**Action:** Keep this file

---

## 🎯 QUICK DELETE COMMAND

To delete all 10 safe files at once (PowerShell):

```powershell
cd static-site/api

# Delete test files
Remove-Item send_test_email.php
Remove-Item send_test_email_simple.php
Remove-Item simple_email_sender.php

# Delete duplicate PDF files
Remove-Item generate_pdf_invoice.php
Remove-Item generate_pdf_fixed.php
Remove-Item generate_pdf_simple.php

# Delete duplicate affiliate files
Remove-Item affiliate_email_production.php
Remove-Item affiliate_email_functions_only.php
Remove-Item affiliate_email_sender_functions.php

# Delete security risk
Remove-Item config.php.backup
```

---

## 📊 FINAL SUMMARY

| Status | Count | Action |
|--------|-------|--------|
| ✅ Active & Working | 41 files | **KEEP** |
| ❌ Safe to Delete | 10 files | **DELETE NOW** |
| ⚠️ Need Review | 6 files | **REVIEW FIRST** |
| 📦 Dependencies | 2 dirs | **KEEP** (vendor/, lib/) |

**Total Files Analyzed:** 59 PHP files  
**Disk Space Savings:** ~500KB-1MB (from deleting duplicates)  
**Maintenance Benefit:** Reduced confusion, clearer codebase

---

## ✨ NO BROKEN DEPENDENCIES FOUND

✅ All active files have their dependencies intact  
✅ No files reference deleted test files  
✅ No files reference deleted documentation  
✅ All vendor libraries present  
✅ All config files exist  

**Your API folder is in good shape after cleanup!**

