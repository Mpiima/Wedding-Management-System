<?php
/**
 * Change password for the current user.
 * POST: current_password, new_password
 */
include("connect/header.php");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$current = isset($input['current_password']) ? $input['current_password'] : '';
$new = isset($input['new_password']) ? (string) $input['new_password'] : '';

if ($current === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Current password is required']);
    exit;
}

if (strlen($new) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'New password must be at least 6 characters']);
    exit;
}

try {
    $stmt = $dbh->prepare("SELECT id, password FROM users WHERE id = :id LIMIT 1");
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_OBJ);
    if (!$user || !password_verify($current, $user->password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Current password is incorrect']);
        exit;
    }

    $hash = password_hash($new, PASSWORD_DEFAULT);
    $upd = $dbh->prepare("UPDATE users SET password = :pwd WHERE id = :id");
    $upd->bindValue(':pwd', $hash, PDO::PARAM_STR);
    $upd->bindValue(':id', $userId, PDO::PARAM_INT);
    $upd->execute();

    echo json_encode(['message' => 'Password changed successfully']);
} catch (PDOException $e) {
    error_log('Change password: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred']);
}
