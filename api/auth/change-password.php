<?php

include("../connect/header.php");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($method !== 'POST') {
    echo json_encode(['message' => 'Invalid request method']);
    exit;
}

$username = isset($_SESSION['username']) ? trim((string) $_SESSION['username']) : '';
$ssid = isset($_SESSION['ssid']) ? trim((string) $_SESSION['ssid']) : '';
$role = isset($_SESSION['role']) ? trim((string) $_SESSION['role']) : '';

if ($username === '' || $ssid === '') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated', 'data' => '']);
    exit;
}

if (strtolower($role) !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Only student portal accounts can use this endpoint', 'data' => '']);
    exit;
}

$current = isset($input['currentPassword']) ? (string) $input['currentPassword'] : '';
$new = isset($input['newPassword']) ? trim((string) $input['newPassword']) : '';

if ($current === '' || $new === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Current password and new password are required', 'data' => '']);
    exit;
}

if (strlen($new) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'New password must be at least 6 characters', 'data' => '']);
    exit;
}

if ($new === $current) {
    http_response_code(400);
    echo json_encode(['error' => 'Choose a new password that is different from your current one', 'data' => '']);
    exit;
}

try {
    $q = $dbh->prepare('SELECT id, password FROM users WHERE username = ? AND ssid = ? LIMIT 1');
    $q->execute([$username, $ssid]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(401);
        echo json_encode(['error' => 'Account not found', 'data' => '']);
        exit;
    }
    if (!password_verify($current, $row['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Current password is incorrect', 'data' => '']);
        exit;
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $u = $dbh->prepare('UPDATE users SET password = ? WHERE id = ? AND ssid = ?');
    $u->execute([$hash, (int) $row['id'], $ssid]);

    echo json_encode(['message' => 'Password updated', 'data' => []]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage(), 'data' => '']);
}
