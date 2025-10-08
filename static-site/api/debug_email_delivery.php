<?php
/**
 * 🔍 Debug Email Delivery Issues
 * Investigate why emails are not being received
 */

// Enable OpenSSL extension
if (!extension_loaded('openssl')) {
    dl('C:\Program Files\php-8.4.12\ext\php_openssl.dll');
}

// Load PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

echo "🔍 Debugging Email Delivery Issues\n";
echo "=================================\n\n";

echo "1. Environment Check...\n";
echo "✅ OpenSSL extension: " . (extension_loaded('openssl') ? 'Loaded' : 'Not loaded') . "\n";
echo "✅ PHP Version: " . PHP_VERSION . "\n";
echo "✅ Current Time: " . date('Y-m-d H:i:s') . "\n";

echo "\n2. PHPMailer Configuration Check...\n";
try {
    require_once __DIR__ . '/brevo_email_service.php';
    $emailService = new BrevoEmailService();
    
    // Load config
    $cfg = include __DIR__ . '/config.php';
    
    echo "SMTP Host: " . ($cfg['SMTP_HOST'] ?? 'Not set') . "\n";
    echo "SMTP Port: " . ($cfg['SMTP_PORT'] ?? 'Not set') . "\n";
    echo "SMTP Username: " . ($cfg['SMTP_USERNAME'] ?? 'Not set') . "\n";
    echo "SMTP Password: " . (isset($cfg['SMTP_PASSWORD']) && $cfg['SMTP_PASSWORD'] ? 'Set' : 'Not set') . "\n";
    echo "Mail From: " . ($cfg['MAIL_FROM'] ?? 'Not set') . "\n";
    echo "Mail From Name: " . ($cfg['MAIL_FROM_NAME'] ?? 'Not set') . "\n";
    
} catch (Exception $e) {
    echo "❌ Configuration error: " . $e->getMessage() . "\n";
}

echo "\n3. Testing PHPMailer with Debug Output...\n";
try {
    // Load PHPMailer
    $composerAutoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($composerAutoload)) {
        require_once $composerAutoload;
    }
    $vendoredSrc = __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer') && file_exists($vendoredSrc)) {
        require_once __DIR__ . '/vendor/phpmailer/src/Exception.php';
        require_once __DIR__ . '/vendor/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/vendor/phpmailer/src/SMTP.php';
    }

    
    // Load config
    $cfg = include __DIR__ . '/config.php';
    
    $mail = new PHPMailer(true);
    
    // Enable verbose debug output
    $mail->SMTPDebug = SMTP::DEBUG_CONNECTION;
    $mail->Debugoutput = function($str, $level) {
        echo "DEBUG [$level]: $str\n";
    };
    
    // Server settings - Brevo SMTP
    $mail->isSMTP();
    $mail->Host = $cfg['SMTP_HOST'] ?? 'smtp-relay.brevo.com';
    $mail->SMTPAuth = true;
    $mail->Username = $cfg['SMTP_USERNAME'] ?? '8c9aee002@smtp-brevo.com';
    $mail->Password = $cfg['SMTP_PASSWORD'] ?? '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = intval($cfg['SMTP_PORT'] ?? 587);
    
    // Recipients
    $mail->setFrom($cfg['MAIL_FROM'] ?? 'info@attral.in', $cfg['MAIL_FROM_NAME'] ?? 'ATTRAL Electronics');
    $mail->addAddress('attralsolar@gmail.com', 'Test Recipient');
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = '🔍 Debug Test Email - ' . date('Y-m-d H:i:s');
    
    $mail->Body = '
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
        <h2>🔍 Debug Test Email</h2>
        <p>This is a debug test email to investigate delivery issues.</p>
        <p><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</p>
        <p><strong>SMTP Host:</strong> ' . ($cfg['SMTP_HOST'] ?? 'smtp-relay.brevo.com') . '</p>
        <p><strong>SMTP Port:</strong> ' . ($cfg['SMTP_PORT'] ?? 587) . '</p>
        <p><strong>From:</strong> ' . ($cfg['MAIL_FROM'] ?? 'info@attral.in') . '</p>
        <p><strong>To:</strong> attralsolar@gmail.com</p>
        <p>If you receive this email, the SMTP connection is working correctly.</p>
    </div>';
    
    $mail->AltBody = 'Debug Test Email - This is a debug test email to investigate delivery issues.';
    
    echo "Attempting to send debug email...\n";
    $mail->send();
    echo "✅ Debug email sent successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Debug email failed: " . $e->getMessage() . "\n";
}

echo "\n4. Testing BrevoEmailService with Debug...\n";
try {
    $emailService = new BrevoEmailService();
    
    $result = $emailService->sendTransactionalEmail(
        'attralsolar@gmail.com',
        '🔍 BrevoEmailService Debug Test - ' . date('Y-m-d H:i:s'),
        '<h1>BrevoEmailService Debug Test</h1><p>This is a debug test from BrevoEmailService.</p><p>Timestamp: ' . date('Y-m-d H:i:s') . '</p>',
        ['toName' => 'Debug Test']
    );
    
    if ($result['success']) {
        echo "✅ BrevoEmailService debug test successful!\n";
        echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ BrevoEmailService debug test failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ BrevoEmailService debug test exception: " . $e->getMessage() . "\n";
}

echo "\n5. Checking Email Logs...\n";
$logFiles = [
    __DIR__ . '/../logs/dev-server.log',
    __DIR__ . '/../logs/email.log',
    __DIR__ . '/email.log'
];

foreach ($logFiles as $logFile) {
    if (file_exists($logFile)) {
        echo "Found log file: $logFile\n";
        $content = file_get_contents($logFile);
        $lines = explode("\n", $content);
        $recentLines = array_slice($lines, -10);
        echo "Recent log entries:\n";
        foreach ($recentLines as $line) {
            if (trim($line)) {
                echo "  $line\n";
            }
        }
        echo "\n";
    }
}

echo "\n6. SMTP Connection Test...\n";
try {
    $smtpHost = $cfg['SMTP_HOST'] ?? 'smtp-relay.brevo.com';
    $smtpPort = intval($cfg['SMTP_PORT'] ?? 587);
    
    echo "Testing SMTP connection to $smtpHost:$smtpPort...\n";
    
    $connection = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 30);
    if ($connection) {
        echo "✅ SMTP connection successful!\n";
        fclose($connection);
    } else {
        echo "❌ SMTP connection failed: $errstr ($errno)\n";
    }
    
} catch (Exception $e) {
    echo "❌ SMTP connection test exception: " . $e->getMessage() . "\n";
}

echo "\n🏁 Email Delivery Debug Completed.\n";
echo "\n📧 Check your email inbox (attralsolar@gmail.com) for debug emails.\n";
echo "📊 If no emails are received, check:\n";
echo "   1. SMTP credentials are correct\n";
echo "   2. Brevo account is active\n";
echo "   3. Email is not in spam folder\n";
echo "   4. SMTP server is accessible\n";
?>
