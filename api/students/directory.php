<?php

declare(strict_types=1);

require_once __DIR__ . '/../erp/_init.php';

$school = erp_school_id($dbh);

if ($method !== 'GET') {
    erp_send(['message' => 'Method not allowed'], 405);
}

try {
    $yearId = (int) ($_GET['academicYearId'] ?? 0);
    $periodId = (int) ($_GET['studyPeriodId'] ?? 0);
    $classId = (int) ($_GET['classId'] ?? 0);

    if ($yearId < 1) {
        $st = $dbh->prepare('SELECT id FROM academic_years WHERE school_number = ? AND is_active = 1 LIMIT 1');
        $st->execute([$school]);
        $yearId = (int) $st->fetchColumn();
    }
    if ($periodId < 1 && $yearId > 0) {
        $st = $dbh->prepare(
            'SELECT id FROM study_periods WHERE school_number = ? AND academic_year_id = ? AND is_active = 1 LIMIT 1'
        );
        $st->execute([$school, $yearId]);
        $periodId = (int) $st->fetchColumn();
    }

    $yearName = '';
    $periodName = '';
    if ($yearId > 0) {
        $st = $dbh->prepare('SELECT name FROM academic_years WHERE id = ? AND school_number = ? LIMIT 1');
        $st->execute([$yearId, $school]);
        $yearName = (string) $st->fetchColumn();
    }
    if ($periodId > 0) {
        $st = $dbh->prepare('SELECT name FROM study_periods WHERE id = ? AND school_number = ? LIMIT 1');
        $st->execute([$periodId, $school]);
        $periodName = (string) $st->fetchColumn();
    }

    if ($yearId < 1 || $periodId < 1) {
        erp_send([
            'data' => [],
            'meta' => [
                'academicYearId' => null,
                'studyPeriodId' => null,
                'academicYearName' => '',
                'studyPeriodName' => '',
                'message' => 'Set an active academic year and study period in Academic setup.'
            ]
        ]);
    }

    $per = erp_period_owned($dbh, $school, $periodId);
    if (!$per || (int) $per['academic_year_id'] !== $yearId) {
        erp_send(['error' => 'Study period does not match academic year'], 400);
    }

    $sql = 'SELECT e.id AS enrollment_id, e.student_id, e.academic_year_id, e.study_period_id, e.class_id, e.stream_id,
            e.enrolled_at,
            s.admission_number, s.first_name, s.last_name, s.gender, s.dob,
            s.guardian_name, s.guardian_phone, s.guardian_email, s.photo_path, s.bio_notes,
            cl.name AS class_name,
            st.name AS stream_name
            FROM enrollments e
            INNER JOIN students s ON s.id = e.student_id AND s.school_number = e.school_number
            LEFT JOIN classes cl ON cl.id = e.class_id AND cl.school_number = e.school_number
            LEFT JOIN streams st ON st.id = e.stream_id AND st.school_number = e.school_number
            WHERE e.school_number = ? AND e.academic_year_id = ? AND e.study_period_id = ?';
    $params = [$school, $yearId, $periodId];
    if ($classId > 0) {
        $sql .= ' AND e.class_id = ?';
        $params[] = $classId;
    }
    $sql .= ' ORDER BY s.last_name ASC, s.first_name ASC';

    $st = $dbh->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    erp_send([
        'data' => $rows,
        'meta' => [
            'academicYearId' => $yearId,
            'studyPeriodId' => $periodId,
            'academicYearName' => $yearName,
            'studyPeriodName' => $periodName
        ]
    ]);
} catch (Exception $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
