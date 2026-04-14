<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);

if ($method !== 'GET') {
    erp_send(['message' => 'Method not allowed'], 405);
}

try {
    $st = $dbh->prepare(
        "SELECT id, firstname, lastname, email, username, role
         FROM users
         WHERE ssid = ? AND position NOT IN ('member','rejected')
         ORDER BY firstname ASC, lastname ASC, id ASC"
    );
    $st->execute([$school]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $fn = trim((string) ($r['firstname'] ?? ''));
        $ln = trim((string) ($r['lastname'] ?? ''));
        $r['display_name'] = trim($fn . ' ' . $ln) !== '' ? trim($fn . ' ' . $ln) : (string) ($r['username'] ?? $r['email']);
    }
    unset($r);
    erp_send(['data' => $rows]);
} catch (Throwable $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
