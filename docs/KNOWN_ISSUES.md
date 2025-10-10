# 🐛 Known Issues

## Current Issues

### ⚠️ Order Saving in Firestore Not Working
**Status:** 🟡 Root Cause Identified - Fix Ready  
**Priority:** High  
**Date Reported:** October 8, 2025  
**Date Diagnosed:** October 9, 2025

**Description:**
Orders are not being saved to Firestore due to missing Google Cloud SDK dependency.

**ROOT CAUSE IDENTIFIED:**
❌ **Missing Dependency**: `Class "Google\ApiCore\Serializer" not found`
- Location: `vendor/google/cloud-core/src/GrpcRequestWrapper.php:93`
- Cause: Missing `google/gax` package in Composer dependencies
- Impact: FirestoreClient cannot initialize → HTTP 500 errors

**Impact:**
- ❌ HTTP 500 errors on all Firestore operations
- ❌ Orders not persisting in database
- ❌ Order tracking not working
- ❌ Customer dashboard shows no orders
- ❌ Webhook fails to create orders

**THE FIX:**
```bash
# On your server (SSH access required)
cd static-site/api
composer require google/gax:^1.15
composer update google/cloud-firestore
```

**Files to Check:**
- ✅ `static-site/api/firestore_order_manager.php` - Code is correct
- ✅ `static-site/api/webhook.php` - Code is correct
- ✅ `static-site/api/config.php` - Configuration is correct
- ✅ `static-site/api/firebase-service-account.json` - Service account is correct
- ❌ `static-site/api/vendor/` - Dependencies incomplete

**Testing Tools Created:**
- `static-site/comprehensive_test.html` - Full test suite
- `static-site/api/simple_order_test.php` - Minimal order API
- `diagnose_500_errors.php` - Diagnostic script
- Browser console test method (see documentation)

**Related Documentation:**
- `FINAL_TEST_RESULTS_AND_SOLUTION.md` - Complete analysis
- `ORDER_CREATION_FINAL_ANALYSIS.md` - Detailed findings
- `COMPREHENSIVE_SOLUTION.md` - Step-by-step fix guide

---

## Resolved Issues

_No resolved issues yet._

---

## Notes

- Please update this file when investigating or resolving issues
- Add new issues with date and description
- Move resolved issues to the "Resolved Issues" section with resolution details

