<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
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

// Log request details for debugging
error_log("NEWSLETTER API: Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("NEWSLETTER API: Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set'));
error_log("NEWSLETTER API: User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Not set'));

// Load configuration
$cfg = include __DIR__ . '/config.php';

// Brevo API configuration
$BREVO_API_KEY = $cfg['BREVO_API_KEY'] ?? null;
$BREVO_API_URL = 'https://api.brevo.com/v3/contacts';
$LIST_ID = 3; // Attral Shopping list ID

// Validate API key
if (empty($BREVO_API_KEY)) {
    http_response_code(500);
    echo json_encode(['error' => 'Brevo API key not configured']);
    exit();
}

// Determine local mode (only by explicit flag, not by cURL availability)
$LOCAL_MODE = (getenv('LOCAL_MODE') && strtolower(getenv('LOCAL_MODE')) === 'true');

// Get JSON input
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

// Debug logging
error_log("NEWSLETTER API: Raw input: " . $rawInput);
error_log("NEWSLETTER API: Parsed input: " . json_encode($input));

// Check if JSON parsing failed
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit();
}

// Validate required fields
if (!isset($input['FIRSTNAME']) || !isset($input['EMAIL'])) {
    error_log("NEWSLETTER API: Missing fields - FIRSTNAME: " . (isset($input['FIRSTNAME']) ? 'OK' : 'MISSING') . ", EMAIL: " . (isset($input['EMAIL']) ? 'OK' : 'MISSING'));
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields: FIRSTNAME and EMAIL']);
    exit();
}

// Sanitize and validate input
$firstName = trim($input['FIRSTNAME']);
$email = trim($input['EMAIL']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid email format']);
    exit();
}

// Validate name
if (strlen($firstName) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'Name must be at least 2 characters long']);
    exit();
}

// Prepare data for Brevo API
$contactData = [
    'email' => $email,
    'attributes' => [
        'FIRSTNAME' => $firstName
    ],
    'listIds' => [$LIST_ID],
    'updateEnabled' => true
];

// In local mode, mock subscription by persisting to a JSON file
if ($LOCAL_MODE) {
    $dir = __DIR__ . '/tmp';
    if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
    $file = $dir . '/newsletter.json';
    $list = [];
    if (file_exists($file)) {
        $decoded = json_decode(file_get_contents($file), true);
        if (is_array($decoded)) { $list = $decoded; }
    }
    $list[$email] = [
        'FIRSTNAME' => $firstName,
        'listIds' => [$LIST_ID],
        'updatedAt' => date('c')
    ];
    @file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT));
    
    // Send welcome email with free shipping code in local mode too
    $freeShippingCode = 'ATTRALFREESHIP100';
    
    // Send welcome email with free shipping code
    try {
        // Load and use email service directly
        require_once __DIR__ . '/brevo_email_service.php';
        $emailService = new BrevoEmailService();
        $emailResult = $emailService->sendFreeShipCouponEmail($email, $firstName);
        
        if ($emailResult['success']) {
            error_log("NEWSLETTER LOCAL: ✅ Welcome email with free shipping code sent to $email");
        } else {
            error_log("NEWSLETTER LOCAL: ⚠️ Failed to send welcome email: " . ($emailResult['error'] ?? 'Unknown error'));
        }
    } catch (Exception $emailError) {
        error_log("NEWSLETTER LOCAL: Email sending error: " . $emailError->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Subscribed locally (mock). Check your email for your free shipping code!',
        'data' => ['local' => true],
        'freeShippingCode' => $freeShippingCode
    ]);
    exit();
}

// Try to use cURL if available, otherwise use file_get_contents
if (function_exists('curl_init')) {
    // Initialize cURL
    $ch = curl_init();

    // Set cURL options
    curl_setopt_array($ch, [
        CURLOPT_URL => $BREVO_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($contactData),
        CURLOPT_HTTPHEADER => [
            'accept: application/json',
            'api-key: ' . $BREVO_API_KEY,
            'content-type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true
    ]);

    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    // Handle cURL errors
    if ($curlError) {
        error_log("Brevo API cURL Error: " . $curlError);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to connect to Brevo API']);
        exit();
    }
} else {
    // Fallback: Use file_get_contents with stream context
    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => [
                'accept: application/json',
                'api-key: ' . $BREVO_API_KEY,
                'content-type: application/json'
            ],
            'content' => json_encode($contactData),
            'timeout' => 30
        ]
    ]);
    
    $response = @file_get_contents($BREVO_API_URL, false, $context);
    
    if ($response === false) {
        error_log("Brevo API file_get_contents Error");
        http_response_code(500);
        echo json_encode(['error' => 'Failed to connect to Brevo API (no cURL available)']);
        exit();
    }
    
    // Get HTTP response code from headers
    $httpCode = 200; // Default, will be updated based on response
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (strpos($header, 'HTTP/') === 0) {
                preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $matches);
                if (isset($matches[1])) {
                    $httpCode = intval($matches[1]);
                }
            }
        }
    }
}

// Handle API response
if ($httpCode === 201) {
    // Contact created successfully - Send welcome email with free shipping code
    $freeShippingCode = 'ATTRALFREESHIP100';
    
    // Send welcome email with free shipping code
    try {
        // Load and use email service directly
        require_once __DIR__ . '/brevo_email_service.php';
        $emailService = new BrevoEmailService();
        $emailResult = $emailService->sendFreeShipCouponEmail($email, $firstName);
        
        if ($emailResult['success']) {
            error_log("NEWSLETTER: ✅ Welcome email with free shipping code sent to $email");
        } else {
            error_log("NEWSLETTER: ⚠️ Failed to send welcome email: " . ($emailResult['error'] ?? 'Unknown error'));
        }
    } catch (Exception $emailError) {
        error_log("NEWSLETTER: Email sending error: " . $emailError->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Successfully subscribed to newsletter! Check your email for your free shipping code!',
        'data' => json_decode($response, true),
        'freeShippingCode' => $freeShippingCode
    ]);
} elseif ($httpCode === 204) {
    // Contact updated successfully - Send welcome email with free shipping code
    $freeShippingCode = 'ATTRALFREESHIP100';
    
    // Send welcome email with free shipping code
    try {
        // Load and use email service directly
        require_once __DIR__ . '/brevo_email_service.php';
        $emailService = new BrevoEmailService();
        $emailResult = $emailService->sendFreeShipCouponEmail($email, $firstName);
        
        if ($emailResult['success']) {
            error_log("NEWSLETTER: ✅ Welcome email with free shipping code sent to $email");
        } else {
            error_log("NEWSLETTER: ⚠️ Failed to send welcome email: " . ($emailResult['error'] ?? 'Unknown error'));
        }
    } catch (Exception $emailError) {
        error_log("NEWSLETTER: Email sending error: " . $emailError->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Successfully updated your subscription! Check your email for your free shipping code!',
        'data' => ['updated' => true],
        'freeShippingCode' => $freeShippingCode
    ]);
} elseif ($httpCode === 400) {
    // Bad request - usually means invalid data
    $errorData = json_decode($response, true);
    error_log("Brevo API Error (400): " . $response);
    echo json_encode([
        'error' => 'Invalid data provided',
        'details' => $errorData
    ]);
} elseif ($httpCode === 401) {
    // Unauthorized - API key issue
    error_log("Brevo API Error (401): Unauthorized - Check API key");
    http_response_code(500);
    echo json_encode(['error' => 'API authentication failed']);
} elseif ($httpCode === 404) {
    // List not found
    error_log("Brevo API Error (404): List not found - Check list ID");
    http_response_code(500);
    echo json_encode(['error' => 'Newsletter list not found']);
} else {
    // Other errors
    error_log("Brevo API Error ($httpCode): " . $response);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to subscribe to newsletter']);
}

// Log successful submissions for debugging
if ($httpCode === 201 || $httpCode === 204) {
    error_log("Newsletter subscription successful: $email ($firstName) added to list $LIST_ID");
}
?>
