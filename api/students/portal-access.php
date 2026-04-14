<?php

declare(strict_types=1);

/**
 * Admin: create/update a users row so the student can sign in with username ADM… + password.
 * GET ?studentId= — returns whether portal login exists and the username (no password).
 * POST { studentId, password? } — set password (generated if omitted); returns username + password once.
 */
require_once __DIR__ . '/../erp/_init.php';

$school = erp_school_id($dbh);

try {
    if ($method === 'GET') {
        $studentId = (int) ($_GET['studentId'] ?? 0);
        if ($studentId < 1 || !erp_student_owned($dbh, $school, $studentId)) {
            erp_send(['error' => 'Student not found'], 404);
        }
        $st = $dbh->prepare('SELECT username FROM users WHERE student_id = ? AND ssid = ? LIMIT 1');
        $st->execute([$studentId, $school]);
        $username = $st->fetchColumn();
        $username = $username === false ? null : (string) $username;
        erp_send([
            'data' => [
                'hasPortalLogin' => $username !== null && $username !== '',
                'username' => $username
            ]
        ]);
    }

    if ($method === 'POST') {
        $body = erp_json_body();
        $studentId = (int) ($body['studentId'] ?? 0);
        $plainPassword = isset($body['password']) ? trim((string) $body['password']) : '';

        if ($studentId < 1 || !erp_student_owned($dbh, $school, $studentId)) {
            erp_send(['error' => 'Student not found'], 404);
        }

        $st = $dbh->prepare(
            'SELECT id, admission_number, first_name, last_name, school_number FROM students WHERE id = ? AND school_number = ? LIMIT 1'
        );
        $st->execute([$studentId, $school]);
        $student = $st->fetch(PDO::FETCH_ASSOC);
        if (!$student) {
            erp_send(['error' => 'Student not found'], 404);
        }

        $adm = preg_replace('/[^A-Za-z0-9]/', '', (string) ($student['admission_number'] ?? ''));
        $username = 'ADM' . ($adm !== '' ? $adm : (string) $studentId);

        $st = $dbh->prepare('SELECT id, student_id, ssid FROM users WHERE username = ? LIMIT 1');
        $st->execute([$username]);
        $conflict = $st->fetch(PDO::FETCH_ASSOC);
        if (
            $conflict
            && ((int) ($conflict['student_id'] ?? 0) !== $studentId || (string) ($conflict['ssid'] ?? '') !== $school)
        ) {
            $username = 'ADM' . $studentId;
        }

        $emailLocal = 'student_' . $studentId . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $school);
        $email = $emailLocal . '@portal.schpro360.local';

        if ($plainPassword === '') {
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
            $plainPassword = '';
            for ($i = 0; $i < 10; $i++) {
                $plainPassword .= $chars[random_int(0, strlen($chars) - 1)];
            }
        }
        if (strlen($plainPassword) < 6) {
            erp_send(['error' => 'Password must be at least 6 characters'], 400);
        }

        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $fn = (string) ($student['first_name'] ?? '');
        $ln = (string) ($student['last_name'] ?? '');

        $st = $dbh->prepare('SELECT id FROM users WHERE student_id = ? AND ssid = ? LIMIT 1');
        $st->execute([$studentId, $school]);
        $existingId = (int) $st->fetchColumn();

        if ($existingId > 0) {
            $dbh->prepare(
                'UPDATE users SET username = ?, password = ?, firstname = ?, lastname = ?, role = ?, rolenumber = ?, powers = ?, position = ?, student_id = ? WHERE id = ? AND ssid = ?'
            )->execute([
                $username,
                $hash,
                $fn,
                $ln,
                'student',
                '3',
                'portal.view',
                'student',
                $studentId,
                $existingId,
                $school
            ]);
        } else {
            $st = $dbh->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $st->execute([$email]);
            if ($st->fetchColumn()) {
                $email = $emailLocal . '_' . time() . '@portal.schpro360.local';
            }
            $dbh->prepare(
                'INSERT INTO users (username, firstname, lastname, email, password, role, rolenumber, ssid, powers, position, student_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $username,
                $fn,
                $ln,
                $email,
                $hash,
                'student',
                '3',
                $school,
                'portal.view',
                'student',
                $studentId
            ]);
        }

        erp_send([
            'message' => 'Portal login saved',
            'data' => [
                'username' => $username,
                'password' => $plainPassword,
                'loginHint' => 'Sign in with this username and password on the login page (use the “Portal username” field).'
            ]
        ]);
    }

    erp_send(['message' => 'Method not allowed'], 405);
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Unknown column') !== false && strpos($e->getMessage(), 'student_id') !== false) {
        erp_send(['error' => 'Run database migration: users_student_id.sql'], 500);
    }
    erp_send(['error' => $e->getMessage()], 500);
}
