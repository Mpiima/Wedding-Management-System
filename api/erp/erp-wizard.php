<?php
require_once __DIR__ . '/_init.php';
$school = erp_school_id($dbh);
$body = erp_json_body();
try {
    if ($method === 'GET') {
        $row = erp_row_school(
            $dbh,
            'SELECT current_step, completed_at FROM school_erp_wizard WHERE school_number = ? LIMIT 1',
            [$school]
        );
        if (!$row) {
            erp_send(['data' => ['currentStep' => 1, 'completed' => false]]);
        } else {
            erp_send([
                'data' => [
                    'currentStep' => (int) $row['current_step'],
                    'completed' => $row['completed_at'] !== null && $row['completed_at'] !== ''
                ]
            ]);
        }
    } elseif ($method === 'POST') {
        $step = (int) ($body['currentStep'] ?? 1);
        if ($step < 1) {
            $step = 1;
        }
        if ($step > 6) {
            $step = 6;
        }
        $completed = !empty($body['completed']);
        $done = $completed ? date('Y-m-d H:i:s') : null;
        $dbh->prepare(
            'INSERT INTO school_erp_wizard (school_number, current_step, completed_at) VALUES (?, ?, NULL)
             ON DUPLICATE KEY UPDATE current_step = ?'
        )->execute([$school, $step, $step]);
        if ($completed) {
            $dbh->prepare('UPDATE school_erp_wizard SET completed_at = ? WHERE school_number = ?')->execute([$done, $school]);
        }
        erp_send(['message' => 'Saved', 'data' => ['currentStep' => $step, 'completed' => $completed]]);
    } else {
        erp_send(['message' => 'Invalid method'], 405);
    }
} catch (Exception $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
