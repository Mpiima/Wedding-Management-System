<?php
/**
 * Vendor contracts API: list, create, update, delete. User-scoped; vendor_id required.
 */
include("connect/header.php");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
$userId = (int) $_SESSION['user_id'];

switch ($method) {
    case 'GET': listContracts($dbh, $userId); break;
    case 'POST': createContract($dbh, $userId, $input); break;
    case 'PUT': updateContract($dbh, $userId, $input); break;
    case 'DELETE': deleteContract($dbh, $userId, $input); break;
    default: http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
}

function ensureVendorBelongsToUser($dbh, $vendorId, $userId) {
    $s = $dbh->prepare("SELECT id FROM vendors WHERE id = :id AND user_id = :uid LIMIT 1");
    $s->bindValue(':id', (int) $vendorId, PDO::PARAM_INT);
    $s->bindValue(':uid', $userId, PDO::PARAM_INT);
    $s->execute();
    return $s->fetch(PDO::FETCH_OBJ) ? true : false;
}

function listContracts($dbh, $userId) {
    try {
        $stmt = $dbh->prepare("
            SELECT c.id, c.user_id, c.vendor_id, c.title, c.description, c.contract_date, c.amount, c.paid_amount, c.status, c.notes, c.created_at, c.updated_at,
                   v.name AS vendor_name
            FROM vendor_contracts c
            INNER JOIN vendors v ON v.id = c.vendor_id AND v.user_id = :uid
            ORDER BY c.contract_date DESC, c.created_at DESC
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['message' => 'OK', 'data' => $stmt->fetchAll(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) { error_log('Vendor contracts list: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function createContract($dbh, $userId, $input) {
    $vendorId = isset($input['vendor_id']) ? (int) $input['vendor_id'] : 0;
    if ($vendorId <= 0 || !ensureVendorBelongsToUser($dbh, $vendorId, $userId)) { http_response_code(400); echo json_encode(['error' => 'Valid vendor required']); return; }
    $title = isset($input['title']) ? trim((string) $input['title']) : '';
    if ($title === '') { http_response_code(400); echo json_encode(['error' => 'Title required']); return; }
    $description = isset($input['description']) ? trim((string) $input['description']) : null;
    $contractDate = isset($input['contract_date']) ? trim((string) $input['contract_date']) : null;
    if ($contractDate) { $d = date_create($contractDate); if (!$d) { http_response_code(400); echo json_encode(['error' => 'Invalid date']); return; } $contractDate = $d->format('Y-m-d'); }
    $amount = isset($input['amount']) ? (float) $input['amount'] : 0;
    if ($amount < 0) $amount = 0;
    $paidAmount = isset($input['paid_amount']) ? (float) $input['paid_amount'] : 0;
    if ($paidAmount < 0) $paidAmount = 0;
    $status = isset($input['status']) ? trim((string) $input['status']) : 'Draft';
    if (!in_array($status, ['Draft', 'Signed', 'Completed'], true)) $status = 'Draft';
    $notes = isset($input['notes']) ? trim((string) $input['notes']) : null;
    try {
        $stmt = $dbh->prepare("INSERT INTO vendor_contracts (user_id, vendor_id, title, description, contract_date, amount, paid_amount, status, notes) VALUES (:uid, :vendor_id, :title, :description, :contract_date, :amount, :paid_amount, :status, :notes)");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':vendor_id', $vendorId, PDO::PARAM_INT);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':contract_date', $contractDate, PDO::PARAM_STR);
        $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindValue(':paid_amount', $paidAmount, PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);
        $stmt->execute();
        $id = (int) $dbh->lastInsertId();
        $f = $dbh->prepare("SELECT c.id, c.vendor_id, c.title, c.description, c.contract_date, c.amount, c.paid_amount, c.status, c.notes, c.created_at, v.name AS vendor_name FROM vendor_contracts c INNER JOIN vendors v ON v.id = c.vendor_id WHERE c.id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        http_response_code(201);
        echo json_encode(['message' => 'Contract created', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) { error_log('Vendor contract create: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function updateContract($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Id required']); return; }
    $check = $dbh->prepare("SELECT id FROM vendor_contracts WHERE id = :id AND user_id = :uid LIMIT 1");
    $check->bindValue(':id', $id, PDO::PARAM_INT);
    $check->bindValue(':uid', $userId, PDO::PARAM_INT);
    $check->execute();
    if (!$check->fetch()) { http_response_code(404); echo json_encode(['error' => 'Not found']); return; }
    $updates = []; $params = [':id' => $id];
    if (array_key_exists('vendor_id', $input)) { $vid = (int) $input['vendor_id']; if ($vid > 0 && ensureVendorBelongsToUser($dbh, $vid, $userId)) { $updates[] = 'vendor_id = :vendor_id'; $params[':vendor_id'] = $vid; } }
    if (array_key_exists('title', $input)) { $updates[] = 'title = :title'; $params[':title'] = trim((string) $input['title']); }
    if (array_key_exists('description', $input)) { $updates[] = 'description = :description'; $params[':description'] = trim((string) $input['description']) ?: null; }
    if (array_key_exists('contract_date', $input)) { $d = !empty($input['contract_date']) && date_create(trim($input['contract_date'])) ? date_create(trim($input['contract_date']))->format('Y-m-d') : null; $updates[] = 'contract_date = :contract_date'; $params[':contract_date'] = $d; }
    if (array_key_exists('amount', $input)) { $updates[] = 'amount = :amount'; $params[':amount'] = max(0, (float) $input['amount']); }
    if (array_key_exists('paid_amount', $input)) { $updates[] = 'paid_amount = :paid_amount'; $params[':paid_amount'] = max(0, (float) $input['paid_amount']); }
    if (array_key_exists('status', $input)) { $st = trim((string) $input['status']); if (in_array($st, ['Draft', 'Signed', 'Completed'], true)) { $updates[] = 'status = :status'; $params[':status'] = $st; } }
    if (array_key_exists('notes', $input)) { $updates[] = 'notes = :notes'; $params[':notes'] = trim((string) $input['notes']) ?: null; }
    if (count($updates) === 0) {
        $f = $dbh->prepare("SELECT c.id, c.vendor_id, c.title, c.description, c.contract_date, c.amount, c.paid_amount, c.status, c.notes, c.created_at, v.name AS vendor_name FROM vendor_contracts c INNER JOIN vendors v ON v.id = c.vendor_id WHERE c.id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        echo json_encode(['message' => 'No changes', 'data' => $f->fetch(PDO::FETCH_OBJ)]); return;
    }
    $sql = "UPDATE vendor_contracts SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    $stmt->execute();
    $f = $dbh->prepare("SELECT c.id, c.vendor_id, c.title, c.description, c.contract_date, c.amount, c.paid_amount, c.status, c.notes, c.created_at, v.name AS vendor_name FROM vendor_contracts c INNER JOIN vendors v ON v.id = c.vendor_id WHERE c.id = :id LIMIT 1");
    $f->bindValue(':id', $id, PDO::PARAM_INT);
    $f->execute();
    echo json_encode(['message' => 'Updated', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
}

function deleteContract($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Id required']); return; }
    $stmt = $dbh->prepare("DELETE FROM vendor_contracts WHERE id = :id AND user_id = :uid");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() === 0) { http_response_code(404); echo json_encode(['error' => 'Not found']); return; }
    echo json_encode(['message' => 'Deleted']);
}
