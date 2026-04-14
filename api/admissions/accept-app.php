<?php
require_once __DIR__ . '/_init.php';
$school = adm_school_id();
$body = adm_json();
if ($method !== 'POST') {
    adm_send(['message' => 'Method not allowed'], 405);
}
try {
    $id = (int) ($body['id'] ?? 0);
    $row = adm_applicant_owned($dbh, $school, $id);
    if (!$row) {
        adm_send(['error' => 'Not found'], 404);
    }
    if ((int) ($row['student_id'] ?? 0) > 0) {
        $dbh->prepare('UPDATE applicants SET pipeline_status = ? WHERE id = ?')->execute(['accepted', $id]);
        adm_log_status($dbh, $id, $row['pipeline_status'], 'accepted', 'Re-confirmed');
        adm_notify($dbh, $school, $id, 'accepted', 'Application accepted.');
        adm_send(['message' => 'OK', 'data' => ['studentId' => (int) $row['student_id']]]);
    }
    $sql = 'INSERT INTO students (school_number, admission_number, first_name, last_name, gender, dob, guardian_name, guardian_phone, guardian_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';
    $st = $dbh->prepare($sql);
    $st->execute([$school, 'TMP', $row['first_name'], $row['last_name'], $row['gender'], $row['dob'], $row['parent_name'], $row['parent_phone'], $row['parent_email']]);
    $sid = (int) $dbh->lastInsertId();
    $dbh->prepare('UPDATE students SET admission_number = CONCAT(\'ADM-\', LPAD(?, 6, \'0\')) WHERE id = ?')->execute([$sid, $sid]);
    $dbh->prepare('UPDATE applicants SET student_id = ?, pipeline_status = ? WHERE id = ?')->execute([$sid, 'accepted', $id]);
    adm_log_status($dbh, $id, $row['pipeline_status'], 'accepted', 'Student record created');
    adm_notify($dbh, $school, $id, 'accepted', 'Your application has been accepted.');
    adm_send(['message' => 'Accepted', 'data' => ['studentId' => $sid]]);
} catch (Exception $e) {
    adm_send(['error' => $e->getMessage()], 500);
}
