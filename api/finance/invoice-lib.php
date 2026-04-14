<?php

declare(strict_types=1);

/**
 * Shared invoice generation & payment logic (included from admissions enroll + finance API).
 */

function fin_actor(): string
{
    return isset($_SESSION['username']) ? (string) $_SESSION['username'] : 'system';
}

function fin_finance_audit(PDO $dbh, string $school, string $entityType, int $entityId, string $action, ?string $detail = null): void
{
    try {
        $st = $dbh->prepare(
            'INSERT INTO finance_audit_log (school_number, entity_type, entity_id, action, detail, actor) VALUES (?,?,?,?,?,?)'
        );
        $st->execute([$school, $entityType, $entityId, $action, $detail, fin_actor()]);
    } catch (Throwable $e) {
        // ignore if table missing
    }
}

function fin_next_invoice_number(PDO $dbh, string $school): string
{
    $y = date('Y');
    $st = $dbh->prepare(
        'SELECT invoice_number FROM invoices WHERE school_number = ? AND invoice_number LIKE ? ORDER BY id DESC LIMIT 1'
    );
    $st->execute([$school, 'INV-' . $y . '-%']);
    $last = $st->fetchColumn();
    $n = 1;
    if ($last && preg_match('/INV-' . preg_quote($y, '/') . '-(\d+)/', (string) $last, $m)) {
        $n = (int) $m[1] + 1;
    }
    return sprintf('INV-%s-%05d', $y, $n);
}

function fin_next_receipt_number(PDO $dbh, string $school): string
{
    $y = date('Y');
    $st = $dbh->prepare(
        'SELECT receipt_number FROM payments WHERE school_number = ? AND receipt_number LIKE ? ORDER BY id DESC LIMIT 1'
    );
    $st->execute([$school, 'RCP-' . $y . '-%']);
    $last = $st->fetchColumn();
    $n = 1;
    if ($last && preg_match('/RCP-' . preg_quote($y, '/') . '-(\d+)/', (string) $last, $m)) {
        $n = (int) $m[1] + 1;
    }
    return sprintf('RCP-%s-%05d', $y, $n);
}

function fin_student_owned(PDO $dbh, string $school, int $id): bool
{
    $st = $dbh->prepare('SELECT id FROM students WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$id, $school]);
    return (bool) $st->fetchColumn();
}

/**
 * Find fee structure covering class for year+period.
 */
function fin_find_structure_for_class(PDO $dbh, string $school, int $classId, int $yearId, int $periodId): ?array
{
    $sql = 'SELECT fs.* FROM fee_structures fs
            INNER JOIN fee_structure_classes fsc ON fsc.fee_structure_id = fs.id
            WHERE fs.school_number = ? AND fs.academic_year_id = ? AND fs.study_period_id = ? AND fsc.class_id = ?
            ORDER BY fs.id DESC LIMIT 1';
    $st = $dbh->prepare($sql);
    $st->execute([$school, $yearId, $periodId, $classId]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : $row;
}

function fin_invoice_exists(PDO $dbh, int $studentId, int $yearId, int $periodId): bool
{
    $st = $dbh->prepare(
        'SELECT id FROM invoices WHERE student_id = ? AND academic_year_id = ? AND study_period_id = ? LIMIT 1'
    );
    $st->execute([$studentId, $yearId, $periodId]);
    return (bool) $st->fetchColumn();
}

function fin_get_wallet_balance(PDO $dbh, string $school, int $studentId): float
{
    $st = $dbh->prepare('SELECT COALESCE(balance,0) FROM student_credit_wallet WHERE school_number = ? AND student_id = ?');
    $st->execute([$school, $studentId]);
    return (float) $st->fetchColumn();
}

function fin_set_wallet_balance(PDO $dbh, string $school, int $studentId, float $balance): void
{
    $dbh->prepare(
        'INSERT INTO student_credit_wallet (school_number, student_id, balance) VALUES (?,?,?)
         ON DUPLICATE KEY UPDATE balance = VALUES(balance), updated_at = CURRENT_TIMESTAMP'
    )->execute([$school, $studentId, round($balance, 2)]);
}

function fin_recompute_invoice(PDO $dbh, int $invoiceId): void
{
    $st = $dbh->prepare('SELECT * FROM invoices WHERE id = ? LIMIT 1');
    $st->execute([$invoiceId]);
    $inv = $st->fetch(PDO::FETCH_ASSOC);
    if (!$inv) {
        return;
    }
    $st = $dbh->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE invoice_id = ?');
    $st->execute([$invoiceId]);
    $paid = (float) $st->fetchColumn();
    $total = (float) $inv['total_amount'];
    $bal = round(max(0, $total - $paid), 2);
    $status = 'unpaid';
    if ($paid <= 0) {
        $status = 'unpaid';
    } elseif ($bal < 0.01) {
        $status = 'paid';
    } else {
        $status = 'partial';
    }
    $dbh->prepare(
        'UPDATE invoices SET amount_paid = ?, balance_due = ?, status = ?, payments_started = IF(? > 0, 1, payments_started), locked = IF(? > 0, 1, locked) WHERE id = ?'
    )->execute([$paid, $bal, $status, $paid, $paid, $invoiceId]);
}

/**
 * @param array<string,mixed> $opts discountAmount, discountPercent, applyCredit (bool), installments: list of {percent, dueDate}, dueDate fallback
 * @return array{ok:bool,message?:string,data?:array}
 */
function fin_generate_invoice(
    PDO $dbh,
    string $school,
    int $studentId,
    int $yearId,
    int $periodId,
    int $classId,
    ?string $createdBy = null,
    array $opts = []
): array {
    $silent = !empty($opts['silent']);
    if (fin_invoice_exists($dbh, $studentId, $yearId, $periodId)) {
        return $silent ? ['ok' => false, 'message' => 'exists'] : ['ok' => false, 'message' => 'Invoice already exists for this period'];
    }
    if (!fin_student_owned($dbh, $school, $studentId)) {
        return ['ok' => false, 'message' => 'Student not found'];
    }
    $fs = fin_find_structure_for_class($dbh, $school, $classId, $yearId, $periodId);
    if (!$fs) {
        return $silent ? ['ok' => false, 'message' => 'no_structure'] : ['ok' => false, 'message' => 'No fee structure for this class, year and period'];
    }
    $fsId = (int) $fs['id'];
    $st = $dbh->prepare(
        'SELECT label, amount, item_type, sort_order FROM fee_structure_items WHERE fee_structure_id = ? ORDER BY sort_order ASC, id ASC'
    );
    $st->execute([$fsId]);
    $items = $st->fetchAll(PDO::FETCH_ASSOC);
    if (!$items) {
        return $silent ? ['ok' => false, 'message' => 'empty_structure'] : ['ok' => false, 'message' => 'Fee structure has no line items'];
    }
    $subtotal = 0.0;
    foreach ($items as $it) {
        $subtotal += (float) $it['amount'];
    }
    $discAmt = (float) ($opts['discountAmount'] ?? 0);
    $discPct = (float) ($opts['discountPercent'] ?? 0);
    if ($discPct > 0) {
        $discAmt += round($subtotal * ($discPct / 100), 2);
    }
    $discAmt = min($discAmt, $subtotal);
    $creditApplied = 0.0;
    if (!empty($opts['applyCredit'])) {
        $wallet = fin_get_wallet_balance($dbh, $school, $studentId);
        $creditApplied = min($wallet, max(0, $subtotal - $discAmt));
    }
    $total = round(max(0, $subtotal - $discAmt - $creditApplied), 2);
    $invNo = fin_next_invoice_number($dbh, $school);
    $dueDate = isset($opts['dueDate']) ? (string) $opts['dueDate'] : null;
    if (!$dueDate) {
        $per = $dbh->prepare('SELECT end_date FROM study_periods WHERE id = ? AND school_number = ?');
        $per->execute([$periodId, $school]);
        $end = $per->fetchColumn();
        $dueDate = $end ? (string) $end : date('Y-m-d', strtotime('+30 days'));
    }
    $actor = $createdBy ?? fin_actor();
    $dbh->beginTransaction();
    try {
        $dbh->prepare(
            'INSERT INTO invoices (school_number, invoice_number, student_id, academic_year_id, study_period_id, class_id, fee_structure_id,
             subtotal, discount_amount, discount_percent, credit_applied, total_amount, amount_paid, balance_due, status, due_date, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $school,
            $invNo,
            $studentId,
            $yearId,
            $periodId,
            $classId,
            $fsId,
            $subtotal,
            $discAmt,
            $discPct,
            $creditApplied,
            $total,
            0,
            $total,
            'unpaid',
            $dueDate,
            $actor
        ]);
        $invId = (int) $dbh->lastInsertId();
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
        if ($creditApplied > 0.009) {
            $newW = round(fin_get_wallet_balance($dbh, $school, $studentId) - $creditApplied, 2);
            fin_set_wallet_balance($dbh, $school, $studentId, max(0, $newW));
        }
        $instPlan = $opts['installments'] ?? null;
        if (is_array($instPlan) && count($instPlan) > 0) {
            $seq = 1;
            $ins = $dbh->prepare(
                'INSERT INTO invoice_installments (invoice_id, sequence_no, due_date, amount, amount_paid) VALUES (?,?,?,?,0)'
            );
            foreach ($instPlan as $row) {
                $pct = (float) ($row['percent'] ?? 0);
                $dd = (string) ($row['dueDate'] ?? $dueDate);
                $amt = round($total * ($pct / 100), 2);
                if ($amt > 0) {
                    $ins->execute([$invId, $seq++, $dd, $amt]);
                }
            }
        } else {
            $dbh->prepare(
                'INSERT INTO invoice_installments (invoice_id, sequence_no, due_date, amount, amount_paid) VALUES (?,?,?,?,0)'
            )->execute([$invId, 1, $dueDate, $total]);
        }
        if ($creditApplied > 0.009) {
            fin_finance_audit($dbh, $school, 'invoice', $invId, 'credit_applied', 'Applied ' . $creditApplied);
        }
        fin_finance_audit($dbh, $school, 'invoice', $invId, 'created', $invNo);
        $dbh->commit();
        return ['ok' => true, 'data' => ['invoiceId' => $invId, 'invoiceNumber' => $invNo, 'total' => $total]];
    } catch (Throwable $e) {
        $dbh->rollBack();
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

function fin_try_generate_invoice_after_enrollment(
    PDO $dbh,
    string $school,
    int $studentId,
    int $yearId,
    int $periodId,
    int $classId
): void {
    try {
        fin_generate_invoice($dbh, $school, $studentId, $yearId, $periodId, $classId, null, ['silent' => true]);
    } catch (Throwable $e) {
        // silent
    }
}

/**
 * @return array{ok:bool,message?:string,data?:array}
 */
function fin_record_payment(
    PDO $dbh,
    string $school,
    int $invoiceId,
    float $amount,
    string $method,
    ?string $reference,
    string $paidAt,
    ?string $notes = null
): array {
    if ($amount <= 0) {
        return ['ok' => false, 'message' => 'Amount must be positive'];
    }
    $st = $dbh->prepare(
        'SELECT i.*, s.school_number AS student_school FROM invoices i INNER JOIN students s ON s.id = i.student_id WHERE i.id = ? LIMIT 1'
    );
    $st->execute([$invoiceId]);
    $inv = $st->fetch(PDO::FETCH_ASSOC);
    if (!$inv || (string) $inv['student_school'] !== $school) {
        return ['ok' => false, 'message' => 'Invoice not found'];
    }
    $balance = (float) $inv['balance_due'];
    $studentId = (int) $inv['student_id'];
    $apply = min($amount, $balance);
    $over = round($amount - $apply, 2);
    $dbh->beginTransaction();
    try {
        $rcp = fin_next_receipt_number($dbh, $school);
        $dbh->prepare(
            'INSERT INTO payments (school_number, invoice_id, student_id, amount, method, reference, paid_at, receipt_number, notes, recorded_by)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $school,
            $invoiceId,
            $studentId,
            $amount,
            $method,
            $reference,
            $paidAt,
            $rcp,
            $notes,
            fin_actor()
        ]);
        $payId = (int) $dbh->lastInsertId();
        fin_recompute_invoice($dbh, $invoiceId);
        if ($over > 0.009) {
            $w = fin_get_wallet_balance($dbh, $school, $studentId);
            fin_set_wallet_balance($dbh, $school, $studentId, $w + $over);
        }
        $msg = 'Payment of ' . number_format((float) $apply, 2) . ' recorded. Receipt ' . $rcp;
        if ($over > 0.009) {
            $msg .= ' Credit ' . number_format($over, 2) . ' added to wallet.';
        }
        fin_finance_audit($dbh, $school, 'payment', $payId, 'recorded', $rcp);
        $dbh->prepare(
            'INSERT INTO finance_notifications (school_number, student_id, channel, event_key, message) VALUES (?,?,?,?,?)'
        )->execute([$school, $studentId, 'in_app', 'payment_received', $msg]);
        $dbh->commit();
        return ['ok' => true, 'data' => ['paymentId' => $payId, 'receiptNumber' => $rcp, 'applied' => $apply, 'creditToWallet' => $over]];
    } catch (Throwable $e) {
        $dbh->rollBack();
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}
