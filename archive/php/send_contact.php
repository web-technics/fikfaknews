<?php
// send_contact.php - Email endpoint for contact form submissions
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Honeypot spam protection
$hp = isset($_POST['hp']) ? trim($_POST['hp']) : '';
if (!empty($hp)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Spam detected']);
    exit;
}

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$name || !$email || !$subject || !$message) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vul alle velden in']);
    exit;
}

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ongeldig e-mailadres']);
    exit;
}

// Sanitization
$name = substr(strip_tags($name), 0, 200);
$email = substr(strip_tags($email), 0, 200);
$subject = substr(strip_tags($subject), 0, 200);
$message = substr(strip_tags($message), 0, 5000);

// Email configuration
$smtp_host = 'smtp.hostinger.com';
$smtp_port = 465;
$smtp_user = 'support@web-technics.services';
$smtp_pass = 'Rh(WcciM6#Zf+Jv';
$from_email = 'support@web-technics.services';
$from_name = 'FikFak News Contact Form';
$to_email = 'info@fikfak.news';
$to_name = 'FikFak News';
$email_subject = '📧 Nieuw contactbericht - FikFak News';

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
                            <h1 style="margin: 0; color: #ffffff; font-size: 24px;">📧 Nieuw Contactbericht</h1>
                            <p style="margin: 8px 0 0 0; color: rgba(255,255,255,0.9); font-size: 14px;">FikFak News</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 40px;">
                            <p style="margin: 0 0 24px 0; color: #333333; font-size: 16px;">Je hebt een nieuw contactbericht ontvangen:</p>
                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
                                <tr>
                                    <td style="padding: 16px; background-color: #f8f9fa; border-bottom: 1px solid #e9ecef; width: 35%;">
                                        <strong style="color: #495057; font-size: 13px; text-transform: uppercase;">Van</strong>
                                    </td>
                                    <td style="padding: 16px; background-color: #ffffff; border-bottom: 1px solid #e9ecef;">
                                        <span style="color: #212529; font-size: 15px; font-weight: 500;">' . htmlspecialchars($name) . '</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px; background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                                        <strong style="color: #495057; font-size: 13px; text-transform: uppercase;">E-mail</strong>
                                    </td>
                                    <td style="padding: 16px; background-color: #ffffff; border-bottom: 1px solid #e9ecef;">
                                        <a href="mailto:' . htmlspecialchars($email) . '" style="color: #ff6b6b; text-decoration: none; font-size: 15px; font-weight: 500;">' . htmlspecialchars($email) . '</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px; background-color: #f8f9fa; border-bottom: 1px solid #e9ecef;">
                                        <strong style="color: #495057; font-size: 13px; text-transform: uppercase;">Onderwerp</strong>
                                    </td>
                                    <td style="padding: 16px; background-color: #ffffff; border-bottom: 1px solid #e9ecef;">
                                        <span style="color: #212529; font-size: 15px; font-weight: 500;">' . htmlspecialchars($subject) . '</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 16px; background-color: #f8f9fa; vertical-align: top;">
                                        <strong style="color: #495057; font-size: 13px; text-transform: uppercase;">Bericht</strong>
                                    </td>
                                    <td style="padding: 16px; background-color: #ffffff;">
                                        <span style="color: #212529; font-size: 15px; line-height: 1.6;">' . nl2br(htmlspecialchars($message)) . '</span>
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
                            <div style="margin-top: 20px; padding: 16px; background-color: #e7f5ff; border-radius: 6px; text-align: center;">
                                <p style="margin: 0; color: #0077cc; font-size: 14px;">
                                    💡 <strong>Tip:</strong> Reageer rechtstreeks op <a href="mailto:' . htmlspecialchars($email) . '" style="color: #ff6b6b; text-decoration: none;">' . htmlspecialchars($email) . '</a>
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #e9ecef;">
                            <p style="margin: 0; color: #6c757d; font-size: 13px;">Automatisch verzonden via FikFak News Contact Formulier</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

// Plain text version
$text_body = "Nieuw contactbericht\n\n";
$text_body .= "Van: $name\n";
$text_body .= "E-mail: $email\n";
$text_body .= "Onderwerp: $subject\n\n";
$text_body .= "Bericht:\n$message\n\n";
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
    fputs($socket, "Reply-To: {$name} <{$email}>\r\n");
    fputs($socket, "Subject: {$email_subject}\r\n");
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
    
    echo json_encode(['success' => true, 'message' => 'Email verzonden']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Email verzenden mislukt']);
}
?>
