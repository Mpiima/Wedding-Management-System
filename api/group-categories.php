<?php
/**
 * Group Categories API: list, create, update, delete (by authenticated user).
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
    case 'GET': listGroupCategories($dbh, $userId); break;
    case 'POST': createGroupCategory($dbh, $userId, $input); break;
    case 'PUT': updateGroupCategory($dbh, $userId, $input); break;
    case 'DELETE': deleteGroupCategory($dbh, $userId, $input); break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function listGroupCategories($dbh, $userId) {
    try {
        $stmt = $dbh->prepare("SELECT id, user_id, name, description, sort_order, created_at, updated_at FROM group_categories WHERE user_id = :user_id ORDER BY sort_order ASC, name ASC");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode(['message' => 'OK', 'data' => $rows]);
    } catch (PDOException $e) {
        error_log('Group categories list: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function createGroupCategory($dbh, $userId, $input) {
    $name = isset($input['name']) ? trim((string) $input['name']) : '';
    if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Category name is required']);
        return;
    }
    $description = isset($input['description']) ? trim((string) $input['description']) : null;
    $sortOrder = isset($input['sort_order']) ? (int) $input['sort_order'] : 0;
    try {
        $stmt = $dbh->prepare("INSERT INTO group_categories (user_id, name, description, sort_order) VALUES (:user_id, :name, :description, :sort_order)");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description ?: null, PDO::PARAM_STR);
        $stmt->bindValue(':sort_order', $sortOrder, PDO::PARAM_INT);
        $stmt->execute();
        $id = (int) $dbh->lastInsertId();
        $fetch = $dbh->prepare("SELECT id, user_id, name, description, sort_order, created_at, updated_at FROM group_categories WHERE id = :id LIMIT 1");
        $fetch->bindValue(':id', $id, PDO::PARAM_INT);
        $fetch->execute();
        http_response_code(201);
        echo json_encode(['message' => 'Category created', 'data' => $fetch->fetch(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) {
        error_log('Group category create: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function updateGroupCategory($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Category id is required']); return; }
    $name = isset($input['name']) ? trim((string) $input['name']) : null;
    $description = array_key_exists('description', $input) ? trim((string) $input['description']) : null;
    $sortOrder = isset($input['sort_order']) ? (int) $input['sort_order'] : null;
    try {
        $check = $dbh->prepare("SELECT id FROM group_categories WHERE id = :id AND user_id = :user_id LIMIT 1");
        $check->bindValue(':id', $id, PDO::PARAM_INT);
        $check->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $check->execute();
        if (!$check->fetch()) { http_response_code(404); echo json_encode(['error' => 'Category not found']); return; }
        $updates = [];
        $params = [':id' => $id, ':user_id' => $userId];
        if ($name !== null) { $updates[] = 'name = :name'; $params[':name'] = $name; }
        if (array_key_exists('description', $input)) { $updates[] = 'description = :description'; $params[':description'] = $description ?: null; }
        if ($sortOrder !== null) { $updates[] = 'sort_order = :sort_order'; $params[':sort_order'] = $sortOrder; }
        if (count($updates) === 0) {
            $f = $dbh->prepare("SELECT id, user_id, name, description, sort_order, created_at, updated_at FROM group_categories WHERE id = :id LIMIT 1");
            $f->bindValue(':id', $id, PDO::PARAM_INT);
            $f->execute();
            echo json_encode(['message' => 'No changes', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
            return;
        }
        $sql = "UPDATE group_categories SET " . implode(', ', $updates) . " WHERE id = :id AND user_id = :user_id";
        $stmt = $dbh->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR); }
        $stmt->execute();
        $f = $dbh->prepare("SELECT id, user_id, name, description, sort_order, created_at, updated_at FROM group_categories WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        echo json_encode(['message' => 'Category updated', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) {
        error_log('Group category update: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function deleteGroupCategory($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Category id is required']); return; }
    try {
        $check = $dbh->prepare("SELECT COUNT(*) AS n FROM members WHERE group_category_id = :id");
        $check->bindValue(':id', $id, PDO::PARAM_INT);
        $check->execute();
        if ((int) $check->fetch(PDO::FETCH_OBJ)->n > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Category has members. Move or delete them first.']);
            return;
        }
        $stmt = $dbh->prepare("DELETE FROM group_categories WHERE id = :id AND user_id = :user_id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() === 0) { http_response_code(404); echo json_encode(['error' => 'Category not found']); return; }
        echo json_encode(['message' => 'Category deleted']);
    } catch (PDOException $e) {
        error_log('Group category delete: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}
