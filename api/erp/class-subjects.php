<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);

if ($method !== 'GET') {
    erp_send(['message' => 'Method not allowed'], 405);
}

$classId = (int) ($_GET['classId'] ?? 0);
if ($classId < 1 || !erp_class_owned($dbh, $school, $classId)) {
    erp_send(['error' => 'Class not found'], 404);
}

try {
    $st = $dbh->prepare(
        'SELECT s.id, s.name, s.sort_order
         FROM subjects s
         INNER JOIN class_subjects cs ON cs.subject_id = s.id AND cs.class_id = ?
         WHERE s.school_number = ?
         ORDER BY s.sort_order ASC, s.name ASC'
    );
    $st->execute([$classId, $school]);
    erp_send(['data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
