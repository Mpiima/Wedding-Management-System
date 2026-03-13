<?php
/**
 * Send a test email using current email configuration. POST: to_email (optional).
 * Requires settings.edit or * permission.
 */
include("connect/header.php");
include_once(__DIR__ . '/lib/EmailHelper.php');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!function_exists('wmis_has_permission') || !wmis_has_permission($dbh, 'settings.edit')) {
    if (!function_exists('wmis_has_permission') || !wmis_has_permission($dbh, '*')) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have permission to send test emails']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$rawInput = file_get_contents('php://input');
$input = $rawInput ? json_decode($rawInput, true) : [];
if (!is_array($input)) {
    $input = [];
}
$toEmail = isset($input['to_email']) ? trim((string) $input['to_email']) : '';

try {
    $config = wmis_get_email_config($dbh);
    if (!$config || trim($config->from_email) === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Email configuration is not set. Save SMTP settings and From address first.']);
        exit;
    }
    if ($toEmail === '') {
        $toEmail = trim($config->from_email);
    }
    if ($toEmail === '' || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'A valid recipient email (to_email) is required.']);
        exit;
    }

    $mail = wmis_create_mailer($dbh);
    if (!$mail) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not create mailer. Check SMTP settings and ensure PHPMailer is installed (composer install).']);
        exit;
    }

    $mail->addAddress($toEmail);
    $mail->isHTML(false);
    $mail->Subject = 'WMIS Test Email';
    $mail->Body = "This is a test email from your Wedding Management Information System (WMIS).\n\nIf you received this, your email configuration is working correctly.\n\nSent at: " . date('Y-m-d H:i:s');

    $mail->send();
    echo json_encode(['message' => 'Test email sent successfully to ' . $toEmail]);
} catch (Exception $e) {
    error_log('Send test email: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send: ' . $e->getMessage()]);
}
