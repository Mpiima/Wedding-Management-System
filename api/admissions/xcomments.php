<?php
require_once __DIR__ . '/_init.php';
$school = adm_school_id();
$body = adm_json();
if ($method !== 'POST') {
    adm_send(['message' => 'Method not allowed'], 405);
}
try {
    $aid = (int) ($body['applicantId'] ?? 0);
    $text = trim((string) ($body['body'] ?? ''));
    if ($aid < 1 || $text === '') {
        adm_send(['error' => 'applicantId and body required'], 400);
    }
    if (!adm_applicant_owned($dbh, $school, $aid)) {
        adm_send(['error' => 'Not found'], 404);
    }
    $dbh->prepare('INSERT INTO applicant_comments (applicant_id, body, created_by) VALUES (?, ?, ?)')->execute([$aid, $text, adm_actor()]);
    adm_send(['message' => 'OK', 'data' => ['id' => (int) $dbh->lastInsertId()]], 201);
} catch (Exception $e) {
    adm_send(['error' => $e->getMessage()], 500);
}
