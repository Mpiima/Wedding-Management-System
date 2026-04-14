<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/invoice-lib.php';

$school = erp_school_id($dbh);
$body = fin_json();

try {
    if ($method !== 'POST') {
        fin_send(['message' => 'Method not allowed'], 405);
    }
    $action = trim((string) ($body['action'] ?? ''));
    if ($action === 'generate_class') {
        $classId = (int) ($body['classId'] ?? 0);
        $year = (int) ($body['academicYearId'] ?? 0);
        $period = (int) ($body['studyPeriodId'] ?? 0);
        if ($classId < 1 || $year < 1 || $period < 1) {
            fin_send(['error' => 'classId, academicYearId, studyPeriodId required'], 400);
        }
        if (!erp_class_owned($dbh, $school, $classId)) {
            fin_send(['error' => 'Invalid class'], 400);
        }
        $st = $dbh->prepare(
            'SELECT DISTINCT e.student_id FROM enrollments e
             WHERE e.school_number = ? AND e.class_id = ? AND e.academic_year_id = ? AND e.study_period_id = ?'
        );
        $st->execute([$school, $classId, $year, $period]);
        $ids = $st->fetchAll(PDO::FETCH_COLUMN);
        $created = 0;
        $skipped = 0;
        foreach ($ids as $sid) {
            $r = fin_generate_invoice($dbh, $school, (int) $sid, $year, $period, $classId, fin_actor(), ['silent' => true]);
            if (!empty($r['ok'])) {
                $created++;
            } else {
                $skipped++;
            }
        }
        fin_send(['message' => 'Bulk generation complete', 'data' => ['created' => $created, 'skipped' => $skipped]]);
    } elseif ($action === 'remind_unpaid') {
        $year = (int) ($body['academicYearId'] ?? 0);
        $period = (int) ($body['studyPeriodId'] ?? 0);
        if ($year < 1 || $period < 1) {
            fin_send(['error' => 'academicYearId, studyPeriodId required'], 400);
        }
        $st = $dbh->prepare(
            'SELECT i.id, i.student_id, i.balance_due, CONCAT(s.first_name," ",s.last_name) AS student_name
             FROM invoices i INNER JOIN students s ON s.id = i.student_id
             WHERE i.school_number = ? AND i.academic_year_id = ? AND i.study_period_id = ? AND i.status IN (\'unpaid\',\'partial\')'
        );
        $st->execute([$school, $year, $period]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        $n = 0;
        $ins = $dbh->prepare(
            'INSERT INTO finance_notifications (school_number, student_id, channel, event_key, message) VALUES (?,?,?,?,?)'
        );
        foreach ($rows as $r) {
            $msg = 'Reminder: ' . $r['student_name'] . ' — balance due ' . number_format((float) $r['balance_due'], 2) . '.';
            $ins->execute([$school, (int) $r['student_id'], 'in_app', 'balance_reminder', $msg]);
            $n++;
        }
        fin_send(['message' => 'Reminders queued', 'data' => ['count' => $n]]);
    } else {
        fin_send(['error' => 'Unknown action'], 400);
    }
} catch (Exception $e) {
    fin_send(['error' => $e->getMessage()], 500);
}
