<?php

declare(strict_types=1);

require_once __DIR__ . '/_init.php';

$school = erp_school_id($dbh);

if ($method !== 'GET') {
    erp_send(['message' => 'Method not allowed'], 405);
}

try {
    $studentId = (int) ($_GET['studentId'] ?? 0);
    $yearId = (int) ($_GET['academicYearId'] ?? 0);
    $periodId = (int) ($_GET['studyPeriodId'] ?? 0);
    if ($studentId < 1 || $yearId < 1 || $periodId < 1) {
        erp_send(['error' => 'studentId, academicYearId, and studyPeriodId are required'], 400);
    }
    if (!erp_student_owned($dbh, $school, $studentId)) {
        erp_send(['error' => 'Student not found'], 404);
    }
    if (!erp_year_owned($dbh, $school, $yearId)) {
        erp_send(['error' => 'Academic year not found'], 404);
    }
    $per = erp_period_owned($dbh, $school, $periodId);
    if (!$per || (int) $per['academic_year_id'] !== $yearId) {
        erp_send(['error' => 'Study period not found'], 404);
    }

    $enr = erp_row_school(
        $dbh,
        'SELECT e.class_id, s.first_name, s.last_name, s.admission_number,
                c.name AS class_name, l.name AS level_name
         FROM enrollments e
         INNER JOIN students s ON s.id = e.student_id AND s.school_number = e.school_number
         INNER JOIN classes c ON c.id = e.class_id AND c.school_number = e.school_number
         INNER JOIN levels l ON l.id = c.level_id AND l.school_number = e.school_number
         WHERE e.school_number = ? AND e.student_id = ? AND e.academic_year_id = ? AND e.study_period_id = ?
         LIMIT 1',
        [$school, $studentId, $yearId, $periodId]
    );

    if (!$enr) {
        erp_send([
            'data' => [
                'student' => null,
                'enrolled' => false,
                'rows' => [],
            ],
        ]);
    }

    $classId = (int) $enr['class_id'];

    $st = $dbh->prepare(
        'SELECT
            et.id AS exam_type_id,
            et.name AS exam_type_name,
            et.max_score AS type_max_score,
            es.id AS exam_schedule_id,
            es.exam_date,
            es.exam_time,
            es.exam_room,
            sub.id AS subject_id,
            sub.name AS subject_name,
            m.score,
            m.teacher_comment
         FROM exam_schedule_marks m
         INNER JOIN exam_schedules es ON es.id = m.exam_schedule_id AND es.school_number = m.school_number
         INNER JOIN exam_types et ON et.id = es.exam_type_id AND et.school_number = es.school_number
         INNER JOIN subjects sub ON sub.id = m.subject_id AND sub.school_number = m.school_number
         WHERE m.school_number = ?
           AND m.student_id = ?
           AND et.academic_year_id = ?
           AND et.study_period_id = ?
           AND es.class_id = ?
         ORDER BY et.name ASC, es.exam_date ASC, es.exam_time ASC, es.id ASC, sub.name ASC'
    );
    $st->execute([$school, $studentId, $yearId, $periodId, $classId]);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['exam_type_id'] = (int) $r['exam_type_id'];
        $r['exam_schedule_id'] = (int) $r['exam_schedule_id'];
        $r['subject_id'] = (int) $r['subject_id'];
        $r['type_max_score'] = (string) $r['type_max_score'];
        if ($r['score'] !== null) {
            $r['score'] = (string) $r['score'];
        }
        $r['exam_time'] = substr((string) $r['exam_time'], 0, 8);
    }
    unset($r);

    erp_send([
        'data' => [
            'enrolled' => true,
            'student' => [
                'id' => $studentId,
                'first_name' => $enr['first_name'],
                'last_name' => $enr['last_name'],
                'admission_number' => $enr['admission_number'],
                'class_name' => $enr['class_name'],
                'level_name' => $enr['level_name'],
            ],
            'rows' => $rows,
        ],
    ]);
} catch (Throwable $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
