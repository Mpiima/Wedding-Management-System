<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);
$body = erp_json_body();

function erp_parse_exam_time(string $s): ?string
{
    $s = trim($s);
    if ($s === '') {
        return null;
    }
    if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $s, $m)) {
        $h = (int) $m[1];
        $min = (int) $m[2];
        $sec = isset($m[3]) ? (int) $m[3] : 0;
        if ($h >= 0 && $h <= 23 && $min >= 0 && $min <= 59 && $sec >= 0 && $sec <= 59) {
            return sprintf('%02d:%02d:%02d', $h, $min, $sec);
        }
    }
    return null;
}

function erp_exam_schedule_row(PDO $dbh, string $school, int $id): ?array
{
    return erp_row_school(
        $dbh,
        'SELECT es.id, es.exam_type_id, es.class_id, es.exam_date, es.exam_time, es.exam_room, es.exam_requirements,
                et.name AS exam_type_name, et.max_score,
                et.academic_year_id, et.study_period_id,
                ay.name AS academic_year_name, sp.name AS study_period_name,
                c.name AS class_name, l.name AS level_name
         FROM exam_schedules es
         INNER JOIN exam_types et ON et.id = es.exam_type_id AND et.school_number = es.school_number
         INNER JOIN academic_years ay ON ay.id = et.academic_year_id AND ay.school_number = es.school_number
         INNER JOIN study_periods sp ON sp.id = et.study_period_id AND sp.school_number = es.school_number
         INNER JOIN classes c ON c.id = es.class_id AND c.school_number = es.school_number
         INNER JOIN levels l ON l.id = c.level_id AND l.school_number = es.school_number
         WHERE es.id = ? AND es.school_number = ? LIMIT 1',
        [$id, $school]
    );
}

function erp_load_schedule_subjects(PDO $dbh, int $scheduleId): array
{
    $st = $dbh->prepare(
        'SELECT s.id, s.name FROM subjects s
         INNER JOIN exam_schedule_subjects ess ON ess.subject_id = s.id
         WHERE ess.exam_schedule_id = ?
         ORDER BY s.sort_order, s.name'
    );
    $st->execute([$scheduleId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

function erp_load_schedule_supervisors(PDO $dbh, int $scheduleId): array
{
    $st = $dbh->prepare(
        'SELECT u.id, u.firstname, u.lastname, u.email, u.username FROM users u
         INNER JOIN exam_schedule_supervisors esu ON esu.user_id = u.id
         WHERE esu.exam_schedule_id = ?
         ORDER BY u.firstname, u.lastname'
    );
    $st->execute([$scheduleId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $fn = trim((string) ($r['firstname'] ?? ''));
        $ln = trim((string) ($r['lastname'] ?? ''));
        $r['display_name'] = trim($fn . ' ' . $ln) !== '' ? trim($fn . ' ' . $ln) : (string) ($r['username'] ?? $r['email']);
    }
    unset($r);
    return $rows;
}

try {
    if ($method === 'GET') {
        $oneId = (int) ($_GET['id'] ?? 0);
        if ($oneId > 0) {
            if (!erp_exam_schedule_owned($dbh, $school, $oneId)) {
                erp_send(['error' => 'Not found'], 404);
            }
            $row = erp_exam_schedule_row($dbh, $school, $oneId);
            if (!$row) {
                erp_send(['error' => 'Not found'], 404);
            }
            $row['max_score'] = (string) $row['max_score'];
            $row['exam_time'] = substr((string) $row['exam_time'], 0, 8);
            $subs = erp_load_schedule_subjects($dbh, $oneId);
            $sups = erp_load_schedule_supervisors($dbh, $oneId);
            $row['subjects'] = $subs;
            $row['subject_ids'] = array_map(static fn ($s) => (int) $s['id'], $subs);
            $row['supervisors'] = $sups;
            $row['supervisor_user_ids'] = array_map(static fn ($u) => (int) $u['id'], $sups);
            erp_send(['data' => $row]);
        }

        $yearId = (int) ($_GET['academicYearId'] ?? 0);
        $periodId = (int) ($_GET['studyPeriodId'] ?? 0);
        if ($yearId < 1 || $periodId < 1) {
            erp_send(['error' => 'academicYearId and studyPeriodId are required (or use id= for one row)'], 400);
        }
        if (!erp_year_owned($dbh, $school, $yearId)) {
            erp_send(['error' => 'Academic year not found'], 404);
        }
        $per = erp_period_owned($dbh, $school, $periodId);
        if (!$per || (int) $per['academic_year_id'] !== $yearId) {
            erp_send(['error' => 'Study period not found'], 404);
        }

        $st = $dbh->prepare(
            'SELECT es.id, es.exam_type_id, es.class_id, es.exam_date, es.exam_time, es.exam_room, es.exam_requirements,
                    et.name AS exam_type_name, et.max_score,
                    ay.name AS academic_year_name, sp.name AS study_period_name,
                    c.name AS class_name, l.name AS level_name
             FROM exam_schedules es
             INNER JOIN exam_types et ON et.id = es.exam_type_id AND et.school_number = es.school_number
             INNER JOIN academic_years ay ON ay.id = et.academic_year_id
             INNER JOIN study_periods sp ON sp.id = et.study_period_id
             INNER JOIN classes c ON c.id = es.class_id
             INNER JOIN levels l ON l.id = c.level_id
             WHERE es.school_number = ? AND et.academic_year_id = ? AND et.study_period_id = ?
             ORDER BY es.exam_date ASC, es.exam_time ASC, es.id ASC'
        );
        $st->execute([$school, $yearId, $periodId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['max_score'] = (string) $r['max_score'];
            $r['exam_time'] = substr((string) $r['exam_time'], 0, 8);
            $sid = (int) $r['id'];
            $subs = erp_load_schedule_subjects($dbh, $sid);
            $sups = erp_load_schedule_supervisors($dbh, $sid);
            $r['subject_names'] = implode(', ', array_column($subs, 'name'));
            $r['supervisor_names'] = implode(', ', array_column($sups, 'display_name'));
            $r['subjects'] = $subs;
            $r['supervisors'] = $sups;
            $r['subject_ids'] = array_map(static fn ($s) => (int) $s['id'], $subs);
            $r['supervisor_user_ids'] = array_map(static fn ($u) => (int) $u['id'], $sups);
        }
        unset($r);
        erp_send(['data' => $rows]);
    }

    if ($method === 'POST' || $method === 'PUT') {
        $examTypeId = (int) ($body['examTypeId'] ?? 0);
        $classId = (int) ($body['classId'] ?? 0);
        $examDate = trim((string) ($body['examDate'] ?? ''));
        $examTimeRaw = (string) ($body['examTime'] ?? '');
        $examRoom = isset($body['examRoom']) ? trim((string) $body['examRoom']) : '';
        $examRequirements = isset($body['examRequirements']) ? trim((string) $body['examRequirements']) : '';
        $subjectIds = isset($body['subjectIds']) && is_array($body['subjectIds']) ? $body['subjectIds'] : [];
        $supervisorIds = isset($body['supervisorUserIds']) && is_array($body['supervisorUserIds']) ? $body['supervisorUserIds'] : [];

        if ($examTypeId < 1 || !erp_exam_type_owned($dbh, $school, $examTypeId)) {
            erp_send(['error' => 'Invalid exam type'], 400);
        }
        if ($classId < 1 || !erp_class_owned($dbh, $school, $classId)) {
            erp_send(['error' => 'Invalid class'], 400);
        }
        if ($examDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $examDate)) {
            erp_send(['error' => 'examDate must be YYYY-MM-DD'], 400);
        }
        $examTime = erp_parse_exam_time($examTimeRaw);
        if ($examTime === null) {
            erp_send(['error' => 'examTime is required (HH:MM or HH:MM:SS)'], 400);
        }

        $normSubjectIds = [];
        foreach ($subjectIds as $sid) {
            $sid = (int) $sid;
            if ($sid < 1) {
                continue;
            }
            if (!erp_subject_owned($dbh, $school, $sid) || !erp_subject_in_class($dbh, $classId, $sid)) {
                erp_send(['error' => 'Each subject must be assigned to the selected class'], 400);
            }
            $normSubjectIds[$sid] = $sid;
        }
        if (count($normSubjectIds) < 1) {
            erp_send(['error' => 'Select at least one subject for this class'], 400);
        }

        $normSupIds = [];
        foreach ($supervisorIds as $uid) {
            $uid = (int) $uid;
            if ($uid < 1) {
                continue;
            }
            if (!erp_school_supervisor_user($dbh, $school, $uid)) {
                erp_send(['error' => 'Invalid supervisor user'], 400);
            }
            $normSupIds[$uid] = $uid;
        }

        $roomSql = $examRoom === '' ? null : mb_substr($examRoom, 0, 255);
        $reqSql = $examRequirements === '' ? null : $examRequirements;

        if ($method === 'POST') {
            $dbh->beginTransaction();
            try {
                $ins = $dbh->prepare(
                    'INSERT INTO exam_schedules (school_number, exam_type_id, class_id, exam_date, exam_time, exam_room, exam_requirements)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $ins->execute([$school, $examTypeId, $classId, $examDate, $examTime, $roomSql, $reqSql]);
                $newId = (int) $dbh->lastInsertId();

                $insSub = $dbh->prepare('INSERT INTO exam_schedule_subjects (exam_schedule_id, subject_id) VALUES (?, ?)');
                foreach ($normSubjectIds as $sid) {
                    $insSub->execute([$newId, $sid]);
                }
                $insSup = $dbh->prepare('INSERT INTO exam_schedule_supervisors (exam_schedule_id, user_id) VALUES (?, ?)');
                foreach ($normSupIds as $uid) {
                    $insSup->execute([$newId, $uid]);
                }
                $dbh->commit();
            } catch (Throwable $e) {
                $dbh->rollBack();
                throw $e;
            }
            erp_send(['message' => 'Exam schedule created', 'data' => ['id' => $newId]], 201);
        }

        if ($method === 'PUT') {
            $id = (int) ($body['id'] ?? 0);
            if ($id < 1 || !erp_exam_schedule_owned($dbh, $school, $id)) {
                erp_send(['error' => 'Not found'], 404);
            }
            $dbh->beginTransaction();
            try {
                $dbh->prepare(
                    'UPDATE exam_schedules SET exam_type_id = ?, class_id = ?, exam_date = ?, exam_time = ?, exam_room = ?, exam_requirements = ?
                     WHERE id = ? AND school_number = ?'
                )->execute([$examTypeId, $classId, $examDate, $examTime, $roomSql, $reqSql, $id, $school]);

                $dbh->prepare('DELETE FROM exam_schedule_subjects WHERE exam_schedule_id = ?')->execute([$id]);
                $dbh->prepare('DELETE FROM exam_schedule_supervisors WHERE exam_schedule_id = ?')->execute([$id]);

                $insSub = $dbh->prepare('INSERT INTO exam_schedule_subjects (exam_schedule_id, subject_id) VALUES (?, ?)');
                foreach ($normSubjectIds as $sid) {
                    $insSub->execute([$id, $sid]);
                }
                $insSup = $dbh->prepare('INSERT INTO exam_schedule_supervisors (exam_schedule_id, user_id) VALUES (?, ?)');
                foreach ($normSupIds as $uid) {
                    $insSup->execute([$id, $uid]);
                }
                $dbh->commit();
            } catch (Throwable $e) {
                $dbh->rollBack();
                throw $e;
            }
            erp_send(['message' => 'Exam schedule updated', 'data' => ['id' => $id]]);
        }
    }

    if ($method === 'DELETE') {
        $id = (int) ($_GET['id'] ?? $body['id'] ?? 0);
        if ($id < 1 || !erp_exam_schedule_owned($dbh, $school, $id)) {
            erp_send(['error' => 'Not found'], 404);
        }
        $dbh->prepare('DELETE FROM exam_schedules WHERE id = ? AND school_number = ?')->execute([$id, $school]);
        erp_send(['message' => 'Deleted']);
    }

    erp_send(['message' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
