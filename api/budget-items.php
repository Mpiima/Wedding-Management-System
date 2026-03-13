<?php
/**
 * Budget Items API: list, create, update, delete. User-scoped via category ownership.
 * GET    = list all items for current user (optional ?category_id=)
 * POST   = create item (cost = quantity * unit_amount)
 * PUT    = update item (id in body, recompute cost)
 * DELETE = delete item (id in body)
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
$scopeUserId = isset($scopeUserId) ? (int) $scopeUserId : $userId;

switch ($method) {
    case 'GET':
        if (!wmis_has_permission($dbh, 'budget.view')) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to view budget']);
            exit;
        }
        listItems($dbh, $scopeUserId, $_GET['category_id'] ?? null);
        break;
    case 'POST':
        if (!wmis_has_permission($dbh, 'budget.add')) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to add budget']);
            exit;
        }
        createItem($dbh, $scopeUserId, $input);
        break;
    case 'PUT':
        if (!wmis_has_permission($dbh, 'budget.edit')) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to edit budget']);
            exit;
        }
        updateItem($dbh, $scopeUserId, $input);
        break;
    case 'DELETE':
        if (!wmis_has_permission($dbh, 'budget.delete')) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to delete budget']);
            exit;
        }
        deleteItem($dbh, $scopeUserId, $input);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function ensureCategoryBelongsToUser($dbh, $categoryId, $userId) {
    $stmt = $dbh->prepare("SELECT id FROM budget_categories WHERE id = :id AND user_id = :user_id LIMIT 1");
    $stmt->bindValue(':id', (int) $categoryId, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_OBJ) ? true : false;
}

function listItems($dbh, $userId, $categoryId) {
    try {
        $categoryId = $categoryId !== null && $categoryId !== '' ? (int) $categoryId : null;
        if ($categoryId !== null && !ensureCategoryBelongsToUser($dbh, $categoryId, $userId)) {
            http_response_code(404);
            echo json_encode(['error' => 'Category not found']);
            return;
        }

        if ($categoryId !== null) {
            $stmt = $dbh->prepare("
                SELECT id, budget_category_id, item_name, quantity, unit_amount, cost, status, covered_type, created_at, updated_at
                FROM budget_items
                WHERE budget_category_id = :category_id
                ORDER BY item_name ASC
            ");
            $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $dbh->prepare("
                SELECT i.id, i.budget_category_id, i.item_name, i.quantity, i.unit_amount, i.cost, i.status, i.covered_type, i.created_at, i.updated_at
                FROM budget_items i
                INNER JOIN budget_categories c ON c.id = i.budget_category_id AND c.user_id = :user_id
                ORDER BY c.sort_order ASC, c.name ASC, i.item_name ASC
            ");
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
        }
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode(['message' => 'OK', 'data' => $rows]);
    } catch (PDOException $e) {
        error_log('Budget items list error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function createItem($dbh, $userId, $input) {
    $categoryId = isset($input['budget_category_id']) ? (int) $input['budget_category_id'] : 0;
    if ($categoryId <= 0 || !ensureCategoryBelongsToUser($dbh, $categoryId, $userId)) {
        http_response_code(400);
        echo json_encode(['error' => 'Valid category is required']);
        return;
    }

    $itemName = isset($input['item_name']) ? trim((string) $input['item_name']) : '';
    if ($itemName === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Item name is required']);
        return;
    }

    $quantity = isset($input['quantity']) ? (float) $input['quantity'] : 1;
    if ($quantity < 0) $quantity = 0;
    $unitAmount = isset($input['unit_amount']) ? (float) $input['unit_amount'] : 0;
    if ($unitAmount < 0) $unitAmount = 0;
    $cost = round($quantity * $unitAmount, 2);

    $status = isset($input['status']) ? strtoupper(trim((string) $input['status'])) : 'NOT_COVERED';
    if (!in_array($status, ['COVERED', 'NOT_COVERED'], true)) $status = 'NOT_COVERED';

    $coveredType = null;
    if ($status === 'COVERED') {
        $ct = isset($input['covered_type']) ? strtoupper(trim((string) $input['covered_type'])) : '';
        if (in_array($ct, ['DEFAULT', 'FROM_CONTRIBUTIONS'], true)) {
            $coveredType = $ct;
        } else {
            $coveredType = 'DEFAULT';
        }
    }

    try {
        $stmt = $dbh->prepare("
            INSERT INTO budget_items (budget_category_id, item_name, quantity, unit_amount, cost, status, covered_type)
            VALUES (:budget_category_id, :item_name, :quantity, :unit_amount, :cost, :status, :covered_type)
        ");
        $stmt->bindValue(':budget_category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':item_name', $itemName, PDO::PARAM_STR);
        $stmt->bindValue(':quantity', $quantity, PDO::PARAM_STR);
        $stmt->bindValue(':unit_amount', $unitAmount, PDO::PARAM_STR);
        $stmt->bindValue(':cost', $cost, PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':covered_type', $coveredType, PDO::PARAM_STR);
        $stmt->execute();

        $id = (int) $dbh->lastInsertId();
        $fetch = $dbh->prepare("SELECT id, budget_category_id, item_name, quantity, unit_amount, cost, status, covered_type, created_at, updated_at FROM budget_items WHERE id = :id LIMIT 1");
        $fetch->bindValue(':id', $id, PDO::PARAM_INT);
        $fetch->execute();
        $row = $fetch->fetch(PDO::FETCH_OBJ);
        http_response_code(201);
        echo json_encode(['message' => 'Item created', 'data' => $row]);
    } catch (PDOException $e) {
        error_log('Budget item create error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function updateItem($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Item id is required']);
        return;
    }

    try {
        $stmt = $dbh->prepare("
            SELECT i.id, i.budget_category_id FROM budget_items i
            INNER JOIN budget_categories c ON c.id = i.budget_category_id AND c.user_id = :user_id
            WHERE i.id = :id LIMIT 1
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_OBJ);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['error' => 'Item not found']);
            return;
        }

        $updates = [];
        $params = [':id' => $id];

        if (array_key_exists('item_name', $input)) {
            $name = trim((string) $input['item_name']);
            $updates[] = 'item_name = :item_name';
            $params[':item_name'] = $name;
        }
        if (array_key_exists('quantity', $input)) {
            $qty = (float) $input['quantity'];
            if ($qty < 0) $qty = 0;
            $updates[] = 'quantity = :quantity';
            $params[':quantity'] = $qty;
        }
        if (array_key_exists('unit_amount', $input)) {
            $ua = (float) $input['unit_amount'];
            if ($ua < 0) $ua = 0;
            $updates[] = 'unit_amount = :unit_amount';
            $params[':unit_amount'] = $ua;
        }
        if (array_key_exists('status', $input)) {
            $st = strtoupper(trim((string) $input['status']));
            if (!in_array($st, ['COVERED', 'NOT_COVERED'], true)) $st = 'NOT_COVERED';
            $updates[] = 'status = :status';
            $params[':status'] = $st;

            if ($st === 'COVERED') {
                $ct = array_key_exists('covered_type', $input) ? strtoupper(trim((string) $input['covered_type'])) : '';
                $coveredType = in_array($ct, ['DEFAULT', 'FROM_CONTRIBUTIONS'], true) ? $ct : 'DEFAULT';
                $updates[] = 'covered_type = :covered_type';
                $params[':covered_type'] = $coveredType;
            } else {
                $updates[] = 'covered_type = :covered_type';
                $params[':covered_type'] = null;
            }
        }

        if (count($updates) > 0) {
            $fetchCurrent = $dbh->prepare("SELECT quantity, unit_amount FROM budget_items WHERE id = :id LIMIT 1");
            $fetchCurrent->bindValue(':id', $id, PDO::PARAM_INT);
            $fetchCurrent->execute();
            $cur = $fetchCurrent->fetch(PDO::FETCH_OBJ);
            $qty = array_key_exists('quantity', $input) ? (float) $input['quantity'] : (float) $cur->quantity;
            if ($qty < 0) $qty = 0;
            $ua = array_key_exists('unit_amount', $input) ? (float) $input['unit_amount'] : (float) $cur->unit_amount;
            if ($ua < 0) $ua = 0;
            $cost = round($qty * $ua, 2);
            $updates[] = 'cost = :cost';
            $params[':cost'] = $cost;
        }

        if (count($updates) === 0) {
            $fetch = $dbh->prepare("SELECT id, budget_category_id, item_name, quantity, unit_amount, cost, status, covered_type, created_at, updated_at FROM budget_items WHERE id = :id LIMIT 1");
            $fetch->bindValue(':id', $id, PDO::PARAM_INT);
            $fetch->execute();
            $row = $fetch->fetch(PDO::FETCH_OBJ);
            echo json_encode(['message' => 'No changes', 'data' => $row]);
            return;
        }

        $sql = "UPDATE budget_items SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $dbh->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        $fetch = $dbh->prepare("SELECT id, budget_category_id, item_name, quantity, unit_amount, cost, status, covered_type, created_at, updated_at FROM budget_items WHERE id = :id LIMIT 1");
        $fetch->bindValue(':id', $id, PDO::PARAM_INT);
        $fetch->execute();
        $row = $fetch->fetch(PDO::FETCH_OBJ);
        echo json_encode(['message' => 'Item updated', 'data' => $row]);
    } catch (PDOException $e) {
        error_log('Budget item update error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function deleteItem($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Item id is required']);
        return;
    }

    try {
        $stmt = $dbh->prepare("
            DELETE i FROM budget_items i
            INNER JOIN budget_categories c ON c.id = i.budget_category_id AND c.user_id = :user_id
            WHERE i.id = :id
        ");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Item not found']);
            return;
        }
        echo json_encode(['message' => 'Item deleted']);
    } catch (PDOException $e) {
        error_log('Budget item delete error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}
