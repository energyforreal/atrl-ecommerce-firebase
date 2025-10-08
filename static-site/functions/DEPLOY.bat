@echo off
echo ========================================
echo  ATTRAL Coupon Tracking Functions
echo  Deployment Script
echo ========================================
echo.

echo Checking if we're in the right directory...
if not exist "index.js" (
    echo ERROR: index.js not found!
    echo Please run this script from the functions directory
    pause
    exit /b 1
)

echo ✓ Found index.js
echo.

echo Installing dependencies...
call npm install
if errorlevel 1 (
    echo ERROR: npm install failed
    pause
    exit /b 1
)

echo ✓ Dependencies installed
echo.

echo Deploying to Firebase...
echo This will deploy 3 new functions:
echo   - onOrderCreated
echo   - incrementCouponUsageHttp
echo   - reprocessOrderCouponsHttp
echo.

call firebase deploy --only functions
if errorlevel 1 (
    echo ERROR: Deployment failed
    echo.
    echo Common issues:
    echo   1. Not logged in - Run: firebase login
    echo   2. Wrong project - Run: firebase use e-commerce-1d40f
    echo   3. Missing permissions - Check Firebase console
    pause
    exit /b 1
)

echo.
echo ========================================
echo  ✓ DEPLOYMENT SUCCESSFUL!
echo ========================================
echo.
echo Check your Firebase Console:
echo https://console.firebase.google.com/project/e-commerce-1d40f/functions
echo.

pause

