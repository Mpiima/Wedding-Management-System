<?php
/**
 * Send a pledge reminder email to the pledger. POST: pledge_id.
 * BCC from email_config is applied. Requires notifications.send or pledges.edit permission.
 */
include("connect/header.php");
include_once(__DIR__ . '/lib/EmailHelper.php');
include_once(__DIR__ . '/lib/EmailTemplates.php');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$hasPermission = (function_exists('wmis_has_permission') && wmis_has_permission($dbh, 'notifications.send'))
    || (function_exists('wmis_has_permission') && wmis_has_permission($dbh, 'pledges.edit'))
    || (function_exists('wmis_has_permission') && wmis_has_permission($dbh, '*'));
if (!$hasPermission) {
    http_response_code(403);
    echo json_encode(['error' => 'You do not have permission to send pledge reminders']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$scopeUserId = isset($scopeUserId) ? (int) $scopeUserId : (int) $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$pledgeId = isset($input['pledge_id']) ? (int) $input['pledge_id'] : 0;

if ($pledgeId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid pledge_id is required']);
    exit;
}

try {
    $stmt = $dbh->prepare("
        SELECT p.id, p.amount_pledged, p.amount_paid, p.created_at, m.name AS member_name, m.email AS member_email
        FROM pledges p
        INNER JOIN members m ON m.id = p.member_id
        INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid
        WHERE p.id = :pid LIMIT 1
    ");
    $stmt->bindValue(':uid', $scopeUserId, PDO::PARAM_INT);
    $stmt->bindValue(':pid', $pledgeId, PDO::PARAM_INT);
    $stmt->execute();
    $pledge = $stmt->fetch(PDO::FETCH_OBJ);
    if (!$pledge) {
        http_response_code(404);
        echo json_encode(['error' => 'Pledge not found']);
        exit;
    }
    $email = trim($pledge->member_email ?? '');
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Pledger has no valid email address']);
        exit;
    }
    $tpl = wmis_email_pledge_reminder(
        $pledge->member_name,
        $pledge->amount_pledged,
        $pledge->amount_paid,
        $pledge->created_at
    );
    $ok = wmis_send_email($dbh, $email, $tpl['subject'], $tpl['body']);
    if ($ok) {
        echo json_encode(['message' => 'Reminder sent']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send email']);
    }
} catch (PDOException $e) {
    error_log('send-pledge-reminder: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
