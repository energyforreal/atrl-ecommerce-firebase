<?php
/**
 * Simple autoloader for ATTRAL E-commerce API
 * This is a fallback autoloader when composer install fails
 */

// Check if the classes exist and load them manually
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    $phpmailerPath = __DIR__ . '/phpmailer/src/PHPMailer.php';
    if (file_exists($phpmailerPath)) {
        require_once __DIR__ . '/phpmailer/src/Exception.php';
        require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/phpmailer/src/SMTP.php';
    }
}

// Firebase classes will be handled by the individual files
// that check for class existence before using them

// Log that fallback autoloader is being used
if (function_exists('error_log')) {
    error_log('ATTRAL: Using fallback autoloader - composer dependencies not installed');
}
