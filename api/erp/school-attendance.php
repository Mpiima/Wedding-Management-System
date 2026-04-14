<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);
$body = erp_json_body();

/**
 * @return array{0:string,1:?string}
 */
function sa_normalize_date(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [date('Y-m-d'), null];
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $raw);
    if (!$dt || $dt->format('Y-m-d') !== $raw) {
        return ['', 'Invalid date (use YYYY-MM-DD)'];
    }
    return [$dt->format('Y-m-d'), null];
}

function sa_valid_time(?string $v): bool
{
    if ($v === null || trim($v) === '') {
        return true;
    }
    return (bool) preg_match('/^\d{2}:\d{2}(:\d{2})?$/', trim($v));
}

/**
 * @return array{year_id:int, period_id:int}
 */
function sa_resolve_year_period(PDO $dbh, string $school, int $yearId, int $periodId): array
{
    if ($yearId < 1) {
        $yr = erp_row_school($dbh, 'SELECT id FROM academic_years WHERE school_number = ? AND is_active = 1 LIMIT 1', [$school]);
        if (!$yr) {
            erp_send(['error' => 'No active academic year found'], 400);
        }
        $yearId = (int) $yr['id'];
    }
    if ($periodId < 1) {
        $pr = erp_row_school(
            $dbh,
            'SELECT id FROM study_periods WHERE school_number = ? AND academic_year_id = ? AND is_active = 1 LIMIT 1',
            [$school, $yearId]
        );
        if (!$pr) {
            $pr = erp_row_school(
                $dbh,
                'SELECT id FROM study_periods WHERE school_number = ? AND academic_year_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1',
                [$school, $yearId]
            );
        }
        if (!$pr) {
            erp_send(['error' => 'No study period found for selected year'], 400);
        }
        $periodId = (int) $pr['id'];
    }
    if (!erp_year_owned($dbh, $school, $yearId)) {
        erp_send(['error' => 'Academic year not found'], 404);
    }
    $period = erp_period_owned($dbh, $school, $periodId);
    if (!$period) {
        erp_send(['error' => 'Study period not found'], 404);
    }
    if ((int) $period['academic_year_id'] !== $yearId) {
        erp_send(['error' => 'Study period does not belong to selected year'], 400);
    }
    return ['year_id' => $yearId, 'period_id' => $periodId];
}

try {
    if ($method === 'GET') {
        $yearId = (int) ($_GET['academicYearId'] ?? 0);
        $periodId = (int) ($_GET['studyPeriodId'] ?? 0);
        $resolved = sa_resolve_year_period($dbh, $school, $yearId, $periodId);
        $yearId = $resolved['year_id'];
        $periodId = $resolved['period_id'];
        [$date, $err] = sa_normalize_date((string) ($_GET['date'] ?? ''));
        if ($err) {
            erp_send(['error' => $err], 400);
        }

        $st = $dbh->prepare(
            'SELECT s.id AS student_id, s.first_name, s.last_name, s.admission_number,
                    c.name AS class_name, l.name AS level_name,
                    sar.id AS attendance_id, sar.check_in_time, sar.check_out_time, sar.remarks, sar.updated_at,
                    uu.firstname AS updated_firstname, uu.lastname AS updated_lastname
             FROM enrollments e
             INNER JOIN students s ON s.id = e.student_id AND s.school_number = e.school_number
             INNER JOIN classes c ON c.id = e.class_id AND c.school_number = e.school_number
             INNER JOIN levels l ON l.id = c.level_id AND l.school_number = c.school_number
             LEFT JOIN school_attendance_records sar ON sar.school_number = e.school_number
                AND sar.student_id = s.id
                AND sar.academic_year_id = e.academic_year_id
                AND sar.study_period_id = e.study_period_id
                AND sar.attendance_date = ?
             LEFT JOIN users uu ON uu.id = sar.updated_by_user_id
             WHERE e.school_number = ? AND e.academic_year_id = ? AND e.study_period_id = ?
             ORDER BY l.sort_order ASC, c.sort_order ASC, s.last_name ASC, s.first_name ASC, s.id ASC'
        );
        $st->execute([$date, $school, $yearId, $periodId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['student_id'] = (int) $r['student_id'];
            $r['attendance_id'] = $r['attendance_id'] !== null ? (int) $r['attendance_id'] : null;
            $r['last_saved_by'] = $r['updated_firstname'] !== null
                ? trim((string) $r['updated_firstname'] . ' ' . (string) $r['updated_lastname'])
                : null;
            unset($r['updated_firstname'], $r['updated_lastname']);
        }
        unset($r);

        erp_send(['data' => [
            'date' => $date,
            'academic_year_id' => $yearId,
            'study_period_id' => $periodId,
            'students' => $rows,
        ]]);
    }

    if ($method === 'POST') {
        $yearId = (int) ($body['academicYearId'] ?? 0);
        $periodId = (int) ($body['studyPeriodId'] ?? 0);
        $resolved = sa_resolve_year_period($dbh, $school, $yearId, $periodId);
        $yearId = $resolved['year_id'];
        $periodId = $resolved['period_id'];
        [$date, $err] = sa_normalize_date((string) ($body['date'] ?? ''));
        if ($err) {
            erp_send(['error' => $err], 400);
        }
        $records = isset($body['records']) && is_array($body['records']) ? $body['records'] : [];
        if (!$records) {
            erp_send(['error' => 'records is required'], 400);
        }

        $uid = erp_current_user_id();
        $up = $dbh->prepare(
            'INSERT INTO school_attendance_records
             (school_number, academic_year_id, study_period_id, attendance_date, student_id, check_in_time, check_out_time, remarks, created_by_user_id, updated_by_user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
               check_in_time = VALUES(check_in_time),
               check_out_time = VALUES(check_out_time),
               remarks = VALUES(remarks),
               updated_by_user_id = VALUES(updated_by_user_id),
               updated_at = CURRENT_TIMESTAMP'
        );

        $dbh->beginTransaction();
        try {
            $saved = 0;
            foreach ($records as $rec) {
                if (!is_array($rec)) {
                    continue;
                }
                $studentId = (int) ($rec['studentId'] ?? $rec['student_id'] ?? 0);
                if ($studentId < 1) {
                    continue;
                }
                if (!erp_student_owned($dbh, $school, $studentId)) {
                    $dbh->rollBack();
                    erp_send(['error' => 'Invalid student: ' . $studentId], 400);
                }

                $en = erp_row_school(
                    $dbh,
                    'SELECT id FROM enrollments WHERE school_number = ? AND student_id = ? AND academic_year_id = ? AND study_period_id = ? LIMIT 1',
                    [$school, $studentId, $yearId, $periodId]
                );
                if (!$en) {
                    $dbh->rollBack();
                    erp_send(['error' => 'Student not enrolled for active year/period: ' . $studentId], 400);
                }

                $in = trim((string) ($rec['checkInTime'] ?? $rec['check_in_time'] ?? ''));
                $out = trim((string) ($rec['checkOutTime'] ?? $rec['check_out_time'] ?? ''));
                if (!sa_valid_time($in) || !sa_valid_time($out)) {
                    $dbh->rollBack();
                    erp_send(['error' => 'Invalid check-in/check-out time format for student ' . $studentId], 400);
                }
                $inSql = $in === '' ? null : (strlen($in) === 5 ? $in . ':00' : $in);
                $outSql = $out === '' ? null : (strlen($out) === 5 ? $out . ':00' : $out);
                $remarks = trim((string) ($rec['remarks'] ?? ''));
                $remarksSql = $remarks === '' ? null : mb_substr($remarks, 0, 500);

                $up->execute([$school, $yearId, $periodId, $date, $studentId, $inSql, $outSql, $remarksSql, $uid, $uid]);
                $saved++;
            }
            $dbh->commit();
            erp_send(['message' => 'Saved', 'data' => ['saved' => $saved, 'date' => $date]]);
        } catch (Throwable $e) {
            if ($dbh->inTransaction()) {
                $dbh->rollBack();
            }
            throw $e;
        }
    }

    erp_send(['error' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    erp_send(['error' => $e->getMessage()], 500);
}

