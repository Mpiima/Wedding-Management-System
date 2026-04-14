<?php

require_once __DIR__ . '/_init.php';
require_once __DIR__ . '/enroll-core.php';

$school = adm_school_id();
$body = adm_json();

if ($method !== 'POST') {
    adm_send(['message' => 'Method not allowed'], 405);
}

$ids = $body['applicantIds'] ?? [];
if (!is_array($ids) || count($ids) === 0) {
    adm_send(['error' => 'applicantIds array required'], 400);
}

$yearId = (int) ($body['academicYearId'] ?? 0);
$periodId = (int) ($body['studyPeriodId'] ?? 0);
$classId = (int) ($body['classId'] ?? 0);
$streamId = (int) ($body['streamId'] ?? 0) ?: null;
$force = !empty($body['forceCapacity']);

$results = ['ok' => 0, 'errors' => []];

foreach ($ids as $aid) {
    $aid = (int) $aid;
    if ($aid < 1) {
        continue;
    }
    $sub = [
        'applicantId' => $aid,
        'academicYearId' => $yearId,
        'studyPeriodId' => $periodId,
        'classId' => $classId,
        'streamId' => $streamId,
        'forceCapacity' => $force
    ];
    $r = adm_perform_enroll($dbh, $school, $sub);
    if ($r['error'] === null) {
        $results['ok']++;
    } else {
        $results['errors'][] = ['applicantId' => $aid, 'error' => $r['error']];
    }
}

adm_send(['data' => $results]);
