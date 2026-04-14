<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$school = adm_school_id();

if ($method !== 'GET') {
    adm_send(['message' => 'Method not allowed'], 405);
}

/**
 * Derive operational enrollment status from invoice + admission number.
 */
function adm_enrollment_ops_status(array $row): string
{
    $adm = trim((string) ($row['admission_number'] ?? ''));
    if ($adm === '') {
        return 'pending';
    }
    $invId = (int) ($row['invoice_id'] ?? 0);
    if ($invId < 1) {
        return 'pending';
    }
    $bal = (float) ($row['balance_due'] ?? 0);
    $paid = (float) ($row['amount_paid'] ?? 0);
    if ($bal < 0.01) {
        return 'active';
    }
    if ($paid > 0.009) {
        return 'partial';
    }

    return 'pending';
}

try {
    $yearId = (int) ($_GET['academicYearId'] ?? 0);
    $periodId = (int) ($_GET['studyPeriodId'] ?? 0);
    $classId = (int) ($_GET['classId'] ?? 0);
    $feeStatus = isset($_GET['feeStatus']) ? trim((string) $_GET['feeStatus']) : '';
    $opsFilter = isset($_GET['enrollmentStatus']) ? trim((string) $_GET['enrollmentStatus']) : '';
    $onboardedOnly = isset($_GET['onboardedOnly']) ? (int) $_GET['onboardedOnly'] : 0;
    $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

    $sql = 'SELECT e.id AS enrollment_id, e.student_id, e.academic_year_id, e.study_period_id, e.class_id, e.stream_id,
            e.enrolled_at,
            s.admission_number, s.first_name, s.last_name, s.gender, s.guardian_phone,
            cl.name AS class_name,
            st.name AS stream_name,
            ay.name AS academic_year_name,
            sp.name AS study_period_name,
            i.id AS invoice_id, i.invoice_number, i.status AS invoice_status,
            i.total_amount, i.amount_paid, i.balance_due
            FROM enrollments e
            INNER JOIN students s ON s.id = e.student_id AND s.school_number = e.school_number
            LEFT JOIN classes cl ON cl.id = e.class_id AND cl.school_number = e.school_number
            LEFT JOIN streams st ON st.id = e.stream_id AND st.school_number = e.school_number
            INNER JOIN academic_years ay ON ay.id = e.academic_year_id AND ay.school_number = e.school_number
            INNER JOIN study_periods sp ON sp.id = e.study_period_id AND sp.school_number = e.school_number
            LEFT JOIN invoices i ON i.student_id = e.student_id AND i.academic_year_id = e.academic_year_id
                AND i.study_period_id = e.study_period_id AND i.school_number = e.school_number
            WHERE e.school_number = ?';
    $params = [$school];

    if ($yearId > 0) {
        $sql .= ' AND e.academic_year_id = ?';
        $params[] = $yearId;
    }
    if ($periodId > 0) {
        $sql .= ' AND e.study_period_id = ?';
        $params[] = $periodId;
    }
    if ($classId > 0) {
        $sql .= ' AND e.class_id = ?';
        $params[] = $classId;
    }
    if ($q !== '') {
        $like = '%' . $q . '%';
        $sql .= ' AND (CONCAT(s.first_name, \' \', s.last_name) LIKE ? OR s.admission_number LIKE ? OR s.guardian_phone LIKE ?)';
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
    }

    $sql .= ' ORDER BY e.enrolled_at DESC, s.last_name ASC, s.first_name ASC';

    $st = $dbh->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $out = [];
    foreach ($rows as $r) {
        $ops = adm_enrollment_ops_status($r);
        $feeLabel = 'Unpaid';
        $invSt = (string) ($r['invoice_status'] ?? '');
        if ($invSt === 'paid') {
            $feeLabel = 'Paid';
        } elseif ($invSt === 'partial') {
            $feeLabel = 'Partial';
        }

        if ($feeStatus !== '' && $feeStatus !== 'all') {
            $map = ['paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid'];
            if (($map[$feeStatus] ?? '') !== $feeLabel) {
                continue;
            }
        }
        if ($opsFilter !== '' && $opsFilter !== 'all' && $ops !== $opsFilter) {
            continue;
        }
        if ($onboardedOnly === 1) {
            $admOk = trim((string) ($r['admission_number'] ?? '')) !== '';
            $balOk = (float) ($r['balance_due'] ?? 0) < 0.01;
            if (!$admOk || !$balOk) {
                continue;
            }
        }

        $r['fee_status_label'] = $feeLabel;
        $r['enrollment_ops_status'] = $ops;
        $r['student_name'] = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        $out[] = $r;
    }

    adm_send(['data' => $out]);
} catch (Exception $e) {
    adm_send(['error' => $e->getMessage()], 500);
}
