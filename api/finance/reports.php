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
    $payMethod = trim((string) ($_GET['paymentMethod'] ?? ''));
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
        "SELECT i.status, COUNT(*) AS cnt, COALESCE(SUM(i.amount_paid),0) AS collected, COALESCE(SUM(i.balance_due),0) AS outstanding
         FROM invoices i $w GROUP BY i.status"
    );
    $st->execute($p);
    $byStatus = $st->fetchAll(PDO::FETCH_ASSOC);

    $pw = 'p.school_number = ?';
    $pp = [$school];
    if ($year > 0) {
        $pw .= ' AND i.academic_year_id = ?';
        $pp[] = $year;
    }
    if ($period > 0) {
        $pw .= ' AND i.study_period_id = ?';
        $pp[] = $period;
    }
    if ($payMethod !== '' && in_array($payMethod, ['cash', 'mobile_money', 'bank', 'card', 'other'], true)) {
        $pw .= ' AND p.method = ?';
        $pp[] = $payMethod;
    }
    $st = $dbh->prepare(
        "SELECT COALESCE(SUM(p.amount),0) AS total_payments, COUNT(*) AS payment_count
         FROM payments p INNER JOIN invoices i ON i.id = p.invoice_id WHERE $pw"
    );
    $st->execute($pp);
    $paySum = $st->fetch(PDO::FETCH_ASSOC);

    fin_send(['data' => ['byStatus' => $byStatus, 'payments' => $paySum]]);
} catch (Exception $e) {
    fin_send(['error' => $e->getMessage()], 500);
}
