@echo off
REM Quick Start Script for ATTRAL E-Commerce Setup
REM Run this after adding firebase-service-account.json

echo.
echo ==========================================
echo   ATTRAL E-Commerce Setup Validator
echo ==========================================
echo.

REM Check if PHP is available
where php >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] PHP not found in PATH
    echo Please install PHP or add it to your system PATH
    pause
    exit /b 1
)

echo [1/3] Validating Firebase Service Account...
echo.
php validate-firebase-setup.php
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Firebase validation failed!
    echo Please follow instructions in GET_FIREBASE_KEY.md
    pause
    exit /b 1
)

echo.
echo.
echo [2/3] Testing Email Configuration...
echo.
php test-email-sending.php
if %ERRORLEVEL% NEQ 0 (
    echo.
    echo [ERROR] Email test failed!
    echo Check SMTP credentials in static-site/api/config.php
    pause
    exit /b 1
)

echo.
echo.
echo [3/3] All validations complete!
echo.
echo ==========================================
echo   ✅ SETUP COMPLETE!
echo ==========================================
echo.
echo Next steps:
echo   1. Place a test order on your website
echo   2. Check your email for confirmation
echo   3. Check Firebase Console for the order
echo.
echo Firebase Console: https://console.firebase.google.com/project/e-commerce-1d40f/firestore
echo.
pause

