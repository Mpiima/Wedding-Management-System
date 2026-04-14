<?php
require_once __DIR__ . '/_init.php';
$school = adm_school_id();
if ($method !== 'GET') {
    adm_send(['message' => 'Method not allowed'], 405);
}
$fn = trim((string) ($_GET['firstName'] ?? ''));
$ln = trim((string) ($_GET['lastName'] ?? ''));
$ph = trim((string) ($_GET['parentPhone'] ?? ''));
if ($fn === '' || $ln === '' || $ph === '') {
    adm_send(['data' => ['duplicate' => false]]);
}
try {
    $st = $dbh->prepare(
        'SELECT id, application_number FROM applicants WHERE school_number = ? AND parent_phone = ? AND LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?) AND pipeline_status NOT IN (\'rejected\',\'enrolled\') LIMIT 1'
    );
    $st->execute([$school, $ph, $fn, $ln]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    adm_send(['data' => ['duplicate' => (bool) $row, 'existing' => $row ?: null]]);
} catch (Exception $e) {
    adm_send(['error' => $e->getMessage()], 500);
}
