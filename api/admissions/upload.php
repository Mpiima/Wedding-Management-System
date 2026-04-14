<?php
require_once __DIR__ . '/_init.php';
$school = adm_school_id();
if ($method !== 'POST') {
    adm_send(['message' => 'Method not allowed'], 405);
}
try {
    $aid = (int) ($_POST['applicantId'] ?? 0);
    $docType = trim((string) ($_POST['docType'] ?? 'other'));
    if ($aid < 1 || !adm_applicant_owned($dbh, $school, $aid)) {
        adm_send(['error' => 'Invalid applicant'], 400);
    }
    if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        adm_send(['error' => 'File upload failed'], 400);
    }
    $base = dirname(__DIR__, 2) . '/uploads/admissions/' . preg_replace('/[^a-zA-Z0-9_-]/', '', $school) . '/' . $aid;
    if (!is_dir($base)) {
        mkdir($base, 0755, true);
    }
    $orig = basename((string) $_FILES['file']['name']);
    $ext = pathinfo($orig, PATHINFO_EXTENSION);
    $safe = uniqid('doc_', true) . ($ext ? '.' . preg_replace('/[^a-zA-Z0-9]/', '', $ext) : '');
    $dest = $base . '/' . $safe;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        adm_send(['error' => 'Could not save file'], 500);
    }
    $rel = 'uploads/admissions/' . $school . '/' . $aid . '/' . $safe;
    $dbh->prepare('INSERT INTO applicant_documents (applicant_id, doc_type, file_path, original_name) VALUES (?, ?, ?, ?)')->execute([$aid, $docType, $rel, $orig]);
    adm_send(['message' => 'Uploaded', 'data' => ['id' => (int) $dbh->lastInsertId(), 'path' => $rel]]);
} catch (Exception $e) {
    adm_send(['error' => $e->getMessage()], 500);
}
