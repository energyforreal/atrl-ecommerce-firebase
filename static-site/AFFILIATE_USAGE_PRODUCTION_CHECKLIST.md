# ✅ Affiliate Usage - Production Integration Checklist

## What we implemented

- Idempotent coupon increment in `static-site/api/firestore_order_manager.php` using per-payment guard docs.
- New `affiliate_usage` subcollection per order with an idempotent guard keyed by `sha1(paymentId|coupon)`. Each entry includes:
  - `orderId`, `razorpayPaymentId`, `couponCode`, `affiliateCode`, `amount`, `customerEmail`, `createdAt`.
- No changes to public API contract; existing flows continue working.

## Firestore structure

Collections:

- `orders/{orderDocId}`
  - Fields: `orderId`, `razorpayPaymentId`, `pricing`, `payment.url_params.ref`, `coupons[]`, etc.
  - Subcollection `couponIncrements/{guardKey}`: idempotency for coupon increments.
  - Subcollection `affiliate_usage/{guardKey}`: one log per payment+coupon.

- `coupons/{couponDocId}`
  - Fields: `code`, `usageCount`, `payoutUsage`, `isAffiliateCoupon`, `affiliateCode`, `updatedAt`.

## Deployment notes

Ensure:

- Firebase service account present: `static-site/api/firebase-service-account.json` (not tracked).
- PHP extensions required: curl, json, openssl.
- Server clock is correct (timestamps).

## Observability

Server logs include messages prefixed with:

- `FIRESTORE COUPON USAGE:` for increments
- `FIRESTORE AFFILIATE USAGE:` for affiliate usage entries
- `FIRESTORE API:` for request/response lifecycle

## Post-deploy validation

1. Place a test order with `payment.url_params.ref` or `coupons[].affiliateCode` set.
2. Verify coupon doc counters incremented.
3. Open the created `orders/{doc}` and confirm `affiliate_usage/{guardKey}` exists.
4. Re-send the same payload: counters should not double-increment; usage log should not duplicate.

## Rollback

Revert edits in `firestore_order_manager.php` if needed; data created is additive and safe.
