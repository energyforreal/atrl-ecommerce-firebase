Write-Host "========================================" -ForegroundColor Cyan
Write-Host " ATTRAL Coupon Tracking Functions" -ForegroundColor Cyan
Write-Host " Deployment Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Check if in correct directory
if (-not (Test-Path "index.js")) {
    Write-Host "ERROR: index.js not found!" -ForegroundColor Red
    Write-Host "Please run this script from the functions directory" -ForegroundColor Yellow
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "✓ Found index.js" -ForegroundColor Green
Write-Host ""

# Install dependencies
Write-Host "Installing dependencies..." -ForegroundColor Yellow
npm install
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: npm install failed" -ForegroundColor Red
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "✓ Dependencies installed" -ForegroundColor Green
Write-Host ""

# Deploy to Firebase
Write-Host "Deploying to Firebase..." -ForegroundColor Yellow
Write-Host "This will deploy 3 new functions:" -ForegroundColor Cyan
Write-Host "  - onOrderCreated" -ForegroundColor White
Write-Host "  - incrementCouponUsageHttp" -ForegroundColor White
Write-Host "  - reprocessOrderCouponsHttp" -ForegroundColor White
Write-Host ""

firebase deploy --only functions
if ($LASTEXITCODE -ne 0) {
    Write-Host ""
    Write-Host "ERROR: Deployment failed" -ForegroundColor Red
    Write-Host ""
    Write-Host "Common issues:" -ForegroundColor Yellow
    Write-Host "  1. Not logged in - Run: firebase login" -ForegroundColor White
    Write-Host "  2. Wrong project - Run: firebase use e-commerce-1d40f" -ForegroundColor White
    Write-Host "  3. Missing permissions - Check Firebase console" -ForegroundColor White
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host " ✓ DEPLOYMENT SUCCESSFUL!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Check your Firebase Console:" -ForegroundColor Cyan
Write-Host "https://console.firebase.google.com/project/e-commerce-1d40f/functions" -ForegroundColor White
Write-Host ""

Read-Host "Press Enter to exit"

