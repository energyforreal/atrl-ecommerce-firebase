<?php
/**
 * 🎯 ATTRAL Brevo Email Service
 * Centralized email handler for all automated emails
 * 
 * Features:
 * - Transactional emails (order confirmations, shipping, etc.)
 * - Marketing emails (newsletters, promotions)
 * - Email list management
 * - Template system
 */

// Suppress warnings and errors to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

// Only set headers if running in web context and no output has been sent
if (php_sapi_name() !== 'cli' && !headers_sent()) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// Load project config and PHPMailer for local mode SMTP fallback
$cfg = @include __DIR__ . '/config.php';
$LOCAL_MODE = isset($cfg['LOCAL_MODE']) ? (bool)$cfg['LOCAL_MODE'] : false;

// Composer autoload if present (PHPMailer installed via composer in project root)
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

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load configuration if not already loaded
if (!defined('BREVO_API_KEY')) {
    require_once __DIR__ . '/config.php';
}

// Brevo Configuration (use config if available, otherwise define)
if (!defined('BREVO_API_URL')) {
    define('BREVO_API_URL', 'https://api.brevo.com/v3');
}
if (!defined('BREVO_CUSTOMER_LIST_ID')) {
    define('BREVO_CUSTOMER_LIST_ID', 3); // Attral Shopping - Customer contacts
}
if (!defined('BREVO_AFFILIATE_LIST_ID')) {
    define('BREVO_AFFILIATE_LIST_ID', 10); // e-Commerce Affiliates - Affiliate contacts
}
if (!defined('FROM_EMAIL')) {
    define('FROM_EMAIL', 'info@attral.in');
}
if (!defined('FROM_NAME')) {
    define('FROM_NAME', 'ATTRAL Electronics');
}

class BrevoEmailService {
    
    private $apiKey;
    private $apiUrl;
    
    public function __construct() {
        $this->apiKey = BREVO_API_KEY;
        $this->apiUrl = BREVO_API_URL;
    }
    
    /**
     * Send transactional email via PHPMailer only (No Brevo API)
     */
    public function sendTransactionalEmail($to, $subject, $htmlContent, $params = []) {
        // Use PHPMailer as primary email service
        $sendResult = $this->sendViaPHPMailer($to, $subject, $htmlContent, $params);
        if ($sendResult['success']) {
            error_log("PHPMailer: Email sent successfully to $to");
            return $sendResult;
        }
        
        // PHPMailer failed - no Brevo API fallback
        error_log("PHPMailer failed for $to: " . ($sendResult['error'] ?? 'Unknown error'));
        
        // If file fallback enabled, save as .eml
        if ($this->shouldSaveToFile()) {
            $saved = $this->saveEmailToFile($to, $subject, $htmlContent, $params);
            if ($saved['success']) {
                error_log("Email saved to file as fallback for $to");
                return $saved;
            }
        }
        
        // Return PHPMailer error
        return $sendResult;
    }
    
    /**
     * Send email via Brevo API (Fallback method)
     */
    private function sendViaBrevoAPI($to, $subject, $htmlContent, $params = []) {
        try {
            $data = [
                'sender' => [
                    'email' => $params['fromEmail'] ?? FROM_EMAIL,
                    'name' => $params['fromName'] ?? FROM_NAME
                ],
                'to' => [
                    [
                        'email' => $to,
                        'name' => $params['toName'] ?? ''
                    ]
                ],
                'subject' => $subject,
                'htmlContent' => $htmlContent,
                'textContent' => strip_tags($htmlContent)
            ];

            // Add reply-to if provided
            if (!empty($params['replyTo'])) {
                $data['replyTo'] = [
                    'email' => $params['replyTo'],
                    'name' => $params['replyToName'] ?? ''
                ];
            }

            // Add CC if provided
            if (!empty($params['cc'])) {
                $data['cc'] = [];
                foreach ($params['cc'] as $cc) {
                    $data['cc'][] = [
                        'email' => $cc['email'],
                        'name' => $cc['name'] ?? ''
                    ];
                }
            }

            // Add BCC if provided
            if (!empty($params['bcc'])) {
                $data['bcc'] = [];
                foreach ($params['bcc'] as $bcc) {
                    $data['bcc'][] = [
                        'email' => $bcc['email'],
                        'name' => $bcc['name'] ?? ''
                    ];
                }
            }

            // Add attachments if provided
            if (!empty($params['attachments'])) {
                $data['attachment'] = [];
                foreach ($params['attachments'] as $attachment) {
                    $data['attachment'][] = [
                        'content' => $attachment['content'],
                        'name' => $attachment['name'] ?? 'attachment',
                        'type' => $attachment['type'] ?? 'application/octet-stream'
                    ];
                }
            }

            return $this->makeBrevoRequest('/smtp/email', $data);
        } catch (Exception $e) {
            error_log("BREVO API ERROR: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Add contact to email list(s)
     * @param string $email Email address
     * @param string $firstName First name
     * @param array $attributes Additional attributes
     * @param string $listType 'customer' or 'affiliate' (default: 'customer')
     */
    public function addToList($email, $firstName = '', $attributes = [], $listType = 'customer') {
        // Determine which list(s) to add to
        $listIds = [];
        if ($listType === 'affiliate') {
            $listIds = [BREVO_AFFILIATE_LIST_ID];
        } elseif ($listType === 'both') {
            $listIds = [BREVO_CUSTOMER_LIST_ID, BREVO_AFFILIATE_LIST_ID];
        } else {
            $listIds = [BREVO_CUSTOMER_LIST_ID];
        }
        
        $data = [
            'email' => $email,
            'listIds' => $listIds,
            'updateEnabled' => true
        ];
        
        $attrs = array_merge(['FIRSTNAME' => $firstName], $attributes);
        if (!empty($attrs)) {
            $data['attributes'] = $attrs;
        }
        
        return $this->makeRequest('/contacts', $data);
    }
    
    /**
     * Add contact to customer list (#3)
     */
    public function addToCustomerList($email, $firstName = '', $attributes = []) {
        return $this->addToList($email, $firstName, $attributes, 'customer');
    }
    
    /**
     * Add contact to affiliate list (#10)
     */
    public function addToAffiliateList($email, $firstName = '', $attributes = []) {
        return $this->addToList($email, $firstName, $attributes, 'affiliate');
    }
    
    /**
     * Add contact to both customer and affiliate lists
     */
    public function addToBothLists($email, $firstName = '', $attributes = []) {
        return $this->addToList($email, $firstName, $attributes, 'both');
    }
    
    /**
     * Remove contact from a list
     */
    public function removeFromList($email, $listType = 'customer') {
        $listId = ($listType === 'affiliate') ? BREVO_AFFILIATE_LIST_ID : BREVO_CUSTOMER_LIST_ID;
        
        return $this->makeRequest(
            '/contacts/lists/' . $listId . '/contacts/remove',
            ['emails' => [$email]],
            'POST'
        );
    }
    
    /**
     * Get contact information
     */
    public function getContact($email) {
        return $this->makeRequest('/contacts/' . urlencode($email), [], 'GET');
    }
    
    /**
     * Update contact attributes
     */
    public function updateContact($email, $attributes = []) {
        $data = ['attributes' => $attributes];
        return $this->makeRequest('/contacts/' . urlencode($email), $data, 'PUT');
    }
    
    /**
     * Send welcome email to new users
     */
    public function sendWelcomeEmail($email, $firstName) {
        $html = $this->getWelcomeEmailTemplate($firstName);
        return $this->sendTransactionalEmail(
            $email,
            "Welcome to ATTRAL! 🚀 Your Journey to Smart Charging Begins",
            $html,
            ['toName' => $firstName]
        );
    }
    
    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($orderData) {
        $html = $this->getOrderConfirmationTemplate($orderData);
        return $this->sendTransactionalEmail(
            $orderData['email'],
            "Order Confirmed #{$orderData['orderId']} ✅",
            $html,
            [
                'toName' => $orderData['customerName'],
                'bcc' => [['email' => FROM_EMAIL]] // BCC admin
            ]
        );
    }
    
    /**
     * Send shipping notification
     */
    public function sendShippingNotification($orderData) {
        $html = $this->getShippingNotificationTemplate($orderData);
        return $this->sendTransactionalEmail(
            $orderData['email'],
            "Your ATTRAL Order is on the way! 🚚",
            $html,
            ['toName' => $orderData['customerName']]
        );
    }
    
    /**
     * Send newsletter confirmation (double opt-in)
     */
    public function sendNewsletterConfirmation($email, $firstName, $confirmationToken) {
        $html = $this->getNewsletterConfirmationTemplate($firstName, $confirmationToken);
        return $this->sendTransactionalEmail(
            $email,
            "Confirm Your ATTRAL Newsletter Subscription 📧",
            $html,
            ['toName' => $firstName]
        );
    }
    
    /**
     * Send contact form confirmation
     */
    public function sendContactFormConfirmation($email, $name, $message) {
        $html = $this->getContactFormConfirmationTemplate($name);
        return $this->sendTransactionalEmail(
            $email,
            "We Received Your Message! 📬",
            $html,
            ['toName' => $name]
        );
    }
    
    /**
     * Send contact form notification to admin
     */
    public function sendContactFormNotification($email, $name, $message, $phone = '') {
        $html = $this->getContactFormAdminTemplate($name, $email, $message, $phone);
        return $this->sendTransactionalEmail(
            FROM_EMAIL,
            "New Contact Form Submission from {$name}",
            $html,
            ['replyTo' => $email, 'replyToName' => $name]
        );
    }
    
    /**
     * Send abandoned cart email
     */
    public function sendAbandonedCartEmail($email, $name, $cartItems, $stage = 1) {
        $html = $this->getAbandonedCartTemplate($name, $cartItems, $stage);
        $subjects = [
            1 => "You left something behind! 👀",
            2 => "Still interested? Here's 5% off 🎁",
            3 => "Last chance - 10% off expires soon! ⏰"
        ];
        return $this->sendTransactionalEmail(
            $email,
            $subjects[$stage] ?? $subjects[1],
            $html,
            ['toName' => $name]
        );
    }
    
    /**
     * Send post-purchase follow-up
     */
    public function sendPostPurchaseFollowup($email, $name, $orderId, $type = 'satisfaction') {
        $html = $this->getPostPurchaseTemplate($name, $orderId, $type);
        $subjects = [
            'satisfaction' => "How's your ATTRAL charger working? 😊",
            'review' => "Share your experience & get 10% off next order! ⭐"
        ];
        return $this->sendTransactionalEmail(
            $email,
            $subjects[$type] ?? $subjects['satisfaction'],
            $html,
            ['toName' => $name]
        );
    }
    
    /**
     * Send affiliate welcome email
     */
    public function sendAffiliateWelcome($email, $name, $affiliateCode, $addToList = true) {
        // Add to affiliate list
        if ($addToList) {
            $this->addToAffiliateList($email, $name, [
                'AFFILIATE_CODE' => $affiliateCode,
                'SIGNUP_DATE' => date('Y-m-d'),
                'STATUS' => 'active'
            ]);
        }
        
        $html = $this->getAffiliateWelcomeTemplate($name, $affiliateCode);
        return $this->sendTransactionalEmail(
            $email,
            "Welcome to ATTRAL Affiliate Program! 🎉",
            $html,
            ['toName' => $name]
        );
    }
    
    /**
     * Send affiliate commission notification
     */
    public function sendAffiliateCommissionNotification($email, $name, $commission, $orderId) {
        $html = $this->getAffiliateCommissionTemplate($name, $commission, $orderId);
        return $this->sendTransactionalEmail(
            $email,
            "You earned ₹{$commission}! 💰",
            $html,
            ['toName' => $name]
        );
    }
    
    /**
     * Make API request to Brevo
     */
    private function makeRequest($endpoint, $data, $method = 'POST') {
        // When running locally without cURL, simulate success
        if ($this->isLocalMode()) {
            return ['success' => true, 'data' => ['local' => true, 'endpoint' => $endpoint], 'httpCode' => 200];
        }
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'accept: application/json',
                'api-key: ' . $this->apiKey,
                'content-type: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            error_log("Brevo API Error: " . $curlError);
            return ['success' => false, 'error' => $curlError];
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $responseData, 'httpCode' => $httpCode];
        } else {
            error_log("Brevo API Error ({$httpCode}): " . $response);
            return ['success' => false, 'error' => $responseData, 'httpCode' => $httpCode];
        }
    }

    private function isLocalMode() {
        return isset($GLOBALS['LOCAL_MODE']) && $GLOBALS['LOCAL_MODE'] === true;
    }

    private function shouldSaveToFile() {
        $cfg = $GLOBALS['cfg'] ?? [];
        return isset($cfg['EMAIL_SAVE_TO_FILE']) ? (bool)$cfg['EMAIL_SAVE_TO_FILE'] : true;
    }

    private function getOutboxDir() {
        $cfg = $GLOBALS['cfg'] ?? [];
        $dir = $cfg['EMAIL_OUTBOX_DIR'] ?? (__DIR__ . '/tmp/mails');
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        return $dir;
    }

    private function sendViaPHPMailer($to, $subject, $htmlContent, $params) {
        try {
            $cfg = $GLOBALS['cfg'] ?? [];
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            
            // Use Brevo SMTP settings from config
            $host = $cfg['SMTP_HOST'] ?? 'smtp-relay.brevo.com';
            $port = intval($cfg['SMTP_PORT'] ?? 587);
            $secure = strtolower($cfg['SMTP_SECURE'] ?? 'tls');
            $username = $cfg['SMTP_USERNAME'] ?? '8c9aee002@smtp-brevo.com';
            $password = $cfg['SMTP_PASSWORD'] ?? '';
            
            $mail->Host = $host;
            $mail->Port = $port;
            
            if ($secure === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($secure === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = false;
            }
            
            $mail->SMTPAuth = true;
            $mail->Username = $username;
            $mail->Password = $password;

            $fromEmail = $params['fromEmail'] ?? (FROM_EMAIL);
            $fromName = $params['fromName'] ?? (FROM_NAME);
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to, $params['toName'] ?? '');
            if (!empty($params['replyTo'])) {
                $mail->addReplyTo($params['replyTo'], $params['replyToName'] ?? '');
            }
            if (!empty($params['cc'])) {
                foreach ($params['cc'] as $cc) { $mail->addCC($cc['email'], $cc['name'] ?? ''); }
            }
            if (!empty($params['bcc'])) {
                foreach ($params['bcc'] as $bcc) { $mail->addBCC($bcc['email'], $bcc['name'] ?? ''); }
            }
            if (!empty($params['attachments'])) {
                foreach ($params['attachments'] as $att) {
                    $content = base64_decode($att['content'] ?? '', true);
                    $name = $att['name'] ?? 'attachment';
                    $type = $att['type'] ?? 'application/octet-stream';
                    if ($content !== false) {
                        $mail->addStringAttachment($content, $name, 'base64', $type);
                    }
                }
            }

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->Subject = $subject;
            $mail->Body = $htmlContent;
            $mail->AltBody = strip_tags($htmlContent);

            $mail->send();
            return ['success' => true, 'data' => ['transport' => 'phpmailer_smtp', 'smtp_host' => $host]];
        } catch (Exception $e) {
            error_log('PHPMailer SMTP ERROR: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function saveEmailToFile($to, $subject, $htmlContent, $params) {
        $dir = $this->getOutboxDir();
        $filename = $dir . '/' . date('Ymd_His') . '_' . preg_replace('/[^a-z0-9]+/i', '_', $to) . '.eml';
        $headers = [];
        $fromEmail = $params['fromEmail'] ?? FROM_EMAIL;
        $fromName = $params['fromName'] ?? FROM_NAME;
        $headers[] = 'From: ' . sprintf('%s <%s>', $fromName, $fromEmail);
        $headers[] = 'To: ' . $to;
        $headers[] = 'Subject: ' . $subject;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $raw = implode("\r\n", $headers) . "\r\n\r\n" . $htmlContent;
        $ok = @file_put_contents($filename, $raw) !== false;
        if ($ok) {
            return ['success' => true, 'data' => ['local' => true, 'saved' => $filename]];
        }
        return ['success' => false, 'error' => 'Failed to save .eml'];
    }
    
    // ==================== EMAIL TEMPLATES ====================
    
    private function getEmailHeader($title = '') {
        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f8fafc; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background: linear-gradient(135deg, #ff6b35, #f7931e); padding: 40px 20px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; }
        .content { padding: 40px 30px; }
        .button { display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #667eea, #764ba2); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 20px 0; }
        .footer { background-color: #1f2937; color: #9ca3af; padding: 30px; text-align: center; font-size: 14px; }
        .footer a { color: #60a5fa; text-decoration: none; }
        .highlight { background-color: #fef3c7; padding: 20px; border-radius: 8px; border-left: 4px solid #f59e0b; margin: 20px 0; }
        .order-summary { background-color: #f9fafb; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .order-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
        .total { font-weight: 700; font-size: 18px; color: #1f2937; }
    </style>
</head>
<body>
    <div class="container">';
    }
    
    private function getEmailFooter() {
        return '
        <div class="footer">
            <p><strong>ATTRAL Electronics</strong></p>
            <p>Premium GaN Chargers for Modern Life</p>
            <p>
                <a href="https://attral.in">Visit Website</a> | 
                <a href="https://attral.in/shop.html">Shop Now</a> | 
                <a href="https://attral.in/contact.html">Contact Us</a>
            </p>
            <p style="font-size: 12px; color: #6b7280; margin-top: 20px;">
                This email was sent to you by ATTRAL Electronics. 
                <a href="{{unsubscribe}}">Unsubscribe</a>
            </p>
            <p style="font-size: 12px; color: #6b7280;">
                © ' . date('Y') . ' ATTRAL Electronics. All rights reserved.
            </p>
        </div>
    </div>
</body>
</html>';
    }
    
    private function getWelcomeEmailTemplate($firstName) {
        return $this->getEmailHeader('Welcome to ATTRAL!') . '
        <div class="header">
            <h1>🚀 Welcome to ATTRAL, ' . htmlspecialchars($firstName) . '!</h1>
        </div>
        <div class="content">
            <p style="font-size: 18px; color: #1f2937;">Thank you for joining the ATTRAL family!</p>
            
            <p style="color: #4b5563; line-height: 1.6;">
                We\'re thrilled to have you with us. Get ready to experience the future of charging with our premium GaN technology chargers.
            </p>
            
            <div class="highlight">
                <h3 style="margin-top: 0; color: #f59e0b;">🎁 Welcome Gift Inside!</h3>
                <p style="margin-bottom: 0;"><strong>FREE SHIPPING</strong> on your first order. Use code: <strong>WELCOME2024</strong></p>
            </div>
            
            <h3 style="color: #1f2937;">Why Choose ATTRAL?</h3>
            <ul style="color: #4b5563; line-height: 1.8;">
                <li>⚡ 100W Power - Charge up to 8 devices simultaneously</li>
                <li>🔥 GaN Technology - 40% smaller than traditional chargers</li>
                <li>🛡️ Advanced Safety - Multiple protection systems</li>
                <li>🚚 Free Shipping - Delivered across India</li>
                <li>✅ 1 Year Warranty - Peace of mind guaranteed</li>
            </ul>
            
            <div style="text-align: center;">
                <a href="https://attral.in/shop.html" class="button">Start Shopping Now</a>
            </div>
            
            <p style="color: #4b5563; line-height: 1.6;">
                Have questions? Our support team is here to help! Reply to this email or visit our <a href="https://attral.in/contact.html" style="color: #667eea;">contact page</a>.
            </p>
            
            <p style="color: #4b5563;">
                Happy Charging! ⚡<br>
                <strong>Team ATTRAL</strong>
            </p>
        </div>
        ' . $this->getEmailFooter();
    }
    
    /**
     * Send invoice email with PDF attachment
     */
    public function sendInvoiceEmail($customerEmail, $orderNumber, $invoicePath, $orderData = []) {
        try {
            // Prepare attachment data
            $attachments = [];
            if (file_exists($invoicePath)) {
                $attachments[] = [
                    'content' => base64_encode(file_get_contents($invoicePath)),
                    'name' => $orderNumber . '.pdf',
                    'type' => 'application/pdf'
                ];
            }
            
            // Generate invoice email template
            $htmlContent = $this->getInvoiceEmailTemplate($orderNumber, $orderData);
            
            // Send email with attachment
            $params = [
                'attachments' => $attachments,
                'toName' => $orderData['customerName'] ?? 'Valued Customer'
            ];
            
            $result = $this->sendTransactionalEmail(
                $customerEmail,
                "📄 Your Invoice - Order #$orderNumber",
                $htmlContent,
                $params
            );
            
            if ($result['success']) {
                error_log("INVOICE EMAIL: Successfully sent invoice for order $orderNumber to $customerEmail");
            } else {
                error_log("INVOICE EMAIL ERROR: Failed to send invoice for order $orderNumber: " . ($result['error'] ?? 'Unknown error'));
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("INVOICE EMAIL EXCEPTION: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function getInvoiceEmailTemplate($orderNumber, $orderData) {
        $customerName = $orderData['customerName'] ?? 'Valued Customer';
        $totalAmount = $orderData['total'] ?? 0;
        $orderDate = $orderData['orderDate'] ?? date('F j, Y');
        
        return $this->getEmailHeader('Your Invoice is Ready!') . '
        <div class="header">
            <h1>📄 Your Invoice is Ready!</h1>
        </div>
        <div class="content">
            <p style="font-size: 18px; color: #1f2937;">Dear ' . htmlspecialchars($customerName) . ',</p>
            
            <p style="color: #4b5563; line-height: 1.6;">
                Thank you for your order! Your invoice has been generated and is attached to this email.
            </p>
            
            <div class="highlight">
                <h3 style="margin-top: 0;">📋 Invoice Details</h3>
                <p><strong>Invoice Number:</strong> ' . htmlspecialchars($orderNumber) . '</p>
                <p><strong>Order Date:</strong> ' . htmlspecialchars($orderDate) . '</p>
                <p><strong>Total Amount:</strong> ₹' . number_format($totalAmount, 2) . '</p>
                <p style="margin-bottom: 0;"><strong>Payment Status:</strong> <span style="color: #10b981;">✅ Paid</span></p>
            </div>
            
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 12px; text-align: center; margin: 20px 0;">
                <h3 style="margin-top: 0; color: white;">📎 Invoice Attached</h3>
                <p style="margin-bottom: 0; font-size: 16px;">Your detailed invoice is attached as a PDF file. Please keep this for your records.</p>
            </div>
            
            <h3 style="color: #1f2937;">📝 What\'s Included in Your Invoice:</h3>
            <ul style="color: #4b5563; line-height: 1.8;">
                <li>📦 Complete order details and itemized breakdown</li>
                <li>💰 Payment information and transaction details</li>
                <li>🏠 Billing and shipping address information</li>
                <li>📅 Order date and invoice generation timestamp</li>
                <li>🏢 Official ATTRAL tax invoice for your records</li>
            </ul>
            
            <div style="text-align: center;">
                <a href="https://attral.in/my-orders.html" class="button">View Order Status</a>
            </div>
            
            <p style="color: #4b5563; line-height: 1.6;">
                <strong>Need Assistance?</strong><br>
                If you have any questions about your invoice or order, please don\'t hesitate to contact our support team. We\'re here to help!
            </p>
            
            <p style="color: #4b5563;">
                Thank you for choosing ATTRAL! ⚡<br>
                <strong>Team ATTRAL</strong>
            </p>
        </div>
        ' . $this->getEmailFooter();
    }

    private function getOrderConfirmationTemplate($orderData) {
        $itemsHtml = '';
        foreach ($orderData['items'] as $item) {
            $itemsHtml .= '<div class="order-item">
                <span>' . htmlspecialchars($item['name']) . ' × ' . $item['quantity'] . '</span>
                <span>₹' . number_format($item['price'] * $item['quantity'], 2) . '</span>
            </div>';
        }
        
        return $this->getEmailHeader('Order Confirmed') . '
        <div class="header">
            <h1>✅ Order Confirmed!</h1>
        </div>
        <div class="content">
            <p style="font-size: 18px; color: #1f2937;">Hi ' . htmlspecialchars($orderData['customerName']) . ',</p>
            
            <p style="color: #4b5563; line-height: 1.6;">
                Thank you for your order! We\'re excited to get your ATTRAL products to you.
            </p>
            
            <div class="highlight">
                <h3 style="margin-top: 0;">📦 Order Details</h3>
                <p><strong>Order ID:</strong> ' . htmlspecialchars($orderData['orderId']) . '</p>
                <p><strong>Order Date:</strong> ' . date('F j, Y') . '</p>
                <p style="margin-bottom: 0;"><strong>Payment Status:</strong> <span style="color: #10b981;">Confirmed</span></p>
            </div>
            
            <div class="order-summary">
                <h3 style="margin-top: 0; color: #1f2937;">Order Summary</h3>
                ' . $itemsHtml . '
                <div class="order-item total">
                    <span>Total Amount</span>
                    <span>₹' . number_format($orderData['total'], 2) . '</span>
                </div>
            </div>
            
            <h3 style="color: #1f2937;">📍 Shipping Address</h3>
            <p style="color: #4b5563; background-color: #f9fafb; padding: 15px; border-radius: 8px;">
                ' . nl2br(htmlspecialchars($orderData['shippingAddress'])) . '
            </p>
            
            <div style="text-align: center;">
                <a href="https://attral.in/my-orders.html" class="button">Track Your Order</a>
            </div>
            
            <p style="color: #4b5563; font-size: 14px;">
                <strong>What\'s Next?</strong><br>
                • We\'ll send you a shipping confirmation with tracking details once your order is dispatched<br>
                • Expected delivery: 3-5 business days<br>
                • Need help? Contact us anytime!
            </p>
            
            <p style="color: #4b5563;">
                Thank you for choosing ATTRAL! 🙏<br>
                <strong>Team ATTRAL</strong>
            </p>
        </div>
        ' . $this->getEmailFooter();
    }
    
    private function getShippingNotificationTemplate($orderData) {
        return $this->getEmailHeader('Order Shipped') . '
        <div class="header">
            <h1>🚚 Your Order is on the Way!</h1>
        </div>
        <div class="content">
            <p style="font-size: 18px; color: #1f2937;">Great news, ' . htmlspecialchars($orderData['customerName']) . '!</p>
            
            <p style="color: #4b5563; line-height: 1.6;">
                Your ATTRAL order has been shipped and is on its way to you!
            </p>
            
            <div class="highlight">
                <h3 style="margin-top: 0;">📦 Shipping Information</h3>
                <p><strong>Order ID:</strong> ' . htmlspecialchars($orderData['orderId']) . '</p>
                <p><strong>Tracking Number:</strong> ' . htmlspecialchars($orderData['trackingNumber'] ?? 'Will be updated soon') . '</p>
                <p><strong>Carrier:</strong> ' . htmlspecialchars($orderData['carrier'] ?? 'Standard Shipping') . '</p>
                <p style="margin-bottom: 0;"><strong>Expected Delivery:</strong> ' . ($orderData['estimatedDelivery'] ?? '3-5 business days') . '</p>
            </div>
            
            ' . (isset($orderData['trackingUrl']) ? '
            <div style="text-align: center;">
                <a href="' . htmlspecialchars($orderData['trackingUrl']) . '" class="button">Track Your Shipment</a>
            </div>
            ' : '') . '
            
            <h3 style="color: #1f2937;">📍 Delivery Address</h3>
            <p style="color: #4b5563; background-color: #f9fafb; padding: 15px; border-radius: 8px;">
                ' . nl2br(htmlspecialchars($orderData['shippingAddress'])) . '
            </p>
            
            <p style="color: #4b5563; font-size: 14px;">
                <strong>Important:</strong><br>
                • Please ensure someone is available to receive the package<br>
                • You\'ll receive updates via SMS and email<br>
                • Contact us if you have any questions
            </p>
            
            <p style="color: #4b5563;">
                We hope you love your new ATTRAL charger! 💙<br>
                <strong>Team ATTRAL</strong>
            </p>
        </div>
        ' . $this->getEmailFooter();
    }
    
    private function getNewsletterConfirmationTemplate($firstName, $confirmationToken) {
        $confirmUrl = "https://attral.in/confirm-newsletter.html?token=" . urlencode($confirmationToken);
        
        return $this->getEmailHeader('Confirm Newsletter') . '
        <div class="header">
            <h1>📧 Confirm Your Subscription</h1>
        </div>
        <div class="content">
            <p style="font-size: 18px; color: #1f2937;">Hi ' . htmlspecialchars($firstName) . ',</p>
            
            <p style="color: #4b5563; line-height: 1.6;">
                Thanks for subscribing to the ATTRAL newsletter! Just one more step to complete your subscription.
            </p>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="' . htmlspecialchars($confirmUrl) . '" class="button">Confirm My Subscription</a>
            </div>
            
            <h3 style="color: #1f2937;">📬 What You\'ll Get:</h3>
            <ul style="color: #4b5563; line-height: 1.8;">
                <li>🎁 Exclusive discounts and early access to sales</li>
                <li>⚡ Charging tips and tech insights</li>
                <li>📱 New product announcements</li>
                <li>💡 How-to guides and tutorials</li>
            </ul>
            
            <p style="color: #6b7280; font-size: 14px; margin-top: 30px;">
                If you didn\'t subscribe to this newsletter, you can safely ignore this email.
            </p>
            
            <p style="color: #4b5563;">
                Looking forward to sharing great content with you! 🚀<br>
                <strong>Team ATTRAL</strong>
            </p>
        </div>
        ' . $this->getEmailFooter();
    }
    
    private function getContactFormConfirmationTemplate($name) {
        return $this->getEmailHeader('Message Received') . '
        <div class="header">
            <h1>📬 We Received Your Message!</h1>
        </div>
        <div class="content">
            <p style="font-size: 18px; color: #1f2937;">Hi ' . htmlspecialchars($name) . ',</p>
            
            <p style="color: #4b5563; line-height: 1.6;">
                Thank you for reaching out to ATTRAL! We\'ve received your message and our team will get back to you shortly.
            </p>
            
            <div class="highlight">
                <h3 style="margin-top: 0;">⏱️ What to Expect</h3>
                <p style="margin-bottom: 0;">
                    Our support team typically responds within <strong>24 hours</strong> (Monday-Friday). 
                    For urgent matters, please call us at <strong>+91-XXXXX-XXXXX</strong>.
                </p>
            </div>
            
            <h3 style="color: #1f2937;">📚 While You Wait:</h3>
            <ul style="color: #4b5563; line-height: 1.8;">
                <li>Check out our <a href="https://attral.in/blog.html" style="color: #667eea;">blog</a> for charging tips</li>
                <li>Browse our <a href="https://attral.in/shop.html" style="color: #667eea;">product catalog</a></li>
                <li>Join our <a href="https://attral.in/affiliates.html" style="color: #667eea;">affiliate program</a></li>
            </ul>
            
            <p style="color: #4b5563;">
                We appreciate your patience! 🙏<br>
                <strong>Team ATTRAL</strong>
            </p>
        </div>
        ' . $this->getEmailFooter();
    }
    
    private function getContactFormAdminTemplate($name, $email, $message, $phone) {
        return $this->getEmailHeader('New Contact Form') . '
        <div class="header">
            <h1>📨 New Contact Form Submission</h1>
        </div>
        <div class="content">
            <div class="highlight">
                <h3 style="margin-top: 0;">Contact Details</h3>
                <p><strong>Name:</strong> ' . htmlspecialchars($name) . '</p>
                <p><strong>Email:</strong> <a href="mailto:' . htmlspecialchars($email) . '">' . htmlspecialchars($email) . '</a></p>
                ' . ($phone ? '<p><strong>Phone:</strong> ' . htmlspecialchars($phone) . '</p>' : '') . '
                <p><strong>Submitted:</strong> ' . date('F j, Y g:i A') . '</p>
            </div>
            
            <h3 style="color: #1f2937;">Message:</h3>
            <div style="background-color: #f9fafb; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea;">
                <p style="color: #1f2937; white-space: pre-wrap;">' . nl2br(htmlspecialchars($message)) . '</p>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="mailto:' . htmlspecialchars($email) . '?subject=Re: Your inquiry to ATTRAL" class="button">Reply to ' . htmlspecialchars($name) . '</a>
            </div>
        </div>
        ' . $this->getEmailFooter();
    }
    
    private function getAbandonedCartTemplate($name, $cartItems, $stage) {
        $discount = ['', '5', '10'][$stage - 1] ?? '';
        $urgency = $stage === 3 ? '<p style="color: #dc2626; font-weight: 600;">⏰ This offer expires in 24 hours!</p>' : '';
        
        return $this->getEmailHeader('Cart Reminder') . '
        <div class="header">
            <h1>' . ['👀 You Left Something Behind!', '🎁 Special Offer Inside!', '⏰ Last Chance!'][$stage - 1] . '</h1>
        </div>
        <div class="content">
            <p style="font-size: 18px; color: #1f2937;">Hi ' . htmlspecialchars($name) . ',</p>
            
            <p style="color: #4b5563; line-height: 1.6;">
                We noticed you left some great items in your cart. Don\'t miss out on powering up your devices!
            </p>
            
            ' . ($discount ? '<div class="highlight">
                <h3 style="margin-top: 0;">🎁 Special Offer for You!</h3>
                <p style="font-size: 24px; font-weight: 700; color: #f59e0b; margin: 10px 0;">' . $discount . '% OFF</p>
                <p>Use code: <strong>COMEBACK' . $discount . '</strong> at checkout</p>
                ' . $urgency . '
            </div>' : '') . '
            
            <h3 style="color: #1f2937;">Your Cart Items:</h3>
            <div style="background-color: #f9fafb; padding: 20px; border-radius: 8px;">
                <p style="color: #4b5563;">ATTRAL 100W GaN Charger and more...</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="https://attral.in/cart.html" class="button">Complete My Purchase</a>
            </div>
            
            <p style="color: #4b5563;">
                Questions? We\'re here to help! 💙<br>
                <strong>Team ATTRAL</strong>
            </p>
        </div>
        ' . $this->getEmailFooter();
    }
    
    private function getPostPurchaseTemplate($name, $orderId, $type) {
        if ($type === 'review') {
            return $this->getEmailHeader('Share Your Experience') . '
            <div class="header">
                <h1>⭐ How\'s Your ATTRAL Charger?</h1>
            </div>
            <div class="content">
                <p style="font-size: 18px; color: #1f2937;">Hi ' . htmlspecialchars($name) . ',</p>
                
                <p style="color: #4b5563; line-height: 1.6;">
                    We hope you\'re loving your ATTRAL charger! Your feedback helps us improve and helps others make informed decisions.
                </p>
                
                <div class="highlight">
                    <h3 style="margin-top: 0;">🎁 Leave a Review, Get 10% Off!</h3>
                    <p style="margin-bottom: 0;">Share your experience and we\'ll send you a <strong>10% discount code</strong> for your next purchase!</p>
                </div>
                
                <div style="text-align: center; margin: 30px 0;">
                    <a href="https://attral.in/review.html?order=' . urlencode($orderId) . '" class="button">Write a Review</a>
                </div>
                
                <p style="color: #4b5563;">
                    Thank you for choosing ATTRAL! 🙏<br>
                    <strong>Team ATTRAL</strong>
                </p>
            </div>
            ' . $this->getEmailFooter();
        } else {
            return $this->getEmailHeader('How Are You Enjoying ATTRAL?') . '
            <div class="header">
                <h1>😊 How\'s Everything Going?</h1>
            </div>
            <div class="content">
                <p style="font-size: 18px; color: #1f2937;">Hi ' . htmlspecialchars($name) . ',</p>
                
                <p style="color: #4b5563; line-height: 1.6;">
                    It\'s been a week since your ATTRAL charger arrived. We\'d love to hear how it\'s working for you!
                </p>
                
                <h3 style="color: #1f2937;">📱 Getting the Most Out of Your Charger:</h3>
                <ul style="color: #4b5563; line-height: 1.8;">
                    <li>Use certified cables for optimal charging speed</li>
                    <li>Keep the charger in a well-ventilated area</li>
                    <li>Clean ports regularly with compressed air</li>
                </ul>
                
                <p style="color: #4b5563;">
                    Have questions or feedback? We\'re all ears! 👂<br>
                    <strong>Team ATTRAL</strong>
                </p>
            </div>
            ' . $this->getEmailFooter();
        }
    }
    
    private function getAffiliateWelcomeTemplate($name, $affiliateCode) {
        return $this->getEmailHeader('Welcome Affiliate') . '
        <div class="header">
            <h1>🎉 Welcome to ATTRAL Affiliate Program!</h1>
        </div>
        <div class="content">
            <p style="font-size: 18px; color: #1f2937;">Congratulations, ' . htmlspecialchars($name) . '!</p>
            
            <p style="color: #4b5563; line-height: 1.6;">
                You\'re now an official ATTRAL affiliate! Start earning commissions by sharing products you love.
            </p>
            
            <div class="highlight">
                <h3 style="margin-top: 0;">🔑 Your Affiliate Code</h3>
                <p style="font-size: 24px; font-weight: 700; color: #667eea; margin: 10px 0;">' . htmlspecialchars($affiliateCode) . '</p>
                <p style="margin-bottom: 0;">Share your unique link: <strong>attral.in?ref=' . htmlspecialchars($affiliateCode) . '</strong></p>
            </div>
            
            <h3 style="color: #1f2937;">💰 Commission Structure:</h3>
            <ul style="color: #4b5563; line-height: 1.8;">
                <li>📈 <strong>10% commission</strong> on every sale</li>
                <li>💳 Monthly payouts via bank transfer</li>
                <li>📊 Real-time dashboard to track earnings</li>
                <li>🎯 Marketing materials provided</li>
            </ul>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="https://attral.in/affiliate-dashboard.html" class="button">Go to Dashboard</a>
            </div>
            
            <p style="color: #4b5563;">
                Let\'s make great things happen together! 🚀<br>
                <strong>Team ATTRAL</strong>
            </p>
        </div>
        ' . $this->getEmailFooter();
    }
    
    private function getAffiliateCommissionTemplate($name, $commission, $orderId) {
        return $this->getEmailHeader('Commission Earned') . '
        <div class="header">
            <h1>💰 You Earned a Commission!</h1>
        </div>
        <div class="content">
            <p style="font-size: 18px; color: #1f2937;">Great news, ' . htmlspecialchars($name) . '!</p>
            
            <p style="color: #4b5563; line-height: 1.6;">
                You just earned a commission from a successful referral!
            </p>
            
            <div class="highlight">
                <h3 style="margin-top: 0;">💵 Commission Details</h3>
                <p style="font-size: 32px; font-weight: 700; color: #10b981; margin: 10px 0;">₹' . number_format($commission, 2) . '</p>
                <p><strong>Order ID:</strong> ' . htmlspecialchars($orderId) . '</p>
                <p style="margin-bottom: 0;"><strong>Date:</strong> ' . date('F j, Y') . '</p>
            </div>
            
            <div style="text-align: center; margin: 30px 0;">
                <a href="https://attral.in/affiliate-dashboard.html" class="button">View Dashboard</a>
            </div>
            
            <p style="color: #4b5563; font-size: 14px;">
                💡 <strong>Tip:</strong> Keep sharing to maximize your earnings! Commissions are paid monthly when you reach ₹1,000.
            </p>
            
            <p style="color: #4b5563;">
                Keep up the excellent work! 🌟<br>
                <strong>Team ATTRAL</strong>
            </p>
        </div>
        ' . $this->getEmailFooter();
    }
}

// ==================== API ENDPOINTS ====================

if (php_sapi_name() !== 'cli' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
    
    $service = new BrevoEmailService();
    $action = $input['action'];
    
    try {
        switch ($action) {
            case 'welcome':
                $result = $service->sendWelcomeEmail($input['email'], $input['firstName']);
                break;
                
            case 'order_confirmation':
                $result = $service->sendOrderConfirmation($input['orderData']);
                break;
                
            case 'shipping':
                $result = $service->sendShippingNotification($input['orderData']);
                break;
                
            case 'newsletter_confirmation':
                $result = $service->sendNewsletterConfirmation($input['email'], $input['firstName'], $input['token']);
                break;
                
            case 'contact_confirmation':
                $result = $service->sendContactFormConfirmation($input['email'], $input['name'], $input['message']);
                break;
                
            case 'contact_admin':
                $result = $service->sendContactFormNotification($input['email'], $input['name'], $input['message'], $input['phone'] ?? '');
                break;
                
            case 'abandoned_cart':
                $result = $service->sendAbandonedCartEmail($input['email'], $input['name'], $input['cartItems'], $input['stage']);
                break;
                
            case 'post_purchase':
                $result = $service->sendPostPurchaseFollowup($input['email'], $input['name'], $input['orderId'], $input['type']);
                break;
                
            case 'affiliate_welcome':
                $result = $service->sendAffiliateWelcome($input['email'], $input['name'], $input['affiliateCode']);
                break;
                
            case 'affiliate_commission':
                $result = $service->sendAffiliateCommissionNotification($input['email'], $input['name'], $input['commission'], $input['orderId']);
                break;
                
            case 'add_to_list':
                $result = $service->addToList(
                    $input['email'], 
                    $input['firstName'] ?? '', 
                    $input['attributes'] ?? [],
                    $input['listType'] ?? 'customer'
                );
                break;
                
            case 'add_to_customer_list':
                $result = $service->addToCustomerList($input['email'], $input['firstName'] ?? '', $input['attributes'] ?? []);
                break;
                
            case 'add_to_affiliate_list':
                $result = $service->addToAffiliateList($input['email'], $input['firstName'] ?? '', $input['attributes'] ?? []);
                break;
                
            case 'remove_from_list':
                $result = $service->removeFromList($input['email'], $input['listType'] ?? 'customer');
                break;
                
            case 'get_contact':
                $result = $service->getContact($input['email']);
                break;
                
            case 'update_contact':
                $result = $service->updateContact($input['email'], $input['attributes'] ?? []);
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Unknown action']);
                exit;
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Email Service Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} elseif (php_sapi_name() !== 'cli') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>



