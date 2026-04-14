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
    $sql = 'SELECT s.id, s.admission_number, s.first_name, s.last_name,
            i.id AS invoice_id, i.invoice_number, i.total_amount, i.amount_paid, i.balance_due, i.status, i.due_date,
            COALESCE(w.balance,0) AS credit_balance
            FROM students s
            LEFT JOIN invoices i ON i.student_id = s.id AND i.academic_year_id = ? AND i.study_period_id = ?
            LEFT JOIN student_credit_wallet w ON w.student_id = s.id AND w.school_number = s.school_number
            WHERE s.school_number = ?';
    $p = [$year, $period, $school];
    if ($classId > 0) {
        $sql .= ' AND EXISTS (SELECT 1 FROM enrollments e WHERE e.student_id = s.id AND e.academic_year_id = ? AND e.study_period_id = ? AND e.class_id = ?)';
        $p[] = $year;
        $p[] = $period;
        $p[] = $classId;
    }
    $sql .= ' ORDER BY s.last_name, s.first_name LIMIT 500';
    $st = $dbh->prepare($sql);
    $st->execute($p);
    fin_send(['data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    fin_send(['error' => $e->getMessage()], 500);
}
