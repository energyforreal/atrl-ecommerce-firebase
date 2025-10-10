# Clean Vendor Directory for Hostinger Production Deployment
# This script removes unnecessary files from vendor/ to reduce upload size
# Run this before uploading to Hostinger

$vendorPath = "static-site/api/vendor"
$backupPath = "static-site/api/vendor-backup"

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  Vendor Cleanup for Production" -ForegroundColor Cyan
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# Check if vendor exists
if (-not (Test-Path $vendorPath)) {
    Write-Host "ERROR: vendor/ directory not found at $vendorPath" -ForegroundColor Red
    Write-Host "Please run 'composer install' first" -ForegroundColor Yellow
    exit 1
}

# Calculate original size
Write-Host "Calculating original size..." -ForegroundColor Yellow
$originalSize = (Get-ChildItem $vendorPath -Recurse -File -ErrorAction SilentlyContinue | Measure-Object -Property Length -Sum).Sum / 1MB
Write-Host "Original vendor/ size: $([math]::Round($originalSize, 2)) MB" -ForegroundColor White
Write-Host ""

# Ask for confirmation
$response = Read-Host "Create backup before cleaning? (Y/n)"
if ($response -ne 'n' -and $response -ne 'N') {
    Write-Host "Creating backup..." -ForegroundColor Yellow
    if (Test-Path $backupPath) {
        Remove-Item $backupPath -Recurse -Force
    }
    Copy-Item $vendorPath $backupPath -Recurse
    Write-Host "Backup created at: $backupPath" -ForegroundColor Green
    Write-Host ""
}

Write-Host "Cleaning vendor directory..." -ForegroundColor Yellow
Write-Host ""

$removedCount = 0

# 1. Remove test directories
Write-Host "[1/7] Removing test directories..." -ForegroundColor Cyan
$testDirs = Get-ChildItem -Path $vendorPath -Recurse -Directory -Include "tests","test","Tests","Test" -ErrorAction SilentlyContinue
$testDirs | ForEach-Object { 
    Remove-Item $_.FullName -Recurse -Force -ErrorAction SilentlyContinue
    $removedCount++
}
Write-Host "  Removed $($testDirs.Count) test directories" -ForegroundColor Gray

# 2. Remove documentation directories
Write-Host "[2/7] Removing documentation directories..." -ForegroundColor Cyan
$docDirs = Get-ChildItem -Path $vendorPath -Recurse -Directory -Include "docs","doc","documentation","Documentation" -ErrorAction SilentlyContinue
$docDirs | ForEach-Object { 
    Remove-Item $_.FullName -Recurse -Force -ErrorAction SilentlyContinue
    $removedCount++
}
Write-Host "  Removed $($docDirs.Count) documentation directories" -ForegroundColor Gray

# 3. Remove example/demo directories
Write-Host "[3/7] Removing example/demo directories..." -ForegroundColor Cyan
$exampleDirs = Get-ChildItem -Path $vendorPath -Recurse -Directory -Include "examples","example","demo","demos","samples" -ErrorAction SilentlyContinue
$exampleDirs | ForEach-Object { 
    Remove-Item $_.FullName -Recurse -Force -ErrorAction SilentlyContinue
    $removedCount++
}
Write-Host "  Removed $($exampleDirs.Count) example/demo directories" -ForegroundColor Gray

# 4. Remove Git directories
Write-Host "[4/7] Removing Git directories..." -ForegroundColor Cyan
$gitDirs = Get-ChildItem -Path $vendorPath -Recurse -Directory -Include ".git",".github" -ErrorAction SilentlyContinue
$gitDirs | ForEach-Object { 
    Remove-Item $_.FullName -Recurse -Force -ErrorAction SilentlyContinue
    $removedCount++
}
Write-Host "  Removed $($gitDirs.Count) Git directories" -ForegroundColor Gray

# 5. Remove markdown files
Write-Host "[5/7] Removing markdown files..." -ForegroundColor Cyan
$mdFiles = Get-ChildItem -Path $vendorPath -Recurse -File -Include "*.md","README*","CHANGELOG*","CONTRIBUTING*","CODE_OF_CONDUCT*" -ErrorAction SilentlyContinue
$mdFiles | ForEach-Object { 
    Remove-Item $_.FullName -Force -ErrorAction SilentlyContinue
    $removedCount++
}
Write-Host "  Removed $($mdFiles.Count) markdown/readme files" -ForegroundColor Gray

# 6. Remove test/config files
Write-Host "[6/7] Removing test/config files..." -ForegroundColor Cyan
$configFiles = Get-ChildItem -Path $vendorPath -Recurse -File -Include "*.dist","phpunit.xml*","phpstan.neon*","psalm.xml*",".travis.yml",".editorconfig","renovate.json",".scrutinizer.yml" -ErrorAction SilentlyContinue
$configFiles | ForEach-Object { 
    Remove-Item $_.FullName -Force -ErrorAction SilentlyContinue
    $removedCount++
}
Write-Host "  Removed $($configFiles.Count) test/config files" -ForegroundColor Gray

# 7. Remove other unnecessary files
Write-Host "[7/7] Removing other unnecessary files..." -ForegroundColor Cyan
$otherFiles = Get-ChildItem -Path $vendorPath -Recurse -File -Include ".gitignore",".gitattributes",".php_cs*","Makefile","*.sh" -ErrorAction SilentlyContinue
$otherFiles | ForEach-Object { 
    Remove-Item $_.FullName -Force -ErrorAction SilentlyContinue
    $removedCount++
}
Write-Host "  Removed $($otherFiles.Count) other files" -ForegroundColor Gray

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan

# Calculate new size
$newSize = (Get-ChildItem $vendorPath -Recurse -File -ErrorAction SilentlyContinue | Measure-Object -Property Length -Sum).Sum / 1MB
$saved = $originalSize - $newSize
$percentSaved = ($saved / $originalSize) * 100

Write-Host "CLEANUP COMPLETE!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Original size:  $([math]::Round($originalSize, 2)) MB" -ForegroundColor White
Write-Host "New size:       $([math]::Round($newSize, 2)) MB" -ForegroundColor Green
Write-Host "Space saved:    $([math]::Round($saved, 2)) MB ($([math]::Round($percentSaved, 1))%)" -ForegroundColor Yellow
Write-Host ""
Write-Host "Total items removed: $removedCount" -ForegroundColor Gray
Write-Host ""
Write-Host "✅ Vendor directory is ready for Hostinger upload!" -ForegroundColor Green
Write-Host ""

if ($response -ne 'n' -and $response -ne 'N') {
    Write-Host "To restore backup:" -ForegroundColor Yellow
    Write-Host "  Remove-Item '$vendorPath' -Recurse -Force" -ForegroundColor Gray
    Write-Host "  Move-Item '$backupPath' '$vendorPath'" -ForegroundColor Gray
    Write-Host ""
}


