# 🚀 ATTRAL eCommerce Local Development Server (PowerShell)
# Enhanced Development Server Setup

param(
    [int]$Port = 8000,
    [string]$ServerHost = "localhost",
    [string]$AdminUsername = "attral",
    [string]$AdminPassword = "Rakeshmurali@10"
)

# Set console colors and title
$Host.UI.RawUI.WindowTitle = "ATTRAL eCommerce - Local Development Server"
$Host.UI.RawUI.BackgroundColor = "Black"
$Host.UI.RawUI.ForegroundColor = "Green"
Clear-Host

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "    🚀 ATTRAL eCommerce Local Server" -ForegroundColor Yellow
Write-Host "    Enhanced Development Environment" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Get the directory where this script is located
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$staticSiteDir = Join-Path $scriptDir "static-site"

# Check if static-site directory exists
if (-not (Test-Path $staticSiteDir)) {
    Write-Host "❌ static-site directory not found at: $staticSiteDir" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please ensure this script is in the main project directory" -ForegroundColor Yellow
    Write-Host "and the static-site folder exists." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Current directory: $scriptDir" -ForegroundColor White
    Write-Host "Expected structure:" -ForegroundColor White
    Write-Host "  $scriptDir" -ForegroundColor White
    Write-Host "  $staticSiteDir\" -ForegroundColor White
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

Write-Host "✅ Found static-site directory: $staticSiteDir" -ForegroundColor Green

# Check if PHP is installed
Write-Host ""
Write-Host "🔍 Checking PHP installation..." -ForegroundColor Yellow
try {
    $null = php --version 2>$null
    if ($LASTEXITCODE -ne 0) {
        throw "PHP not found"
    }
    Write-Host "✅ PHP is installed and accessible" -ForegroundColor Green
} catch {
    Write-Host "❌ PHP is not installed or not in PATH" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please install PHP from: https://windows.php.net/download/" -ForegroundColor Yellow
    Write-Host "Or use XAMPP: https://www.apachefriends.org/download.html" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Press Enter to exit"
    exit 1
}

# Check if Node.js is available (for potential future use)
Write-Host ""
Write-Host "🔍 Checking Node.js installation..." -ForegroundColor Yellow
try {
    $null = node --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Node.js is available" -ForegroundColor Green
    } else {
        throw "Node.js not found"
    }
} catch {
    Write-Host "⚠️ Node.js not found (optional for some features)" -ForegroundColor Yellow
}

# Create necessary directories
Write-Host ""
Write-Host "📁 Creating necessary directories..." -ForegroundColor Yellow
$sslDir = Join-Path $staticSiteDir "ssl"
$logsDir = Join-Path $staticSiteDir "logs"
$invoicesDir = Join-Path $staticSiteDir "invoices"
$tempDir = Join-Path $staticSiteDir "temp"

New-Item -ItemType Directory -Path $sslDir -Force | Out-Null
New-Item -ItemType Directory -Path $logsDir -Force | Out-Null
New-Item -ItemType Directory -Path $invoicesDir -Force | Out-Null
New-Item -ItemType Directory -Path $tempDir -Force | Out-Null
Write-Host "✅ Directories ready" -ForegroundColor Green

# Create enhanced local configuration file
Write-Host ""
Write-Host "📝 Creating enhanced local development configuration..." -ForegroundColor Yellow
$localConfigFile = Join-Path $staticSiteDir "js\local-config.js"
$currentTime = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
$localConfigContent = @"
// 🔧 ATTRAL Local Development Configuration
// Generated on: $currentTime
window.ATTRAL_PUBLIC = window.ATTRAL_PUBLIC || {};
window.ATTRAL_PUBLIC.__API_BASE_URL_OVERRIDE__ = 'http://$ServerHost`:$Port';
window.ATTRAL_PUBLIC.IS_LOCAL_DEV = true;
window.ATTRAL_PUBLIC.LOCAL_DEV_CONFIG = {
    serverPort: $Port,
    httpsPort: 8443,
    adminUsername: '$AdminUsername',
    adminPassword: '$AdminPassword',
    enableDebugMode: true,
    enableAdminBypass: true
};
console.log('🔧 Local development configuration loaded');
console.log('📊 Admin credentials: $AdminUsername / $AdminPassword');
"@
Set-Content -Path $localConfigFile -Value $localConfigContent
Write-Host "✅ Enhanced local configuration created" -ForegroundColor Green

# Create enhanced admin bypass file
Write-Host ""
Write-Host "🔓 Creating enhanced admin access bypass..." -ForegroundColor Yellow
$adminBypassFile = Join-Path $staticSiteDir "local-admin-bypass.php"
$adminBypassContent = @"
<?php
// Enhanced local development admin bypass
// Generated on: $currentTime
session_start();
`$_SESSION['attral_admin_access'] = true;
`$_SESSION['attral_admin_user'] = 'local-admin';
`$_SESSION['attral_admin_login_time'] = time();
`$_SESSION['attral_admin_username'] = '$AdminUsername';
`$_SESSION['attral_admin_password'] = '$AdminPassword';
`$_SESSION['attral_admin_permissions'] = ['all'];
echo 'Local admin access enabled with full permissions';
echo 'Username: $AdminUsername';
echo 'Password: $AdminPassword';
?>
"@
Set-Content -Path $adminBypassFile -Value $adminBypassContent
Write-Host "✅ Enhanced admin bypass created" -ForegroundColor Green

# Create enhanced router
Write-Host ""
Write-Host "🔧 Creating enhanced PHP router..." -ForegroundColor Yellow
$routerFile = Join-Path $staticSiteDir "router.php"
$routerContent = @"
<?php
// Enhanced router for local development
// Generated on: $currentTime
`$uri = parse_url(`$_SERVER['REQUEST_URI'], PHP_URL_PATH);
`$method = `$_SERVER['REQUEST_METHOD'];
`$query = `$_GET;

// Admin bypass endpoint
if (`$uri === '/local-admin-bypass.php') {
    session_start();
    `$_SESSION['attral_admin_access'] = true;
    `$_SESSION['attral_admin_user'] = 'local-admin';
    `$_SESSION['attral_admin_login_time'] = time();
    `$_SESSION['attral_admin_username'] = '$AdminUsername';
    `$_SESSION['attral_admin_password'] = '$AdminPassword';
    `$_SESSION['attral_admin_permissions'] = ['all'];
    echo 'Local admin access enabled with full permissions';
    echo 'Username: $AdminUsername';
    echo 'Password: $AdminPassword';
    exit;
}

// API endpoint for admin functions
if (`$uri === '/api/admin-api.php') {
    include 'api/admin-api.php';
    exit;
}

// Health check endpoint
if (`$uri === '/health') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'timestamp' => time(), 'server' => 'ATTRAL Local Dev']);
    exit;
}

// Admin system status endpoint
if (`$uri === '/admin-status') {
    header('Content-Type: application/json');
    echo json_encode([
        'admin_system' => 'active',
        'firebase_integration' => 'enabled',
        'admin_functions' => [
            'site_access_control' => true,
            'maintenance_mode' => true,
            'ip_management' => true,
            'order_management' => true,
            'user_management' => true,
            'analytics_dashboard' => true
        ],
        'timestamp' => time()
    ]);
    exit;
}

return false;
?>
"@
Set-Content -Path $routerFile -Value $routerContent
Write-Host "✅ Enhanced router created" -ForegroundColor Green

# Create SSL certificate if OpenSSL is available
Write-Host ""
Write-Host "🔐 Checking SSL certificate..." -ForegroundColor Yellow
try {
    $null = openssl version 2>$null
    if ($LASTEXITCODE -eq 0) {
        $crtFile = Join-Path $sslDir "server.crt"
        if (-not (Test-Path $crtFile)) {
            Write-Host "Creating SSL certificate for HTTPS..." -ForegroundColor Yellow
            $keyFile = Join-Path $sslDir "server.key"
            $opensslCmd = "openssl req -x509 -newkey rsa:4096 -keyout `"$keyFile`" -out `"$crtFile`" -days 365 -nodes -subj `/C=IN/ST=Karnataka/L=Bangalore/O=ATTRAL/OU=IT/CN=localhost`"
            Invoke-Expression $opensslCmd 2>$null
            
            if (Test-Path $crtFile) {
                Write-Host "✅ SSL certificate created" -ForegroundColor Green
            } else {
                Write-Host "⚠️ Failed to create SSL certificate" -ForegroundColor Yellow
            }
        } else {
            Write-Host "✅ SSL certificate already exists" -ForegroundColor Green
        }
    } else {
        throw "OpenSSL not found"
    }
} catch {
    Write-Host "⚠️ OpenSSL not found - HTTPS will not be available" -ForegroundColor Yellow
    Write-Host "Creating dummy certificate files..." -ForegroundColor Yellow
    New-Item -ItemType File -Path (Join-Path $sslDir "server.crt") -Force | Out-Null
    New-Item -ItemType File -Path (Join-Path $sslDir "server.key") -Force | Out-Null
}

# Create development log file
Write-Host ""
Write-Host "📝 Creating development log..." -ForegroundColor Yellow
$logFile = Join-Path $logsDir "dev-server.log"
$logContent = "[$currentTime] ATTRAL Local Development Server Started`n[$currentTime] Admin Username: $AdminUsername`n[$currentTime] Admin Password: $AdminPassword`n[$currentTime] Server Port: $Port"
Set-Content -Path $logFile -Value $logContent
Write-Host "✅ Development log created" -ForegroundColor Green

# Change to static-site directory for server
Set-Location $staticSiteDir

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "    🌐 Starting Enhanced Local Server" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "📁 Server Directory: $staticSiteDir" -ForegroundColor Green
Write-Host "🌐 HTTP Server: http://$ServerHost`:$Port" -ForegroundColor Green
Write-Host "🔐 HTTPS Server: https://$ServerHost`:8443" -ForegroundColor Green
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "    📋 Quick Access URLs" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "🏠 Homepage:           http://$ServerHost`:$Port/index.html" -ForegroundColor White
Write-Host "🛒 Shop:               http://$ServerHost`:$Port/shop.html" -ForegroundColor White
Write-Host "🛍️ Cart:               http://$ServerHost`:$Port/cart.html" -ForegroundColor White
Write-Host "📦 Orders:             http://$ServerHost`:$Port/order.html" -ForegroundColor White
Write-Host "✅ Order Success:      http://$ServerHost`:$Port/order-success.html" -ForegroundColor White
Write-Host "👤 User Dashboard:     http://$ServerHost`:$Port/user-dashboard.html" -ForegroundColor White
Write-Host "👨‍💼 Admin Dashboard:   http://$ServerHost`:$Port/admin-dashboard.html" -ForegroundColor White
Write-Host "🔐 Admin Login:        http://$ServerHost`:$Port/admin-login.html" -ForegroundColor White
Write-Host "🛡️ Access Control:     http://$ServerHost`:$Port/admin-access.html" -ForegroundColor White
Write-Host "🔓 Admin Bypass:       http://$ServerHost`:$Port/local-admin-bypass.php" -ForegroundColor White
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "    🛡️ Enhanced Admin Functions" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "✅ Site Access Control" -ForegroundColor Green
Write-Host "✅ Maintenance Mode Toggle" -ForegroundColor Green
Write-Host "✅ IP Management" -ForegroundColor Green
Write-Host "✅ Order Management" -ForegroundColor Green
Write-Host "✅ User Management" -ForegroundColor Green
Write-Host "✅ Analytics Dashboard" -ForegroundColor Green
Write-Host "✅ Firebase Integration" -ForegroundColor Green
Write-Host "✅ Real-time Updates" -ForegroundColor Green
Write-Host "✅ Email Notifications" -ForegroundColor Green
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "    🔧 Development Tools" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "📊 Health Check:       http://$ServerHost`:$Port/health" -ForegroundColor White
Write-Host "📈 Admin Status:       http://$ServerHost`:$Port/admin-status" -ForegroundColor White
Write-Host "📝 Development Log:    $logFile" -ForegroundColor White
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "    ⚠️  Important Notes" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "• This is a DEVELOPMENT server only" -ForegroundColor Red
Write-Host "• Do NOT use for production" -ForegroundColor Red
Write-Host "• SSL certificate is self-signed (browser warning expected)" -ForegroundColor Yellow
Write-Host "• Admin functions are available without restrictions" -ForegroundColor Yellow
Write-Host "• Press Ctrl+C to stop the server" -ForegroundColor Yellow
Write-Host "• Check logs for debugging information" -ForegroundColor Yellow
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "🚀 Starting PHP server on port $Port..." -ForegroundColor Yellow
Write-Host ""

# Start the PHP server
try {
    # Use PHP built-in server with custom router
    php -S "${ServerHost}:${Port}" -t . router.php
} catch {
    Write-Host "❌ Failed to start PHP server: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "Trying alternative method..." -ForegroundColor Yellow
    
    # Try alternative PHP command
    try {
        php -S "127.0.0.1:${Port}"
    } catch {
        Write-Host "❌ PHP server failed to start completely" -ForegroundColor Red
        Write-Host "Please check your PHP installation and try again" -ForegroundColor Yellow
    }
}

# If the server stops, show message
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "🛑 Server stopped" -ForegroundColor Red
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "The PHP server has stopped running." -ForegroundColor Yellow
Write-Host ""
Write-Host "Possible reasons:" -ForegroundColor White
Write-Host "• You pressed Ctrl+C to stop the server" -ForegroundColor White
Write-Host "• PHP encountered an error" -ForegroundColor White
Write-Host "• Port $Port is already in use" -ForegroundColor White
Write-Host ""
Write-Host "To restart the server, run this script again." -ForegroundColor Yellow
Write-Host ""
Write-Host "Development log: $logFile" -ForegroundColor White
Write-Host ""
Read-Host "Press Enter to exit"