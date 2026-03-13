<?php
/**
 * Email configuration (SMTP). GET = current config (password masked). PUT = save.
 * Requires settings.edit or * permission.
 */
include("connect/header.php");
include_once(__DIR__ . '/lib/EmailHelper.php');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
if (!function_exists('wmis_has_permission') || !wmis_has_permission($dbh, 'settings.edit')) {
    if (!function_exists('wmis_has_permission') || !wmis_has_permission($dbh, '*')) {
        http_response_code(403);
        echo json_encode(['error' => 'You do not have permission to manage email configuration']);
        exit;
    }
}
switch ($method) {
    case 'GET': getConfig($dbh); break;
    case 'PUT': putConfig($dbh, $input); break;
    default: http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}

function getConfig(PDO $dbh) {
    try {
        $config = wmis_get_email_config($dbh);
        if (!$config) {
            echo json_encode(['message' => 'OK', 'data' => (object) ['id' => null, 'smtp_host' => '', 'smtp_port' => 587, 'smtp_username' => '', 'smtp_password' => '', 'smtp_password_set' => false, 'encryption' => 'tls', 'from_email' => '', 'from_name' => '', 'bcc_emails' => '']]);
            return;
        }
        $out = (object) [
            'id' => (int) $config->id,
            'smtp_host' => $config->smtp_host ?? '',
            'smtp_port' => (int) $config->smtp_port,
            'smtp_username' => $config->smtp_username ?? '',
            'smtp_password' => '',
            'smtp_password_set' => !empty(trim($config->smtp_password ?? '')),
            'encryption' => in_array($config->encryption ?? '', ['none', 'tls', 'ssl'], true) ? $config->encryption : 'tls',
            'from_email' => $config->from_email ?? '',
            'from_name' => $config->from_name ?? '',
            'bcc_emails' => isset($config->bcc_emails) ? trim((string) $config->bcc_emails) : ''
        ];
        echo json_encode(['message' => 'OK', 'data' => $out]);
    } catch (PDOException $e) {
        error_log('Email config GET: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function putConfig(PDO $dbh, array $input) {
    $smtpHost = isset($input['smtp_host']) ? trim((string) $input['smtp_host']) : '';
    $smtpPort = isset($input['smtp_port']) ? (int) $input['smtp_port'] : 587;
    $smtpUsername = array_key_exists('smtp_username', $input) ? trim((string) $input['smtp_username']) : null;
    $smtpPassword = array_key_exists('smtp_password', $input) ? trim((string) $input['smtp_password']) : null;
    $encryption = isset($input['encryption']) ? trim((string) $input['encryption']) : 'tls';
    $fromEmail = isset($input['from_email']) ? trim((string) $input['from_email']) : '';
    $fromName = array_key_exists('from_name', $input) ? trim((string) $input['from_name']) : null;
    $bccEmails = array_key_exists('bcc_emails', $input) ? trim((string) $input['bcc_emails']) : null;
    if (!in_array($encryption, ['none', 'tls', 'ssl'], true)) $encryption = 'tls';
    if ($smtpPort <= 0 || $smtpPort > 65535) $smtpPort = 587;
    try {
        $existing = wmis_get_email_config($dbh);
        if ($existing) {
            $sql = "UPDATE email_config SET smtp_host = :smtp_host, smtp_port = :smtp_port, smtp_username = :smtp_username, encryption = :encryption, from_email = :from_email, from_name = :from_name, bcc_emails = :bcc_emails";
            $params = [':smtp_host' => $smtpHost, ':smtp_port' => $smtpPort, ':smtp_username' => $smtpUsername !== '' ? $smtpUsername : null, ':encryption' => $encryption, ':from_email' => $fromEmail, ':from_name' => $fromName !== '' ? $fromName : null, ':bcc_emails' => $bccEmails !== '' ? $bccEmails : null];
            if ($smtpPassword !== null && $smtpPassword !== '') { $sql .= ", smtp_password = :smtp_password"; $params[':smtp_password'] = $smtpPassword; }
            $sql .= " WHERE id = :id"; $params[':id'] = $existing->id;
            $stmt = $dbh->prepare($sql);
            foreach ($params as $k => $v) { $stmt->bindValue($k, $v, $v === null ? PDO::PARAM_NULL : (is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR)); }
            $stmt->execute();
        } else {
            $stmt = $dbh->prepare("INSERT INTO email_config (smtp_host, smtp_port, smtp_username, smtp_password, encryption, from_email, from_name, bcc_emails) VALUES (:smtp_host, :smtp_port, :smtp_username, :smtp_password, :encryption, :from_email, :from_name, :bcc_emails)");
            $stmt->execute([':smtp_host' => $smtpHost, ':smtp_port' => $smtpPort, ':smtp_username' => $smtpUsername !== '' ? $smtpUsername : null, ':smtp_password' => $smtpPassword !== '' ? $smtpPassword : null, ':encryption' => $encryption, ':from_email' => $fromEmail, ':from_name' => $fromName !== '' ? $fromName : null, ':bcc_emails' => $bccEmails !== '' ? $bccEmails : null]);
        }
        getConfig($dbh);
    } catch (PDOException $e) {
        error_log('Email config PUT: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}
