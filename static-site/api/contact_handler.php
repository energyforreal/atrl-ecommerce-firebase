<?php
/**
 * 📧 Contact Form Handler with Email Automation
 * Sends confirmation to customer and notification to admin
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['name']) || !isset($input['email']) || !isset($input['message'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields: name, email, message']);
    exit;
}

$name = trim($input['name']);
$email = trim($input['email']);
$message = trim($input['message']);
$phone = trim($input['phone'] ?? '');

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

// Validate name
if (strlen($name) < 2) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Name must be at least 2 characters']);
    exit;
}

// Validate message
if (strlen($message) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Message must be at least 10 characters']);
    exit;
}

try {
    // Save to Firestore (if available)
    $saved = false;
    if (class_exists('Google\Cloud\Firestore\FirestoreClient')) {
        $serviceAccountPath = __DIR__ . '/firebase-service-account.json';
        if (file_exists($serviceAccountPath)) {
            $firestore = new Google\Cloud\Firestore\FirestoreClient([
                'projectId' => 'e-commerce-1d40f',
                'keyFilePath' => $serviceAccountPath
            ]);
            
            $contactData = [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'message' => $message,
                'createdAt' => new Google\Cloud\Core\Timestamp(new DateTime()),
                'status' => 'new',
                'source' => 'contact_form'
            ];
            
            $docRef = $firestore->collection('contact_messages')->add($contactData);
            $saved = true;
            error_log("Contact message saved to Firestore: " . $docRef->id());
        }
    }
    
    // Send confirmation email to customer
    $customerEmailData = [
        'action' => 'contact_confirmation',
        'email' => $email,
        'name' => $name,
        'message' => $message
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'http://localhost/api/brevo_email_service.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($customerEmailData),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10
    ]);
    $customerEmailResponse = curl_exec($ch);
    $customerEmailCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Send notification email to admin
    $adminEmailData = [
        'action' => 'contact_admin',
        'email' => $email,
        'name' => $name,
        'message' => $message,
        'phone' => $phone
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'http://localhost/api/brevo_email_service.php',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($adminEmailData),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10
    ]);
    $adminEmailResponse = curl_exec($ch);
    $adminEmailCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    error_log("Contact form emails sent - Customer: {$customerEmailCode}, Admin: {$adminEmailCode}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for contacting us! We\'ll get back to you within 24 hours.',
        'saved' => $saved,
        'emailsSent' => [
            'customer' => $customerEmailCode === 200,
            'admin' => $adminEmailCode === 200
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to process contact form. Please try again later.'
    ]);
}
?>



