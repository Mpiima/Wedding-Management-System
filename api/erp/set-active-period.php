<?php

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);
$body = erp_json_body();

if ($method !== 'POST') {
    erp_send(['message' => 'Method not allowed'], 405);
}

try {
    $id = (int) ($body['id'] ?? $body['studyPeriodId'] ?? 0);
    $row = erp_period_owned($dbh, $school, $id);
    if (!$row) {
        erp_send(['error' => 'Study period not found'], 404);
    }

    $yearId = (int) $row['academic_year_id'];
    erp_deactivate_other_periods($dbh, $school, $yearId, $id);
    $dbh->prepare('UPDATE study_periods SET is_active = 1 WHERE id = ? AND school_number = ?')->execute([$id, $school]);

    erp_send(['message' => 'Active study period set', 'data' => ['id' => $id]]);
} catch (Throwable $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
