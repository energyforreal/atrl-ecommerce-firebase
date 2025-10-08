@echo off
title ATTRAL eCommerce - Local Development Server
color 0A
setlocal enabledelayedexpansion

REM ========================================
REM    🚀 ATTRAL eCommerce Local Server
REM    Enhanced Development Server Setup
REM ========================================

echo.
echo ========================================
echo    🚀 ATTRAL eCommerce Local Server
echo    Enhanced Development Environment
echo ========================================
echo.

REM Configuration
set "PROJECT_ROOT=%~dp0"
set "STATIC_SITE=%PROJECT_ROOT%static-site"
set "DEFAULT_PORT=8000"
set "DEFAULT_HTTPS_PORT=8443"
set "ADMIN_USERNAME=attral"
set "ADMIN_PASSWORD=Rakeshmurali@10"

REM Check if static-site directory exists
if not exist "%STATIC_SITE%" (
    echo ❌ static-site directory not found at: %STATIC_SITE%
    echo.
    echo Please ensure this batch file is in the main project directory
    echo and the static-site folder exists.
    echo.
    echo Current directory: %PROJECT_ROOT%
    echo Expected structure:
    echo   %PROJECT_ROOT%
    echo   %PROJECT_ROOT%static-site\
    echo.
    pause
    exit /b 1
)

echo ✅ Found static-site directory: %STATIC_SITE%

REM Check if PHP is installed
echo.
echo 🔍 Checking PHP installation...
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP is not installed or not in PATH
    echo.
    echo Please install PHP from: https://windows.php.net/download/
    echo Or use XAMPP: https://www.apachefriends.org/download.html
    echo.
    pause
    exit /b 1
)

echo ✅ PHP is installed and accessible

REM Check if Node.js is available (for potential future use)
echo.
echo 🔍 Checking Node.js installation...
node --version >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Node.js is available
) else (
    echo ⚠️  Node.js not found (optional for some features)
)

REM Create necessary directories
echo.
echo 📁 Creating necessary directories...
mkdir "%STATIC_SITE%\ssl" 2>nul
mkdir "%STATIC_SITE%\logs" 2>nul
mkdir "%STATIC_SITE%\invoices" 2>nul
mkdir "%STATIC_SITE%\temp" 2>nul
echo ✅ Directories ready

REM Create enhanced local configuration file
echo.
echo 📝 Creating enhanced local development configuration...
(
echo // 🔧 ATTRAL Local Development Configuration
echo // Generated on: %date% %time%
echo window.ATTRAL_PUBLIC = window.ATTRAL_PUBLIC ^|^| {};
echo window.ATTRAL_PUBLIC.__API_BASE_URL_OVERRIDE__ = 'http://localhost:%DEFAULT_PORT%';
echo window.ATTRAL_PUBLIC.IS_LOCAL_DEV = true;
echo window.ATTRAL_PUBLIC.LOCAL_DEV_CONFIG = {
echo     serverPort: %DEFAULT_PORT%,
echo     httpsPort: %DEFAULT_HTTPS_PORT%,
echo     adminUsername: '%ADMIN_USERNAME%',
echo     adminPassword: '%ADMIN_PASSWORD%',
echo     enableDebugMode: true,
echo     enableAdminBypass: true
echo };
echo console.log('🔧 Local development configuration loaded');
echo console.log('📊 Admin credentials: %ADMIN_USERNAME% / %ADMIN_PASSWORD%');
) > "%STATIC_SITE%\js\local-config.js"
echo ✅ Enhanced local configuration created

REM Create enhanced admin bypass file
echo.
echo 🔓 Creating enhanced admin access bypass...
(
echo ^<?php
echo // Enhanced local development admin bypass
echo // Generated on: %date% %time%
echo session_start^(^);
echo $_SESSION['attral_admin_access'] = true;
echo $_SESSION['attral_admin_user'] = 'local-admin';
echo $_SESSION['attral_admin_login_time'] = time^(^);
echo $_SESSION['attral_admin_username'] = '%ADMIN_USERNAME%';
echo $_SESSION['attral_admin_password'] = '%ADMIN_PASSWORD%';
echo $_SESSION['attral_admin_permissions'] = ['all'];
echo echo 'Local admin access enabled with full permissions';
echo echo 'Username: %ADMIN_USERNAME%';
echo echo 'Password: %ADMIN_PASSWORD%';
echo ?^>
) > "%STATIC_SITE%\local-admin-bypass.php"
echo ✅ Enhanced admin bypass created

REM Create enhanced router
echo.
echo 🔧 Creating enhanced PHP router...
(
echo ^<?php
echo // Enhanced router for local development
echo // Generated on: %date% %time%
echo $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH^);
echo $method = $_SERVER['REQUEST_METHOD'];
echo $query = $_GET;
echo.
echo // Admin bypass endpoint
echo if ($uri === '/local-admin-bypass.php'^) {
echo     session_start^(^);
echo     $_SESSION['attral_admin_access'] = true;
echo     $_SESSION['attral_admin_user'] = 'local-admin';
echo     $_SESSION['attral_admin_login_time'] = time^(^);
echo     $_SESSION['attral_admin_username'] = '%ADMIN_USERNAME%';
echo     $_SESSION['attral_admin_password'] = '%ADMIN_PASSWORD%';
echo     $_SESSION['attral_admin_permissions'] = ['all'];
echo     echo 'Local admin access enabled with full permissions';
echo     echo 'Username: %ADMIN_USERNAME%';
echo     echo 'Password: %ADMIN_PASSWORD%';
echo     exit;
echo }
echo.
echo // API endpoint for admin functions
echo if ($uri === '/api/admin-api.php'^) {
echo     include 'api/admin-api.php';
echo     exit;
echo }
echo.
echo // Health check endpoint
echo if ($uri === '/health'^) {
echo     header('Content-Type: application/json'^);
echo     echo json_encode(['status' => 'ok', 'timestamp' => time^(^), 'server' => 'ATTRAL Local Dev'^]);
echo     exit;
echo }
echo.
echo // Admin system status endpoint
echo if ($uri === '/admin-status'^) {
echo     header('Content-Type: application/json'^);
echo     echo json_encode([
echo         'admin_system' => 'active',
echo         'firebase_integration' => 'enabled',
echo         'admin_functions' => [
echo             'site_access_control' => true,
echo             'maintenance_mode' => true,
echo             'ip_management' => true,
echo             'order_management' => true,
echo             'user_management' => true,
echo             'analytics_dashboard' => true
echo         ],
echo         'timestamp' => time^(^)
echo     ]);
echo     exit;
echo }
echo.
echo return false;
echo ?^>
) > "%STATIC_SITE%\router.php"
echo ✅ Enhanced router created

REM Create SSL certificate if OpenSSL is available
echo.
echo 🔐 Checking SSL certificate...
openssl version >nul 2>&1
if %errorlevel% equ 0 (
    if not exist "%STATIC_SITE%\ssl\server.crt" (
        echo Creating SSL certificate for HTTPS...
        openssl req -x509 -newkey rsa:4096 -keyout "%STATIC_SITE%\ssl\server.key" -out "%STATIC_SITE%\ssl\server.crt" -days 365 -nodes -subj "/C=IN/ST=Karnataka/L=Bangalore/O=ATTRAL/OU=IT/CN=localhost" 2>nul
        if exist "%STATIC_SITE%\ssl\server.crt" (
            echo ✅ SSL certificate created
        ) else (
            echo ⚠️  Failed to create SSL certificate
        )
    ) else (
        echo ✅ SSL certificate already exists
    )
) else (
    echo ⚠️  OpenSSL not found - HTTPS will not be available
    echo Creating dummy certificate files...
    echo. > "%STATIC_SITE%\ssl\server.crt"
    echo. > "%STATIC_SITE%\ssl\server.key"
)

REM Create development log file
echo.
echo 📝 Creating development log...
echo [%date% %time%] ATTRAL Local Development Server Started > "%STATIC_SITE%\logs\dev-server.log"
echo [%date% %time%] Admin Username: %ADMIN_USERNAME% >> "%STATIC_SITE%\logs\dev-server.log"
echo [%date% %time%] Admin Password: %ADMIN_PASSWORD% >> "%STATIC_SITE%\logs\dev-server.log"
echo [%date% %time%] Server Port: %DEFAULT_PORT% >> "%STATIC_SITE%\logs\dev-server.log"
echo ✅ Development log created

REM Change to static-site directory for server
cd /d "%STATIC_SITE%"

echo.
echo ========================================
echo    🌐 Starting Enhanced Local Server
echo ========================================
echo.
echo 📁 Server Directory: %STATIC_SITE%
echo 🌐 HTTP Server: http://localhost:%DEFAULT_PORT%
echo 🔐 HTTPS Server: https://localhost:%DEFAULT_HTTPS_PORT%
echo.
echo ========================================
echo    📋 Quick Access URLs
echo ========================================
echo 🏠 Homepage:           http://localhost:%DEFAULT_PORT%/index.html
echo 🛒 Shop:               http://localhost:%DEFAULT_PORT%/shop.html
echo 🛍️ Cart:               http://localhost:%DEFAULT_PORT%/cart.html
echo 📦 Orders:             http://localhost:%DEFAULT_PORT%/order.html
echo ✅ Order Success:      http://localhost:%DEFAULT_PORT%/order-success.html
echo 👤 User Dashboard:     http://localhost:%DEFAULT_PORT%/user-dashboard.html
echo 👨‍💼 Admin Dashboard:   http://localhost:%DEFAULT_PORT%/admin-dashboard-unified.html
echo 🔐 Admin Login:        http://localhost:%DEFAULT_PORT%/admin-login.html
echo 🛡️ Access Control:     http://localhost:%DEFAULT_PORT%/admin-access.html
echo 🔓 Admin Bypass:       http://localhost:%DEFAULT_PORT%/local-admin-bypass.php
echo.
echo ========================================
echo    🛡️ Enhanced Admin Functions
echo ========================================
echo ✅ Site Access Control
echo ✅ Maintenance Mode Toggle
echo ✅ IP Management
echo ✅ Order Management
echo ✅ User Management
echo ✅ Analytics Dashboard
echo ✅ Firebase Integration
echo ✅ Real-time Updates
echo ✅ Email Notifications
echo.
echo ========================================
echo    🔧 Development Tools
echo ========================================
echo 📊 Health Check:       http://localhost:%DEFAULT_PORT%/health
echo 📈 Admin Status:       http://localhost:%DEFAULT_PORT%/admin-status
echo 📝 Development Log:    %STATIC_SITE%\logs\dev-server.log
echo.
echo ========================================
echo    ⚠️  Important Notes
echo ========================================
echo • This is a DEVELOPMENT server only
echo • Do NOT use for production
echo • SSL certificate is self-signed (browser warning expected)
echo • Admin functions are available without restrictions
echo • Press Ctrl+C to stop the server
echo • Check logs for debugging information
echo.
echo ========================================
echo.
echo 🚀 Starting PHP server on port %DEFAULT_PORT%...
echo.

REM Start PHP built-in server with enhanced router
php -S localhost:%DEFAULT_PORT% -t . router.php

REM If the server stops, show message and pause
echo.
echo ========================================
echo 🛑 Server stopped
echo ========================================
echo.
echo The PHP server has stopped running.
echo.
echo Possible reasons:
echo • You pressed Ctrl+C to stop the server
echo • PHP encountered an error
echo • Port %DEFAULT_PORT% is already in use
echo.
echo To restart the server, run this batch file again.
echo.
echo Development log: %STATIC_SITE%\logs\dev-server.log
echo.
pause
