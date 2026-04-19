<?php
// send_bank.php - Email endpoint for bank transfer submissions

// Security Headers
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Vary: Origin');

$allowedOrigins = ['https://www.fikfak.news', 'https://go.fikfak.news'];
$origin = isset($_SERVER['HTTP_ORIGIN']) ? rtrim((string) $_SERVER['HTTP_ORIGIN'], '/') : '';
if (in_array($origin, $allowedOrigins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} else {
    header('Access-Control-Allow-Origin: https://www.fikfak.news');
}

// Ensure HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'HTTPS required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Rate limiting check (simple file-based)
session_start();
$now = time();
$rate_limit_file = sys_get_temp_dir() . '/bank_rate_' . md5($_SERVER['REMOTE_ADDR']);
if (file_exists($rate_limit_file)) {
    $last_submit = (int)file_get_contents($rate_limit_file);
    if ($now - $last_submit < 60) { // 1 minute cooldown
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Te veel verzoeken. Probeer het over een minuut opnieuw.']);
        exit;
    }
}

// Honeypot spam protection
$hp = isset($_POST['hp']) ? trim($_POST['hp']) : '';
if (!empty($hp)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Spam detected']);
    exit;
}

$txid = isset($_POST['txid']) ? trim($_POST['txid']) : '';
$bank = isset($_POST['bank']) ? trim($_POST['bank']) : '';
$holder = isset($_POST['holder']) ? trim($_POST['holder']) : '';
$note = isset($_POST['note']) ? trim($_POST['note']) : '';

if (!$txid || !$bank || !$holder || !$note) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vul alle velden in']);
    exit;
}

// Sanitization
$txid = substr(strip_tags($txid), 0, 200);
$bank = substr(strip_tags($bank), 0, 200);
$holder = substr(strip_tags($holder), 0, 200);
$note = substr(strip_tags($note), 0, 2000);

// Email configuration
$smtp_host = 'smtp.hostinger.com';
$smtp_port = 465;
$smtp_user = 'support@web-technics.services';
$smtp_pass = 'Rh(WcciM6#Zf+Jv';
$from_email = 'support@web-technics.services';
$from_name = 'FikFak News Support';
$to_email = 'info@fikfak.news';
$to_name = 'FikFak News';
$subject = '💳 Nieuwe bankoverschrijving - FikFak News';

// HTML Email
$html_body = '<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    <tr>
                        <td style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%); padding: 30px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px;">💳 Nieuwe Bankoverschrijving</h1>
                            <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">FikFak News</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 24px 0; color: #333333; font-size: 16px;">Er is een nieuwe bankoverschrijving ontvangen:</p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 16px; background-color: #f8f9fa; border-bottom: 1px solid #e9ecef; width: 40%;">
                                        <strong style="color: #495057; font-size: 13px; text-transform: uppercase;">Transactie-ID</strong>
                                    </td>
                                    <td style="padding: 16px; background-color: #ffffff; border-bottom: 1px solid #e9ecef;">
                                        <span style="color: #212529; font-size: 15px; font-weight: 500;">' . htmlspecialchars($txid) . '</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px; background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                                        <strong style="color: #495057; font-size: 13px; text-transform: uppercase;">Bank</strong>
                                    </td>
                                    <td style="padding: 16px; background-color: #ffffff; border-bottom: 1px solid #e9ecef;">
                                        <span style="color: #212529; font-size: 15px; font-weight: 500;">' . htmlspecialchars($bank) . '</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px; background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                                        <strong style="color: #495057; font-size: 13px; text-transform: uppercase;">Rekeninghouder</strong>
                                    </td>
                                    <td style="padding: 16px; background-color: #ffffff; border-bottom: 1px solid #e9ecef;">
                                        <span style="color: #212529; font-size: 15px; font-weight: 500;">' . htmlspecialchars($holder) . '</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px; background-color: #f8f9fa; vertical-align: top;">
                                        <strong style="color: #495057; font-size: 13px; text-transform: uppercase;">Mededeling</strong>
                                    </td>
                                    <td style="padding: 16px; background-color: #ffffff;">
                                        <span style="color: #212529; font-size: 15px; line-height: 1.6;">' . nl2br(htmlspecialchars($note)) . '</span>
                                    </td>
                                </tr>
                            </table>
                            <div style="margin-top: 30px; padding: 16px; background-color: #f8f9fa; border-radius: 6px; border-left: 4px solid #ff6b6b;">
                                <p style="margin: 0 0 8px 0; color: #6c757d; font-size: 13px;">
                                    <strong>📅 Datum:</strong> ' . date('d-m-Y H:i:s') . '
                                </p>
                                <p style="margin: 0; color: #6c757d; font-size: 13px;">
                                    <strong>🌐 IP:</strong> ' . htmlspecialchars($_SERVER['REMOTE_ADDR']) . '
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="margin: 0; color: #6c757d; font-size: 13px;">Automatisch verzonden via FikFak News</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

// Plain text version
$text_body = "Nieuwe bankoverschrijving\n\n";
$text_body .= "Transactie-ID: $txid\n";
$text_body .= "Bank: $bank\n";
$text_body .= "Rekeninghouder: $holder\n";
$text_body .= "Mededeling: $note\n\n";
$text_body .= "Datum: " . date('d-m-Y H:i:s') . "\n";
$text_body .= "IP: " . $_SERVER['REMOTE_ADDR'];

// Send via SMTP
try {
    // Port 465 uses SSL from the start
    $socket = @fsockopen('ssl://' . $smtp_host, $smtp_port, $errno, $errstr, 10);
    if (!$socket) {
        throw new Exception("Connection failed");
    }
    
    fgets($socket, 515);
    fputs($socket, "EHLO " . $_SERVER['SERVER_NAME'] . "\r\n");
    while ($str = fgets($socket, 515)) {
        if (substr($str, 3, 1) == ' ') break;
    }
    
    // No STARTTLS needed for port 465 - already encrypted
    
    fputs($socket, "AUTH LOGIN\r\n");
    fgets($socket, 515);
    fputs($socket, base64_encode($smtp_user) . "\r\n");
    fgets($socket, 515);
    fputs($socket, base64_encode($smtp_pass) . "\r\n");
    $auth = fgets($socket, 515);
    
    if (strpos($auth, '235') === false) {
        throw new Exception("Auth failed");
    }
    
    fputs($socket, "MAIL FROM: <{$from_email}>\r\n");
    fgets($socket, 515);
    fputs($socket, "RCPT TO: <{$to_email}>\r\n");
    fgets($socket, 515);
    fputs($socket, "DATA\r\n");
    fgets($socket, 515);
    
    $boundary = md5(time());
    fputs($socket, "From: {$from_name} <{$from_email}>\r\n");
    fputs($socket, "To: {$to_name} <{$to_email}>\r\n");
    fputs($socket, "Subject: {$subject}\r\n");
    fputs($socket, "MIME-Version: 1.0\r\n");
    fputs($socket, "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n\r\n");
    fputs($socket, "--{$boundary}\r\n");
    fputs($socket, "Content-Type: text/plain; charset=utf-8\r\n\r\n");
    fputs($socket, $text_body . "\r\n\r\n");
    fputs($socket, "--{$boundary}\r\n");
    fputs($socket, "Content-Type: text/html; charset=utf-8\r\n\r\n");
    fputs($socket, $html_body . "\r\n\r\n");
    fputs($socket, "--{$boundary}--\r\n");
    fputs($socket, ".\r\n");
    fgets($socket, 515);
    
    fputs($socket, "QUIT\r\n");
    fclose($socket);
    
    // Update rate limit timestamp on success
    file_put_contents($rate_limit_file, $now);
    
    echo json_encode(['success' => true, 'message' => 'Email verzonden']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Email verzenden mislukt']);
}
?>
