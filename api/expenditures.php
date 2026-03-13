<?php
/**
 * Expenditures API: list, create, update, delete. Item, amount, description, date.
 */
include("connect/header.php");
include_once(__DIR__ . '/lib/EmailHelper.php');
include_once(__DIR__ . '/lib/EmailTemplates.php');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
$userId = (int) $_SESSION['user_id'];
$scopeUserId = isset($scopeUserId) ? (int) $scopeUserId : $userId;

switch ($method) {
    case 'GET':
        if (!wmis_has_permission($dbh, 'expenditures.view')) {
            http_response_code(403); echo json_encode(['error' => 'You do not have permission to view expenditures']); exit;
        }
        listExpenditures($dbh, $scopeUserId);
        break;
    case 'POST':
        if (!wmis_has_permission($dbh, 'expenditures.add')) {
            http_response_code(403); echo json_encode(['error' => 'You do not have permission to add expenditures']); exit;
        }
        createExpenditure($dbh, $scopeUserId, $input);
        break;
    case 'PUT':
        if (!wmis_has_permission($dbh, 'expenditures.edit')) {
            http_response_code(403); echo json_encode(['error' => 'You do not have permission to edit expenditures']); exit;
        }
        updateExpenditure($dbh, $scopeUserId, $input);
        break;
    case 'DELETE':
        if (!wmis_has_permission($dbh, 'expenditures.delete')) {
            http_response_code(403); echo json_encode(['error' => 'You do not have permission to delete expenditures']); exit;
        }
        deleteExpenditure($dbh, $scopeUserId, $input);
        break;
    default: http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
}

function listExpenditures($dbh, $userId) {
    try {
        $stmt = $dbh->prepare("SELECT id, user_id, item, amount, description, expenditure_date, created_at FROM expenditures WHERE user_id = :uid ORDER BY expenditure_date DESC, created_at DESC");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['message' => 'OK', 'data' => $stmt->fetchAll(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) { error_log('Expenditures list: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function createExpenditure($dbh, $userId, $input) {
    $item = isset($input['item']) ? trim((string) $input['item']) : '';
    if ($item === '') { http_response_code(400); echo json_encode(['error' => 'Item required']); return; }
    $amount = isset($input['amount']) ? (float) $input['amount'] : 0;
    if ($amount < 0) $amount = 0;
    $description = isset($input['description']) ? trim((string) $input['description']) : null;
    $date = isset($input['expenditure_date']) ? trim((string) $input['expenditure_date']) : date('Y-m-d');
    $d = date_create($date);
    if (!$d) { http_response_code(400); echo json_encode(['error' => 'Invalid date']); return; }
    $date = $d->format('Y-m-d');
    try {
        $stmt = $dbh->prepare("INSERT INTO expenditures (user_id, item, amount, description, expenditure_date) VALUES (:uid, :item, :amount, :description, :expenditure_date)");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':item', $item, PDO::PARAM_STR);
        $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':expenditure_date', $date, PDO::PARAM_STR);
        $stmt->execute();
        $id = (int) $dbh->lastInsertId();
        $f = $dbh->prepare("SELECT id, user_id, item, amount, description, expenditure_date, created_at FROM expenditures WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        $data = $f->fetch(PDO::FETCH_OBJ);
        try {
            if (function_exists('wmis_send_email') && function_exists('wmis_email_expenditure_made')) {
                $desc = ($data->description !== null && trim($data->description) !== '') ? $data->description : $data->item;
                $stmtRoles = $dbh->prepare("
                    SELECT DISTINCT m.id, m.name, m.email FROM members m
                    INNER JOIN member_roles mr ON mr.member_id = m.id
                    INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid
                    WHERE TRIM(COALESCE(m.email, '')) != '' AND m.email IS NOT NULL
                ");
                $stmtRoles->bindValue(':uid', $userId, PDO::PARAM_INT);
                $stmtRoles->execute();
                while ($row = $stmtRoles->fetch(PDO::FETCH_OBJ)) {
                    if (filter_var(trim($row->email), FILTER_VALIDATE_EMAIL)) {
                        $tpl = wmis_email_expenditure_made($row->name, $desc, $data->amount, $data->expenditure_date);
                        wmis_send_email($dbh, trim($row->email), $tpl['subject'], $tpl['body']);
                    }
                }
            }
        } catch (Exception $e) { error_log('Expenditure email: ' . $e->getMessage()); }
        http_response_code(201);
        echo json_encode(['message' => 'Expenditure created', 'data' => $data]);
    } catch (PDOException $e) { error_log('Expenditure create: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function updateExpenditure($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Id required']); return; }
    $check = $dbh->prepare("SELECT id FROM expenditures WHERE id = :id AND user_id = :uid LIMIT 1");
    $check->bindValue(':id', $id, PDO::PARAM_INT);
    $check->bindValue(':uid', $userId, PDO::PARAM_INT);
    $check->execute();
    if (!$check->fetch()) { http_response_code(404); echo json_encode(['error' => 'Not found']); return; }
    $updates = []; $params = [':id' => $id];
    if (array_key_exists('item', $input)) { $updates[] = 'item = :item'; $params[':item'] = trim((string) $input['item']); }
    if (array_key_exists('amount', $input)) { $updates[] = 'amount = :amount'; $params[':amount'] = max(0, (float) $input['amount']); }
    if (array_key_exists('description', $input)) { $updates[] = 'description = :description'; $params[':description'] = trim((string) $input['description']) ?: null; }
    if (array_key_exists('expenditure_date', $input)) { $d = date_create(trim($input['expenditure_date'])); if ($d) { $updates[] = 'expenditure_date = :expenditure_date'; $params[':expenditure_date'] = $d->format('Y-m-d'); } }
    if (count($updates) === 0) {
        $f = $dbh->prepare("SELECT id, user_id, item, amount, description, expenditure_date, created_at FROM expenditures WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        echo json_encode(['message' => 'No changes', 'data' => $f->fetch(PDO::FETCH_OBJ)]); return;
    }
    $sql = "UPDATE expenditures SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    $stmt->execute();
    $f = $dbh->prepare("SELECT id, user_id, item, amount, description, expenditure_date, created_at FROM expenditures WHERE id = :id LIMIT 1");
    $f->bindValue(':id', $id, PDO::PARAM_INT);
    $f->execute();
    echo json_encode(['message' => 'Updated', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
}

function deleteExpenditure($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Id required']); return; }
    $stmt = $dbh->prepare("DELETE FROM expenditures WHERE id = :id AND user_id = :uid");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() === 0) { http_response_code(404); echo json_encode(['error' => 'Not found']); return; }
    echo json_encode(['message' => 'Deleted']);
}
