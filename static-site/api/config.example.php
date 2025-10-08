<?php
// Configuration Template
// Copy this file to config.php and fill in your actual values

// Razorpay API Credentials
define('RAZORPAY_KEY_ID', 'YOUR_RAZORPAY_KEY_ID');
define('RAZORPAY_KEY_SECRET', 'YOUR_RAZORPAY_KEY_SECRET');

// Brevo (Sendinblue) API Key
define('BREVO_API_KEY', 'YOUR_BREVO_API_KEY');

// Email Configuration
define('SMTP_HOST', 'smtp-relay.brevo.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'YOUR_SMTP_USERNAME');
define('SMTP_PASSWORD', 'YOUR_SMTP_PASSWORD');
define('FROM_EMAIL', 'your-email@example.com');
define('FROM_NAME', 'Your Company Name');

// Firebase Configuration
define('FIREBASE_PROJECT_ID', 'YOUR_FIREBASE_PROJECT_ID');
define('FIREBASE_SERVICE_ACCOUNT_PATH', __DIR__ . '/firebase-service-account.json');

// Database Configuration (if using SQLite)
define('DB_PATH', __DIR__ . '/orders.db');

// Environment
define('ENVIRONMENT', 'development'); // 'development' or 'production'

// Enable error reporting for development
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>

