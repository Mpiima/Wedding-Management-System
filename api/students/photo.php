<?php

declare(strict_types=1);

require_once __DIR__ . '/../erp/_init.php';

$school = erp_school_id($dbh);

if ($method !== 'POST') {
    erp_send(['message' => 'Method not allowed'], 405);
}

try {
    $id = (int) ($_POST['studentId'] ?? 0);
    if ($id < 1 || !erp_student_owned($dbh, $school, $id)) {
        erp_send(['error' => 'Student not found'], 404);
    }
    if (empty($_FILES['file']) || (int) $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        erp_send(['error' => 'Image upload failed'], 400);
    }

    $mime = mime_content_type($_FILES['file']['tmp_name']);
    $allowedMime = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowedMime[$mime])) {
        erp_send(['error' => 'Use a JPG, PNG, WebP, or GIF image'], 400);
    }
    $ext = $allowedMime[$mime];

    $safeSchool = preg_replace('/[^a-zA-Z0-9_-]/', '', $school);
    $base = dirname(__DIR__, 2) . '/uploads/students/' . $safeSchool . '/' . $id;
    if (!is_dir($base)) {
        mkdir($base, 0755, true);
    }

    $stOld = $dbh->prepare('SELECT photo_path FROM students WHERE id = ? AND school_number = ? LIMIT 1');
    $stOld->execute([$id, $school]);
    $oldPath = $stOld->fetchColumn();
    if ($oldPath) {
        $root = dirname(__DIR__, 2);
        $fullOld = $root . '/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $oldPath);
        if (is_file($fullOld)) {
            @unlink($fullOld);
        }
    }

    $safe = 'profile.' . $ext;
    $dest = $base . '/' . $safe;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        erp_send(['error' => 'Could not save file'], 500);
    }

    $rel = 'uploads/students/' . $school . '/' . $id . '/' . $safe;
    $dbh->prepare('UPDATE students SET photo_path = ? WHERE id = ? AND school_number = ?')->execute([$rel, $id, $school]);

    erp_send(['message' => 'Uploaded', 'data' => ['photo_path' => $rel]]);
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Unknown column') !== false) {
        erp_send(
            [
                'error' => 'Database missing students.photo_path. Run database/migrations/students_profile_photo.sql'
            ],
            500
        );
    }
    erp_send(['error' => $e->getMessage()], 500);
}
