<?php
require_once __DIR__ . '/_init.php';
$school = erp_school_id($dbh);
$body = erp_json_body();

try {
    if ($method === 'GET') {
        $st = $dbh->prepare('SELECT id, name, sort_order FROM subjects WHERE school_number = ? ORDER BY sort_order ASC, id ASC');
        $st->execute([$school]);
        erp_send(['data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($method === 'POST') {
        $names = $body['names'] ?? null;
        if (is_array($names) && count($names) > 0) {
            $mx = erp_row_school(
                $dbh,
                'SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM subjects WHERE school_number = ?',
                [$school]
            );
            $sort = (int) ($mx['n'] ?? 0);
            $ins = $dbh->prepare('INSERT INTO subjects (school_number, name, sort_order) VALUES (?, ?, ?)');
            $added = 0;
            foreach ($names as $n) {
                $nm = trim((string) $n);
                if ($nm === '') {
                    continue;
                }
                try {
                    $ins->execute([$school, $nm, $sort++]);
                    $added++;
                } catch (PDOException $e) {
                    if ((int) $e->getCode() !== 23000) {
                        throw $e;
                    }
                }
            }
            erp_send(['message' => 'Subjects imported', 'data' => ['added' => $added]]);
        }

        $name = trim((string) ($body['name'] ?? ''));
        if ($name === '') {
            erp_send(['error' => 'name is required'], 400);
        }
        $mx = erp_row_school(
            $dbh,
            'SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM subjects WHERE school_number = ?',
            [$school]
        );
        $sort = (int) ($mx['n'] ?? 0);
        try {
            $st = $dbh->prepare('INSERT INTO subjects (school_number, name, sort_order) VALUES (?, ?, ?)');
            $st->execute([$school, $name, $sort]);
        } catch (PDOException $e) {
            if ((int) $e->getCode() === 23000) {
                erp_send(['error' => 'Subject already exists'], 409);
            }
            throw $e;
        }
        $id = (int) $dbh->lastInsertId();
        erp_send(['message' => 'Subject created', 'data' => ['id' => $id]], 201);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? $body['id'] ?? 0);
        if ($id < 1 || !erp_subject_owned($dbh, $school, $id)) {
            erp_send(['error' => 'Not found'], 404);
        }
        $dbh->prepare('DELETE FROM subjects WHERE id = ? AND school_number = ?')->execute([$id, $school]);
        erp_send(['message' => 'Deleted']);
    }

    erp_send(['message' => 'Invalid method'], 405);
} catch (Exception $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
