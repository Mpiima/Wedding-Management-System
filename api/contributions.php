<?php
/**
 * Contributions API: list, create, update, delete. Member, amount, date.
 */
include("connect/header.php");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
$userId = (int) $_SESSION['user_id'];

switch ($method) {
    case 'GET': listContributions($dbh, $userId); break;
    case 'POST': createContribution($dbh, $userId, $input); break;
    case 'PUT': updateContribution($dbh, $userId, $input); break;
    case 'DELETE': deleteContribution($dbh, $userId, $input); break;
    default: http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
}

function ensureMemberBelongsToUser($dbh, $memberId, $userId) {
    $s = $dbh->prepare("SELECT m.id FROM members m INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid WHERE m.id = :mid LIMIT 1");
    $s->bindValue(':uid', $userId, PDO::PARAM_INT);
    $s->bindValue(':mid', (int) $memberId, PDO::PARAM_INT);
    $s->execute();
    return $s->fetch(PDO::FETCH_OBJ) ? true : false;
}

function listContributions($dbh, $userId) {
    try {
        $stmt = $dbh->prepare("
            SELECT c.id, c.user_id, c.member_id, c.amount, c.contribution_date, c.created_at, m.name AS member_name
            FROM contributions c
            INNER JOIN members m ON m.id = c.member_id
            INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid
            ORDER BY c.contribution_date DESC, c.created_at DESC
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['message' => 'OK', 'data' => $stmt->fetchAll(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) { error_log('Contributions list: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function createContribution($dbh, $userId, $input) {
    $memberId = isset($input['member_id']) ? (int) $input['member_id'] : 0;
    if ($memberId <= 0 || !ensureMemberBelongsToUser($dbh, $memberId, $userId)) { http_response_code(400); echo json_encode(['error' => 'Valid member required']); return; }
    $amount = isset($input['amount']) ? (float) $input['amount'] : 0;
    if ($amount < 0) $amount = 0;
    $date = isset($input['contribution_date']) ? trim((string) $input['contribution_date']) : date('Y-m-d');
    $d = date_create($date);
    if (!$d) { http_response_code(400); echo json_encode(['error' => 'Invalid date']); return; }
    $date = $d->format('Y-m-d');
    try {
        $stmt = $dbh->prepare("INSERT INTO contributions (user_id, member_id, amount, contribution_date) VALUES (:uid, :member_id, :amount, :contribution_date)");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
        $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindValue(':contribution_date', $date, PDO::PARAM_STR);
        $stmt->execute();
        $id = (int) $dbh->lastInsertId();
        $f = $dbh->prepare("SELECT c.id, c.member_id, c.amount, c.contribution_date, c.created_at, m.name AS member_name FROM contributions c INNER JOIN members m ON m.id = c.member_id WHERE c.id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        http_response_code(201);
        echo json_encode(['message' => 'Contribution created', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) { error_log('Contribution create: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function updateContribution($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Id required']); return; }
    $check = $dbh->prepare("SELECT c.id FROM contributions c INNER JOIN members m ON m.id = c.member_id INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid WHERE c.id = :id LIMIT 1");
    $check->bindValue(':uid', $userId, PDO::PARAM_INT);
    $check->bindValue(':id', $id, PDO::PARAM_INT);
    $check->execute();
    if (!$check->fetch()) { http_response_code(404); echo json_encode(['error' => 'Not found']); return; }
    $updates = []; $params = [':id' => $id];
    if (array_key_exists('member_id', $input)) { $mid = (int) $input['member_id']; if ($mid > 0 && ensureMemberBelongsToUser($dbh, $mid, $userId)) { $updates[] = 'member_id = :member_id'; $params[':member_id'] = $mid; } }
    if (array_key_exists('amount', $input)) { $updates[] = 'amount = :amount'; $params[':amount'] = max(0, (float) $input['amount']); }
    if (array_key_exists('contribution_date', $input)) { $d = date_create(trim($input['contribution_date'])); if ($d) { $updates[] = 'contribution_date = :contribution_date'; $params[':contribution_date'] = $d->format('Y-m-d'); } }
    if (count($updates) === 0) {
        $f = $dbh->prepare("SELECT c.id, c.member_id, c.amount, c.contribution_date, c.created_at, m.name AS member_name FROM contributions c INNER JOIN members m ON m.id = c.member_id WHERE c.id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        echo json_encode(['message' => 'No changes', 'data' => $f->fetch(PDO::FETCH_OBJ)]); return;
    }
    $sql = "UPDATE contributions SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    $stmt->execute();
    $f = $dbh->prepare("SELECT c.id, c.member_id, c.amount, c.contribution_date, c.created_at, m.name AS member_name FROM contributions c INNER JOIN members m ON m.id = c.member_id WHERE c.id = :id LIMIT 1");
    $f->bindValue(':id', $id, PDO::PARAM_INT);
    $f->execute();
    echo json_encode(['message' => 'Updated', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
}

function deleteContribution($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Id required']); return; }
    $stmt = $dbh->prepare("DELETE c FROM contributions c INNER JOIN members m ON m.id = c.member_id INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid WHERE c.id = :id");
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() === 0) { http_response_code(404); echo json_encode(['error' => 'Not found']); return; }
    echo json_encode(['message' => 'Deleted']);
}
