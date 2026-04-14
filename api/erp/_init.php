<?php

declare(strict_types=1);

require_once __DIR__ . '/../connect/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

/**
 * Require logged-in school user (not super_admin without ssid).
 */
function erp_current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function erp_school_id(PDO $dbh): string
{
    $ssid = isset($_SESSION['ssid']) ? (string) $_SESSION['ssid'] : '';
    if ($ssid === '' || $ssid === '0') {
        http_response_code(401);
        echo json_encode(['error' => 'School session required', 'data' => null]);
        exit;
    }
    return $ssid;
}

function erp_json_body(): array
{
    global $input;
    return is_array($input) ? $input : [];
}

function erp_send($payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function erp_row_school(PDO $dbh, string $sql, array $params): ?array
{
    $st = $dbh->prepare($sql);
    $st->execute($params);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : $row;
}

function erp_deactivate_other_years(PDO $dbh, string $school, int $exceptId): void
{
    $st = $dbh->prepare('UPDATE academic_years SET is_active = 0 WHERE school_number = ? AND id <> ?');
    $st->execute([$school, $exceptId]);
}

function erp_deactivate_other_periods(PDO $dbh, string $school, int $yearId, int $exceptId): void
{
    $st = $dbh->prepare(
        'UPDATE study_periods SET is_active = 0 WHERE school_number = ? AND academic_year_id = ? AND id <> ?'
    );
    $st->execute([$school, $yearId, $exceptId]);
}

function erp_year_owned(PDO $dbh, string $school, int $id): bool
{
    $st = $dbh->prepare('SELECT id FROM academic_years WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$id, $school]);
    return (bool) $st->fetchColumn();
}

function erp_period_owned(PDO $dbh, string $school, int $id): ?array
{
    return erp_row_school(
        $dbh,
        'SELECT * FROM study_periods WHERE id = ? AND school_number = ? LIMIT 1',
        [$id, $school]
    );
}

function erp_level_owned(PDO $dbh, string $school, int $id): bool
{
    $st = $dbh->prepare('SELECT id FROM levels WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$id, $school]);
    return (bool) $st->fetchColumn();
}

function erp_class_owned(PDO $dbh, string $school, int $id): bool
{
    $st = $dbh->prepare('SELECT id FROM classes WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$id, $school]);
    return (bool) $st->fetchColumn();
}

function erp_stream_owned(PDO $dbh, string $school, int $id): bool
{
    $st = $dbh->prepare('SELECT id FROM streams WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$id, $school]);
    return (bool) $st->fetchColumn();
}

function erp_student_owned(PDO $dbh, string $school, int $id): bool
{
    $st = $dbh->prepare('SELECT id FROM students WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$id, $school]);
    return (bool) $st->fetchColumn();
}

function erp_subject_owned(PDO $dbh, string $school, int $id): bool
{
    $st = $dbh->prepare('SELECT id FROM subjects WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$id, $school]);
    return (bool) $st->fetchColumn();
}

function erp_exam_type_owned(PDO $dbh, string $school, int $id): bool
{
    $st = $dbh->prepare('SELECT id FROM exam_types WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$id, $school]);
    return (bool) $st->fetchColumn();
}

function erp_exam_schedule_owned(PDO $dbh, string $school, int $id): bool
{
    $st = $dbh->prepare('SELECT id FROM exam_schedules WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$id, $school]);
    return (bool) $st->fetchColumn();
}

function erp_subject_in_class(PDO $dbh, int $classId, int $subjectId): bool
{
    $st = $dbh->prepare('SELECT 1 FROM class_subjects WHERE class_id = ? AND subject_id = ? LIMIT 1');
    $st->execute([$classId, $subjectId]);
    return (bool) $st->fetchColumn();
}

/**
 * School staff user usable as exam supervisors (same school, not blocked positions).
 */
function erp_school_supervisor_user(PDO $dbh, string $school, int $userId): bool
{
    $st = $dbh->prepare(
        "SELECT id FROM users WHERE id = ? AND ssid = ? AND position NOT IN ('member','rejected') LIMIT 1"
    );
    $st->execute([$userId, $school]);
    return (bool) $st->fetchColumn();
}

function erp_student_enrolled_in_class(PDO $dbh, string $school, int $studentId, int $yearId, int $periodId, int $classId): bool
{
    $st = $dbh->prepare(
        'SELECT 1 FROM enrollments
         WHERE school_number = ? AND student_id = ? AND academic_year_id = ? AND study_period_id = ? AND class_id = ?
         LIMIT 1'
    );
    $st->execute([$school, $studentId, $yearId, $periodId, $classId]);
    return (bool) $st->fetchColumn();
}
