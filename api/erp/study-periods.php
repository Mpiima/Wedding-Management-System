<?php

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);
$body = erp_json_body();

try {
    if ($method === 'GET') {
        $yearId = (int) ($_GET['academic_year_id'] ?? $_GET['academicYearId'] ?? 0);
        if ($yearId < 1 || !erp_year_owned($dbh, $school, $yearId)) {
            erp_send(['error' => 'Invalid academic year'], 400);
        }
        $st = $dbh->prepare(
            'SELECT id, academic_year_id, name, start_date, end_date, is_active, sort_order
             FROM study_periods WHERE school_number = ? AND academic_year_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $st->execute([$school, $yearId]);
        erp_send(['data' => $st->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($method === 'POST') {
        $action = (string) ($body['action'] ?? '');
        if ($action === 'reorder') {
            $ids = $body['order'] ?? $body['ids'] ?? [];
            if (!is_array($ids) || count($ids) === 0) {
                erp_send(['error' => 'order array required'], 400);
            }
            $yearId = (int) ($body['academicYearId'] ?? $body['academic_year_id'] ?? 0);
            if ($yearId < 1 || !erp_year_owned($dbh, $school, $yearId)) {
                erp_send(['error' => 'Invalid academic year'], 400);
            }
            $ord = 0;
            foreach ($ids as $pid) {
                $pid = (int) $pid;
                if ($pid < 1) {
                    continue;
                }
                $r = erp_period_owned($dbh, $school, $pid);
                if ($r && (int) $r['academic_year_id'] === $yearId) {
                    $dbh->prepare('UPDATE study_periods SET sort_order = ? WHERE id = ? AND school_number = ?')
                        ->execute([$ord++, $pid, $school]);
                }
            }
            erp_send(['message' => 'Order updated']);
        }

        $yearId = (int) ($body['academicYearId'] ?? $body['academic_year_id'] ?? 0);
        if ($yearId < 1 || !erp_year_owned($dbh, $school, $yearId)) {
            erp_send(['error' => 'Valid academicYearId required'], 400);
        }
        $name = trim((string) ($body['name'] ?? ''));
        if ($name === '') {
            erp_send(['error' => 'name is required'], 400);
        }
        $start = trim((string) ($body['startDate'] ?? $body['start_date'] ?? ''));
        $end = trim((string) ($body['endDate'] ?? $body['end_date'] ?? ''));
        $start = $start === '' ? null : $start;
        $end = $end === '' ? null : $end;
        if ($start !== null && $end !== null && $start >= $end) {
            erp_send(['error' => 'startDate must be before endDate'], 400);
        }

        $active = !empty($body['isActive']) || !empty($body['is_active']);
        $mx = erp_row_school(
            $dbh,
            'SELECT COALESCE(MAX(sort_order), -1) + 1 AS n FROM study_periods WHERE school_number = ? AND academic_year_id = ?',
            [$school, $yearId]
        );
        $sort = (int) ($mx['n'] ?? 0);

        if ($active) {
            erp_deactivate_other_periods($dbh, $school, $yearId, 0);
        }

        $st = $dbh->prepare(
            'INSERT INTO study_periods (school_number, academic_year_id, name, start_date, end_date, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$school, $yearId, $name, $start, $end, $active ? 1 : 0, $sort]);
        $id = (int) $dbh->lastInsertId();
        if ($active) {
            erp_deactivate_other_periods($dbh, $school, $yearId, $id);
            $dbh->prepare('UPDATE study_periods SET is_active = 1 WHERE id = ?')->execute([$id]);
        }

        erp_send(['message' => 'Study period created', 'data' => ['id' => $id]], 201);
    }

    if ($method === 'PUT') {
        $id = (int) ($body['id'] ?? 0);
        $row = erp_period_owned($dbh, $school, $id);
        if (!$row) {
            erp_send(['error' => 'Not found'], 404);
        }
        $yearId = (int) $row['academic_year_id'];
        $name = trim((string) ($body['name'] ?? ''));
        if ($name === '') {
            erp_send(['error' => 'name is required'], 400);
        }
        $start = trim((string) ($body['startDate'] ?? $body['start_date'] ?? ''));
        $end = trim((string) ($body['endDate'] ?? $body['end_date'] ?? ''));
        $start = $start === '' ? null : $start;
        $end = $end === '' ? null : $end;
        if ($start !== null && $end !== null && $start >= $end) {
            erp_send(['error' => 'startDate must be before endDate'], 400);
        }

        $active = array_key_exists('isActive', $body) || array_key_exists('is_active', $body)
            ? (!empty($body['isActive']) || !empty($body['is_active']))
            : null;

        if ($active === true) {
            erp_deactivate_other_periods($dbh, $school, $yearId, $id);
        }

        if ($active !== null) {
            $dbh->prepare(
                'UPDATE study_periods SET name = ?, start_date = ?, end_date = ?, is_active = ? WHERE id = ? AND school_number = ?'
            )->execute([$name, $start, $end, $active ? 1 : 0, $id, $school]);
        } else {
            $dbh->prepare(
                'UPDATE study_periods SET name = ?, start_date = ?, end_date = ? WHERE id = ? AND school_number = ?'
            )->execute([$name, $start, $end, $id, $school]);
        }

        erp_send(['message' => 'Study period updated', 'data' => ['id' => $id]]);
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? $body['id'] ?? 0);
        if ($id < 1 || !erp_period_owned($dbh, $school, $id)) {
            erp_send(['error' => 'Not found'], 404);
        }
        $dbh->prepare('DELETE FROM study_periods WHERE id = ? AND school_number = ?')->execute([$id, $school]);
        erp_send(['message' => 'Deleted']);
    }

    erp_send(['message' => 'Invalid method'], 405);
} catch (Throwable $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
