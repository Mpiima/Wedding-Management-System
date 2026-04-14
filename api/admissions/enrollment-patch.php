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
    $classId = (int) ($body['classId'] ?? 0);
    $streamId = (int) ($body['streamId'] ?? 0) ?: null;
    if ($eid < 1 || $classId < 1) {
        adm_send(['error' => 'enrollmentId and classId required'], 400);
    }

    $st = $dbh->prepare('SELECT id FROM enrollments WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$eid, $school]);
    if (!$st->fetchColumn()) {
        adm_send(['error' => 'Enrollment not found'], 404);
    }

    $st = $dbh->prepare('SELECT id FROM classes WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$classId, $school]);
    if (!$st->fetchColumn()) {
        adm_send(['error' => 'Invalid class'], 400);
    }

    if ($streamId) {
        $st = $dbh->prepare('SELECT id FROM streams WHERE id = ? AND school_number = ? LIMIT 1');
        $st->execute([$streamId, $school]);
        if (!$st->fetchColumn()) {
            adm_send(['error' => 'Invalid stream'], 400);
        }
    }

    $dbh->prepare('UPDATE enrollments SET class_id = ?, stream_id = ? WHERE id = ? AND school_number = ?')->execute([
        $classId,
        $streamId,
        $eid,
        $school
    ]);

    adm_send(['message' => 'Updated']);
} catch (Exception $e) {
    adm_send(['error' => $e->getMessage()], 500);
}
