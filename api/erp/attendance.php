<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);
$body = erp_json_body();

const ATT_STATUSES = ['present', 'absent', 'late', 'half_day', 'excused'];

/**
 * @return array{0:string,1:?string} [Y-m-d, error message or null]
 */
function att_normalize_date(string $raw): array
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

function att_status_ok(string $s): bool
{
    return in_array($s, ATT_STATUSES, true);
}

/**
 * Resolve missing year/period to active context defaults.
 *
 * @return array{year_id:int, period_id:int}
 */
function att_resolve_year_period(PDO $dbh, string $school, int $yearId, int $periodId): array
{
    if ($yearId < 1) {
        $yr = erp_row_school(
            $dbh,
            'SELECT id FROM academic_years WHERE school_number = ? AND is_active = 1 LIMIT 1',
            [$school]
        );
        if (!$yr) {
            erp_send(['error' => 'No active academic year found. Set one under Academic setup.'], 400);
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
            erp_send(['error' => 'No study periods found for the selected/active academic year.'], 400);
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
        erp_send(['error' => 'Study period does not belong to the selected academic year'], 400);
    }

    return ['year_id' => $yearId, 'period_id' => $periodId];
}

/**
 * Enrollment count for class (+ optional stream) in year/period.
 */
function att_enrollment_count(
    PDO $dbh,
    string $school,
    int $yearId,
    int $periodId,
    int $classId,
    ?int $streamId
): int {
    if ($streamId !== null && $streamId > 0) {
        $st = $dbh->prepare(
            'SELECT COUNT(*) FROM enrollments
             WHERE school_number = ? AND academic_year_id = ? AND study_period_id = ? AND class_id = ?
             AND stream_id = ?'
        );
        $st->execute([$school, $yearId, $periodId, $classId, $streamId]);
    } else {
        $st = $dbh->prepare(
            'SELECT COUNT(*) FROM enrollments
             WHERE school_number = ? AND academic_year_id = ? AND study_period_id = ? AND class_id = ?'
        );
        $st->execute([$school, $yearId, $periodId, $classId]);
    }
    return (int) $st->fetchColumn();
}

try {
    $action = (string) ($_GET['action'] ?? $body['action'] ?? ($method === 'POST' ? 'save' : 'session'));
    if ($action === '') {
        $action = $method === 'POST' ? 'save' : 'session';
    }

    if ($method === 'GET' && $action === 'session') {
        $yearId = (int) ($_GET['academicYearId'] ?? 0);
        $periodId = (int) ($_GET['studyPeriodId'] ?? 0);
        $resolved = att_resolve_year_period($dbh, $school, $yearId, $periodId);
        $yearId = $resolved['year_id'];
        $periodId = $resolved['period_id'];
        $classId = (int) ($_GET['classId'] ?? 0);
        $streamRaw = (int) ($_GET['streamId'] ?? 0);
        $streamId = $streamRaw > 0 ? $streamRaw : null;
        [$date, $err] = att_normalize_date((string) ($_GET['date'] ?? ''));
        if ($err) {
            erp_send(['error' => $err], 400);
        }
        if ($classId < 1) {
            erp_send(['error' => 'classId is required'], 400);
        }
        if (!erp_class_owned($dbh, $school, $classId)) {
            erp_send(['error' => 'Class not found'], 404);
        }
        if ($streamId !== null && !erp_stream_owned($dbh, $school, $streamId)) {
            erp_send(['error' => 'Stream not found'], 404);
        }

        $streamClause = '';
        $params = [$date, $school, $yearId, $periodId, $classId];
        if ($streamId !== null) {
            $streamClause = ' AND e.stream_id = ? ';
            $params[] = $streamId;
        }

        $sql = 'SELECT s.id AS student_id, s.first_name, s.last_name, s.admission_number,
                       e.stream_id AS enrollment_stream_id,
                       ar.id AS attendance_id, ar.status, ar.remarks,
                       ar.created_at, ar.updated_at,
                       uc.firstname AS updated_by_firstname, uc.lastname AS updated_by_lastname,
                       uu.firstname AS last_saved_by_firstname, uu.lastname AS last_saved_by_lastname
                FROM enrollments e
                INNER JOIN students s ON s.id = e.student_id AND s.school_number = e.school_number
                LEFT JOIN attendance_records ar ON ar.student_id = s.id AND ar.school_number = e.school_number
                  AND ar.academic_year_id = e.academic_year_id AND ar.study_period_id = e.study_period_id
                  AND ar.class_id = e.class_id AND ar.attendance_date = ?
                LEFT JOIN users uu ON uu.id = ar.updated_by_user_id
                LEFT JOIN users uc ON uc.id = ar.created_by_user_id
                WHERE e.school_number = ? AND e.academic_year_id = ? AND e.study_period_id = ? AND e.class_id = ?'
            . $streamClause
            . ' ORDER BY s.last_name ASC, s.first_name ASC, s.id ASC';

        $st = $dbh->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        $out = [];
        foreach ($rows as $row) {
            $sid = (int) $row['student_id'];
            $hasRow = $row['attendance_id'] !== null;
            $status = $hasRow ? (string) $row['status'] : 'present';
            $out[] = [
                'student_id' => $sid,
                'first_name' => (string) $row['first_name'],
                'last_name' => (string) $row['last_name'],
                'admission_number' => (string) ($row['admission_number'] ?? ''),
                'enrollment_stream_id' => $row['enrollment_stream_id'] !== null ? (int) $row['enrollment_stream_id'] : null,
                'attendance_id' => $hasRow ? (int) $row['attendance_id'] : null,
                'status' => $status,
                'remarks' => $row['remarks'] !== null ? (string) $row['remarks'] : '',
                'saved' => $hasRow,
                'created_at' => $row['created_at'] ?? null,
                'updated_at' => $row['updated_at'] ?? null,
                'last_saved_by' => $row['last_saved_by_firstname'] !== null
                    ? trim((string) $row['last_saved_by_firstname'] . ' ' . (string) $row['last_saved_by_lastname'])
                    : null,
            ];
        }

        erp_send([
            'data' => [
                'date' => $date,
                'academic_year_id' => $yearId,
                'study_period_id' => $periodId,
                'class_id' => $classId,
                'stream_id' => $streamId,
                'students' => $out,
            ],
        ]);
    }

    if ($method === 'GET' && $action === 'history') {
        $yearId = (int) ($_GET['academicYearId'] ?? 0);
        $periodId = (int) ($_GET['studyPeriodId'] ?? 0);
        $resolved = att_resolve_year_period($dbh, $school, $yearId, $periodId);
        $yearId = $resolved['year_id'];
        $periodId = $resolved['period_id'];
        $classId = (int) ($_GET['classId'] ?? 0);
        $streamRaw = (int) ($_GET['streamId'] ?? 0);
        $streamId = $streamRaw > 0 ? $streamRaw : null;
        $from = trim((string) ($_GET['dateFrom'] ?? ''));
        $to = trim((string) ($_GET['dateTo'] ?? ''));
        [$dFrom, $e1] = att_normalize_date($from !== '' ? $from : date('Y-m-d'));
        [$dTo, $e2] = att_normalize_date($to !== '' ? $to : date('Y-m-d'));
        if ($e1 || $e2) {
            erp_send(['error' => $e1 ?? $e2], 400);
        }
        if ($dFrom > $dTo) {
            $tmp = $dFrom;
            $dFrom = $dTo;
            $dTo = $tmp;
        }
        if ($classId < 1) {
            erp_send(['error' => 'classId is required'], 400);
        }
        if (!erp_class_owned($dbh, $school, $classId)) {
            erp_send(['error' => 'Class not found'], 404);
        }

        $totalEnrolled = att_enrollment_count($dbh, $school, $yearId, $periodId, $classId, $streamId);

        $st = $dbh->prepare(
            'SELECT attendance_date,
                    SUM(CASE WHEN status = \'present\' THEN 1 ELSE 0 END) AS present_count,
                    SUM(CASE WHEN status = \'absent\' THEN 1 ELSE 0 END) AS absent_count,
                    SUM(CASE WHEN status = \'late\' THEN 1 ELSE 0 END) AS late_count,
                    SUM(CASE WHEN status = \'half_day\' THEN 1 ELSE 0 END) AS half_day_count,
                    SUM(CASE WHEN status = \'excused\' THEN 1 ELSE 0 END) AS excused_count,
                    COUNT(*) AS recorded_count
             FROM attendance_records
             WHERE school_number = ? AND academic_year_id = ? AND study_period_id = ?
               AND class_id = ? AND attendance_date BETWEEN ? AND ?'
            . ($streamId !== null ? ' AND stream_id = ? ' : '')
            . ' GROUP BY attendance_date ORDER BY attendance_date ASC'
        );
        $bind = [$school, $yearId, $periodId, $classId, $dFrom, $dTo];
        if ($streamId !== null) {
            $bind[] = $streamId;
        }
        $st->execute($bind);
        $agg = $st->fetchAll(PDO::FETCH_ASSOC);

        $history = [];
        foreach ($agg as $r) {
            $present = (int) $r['present_count'];
            $absent = (int) $r['absent_count'];
            $late = (int) $r['late_count'];
            $half = (int) $r['half_day_count'];
            $excused = (int) $r['excused_count'];
            $recorded = (int) $r['recorded_count'];
            $attended = $present + $late + $half + $excused;
            $den = max(1, $totalEnrolled);
            $rate = round(100.0 * $attended / $den, 1);
            $history[] = [
                'date' => (string) $r['attendance_date'],
                'total_enrolled' => $totalEnrolled,
                'recorded_count' => $recorded,
                'present_count' => $present,
                'absent_count' => $absent,
                'late_count' => $late,
                'half_day_count' => $half,
                'excused_count' => $excused,
                'attendance_rate_pct' => $rate,
            ];
        }

        erp_send([
            'data' => [
                'date_from' => $dFrom,
                'date_to' => $dTo,
                'class_id' => $classId,
                'stream_id' => $streamId,
                'rows' => $history,
            ],
        ]);
    }

    if ($method === 'GET' && $action === 'student_profile') {
        $yearId = (int) ($_GET['academicYearId'] ?? 0);
        $periodId = (int) ($_GET['studyPeriodId'] ?? 0);
        $resolved = att_resolve_year_period($dbh, $school, $yearId, $periodId);
        $yearId = $resolved['year_id'];
        $periodId = $resolved['period_id'];
        $studentId = (int) ($_GET['studentId'] ?? 0);
        $from = trim((string) ($_GET['dateFrom'] ?? ''));
        $to = trim((string) ($_GET['dateTo'] ?? ''));
        [$dFrom, $e1] = att_normalize_date($from !== '' ? $from : '1970-01-01');
        [$dTo, $e2] = att_normalize_date($to !== '' ? $to : date('Y-m-d'));
        if ($e1 || $e2) {
            erp_send(['error' => $e1 ?? $e2], 400);
        }
        if ($studentId < 1) {
            erp_send(['error' => 'studentId is required'], 400);
        }
        if (!erp_student_owned($dbh, $school, $studentId)) {
            erp_send(['error' => 'Student not found'], 404);
        }

        $st = $dbh->prepare(
            'SELECT status, COUNT(*) AS c FROM attendance_records
             WHERE school_number = ? AND academic_year_id = ? AND study_period_id = ?
               AND student_id = ? AND attendance_date BETWEEN ? AND ?
             GROUP BY status'
        );
        $st->execute([$school, $yearId, $periodId, $studentId, $dFrom, $dTo]);
        $counts = ['present' => 0, 'absent' => 0, 'late' => 0, 'half_day' => 0, 'excused' => 0];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $k = (string) $r['status'];
            if (isset($counts[$k])) {
                $counts[$k] = (int) $r['c'];
            }
        }
        $totalMarked = array_sum($counts);
        $attended = $counts['present'] + $counts['late'] + $counts['half_day'] + $counts['excused'];
        $pct = $totalMarked > 0 ? round(100.0 * $attended / $totalMarked, 1) : null;

        $stu = erp_row_school(
            $dbh,
            'SELECT first_name, last_name, admission_number FROM students WHERE id = ? AND school_number = ? LIMIT 1',
            [$studentId, $school]
        );

        erp_send([
            'data' => [
                'student' => [
                    'id' => $studentId,
                    'first_name' => $stu ? (string) $stu['first_name'] : '',
                    'last_name' => $stu ? (string) $stu['last_name'] : '',
                    'admission_number' => $stu ? (string) ($stu['admission_number'] ?? '') : '',
                ],
                'date_from' => $dFrom,
                'date_to' => $dTo,
                'counts' => $counts,
                'total_days_marked' => $totalMarked,
                'attendance_percentage' => $pct,
            ],
        ]);
    }

    if ($method === 'GET' && $action === 'dashboard') {
        $yearId = (int) ($_GET['academicYearId'] ?? 0);
        $periodId = (int) ($_GET['studyPeriodId'] ?? 0);
        $resolved = att_resolve_year_period($dbh, $school, $yearId, $periodId);
        $yearId = $resolved['year_id'];
        $periodId = $resolved['period_id'];
        [$date, $err] = att_normalize_date((string) ($_GET['date'] ?? ''));
        if ($err) {
            erp_send(['error' => $err], 400);
        }
        $st = $dbh->prepare(
            'SELECT
                COUNT(*) AS total_rows,
                SUM(CASE WHEN status = \'present\' THEN 1 ELSE 0 END) AS present_count,
                SUM(CASE WHEN status = \'absent\' THEN 1 ELSE 0 END) AS absent_count,
                SUM(CASE WHEN status = \'late\' THEN 1 ELSE 0 END) AS late_count,
                SUM(CASE WHEN status = \'half_day\' THEN 1 ELSE 0 END) AS half_day_count,
                SUM(CASE WHEN status = \'excused\' THEN 1 ELSE 0 END) AS excused_count
             FROM attendance_records
             WHERE school_number = ? AND academic_year_id = ? AND study_period_id = ?
               AND attendance_date = ?'
        );
        $st->execute([$school, $yearId, $periodId, $date]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        $totalRows = (int) ($row['total_rows'] ?? 0);
        $present = (int) ($row['present_count'] ?? 0);
        $absent = (int) ($row['absent_count'] ?? 0);
        $late = (int) ($row['late_count'] ?? 0);
        $half = (int) ($row['half_day_count'] ?? 0);
        $excused = (int) ($row['excused_count'] ?? 0);
        $attended = $present + $late + $half + $excused;
        $rate = $totalRows > 0 ? round(100.0 * $attended / $totalRows, 1) : null;

        erp_send([
            'data' => [
                'date' => $date,
                'total_records' => $totalRows,
                'present' => $present,
                'absent' => $absent,
                'late' => $late,
                'half_day' => $half,
                'excused' => $excused,
                'attendance_rate_pct' => $rate,
            ],
        ]);
    }

    if ($method === 'GET' && $action === 'insights') {
        $yearId = (int) ($_GET['academicYearId'] ?? 0);
        $periodId = (int) ($_GET['studyPeriodId'] ?? 0);
        $resolved = att_resolve_year_period($dbh, $school, $yearId, $periodId);
        $yearId = $resolved['year_id'];
        $periodId = $resolved['period_id'];
        $lookback = (int) ($_GET['lookbackDays'] ?? 28);
        if ($lookback < 7) {
            $lookback = 7;
        }
        if ($lookback > 120) {
            $lookback = 120;
        }
        $since = (new DateTimeImmutable('today'))->modify('-' . $lookback . ' days')->format('Y-m-d');

        $st = $dbh->prepare(
            'SELECT ar.student_id, s.first_name, s.last_name, s.admission_number,
                    COUNT(*) AS absent_days
             FROM attendance_records ar
             INNER JOIN students s ON s.id = ar.student_id AND s.school_number = ar.school_number
             WHERE ar.school_number = ? AND ar.academic_year_id = ? AND ar.study_period_id = ?
               AND ar.status = \'absent\'
               AND ar.attendance_date >= ?
             GROUP BY ar.student_id, s.first_name, s.last_name, s.admission_number
             HAVING absent_days >= 3
             ORDER BY absent_days DESC
             LIMIT 50'
        );
        $st->execute([$school, $yearId, $periodId, $since]);
        $frequent = $st->fetchAll(PDO::FETCH_ASSOC);

        $st2 = $dbh->prepare(
            'SELECT ar.student_id, ar.attendance_date, ar.class_id, c.name AS class_name
             FROM attendance_records ar
             INNER JOIN classes c ON c.id = ar.class_id AND c.school_number = ar.school_number
             WHERE ar.school_number = ? AND ar.academic_year_id = ? AND ar.study_period_id = ?
               AND ar.status = \'absent\' AND ar.attendance_date >= ?
             ORDER BY ar.student_id ASC, ar.attendance_date ASC'
        );
        $st2->execute([$school, $yearId, $periodId, $since]);
        $absRows = $st2->fetchAll(PDO::FETCH_ASSOC);

        $byStudent = [];
        foreach ($absRows as $r) {
            $sid = (int) $r['student_id'];
            if (!isset($byStudent[$sid])) {
                $byStudent[$sid] = [];
            }
            $dstr = (string) $r['attendance_date'];
            $byStudent[$sid][$dstr] = true;
        }

        $consecutive = [];
        foreach ($byStudent as $sid => $dateMap) {
            $dates = array_keys($dateMap);
            sort($dates);
            $best = 0;
            $cur = 0;
            $prevTs = null;
            foreach ($dates as $dstr) {
                $ts = strtotime($dstr);
                if ($ts === false) {
                    continue;
                }
                if ($prevTs === null) {
                    $cur = 1;
                } else {
                    $cur = (($ts - $prevTs) === 86400) ? $cur + 1 : 1;
                }
                $prevTs = $ts;
                if ($cur > $best) {
                    $best = $cur;
                }
            }
            if ($best >= 3) {
                $consecutive[] = [
                    'student_id' => $sid,
                    'consecutive_absent_days' => $best,
                ];
            }
        }

        $mondayHints = [];
        $st3 = $dbh->prepare(
            'SELECT ar.student_id,
                    SUM(CASE WHEN DAYOFWEEK(ar.attendance_date) = 2 THEN 1 ELSE 0 END) AS mon_abs,
                    COUNT(*) AS total_abs
             FROM attendance_records ar
             WHERE ar.school_number = ? AND ar.academic_year_id = ? AND ar.study_period_id = ?
               AND ar.status = \'absent\' AND ar.attendance_date >= ?
             GROUP BY ar.student_id
             HAVING mon_abs >= 2 AND total_abs >= 3'
        );
        $st3->execute([$school, $yearId, $periodId, $since]);
        foreach ($st3->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $mondayHints[] = [
                'student_id' => (int) $r['student_id'],
                'pattern' => 'Often absent on Mondays (last ' . $lookback . ' days)',
            ];
        }

        erp_send([
            'data' => [
                'lookback_days' => $lookback,
                'frequent_absences' => array_map(static function (array $r): array {
                    return [
                        'student_id' => (int) $r['student_id'],
                        'first_name' => (string) $r['first_name'],
                        'last_name' => (string) $r['last_name'],
                        'admission_number' => (string) ($r['admission_number'] ?? ''),
                        'absent_days' => (int) $r['absent_days'],
                    ];
                }, $frequent),
                'consecutive_absences' => $consecutive,
                'patterns' => $mondayHints,
            ],
        ]);
    }

    if ($method === 'POST' && ($action === 'save' || $action === 'session')) {
        $yearId = (int) ($body['academicYearId'] ?? 0);
        $periodId = (int) ($body['studyPeriodId'] ?? 0);
        $resolved = att_resolve_year_period($dbh, $school, $yearId, $periodId);
        $yearId = $resolved['year_id'];
        $periodId = $resolved['period_id'];
        $classId = (int) ($body['classId'] ?? 0);
        $streamRaw = (int) ($body['streamId'] ?? 0);
        $streamFilter = $streamRaw > 0 ? $streamRaw : null;
        [$date, $err] = att_normalize_date((string) ($body['date'] ?? ''));
        if ($err) {
            erp_send(['error' => $err], 400);
        }
        $records = $body['records'] ?? null;
        if (!is_array($records)) {
            erp_send(['error' => 'records array is required'], 400);
        }
        if ($classId < 1) {
            erp_send(['error' => 'classId is required'], 400);
        }
        if (!erp_class_owned($dbh, $school, $classId)) {
            erp_send(['error' => 'Class not found'], 404);
        }
        if ($streamFilter !== null && !erp_stream_owned($dbh, $school, $streamFilter)) {
            erp_send(['error' => 'Stream not found'], 404);
        }

        $uid = erp_current_user_id();

        $ins = $dbh->prepare(
            'INSERT INTO attendance_records
            (school_number, academic_year_id, study_period_id, attendance_date, class_id, stream_id, student_id, status, remarks, created_by_user_id, updated_by_user_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE
              status = VALUES(status),
              remarks = VALUES(remarks),
              stream_id = VALUES(stream_id),
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
                $status = strtolower(trim((string) ($rec['status'] ?? 'present')));
                if (!att_status_ok($status)) {
                    $dbh->rollBack();
                    erp_send(['error' => 'Invalid status for student ' . $studentId], 400);
                }
                $remarks = trim((string) ($rec['remarks'] ?? ''));
                $remarksSql = $remarks === '' ? null : mb_substr($remarks, 0, 500);

                if (!erp_student_owned($dbh, $school, $studentId)) {
                    $dbh->rollBack();
                    erp_send(['error' => 'Student not found: ' . $studentId], 404);
                }
                if (!erp_student_enrolled_in_class($dbh, $school, $studentId, $yearId, $periodId, $classId)) {
                    $dbh->rollBack();
                    erp_send(['error' => 'Student ' . $studentId . ' is not enrolled in this class for the selected term'], 400);
                }

                $en = erp_row_school(
                    $dbh,
                    'SELECT stream_id FROM enrollments WHERE school_number = ? AND student_id = ? AND academic_year_id = ? AND study_period_id = ? AND class_id = ? LIMIT 1',
                    [$school, $studentId, $yearId, $periodId, $classId]
                );
                if (!$en) {
                    $dbh->rollBack();
                    erp_send(['error' => 'Enrollment not found'], 400);
                }
                $enStream = $en['stream_id'] !== null ? (int) $en['stream_id'] : null;
                if ($streamFilter !== null && $enStream !== $streamFilter) {
                    $dbh->rollBack();
                    erp_send(['error' => 'Stream filter does not match student enrollment'], 400);
                }
                $rowStream = $enStream;

                $ins->execute([
                    $school,
                    $yearId,
                    $periodId,
                    $date,
                    $classId,
                    $rowStream,
                    $studentId,
                    $status,
                    $remarksSql,
                    $uid,
                    $uid,
                ]);
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

    if ($method === 'POST' && $action === 'notify_absents') {
        erp_send([
            'message' => 'Notification dispatch is not configured yet. Wire SMS/email in school settings.',
            'data' => ['queued' => 0],
        ], 200);
    }

    erp_send(['error' => 'Method or action not supported'], 405);
} catch (Throwable $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
