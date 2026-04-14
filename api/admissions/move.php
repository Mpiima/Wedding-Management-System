<?php

require_once __DIR__ . '/_init.php';

$school = adm_school_id();
$body = adm_json();

if ($method !== 'POST') {
    adm_send(['message' => 'Method not allowed'], 405);
}

$allowed = ['application', 'review', 'accepted', 'enrolled', 'rejected', 'waitlist'];

try {
    $id = (int) ($body['id'] ?? 0);
    $to = trim((string) ($body['pipelineStatus'] ?? $body['pipeline_status'] ?? ''));
    $note = trim((string) ($body['note'] ?? ''));

    if ($id < 1 || !in_array($to, $allowed, true)) {
        adm_send(['error' => 'Invalid applicant or status'], 400);
    }

    $row = adm_applicant_owned($dbh, $school, $id);
    if (!$row) {
        adm_send(['error' => 'Not found'], 404);
    }

    $from = (string) $row['pipeline_status'];
    if ($from === $to) {
        adm_send(['message' => 'No change', 'data' => ['pipeline_status' => $to]]);
    }

    // Enrolled must only be set via enroll.php (creates enrollment row + class placement).
    if ($to === 'enrolled') {
        adm_send(
            [
                'error' => 'Use “Complete enrollment” in the applicant profile to assign class and period. Drag-and-drop cannot move cards to Enrolled.'
            ],
            400
        );
    }

    if ($to === 'accepted' && !(int) ($row['student_id'] ?? 0)) {
        $stIns = $dbh->prepare(
            'INSERT INTO students (school_number, admission_number, first_name, last_name, gender, dob, guardian_name, guardian_phone, guardian_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stIns->execute([
            $school,
            'TMP',
            $row['first_name'],
            $row['last_name'],
            $row['gender'],
            $row['dob'],
            $row['parent_name'],
            $row['parent_phone'],
            $row['parent_email']
        ]);
        $sid = (int) $dbh->lastInsertId();
        $dbh->prepare('UPDATE students SET admission_number = CONCAT(\'ADM-\', LPAD(?, 6, \'0\')) WHERE id = ?')->execute([$sid, $sid]);
        $dbh->prepare('UPDATE applicants SET student_id = ? WHERE id = ?')->execute([$sid, $id]);
        $row['student_id'] = $sid;
    }

    if ($to !== 'waitlist') {
        $dbh->prepare('UPDATE applicants SET pipeline_status = ?, waitlist_position = NULL WHERE id = ? AND school_number = ?')->execute([$to, $id, $school]);
    } else {
        $dbh->prepare('UPDATE applicants SET pipeline_status = ? WHERE id = ? AND school_number = ?')->execute([$to, $id, $school]);
        $mx = $dbh->prepare('SELECT COALESCE(MAX(waitlist_position), 0) + 1 FROM applicants WHERE school_number = ? AND pipeline_status = \'waitlist\'');
        $mx->execute([$school]);
        $pos = (int) $mx->fetchColumn();
        $dbh->prepare('UPDATE applicants SET waitlist_position = ? WHERE id = ?')->execute([$pos, $id]);
    }

    adm_log_status($dbh, $id, $from, $to, $note ?: 'Status changed');
    adm_send(['message' => 'Moved', 'data' => ['pipeline_status' => $to]]);
} catch (Exception $e) {
    adm_send(['error' => $e->getMessage()], 500);
}
