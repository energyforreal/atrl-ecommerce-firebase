# PowerShell Script to Help Deploy config.php to Production Server
# Usage: Right-click and "Run with PowerShell" OR run in PowerShell: .\deploy-config.ps1

Write-Host ""
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "   ATTRAL Config.php Deployment Helper" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

# Check if config.php exists
$configPath = "static-site\api\config.php"
if (Test-Path $configPath) {
    Write-Host "✅ Found config.php at: $configPath" -ForegroundColor Green
    
    # Show file info
    $fileInfo = Get-Item $configPath
    Write-Host "   File Size: $($fileInfo.Length) bytes" -ForegroundColor Gray
    Write-Host "   Last Modified: $($fileInfo.LastWriteTime)" -ForegroundColor Gray
    Write-Host ""
    
    # Verify it contains credentials
    $content = Get-Content $configPath -Raw
    if ($content -match "rzp_live_RKD5kwFAOZ05UD") {
        Write-Host "✅ File contains Razorpay credentials" -ForegroundColor Green
    } else {
        Write-Host "⚠️  Warning: File may not contain correct credentials" -ForegroundColor Yellow
    }
    Write-Host ""
    
} else {
    Write-Host "❌ ERROR: config.php not found!" -ForegroundColor Red
    Write-Host "   Expected location: $configPath" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please run this script from your project root directory." -ForegroundColor Yellow
    Write-Host "Current directory: $(Get-Location)" -ForegroundColor Yellow
    Write-Host ""
    Pause
    exit
}

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "   DEPLOYMENT OPTIONS" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Option 1: Manual Upload (RECOMMENDED)" -ForegroundColor Yellow
Write-Host "   1. Open your FTP client or Hostinger File Manager" -ForegroundColor White
Write-Host "   2. Navigate to: public_html/static-site/api/" -ForegroundColor White
Write-Host "   3. Upload: $configPath" -ForegroundColor White
Write-Host "   4. Test at: https://attral.in/api/test_config.php" -ForegroundColor White
Write-Host ""

Write-Host "Option 2: Use Pre-Configured Hardcoded Version" -ForegroundColor Yellow
Write-Host "   1. Upload: create_order_WITH_HARDCODED_CREDENTIALS.php" -ForegroundColor White
Write-Host "   2. Rename to: create_order.php" -ForegroundColor White
Write-Host "   3. This works immediately (credentials are hardcoded)" -ForegroundColor White
Write-Host ""

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "   TEST URLS" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "After uploading, test these URLs:" -ForegroundColor White
Write-Host ""
Write-Host "1. Config Test:" -ForegroundColor Yellow
Write-Host "   https://attral.in/api/test_config.php" -ForegroundColor Cyan
Write-Host ""
Write-Host "2. Payment Test:" -ForegroundColor Yellow
Write-Host "   http://localhost:8000/order.html?type=cart" -ForegroundColor Cyan
Write-Host ""

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "   HELPFUL COMMANDS" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Open File Location:" -ForegroundColor Yellow
Write-Host "   explorer.exe $(Split-Path $configPath)" -ForegroundColor White
Write-Host ""

Write-Host "Copy File Path to Clipboard:" -ForegroundColor Yellow
Write-Host "   (Get-Item '$configPath').FullName | Set-Clipboard" -ForegroundColor White
Write-Host ""

# Ask if user wants to open file location
Write-Host ""
$openLocation = Read-Host "Do you want to open the file location? (Y/N)"
if ($openLocation -eq "Y" -or $openLocation -eq "y") {
    explorer.exe (Split-Path $configPath)
    Write-Host "✅ Opened file location" -ForegroundColor Green
}

Write-Host ""
$copyPath = Read-Host "Copy file path to clipboard? (Y/N)"
if ($copyPath -eq "Y" -or $copyPath -eq "y") {
    $fullPath = (Get-Item $configPath).FullName
    $fullPath | Set-Clipboard
    Write-Host "✅ Path copied to clipboard: $fullPath" -ForegroundColor Green
}

Write-Host ""
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "   NEXT STEPS" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Upload config.php to your server" -ForegroundColor White
Write-Host "2. Visit: https://attral.in/api/test_config.php" -ForegroundColor White
Write-Host "3. Look for: '✅ SUCCESS'" -ForegroundColor White
Write-Host "4. Test payment flow" -ForegroundColor White
Write-Host "5. Delete test_config.php from server" -ForegroundColor White
Write-Host ""

# Offer to open test URL
Write-Host ""
$openTest = Read-Host "After uploading, press ENTER to open test URL (or N to skip)"
if ($openTest -ne "N" -and $openTest -ne "n") {
    Start-Process "https://attral.in/api/test_config.php"
    Write-Host "✅ Opened test URL in browser" -ForegroundColor Green
}

Write-Host ""
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "Script completed! Upload the file and test." -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

Pause

