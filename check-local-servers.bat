@echo off
echo Checking for local web servers on your system...
echo.

REM Check for XAMPP
if exist "C:\xampp" (
    echo ✅ XAMPP found at C:\xampp
    echo    - Apache: C:\xampp\apache\bin\httpd.exe
    echo    - PHP: C:\xampp\php\php.exe
    echo    - All extensions should be enabled
    echo.
) else (
    echo ❌ XAMPP not found at C:\xampp
    echo.
)

REM Check for WAMP
if exist "C:\wamp64" (
    echo ✅ WAMP64 found at C:\wamp64
    echo    - Apache: C:\wamp64\bin\apache\apache*\bin\httpd.exe
    echo    - PHP: C:\wamp64\bin\php\php*\php.exe
    echo.
) else (
    echo ❌ WAMP64 not found at C:\wamp64
    echo.
)

REM Check for WAMP (32-bit)
if exist "C:\wamp" (
    echo ✅ WAMP found at C:\wamp
    echo    - Apache: C:\wamp\bin\apache\apache*\bin\httpd.exe
    echo    - PHP: C:\wamp\bin\php\php*\php.exe
    echo.
) else (
    echo ❌ WAMP not found at C:\wamp
    echo.
)

REM Check for Laragon
if exist "C:\laragon" (
    echo ✅ Laragon found at C:\laragon
    echo    - Apache: C:\laragon\bin\apache\httpd-*\bin\httpd.exe
    echo    - PHP: C:\laragon\bin\php\php-*\php.exe
    echo.
) else (
    echo ❌ Laragon not found at C:\laragon
    echo.
)

REM Check current PHP location
echo Current PHP installation:
where php 2>nul
if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✅ PHP found in PATH
) else (
    echo ❌ PHP not found in PATH
)

echo.
echo ==========================================
echo   RECOMMENDATION
echo ==========================================
echo.
echo If you don't have XAMPP/WAMP/Laragon:
echo   → Install XAMPP (easiest solution)
echo   → Download: https://www.apachefriends.org/download.html
echo.
echo If you have one of these servers:
echo   → Use it instead of your current PHP
echo   → All extensions will be enabled
echo.
pause
