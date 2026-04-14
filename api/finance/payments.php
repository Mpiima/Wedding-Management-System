<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/invoice-lib.php';

$school = erp_school_id($dbh);
$body = fin_json();

try {
    if ($method === 'GET') {
        $invoiceId = (int) ($_GET['invoiceId'] ?? 0);
        $from = trim((string) ($_GET['from'] ?? ''));
        $sql = 'SELECT p.*, CONCAT(s.first_name," ",s.last_name) AS student_name, i.invoice_number
                FROM payments p
                INNER JOIN students s ON s.id = p.student_id
                INNER JOIN invoices i ON i.id = p.invoice_id
                WHERE p.school_number = ?';
        $params = [$school];
        if ($invoiceId > 0) {
            $sql .= ' AND p.invoice_id = ?';
            $params[] = $invoiceId;
        }
        if ($from !== '') {
            $sql .= ' AND p.paid_at >= ?';
            $params[] = $from;
        }
        $sql .= ' ORDER BY p.paid_at DESC, p.id DESC LIMIT 500';
        $st = $dbh->prepare($sql);
        $st->execute($params);
        fin_send(['data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($method === 'POST') {
        $invId = (int) ($body['invoiceId'] ?? 0);
        $amt = (float) ($body['amount'] ?? 0);
        $methodPay = trim((string) ($body['method'] ?? 'cash'));
        $allowed = ['cash', 'mobile_money', 'bank', 'card', 'other'];
        if (!in_array($methodPay, $allowed, true)) {
            $methodPay = 'cash';
        }
        $ref = trim((string) ($body['reference'] ?? '')) ?: null;
        $paidAt = trim((string) ($body['paidAt'] ?? '')) ?: date('Y-m-d');
        $notes = trim((string) ($body['notes'] ?? '')) ?: null;
        $r = fin_record_payment($dbh, $school, $invId, $amt, $methodPay, $ref, $paidAt, $notes);
        if (!$r['ok']) {
            fin_send(['error' => $r['message'] ?? 'Failed'], 400);
        }
        fin_send(['message' => 'Payment recorded', 'data' => $r['data'] ?? null], 201);
    }

    fin_send(['message' => 'Method not allowed'], 405);
} catch (Exception $e) {
    fin_send(['error' => $e->getMessage()], 500);
}
