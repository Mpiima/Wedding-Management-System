<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/invoice-lib.php';

$school = erp_school_id($dbh);
$body = fin_json();

try {
    if ($method === 'GET') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            $st = $dbh->prepare(
                'SELECT i.*, CONCAT(s.first_name," ",s.last_name) AS student_name, s.admission_number
                 FROM invoices i INNER JOIN students s ON s.id = i.student_id
                 WHERE i.id = ? AND i.school_number = ?'
            );
            $st->execute([$id, $school]);
            $inv = $st->fetch(PDO::FETCH_ASSOC);
            if (!$inv) {
                fin_send(['error' => 'Not found'], 404);
            }
            $ln = $dbh->prepare('SELECT * FROM invoice_lines WHERE invoice_id = ? ORDER BY sort_order, id');
            $ln->execute([$id]);
            $inv['lines'] = $ln->fetchAll(PDO::FETCH_ASSOC);
            $inst = $dbh->prepare('SELECT * FROM invoice_installments WHERE invoice_id = ? ORDER BY sequence_no');
            $inst->execute([$id]);
            $inv['installments'] = $inst->fetchAll(PDO::FETCH_ASSOC);
            $pay = $dbh->prepare('SELECT * FROM payments WHERE invoice_id = ? ORDER BY paid_at DESC, id DESC');
            $pay->execute([$id]);
            $inv['payments'] = $pay->fetchAll(PDO::FETCH_ASSOC);
            fin_send(['data' => $inv]);
        }
        $year = (int) ($_GET['academicYearId'] ?? 0);
        $period = (int) ($_GET['studyPeriodId'] ?? 0);
        $status = trim((string) ($_GET['status'] ?? ''));
        $sql = 'SELECT i.*, CONCAT(s.first_name," ",s.last_name) AS student_name, s.admission_number
                FROM invoices i INNER JOIN students s ON s.id = i.student_id
                WHERE i.school_number = ?';
        $p = [$school];
        if ($year > 0) {
            $sql .= ' AND i.academic_year_id = ?';
            $p[] = $year;
        }
        if ($period > 0) {
            $sql .= ' AND i.study_period_id = ?';
            $p[] = $period;
        }
        if ($status !== '' && in_array($status, ['unpaid', 'partial', 'paid'], true)) {
            $sql .= ' AND i.status = ?';
            $p[] = $status;
        }
        $sql .= ' ORDER BY i.id DESC LIMIT 500';
        $st = $dbh->prepare($sql);
        $st->execute($p);
        fin_send(['data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($method === 'POST') {
        $action = trim((string) ($body['action'] ?? 'generate'));
        if ($action === 'generate') {
            $sid = (int) ($body['studentId'] ?? 0);
            $y = (int) ($body['academicYearId'] ?? 0);
            $p = (int) ($body['studyPeriodId'] ?? 0);
            $c = (int) ($body['classId'] ?? 0);
            if ($sid < 1 || $y < 1 || $p < 1 || $c < 1) {
                fin_send(['error' => 'studentId, academicYearId, studyPeriodId, classId required'], 400);
            }
            $opts = [
                'discountAmount' => (float) ($body['discountAmount'] ?? 0),
                'discountPercent' => (float) ($body['discountPercent'] ?? 0),
                'applyCredit' => !empty($body['applyCredit']),
                'dueDate' => $body['dueDate'] ?? null,
            ];
            if (!empty($body['installments']) && is_array($body['installments'])) {
                $opts['installments'] = $body['installments'];
            }
            $r = fin_generate_invoice($dbh, $school, $sid, $y, $p, $c, fin_actor(), $opts);
            if (!$r['ok']) {
                fin_send(['error' => $r['message'] ?? 'Failed'], 400);
            }
            fin_send(['message' => 'Invoice created', 'data' => $r['data'] ?? null], 201);
        }
        if ($action === 'regenerate') {
            $invId = (int) ($body['invoiceId'] ?? 0);
            if ($invId < 1) {
                fin_send(['error' => 'invoiceId required'], 400);
            }
            $st = $dbh->prepare('SELECT * FROM invoices WHERE id = ? AND school_number = ?');
            $st->execute([$invId, $school]);
            $inv = $st->fetch(PDO::FETCH_ASSOC);
            if (!$inv) {
                fin_send(['error' => 'Not found'], 404);
            }
            if ((float) $inv['amount_paid'] > 0.009 || (int) $inv['payments_started']) {
                fin_send(['error' => 'Cannot regenerate after payments'], 400);
            }
            $fsId = (int) ($inv['fee_structure_id'] ?? 0);
            if ($fsId < 1) {
                fin_send(['error' => 'Invoice missing fee structure'], 400);
            }
            $st = $dbh->prepare('SELECT label, amount, item_type, sort_order FROM fee_structure_items WHERE fee_structure_id = ? ORDER BY sort_order');
            $st->execute([$fsId]);
            $items = $st->fetchAll(PDO::FETCH_ASSOC);
            if (!$items) {
                fin_send(['error' => 'Structure has no items'], 400);
            }
            $subtotal = 0;
            foreach ($items as $it) {
                $subtotal += (float) $it['amount'];
            }
            $disc = (float) $inv['discount_amount'];
            $pct = (float) $inv['discount_percent'];
            if ($pct > 0) {
                $disc = min($subtotal, round($subtotal * ($pct / 100), 2));
            }
            $cred = (float) $inv['credit_applied'];
            $total = round(max(0, $subtotal - $disc - $cred), 2);
            $dbh->beginTransaction();
            $dbh->prepare('DELETE FROM invoice_lines WHERE invoice_id = ?')->execute([$invId]);
            $dbh->prepare('DELETE FROM invoice_installments WHERE invoice_id = ?')->execute([$invId]);
            $ln = $dbh->prepare(
                'INSERT INTO invoice_lines (invoice_id, label, line_source, amount, item_type, sort_order) VALUES (?,?,?,?,?,?)'
            );
            foreach ($items as $it) {
                $ln->execute([
                    $invId,
                    $it['label'],
                    'structure',
                    $it['amount'],
                    $it['item_type'],
                    (int) $it['sort_order']
                ]);
            }
            $dbh->prepare(
                'UPDATE invoices SET subtotal = ?, discount_amount = ?, total_amount = ?, balance_due = ?, amount_paid = 0, status = \'unpaid\' WHERE id = ?'
            )->execute([$subtotal, $disc, $total, $total, $invId]);
            $due = $inv['due_date'];
            $dbh->prepare(
                'INSERT INTO invoice_installments (invoice_id, sequence_no, due_date, amount, amount_paid) VALUES (?,?,?,?,0)'
            )->execute([$invId, 1, $due, $total]);
            fin_finance_audit($dbh, $school, 'invoice', $invId, 'regenerated', null);
            $dbh->commit();
            fin_send(['message' => 'Regenerated']);
        }
        fin_send(['error' => 'Unknown action'], 400);
    }

    fin_send(['message' => 'Method not allowed'], 405);
} catch (Exception $e) {
    fin_send(['error' => $e->getMessage()], 500);
}
