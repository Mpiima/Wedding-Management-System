<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);
$body = erp_json_body();

/**
 * Ensure study period belongs to academic year and school.
 */
function erp_validate_year_period(PDO $dbh, string $school, int $yearId, int $periodId): void
{
    if (!erp_year_owned($dbh, $school, $yearId)) {
        erp_send(['error' => 'Academic year not found'], 404);
    }
    $period = erp_period_owned($dbh, $school, $periodId);
    if (!$period) {
        erp_send(['error' => 'Study period not found'], 404);
    }
    if ((int) $period['academic_year_id'] !== $yearId) {
        erp_send(['error' => 'Study period does not belong to this academic year'], 400);
    }
}

try {
    if ($method === 'GET') {
        $yearId = (int) ($_GET['academicYearId'] ?? 0);
        $periodId = (int) ($_GET['studyPeriodId'] ?? 0);
        if ($yearId < 1 || $periodId < 1) {
            erp_send(['error' => 'academicYearId and studyPeriodId are required'], 400);
        }
        erp_validate_year_period($dbh, $school, $yearId, $periodId);
        $st = $dbh->prepare(
            'SELECT id, academic_year_id, study_period_id, name, max_score, status, show_on_report_card, report_column_no, created_at, updated_at
             FROM exam_types
             WHERE school_number = ? AND academic_year_id = ? AND study_period_id = ?
             ORDER BY name ASC, id ASC'
        );
        $st->execute([$school, $yearId, $periodId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['show_on_report_card'] = (int) $r['show_on_report_card'];
            $r['max_score'] = (string) $r['max_score'];
        }
        unset($r);
        erp_send(['data' => $rows]);
    }

    if ($method === 'POST' || $method === 'PUT') {
        $yearId = (int) ($body['academicYearId'] ?? 0);
        $periodId = (int) ($body['studyPeriodId'] ?? 0);
        if ($yearId < 1 || $periodId < 1) {
            erp_send(['error' => 'academicYearId and studyPeriodId are required'], 400);
        }
        erp_validate_year_period($dbh, $school, $yearId, $periodId);

        $name = trim((string) ($body['name'] ?? ''));
        if ($name === '') {
            erp_send(['error' => 'name is required'], 400);
        }
        if (mb_strlen($name) > 128) {
            erp_send(['error' => 'name is too long'], 400);
        }

        $maxScore = isset($body['maxScore']) ? (float) $body['maxScore'] : 0.0;
        if ($maxScore <= 0 || $maxScore > 999999.99) {
            erp_send(['error' => 'maxScore must be between 0.01 and 999999.99'], 400);
        }

        $status = (string) ($body['status'] ?? 'active');
        if (!in_array($status, ['active', 'inactive'], true)) {
            erp_send(['error' => 'status must be active or inactive'], 400);
        }

        $showReport = !empty($body['showOnReportCard']);
        $reportCol = null;
        if ($showReport) {
            $reportCol = isset($body['reportColumnNo']) ? (int) $body['reportColumnNo'] : 0;
            if ($reportCol < 1) {
                erp_send(['error' => 'reportColumnNo is required (positive integer) when show on report card is enabled'], 400);
            }
        }

        if ($method === 'POST') {
            try {
                $st = $dbh->prepare(
                    'INSERT INTO exam_types (school_number, academic_year_id, study_period_id, name, max_score, status, show_on_report_card, report_column_no)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $st->execute([
                    $school,
                    $yearId,
                    $periodId,
                    $name,
                    number_format($maxScore, 2, '.', ''),
                    $status,
                    $showReport ? 1 : 0,
                    $reportCol
                ]);
            } catch (PDOException $e) {
                if ((int) $e->getCode() === 23000) {
                    erp_send(['error' => 'An exam type with this name already exists for this year and period'], 409);
                }
                throw $e;
            }
            $id = (int) $dbh->lastInsertId();
            erp_send(['message' => 'Exam type created', 'data' => ['id' => $id]], 201);
        }

        if ($method === 'PUT') {
            $id = (int) ($body['id'] ?? 0);
            if ($id < 1 || !erp_exam_type_owned($dbh, $school, $id)) {
                erp_send(['error' => 'Not found'], 404);
            }
            try {
                $st = $dbh->prepare(
                    'UPDATE exam_types SET academic_year_id = ?, study_period_id = ?, name = ?, max_score = ?, status = ?, show_on_report_card = ?, report_column_no = ?
                     WHERE id = ? AND school_number = ?'
                );
                $st->execute([
                    $yearId,
                    $periodId,
                    $name,
                    number_format($maxScore, 2, '.', ''),
                    $status,
                    $showReport ? 1 : 0,
                    $reportCol,
                    $id,
                    $school
                ]);
            } catch (PDOException $e) {
                if ((int) $e->getCode() === 23000) {
                    erp_send(['error' => 'An exam type with this name already exists for this year and period'], 409);
                }
                throw $e;
            }
            erp_send(['message' => 'Exam type updated', 'data' => ['id' => $id]]);
        }
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? $body['id'] ?? 0);
        if ($id < 1 || !erp_exam_type_owned($dbh, $school, $id)) {
            erp_send(['error' => 'Not found'], 404);
        }
        $dbh->prepare('DELETE FROM exam_types WHERE id = ? AND school_number = ?')->execute([$id, $school]);
        erp_send(['message' => 'Deleted']);
    }

    erp_send(['message' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
