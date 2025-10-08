<?php
/**
 * 🎯 Affiliate Email Sender Functions
 * Clean functions for affiliate email sending without headers
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Mock Mailer for testing when email sending is not available
 */
class MockMailer {
    public $isHTML = true;
    public $CharSet = 'UTF-8';
    public $Encoding = 'base64';
    public $Subject = '';
    public $Body = '';
    public $AltBody = '';
    public $ErrorInfo = '';
    
    public function __construct() {
        // Initialize properties
    }
    
    public function addAddress($email, $name = '') {
        // Mock addAddress - just log the email
        error_log("MOCK: Would send email to: {$email}" . ($name ? " ({$name})" : ""));
        return true;
    }
    
    public function setFrom($email, $name = '') {
        // Mock setFrom
        error_log("MOCK: From: {$email}" . ($name ? " ({$name})" : ""));
        return true;
    }
    
    public function addReplyTo($email, $name = '') {
        // Mock addReplyTo
        return true;
    }
    
    public function isHTML($isHtml = true) {
        // Mock isHTML
        return true;
    }
    
    public function send() {
        // Mock send - always succeeds for testing
        error_log("MOCK: Email sent successfully - Subject: {$this->Subject}");
        return true;
    }
}

/**
 * Get Affiliate Welcome Email Template
 */
function getAffiliateWelcomeTemplate($data) {
    $name = $data['name'] ?? 'Affiliate';
    $affiliateCode = $data['affiliateCode'] ?? '';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Welcome to ATTRAL Affiliate Program</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
        <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;'>
                <h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;'>🎉 Welcome to ATTRAL!</h1>
                <p style='color: #e8f4f8; margin: 10px 0 0 0; font-size: 16px;'>Your affiliate journey starts here</p>
            </div>
            
            <!-- Content -->
            <div style='padding: 40px 30px;'>
                <h2 style='color: #333333; margin: 0 0 20px 0; font-size: 24px;'>Hello {$name}!</h2>
                
                <p style='color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;'>
                    Welcome to the ATTRAL Affiliate Program! We're thrilled to have you join our community of successful partners.
                </p>
                
                <div style='background-color: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='color: #333333; margin: 0 0 15px 0; font-size: 18px;'>Your Affiliate Details:</h3>
                    <ul style='color: #666666; margin: 0; padding-left: 20px;'>
                        <li><strong>Affiliate Code:</strong> <code style='background-color: #e9ecef; padding: 2px 6px; border-radius: 3px;'>{$affiliateCode}</code></li>
                        <li><strong>Commission Rate:</strong> 10% on all sales</li>
                        <li><strong>Payment Terms:</strong> Monthly payouts</li>
                        <li><strong>Minimum Payout:</strong> $50</li>
                    </ul>
                </div>
                
                <div style='background-color: #e8f5e8; border: 1px solid #4caf50; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='color: #2e7d32; margin: 0 0 10px 0; font-size: 18px;'>🚀 Getting Started:</h3>
                    <ol style='color: #2e7d32; margin: 0; padding-left: 20px;'>
                        <li>Share your affiliate link: <strong>attral.in?ref={$affiliateCode}</strong></li>
                        <li>Track your performance in the affiliate dashboard</li>
                        <li>Earn commissions on every sale you refer</li>
                        <li>Get paid monthly via PayPal or bank transfer</li>
                    </ol>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://attral.in/affiliate-dashboard' style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block; font-size: 16px;'>Access Your Dashboard</a>
                </div>
            </div>
            
            <!-- Footer -->
            <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;'>
                <p style='color: #666666; margin: 0; font-size: 14px;'>
                    Questions? Contact us at <a href='mailto:affiliates@attral.in' style='color: #667eea;'>affiliates@attral.in</a>
                </p>
                <p style='color: #999999; margin: 10px 0 0 0; font-size: 12px;'>
                    © 2025 ATTRAL. All rights reserved.
                </p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Get Affiliate Commission Email Template
 */
function getAffiliateCommissionTemplate($data) {
    $name = $data['name'] ?? 'Affiliate';
    $commission = number_format($data['commission'] ?? 0, 2);
    $orderId = $data['orderId'] ?? '';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>New Commission Earned - ATTRAL</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
        <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); padding: 30px; text-align: center;'>
                <h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;'>💰 Commission Earned!</h1>
                <p style='color: #e8f5e8; margin: 10px 0 0 0; font-size: 16px;'>You've earned a new commission</p>
            </div>
            
            <!-- Content -->
            <div style='padding: 40px 30px;'>
                <h2 style='color: #333333; margin: 0 0 20px 0; font-size: 24px;'>Congratulations {$name}!</h2>
                
                <p style='color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;'>
                    Great news! You've earned a commission from a successful referral. Keep up the excellent work!
                </p>
                
                <div style='background-color: #e8f5e8; border: 2px solid #4caf50; padding: 25px; margin: 25px 0; border-radius: 10px; text-align: center;'>
                    <h3 style='color: #2e7d32; margin: 0 0 15px 0; font-size: 20px;'>Commission Details</h3>
                    <div style='font-size: 36px; font-weight: bold; color: #2e7d32; margin: 15px 0;'>$ {$commission}</div>
                    <p style='color: #2e7d32; margin: 10px 0 0 0; font-size: 16px;'>From Order: <strong>{$orderId}</strong></p>
                </div>
                
                <div style='background-color: #f8f9fa; border-left: 4px solid #4caf50; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='color: #333333; margin: 0 0 15px 0; font-size: 18px;'>Commission Summary:</h3>
                    <ul style='color: #666666; margin: 0; padding-left: 20px;'>
                        <li><strong>Commission Amount:</strong> $ {$commission}</li>
                        <li><strong>Order ID:</strong> {$orderId}</li>
                        <li><strong>Status:</strong> Confirmed</li>
                        <li><strong>Next Payout:</strong> End of month</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://attral.in/affiliate-dashboard' style='background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block; font-size: 16px;'>View Dashboard</a>
                </div>
            </div>
            
            <!-- Footer -->
            <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;'>
                <p style='color: #666666; margin: 0; font-size: 14px;'>
                    Questions? Contact us at <a href='mailto:affiliates@attral.in' style='color: #4caf50;'>affiliates@attral.in</a>
                </p>
                <p style='color: #999999; margin: 10px 0 0 0; font-size: 12px;'>
                    © 2025 ATTRAL. All rights reserved.
                </p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Get Affiliate Payout Email Template
 */
function getAffiliatePayoutTemplate($data) {
    $name = $data['name'] ?? 'Affiliate';
    $payoutAmount = number_format($data['payoutAmount'] ?? 0, 2);
    $payoutPeriod = $data['payoutPeriod'] ?? '';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Payout Processed - ATTRAL</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
        <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); padding: 30px; text-align: center;'>
                <h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;'>💳 Payout Processed!</h1>
                <p style='color: #fff3e0; margin: 10px 0 0 0; font-size: 16px;'>Your commission has been sent</p>
            </div>
            
            <!-- Content -->
            <div style='padding: 40px 30px;'>
                <h2 style='color: #333333; margin: 0 0 20px 0; font-size: 24px;'>Hello {$name}!</h2>
                
                <p style='color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;'>
                    Great news! Your commission payout has been successfully processed and sent to your registered payment method.
                </p>
                
                <div style='background-color: #fff3e0; border: 2px solid #ff9800; padding: 25px; margin: 25px 0; border-radius: 10px; text-align: center;'>
                    <h3 style='color: #e65100; margin: 0 0 15px 0; font-size: 20px;'>Payout Details</h3>
                    <div style='font-size: 36px; font-weight: bold; color: #e65100; margin: 15px 0;'>$ {$payoutAmount}</div>
                    <p style='color: #e65100; margin: 10px 0 0 0; font-size: 16px;'>Period: <strong>{$payoutPeriod}</strong></p>
                </div>
                
                <div style='background-color: #f8f9fa; border-left: 4px solid #ff9800; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='color: #333333; margin: 0 0 15px 0; font-size: 18px;'>Payout Summary:</h3>
                    <ul style='color: #666666; margin: 0; padding-left: 20px;'>
                        <li><strong>Amount:</strong> $ {$payoutAmount}</li>
                        <li><strong>Period:</strong> {$payoutPeriod}</li>
                        <li><strong>Status:</strong> Processed</li>
                        <li><strong>Payment Method:</strong> As registered in your account</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://attral.in/affiliate-dashboard' style='background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block; font-size: 16px;'>View Dashboard</a>
                </div>
            </div>
            
            <!-- Footer -->
            <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;'>
                <p style='color: #666666; margin: 0; font-size: 14px;'>
                    Questions? Contact us at <a href='mailto:affiliates@attral.in' style='color: #ff9800;'>affiliates@attral.in</a>
                </p>
                <p style='color: #999999; margin: 10px 0 0 0; font-size: 12px;'>
                    © 2025 ATTRAL. All rights reserved.
                </p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Get Affiliate Milestone Email Template
 */
function getAffiliateMilestoneTemplate($data) {
    $name = $data['name'] ?? 'Affiliate';
    $milestone = $data['milestone'] ?? '';
    $achievement = $data['achievement'] ?? '';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Milestone Achievement - ATTRAL</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;'>
        <div style='max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%); padding: 30px; text-align: center;'>
                <h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: bold;'>🏆 Milestone Achieved!</h1>
                <p style='color: #f3e5f5; margin: 10px 0 0 0; font-size: 16px;'>Congratulations on your achievement</p>
            </div>
            
            <!-- Content -->
            <div style='padding: 40px 30px;'>
                <h2 style='color: #333333; margin: 0 0 20px 0; font-size: 24px;'>Amazing work, {$name}!</h2>
                
                <p style='color: #666666; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;'>
                    We're thrilled to celebrate this incredible milestone with you! Your dedication and hard work have paid off.
                </p>
                
                <div style='background-color: #f3e5f5; border: 2px solid #9c27b0; padding: 25px; margin: 25px 0; border-radius: 10px; text-align: center;'>
                    <h3 style='color: #6a1b9a; margin: 0 0 15px 0; font-size: 20px;'>Milestone Achievement</h3>
                    <div style='font-size: 24px; font-weight: bold; color: #6a1b9a; margin: 15px 0;'>{$milestone}</div>
                    <p style='color: #6a1b9a; margin: 10px 0 0 0; font-size: 16px;'>{$achievement}</p>
                </div>
                
                <div style='background-color: #f8f9fa; border-left: 4px solid #9c27b0; padding: 20px; margin: 20px 0; border-radius: 5px;'>
                    <h3 style='color: #333333; margin: 0 0 15px 0; font-size: 18px;'>What's Next?</h3>
                    <ul style='color: #666666; margin: 0; padding-left: 20px;'>
                        <li>Keep up the excellent performance</li>
                        <li>Explore new marketing strategies</li>
                        <li>Connect with other top affiliates</li>
                        <li>Unlock even higher commission tiers</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='https://attral.in/affiliate-dashboard' style='background: linear-gradient(135deg, #9c27b0 0%, #7b1fa2 100%); color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block; font-size: 16px;'>View Dashboard</a>
                </div>
            </div>
            
            <!-- Footer -->
            <div style='background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;'>
                <p style='color: #666666; margin: 0; font-size: 14px;'>
                    Questions? Contact us at <a href='mailto:affiliates@attral.in' style='color: #9c27b0;'>affiliates@attral.in</a>
                </p>
                <p style='color: #999999; margin: 10px 0 0 0; font-size: 12px;'>
                    © 2025 ATTRAL. All rights reserved.
                </p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Initialize PHPMailer
 */
function initializePHPMailer() {
    // Check if we're in test mode (no actual email sending)
    if (isset($_GET['test_mode']) || isset($_POST['test_mode'])) {
        return new MockMailer();
    }
    
    // Check for test_mode in JSON input (for API calls)
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['test_mode']) && $input['test_mode']) {
        return new MockMailer();
    }
    
    // Check for test_mode in request data
    if (isset($_REQUEST['test_mode']) && $_REQUEST['test_mode']) {
        return new MockMailer();
    }
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
    
    $mail = new PHPMailer(true);
    
    // Load config (same as send_email_real.php)
    $cfg = include __DIR__ . '/config.php';
    
    // SMTP Configuration (exactly same as send_email_real.php)
    $mail->isSMTP();
    $mail->Host = $cfg['SMTP_HOST'] ?? 'smtp-relay.brevo.com';
    $mail->SMTPAuth = true;
    $mail->Username = $cfg['SMTP_USERNAME'] ?? 'info@attral.in';
    $mail->Password = $cfg['SMTP_PASSWORD'] ?? '8f8c1e4b5c6d7e8f9a0b1c2d3e4f5g6h';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = intval($cfg['SMTP_PORT'] ?? 587);
    
    // Sender (same as send_email_real.php)
    $mail->setFrom($cfg['MAIL_FROM'] ?? 'info@attral.in', $cfg['MAIL_FROM_NAME'] ?? 'ATTRAL Affiliate Program');
    $mail->addReplyTo('affiliates@attral.in', 'ATTRAL Affiliate Team');
    
    return $mail;
}

/**
 * Send Affiliate Welcome Email
 */
function sendAffiliateWelcomeEmail($mail, $input) {
    $required = ['email', 'name', 'affiliateCode'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $mail = initializePHPMailer();
    $email = $input['email'];
    $name = $input['name'];
    $affiliateCode = $input['affiliateCode'];
    
    $mail->addAddress($email, $name);
    
    // Content
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    
    $mail->Subject = '🎉 Welcome to ATTRAL Affiliate Program!';
    $mail->Body = getWelcomeEmailTemplate($name, $affiliateCode);
    $mail->AltBody = "Welcome to ATTRAL Affiliate Program!\n\nDear $name,\n\nCongratulations! You're now an official ATTRAL affiliate.\n\nYour Affiliate Code: $affiliateCode\nYour unique link: https://attral.in?ref=$affiliateCode\n\nStart earning 10% commission on every sale you refer!\n\nBest regards,\nATTRAL Affiliate Team";
    
    try {
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'Welcome email sent successfully',
            'action' => 'welcome',
            'timestamp' => date('Y-m-d H:i:s'),
            'recipient' => $email,
            'affiliateCode' => $affiliateCode
        ];
    } catch (Exception $e) {
        throw new Exception('Failed to send welcome email: ' . $e->getMessage());
    }
}

/**
 * Send Affiliate Commission Email
 */
function sendAffiliateCommissionEmail($mail, $input) {
    $required = ['email', 'name', 'commission', 'orderId'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $mail = initializePHPMailer();
    $email = $input['email'];
    $name = $input['name'];
    $commission = $input['commission'];
    $orderId = $input['orderId'];
    
    $mail->addAddress($email, $name);
    
    // Content
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    
    $mail->Subject = "💰 You earned ₹{$commission}!";
    $mail->Body = getCommissionEmailTemplate($name, $commission, $orderId);
    $mail->AltBody = "Commission Earned!\n\nDear $name,\n\nGreat news! You just earned ₹$commission commission from order $orderId.\n\nKeep sharing your affiliate link to earn more!\n\nBest regards,\nATTRAL Affiliate Team";
    
    $mail->send();
    
    return [
        'success' => true,
        'message' => 'Commission email sent successfully',
        'action' => 'commission',
        'timestamp' => date('Y-m-d H:i:s'),
        'recipient' => $email,
        'commission' => $commission,
        'orderId' => $orderId
    ];
}

/**
 * Send Affiliate Payout Email
 */
function sendAffiliatePayoutEmail($mail, $input) {
    $required = ['email', 'name', 'payoutAmount', 'payoutPeriod'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $mail = initializePHPMailer();
    $email = $input['email'];
    $name = $input['name'];
    $payoutAmount = $input['payoutAmount'];
    $payoutPeriod = $input['payoutPeriod'];
    
    $mail->addAddress($email, $name);
    
    // Content
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    
    $mail->Subject = "💳 Your payout of ₹{$payoutAmount} is ready!";
    $mail->Body = getPayoutEmailTemplate($name, $payoutAmount, $payoutPeriod);
    $mail->AltBody = "Payout Ready!\n\nDear $name,\n\nYour payout of ₹$payoutAmount for $payoutPeriod is ready!\n\nThank you for being an amazing affiliate!\n\nBest regards,\nATTRAL Affiliate Team";
    
    $mail->send();
    
    return [
        'success' => true,
        'message' => 'Payout email sent successfully',
        'action' => 'payout',
        'timestamp' => date('Y-m-d H:i:s'),
        'recipient' => $email,
        'payoutAmount' => $payoutAmount,
        'payoutPeriod' => $payoutPeriod
    ];
}

/**
 * Send Affiliate Milestone Email
 */
function sendAffiliateMilestoneEmail($mail, $input) {
    $required = ['email', 'name', 'milestone', 'achievement'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $mail = initializePHPMailer();
    $email = $input['email'];
    $name = $input['name'];
    $milestone = $input['milestone'];
    $achievement = $input['achievement'];
    
    $mail->addAddress($email, $name);
    
    // Content
    $mail->isHTML(true);
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    
    $mail->Subject = "🏆 Milestone Achieved: {$milestone}!";
    $mail->Body = getMilestoneEmailTemplate($name, $milestone, $achievement);
    $mail->AltBody = "Milestone Achieved!\n\nDear $name,\n\nCongratulations! You've achieved: $milestone\n\n$achievement\n\nKeep up the amazing work!\n\nBest regards,\nATTRAL Affiliate Team";
    
    $mail->send();
    
    return [
        'success' => true,
        'message' => 'Milestone email sent successfully',
        'action' => 'milestone',
        'timestamp' => date('Y-m-d H:i:s'),
        'recipient' => $email,
        'milestone' => $milestone
    ];
}

/**
 * Email Template Functions
 */

function getWelcomeEmailTemplate($name, $affiliateCode) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Welcome to ATTRAL Affiliate Program</title>
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8fafc;">
        <div style="background-color: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                <h1 style="color: white; margin: 0; font-size: 28px; font-weight: 700;">🎉 Welcome to ATTRAL!</h1>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0; font-size: 16px;">Your Affiliate Journey Starts Now</p>
            </div>
            
            <!-- Content -->
            <div style="padding: 40px 30px;">
                <h2 style="color: #1f2937; margin: 0 0 20px; font-size: 24px;">Congratulations, ' . htmlspecialchars($name) . '! 🚀</h2>
                
                <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 30px;">
                    You\'re now an official ATTRAL affiliate! Start earning commissions by sharing innovative GaN technology products with your network.
                </p>
                
                <!-- Affiliate Code Card -->
                <div style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 2px solid #0ea5e9; border-radius: 12px; padding: 25px; text-align: center; margin: 30px 0;">
                    <h3 style="color: #0369a1; margin: 0 0 15px; font-size: 18px;">🔑 Your Unique Affiliate Code</h3>
                    <div style="background: white; border-radius: 8px; padding: 15px; margin: 15px 0; border: 1px solid #e5e7eb;">
                        <span style="font-size: 28px; font-weight: 800; color: #667eea; letter-spacing: 2px;">' . htmlspecialchars($affiliateCode) . '</span>
                    </div>
                    <p style="color: #0369a1; margin: 10px 0 0; font-size: 14px;">
                        Your unique link: <strong>attral.in?ref=' . htmlspecialchars($affiliateCode) . '</strong>
                    </p>
                </div>
                
                <!-- Benefits -->
                <div style="margin: 30px 0;">
                    <h3 style="color: #1f2937; margin: 0 0 20px; font-size: 20px;">💰 Why Choose Our Affiliate Program?</h3>
                    <div style="display: grid; gap: 15px;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="background: #10b981; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold;">10%</div>
                            <span style="color: #4b5563; font-size: 15px;"><strong>10% Commission</strong> on every sale you refer</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="background: #3b82f6; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px;">💳</div>
                            <span style="color: #4b5563; font-size: 15px;"><strong>Monthly Payouts</strong> via bank transfer or UPI</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="background: #8b5cf6; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px;">📊</div>
                            <span style="color: #4b5563; font-size: 15px;"><strong>Real-time Dashboard</strong> to track your earnings</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="background: #f59e0b; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px;">🎯</div>
                            <span style="color: #4b5563; font-size: 15px;"><strong>Marketing Materials</strong> provided for your success</span>
                        </div>
                    </div>
                </div>
                
                <!-- CTA Button -->
                <div style="text-align: center; margin: 40px 0;">
                    <a href="https://attral.in/affiliate-dashboard.html" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; padding: 16px 32px; border-radius: 50px; font-weight: 600; font-size: 16px; display: inline-block; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);">Go to Your Dashboard</a>
                </div>
                
                <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 30px 0 0;">
                    Let\'s make great things happen together! If you have any questions, feel free to reach out to our support team.
                </p>
            </div>
            
            <!-- Footer -->
            <div style="background: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                <p style="color: #6b7280; font-size: 14px; margin: 0 0 10px;">
                    <strong>Team ATTRAL</strong> | Smart Power. Smarter Living.
                </p>
                <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                    📧 affiliates@attral.in | 🌐 attral.in
                </p>
            </div>
        </div>
    </body>
    </html>';
}

function getCommissionEmailTemplate($name, $commission, $orderId) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Commission Earned - ATTRAL Affiliate</title>
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8fafc;">
        <div style="background-color: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px 30px; text-align: center;">
                <h1 style="color: white; margin: 0; font-size: 28px; font-weight: 700;">💰 Commission Earned!</h1>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0; font-size: 16px;">Your referral just made a purchase!</p>
            </div>
            
            <!-- Content -->
            <div style="padding: 40px 30px;">
                <h2 style="color: #1f2937; margin: 0 0 20px; font-size: 24px;">Great news, ' . htmlspecialchars($name) . '! 🎉</h2>
                
                <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 30px;">
                    Someone just made a purchase using your affiliate link! You\'ve earned a commission.
                </p>
                
                <!-- Commission Card -->
                <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #10b981; border-radius: 12px; padding: 25px; text-align: center; margin: 30px 0;">
                    <h3 style="color: #047857; margin: 0 0 15px; font-size: 18px;">💵 Commission Details</h3>
                    <div style="background: white; border-radius: 8px; padding: 20px; margin: 15px 0; border: 1px solid #e5e7eb;">
                        <div style="font-size: 36px; font-weight: 800; color: #10b981; margin-bottom: 5px;">₹' . number_format($commission, 2) . '</div>
                        <div style="color: #047857; font-size: 14px; margin-bottom: 15px;">Commission Earned</div>
                        <div style="background: #f3f4f6; padding: 10px; border-radius: 6px; font-size: 14px; color: #374151;">
                            Order ID: <strong>' . htmlspecialchars($orderId) . '</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Next Steps -->
                <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 12px; padding: 20px; margin: 30px 0;">
                    <h4 style="color: #92400e; margin: 0 0 10px; font-size: 16px;">⏰ What happens next?</h4>
                    <ul style="color: #92400e; font-size: 14px; line-height: 1.6; margin: 0; padding-left: 20px;">
                        <li>Your commission will be locked for <strong>30 days</strong> (pending any returns)</li>
                        <li>After the lock period, it will be marked as <strong>approved</strong></li>
                        <li>Monthly payouts processed when you reach <strong>₹1,000</strong> minimum</li>
                    </ul>
                </div>
                
                <!-- Dashboard CTA -->
                <div style="text-align: center; margin: 40px 0;">
                    <a href="https://attral.in/affiliate-dashboard.html" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; text-decoration: none; padding: 16px 32px; border-radius: 50px; font-weight: 600; font-size: 16px; display: inline-block; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);">View Your Dashboard</a>
                </div>
                
                <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 30px 0 0; text-align: center;">
                    💡 <strong>Tip:</strong> Keep sharing to maximize your earnings! The more you share, the more you earn!
                </p>
            </div>
            
            <!-- Footer -->
            <div style="background: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                <p style="color: #6b7280; font-size: 14px; margin: 0 0 10px;">
                    <strong>Team ATTRAL</strong> | Smart Power. Smarter Living.
                </p>
                <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                    📧 affiliates@attral.in | 🌐 attral.in
                </p>
            </div>
        </div>
    </body>
    </html>';
}

function getPayoutEmailTemplate($name, $amount, $payoutDate) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Payout Ready - ATTRAL Affiliate</title>
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8fafc;">
        <div style="background-color: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); padding: 40px 30px; text-align: center;">
                <h1 style="color: white; margin: 0; font-size: 28px; font-weight: 700;">💸 Payout Ready!</h1>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0; font-size: 16px;">Your earnings are ready for transfer</p>
            </div>
            
            <!-- Content -->
            <div style="padding: 40px 30px;">
                <h2 style="color: #1f2937; margin: 0 0 20px; font-size: 24px;">Congratulations, ' . htmlspecialchars($name) . '! 🎊</h2>
                
                <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 30px;">
                    Your affiliate earnings have been processed and are ready for payout!
                </p>
                
                <!-- Payout Card -->
                <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 2px solid #3b82f6; border-radius: 12px; padding: 25px; text-align: center; margin: 30px 0;">
                    <h3 style="color: #1d4ed8; margin: 0 0 15px; font-size: 18px;">💰 Payout Details</h3>
                    <div style="background: white; border-radius: 8px; padding: 20px; margin: 15px 0; border: 1px solid #e5e7eb;">
                        <div style="font-size: 36px; font-weight: 800; color: #3b82f6; margin-bottom: 5px;">₹' . number_format($amount, 2) . '</div>
                        <div style="color: #1d4ed8; font-size: 14px; margin-bottom: 15px;">Total Payout Amount</div>
                        <div style="background: #f3f4f6; padding: 10px; border-radius: 6px; font-size: 14px; color: #374151;">
                            Payout Date: <strong>' . htmlspecialchars($payoutDate) . '</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Payment Info -->
                <div style="background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 12px; padding: 20px; margin: 30px 0;">
                    <h4 style="color: #0c4a6e; margin: 0 0 10px; font-size: 16px;">💳 Payment Information</h4>
                    <ul style="color: #0c4a6e; font-size: 14px; line-height: 1.6; margin: 0; padding-left: 20px;">
                        <li>Payment will be transferred to your registered bank account or UPI ID</li>
                        <li>You should receive the payment within <strong>2-3 business days</strong></li>
                        <li>If you haven\'t updated your payment details, please do so immediately</li>
                    </ul>
                </div>
                
                <!-- Dashboard CTA -->
                <div style="text-align: center; margin: 40px 0;">
                    <a href="https://attral.in/affiliate-dashboard.html" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); color: white; text-decoration: none; padding: 16px 32px; border-radius: 50px; font-weight: 600; font-size: 16px; display: inline-block; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.4);">Update Payment Details</a>
                </div>
                
                <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 30px 0 0; text-align: center;">
                    Thank you for being an amazing affiliate partner! Keep up the great work! 🌟
                </p>
            </div>
            
            <!-- Footer -->
            <div style="background: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                <p style="color: #6b7280; font-size: 14px; margin: 0 0 10px;">
                    <strong>Team ATTRAL</strong> | Smart Power. Smarter Living.
                </p>
                <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                    📧 affiliates@attral.in | 🌐 attral.in
                </p>
            </div>
        </div>
    </body>
    </html>';
}

function getMilestoneEmailTemplate($name, $milestone, $achievement) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Milestone Achieved - ATTRAL Affiliate</title>
    </head>
    <body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8fafc;">
        <div style="background-color: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.1);">
            <!-- Header -->
            <div style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); padding: 40px 30px; text-align: center;">
                <h1 style="color: white; margin: 0; font-size: 28px; font-weight: 700;">🏆 Milestone Achieved!</h1>
                <p style="color: rgba(255,255,255,0.9); margin: 10px 0 0; font-size: 16px;">You\'re doing amazing!</p>
            </div>
            
            <!-- Content -->
            <div style="padding: 40px 30px;">
                <h2 style="color: #1f2937; margin: 0 0 20px; font-size: 24px;">Incredible work, ' . htmlspecialchars($name) . '! 🚀</h2>
                
                <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 30px;">
                    You\'ve reached a significant milestone in your affiliate journey with ATTRAL!
                </p>
                
                <!-- Milestone Card -->
                <div style="background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); border: 2px solid #8b5cf6; border-radius: 12px; padding: 25px; text-align: center; margin: 30px 0;">
                    <h3 style="color: #7c3aed; margin: 0 0 15px; font-size: 18px;">🎯 Achievement Unlocked</h3>
                    <div style="background: white; border-radius: 8px; padding: 20px; margin: 15px 0; border: 1px solid #e5e7eb;">
                        <div style="font-size: 24px; font-weight: 700; color: #8b5cf6; margin-bottom: 10px;">' . htmlspecialchars($milestone) . '</div>
                        <div style="color: #7c3aed; font-size: 14px; line-height: 1.5;">' . htmlspecialchars($achievement) . '</div>
                    </div>
                </div>
                
                <!-- Recognition -->
                <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 12px; padding: 20px; margin: 30px 0; text-align: center;">
                    <h4 style="color: #92400e; margin: 0 0 10px; font-size: 18px;">🌟 You\'re Among Our Top Performers!</h4>
                    <p style="color: #92400e; font-size: 14px; line-height: 1.6; margin: 0;">
                        Your dedication and success don\'t go unnoticed. Thank you for being an outstanding affiliate partner!
                    </p>
                </div>
                
                <!-- Next Goals -->
                <div style="background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 12px; padding: 20px; margin: 30px 0;">
                    <h4 style="color: #0c4a6e; margin: 0 0 10px; font-size: 16px;">🎯 Keep the Momentum Going!</h4>
                    <ul style="color: #0c4a6e; font-size: 14px; line-height: 1.6; margin: 0; padding-left: 20px;">
                        <li>Continue sharing your affiliate link on social media</li>
                        <li>Create engaging content about ATTRAL products</li>
                        <li>Reach out to your network and share product benefits</li>
                        <li>Track your performance in the dashboard</li>
                    </ul>
                </div>
                
                <!-- Dashboard CTA -->
                <div style="text-align: center; margin: 40px 0;">
                    <a href="https://attral.in/affiliate-dashboard.html" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; text-decoration: none; padding: 16px 32px; border-radius: 50px; font-weight: 600; font-size: 16px; display: inline-block; box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);">View Your Progress</a>
                </div>
                
                <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 30px 0 0; text-align: center;">
                    We can\'t wait to see what you achieve next! 🚀
                </p>
            </div>
            
            <!-- Footer -->
            <div style="background: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;">
                <p style="color: #6b7280; font-size: 14px; margin: 0 0 10px;">
                    <strong>Team ATTRAL</strong> | Smart Power. Smarter Living.
                </p>
                <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                    📧 affiliates@attral.in | 🌐 attral.in
                </p>
            </div>
        </div>
    </body>
    </html>';
}
?>