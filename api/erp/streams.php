<?php
require_once __DIR__ . '/_init.php';
$school = erp_school_id($dbh);
$body = erp_json_body();
try {
    if ($method === 'GET') {
        $st = $dbh->prepare('SELECT id, name, sort_order FROM streams WHERE school_number = ? ORDER BY sort_order ASC, id ASC');
        $st->execute([$school]);
        erp_send(['data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    } elseif ($method === 'POST') {
        $name = trim((string) ($body['name'] ?? ''));
        if ($name === '') {
            erp_send(['error' => 'name is required'], 400);
        }
        $mx = erp_row_school(
            $dbh,
            'SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM streams WHERE school_number = ?',
            [$school]
        );
        $sort = (int) ($mx['n'] ?? 0);
        $st = $dbh->prepare('INSERT INTO streams (school_number, name, sort_order) VALUES (?, ?, ?)');
        $st->execute([$school, $name, $sort]);
        erp_send(['message' => 'Stream created', 'data' => ['id' => (int) $dbh->lastInsertId()]], 201);
    } elseif ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? $body['id'] ?? 0);
        if ($id < 1 || !erp_stream_owned($dbh, $school, $id)) {
            erp_send(['error' => 'Not found'], 404);
        }
        $dbh->prepare('DELETE FROM streams WHERE id = ? AND school_number = ?')->execute([$id, $school]);
        erp_send(['message' => 'Deleted']);
    } else {
        erp_send(['message' => 'Invalid method'], 405);
    }
} catch (Exception $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
