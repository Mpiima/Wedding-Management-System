<?php
/**
 * Vendors API: list, create, update, delete. User-scoped.
 */
include("connect/header.php");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
$userId = (int) $_SESSION['user_id'];

switch ($method) {
    case 'GET': listVendors($dbh, $userId); break;
    case 'POST': createVendor($dbh, $userId, $input); break;
    case 'PUT': updateVendor($dbh, $userId, $input); break;
    case 'DELETE': deleteVendor($dbh, $userId, $input); break;
    default: http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
}

function listVendors($dbh, $userId) {
    try {
        $stmt = $dbh->prepare("SELECT id, user_id, name, category, contact_person, email, phone, status, notes, created_at, updated_at FROM vendors WHERE user_id = :uid ORDER BY name ASC");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['message' => 'OK', 'data' => $stmt->fetchAll(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) { error_log('Vendors list: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function createVendor($dbh, $userId, $input) {
    $name = isset($input['name']) ? trim((string) $input['name']) : '';
    if ($name === '') { http_response_code(400); echo json_encode(['error' => 'Name required']); return; }
    $category = isset($input['category']) ? trim((string) $input['category']) : null;
    $contactPerson = isset($input['contact_person']) ? trim((string) $input['contact_person']) : null;
    $email = isset($input['email']) ? trim((string) $input['email']) : null;
    $phone = isset($input['phone']) ? trim((string) $input['phone']) : null;
    $status = isset($input['status']) ? trim((string) $input['status']) : 'Considering';
    if (!in_array($status, ['Considering', 'Booked', 'Completed'], true)) $status = 'Considering';
    $notes = isset($input['notes']) ? trim((string) $input['notes']) : null;
    try {
        $stmt = $dbh->prepare("INSERT INTO vendors (user_id, name, category, contact_person, email, phone, status, notes) VALUES (:uid, :name, :category, :contact_person, :email, :phone, :status, :notes)");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':category', $category, PDO::PARAM_STR);
        $stmt->bindValue(':contact_person', $contactPerson, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
        $stmt->bindValue(':status', $status, PDO::PARAM_STR);
        $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);
        $stmt->execute();
        $id = (int) $dbh->lastInsertId();
        $f = $dbh->prepare("SELECT id, user_id, name, category, contact_person, email, phone, status, notes, created_at, updated_at FROM vendors WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        http_response_code(201);
        echo json_encode(['message' => 'Vendor created', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) { error_log('Vendor create: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function updateVendor($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Id required']); return; }
    $check = $dbh->prepare("SELECT id FROM vendors WHERE id = :id AND user_id = :uid LIMIT 1");
    $check->bindValue(':id', $id, PDO::PARAM_INT);
    $check->bindValue(':uid', $userId, PDO::PARAM_INT);
    $check->execute();
    if (!$check->fetch()) { http_response_code(404); echo json_encode(['error' => 'Not found']); return; }
    $updates = []; $params = [':id' => $id];
    if (array_key_exists('name', $input)) { $updates[] = 'name = :name'; $params[':name'] = trim((string) $input['name']); }
    if (array_key_exists('category', $input)) { $updates[] = 'category = :category'; $params[':category'] = trim((string) $input['category']) ?: null; }
    if (array_key_exists('contact_person', $input)) { $updates[] = 'contact_person = :contact_person'; $params[':contact_person'] = trim((string) $input['contact_person']) ?: null; }
    if (array_key_exists('email', $input)) { $updates[] = 'email = :email'; $params[':email'] = trim((string) $input['email']) ?: null; }
    if (array_key_exists('phone', $input)) { $updates[] = 'phone = :phone'; $params[':phone'] = trim((string) $input['phone']) ?: null; }
    if (array_key_exists('status', $input)) { $st = trim((string) $input['status']); if (in_array($st, ['Considering', 'Booked', 'Completed'], true)) { $updates[] = 'status = :status'; $params[':status'] = $st; } }
    if (array_key_exists('notes', $input)) { $updates[] = 'notes = :notes'; $params[':notes'] = trim((string) $input['notes']) ?: null; }
    if (count($updates) === 0) {
        $f = $dbh->prepare("SELECT id, user_id, name, category, contact_person, email, phone, status, notes, created_at, updated_at FROM vendors WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        echo json_encode(['message' => 'No changes', 'data' => $f->fetch(PDO::FETCH_OBJ)]); return;
    }
    $sql = "UPDATE vendors SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    $stmt->execute();
    $f = $dbh->prepare("SELECT id, user_id, name, category, contact_person, email, phone, status, notes, created_at, updated_at FROM vendors WHERE id = :id LIMIT 1");
    $f->bindValue(':id', $id, PDO::PARAM_INT);
    $f->execute();
    echo json_encode(['message' => 'Updated', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
}

function deleteVendor($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Id required']); return; }
    $stmt = $dbh->prepare("DELETE FROM vendors WHERE id = :id AND user_id = :uid");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() === 0) { http_response_code(404); echo json_encode(['error' => 'Not found']); return; }
    echo json_encode(['message' => 'Deleted']);
}
