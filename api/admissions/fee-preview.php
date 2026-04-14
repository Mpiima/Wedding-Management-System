<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/../finance/invoice-lib.php';

$school = adm_school_id();

if ($method !== 'GET') {
    adm_send(['message' => 'Method not allowed'], 405);
}

try {
    $classId = (int) ($_GET['classId'] ?? 0);
    $yearId = (int) ($_GET['academicYearId'] ?? 0);
    $periodId = (int) ($_GET['studyPeriodId'] ?? 0);
    if ($classId < 1 || $yearId < 1 || $periodId < 1) {
        adm_send(['error' => 'classId, academicYearId, and studyPeriodId are required'], 400);
    }

    $fs = fin_find_structure_for_class($dbh, $school, $classId, $yearId, $periodId);
    if (!$fs) {
        adm_send([
            'data' => [
                'total' => 0,
                'lines' => [],
                'feeStructureId' => null,
                'message' => 'No fee structure for this class, year, and period'
            ]
        ]);
    }

    $fsId = (int) $fs['id'];
    $st = $dbh->prepare(
        'SELECT label, amount, item_type FROM fee_structure_items WHERE fee_structure_id = ? ORDER BY sort_order ASC, id ASC'
    );
    $st->execute([$fsId]);
    $lines = $st->fetchAll(PDO::FETCH_ASSOC);
    $total = 0.0;
    foreach ($lines as &$ln) {
        $total += (float) $ln['amount'];
    }
    unset($ln);

    adm_send([
        'data' => [
            'total' => round($total, 2),
            'lines' => $lines,
            'feeStructureId' => $fsId,
            'message' => null
        ]
    ]);
} catch (Exception $e) {
    adm_send(['error' => $e->getMessage()], 500);
}
