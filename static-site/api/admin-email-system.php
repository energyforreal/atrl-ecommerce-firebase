<?php
/**
 * 📧 ATTRAL Admin Email System
 * Custom email functionality for admin dashboard
 * 
 * Features:
 * - Send custom emails to Firestore users and affiliates
 * - Access Brevo contact lists
 * - Email templates and bulk sending
 * - Integration with existing Brevo SMTP setup
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

// Load project config and dependencies
$cfg = @include __DIR__ . '/config.php';
$LOCAL_MODE = isset($cfg['LOCAL_MODE']) ? (bool)$cfg['LOCAL_MODE'] : false;

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

// Load Firebase for Firestore access
require_once __DIR__ . '/firestore_admin_service.php';

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

class AdminEmailSystem {
    
    private $apiKey;
    private $apiUrl;
    private $firestoreService;
    
    public function __construct() {
        $this->apiKey = BREVO_API_KEY;
        $this->apiUrl = BREVO_API_URL;
        $this->firestoreService = new FirestoreAdminService();
    }
    
    /**
     * Send custom email to single recipient
     */
    public function sendCustomEmail($to, $subject, $htmlContent, $params = []) {
        try {
            $result = $this->sendViaPHPMailer($to, $subject, $htmlContent, $params);
            
            if ($result['success']) {
                error_log("ADMIN EMAIL: Successfully sent to $to");
                return $result;
            }
            
            // Fallback to file save if enabled
            if ($this->shouldSaveToFile()) {
                $saved = $this->saveEmailToFile($to, $subject, $htmlContent, $params);
                if ($saved['success']) {
                    error_log("ADMIN EMAIL: Saved to file as fallback for $to");
                    return $saved;
                }
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("ADMIN EMAIL ERROR: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send bulk emails to multiple recipients
     */
    public function sendBulkEmails($recipients, $subject, $htmlContent, $params = []) {
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($recipients as $recipient) {
            $to = is_array($recipient) ? $recipient['email'] : $recipient;
            $toName = is_array($recipient) ? ($recipient['name'] ?? '') : '';
            
            $emailParams = array_merge($params, ['toName' => $toName]);
            $result = $this->sendCustomEmail($to, $subject, $htmlContent, $emailParams);
            
            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;
            }
            
            $results[] = [
                'email' => $to,
                'name' => $toName,
                'success' => $result['success'],
                'error' => $result['error'] ?? null
            ];
            
            // Add small delay to avoid rate limiting
            usleep(100000); // 0.1 second delay
        }
        
        return [
            'success' => $failureCount === 0,
            'totalSent' => count($recipients),
            'successCount' => $successCount,
            'failureCount' => $failureCount,
            'results' => $results
        ];
    }
    
    /**
     * Get Firestore users for email campaigns
     */
    public function getFirestoreUsers($type = 'all', $limit = 100) {
        try {
            $users = [];
            
            if ($type === 'all' || $type === 'customers') {
                $customerUsers = $this->firestoreService->getCollection('users', [
                    'orderBy' => 'created_at',
                    'direction' => 'desc',
                    'limit' => $limit
                ]);
                
                foreach ($customerUsers as $user) {
                    if (!empty($user['email'])) {
                        $users[] = [
                            'id' => $user['id'] ?? '',
                            'email' => $user['email'],
                            'name' => $user['displayName'] ?? $user['name'] ?? 'Customer',
                            'type' => 'customer',
                            'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
                            'is_affiliate' => $user['is_affiliate'] ?? false
                        ];
                    }
                }
            }
            
            if ($type === 'all' || $type === 'affiliates') {
                $affiliateUsers = $this->firestoreService->getCollection('affiliates', [
                    'orderBy' => 'createdAt',
                    'direction' => 'desc',
                    'limit' => $limit
                ]);
                
                foreach ($affiliateUsers as $affiliate) {
                    if (!empty($affiliate['email'])) {
                        $users[] = [
                            'id' => $affiliate['id'] ?? '',
                            'email' => $affiliate['email'],
                            'name' => $affiliate['displayName'] ?? $affiliate['name'] ?? 'Affiliate',
                            'type' => 'affiliate',
                            'code' => $affiliate['code'] ?? '',
                            'created_at' => $affiliate['createdAt'] ?? date('Y-m-d H:i:s'),
                            'is_affiliate' => true
                        ];
                    }
                }
            }
            
            return [
                'success' => true,
                'users' => $users,
                'total' => count($users)
            ];
            
        } catch (Exception $e) {
            error_log("FIRESTORE USERS ERROR: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get Brevo contact lists
     */
    public function getBrevoContactLists() {
        try {
            $result = $this->makeBrevoRequest('/contacts/lists', [], 'GET');
            
            if ($result['success']) {
                $lists = [];
                foreach ($result['data']['lists'] ?? [] as $list) {
                    $lists[] = [
                        'id' => $list['id'],
                        'name' => $list['name'],
                        'totalBlacklisted' => $list['totalBlacklisted'] ?? 0,
                        'totalSubscribers' => $list['totalSubscribers'] ?? 0,
                        'uniqueSubscribers' => $list['uniqueSubscribers'] ?? 0
                    ];
                }
                
                return [
                    'success' => true,
                    'lists' => $lists
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("BREVO LISTS ERROR: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get contacts from specific Brevo list
     */
    public function getBrevoContacts($listId, $limit = 100, $offset = 0) {
        try {
            $result = $this->makeBrevoRequest(
                "/contacts/lists/{$listId}/contacts?limit={$limit}&offset={$offset}",
                [],
                'GET'
            );
            
            if ($result['success']) {
                $contacts = [];
                foreach ($result['data']['contacts'] ?? [] as $contact) {
                    $contacts[] = [
                        'email' => $contact['email'],
                        'attributes' => $contact['attributes'] ?? [],
                        'listIds' => $contact['listIds'] ?? [],
                        'emailBlacklisted' => $contact['emailBlacklisted'] ?? false,
                        'smsBlacklisted' => $contact['smsBlacklisted'] ?? false,
                        'createdAt' => $contact['createdAt'] ?? '',
                        'modifiedAt' => $contact['modifiedAt'] ?? ''
                    ];
                }
                
                return [
                    'success' => true,
                    'contacts' => $contacts,
                    'total' => count($contacts)
                ];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("BREVO CONTACTS ERROR: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Add contact to Brevo list
     */
    public function addContactToBrevoList($email, $firstName = '', $listId = BREVO_CUSTOMER_LIST_ID, $attributes = []) {
        try {
            $data = [
                'email' => $email,
                'listIds' => [$listId],
                'updateEnabled' => true
            ];
            
            $attrs = array_merge(['FIRSTNAME' => $firstName], $attributes);
            if (!empty($attrs)) {
                $data['attributes'] = $attrs;
            }
            
            return $this->makeBrevoRequest('/contacts', $data);
            
        } catch (Exception $e) {
            error_log("ADD BREVO CONTACT ERROR: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Create email template
     */
    public function createEmailTemplate($name, $subject, $htmlContent, $textContent = '') {
        $html = $this->getEmailTemplate($subject, $htmlContent);
        return $html;
    }
    
    /**
     * Send email via PHPMailer with Brevo SMTP
     */
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
            $password = $cfg['SMTP_PASSWORD'] ?? 'FXr1TZ9mQ0aEVqjp';
            
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

            $fromEmail = $params['fromEmail'] ?? FROM_EMAIL;
            $fromName = $params['fromName'] ?? FROM_NAME;
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to, $params['toName'] ?? '');
            
            if (!empty($params['replyTo'])) {
                $mail->addReplyTo($params['replyTo'], $params['replyToName'] ?? '');
            }
            if (!empty($params['cc'])) {
                foreach ($params['cc'] as $cc) { 
                    $mail->addCC($cc['email'], $cc['name'] ?? ''); 
                }
            }
            if (!empty($params['bcc'])) {
                foreach ($params['bcc'] as $bcc) { 
                    $mail->addBCC($bcc['email'], $bcc['name'] ?? ''); 
                }
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
    
    /**
     * Make API request to Brevo
     */
    private function makeBrevoRequest($endpoint, $data, $method = 'POST') {
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
    
    /**
     * Generate email template with ATTRAL branding
     */
    private function getEmailTemplate($subject, $content) {
        return '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($subject) . '</title>
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f8fafc; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 40px 20px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; }
        .content { padding: 40px 30px; }
        .footer { background-color: #1f2937; color: #9ca3af; padding: 30px; text-align: center; font-size: 14px; }
        .footer a { color: #60a5fa; text-decoration: none; }
        .button { display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #667eea, #764ba2); color: #ffffff; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚡ ATTRAL Electronics</h1>
        </div>
        <div class="content">
            ' . $content . '
        </div>
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
}

// ==================== API ENDPOINTS ====================

if (php_sapi_name() !== 'cli' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['action'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid request']);
        exit;
    }
    
    $emailSystem = new AdminEmailSystem();
    $action = $input['action'];
    
    try {
        switch ($action) {
            case 'send_custom_email':
                $htmlContent = $emailSystem->createEmailTemplate(
                    $input['subject'] ?? 'Custom Email',
                    $input['subject'] ?? 'Custom Email',
                    $input['content'] ?? ''
                );
                $result = $emailSystem->sendCustomEmail(
                    $input['to'],
                    $input['subject'],
                    $htmlContent,
                    $input['params'] ?? []
                );
                break;
                
            case 'send_bulk_emails':
                $htmlContent = $emailSystem->createEmailTemplate(
                    $input['subject'] ?? 'Bulk Email',
                    $input['subject'] ?? 'Bulk Email',
                    $input['content'] ?? ''
                );
                $result = $emailSystem->sendBulkEmails(
                    $input['recipients'] ?? [],
                    $input['subject'],
                    $htmlContent,
                    $input['params'] ?? []
                );
                break;
                
            case 'get_firestore_users':
                $result = $emailSystem->getFirestoreUsers(
                    $input['type'] ?? 'all',
                    $input['limit'] ?? 100
                );
                break;
                
            case 'get_brevo_lists':
                $result = $emailSystem->getBrevoContactLists();
                break;
                
            case 'get_brevo_contacts':
                $result = $emailSystem->getBrevoContacts(
                    $input['listId'] ?? BREVO_CUSTOMER_LIST_ID,
                    $input['limit'] ?? 100,
                    $input['offset'] ?? 0
                );
                break;
                
            case 'add_contact_to_brevo':
                $result = $emailSystem->addContactToBrevoList(
                    $input['email'],
                    $input['firstName'] ?? '',
                    $input['listId'] ?? BREVO_CUSTOMER_LIST_ID,
                    $input['attributes'] ?? []
                );
                break;
                
            default:
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Unknown action']);
                exit;
        }
        
        echo json_encode($result);
        
    } catch (Exception $e) {
        error_log("Admin Email System Error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} elseif (php_sapi_name() !== 'cli') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>
