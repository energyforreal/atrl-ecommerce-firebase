<?php
// Admin Messages API - Handles contact form submissions and admin message management
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(204); 
    exit; 
}

// Load configuration
$cfg = @include __DIR__.'/config.php';

function bad_request($msg, $code = 400) {
    http_response_code($code);
    echo json_encode([ 'error' => $msg ]);
    exit;
}

function send_email_notification($data) {
    // Email configuration for contact form
    $to = 'info@attral.in';
    $subject = isset($data['subject']) ? $data['subject'] : 'Contact Form Message';
    
    // Determine if user is authenticated
    $authStatus = isset($data['isAuthenticated']) && $data['isAuthenticated'] ? '🔐 Authenticated User' : '👤 Guest User';
    $authClass = isset($data['isAuthenticated']) && $data['isAuthenticated'] ? 'authenticated' : 'guest';
    
    $html_message = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #ff6b35; border-bottom: 2px solid #ff6b35; padding-bottom: 10px;'>
            📧 New {$subject}
        </h2>
        
        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>
            <h3 style='color: #333; margin-top: 0;'>Contact Details:</h3>
            <p><strong>Name:</strong> {$data['name']}</p>
            <p><strong>Email:</strong> <a href='mailto:{$data['email']}' style='color: #ff6b35;'>{$data['email']}</a></p>
            <p><strong>Status:</strong> <span style='color: #10b981; font-weight: bold;'>{$authStatus}</span></p>
            <p><strong>Submitted:</strong> " . date('Y-m-d H:i:s T') . "</p>
        </div>
        
        <div style='background: #ffffff; padding: 20px; border: 1px solid #e9ecef; border-radius: 8px;'>
            <h3 style='color: #333; margin-top: 0;'>Message:</h3>
            <p style='line-height: 1.6; color: #555;'>" . nl2br(htmlspecialchars($data['message'])) . "</p>
        </div>
        
        <div style='margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;'>
            <p style='margin: 0; color: #1976d2;'>
                <strong>💡 Quick Reply:</strong> Click <a href='mailto:{$data['email']}' style='color: #ff6b35;'>here</a> to reply directly to {$data['name']}.
            </p>
        </div>
        
        <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>
        <p style='color: #666; font-size: 12px; text-align: center;'>
            This message was sent from the ATTRAL Store contact form.<br>
            <strong>ATTRAL Store</strong> | Smart Power. Smarter Living.
        </p>
    </div>
    ";
    
    $text_message = "
{$subject} from {$data['name']}

Contact Details:
- Name: {$data['name']}
- Email: {$data['email']}
- Status: {$authStatus}
- Submitted: " . date('Y-m-d H:i:s T') . "

Message:
{$data['message']}

---
This message was sent from the ATTRAL Store contact form.
ATTRAL Store | Smart Power. Smarter Living.
    ";
    
    // Email headers
    $headers = [
        'From: info@attral.in',
        'Reply-To: ' . $data['email'],
        'Content-Type: text/html; charset=UTF-8',
        'X-Mailer: ATTRAL Contact Form'
    ];
    
    // Send email
    return mail($to, $subject, $html_message, implode("\r\n", $headers));
}

// Handle different HTTP methods
switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        if (!$data) {
            bad_request('Invalid JSON');
        }
        
        // Validate required fields
        $required_fields = ['name', 'email', 'message'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                bad_request("Missing required field: {$field}");
            }
        }
        
        // Email validation
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            bad_request('Invalid email format');
        }
        
        // Log the message
        error_log("Contact form submission: " . json_encode($data));
        
        // Send email notification
        $email_sent = send_email_notification($data);
        
        if ($email_sent) {
            echo json_encode([
                'success' => true,
                'message' => 'Thank you for your message! We\'ll get back to you soon.',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode([
                'success' => true, // Still success for user experience
                'message' => 'Thank you for your message! We\'ll get back to you soon.',
                'note' => 'Email notification may not have been sent, but your message was received.',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        break;
        
    case 'GET':
        // Return API information
        echo json_encode([
            'api' => 'ATTRAL Admin Messages API',
            'version' => '1.0.0',
            'endpoints' => [
                'POST /' => 'Submit contact form message',
                'GET /' => 'API information'
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;
        
    default:
        bad_request('Method not allowed', 405);
        break;
}
?>