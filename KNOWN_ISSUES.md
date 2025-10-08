# 🐛 Known Issues

## Current Issues

### ⚠️ Order Saving in Firestore Not Working
**Status:** 🔴 Active Issue  
**Priority:** High  
**Date Reported:** October 8, 2025

**Description:**
Orders are not being saved to Firestore properly.

**Impact:**
- Orders may not persist in the database
- Could affect order tracking and management
- May impact customer dashboard and admin panel

**Potential Causes to Investigate:**
1. Firebase service account permissions
2. Firestore security rules configuration
3. API endpoint connectivity issues
4. Data validation errors
5. Webhook integration problems

**Files to Check:**
- `static-site/api/firestore_order_manager.php`
- `static-site/api/order_manager.php`
- `static-site/api/webhook.php`
- `firestore-rules-secure.rules`
- Firebase service account configuration

**Next Steps:**
1. Check Firebase console for error logs
2. Verify Firestore security rules allow writes
3. Test webhook endpoint manually
4. Check service account permissions
5. Review order creation flow in `order-success.html`

**Related Documentation:**
- `FIRESTORE_ONLY_SYSTEM.md`
- `ORDERS_TROUBLESHOOTING_GUIDE.md`
- `PRIMARY_WEBHOOK_SETUP.md`

---

## Resolved Issues

_No resolved issues yet._

---

## Notes

- Please update this file when investigating or resolving issues
- Add new issues with date and description
- Move resolved issues to the "Resolved Issues" section with resolution details

