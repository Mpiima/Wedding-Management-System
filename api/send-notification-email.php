<?php
/**
 * Send a notification email to all members of a group. POST: group_id, subject, body_html.
 * BCC from email_config is applied. Requires notifications.send or settings.edit permission.
 */
include("connect/header.php");
include_once(__DIR__ . '/lib/EmailHelper.php');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$hasPermission = (function_exists('wmis_has_permission') && wmis_has_permission($dbh, 'notifications.send'))
    || (function_exists('wmis_has_permission') && wmis_has_permission($dbh, 'settings.edit'))
    || (function_exists('wmis_has_permission') && wmis_has_permission($dbh, '*'));
if (!$hasPermission) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have permission to send notification emails']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$scopeUserId = isset($scopeUserId) ? (int) $scopeUserId : (int) $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$groupCategoryId = isset($input['group_id']) ? (int) $input['group_id'] : 0;
$subject = isset($input['subject']) ? trim((string) $input['subject']) : '';
$bodyHtml = isset($input['body_html']) ? (string) $input['body_html'] : '';

if ($groupCategoryId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid group_id is required']);
    exit;
}
if ($subject === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Subject is required']);
    exit;
}

try {
    $stmt = $dbh->prepare("
        SELECT m.id, m.name, m.email
        FROM members m
        INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :user_id
        WHERE m.group_category_id = :group_id
        ORDER BY m.name ASC
    ");
    $stmt->bindValue(':user_id', $scopeUserId, PDO::PARAM_INT);
    $stmt->bindValue(':group_id', $groupCategoryId, PDO::PARAM_INT);
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_OBJ);
    $emails = [];
    foreach ($members as $m) {
        $e = trim($m->email ?? '');
        if ($e !== '' && filter_var($e, FILTER_VALIDATE_EMAIL)) {
            $emails[] = $e;
        }
    }
    if (count($emails) === 0) {
        echo json_encode(['message' => 'No members with valid emails in this group', 'sent' => 0, 'failed' => 0]);
        exit;
    }
    $sent = 0;
    $failed = 0;
    foreach ($emails as $to) {
        if (wmis_send_email($dbh, $to, $subject, $bodyHtml)) {
            $sent++;
        } else {
            $failed++;
        }
    }
    echo json_encode(['message' => 'OK', 'sent' => $sent, 'failed' => $failed]);
} catch (PDOException $e) {
    error_log('send-notification-email: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
