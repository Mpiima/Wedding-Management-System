<?php

declare(strict_types=1);

require_once __DIR__ . '/../erp/_init.php';

$school = erp_school_id($dbh);

try {
    if ($method === 'GET') {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id < 1 || !erp_student_owned($dbh, $school, $id)) {
            erp_send(['error' => 'Student not found'], 404);
        }
        $st = $dbh->prepare(
            'SELECT id, school_number, admission_number, first_name, last_name, gender, dob,
                    guardian_name, guardian_phone, guardian_email, photo_path, bio_notes, created_at
             FROM students WHERE id = ? AND school_number = ? LIMIT 1'
        );
        $st->execute([$id, $school]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        erp_send(['data' => $row]);
    }

    if ($method === 'PUT' || $method === 'PATCH') {
        $body = erp_json_body();
        $id = (int) ($body['studentId'] ?? $body['id'] ?? 0);
        if ($id < 1 || !erp_student_owned($dbh, $school, $id)) {
            erp_send(['error' => 'Student not found'], 404);
        }

        $allowed = [
            'first_name' => true,
            'last_name' => true,
            'gender' => true,
            'dob' => true,
            'guardian_name' => true,
            'guardian_phone' => true,
            'guardian_email' => true,
            'bio_notes' => true
        ];

        $sets = [];
        $params = [];
        foreach ($allowed as $field => $_) {
            if (!array_key_exists($field, $body)) {
                continue;
            }
            $val = $body[$field];
            if ($field === 'dob') {
                if ($val === null || $val === '') {
                    $sets[] = 'dob = NULL';
                } else {
                    $sets[] = 'dob = ?';
                    $params[] = substr((string) $val, 0, 10);
                }
                continue;
            }
            if ($field === 'bio_notes') {
                $sets[] = 'bio_notes = ?';
                $params[] = $val === null ? null : (string) $val;
                continue;
            }
            $sets[] = "`{$field}` = ?";
            $params[] = (string) $val;
        }

        if ($sets === []) {
            erp_send(['error' => 'No fields to update'], 400);
        }

        $params[] = $id;
        $params[] = $school;

        $sql = 'UPDATE students SET ' . implode(', ', $sets) . ' WHERE id = ? AND school_number = ?';
        $st = $dbh->prepare($sql);
        $st->execute($params);

        $st = $dbh->prepare(
            'SELECT id, school_number, admission_number, first_name, last_name, gender, dob,
                    guardian_name, guardian_phone, guardian_email, photo_path, bio_notes, created_at
             FROM students WHERE id = ? AND school_number = ? LIMIT 1'
        );
        $st->execute([$id, $school]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        erp_send(['message' => 'Saved', 'data' => $row]);
    }

    erp_send(['message' => 'Method not allowed'], 405);
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Unknown column') !== false) {
        erp_send(
            [
                'error' => 'Database missing students.photo_path / bio_notes. Run database/migrations/students_profile_photo.sql'
            ],
            500
        );
    }
    erp_send(['error' => $e->getMessage()], 500);
}
