<?php
require_once __DIR__ . '/_init.php';
$school = erp_school_id($dbh);
$body = erp_json_body();
try {
    if ($method === 'GET') {
        $st = $dbh->prepare(
            'SELECT c.id, c.level_id, c.name, c.sort_order, c.max_students, l.name AS level_name FROM classes c
             INNER JOIN levels l ON l.id = c.level_id AND l.school_number = c.school_number
             WHERE c.school_number = ? ORDER BY l.sort_order ASC, c.sort_order ASC, c.id ASC'
        );
        $st->execute([$school]);
        erp_send(['data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    } elseif ($method === 'POST' && !empty($body['bulk'])) {
        $levelId = (int) ($body['levelId'] ?? 0);
        if ($levelId < 1 || !erp_level_owned($dbh, $school, $levelId)) {
            erp_send(['error' => 'Valid levelId required'], 400);
        }
        $prefix = trim((string) ($body['prefix'] ?? 'P'));
        $from = (int) ($body['from'] ?? 1);
        $to = (int) ($body['to'] ?? 7);
        $mx = erp_row_school(
            $dbh,
            'SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM classes WHERE school_number = ? AND level_id = ?',
            [$school, $levelId]
        );
        $sort = (int) ($mx['n'] ?? 0);
        $ins = $dbh->prepare('INSERT INTO classes (school_number, level_id, name, sort_order) VALUES (?, ?, ?, ?)');
        $created = 0;
        for ($i = $from; $i <= $to && $i <= $from + 50; $i++) {
            try {
                $ins->execute([$school, $levelId, $prefix . $i, $sort++]);
                $created++;
            } catch (PDOException $e) {
                if ((int) $e->getCode() !== 23000) {
                    throw $e;
                }
            }
        }
        erp_send(['message' => 'Bulk create finished', 'data' => ['created' => $created]]);
    } elseif ($method === 'POST') {
        $levelId = (int) ($body['levelId'] ?? 0);
        $name = trim((string) ($body['name'] ?? ''));
        if ($levelId < 1 || !erp_level_owned($dbh, $school, $levelId) || $name === '') {
            erp_send(['error' => 'levelId and name required'], 400);
        }
        $mx = erp_row_school(
            $dbh,
            'SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM classes WHERE school_number = ? AND level_id = ?',
            [$school, $levelId]
        );
        $sort = (int) ($mx['n'] ?? 0);
        $st = $dbh->prepare('INSERT INTO classes (school_number, level_id, name, sort_order) VALUES (?, ?, ?, ?)');
        $st->execute([$school, $levelId, $name, $sort]);
        erp_send(['message' => 'Class created', 'data' => ['id' => (int) $dbh->lastInsertId()]], 201);
    } elseif ($method === 'PUT') {
        $id = (int) ($body['id'] ?? 0);
        if ($id < 1 || !erp_class_owned($dbh, $school, $id)) {
            erp_send(['error' => 'Not found'], 404);
        }
        $name = trim((string) ($body['name'] ?? ''));
        if ($name === '') {
            erp_send(['error' => 'name required'], 400);
        }
        $dbh->prepare('UPDATE classes SET name = ? WHERE id = ? AND school_number = ?')->execute([$name, $id, $school]);
        erp_send(['message' => 'Class updated', 'data' => ['id' => $id]]);
    } elseif ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? $body['id'] ?? 0);
        if ($id < 1 || !erp_class_owned($dbh, $school, $id)) {
            erp_send(['error' => 'Not found'], 404);
        }
        $dbh->prepare('DELETE FROM classes WHERE id = ? AND school_number = ?')->execute([$id, $school]);
        erp_send(['message' => 'Deleted']);
    } else {
        erp_send(['message' => 'Invalid method'], 405);
    }
} catch (Exception $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
