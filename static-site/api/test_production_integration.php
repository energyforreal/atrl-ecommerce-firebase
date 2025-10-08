<?php
/**
 * 🧪 Production Integration Test
 * Test the complete affiliate email system integration
 */

// Set headers only if not in CLI mode
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
}

try {
    // Handle input differently for CLI vs web
    if (php_sapi_name() === 'cli') {
        $input = ['testType' => 'full'];
    } else {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
    }
    
    $testType = $input['testType'] ?? 'full';
    
    $results = [
        'testType' => $testType,
        'timestamp' => date('Y-m-d H:i:s'),
        'tests' => []
    ];
    
    // Test 1: API Endpoints
    $results['tests']['api_endpoints'] = testApiEndpoints();
    
    // Test 2: Email Templates
    $results['tests']['email_templates'] = testEmailTemplates();
    
    // Test 3: Order Integration
    $results['tests']['order_integration'] = testOrderIntegration();
    
    // Test 4: Affiliate Integration
    $results['tests']['affiliate_integration'] = testAffiliateIntegration();
    
    // Test 5: SMTP Configuration
    $results['tests']['smtp_config'] = testSmtpConfiguration();
    
    // Calculate overall success
    $successCount = 0;
    $totalTests = count($results['tests']);
    
    foreach ($results['tests'] as $test) {
        if ($test['success']) {
            $successCount++;
        }
    }
    
    $results['overall'] = [
        'success' => $successCount === $totalTests,
        'successCount' => $successCount,
        'totalTests' => $totalTests,
        'percentage' => round(($successCount / $totalTests) * 100, 2)
    ];
    
    echo json_encode($results, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function testApiEndpoints() {
    $endpoints = [
        'affiliate_email_sender.php',
        'test_affiliate_api.php',
        'send_affiliate_welcome_on_signup.php'
    ];
    
    $results = [];
    $successCount = 0;
    
    foreach ($endpoints as $endpoint) {
        $filePath = __DIR__ . '/' . $endpoint;
        if (file_exists($filePath)) {
            $results[$endpoint] = 'exists';
            $successCount++;
        } else {
            $results[$endpoint] = 'missing';
        }
    }
    
    return [
        'success' => $successCount === count($endpoints),
        'successCount' => $successCount,
        'total' => count($endpoints),
        'details' => $results
    ];
}

function testEmailTemplates() {
    $templates = [
        'Welcome Email Template',
        'Commission Email Template',
        'Payout Email Template',
        'Milestone Email Template'
    ];
    
    // Test if affiliate_email_sender.php has all template functions
    $filePath = __DIR__ . '/affiliate_email_sender.php';
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        $templateFunctions = [
            'getAffiliateWelcomeTemplate',
            'getAffiliateCommissionTemplate',
            'getAffiliatePayoutTemplate',
            'getAffiliateMilestoneTemplate'
        ];
        
        $foundCount = 0;
        foreach ($templateFunctions as $function) {
            if (strpos($content, $function) !== false) {
                $foundCount++;
            }
        }
        
        return [
            'success' => $foundCount === count($templateFunctions),
            'successCount' => $foundCount,
            'total' => count($templateFunctions),
            'details' => 'Template functions found in affiliate_email_sender.php'
        ];
    }
    
    return [
        'success' => false,
        'successCount' => 0,
        'total' => count($templates),
        'details' => 'affiliate_email_sender.php not found'
    ];
}

function testOrderIntegration() {
    // Test if order managers are integrated with affiliate email system
    $files = [
        'order_manager.php',
        'firestore_order_manager.php'
    ];
    
    $successCount = 0;
    $details = [];
    
    foreach ($files as $file) {
        $filePath = __DIR__ . '/' . $file;
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            if (strpos($content, 'sendAffiliateCommissionEmail') !== false) {
                $successCount++;
                $details[$file] = 'integrated';
            } else {
                $details[$file] = 'not_integrated';
            }
        } else {
            $details[$file] = 'missing';
        }
    }
    
    return [
        'success' => $successCount === count($files),
        'successCount' => $successCount,
        'total' => count($files),
        'details' => $details
    ];
}

function testAffiliateIntegration() {
    // Test if affiliate sign-up triggers welcome emails
    $filePath = __DIR__ . '/../affiliates.html';
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        if (strpos($content, 'sendWelcomeEmailToNewAffiliate') !== false) {
            return [
                'success' => true,
                'successCount' => 1,
                'total' => 1,
                'details' => 'Welcome email trigger found in affiliates.html'
            ];
        }
    }
    
    return [
        'success' => false,
        'successCount' => 0,
        'total' => 1,
        'details' => 'Welcome email trigger not found'
    ];
}

function testSmtpConfiguration() {
    // Test SMTP configuration
    $configPath = __DIR__ . '/config.php';
    if (file_exists($configPath)) {
        $config = include $configPath;
        
        $requiredKeys = ['SMTP_HOST', 'SMTP_PORT', 'SMTP_USERNAME', 'SMTP_PASSWORD'];
        $foundCount = 0;
        
        foreach ($requiredKeys as $key) {
            if (isset($config[$key]) && !empty($config[$key])) {
                $foundCount++;
            }
        }
        
        // Test OpenSSL availability
        $opensslAvailable = extension_loaded('openssl');
        
        return [
            'success' => $foundCount === count($requiredKeys),
            'successCount' => $foundCount,
            'total' => count($requiredKeys),
            'details' => [
                'config_found' => true,
                'openssl_available' => $opensslAvailable,
                'smtp_configured' => $foundCount === count($requiredKeys)
            ]
        ];
    }
    
    return [
        'success' => false,
        'successCount' => 0,
        'total' => 4,
        'details' => 'config.php not found'
    ];
}
?>
