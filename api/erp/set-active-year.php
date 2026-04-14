<?php
require_once __DIR__ . '/_init.php';
$school = erp_school_id($dbh);
$body = erp_json_body();
if ($method !== 'POST') {
    erp_send(['message' => 'Method not allowed'], 405);
}
try {
    $id = (int) ($body['id'] ?? $body['academicYearId'] ?? 0);
    if ($id < 1 || !erp_year_owned($dbh, $school, $id)) {
        erp_send(['error' => 'Academic year not found'], 404);
    }
    erp_deactivate_other_years($dbh, $school, $id);
    $dbh->prepare('UPDATE academic_years SET is_active = 1 WHERE id = ? AND school_number = ?')->execute([$id, $school]);
    erp_send(['message' => 'Active academic year set', 'data' => ['id' => $id]]);
} catch (Exception $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
