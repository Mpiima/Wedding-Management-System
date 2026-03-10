<?php
/**
 * Wedding program API: list, create, update, delete. Event timeline / ceremony order.
 */
include("connect/header.php");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
$userId = (int) $_SESSION['user_id'];

switch ($method) {
    case 'GET': listProgram($dbh, $userId); break;
    case 'POST': createProgram($dbh, $userId, $input); break;
    case 'PUT': updateProgram($dbh, $userId, $input); break;
    case 'DELETE': deleteProgram($dbh, $userId, $input); break;
    default: http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
}

function listProgram($dbh, $userId) {
    try {
        $stmt = $dbh->prepare("SELECT id, user_id, title, description, sort_order, created_at, updated_at FROM wedding_program WHERE user_id = :uid ORDER BY sort_order ASC, id ASC");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['message' => 'OK', 'data' => $stmt->fetchAll(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) { error_log('Wedding program list: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function createProgram($dbh, $userId, $input) {
    $title = isset($input['title']) ? trim((string) $input['title']) : '';
    if ($title === '') { http_response_code(400); echo json_encode(['error' => 'Title required']); return; }
    $description = isset($input['description']) ? trim((string) $input['description']) : null;
    $sortOrder = isset($input['sort_order']) ? (int) $input['sort_order'] : 0;
    try {
        $stmt = $dbh->prepare("INSERT INTO wedding_program (user_id, title, description, sort_order) VALUES (:uid, :title, :description, :sort_order)");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':sort_order', $sortOrder, PDO::PARAM_INT);
        $stmt->execute();
        $id = (int) $dbh->lastInsertId();
        $f = $dbh->prepare("SELECT id, user_id, title, description, sort_order, created_at, updated_at FROM wedding_program WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        http_response_code(201);
        echo json_encode(['message' => 'Created', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) { error_log('Wedding program create: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function updateProgram($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Id required']); return; }
    $check = $dbh->prepare("SELECT id FROM wedding_program WHERE id = :id AND user_id = :uid LIMIT 1");
    $check->bindValue(':id', $id, PDO::PARAM_INT);
    $check->bindValue(':uid', $userId, PDO::PARAM_INT);
    $check->execute();
    if (!$check->fetch()) { http_response_code(404); echo json_encode(['error' => 'Not found']); return; }
    $updates = []; $params = [':id' => $id];
    if (array_key_exists('title', $input)) { $updates[] = 'title = :title'; $params[':title'] = trim((string) $input['title']); }
    if (array_key_exists('description', $input)) { $updates[] = 'description = :description'; $params[':description'] = trim((string) $input['description']) ?: null; }
    if (array_key_exists('sort_order', $input)) { $updates[] = 'sort_order = :sort_order'; $params[':sort_order'] = (int) $input['sort_order']; }
    if (count($updates) === 0) {
        $f = $dbh->prepare("SELECT id, user_id, title, description, sort_order, created_at, updated_at FROM wedding_program WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        echo json_encode(['message' => 'No changes', 'data' => $f->fetch(PDO::FETCH_OBJ)]); return;
    }
    $sql = "UPDATE wedding_program SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    $stmt->execute();
    $f = $dbh->prepare("SELECT id, user_id, title, description, sort_order, created_at, updated_at FROM wedding_program WHERE id = :id LIMIT 1");
    $f->bindValue(':id', $id, PDO::PARAM_INT);
    $f->execute();
    echo json_encode(['message' => 'Updated', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
}

function deleteProgram($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Id required']); return; }
    $stmt = $dbh->prepare("DELETE FROM wedding_program WHERE id = :id AND user_id = :uid");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() === 0) { http_response_code(404); echo json_encode(['error' => 'Not found']); return; }
    echo json_encode(['message' => 'Deleted']);
}
