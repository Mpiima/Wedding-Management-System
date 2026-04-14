<?php
require_once __DIR__ . '/_init.php';
$school = erp_school_id($dbh);
$body = erp_json_body();
if ($method !== 'POST') {
    erp_send(['message' => 'Method not allowed'], 405);
}
try {
    $fromId = (int) ($body['fromYearId'] ?? 0);
    if ($fromId < 1 || !erp_year_owned($dbh, $school, $fromId)) {
        erp_send(['error' => 'Source not found'], 404);
    }
    $name = trim((string) ($body['name'] ?? ''));
    $start = trim((string) ($body['startDate'] ?? ''));
    $end = trim((string) ($body['endDate'] ?? ''));
    if ($name === '' || $start === '' || $end === '' || $start >= $end) {
        erp_send(['error' => 'Invalid input'], 400);
    }
    $dbh->prepare(
        'INSERT INTO academic_years (school_number, name, start_date, end_date, is_active) VALUES (?, ?, ?, ?, 0)'
    )->execute([$school, $name, $start, $end]);
    $newId = (int) $dbh->lastInsertId();
    $dbh->prepare(
        'INSERT INTO study_periods (school_number, academic_year_id, name, start_date, end_date, is_active, sort_order)
         SELECT school_number, ?, name, NULL, NULL, 0, sort_order FROM study_periods WHERE academic_year_id = ? AND school_number = ?'
    )->execute([$newId, $fromId, $school]);
    erp_send(['message' => 'Cloned', 'data' => ['id' => $newId]], 201);
} catch (Exception $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
