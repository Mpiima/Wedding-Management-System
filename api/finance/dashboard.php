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
    $w = 'WHERE i.school_number = ?';
    $p = [$school];
    if ($year > 0) {
        $w .= ' AND i.academic_year_id = ?';
        $p[] = $year;
    }
    if ($period > 0) {
        $w .= ' AND i.study_period_id = ?';
        $p[] = $period;
    }
    $st = $dbh->prepare(
        "SELECT COALESCE(SUM(i.total_amount),0) AS expected, COALESCE(SUM(i.amount_paid),0) AS collected, COALESCE(SUM(i.balance_due),0) AS outstanding
         FROM invoices i $w"
    );
    $st->execute($p);
    $tot = $st->fetch(PDO::FETCH_ASSOC);

    $iw = 'i.school_number = ?';
    $ip = [$school];
    if ($year > 0) {
        $iw .= ' AND i.academic_year_id = ?';
        $ip[] = $year;
    }
    if ($period > 0) {
        $iw .= ' AND i.study_period_id = ?';
        $ip[] = $period;
    }
    $byClass = $dbh->prepare(
        "SELECT c.id, c.name, l.name AS level_name,
                COALESCE(SUM(i.total_amount),0) AS expected,
                COALESCE(SUM(i.amount_paid),0) AS collected,
                COALESCE(SUM(i.balance_due),0) AS outstanding
         FROM classes c
         INNER JOIN levels l ON l.id = c.level_id AND l.school_number = c.school_number
         LEFT JOIN invoices i ON i.class_id = c.id AND ($iw)
         WHERE c.school_number = ?
         GROUP BY c.id, c.name, l.name
         ORDER BY l.sort_order, c.sort_order"
    );
    $byClass->execute(array_merge($ip, [$school]));

    $mw = 'p.school_number = ? AND i.school_number = ?';
    $mp = [$school, $school];
    if ($year > 0) {
        $mw .= ' AND i.academic_year_id = ?';
        $mp[] = $year;
    }
    if ($period > 0) {
        $mw .= ' AND i.study_period_id = ?';
        $mp[] = $period;
    }
    $byMethod = $dbh->prepare(
        "SELECT p.method, COALESCE(SUM(p.amount),0) AS total
         FROM payments p
         INNER JOIN invoices i ON i.id = p.invoice_id
         WHERE $mw
         GROUP BY p.method"
    );
    $byMethod->execute($mp);

    $today = date('Y-m-d');
    $td = $dbh->prepare(
        'SELECT p.*, CONCAT(s.first_name," ",s.last_name) AS student_name, i.invoice_number
         FROM payments p
         INNER JOIN students s ON s.id = p.student_id
         INNER JOIN invoices i ON i.id = p.invoice_id
         WHERE p.school_number = ? AND p.paid_at = ? ORDER BY p.id DESC LIMIT 50'
    );
    $td->execute([$school, $today]);

    fin_send([
        'data' => [
            'totals' => $tot,
            'byClass' => $byClass->fetchAll(PDO::FETCH_ASSOC),
            'byMethod' => $byMethod->fetchAll(PDO::FETCH_ASSOC),
            'todayPayments' => $td->fetchAll(PDO::FETCH_ASSOC)
        ]
    ]);
} catch (Exception $e) {
    fin_send(['error' => $e->getMessage()], 500);
}
