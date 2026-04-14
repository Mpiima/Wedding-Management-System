<?php

declare(strict_types=1);

/**
 * Full student + current (active year/period) enrollment for the student portal.
 * studentId: query param, or session portal_student_id (set at login when users.student_id is set).
 */
require_once __DIR__ . '/../erp/_init.php';

$school = erp_school_id($dbh);

if ($method !== 'GET') {
    erp_send(['message' => 'Method not allowed'], 405);
}

try {
    $studentId = (int) ($_GET['studentId'] ?? 0);
    if ($studentId < 1) {
        $studentId = (int) ($_SESSION['portal_student_id'] ?? 0);
    }
    if ($studentId < 1 || !erp_student_owned($dbh, $school, $studentId)) {
        erp_send(['error' => 'Student not found or access denied'], 404);
    }

    $st = $dbh->prepare(
        'SELECT id, school_number, admission_number, first_name, last_name, gender, dob,
                guardian_name, guardian_phone, guardian_email, photo_path, bio_notes, created_at
         FROM students WHERE id = ? AND school_number = ? LIMIT 1'
    );
    $st->execute([$studentId, $school]);
    $student = $st->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        erp_send(['error' => 'Student not found'], 404);
    }

    $yearId = (int) ($_GET['academicYearId'] ?? 0);
    $periodId = (int) ($_GET['studyPeriodId'] ?? 0);
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

    $enrollment = null;
    if ($yearId > 0 && $periodId > 0) {
        $per = erp_period_owned($dbh, $school, $periodId);
        if ($per && (int) $per['academic_year_id'] === $yearId) {
            $st = $dbh->prepare(
                'SELECT e.id AS enrollment_id, e.enrolled_at,
                        ay.name AS academic_year_name,
                        sp.name AS study_period_name,
                        cl.name AS class_name,
                        st.name AS stream_name
                 FROM enrollments e
                 INNER JOIN academic_years ay ON ay.id = e.academic_year_id AND ay.school_number = e.school_number
                 INNER JOIN study_periods sp ON sp.id = e.study_period_id AND sp.school_number = e.school_number
                 LEFT JOIN classes cl ON cl.id = e.class_id AND cl.school_number = e.school_number
                 LEFT JOIN streams st ON st.id = e.stream_id AND st.school_number = e.school_number
                 WHERE e.student_id = ? AND e.school_number = ? AND e.academic_year_id = ? AND e.study_period_id = ?
                 LIMIT 1'
            );
            $st->execute([$studentId, $school, $yearId, $periodId]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            $enrollment = $row === false ? null : $row;
        }
    }

    $feesBalance = null;
    $walletBalance = null;
    try {
        if ($yearId > 0 && $periodId > 0) {
            $st = $dbh->prepare(
                'SELECT balance_due FROM invoices WHERE student_id = ? AND school_number = ? AND academic_year_id = ? AND study_period_id = ? LIMIT 1'
            );
            $st->execute([$studentId, $school, $yearId, $periodId]);
            $due = $st->fetchColumn();
            if ($due !== false) {
                $feesBalance = round((float) $due, 2);
            }
        }
        $st = $dbh->prepare(
            'SELECT balance FROM student_credit_wallet WHERE student_id = ? AND school_number = ? LIMIT 1'
        );
        $st->execute([$studentId, $school]);
        $wb = $st->fetchColumn();
        if ($wb !== false) {
            $walletBalance = round((float) $wb, 2);
        }
    } catch (Exception $e) {
        // invoices / wallet tables may be missing on partial installs
    }

    erp_send([
        'data' => [
            'student' => $student,
            'enrollment' => $enrollment,
            'meta' => [
                'academicYearId' => $yearId > 0 ? $yearId : null,
                'studyPeriodId' => $periodId > 0 ? $periodId : null,
                'academicYearName' => $yearName,
                'studyPeriodName' => $periodName
            ],
            'finance' => [
                'feesBalance' => $feesBalance,
                'walletBalance' => $walletBalance
            ]
        ]
    ]);
} catch (Exception $e) {
    erp_send(['error' => $e->getMessage()], 500);
}
