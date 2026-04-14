<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);

if ($method !== 'GET') {
    fin_send(['message' => 'Method not allowed'], 405);
}

try {
    $year = (int) ($_GET['academicYearId'] ?? 0);
    $period = (int) ($_GET['studyPeriodId'] ?? 0);
    $classId = (int) ($_GET['classId'] ?? 0);
    if ($year < 1 || $period < 1) {
        fin_send(['error' => 'academicYearId and studyPeriodId required'], 400);
    }
    $sql = 'SELECT s.id, s.admission_number, s.first_name, s.last_name, e.class_id
FROM enrollments e
INNER JOIN students s ON s.id = e.student_id AND s.school_number = e.school_number
WHERE e.school_number = ? AND e.academic_year_id = ? AND e.study_period_id = ?';
    $p = [$school, $year, $period];
    if ($classId > 0) {
        $sql .= ' AND e.class_id = ?';
        $p[] = $classId;
    }
    $sql .= ' ORDER BY s.last_name, s.first_name';
    $st = $dbh->prepare($sql);
    $st->execute($p);
    fin_send(['data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    fin_send(['error' => $e->getMessage()], 500);
}
