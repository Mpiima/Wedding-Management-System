<?php

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);
$body = erp_json_body();

try {
    if ($method === 'GET') {
        $st = $dbh->prepare('SELECT id, name, sort_order FROM levels WHERE school_number = ? ORDER BY sort_order ASC, id ASC');
        $st->execute([$school]);
        erp_send(['data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($method === 'POST') {
        $name = trim((string) ($body['name'] ?? ''));
        if ($name === '') {
            erp_send(['error' => 'name is required'], 400);
        }
        $mx = erp_row_school(
            $dbh,
            'SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM levels WHERE school_number = ?',
            [$school]
        );
        $sort = (int) ($mx['n'] ?? 0);
        try {
            $st = $dbh->prepare('INSERT INTO levels (school_number, name, sort_order) VALUES (?, ?, ?)');
            $st->execute([$school, $name, $sort]);
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                erp_send(['error' => 'A level with this name already exists'], 409);
            }
            throw $e;
        }
        $id = (int) $dbh->lastInsertId();
        erp_send(['message' => 'Level created', 'data' => ['id' => $id]], 201);
    }

    if ($method === 'PUT') {
        $id = (int) ($body['id'] ?? 0);
        if ($id < 1 || !erp_level_owned($dbh, $school, $id)) {
            erp_send(['error' => 'Not found'], 404);
        }
        $name = trim((string) ($body['name'] ?? ''));
        if ($name === '') {
            erp_send(['error' => 'name is required'], 400);
        }
        try {
            $dbh->prepare('UPDATE levels SET name = ? WHERE id = ? AND school_number = ?')->execute([$name, $id, $school]);
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                erp_send(['error' => 'A level with this name already exists'], 409);
            }
            throw $e;
        }
        erp_send(['message' => 'Level updated', 'data' => ['id' => $id]]);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? $body['id'] ?? 0);
        if ($id < 1 || !erp_level_owned($dbh, $school, $id)) {
            erp_send(['error' => 'Not found'], 404);
        }
        try {
            $dbh->prepare('DELETE FROM levels WHERE id = ? AND school_number = ?')->execute([$id, $school]);
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                erp_send(['error' => 'Cannot delete: classes still use this level'], 409);
            }
            throw $e;
        }
        erp_send(['message' => 'Deleted']);
    }

    erp_send(['message' => 'Invalid method'], 405);
} catch (Throwable $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
