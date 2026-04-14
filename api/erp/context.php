<?php
require_once __DIR__ . '/_init.php';
$school = erp_school_id($dbh);
if ($method !== 'GET') {
    erp_send(['message' => 'Method not allowed'], 405);
}
try {
    $year = erp_row_school(
        $dbh,
        'SELECT id, name, start_date, end_date FROM academic_years WHERE school_number = ? AND is_active = 1 LIMIT 1',
        [$school]
    );
    $period = null;
    if ($year) {
        $period = erp_row_school(
            $dbh,
            'SELECT id, name, start_date, end_date FROM study_periods
             WHERE school_number = ? AND academic_year_id = ? AND is_active = 1 LIMIT 1',
            [$school, (int) $year['id']]
        );
    }
    erp_send(['data' => ['academicYear' => $year, 'studyPeriod' => $period]]);
} catch (Exception $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
