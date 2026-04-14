<?php
require_once __DIR__ . '/_init.php';
$school = adm_school_id();
$body = adm_json();
if ($method !== 'POST') {
    adm_send(['message' => 'Method not allowed'], 405);
}
try {
    $id = (int) ($body['id'] ?? 0);
    $reason = trim((string) ($body['reason'] ?? ''));
    $notify = !empty($body['notifyParent']);
    if ($id < 1 || $reason === '') {
        adm_send(['error' => 'id and reason required'], 400);
    }
    $row = adm_applicant_owned($dbh, $school, $id);
    if (!$row) {
        adm_send(['error' => 'Not found'], 404);
    }
    $from = (string) $row['pipeline_status'];
    $dbh->prepare('UPDATE applicants SET pipeline_status = ?, rejection_reason = ?, notify_parent_on_reject = ? WHERE id = ? AND school_number = ?')->execute(['rejected', $reason, $notify ? 1 : 0, $id, $school]);
    adm_log_status($dbh, $id, $from, 'rejected', $reason);
    adm_notify($dbh, $school, $id, 'rejected', 'Application not successful.');
    adm_send(['message' => 'Rejected']);
} catch (Exception $e) {
    adm_send(['error' => $e->getMessage()], 500);
}
