<?php
require_once __DIR__ . '/_init.php';
$school = adm_school_id();
if ($method !== 'GET') {
    adm_send(['message' => 'Method not allowed'], 405);
}
try {
    $st = $dbh->prepare('SELECT pipeline_status AS s, COUNT(*) AS c FROM applicants WHERE school_number = ? GROUP BY pipeline_status');
    $st->execute([$school]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    $out = ['application' => 0, 'review' => 0, 'accepted' => 0, 'enrolled' => 0, 'rejected' => 0, 'waitlist' => 0, 'total' => 0];
    foreach ($rows as $r) {
        $k = $r['s'];
        $c = (int) $r['c'];
        if (isset($out[$k])) {
            $out[$k] = $c;
        }
        $out['total'] += $c;
    }

    $out['applicants_pipeline'] = max(0, $out['total'] - $out['enrolled']);

    $out['enrollments_total'] = 0;
    $out['fully_onboarded'] = 0;
    $out['outstanding_invoices'] = 0;
    try {
        $st = $dbh->prepare('SELECT COUNT(*) FROM enrollments WHERE school_number = ?');
        $st->execute([$school]);
        $out['enrollments_total'] = (int) $st->fetchColumn();
    } catch (Exception $e) {
        // table may be missing in partial installs
    }
    try {
        $st = $dbh->prepare(
            'SELECT COUNT(DISTINCT e.student_id) FROM enrollments e
             INNER JOIN students s ON s.id = e.student_id AND s.school_number = e.school_number
             INNER JOIN invoices i ON i.student_id = e.student_id AND i.academic_year_id = e.academic_year_id
                AND i.study_period_id = e.study_period_id
             WHERE e.school_number = ? AND TRIM(s.admission_number) <> \'\' AND i.balance_due < 0.01'
        );
        $st->execute([$school]);
        $out['fully_onboarded'] = (int) $st->fetchColumn();
    } catch (Exception $e) {
    }
    try {
        $st = $dbh->prepare(
            'SELECT COUNT(*) FROM invoices WHERE school_number = ? AND balance_due >= 0.01 AND status IN (\'unpaid\',\'partial\')'
        );
        $st->execute([$school]);
        $out['outstanding_invoices'] = (int) $st->fetchColumn();
    } catch (Exception $e) {
    }

    adm_send(['data' => $out]);
} catch (Exception $e) {
    adm_send(['error' => $e->getMessage()], 500);
}
