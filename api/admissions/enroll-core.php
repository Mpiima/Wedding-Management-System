<?php

function adm_perform_enroll(PDO $dbh, string $school, array $body): array
{
    $applicantId = (int) ($body['applicantId'] ?? 0);
    $yearId = (int) ($body['academicYearId'] ?? 0);
    $periodId = (int) ($body['studyPeriodId'] ?? 0);
    $classId = (int) ($body['classId'] ?? 0);
    $streamId = (int) ($body['streamId'] ?? 0) ?: null;
    $force = !empty($body['forceCapacity']);

    if ($applicantId < 1 || $yearId < 1 || $periodId < 1 || $classId < 1) {
        return ['error' => 'applicantId, academicYearId, studyPeriodId, classId required'];
    }

    $app = adm_applicant_owned($dbh, $school, $applicantId);
    if (!$app) {
        return ['error' => 'Applicant not found'];
    }

    $st = $dbh->prepare('SELECT id FROM academic_years WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$yearId, $school]);
    if (!$st->fetchColumn()) {
        return ['error' => 'Invalid academic year'];
    }

    $st = $dbh->prepare('SELECT id, academic_year_id FROM study_periods WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$periodId, $school]);
    $per = $st->fetch(PDO::FETCH_ASSOC);
    if (!$per || (int) $per['academic_year_id'] !== $yearId) {
        return ['error' => 'Study period does not match year'];
    }

    $st = $dbh->prepare('SELECT id FROM classes WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$classId, $school]);
    if (!$st->fetchColumn()) {
        return ['error' => 'Invalid class'];
    }

    if ($streamId) {
        $st = $dbh->prepare('SELECT id FROM streams WHERE id = ? AND school_number = ? LIMIT 1');
        $st->execute([$streamId, $school]);
        if (!$st->fetchColumn()) {
            return ['error' => 'Invalid stream'];
        }
    }

    $studentId = (int) ($app['student_id'] ?? 0);
    if ($studentId < 1) {
        return ['error' => 'Accept the applicant first so a student record is created, then complete enrollment.'];
    }

    $ps = (string) ($app['pipeline_status'] ?? '');
    if (!in_array($ps, ['accepted', 'waitlist'], true)) {
        return [
            'error' => 'Enrollment is only available when the applicant is Accepted or on the Waitlist. Current status: ' . ($ps ?: 'unknown')
        ];
    }

    $st = $dbh->prepare(
        'SELECT id FROM enrollments WHERE student_id = ? AND academic_year_id = ? AND study_period_id = ? LIMIT 1'
    );
    $st->execute([$studentId, $yearId, $periodId]);
    if ($st->fetchColumn()) {
        return ['error' => 'Already enrolled for this period'];
    }

    $cap = adm_class_capacity($dbh, $school, $classId, $yearId);
    if (!$force && $cap['remaining'] <= 0) {
        return ['error' => 'Class is full', 'capacity' => $cap, 'code' => 409];
    }

    $dbh->prepare(
        'INSERT INTO enrollments (school_number, student_id, academic_year_id, study_period_id, class_id, stream_id) VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([$school, $studentId, $yearId, $periodId, $classId, $streamId]);

    $dbh->prepare('UPDATE applicants SET pipeline_status = \'enrolled\', waitlist_position = NULL WHERE id = ?')->execute([$applicantId]);

    adm_log_status($dbh, $applicantId, $app['pipeline_status'], 'enrolled', 'Enrolled in class');
    adm_notify($dbh, $school, $applicantId, 'enrolled', 'Enrolled successfully.');

    $dbh->prepare(
        'INSERT INTO student_fee_links (student_id, academic_year_id, fee_profile, overridden) VALUES (?, ?, \'class_default\', 0) ON DUPLICATE KEY UPDATE fee_profile = VALUES(fee_profile)'
    )->execute([$studentId, $yearId]);

    $lib = __DIR__ . '/../finance/invoice-lib.php';
    if (is_readable($lib)) {
        require_once $lib;
        fin_try_generate_invoice_after_enrollment($dbh, $school, $studentId, $yearId, $periodId, $classId);
    }

    return ['error' => null, 'capacity' => $cap, 'studentId' => $studentId];
}
