<?php

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);
$body = erp_json_body();

try {
    if ($method === 'GET') {
        $st = $dbh->prepare(
            'SELECT id, name, start_date, end_date, is_active, created_at FROM academic_years
             WHERE school_number = ? ORDER BY start_date DESC'
        );
        $st->execute([$school]);
        erp_send(['data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($method === 'POST') {
        $name = trim((string) ($body['name'] ?? ''));
        $start = trim((string) ($body['startDate'] ?? $body['start_date'] ?? ''));
        $end = trim((string) ($body['endDate'] ?? $body['end_date'] ?? ''));
        $active = !empty($body['isActive']) || !empty($body['is_active']);

        if ($name === '' || $start === '' || $end === '') {
            erp_send(['error' => 'name, startDate and endDate are required'], 400);
        }
        if ($start >= $end) {
            erp_send(['error' => 'startDate must be before endDate'], 400);
        }

        if ($active) {
            $dbh->prepare('UPDATE academic_years SET is_active = 0 WHERE school_number = ?')->execute([$school]);
        }

        $st = $dbh->prepare(
            'INSERT INTO academic_years (school_number, name, start_date, end_date, is_active)
             VALUES (?, ?, ?, ?, ?)'
        );
        $st->execute([$school, $name, $start, $end, $active ? 1 : 0]);
        $id = (int) $dbh->lastInsertId();

        erp_send(['message' => 'Academic year created', 'data' => ['id' => $id]], 201);
    }

    if ($method === 'PUT') {
        $id = (int) ($body['id'] ?? 0);
        if ($id < 1 || !erp_year_owned($dbh, $school, $id)) {
            erp_send(['error' => 'Year not found'], 404);
        }
        $name = trim((string) ($body['name'] ?? ''));
        $start = trim((string) ($body['startDate'] ?? $body['start_date'] ?? ''));
        $end = trim((string) ($body['endDate'] ?? $body['end_date'] ?? ''));
        if ($name === '' || $start === '' || $end === '') {
            erp_send(['error' => 'name, startDate and endDate are required'], 400);
        }
        if ($start >= $end) {
            erp_send(['error' => 'startDate must be before endDate'], 400);
        }
        $active = array_key_exists('isActive', $body) || array_key_exists('is_active', $body)
            ? (!empty($body['isActive']) || !empty($body['is_active']))
            : null;

        if ($active === true) {
            erp_deactivate_other_years($dbh, $school, $id);
        }

        if ($active !== null) {
            $st = $dbh->prepare(
                'UPDATE academic_years SET name = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ? AND school_number = ?'
            );
            $st->execute([$name, $start, $end, $active ? 1 : 0, $id, $school]);
        } else {
            $st = $dbh->prepare(
                'UPDATE academic_years SET name = ?, start_date = ?, end_date = ? WHERE id = ? AND school_number = ?'
            );
            $st->execute([$name, $start, $end, $id, $school]);
        }

        erp_send(['message' => 'Academic year updated', 'data' => ['id' => $id]]);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? $body['id'] ?? 0);
        if ($id < 1 || !erp_year_owned($dbh, $school, $id)) {
            erp_send(['error' => 'Year not found'], 404);
        }
        $dbh->prepare('DELETE FROM academic_years WHERE id = ? AND school_number = ?')->execute([$id, $school]);
        erp_send(['message' => 'Deleted']);
    }

    erp_send(['message' => 'Invalid method'], 405);
} catch (Throwable $e) {
    erp_send(['error' => $e->getMessage()], 500);
}

