<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once __DIR__ . '/config.php';

// Composer autoload if present (PHPMailer installed via composer in project root)
// Prefer Composer autoload; fall back to vendored PHPMailer src files
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

function respond($data, $code = 200) {
  http_response_code($code);
  echo json_encode($data);
  exit;
}

// Simple admin token verification using existing admin_auth.php logic (database-backed)
function verify_admin_token($token) {
  $db = new SQLite3(__DIR__ . '/admin.db');
  $stmt = $db->prepare("SELECT as.token FROM admin_sessions as JOIN admin_users au ON au.id = as.admin_id WHERE as.token = ? AND as.expires_at > CURRENT_TIMESTAMP AND au.is_active = 1");
  $stmt->bindValue(1, $token);
  $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
  return !!$row;
}

$cfg = @include __DIR__ . '/config.php';

$raw = file_get_contents('php://input');
$req = json_decode($raw, true);
if (!$req) { respond(['success' => false, 'error' => 'Invalid JSON'], 400); }

$token = $_SERVER['HTTP_AUTHORIZATION'] ?? ($req['token'] ?? '');
if (!$token) { respond(['success' => false, 'error' => 'Missing admin token'], 401); }

if (!verify_admin_token($token)) {
  respond(['success' => false, 'error' => 'Unauthorized'], 401);
}

$to = $req['to'] ?? '';
$subject = $req['subject'] ?? '';
$html = $req['html'] ?? '';
$text = $req['text'] ?? '';
$replyTo = $req['reply_to'] ?? '';

if (!$to || !$subject || (!$html && !$text)) {
  respond(['success' => false, 'error' => 'Fields required: to, subject, and html or text'], 400);
}

try {
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host = $cfg['SMTP_HOST'] ?? 'smtp.hostinger.com';
  $mail->Port = intval($cfg['SMTP_PORT'] ?? 465);
  $secure = strtolower($cfg['SMTP_SECURE'] ?? 'ssl');
  $mail->SMTPSecure = $secure === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
  $mail->SMTPAuth = true;
  $mail->Username = $cfg['SMTP_USERNAME'] ?? '';
  $mail->Password = $cfg['SMTP_PASSWORD'] ?? '';

  $fromEmail = $cfg['MAIL_FROM'] ?? $mail->Username;
  $fromName = $cfg['MAIL_FROM_NAME'] ?? 'ATTRAL';

  $mail->setFrom($fromEmail, $fromName);
  $mail->addAddress($to);
  if (!empty($replyTo)) {
    $mail->addReplyTo($replyTo);
  }

  $mail->isHTML(!empty($html));
  $mail->Subject = $subject;
  if (!empty($html)) { $mail->Body = $html; }
  if (!empty($text)) { $mail->AltBody = $text; }

  $mail->send();

  respond(['success' => true, 'message' => 'Email sent']);
} catch (Exception $e) {
  error_log('MAIL ERROR: ' . $e->getMessage());
  respond(['success' => false, 'error' => 'Failed to send email'], 500);
}

?>


