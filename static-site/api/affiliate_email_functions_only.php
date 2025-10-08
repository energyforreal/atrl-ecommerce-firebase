<?php
/**
 * 🎯 Affiliate Email Functions Only
 * Contains just the email functions and templates without HTTP handling
 */

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
    
    $email = $input['email'];
    $name = $input['name'];
    $affiliateCode = $input['affiliateCode'];
    
    $mail->addAddress($email, $name);
    $mail->Subject = '🎉 Welcome to ATTRAL Affiliate Program!';
    $mail->Body = getAffiliateWelcomeTemplate($input);
    $mail->AltBody = "Welcome to ATTRAL Affiliate Program!\n\nDear $name,\n\nCongratulations! You're now an official ATTRAL affiliate.\n\nYour Affiliate Code: $affiliateCode\nYour unique link: https://attral.in?ref=$affiliateCode\n\nStart earning 10% commission on every sale you refer!\n\nBest regards,\nATTRAL Affiliate Team";
    
    $mail->send();
    
    error_log("AFFILIATE WELCOME: Email sent to $email for code $affiliateCode");
    
    return [
        'success' => true,
        'message' => 'Welcome email sent successfully',
        'action' => 'welcome',
        'timestamp' => date('Y-m-d H:i:s'),
        'recipient' => $email,
        'affiliateCode' => $affiliateCode
    ];
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
    
    $email = $input['email'];
    $name = $input['name'];
    $commission = $input['commission'];
    $orderId = $input['orderId'];
    
    $mail->addAddress($email, $name);
    $mail->Subject = "💰 You earned ₹{$commission}!";
    $mail->Body = getAffiliateCommissionTemplate($input);
    $mail->AltBody = "Commission Earned!\n\nDear $name,\n\nGreat news! You just earned ₹$commission commission from order $orderId.\n\nKeep sharing your affiliate link to earn more!\n\nBest regards,\nATTRAL Affiliate Team";
    
    $mail->send();
    
    error_log("AFFILIATE COMMISSION: Email sent to $email for order $orderId - ₹$commission");
    
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
    $required = ['email', 'name', 'payoutAmount'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    $email = $input['email'];
    $name = $input['name'];
    $payoutAmount = $input['payoutAmount'];
    $payoutPeriod = $input['payoutPeriod'] ?? 'Current Period';
    
    $mail->addAddress($email, $name);
    $mail->Subject = "💳 Your Payout of ₹{$payoutAmount} is Ready!";
    $mail->Body = getAffiliatePayoutTemplate($input);
    $mail->AltBody = "Payout Ready!\n\nDear $name,\n\nYour payout of ₹$payoutAmount is ready and will be processed for $payoutPeriod.\n\nThank you for being an amazing affiliate!\n\nBest regards,\nATTRAL Affiliate Team";
    
    $mail->send();
    
    error_log("AFFILIATE PAYOUT: Email sent to $email for payout ₹$payoutAmount");
    
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
    
    $email = $input['email'];
    $name = $input['name'];
    $milestone = $input['milestone'];
    $achievement = $input['achievement'];
    
    $mail->addAddress($email, $name);
    $mail->Subject = "🏆 Milestone Achieved: {$milestone}!";
    $mail->Body = getAffiliateMilestoneTemplate($input);
    $mail->AltBody = "Milestone Achieved!\n\nDear $name,\n\nCongratulations! You've achieved: $milestone\n\n$achievement\n\nKeep up the excellent work!\n\nBest regards,\nATTRAL Affiliate Team";
    
    $mail->send();
    
    error_log("AFFILIATE MILESTONE: Email sent to $email for milestone $milestone");
    
    return [
        'success' => true,
        'message' => 'Milestone email sent successfully',
        'action' => 'milestone',
        'timestamp' => date('Y-m-d H:i:s'),
        'recipient' => $email,
        'milestone' => $milestone,
        'achievement' => $achievement
    ];
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
?>
