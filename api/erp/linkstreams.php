<?php
require_once __DIR__ . '/_init.php';
$school = erp_school_id($dbh);
$body = erp_json_body();
try {
    if ($method === 'GET') {
        $classId = (int) ($_GET['classId'] ?? 0);
        if ($classId < 1 || !erp_class_owned($dbh, $school, $classId)) {
            erp_send(['error' => 'Invalid class'], 400);
        }
        $st = $dbh->prepare(
            'SELECT cs.stream_id FROM class_streams cs INNER JOIN classes c ON c.id = cs.class_id WHERE cs.class_id = ? AND c.school_number = ?'
        );
        $st->execute([$classId, $school]);
        erp_send(['data' => array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN))]);
    } elseif ($method === 'POST') {
        $applyAll = !empty($body['applyToAllClasses']);
        $classIds = isset($body['classIds']) && is_array($body['classIds']) ? $body['classIds'] : [];
        $streamIds = isset($body['streamIds']) && is_array($body['streamIds']) ? $body['streamIds'] : [];
        $replace = !empty($body['replace']);
        if (count($streamIds) === 0) {
            erp_send(['error' => 'streamIds required'], 400);
        }
        if ($applyAll) {
            $st = $dbh->prepare('SELECT id FROM classes WHERE school_number = ?');
            $st->execute([$school]);
            $classIds = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        }
        if (count($classIds) === 0) {
            erp_send(['error' => 'No classes'], 400);
        }
        $ins = $dbh->prepare('INSERT IGNORE INTO class_streams (class_id, stream_id) VALUES (?, ?)');
        $del = $dbh->prepare('DELETE FROM class_streams WHERE class_id = ?');
        $total = 0;
        foreach ($classIds as $cid) {
            $cid = (int) $cid;
            if ($cid < 1 || !erp_class_owned($dbh, $school, $cid)) {
                continue;
            }
            if ($replace) {
                $del->execute([$cid]);
            }
            foreach ($streamIds as $sid) {
                $sid = (int) $sid;
                if ($sid < 1 || !erp_stream_owned($dbh, $school, $sid)) {
                    continue;
                }
                $ins->execute([$cid, $sid]);
                if ($ins->rowCount() > 0) {
                    $total++;
                }
            }
        }
        erp_send(['message' => 'OK', 'data' => ['links' => $total]]);
    } else {
        erp_send(['message' => 'Invalid method'], 405);
    }
} catch (Exception $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
