<?php
/**
 * Members API: list, create, update, delete. Each member belongs to a group_category_id.
 */
include("connect/header.php");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$scopeUserId = isset($scopeUserId) ? (int) $scopeUserId : $userId;

switch ($method) {
    case 'GET': listMembers($dbh, $scopeUserId); break;
    case 'POST': createMember($dbh, $scopeUserId, $input); break;
    case 'PUT': updateMember($dbh, $scopeUserId, $input); break;
    case 'DELETE': deleteMember($dbh, $scopeUserId, $input); break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function ensureCategoryBelongsToUser($dbh, $categoryId, $userId) {
    $stmt = $dbh->prepare("SELECT id FROM group_categories WHERE id = :id AND user_id = :user_id LIMIT 1");
    $stmt->bindValue(':id', (int) $categoryId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_OBJ) ? true : false;
}

function listMembers($dbh, $userId) {
    try {
        $groupCategoryId = isset($_GET['group_category_id']) ? (int) $_GET['group_category_id'] : 0;
        $sql = "
            SELECT m.id, m.user_id, m.group_category_id, m.name, m.email, m.phone, m.created_at, m.updated_at,
                   g.name AS group_category_name
            FROM members m
            INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :user_id
        ";
        $params = [':user_id' => $userId];
        if ($groupCategoryId > 0) {
            $sql .= " WHERE m.group_category_id = :group_category_id";
            $params[':group_category_id'] = $groupCategoryId;
        }
        $sql .= " ORDER BY m.name ASC";
        $stmt = $dbh->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();
        echo json_encode(['message' => 'OK', 'data' => $stmt->fetchAll(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) {
        error_log('Members list: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function createMember($dbh, $userId, $input) {
    $categoryId = isset($input['group_category_id']) ? (int) $input['group_category_id'] : 0;
    if ($categoryId <= 0 || !ensureCategoryBelongsToUser($dbh, $categoryId, $userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid group category is required']);
        return;
    }
    $name = isset($input['name']) ? trim((string) $input['name']) : '';
    if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Member name is required']);
        return;
    }
    $email = isset($input['email']) ? trim((string) $input['email']) : null;
    $phone = isset($input['phone']) ? trim((string) $input['phone']) : null;
    try {
        $stmt = $dbh->prepare("INSERT INTO members (user_id, group_category_id, name, email, phone) VALUES (:user_id, :group_category_id, :name, :email, :phone)");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':group_category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email ?: null, PDO::PARAM_STR);
        $stmt->bindValue(':phone', $phone ?: null, PDO::PARAM_STR);
        $stmt->execute();
        $id = (int) $dbh->lastInsertId();
        $f = $dbh->prepare("SELECT id, user_id, group_category_id, name, email, phone, created_at, updated_at FROM members WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        http_response_code(201);
        echo json_encode(['message' => 'Member created', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) {
        error_log('Member create: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function updateMember($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Member id is required']); return; }
    $categoryId = isset($input['group_category_id']) ? (int) $input['group_category_id'] : null;
    if ($categoryId !== null && ($categoryId <= 0 || !ensureCategoryBelongsToUser($dbh, $categoryId, $userId))) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid group category is required']);
        return;
    }
    $name = isset($input['name']) ? trim((string) $input['name']) : null;
    $email = array_key_exists('email', $input) ? trim((string) $input['email']) : null;
    $phone = array_key_exists('phone', $input) ? trim((string) $input['phone']) : null;
    if ($name !== null && $name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Member name cannot be empty']);
        return;
    }
    try {
        $check = $dbh->prepare("SELECT m.id FROM members m INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :user_id WHERE m.id = :id LIMIT 1");
        $check->bindValue(':id', $id, PDO::PARAM_INT);
        $check->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $check->execute();
        if (!$check->fetch()) { http_response_code(404); echo json_encode(['error' => 'Member not found']); return; }
        $updates = [];
        $params = [':id' => $id];
        if ($name !== null) { $updates[] = 'name = :name'; $params[':name'] = $name; }
        if ($categoryId !== null) { $updates[] = 'group_category_id = :group_category_id'; $params[':group_category_id'] = $categoryId; }
        if (array_key_exists('email', $input)) { $updates[] = 'email = :email'; $params[':email'] = $email ?: null; }
        if (array_key_exists('phone', $input)) { $updates[] = 'phone = :phone'; $params[':phone'] = $phone ?: null; }
        if (count($updates) === 0) {
            $f = $dbh->prepare("SELECT id, user_id, group_category_id, name, email, phone, created_at, updated_at FROM members WHERE id = :id LIMIT 1");
            $f->bindValue(':id', $id, PDO::PARAM_INT);
            $f->execute();
            echo json_encode(['message' => 'No changes', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
            return;
        }
        $sql = "UPDATE members SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $dbh->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, $v === null ? PDO::PARAM_NULL : (is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR));
        }
        $stmt->execute();
        $f = $dbh->prepare("SELECT id, user_id, group_category_id, name, email, phone, created_at, updated_at FROM members WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        echo json_encode(['message' => 'Member updated', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) {
        error_log('Member update: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred', 'detail' => $e->getMessage()]);
    }
}

function deleteMember($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Member id is required']); return; }
    try {
        $stmt = $dbh->prepare("DELETE m FROM members m INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :user_id WHERE m.id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() === 0) { http_response_code(404); echo json_encode(['error' => 'Member not found']); return; }
        echo json_encode(['message' => 'Member deleted']);
    } catch (PDOException $e) {
        error_log('Member delete: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}
