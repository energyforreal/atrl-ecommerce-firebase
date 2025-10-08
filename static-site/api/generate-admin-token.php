<?php
/**
 * Generate Admin Custom Token
 * Creates a Firebase custom token with admin claims for attralsolar@gmail.com
 */

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';

// Validate admin email
if ($email !== 'attralsolar@gmail.com') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized email']);
    exit();
}

// Load Firebase Admin SDK
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    http_response_code(500);
    echo json_encode(['error' => 'Composer dependencies not installed. Run: composer install']);
    exit();
}

require_once $vendorAutoload;

// Check if Firebase classes are available
if (!class_exists('Kreait\Firebase\Factory')) {
    http_response_code(500);
    echo json_encode(['error' => 'Firebase SDK not found. Install with: composer require kreait/firebase-php']);
    exit();
}

use Kreait\Firebase\Factory;

try {
    
    $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
    
    if (!file_exists($serviceAccountPath)) {
        throw new Exception('Firebase service account key file not found at: ' . $serviceAccountPath);
    }
    
    // Validate service account file
    $serviceAccountContent = file_get_contents($serviceAccountPath);
    $serviceAccountData = json_decode($serviceAccountContent, true);
    
    if (!$serviceAccountData || !isset($serviceAccountData['type']) || $serviceAccountData['type'] !== 'service_account') {
        throw new Exception('Invalid service account file format');
    }
    
    // Create Firebase instance
    $factory = (new Factory)
        ->withServiceAccount($serviceAccountPath);
    
    $auth = $factory->createAuth();
    
    // Create custom token with admin claims
    $uid = 'admin-' . md5($email);
    $customToken = $auth->createCustomToken($uid, [
        'admin' => true,
        'email' => $email,
        'role' => 'administrator'
    ]);
    
    echo json_encode([
        'success' => true,
        'customToken' => $customToken->toString(),
        'uid' => $uid,
        'expiresAt' => date('c', time() + 3600), // 1 hour from now
        'debug' => [
            'serviceAccountPath' => $serviceAccountPath,
            'email' => $email,
            'timestamp' => date('c')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to generate custom token',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'debug' => [
            'serviceAccountExists' => file_exists(__DIR__ . '/firebase-service-account.json'),
            'vendorAutoloadExists' => file_exists(__DIR__ . '/../vendor/autoload.php'),
            'phpVersion' => PHP_VERSION
        ]
    ]);
}
?>
