<?php
/**
 * 🚀 ATTRAL Local Development Router
 * Custom router for PHP built-in server with admin function support
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Handle admin bypass for local development
if ($uri === '/local-admin-bypass.php') {
    session_start();
    $_SESSION['attral_admin_access'] = true;
    $_SESSION['attral_admin_user'] = 'local-admin';
    $_SESSION['attral_admin_login_time'] = time();
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Local admin access enabled',
        'redirect' => '/admin-access.html'
    ]);
    exit;
}

// Handle API requests
if (strpos($uri, '/api/') === 0) {
    // Set proper headers for API requests
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
    
    // Serve API files
    $file = __DIR__ . $uri;
    if (file_exists($file) && is_file($file)) {
        // Set proper MIME type for PHP files
        if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            include $file;
            exit;
        }
    }
}

// Handle static files
$file = __DIR__ . $uri;
if (file_exists($file) && is_file($file)) {
    // Serve static files directly
    $mimeTypes = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'pdf' => 'application/pdf',
        'mp4' => 'video/mp4',
        'mp3' => 'audio/mpeg',
        'txt' => 'text/plain'
    ];
    
    $extension = pathinfo($file, PATHINFO_EXTENSION);
    if (isset($mimeTypes[$extension])) {
        header('Content-Type: ' . $mimeTypes[$extension]);
    }
    
    return false; // Let PHP serve the file
}

// Handle directory requests (serve index.html)
if (is_dir(__DIR__ . $uri)) {
    $indexFile = __DIR__ . $uri . '/index.html';
    if (file_exists($indexFile)) {
        header('Content-Type: text/html');
        include $indexFile;
        exit;
    }
}

// Handle 404 errors
http_response_code(404);
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found | ATTRAL Local Server</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
        }
        h1 {
            color: #2d3748;
            margin-bottom: 20px;
        }
        p {
            color: #4a5568;
            margin-bottom: 30px;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 0 10px;
        }
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Page Not Found</h1>
        <p>The requested page <code><?php echo htmlspecialchars($uri); ?></code> was not found on the ATTRAL local server.</p>
        <a href="/" class="btn">🏠 Go Home</a>
        <a href="/admin-login.html" class="btn">🔐 Admin Panel</a>
    </div>
</body>
</html>
