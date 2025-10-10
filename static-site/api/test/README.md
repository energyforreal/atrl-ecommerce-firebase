# 🧪 Firestore REST API Test Suite

## Overview

This directory contains test files for validating the Firestore REST API migration.

## Test Files

### 1. `test_firestore_rest_client.php`

Tests the core Firestore REST API client functionality.

**Tests Included**:
- ✅ OAuth2 token generation via JWT
- ✅ Token caching performance
- ✅ Document write operations
- ✅ Document read operations
- ✅ Document update operations
- ✅ Atomic field increments
- ✅ Document queries with filters
- ✅ Document deletion
- ✅ Performance benchmarks

**Run**:
```bash
php test_firestore_rest_client.php
```

### 2. `test_order_creation.php`

Tests the complete order creation flow.

**Tests Included**:
- ✅ Order number generation
- ✅ Test order creation
- ✅ Order retrieval by payment ID
- ✅ Order status updates
- ✅ Status history logging
- ✅ Idempotency validation
- ✅ Order deletion

**Run**:
```bash
php test_order_creation.php
```

## Running Tests

### Local Testing

```bash
# Navigate to test directory
cd static-site/api/test

# Run all tests
php test_firestore_rest_client.php
php test_order_creation.php
```

### Remote Testing (Hostinger)

```bash
# Via browser
https://yourdomain.com/api/test/test_firestore_rest_client.php
https://yourdomain.com/api/test/test_order_creation.php

# Via SSH (if available)
php /home/username/public_html/api/test/test_firestore_rest_client.php
```

## Expected Results

All tests should display:
- ✅ Green checkmarks for passed tests
- ❌ Red X marks for failed tests (with error details)
- ⚠️ Warning symbols for skipped tests

Example output:
```
🧪 FIRESTORE REST CLIENT TEST SUITE
=====================================

✅ Service account file found
✅ FirestoreRestClient initialized

TEST 1: OAuth2 Token Generation
--------------------------------
✅ Access token generated (length: 234)
Token preview: ya29.c.b0Aaekm1K...

...
```

## Interpreting Results

### Success Indicators
- All OAuth2 token tests pass
- Documents write successfully
- Queries return expected results
- Performance < 2 seconds per operation

### Common Failures

**"Service account file not found"**
- Solution: Verify `firebase-service-account.json` exists in `/api` directory

**"Failed to obtain access token"**
- Solution: Check OpenSSL extension enabled
- Solution: Verify service account credentials valid

**"Firestore request failed (HTTP 403)"**
- Solution: Check Firestore rules allow service account access
- Solution: Verify project ID is correct

**"Performance > 5 seconds"**
- Solution: Check Hostinger server performance
- Solution: Verify token caching is working
- Solution: Check network latency to Google servers

## Test Data

Tests create temporary documents in these collections:
- `test_collection` - General REST API tests
- `test_perf` - Performance benchmark tests
- `orders` - Order creation tests (deleted after test)

**Note**: Test documents are automatically deleted after tests complete.

## Cleanup

Tests clean up after themselves, but if interrupted:

```bash
# Manual cleanup via Firebase Console
1. Go to Firestore Database
2. Navigate to test_collection
3. Delete all documents
4. Navigate to test_perf
5. Delete all documents
```

## Security Notes

- ⚠️ **Never commit these test files to public repositories**
- ⚠️ **Test files contain real Firebase project IDs**
- ⚠️ **Remove test files from production after migration complete**
- ✅ **Tests use service account authentication (secure)**
- ✅ **No sensitive data exposed in test output**

## Debugging

Enable verbose logging:

```php
// Add at top of test file
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
```

Check logs:
```bash
# Hostinger error logs
tail -f /home/username/logs/error.log | grep "FIRESTORE REST"
```

## Performance Benchmarks

Expected performance on Hostinger shared hosting:

| Operation | Target | Excellent | Acceptable | Poor |
|-----------|--------|-----------|------------|------|
| Token Generation | First-time | < 1s | 1-2s | > 2s |
| Token Cache | Subsequent | < 0.01s | < 0.1s | > 0.1s |
| Document Write | Single | < 1s | 1-2s | > 2s |
| Document Read | Single | < 0.5s | 0.5-1.5s | > 1.5s |
| Query | Simple | < 1s | 1-2s | > 2s |
| Atomic Increment | Single | < 1s | 1-2s | > 2s |

## Next Steps

After tests pass:

1. ✅ Review `DEPLOYMENT_GUIDE.md`
2. ✅ Upload files to Hostinger
3. ✅ Run tests on live server
4. ✅ Test with real Razorpay payment
5. ✅ Monitor for 24-48 hours
6. ✅ Remove SDK dependencies

## Support

- See `MIGRATION_SUMMARY.md` for comprehensive documentation
- See `DEPLOYMENT_GUIDE.md` for deployment instructions
- Check Firebase Console for Firestore data
- Review error logs for detailed diagnostics

---

**Last Updated**: 2025-10-10

