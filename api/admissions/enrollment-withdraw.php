<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$school = adm_school_id();
$body = adm_json();

if ($method !== 'POST') {
    adm_send(['message' => 'Method not allowed'], 405);
}

try {
    $eid = (int) ($body['enrollmentId'] ?? 0);
    if ($eid < 1) {
        adm_send(['error' => 'enrollmentId required'], 400);
    }

    $st = $dbh->prepare('SELECT id FROM enrollments WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$eid, $school]);
    if (!$st->fetchColumn()) {
        adm_send(['error' => 'Enrollment not found'], 404);
    }

    $dbh->prepare('DELETE FROM enrollments WHERE id = ? AND school_number = ?')->execute([$eid, $school]);

    adm_send(['message' => 'Withdrawn']);
} catch (Exception $e) {
    adm_send(['error' => $e->getMessage()], 500);
}
