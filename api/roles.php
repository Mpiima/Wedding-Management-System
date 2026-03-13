<?php
/**
 * Roles API: list, create, update, delete (by authenticated user).
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
    case 'GET': listRoles($dbh, $scopeUserId); break;
    case 'POST': createRole($dbh, $scopeUserId, $input); break;
    case 'PUT': updateRole($dbh, $scopeUserId, $input); break;
    case 'DELETE': deleteRole($dbh, $scopeUserId, $input); break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function listRoles($dbh, $userId) {
    try {
        $stmt = $dbh->prepare("SELECT id, user_id, name, description, sort_order, permissions, created_at, updated_at FROM roles WHERE user_id = :user_id ORDER BY sort_order ASC, name ASC");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        foreach ($rows as $r) {
            if (isset($r->permissions) && is_string($r->permissions)) {
                $r->permissions = json_decode($r->permissions, true);
                if (!is_array($r->permissions)) $r->permissions = [];
            }
        }
        echo json_encode(['message' => 'OK', 'data' => $rows]);
    } catch (PDOException $e) {
        error_log('Roles list: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function createRole($dbh, $userId, $input) {
    $name = isset($input['name']) ? trim((string) $input['name']) : '';
    if ($name === '') { http_response_code(400); echo json_encode(['error' => 'Role name is required']); return; }
    $description = isset($input['description']) ? trim((string) $input['description']) : null;
    $sortOrder = isset($input['sort_order']) ? (int) $input['sort_order'] : 0;
    $permissions = isset($input['permissions']) && is_array($input['permissions']) ? $input['permissions'] : [];
    $permissionsJson = json_encode(array_values(array_filter(array_map('trim', $permissions), function ($p) { return $p !== ''; })));
    try {
        $stmt = $dbh->prepare("INSERT INTO roles (user_id, name, description, sort_order, permissions) VALUES (:user_id, :name, :description, :sort_order, :permissions)");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description ?: null, PDO::PARAM_STR);
        $stmt->bindValue(':sort_order', $sortOrder, PDO::PARAM_INT);
        $stmt->bindValue(':permissions', $permissionsJson, PDO::PARAM_STR);
        $stmt->execute();
        $id = (int) $dbh->lastInsertId();
        $f = $dbh->prepare("SELECT id, user_id, name, description, sort_order, permissions, created_at, updated_at FROM roles WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        $row = $f->fetch(PDO::FETCH_OBJ);
        if ($row && isset($row->permissions)) { $row->permissions = json_decode($row->permissions, true) ?: []; }
        http_response_code(201);
        echo json_encode(['message' => 'Role created', 'data' => $row]);
    } catch (PDOException $e) {
        error_log('Role create: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function updateRole($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Role id is required']); return; }
    $name = isset($input['name']) ? trim((string) $input['name']) : null;
    $description = array_key_exists('description', $input) ? trim((string) $input['description']) : null;
    $sortOrder = isset($input['sort_order']) ? (int) $input['sort_order'] : null;
    $permissions = array_key_exists('permissions', $input) && is_array($input['permissions']) ? $input['permissions'] : null;
    try {
        $check = $dbh->prepare("SELECT id FROM roles WHERE id = :id AND user_id = :user_id LIMIT 1");
        $check->bindValue(':id', $id, PDO::PARAM_INT);
        $check->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $check->execute();
        if (!$check->fetch()) { http_response_code(404); echo json_encode(['error' => 'Role not found']); return; }
        $updates = [];
        $params = [':id' => $id, ':user_id' => $userId];
        if ($name !== null) { $updates[] = 'name = :name'; $params[':name'] = $name; }
        if (array_key_exists('description', $input)) { $updates[] = 'description = :description'; $params[':description'] = $description ?: null; }
        if ($sortOrder !== null) { $updates[] = 'sort_order = :sort_order'; $params[':sort_order'] = $sortOrder; }
        if ($permissions !== null) { $updates[] = 'permissions = :permissions'; $params[':permissions'] = json_encode(array_values(array_filter(array_map('trim', $permissions), function ($p) { return $p !== ''; }))); }
        if (count($updates) === 0) {
            $f = $dbh->prepare("SELECT id, user_id, name, description, sort_order, permissions, created_at, updated_at FROM roles WHERE id = :id LIMIT 1");
            $f->bindValue(':id', $id, PDO::PARAM_INT);
            $f->execute();
            $row = $f->fetch(PDO::FETCH_OBJ);
            if ($row && isset($row->permissions)) { $row->permissions = json_decode($row->permissions, true) ?: []; }
            echo json_encode(['message' => 'No changes', 'data' => $row]);
            return;
        }
        $sql = "UPDATE roles SET " . implode(', ', $updates) . " WHERE id = :id AND user_id = :user_id";
        $stmt = $dbh->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR); }
        $stmt->execute();
        $f = $dbh->prepare("SELECT id, user_id, name, description, sort_order, permissions, created_at, updated_at FROM roles WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        $row = $f->fetch(PDO::FETCH_OBJ);
        if ($row && isset($row->permissions)) { $row->permissions = json_decode($row->permissions, true) ?: []; }
        echo json_encode(['message' => 'Role updated', 'data' => $row]);
    } catch (PDOException $e) {
        error_log('Role update: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function deleteRole($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Role id is required']); return; }
    try {
        $stmt = $dbh->prepare("DELETE FROM roles WHERE id = :id AND user_id = :user_id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() === 0) { http_response_code(404); echo json_encode(['error' => 'Role not found']); return; }
        echo json_encode(['message' => 'Role deleted']);
    } catch (PDOException $e) {
        error_log('Role delete: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}
