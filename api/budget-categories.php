<?php
/**
 * Budget Categories API: list, create, update, delete (by authenticated user).
 * GET    = list all categories for current user
 * POST   = create category
 * PUT    = update category (id in body)
 * DELETE = delete category (id in body)
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

$userId = (int) $_SESSION['user_id'];

switch ($method) {
    case 'GET':
        listCategories($dbh, $userId);
        break;
    case 'POST':
        createCategory($dbh, $userId, $input);
        break;
    case 'PUT':
        updateCategory($dbh, $userId, $input);
        break;
    case 'DELETE':
        deleteCategory($dbh, $userId, $input);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function listCategories($dbh, $userId) {
    try {
        $stmt = $dbh->prepare("
            SELECT id, user_id, name, planned_amount, description, sort_order, created_at, updated_at
            FROM budget_categories
            WHERE user_id = :user_id
            ORDER BY sort_order ASC, name ASC
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode(['message' => 'OK', 'data' => $rows]);
    } catch (PDOException $e) {
        error_log('Budget categories list error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function createCategory($dbh, $userId, $input) {
    $name = isset($input['name']) ? trim((string) $input['name']) : '';
    if ($name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Category name is required']);
        return;
    }
    $planned = isset($input['planned_amount']) ? (float) $input['planned_amount'] : 0;
    if ($planned < 0) $planned = 0;
    $description = isset($input['description']) ? trim((string) $input['description']) : null;
    $sortOrder = isset($input['sort_order']) ? (int) $input['sort_order'] : 0;

    try {
        $stmt = $dbh->prepare("
            INSERT INTO budget_categories (user_id, name, planned_amount, description, sort_order)
            VALUES (:user_id, :name, :planned_amount, :description, :sort_order)
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':planned_amount', $planned, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description ?: null, PDO::PARAM_STR);
        $stmt->bindValue(':sort_order', $sortOrder, PDO::PARAM_INT);
        $stmt->execute();

        $id = (int) $dbh->lastInsertId();
        $fetch = $dbh->prepare("SELECT id, user_id, name, planned_amount, description, sort_order, created_at, updated_at FROM budget_categories WHERE id = :id LIMIT 1");
        $fetch->bindValue(':id', $id, PDO::PARAM_INT);
        $fetch->execute();
        $row = $fetch->fetch(PDO::FETCH_OBJ);
        http_response_code(201);
        echo json_encode(['message' => 'Category created', 'data' => $row]);
    } catch (PDOException $e) {
        error_log('Budget category create error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function updateCategory($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Category id is required']);
        return;
    }

    $name = isset($input['name']) ? trim((string) $input['name']) : null;
    $planned = isset($input['planned_amount']) ? (float) $input['planned_amount'] : null;
    $description = array_key_exists('description', $input) ? trim((string) $input['description']) : null;
    $sortOrder = isset($input['sort_order']) ? (int) $input['sort_order'] : null;

    try {
        $check = $dbh->prepare("SELECT id FROM budget_categories WHERE id = :id AND user_id = :user_id LIMIT 1");
        $check->bindValue(':id', $id, PDO::PARAM_INT);
        $check->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $check->execute();
        if (!$check->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            return;
        }

        $updates = [];
        $params = [':id' => $id, ':user_id' => $userId];
        if ($name !== null) {
            $updates[] = 'name = :name';
            $params[':name'] = $name;
        }
        if ($planned !== null) {
            $updates[] = 'planned_amount = :planned_amount';
            $params[':planned_amount'] = $planned < 0 ? 0 : $planned;
        }
        if (array_key_exists('description', $input)) {
            $updates[] = 'description = :description';
            $params[':description'] = $description ?: null;
        }
        if ($sortOrder !== null) {
            $updates[] = 'sort_order = :sort_order';
            $params[':sort_order'] = $sortOrder;
        }

        if (count($updates) === 0) {
            $fetch = $dbh->prepare("SELECT id, user_id, name, planned_amount, description, sort_order, created_at, updated_at FROM budget_categories WHERE id = :id LIMIT 1");
            $fetch->bindValue(':id', $id, PDO::PARAM_INT);
            $fetch->execute();
            $row = $fetch->fetch(PDO::FETCH_OBJ);
            echo json_encode(['message' => 'No changes', 'data' => $row]);
            return;
        }

        $sql = "UPDATE budget_categories SET " . implode(', ', $updates) . " WHERE id = :id AND user_id = :user_id";
        $stmt = $dbh->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        $fetch = $dbh->prepare("SELECT id, user_id, name, planned_amount, description, sort_order, created_at, updated_at FROM budget_categories WHERE id = :id LIMIT 1");
        $fetch->bindValue(':id', $id, PDO::PARAM_INT);
        $fetch->execute();
        $row = $fetch->fetch(PDO::FETCH_OBJ);
        echo json_encode(['message' => 'Category updated', 'data' => $row]);
    } catch (PDOException $e) {
        error_log('Budget category update error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function deleteCategory($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Category id is required']);
        return;
    }

    try {
        $stmt = $dbh->prepare("DELETE FROM budget_categories WHERE id = :id AND user_id = :user_id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            return;
        }
        echo json_encode(['message' => 'Category deleted']);
    } catch (PDOException $e) {
        error_log('Budget category delete error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}
