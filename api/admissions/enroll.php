<?php

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/enroll-core.php';

$school = adm_school_id();
$body = adm_json();

if ($method !== 'POST') {
    adm_send(['message' => 'Method not allowed'], 405);
}

try {
    $r = adm_perform_enroll($dbh, $school, $body);
    if ($r['error'] !== null) {
        $code = (isset($r['code']) && $r['code'] === 409) ? 409 : 400;
        $payload = ['error' => $r['error']];
        if (isset($r['capacity'])) {
            $payload['data'] = ['capacity' => $r['capacity']];
        }
        adm_send($payload, $code);
    }

    $paymentRecorded = null;
    $amountPaid = (float) ($body['amountPaid'] ?? 0);
    if ($amountPaid > 0.009) {
        $lib = __DIR__ . '/../finance/invoice-lib.php';
        if (is_readable($lib)) {
            require_once $lib;
            $studentId = (int) ($r['studentId'] ?? 0);
            $yearId = (int) ($body['academicYearId'] ?? 0);
            $periodId = (int) ($body['studyPeriodId'] ?? 0);
            $st = $dbh->prepare(
                'SELECT id FROM invoices WHERE student_id = ? AND academic_year_id = ? AND study_period_id = ? LIMIT 1'
            );
            $st->execute([$studentId, $yearId, $periodId]);
            $invId = (int) $st->fetchColumn();
            if ($invId > 0) {
                $method = trim((string) ($body['paymentMethod'] ?? 'cash'));
                if ($method === '') {
                    $method = 'cash';
                }
                $reference = trim((string) ($body['paymentReference'] ?? ''));
                $paidAt = date('Y-m-d H:i:s');
                $paymentRecorded = fin_record_payment(
                    $dbh,
                    $school,
                    $invId,
                    $amountPaid,
                    $method,
                    $reference !== '' ? $reference : null,
                    $paidAt,
                    'Enrollment'
                );
            } else {
                $paymentRecorded = ['ok' => false, 'message' => 'No invoice found for this period; enrollment saved without payment'];
            }
        }
    }

    adm_send([
        'message' => 'Enrolled',
        'data' => [
            'capacity' => $r['capacity'],
            'studentId' => $r['studentId'] ?? null,
            'payment' => $paymentRecorded
        ]
    ]);
} catch (Exception $e) {
    adm_send(['error' => $e->getMessage()], 500);
}
