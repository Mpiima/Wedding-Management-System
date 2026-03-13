<?php
/**
 * Current user profile: GET = read, PUT = update (firstname, lastname, email, avatar).
 */
include("connect/header.php");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$userId = (int) $_SESSION['user_id'];
switch ($method) {
    case 'GET': getMe($dbh, $userId); break;
    case 'PUT': updateMe($dbh, $userId, $input); break;
    default: http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit;
}
function getMe($dbh, $userId) {
    try {
        $stmt = $dbh->prepare("SELECT id, username, firstname, lastname, email, avatar, role, position, created_at, updated_at FROM users WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$row) { http_response_code(404); echo json_encode(['error' => 'User not found']); return; }
        echo json_encode(['message' => 'OK', 'data' => $row]);
    } catch (PDOException $e) {
        error_log('Me GET: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}
function updateMe($dbh, $userId, $input) {
    $updates = [];
    $params = [':id' => $userId];
    if (array_key_exists('firstname', $input)) { $updates[] = 'firstname = :firstname'; $params[':firstname'] = trim((string) $input['firstname']); }
    if (array_key_exists('lastname', $input)) { $updates[] = 'lastname = :lastname'; $params[':lastname'] = trim((string) $input['lastname']); }
    if (array_key_exists('email', $input)) { $updates[] = 'email = :email'; $params[':email'] = trim((string) $input['email']); }
    if (array_key_exists('avatar', $input)) { $updates[] = 'avatar = :avatar'; $params[':avatar'] = trim((string) $input['avatar']); }
    if (count($updates) === 0) { getMe($dbh, $userId); return; }
    try {
        $stmt = $dbh->prepare("UPDATE users SET " . implode(', ', $updates) . " WHERE id = :id");
        foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        $stmt->execute();
        getMe($dbh, $userId);
    } catch (PDOException $e) {
        error_log('Me PUT: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}
