<?php

require_once __DIR__ . '/../connect/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

function adm_school_id(): string
{
    $ssid = isset($_SESSION['ssid']) ? (string) $_SESSION['ssid'] : '';
    if ($ssid === '' || $ssid === '0') {
        http_response_code(401);
        echo json_encode(['error' => 'School session required', 'data' => null]);
        exit;
    }
    return $ssid;
}

function adm_json(): array
{
    global $input;
    return is_array($input) ? $input : [];
}

function adm_send($payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}

function adm_actor(): string
{
    return trim((string) ($_SESSION['username'] ?? $_SESSION['firstname'] ?? 'staff'));
}

function adm_applicant_owned(PDO $dbh, string $school, int $id): ?array
{
    $st = $dbh->prepare('SELECT * FROM applicants WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$id, $school]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row === false ? null : $row;
}

function adm_log_status(PDO $dbh, int $applicantId, ?string $from, string $to, ?string $note): void
{
    $st = $dbh->prepare(
        'INSERT INTO applicant_status_log (applicant_id, from_status, to_status, actor, note) VALUES (?, ?, ?, ?, ?)'
    );
    $st->execute([$applicantId, $from, $to, adm_actor(), $note]);
}

function adm_notify(PDO $dbh, string $school, int $applicantId, string $eventKey, string $message): void
{
    $st = $dbh->prepare(
        'INSERT INTO admission_notifications (school_number, applicant_id, channel, event_key, message) VALUES (?, ?, ?, ?, ?)'
    );
    $st->execute([$school, $applicantId, 'in_app', $eventKey, $message]);
}

function adm_fix_application_number(PDO $dbh, int $id): void
{
    $dbh->prepare('UPDATE applicants SET application_number = CONCAT(\'APP-\', LPAD(id, 6, \'0\')) WHERE id = ?')->execute([$id]);
}

function adm_class_capacity(PDO $dbh, string $school, int $classId, int $yearId): array
{
    $st = $dbh->prepare('SELECT max_students FROM classes WHERE id = ? AND school_number = ? LIMIT 1');
    $st->execute([$classId, $school]);
    $max = (int) $st->fetchColumn();
    if ($max < 1) {
        $max = 40;
    }
    $st = $dbh->prepare(
        'SELECT COUNT(*) FROM enrollments e
         INNER JOIN students s ON s.id = e.student_id
         WHERE e.class_id = ? AND e.academic_year_id = ? AND s.school_number = ?'
    );
    $st->execute([$classId, $yearId, $school]);
    $used = (int) $st->fetchColumn();
    return ['max' => $max, 'used' => $used, 'remaining' => max(0, $max - $used)];
}
