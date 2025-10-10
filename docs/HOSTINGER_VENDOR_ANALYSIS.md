# Vendor Directory Analysis for Hostinger Deployment

## Total Vendor Size Breakdown

Based on analysis of `static-site/api/vendor/`:

| Package | Size (MB) | Essential? | Notes |
|---------|-----------|------------|-------|
| **google/** | **11.94** | **YES** | **Firestore & Firebase** |
| └─ cloud-storage | 5.97 | OPTIONAL | Only if using Firebase Storage |
| └─ proto-client | 2.38 | YES | Required for Firestore |
| └─ gax | 1.12 | YES | Google API extensions |
| └─ cloud-core | 0.78 | YES | Core Google Cloud |
| └─ auth | 0.78 | YES | Authentication |
| └─ protobuf | 0.62 | YES | Protocol buffers |
| └─ cloud-firestore | 0.20 | **YES** | **Firestore client** |
| └─ crc32 | 0.07 | YES | Checksums |
| **guzzlehttp/** | **1.75** | **YES** | **HTTP client** |
| **monolog/** | **1.65** | **YES** | **Logging** |
| **phpmailer/** | **1.25** | **YES** | **Email sending** |
| **kreait/** | **1.06** | **YES** | **Firebase PHP SDK** |
| **lcobucci/** | **0.78** | **YES** | **JWT tokens** |
| **composer/** | **0.64** | **YES** | **Autoloader** |
| **ramsey/** | **0.37** | YES | UUID generation |
| **grpc/** | **0.33** | OPTIONAL | gRPC protocol (fallback exists) |
| **mtdowling/** | **0.24** | YES | JMESPath queries |
| **symfony/** | **0.15** | YES | Polyfills |
| **firebase/** | **0.13** | YES | Firebase JWT |
| **psr/** | **0.10** | YES | PSR standards |
| **rize/** | **0.10** | YES | URI templates |
| **beste/** | **0.05** | YES | Utilities |
| **paragonie/** | **0.05** | YES | Random compatibility |
| **riverline/** | **0.04** | YES | Multipart parser |
| **bin/** | **0.01** | NO | CLI tools (not needed) |
| **fig/** | **0.01** | YES | HTTP message utils |
| **ralouphie/** | **0.01** | YES | Get headers |
| **stella-maris/** | **0.01** | YES | Clock interface |
| **autoload.php** | **0.001** | **YES** | **Critical autoloader** |
| **TOTAL** | **~20.67 MB** | | |

## ❌ Can You Skip Uploading vendor/?

### Answer: **NO, but you have options**

**Option 1: Upload Complete vendor/ (Recommended) ✅**
- **Pros:**
  - Guaranteed to work immediately
  - No server-side commands needed
  - No dependency conflicts
  - Fastest deployment
- **Cons:**
  - Larger upload size (~21 MB)
  - Takes longer to upload via FTP

**Option 2: Use Composer on Hostinger ✅**
- **Pros:**
  - Smaller upload (only need composer.json and composer.lock)
  - Cleaner deployment
  - Professional approach
- **Cons:**
  - Requires SSH access to Hostinger
  - Must run `composer install` on server
  - Hostinger must have Composer installed
- **Process:**
  ```bash
  # Upload only:
  - composer.json
  - composer.lock
  
  # Then SSH into Hostinger:
  cd public_html/api
  composer install --no-dev --optimize-autoloader
  ```

**Option 3: Hybrid Approach (Best Balance) ⭐**
- Upload vendor/ with development files excluded
- Exclude test/doc files from vendor packages

## 🎯 Recommended Approach for Hostinger

### RECOMMENDED: Upload Complete vendor/ Directory

**Why?**
1. Hostinger shared hosting may not have Composer CLI access
2. Eliminates dependency installation errors
3. Faster to get site working
4. Only 21 MB - reasonable for one-time upload

**How to Optimize:**

### Files You CAN Exclude from vendor/ to Save Space:

```
vendor/
├── */tests/           ← DELETE (test files)
├── */test/            ← DELETE (test files)
├── */docs/            ← DELETE (documentation)
├── */doc/             ← DELETE (documentation)
├── */.git/            ← DELETE (git files)
├── */.github/         ← DELETE (GitHub files)
├── */examples/        ← DELETE (example files)
├── */demo/            ← DELETE (demo files)
├── *.md               ← DELETE (markdown docs)
├── *.dist             ← DELETE (distribution configs)
├── phpunit.xml*       ← DELETE (test configs)
├── phpstan.neon*      ← DELETE (static analysis)
├── psalm.xml*         ← DELETE (static analysis)
├── .travis.yml        ← DELETE (CI configs)
├── .editorconfig      ← DELETE (editor configs)
├── CHANGELOG*         ← DELETE (changelogs)
├── LICENSE*           ← KEEP (legal requirement)
└── README*            ← DELETE (documentation)
```

### Estimated Savings: **~30-40%** (reduces to ~13-15 MB)

## 🚀 Step-by-Step: Optimized Upload

### Method 1: Upload Pre-Cleaned vendor/ (Easiest)

**Before Upload - Clean vendor/ locally:**

```powershell
# Run in static-site/api/vendor/ directory
# Remove test directories
Get-ChildItem -Path . -Recurse -Directory -Include "tests","test","Tests" | Remove-Item -Recurse -Force

# Remove docs directories
Get-ChildItem -Path . -Recurse -Directory -Include "docs","doc","documentation" | Remove-Item -Recurse -Force

# Remove examples
Get-ChildItem -Path . -Recurse -Directory -Include "examples","demo","demos" | Remove-Item -Recurse -Force

# Remove markdown files
Get-ChildItem -Path . -Recurse -File -Include "*.md" | Remove-Item -Force

# Remove config files
Get-ChildItem -Path . -Recurse -File -Include "*.dist","*.xml.dist","phpunit.xml","phpstan.neon","psalm.xml" | Remove-Item -Force

# Remove CI files
Get-ChildItem -Path . -Recurse -File -Include ".travis.yml",".editorconfig","renovate.json" | Remove-Item -Force
```

**After Cleaning:**
- vendor/ size: ~13-15 MB (vs 21 MB)
- Still contains all functional code
- Upload via FTP/SFTP

### Method 2: Use Composer on Hostinger (Professional)

**Upload to Hostinger:**
```
static-site/api/
├── composer.json (20 lines, < 1 KB)
├── composer.lock (generated, ~200 KB)
└── (all your PHP files)
```

**SSH into Hostinger and run:**
```bash
cd public_html/api
composer install --no-dev --optimize-autoloader --no-scripts
```

**Pros:**
- Only ~200 KB upload (composer.lock)
- Clean, professional approach
- No bloat from dev dependencies

**Cons:**
- Requires SSH access
- Hostinger must have Composer
- Takes 2-3 minutes to install on server

## 📊 Package Dependency Analysis

### Critical Packages (MUST HAVE):

**For Firestore/Firebase:**
- ✅ google/cloud-firestore (0.20 MB)
- ✅ google/cloud-core (0.78 MB)
- ✅ google/auth (0.78 MB)
- ✅ google/gax (1.12 MB)
- ✅ google/proto-client (2.38 MB)
- ✅ google/protobuf (0.62 MB)
- ✅ kreait/firebase-php (1.06 MB)
- ✅ grpc/grpc (0.33 MB)

**For Email (PHPMailer):**
- ✅ phpmailer/phpmailer (1.25 MB)

**For HTTP Requests:**
- ✅ guzzlehttp/guzzle (1.75 MB)
- ✅ guzzlehttp/promises
- ✅ guzzlehttp/psr7

**For Logging:**
- ✅ monolog/monolog (1.65 MB)

**For Auth/Tokens:**
- ✅ lcobucci/jwt (0.78 MB)
- ✅ firebase/php-jwt (0.13 MB)

**Core Dependencies:**
- ✅ composer/* (0.64 MB) - autoloader
- ✅ psr/* (0.10 MB) - standards
- ✅ symfony/* (0.15 MB) - polyfills

### Optional/Potentially Unused:

**Google Cloud Storage (5.97 MB):**
- ❓ **Do you upload files to Firebase Storage?**
- If NO: Can potentially remove (but risky)
- If YES: Must keep

**gRPC (0.33 MB):**
- Used for faster Firestore connections
- Has fallback to HTTP/REST
- **Recommendation:** Keep it (small size, better performance)

## 🎯 Final Recommendations

### For Hostinger Deployment:

**Option A: Quick & Safe (Recommended for beginners) ⭐**
```
1. Clean vendor/ locally (remove tests/docs)
2. Upload entire cleaned vendor/ (~13-15 MB)
3. Upload time: 5-10 minutes on decent connection
4. Site works immediately
```

**Option B: Professional (Recommended if you have SSH access) ⭐⭐**
```
1. Upload composer.json and composer.lock only
2. SSH into Hostinger
3. Run: composer install --no-dev --optimize-autoloader
4. Smaller upload, cleaner deployment
```

**Option C: Hybrid (Best of both worlds) ⭐⭐⭐**
```
1. Upload vendor/ as backup
2. Also upload composer.json/composer.lock
3. If composer fails, vendor/ is already there
4. Maximum reliability
```

## 📝 Vendor Cleanup Script

Save this as `clean-vendor.ps1`:

```powershell
# Clean vendor directory for production deployment
$vendorPath = "static-site/api/vendor"

Write-Host "Cleaning vendor directory for production..." -ForegroundColor Green

# Directories to remove
$excludeDirs = @("tests", "test", "Tests", "docs", "doc", "examples", "demo", "demos", ".git", ".github")
foreach ($dir in $excludeDirs) {
    Get-ChildItem -Path $vendorPath -Recurse -Directory -Include $dir -ErrorAction SilentlyContinue | Remove-Item -Recurse -Force
    Write-Host "Removed: $dir directories" -ForegroundColor Yellow
}

# Files to remove
$excludeFiles = @("*.md", "*.dist", "phpunit.xml*", "phpstan.neon*", "psalm.xml*", ".travis.yml", ".editorconfig", "renovate.json", "CHANGELOG*")
foreach ($pattern in $excludeFiles) {
    Get-ChildItem -Path $vendorPath -Recurse -File -Include $pattern -ErrorAction SilentlyContinue | Remove-Item -Force
    Write-Host "Removed: $pattern files" -ForegroundColor Yellow
}

# Calculate new size
$size = (Get-ChildItem $vendorPath -Recurse -File | Measure-Object -Property Length -Sum).Sum / 1MB
Write-Host "`nCleaned vendor size: $([math]::Round($size, 2)) MB" -ForegroundColor Green
Write-Host "Ready for Hostinger upload!" -ForegroundColor Green
```

Run with:
```powershell
.\clean-vendor.ps1
```

## Summary

**Can you omit vendor/ when uploading?**
- ❌ NO - Your application needs these dependencies to run
- ✅ YES - You can use Composer on Hostinger instead
- ⭐ BEST - Upload cleaned vendor/ (remove tests/docs) for fastest deployment

**Final Upload Size (optimized):**
- vendor/ (cleaned): ~13-15 MB
- Your code: ~5 MB
- Assets: ~20 MB
- **Total: ~38-40 MB** (very reasonable for Hostinger)

**My Recommendation:**
Upload the complete vendor/ directory (cleaned of tests/docs). It's the most reliable method for Hostinger shared hosting.


