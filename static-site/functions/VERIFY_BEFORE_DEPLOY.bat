@echo off
echo ========================================
echo  Pre-Deployment Verification
echo ========================================
echo.

echo Checking required files...
echo.

if exist "index.js" (
    echo [OK] index.js found
) else (
    echo [FAIL] index.js NOT found
    goto :error
)

if exist "coupon-usage-tracker.js" (
    echo [OK] coupon-usage-tracker.js found
) else (
    echo [FAIL] coupon-usage-tracker.js NOT found
    goto :error
)

if exist "package.json" (
    echo [OK] package.json found
) else (
    echo [FAIL] package.json NOT found
    goto :error
)

echo.
echo Checking for coupon tracker exports in index.js...
findstr /C:"coupon-usage-tracker" index.js >nul
if errorlevel 1 (
    echo [FAIL] coupon-usage-tracker import NOT found in index.js
    goto :error
) else (
    echo [OK] coupon-usage-tracker imported
)

findstr /C:"onOrderCreated" index.js >nul
if errorlevel 1 (
    echo [FAIL] onOrderCreated export NOT found in index.js
    goto :error
) else (
    echo [OK] onOrderCreated exported
)

findstr /C:"incrementCouponUsageHttp" index.js >nul
if errorlevel 1 (
    echo [FAIL] incrementCouponUsageHttp export NOT found in index.js
    goto :error
) else (
    echo [OK] incrementCouponUsageHttp exported
)

findstr /C:"reprocessOrderCouponsHttp" index.js >nul
if errorlevel 1 (
    echo [FAIL] reprocessOrderCouponsHttp export NOT found in index.js
    goto :error
) else (
    echo [OK] reprocessOrderCouponsHttp exported
)

echo.
echo ========================================
echo  ALL CHECKS PASSED!
echo ========================================
echo.
echo Everything is ready for deployment.
echo.
echo Next step: Run this command to deploy:
echo   firebase deploy --only functions
echo.
echo Or run: DEPLOY.bat
echo.
pause
exit /b 0

:error
echo.
echo ========================================
echo  VERIFICATION FAILED
echo ========================================
echo.
echo Please ensure you are in the correct directory:
echo   C:\Users\lohit\OneDrive\Documents\ATTRAL\Projects\eCommerce\static-site\functions
echo.
pause
exit /b 1

