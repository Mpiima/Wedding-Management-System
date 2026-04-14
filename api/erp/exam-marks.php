<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);
$body = erp_json_body();

/**
 * @return array{class_id:int, academic_year_id:int, study_period_id:int, max_score:float, exam_type_name:string, exam_date:string}|null
 */
function erp_exam_mark_schedule_meta(PDO $dbh, string $school, int $scheduleId): ?array
{
    $row = erp_row_school(
        $dbh,
        'SELECT es.class_id, et.academic_year_id, et.study_period_id, et.max_score, et.name AS exam_type_name, es.exam_date
         FROM exam_schedules es
         INNER JOIN exam_types et ON et.id = es.exam_type_id AND et.school_number = es.school_number
         WHERE es.id = ? AND es.school_number = ? LIMIT 1',
        [$scheduleId, $school]
    );
    if (!$row) {
        return null;
    }
    return [
        'class_id' => (int) $row['class_id'],
        'academic_year_id' => (int) $row['academic_year_id'],
        'study_period_id' => (int) $row['study_period_id'],
        'max_score' => (float) $row['max_score'],
        'exam_type_name' => (string) $row['exam_type_name'],
        'exam_date' => (string) $row['exam_date'],
    ];
}

function erp_subject_on_exam_schedule(PDO $dbh, int $scheduleId, int $subjectId): bool
{
    $st = $dbh->prepare(
        'SELECT 1 FROM exam_schedule_subjects WHERE exam_schedule_id = ? AND subject_id = ? LIMIT 1'
    );
    $st->execute([$scheduleId, $subjectId]);
    return (bool) $st->fetchColumn();
}

try {
    if ($method === 'GET') {
        $scheduleId = (int) ($_GET['examScheduleId'] ?? 0);
        $subjectId = (int) ($_GET['subjectId'] ?? 0);
        $yearId = (int) ($_GET['academicYearId'] ?? 0);
        $periodId = (int) ($_GET['studyPeriodId'] ?? 0);
        if ($scheduleId < 1 || $subjectId < 1 || $yearId < 1 || $periodId < 1) {
            erp_send(['error' => 'examScheduleId, subjectId, academicYearId, and studyPeriodId are required'], 400);
        }
        if (!erp_exam_schedule_owned($dbh, $school, $scheduleId)) {
            erp_send(['error' => 'Exam schedule not found'], 404);
        }
        if (!erp_subject_owned($dbh, $school, $subjectId)) {
            erp_send(['error' => 'Subject not found'], 404);
        }
        if (!erp_subject_on_exam_schedule($dbh, $scheduleId, $subjectId)) {
            erp_send(['error' => 'This subject is not on the selected exam schedule'], 400);
        }
        $meta = erp_exam_mark_schedule_meta($dbh, $school, $scheduleId);
        if (!$meta) {
            erp_send(['error' => 'Exam schedule not found'], 404);
        }
        if ($meta['academic_year_id'] !== $yearId || $meta['study_period_id'] !== $periodId) {
            erp_send(['error' => 'Selected year/period does not match this exam schedule'], 400);
        }

        $classId = $meta['class_id'];
        $maxScore = $meta['max_score'];

        $subRow = erp_row_school(
            $dbh,
            'SELECT name FROM subjects WHERE id = ? AND school_number = ? LIMIT 1',
            [$subjectId, $school]
        );
        $subjectName = $subRow ? (string) $subRow['name'] : '';

        $st = $dbh->prepare(
            'SELECT s.id AS student_id, s.first_name, s.last_name, s.admission_number,
                    m.id AS mark_id, m.score, m.teacher_comment
             FROM enrollments e
             INNER JOIN students s ON s.id = e.student_id AND s.school_number = e.school_number
             LEFT JOIN exam_schedule_marks m ON m.exam_schedule_id = ? AND m.student_id = s.id
                AND m.subject_id = ? AND m.school_number = e.school_number
             WHERE e.school_number = ? AND e.academic_year_id = ? AND e.study_period_id = ? AND e.class_id = ?
             ORDER BY s.last_name ASC, s.first_name ASC, s.id ASC'
        );
        $st->execute([$scheduleId, $subjectId, $school, $yearId, $periodId, $classId]);
        $students = $st->fetchAll(PDO::FETCH_ASSOC);
        foreach ($students as &$srow) {
            $srow['mark_id'] = $srow['mark_id'] !== null ? (int) $srow['mark_id'] : null;
            $srow['student_id'] = (int) $srow['student_id'];
            if ($srow['score'] !== null) {
                $srow['score'] = (string) $srow['score'];
            }
        }
        unset($srow);

        erp_send([
            'data' => [
                'max_score' => (string) $maxScore,
                'exam_type_name' => $meta['exam_type_name'],
                'exam_date' => $meta['exam_date'],
                'class_id' => $classId,
                'subject_id' => $subjectId,
                'subject_name' => $subjectName,
                'students' => $students,
            ],
        ]);
    }

    if ($method === 'POST') {
        $scheduleId = (int) ($body['examScheduleId'] ?? 0);
        $subjectId = (int) ($body['subjectId'] ?? 0);
        $studentId = (int) ($body['studentId'] ?? 0);
        if ($scheduleId < 1 || $subjectId < 1 || $studentId < 1) {
            erp_send(['error' => 'examScheduleId, subjectId, and studentId are required'], 400);
        }
        if (!erp_exam_schedule_owned($dbh, $school, $scheduleId)) {
            erp_send(['error' => 'Exam schedule not found'], 404);
        }
        if (!erp_student_owned($dbh, $school, $studentId)) {
            erp_send(['error' => 'Student not found'], 404);
        }
        if (!erp_subject_owned($dbh, $school, $subjectId)) {
            erp_send(['error' => 'Subject not found'], 404);
        }
        if (!erp_subject_on_exam_schedule($dbh, $scheduleId, $subjectId)) {
            erp_send(['error' => 'This subject is not on the selected exam schedule'], 400);
        }

        $meta = erp_exam_mark_schedule_meta($dbh, $school, $scheduleId);
        if (!$meta) {
            erp_send(['error' => 'Exam schedule not found'], 404);
        }

        $yearId = $meta['academic_year_id'];
        $periodId = $meta['study_period_id'];
        $classId = $meta['class_id'];
        $maxScore = $meta['max_score'];

        if (!erp_student_enrolled_in_class($dbh, $school, $studentId, $yearId, $periodId, $classId)) {
            erp_send(['error' => 'Student is not enrolled in this class for the schedule term'], 400);
        }

        $scoreRaw = $body['score'] ?? null;
        $scoreSql = null;
        if ($scoreRaw !== null && $scoreRaw !== '') {
            $scoreSql = (float) $scoreRaw;
            if ($scoreSql < 0 || $scoreSql > $maxScore + 0.0001) {
                erp_send(['error' => 'Score must be between 0 and ' . (string) $maxScore], 400);
            }
        }

        $comment = trim((string) ($body['teacherComment'] ?? ''));
        $commentSql = $comment === '' ? null : $comment;

        $exists = erp_row_school(
            $dbh,
            'SELECT id FROM exam_schedule_marks WHERE exam_schedule_id = ? AND student_id = ? AND subject_id = ? AND school_number = ? LIMIT 1',
            [$scheduleId, $studentId, $subjectId, $school]
        );

        if ($exists) {
            $dbh->prepare(
                'UPDATE exam_schedule_marks SET score = ?, teacher_comment = ? WHERE id = ? AND school_number = ?'
            )->execute([$scoreSql, $commentSql, (int) $exists['id'], $school]);
            $markId = (int) $exists['id'];
        } else {
            $dbh->prepare(
                'INSERT INTO exam_schedule_marks (school_number, exam_schedule_id, student_id, subject_id, score, teacher_comment)
                 VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([$school, $scheduleId, $studentId, $subjectId, $scoreSql, $commentSql]);
            $markId = (int) $dbh->lastInsertId();
        }

        erp_send(['message' => 'Saved', 'data' => ['mark_id' => $markId]]);
    }

    erp_send(['message' => 'Method not allowed'], 405);
} catch (Throwable $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
