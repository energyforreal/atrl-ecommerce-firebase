# Order Creation Fix Plan

## Problem Identified
Order creation in Firestore is currently broken. Analysis shows changes between working version (commit 05af57c) and current version.

## Key Issues Found

### 1. Firestore SDK Check Changed
**Working Version (05af57c):**
- Checked for `Kreait\Firebase\Factory`
- Had fallback to SQLite if Firebase SDK not available

**Current Broken Version:**
- Checks for `Google\Cloud\Firestore\FirestoreClient`
- No fallback - throws exception if not available
- This might be causing initialization failures

### 2. Webhook User ID Extraction
**Working Version:**
- Set `user_id` to null

**Current Version:**
- Extracts `user_id` from `$notes['uid']`
- Moved $notes extraction before usage

### 3. Possible Missing Dependencies
The SDK change suggests a dependency might not be properly installed.

## Fix Strategy

1. **Check which Firebase SDK is actually available**
2. **Restore proper SDK initialization with fallback**
3. **Ensure webhook properly saves orders**
4. **Add better error logging**

## Files to Fix
- `static-site/api/firestore_order_manager.php`
- `static-site/api/webhook.php` (verify current changes are correct)

