<?php
require_once __DIR__ . '/_init.php';
$school = erp_school_id($dbh);
$body = erp_json_body();
if ($method !== 'POST') {
    erp_send(['message' => 'Method not allowed'], 405);
}
try {
    $classIds = isset($body['classIds']) && is_array($body['classIds']) ? $body['classIds'] : [];
    $subjectIds = isset($body['subjectIds']) && is_array($body['subjectIds']) ? $body['subjectIds'] : [];
    if (count($classIds) === 0 || count($subjectIds) === 0) {
        erp_send(['error' => 'classIds and subjectIds required'], 400);
    }
    $ins = $dbh->prepare('INSERT IGNORE INTO class_subjects (class_id, subject_id) VALUES (?, ?)');
    $n = 0;
    foreach ($classIds as $cid) {
        $cid = (int) $cid;
        if ($cid < 1 || !erp_class_owned($dbh, $school, $cid)) {
            continue;
        }
        foreach ($subjectIds as $sid) {
            $sid = (int) $sid;
            if ($sid < 1 || !erp_subject_owned($dbh, $school, $sid)) {
                continue;
            }
            $ins->execute([$cid, $sid]);
            if ($ins->rowCount() > 0) {
                $n++;
            }
        }
    }
    erp_send(['message' => 'OK', 'data' => ['linksAdded' => $n]]);
} catch (Exception $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
