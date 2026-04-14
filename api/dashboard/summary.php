<?php

declare(strict_types=1);

/**
 * School admin dashboard: aggregates students, enrollments, finance, admissions, charts, recent activity.
 */
require_once __DIR__ . '/../erp/_init.php';

$school = erp_school_id($dbh);

if ($method !== 'GET') {
    erp_send(['message' => 'Method not allowed'], 405);
}

function dash_fmt_money(float $n): string
{
    return number_format($n, 0, '.', ',');
}

function dash_time_label(?string $dateStr): string
{
    if ($dateStr === null || $dateStr === '') {
        return '—';
    }
    $ts = strtotime($dateStr);
    if ($ts === false) {
        return substr($dateStr, 0, 16);
    }
    $today = date('Y-m-d');
    $d = date('Y-m-d', $ts);
    if ($d === $today) {
        return date('H:i', $ts);
    }
    return date('M j', $ts);
}

try {
    $year = erp_row_school(
        $dbh,
        'SELECT id, name, start_date, end_date FROM academic_years WHERE school_number = ? AND is_active = 1 LIMIT 1',
        [$school]
    );
    $period = null;
    $yearId = 0;
    $periodId = 0;
    if ($year) {
        $yearId = (int) $year['id'];
        $period = erp_row_school(
            $dbh,
            'SELECT id, name, start_date, end_date FROM study_periods
             WHERE school_number = ? AND academic_year_id = ? AND is_active = 1 LIMIT 1',
            [$school, $yearId]
        );
        if ($period) {
            $periodId = (int) $period['id'];
        }
    }

    $studentCount = 0;
    try {
        $st = $dbh->prepare('SELECT COUNT(*) FROM students WHERE school_number = ?');
        $st->execute([$school]);
        $studentCount = (int) $st->fetchColumn();
    } catch (Exception $e) {
        // ignore
    }

    $enrolledCount = 0;
    if ($yearId > 0 && $periodId > 0) {
        try {
            $st = $dbh->prepare(
                'SELECT COUNT(*) FROM enrollments WHERE school_number = ? AND academic_year_id = ? AND study_period_id = ?'
            );
            $st->execute([$school, $yearId, $periodId]);
            $enrolledCount = (int) $st->fetchColumn();
        } catch (Exception $e) {
            // ignore
        }
    }

    $feesExpected = 0.0;
    $feesCollected = 0.0;
    $feesOutstanding = 0.0;
    if ($yearId > 0 && $periodId > 0) {
        try {
            $st = $dbh->prepare(
                'SELECT COALESCE(SUM(total_amount),0) AS expected, COALESCE(SUM(amount_paid),0) AS collected, COALESCE(SUM(balance_due),0) AS outstanding
                 FROM invoices WHERE school_number = ? AND academic_year_id = ? AND study_period_id = ?'
            );
            $st->execute([$school, $yearId, $periodId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $feesExpected = (float) $row['expected'];
                $feesCollected = (float) $row['collected'];
                $feesOutstanding = (float) $row['outstanding'];
            }
        } catch (Exception $e) {
            // invoices table may be missing
        }
    }

    $applicantsPipeline = 0;
    try {
        $st = $dbh->prepare(
            "SELECT COUNT(*) FROM applicants WHERE school_number = ? AND pipeline_status NOT IN ('enrolled','rejected')"
        );
        $st->execute([$school]);
        $applicantsPipeline = (int) $st->fetchColumn();
    } catch (Exception $e) {
        // ignore
    }

    $feesByMonth = ['labels' => [], 'values' => []];
    try {
        $st = $dbh->prepare(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m-01') AS bucket, COALESCE(SUM(amount),0) AS total
             FROM payments
             WHERE school_number = ? AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
             GROUP BY bucket
             ORDER BY bucket ASC"
        );
        $st->execute([$school]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $b = (string) ($r['bucket'] ?? '');
            $ts = strtotime($b);
            $feesByMonth['labels'][] = $ts ? date('M', $ts) : '';
            $feesByMonth['values'][] = round((float) $r['total'], 2);
        }
    } catch (Exception $e) {
        // payments table may be missing
    }

    $enrollmentByLevel = ['labels' => [], 'values' => []];
    if ($yearId > 0 && $periodId > 0) {
        try {
            $st = $dbh->prepare(
                'SELECT l.name AS level_name, COUNT(e.id) AS c
                 FROM enrollments e
                 INNER JOIN classes cl ON cl.id = e.class_id AND cl.school_number = e.school_number
                 INNER JOIN levels l ON l.id = cl.level_id AND l.school_number = cl.school_number
                 WHERE e.school_number = ? AND e.academic_year_id = ? AND e.study_period_id = ?
                 GROUP BY l.id, l.name, l.sort_order
                 ORDER BY l.sort_order ASC, l.name ASC'
            );
            $st->execute([$school, $yearId, $periodId]);
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $enrollmentByLevel['labels'][] = (string) $r['level_name'];
                $enrollmentByLevel['values'][] = (int) $r['c'];
            }
        } catch (Exception $e) {
            // ignore
        }
    }

    $recentActivity = [];
    $paymentRows = [];
    try {
        $st = $dbh->prepare(
            'SELECT p.id, p.amount, p.paid_at, p.receipt_number, p.created_at,
                    CONCAT(s.first_name, " ", s.last_name) AS student_name
             FROM payments p
             INNER JOIN students s ON s.id = p.student_id AND s.school_number = p.school_number
             WHERE p.school_number = ?
             ORDER BY p.created_at DESC, p.id DESC
             LIMIT 12'
        );
        $st->execute([$school]);
        $paymentRows = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // ignore
    }

    foreach ($paymentRows as $p) {
        $sort = (string) ($p['created_at'] ?? $p['paid_at'] ?? '');
        $amt = (float) $p['amount'];
        $recentActivity[] = [
            'sort' => $sort,
            'id' => 'pay-' . (int) $p['id'],
            'time' => dash_time_label($p['paid_at'] ?? null),
            'action' => 'Payment received',
            'detail' => 'UGX ' . dash_fmt_money($amt) . ' · ' . trim((string) $p['student_name']) . ' · ' . (string) $p['receipt_number']
        ];
    }

    try {
        $st = $dbh->prepare(
            'SELECT id, application_number, pipeline_status, first_name, last_name, created_at, updated_at
             FROM applicants WHERE school_number = ?
             ORDER BY COALESCE(updated_at, created_at) DESC, id DESC
             LIMIT 8'
        );
        $st->execute([$school]);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $a) {
            $sort = (string) ($a['updated_at'] ?? $a['created_at'] ?? '');
            $recentActivity[] = [
                'sort' => $sort,
                'id' => 'app-' . (int) $a['id'],
                'time' => dash_time_label($a['updated_at'] ?? $a['created_at'] ?? null),
                'action' => 'Admission · ' . (string) $a['pipeline_status'],
                'detail' => trim((string) $a['first_name'] . ' ' . (string) $a['last_name']) . ' · ' . (string) $a['application_number']
            ];
        }
    } catch (Exception $e) {
        // ignore
    }

    usort($recentActivity, static function ($a, $b) {
        return strcmp((string) ($b['sort'] ?? ''), (string) ($a['sort'] ?? ''));
    });
    foreach ($recentActivity as &$row) {
        unset($row['sort']);
    }
    unset($row);
    $recentActivity = array_slice($recentActivity, 0, 12);

    $periodLabel = '';
    if ($year && $period) {
        $periodLabel = (string) $year['name'] . ' · ' . (string) $period['name'];
    } elseif ($year) {
        $periodLabel = (string) $year['name'] . ' · set active term';
    }

    $meta = [
        'academicYearId' => $yearId > 0 ? $yearId : null,
        'studyPeriodId' => $periodId > 0 ? $periodId : null,
        'academicYearName' => $year ? (string) $year['name'] : '',
        'studyPeriodName' => $period ? (string) $period['name'] : '',
        'periodLabel' => $periodLabel,
        'hasActivePeriod' => $yearId > 0 && $periodId > 0
    ];

    erp_send([
        'data' => [
            'meta' => $meta,
            'summaryCards' => [
                [
                    'id' => 'totalStudents',
                    'label' => 'Total students',
                    'value' => $studentCount,
                    'format' => 'count',
                    'subtext' => 'Records in your school directory',
                    'trend' => ''
                ],
                [
                    'id' => 'enrolled',
                    'label' => 'Enrolled this term',
                    'value' => $enrolledCount,
                    'format' => 'count',
                    'subtext' => $periodLabel !== '' ? $periodLabel : 'Set active year & term in Academic setup',
                    'trend' => ''
                ],
                [
                    'id' => 'feesCollected',
                    'label' => 'Fees collected',
                    'value' => round($feesCollected, 2),
                    'format' => 'currency',
                    'subtext' => $periodLabel !== '' ? 'Current term (invoices)' : 'No active term — finance totals empty',
                    'trend' => ''
                ],
                [
                    'id' => 'outstandingFees',
                    'label' => 'Outstanding fees',
                    'value' => round($feesOutstanding, 2),
                    'format' => 'currency',
                    'subtext' => 'Balance due on invoices',
                    'trend' => ''
                ],
                [
                    'id' => 'admissionsPipeline',
                    'label' => 'Admissions pipeline',
                    'value' => $applicantsPipeline,
                    'format' => 'count',
                    'subtext' => 'Applicants not yet enrolled or rejected',
                    'trend' => ''
                ]
            ],
            'charts' => [
                'feesByMonth' => $feesByMonth,
                'enrollmentByLevel' => $enrollmentByLevel
            ],
            'recentActivity' => $recentActivity
        ]
    ]);
} catch (Exception $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
