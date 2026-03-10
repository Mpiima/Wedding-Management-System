<?php
/**
 * Pledges API: list, create, update, delete. POST action=pay to record payment.
 */
include("connect/header.php");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
$userId = (int) $_SESSION['user_id'];

switch ($method) {
    case 'GET': listPledges($dbh, $userId); break;
    case 'POST':
        if (!empty($input['action']) && $input['action'] === 'pay') {
            recordPayment($dbh, $userId, $input);
        } else {
            createPledge($dbh, $userId, $input);
        }
        break;
    case 'PUT': updatePledge($dbh, $userId, $input); break;
    case 'DELETE': deletePledge($dbh, $userId, $input); break;
    default: http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
}

function ensureMemberBelongsToUser($dbh, $memberId, $userId) {
    $s = $dbh->prepare("SELECT m.id FROM members m INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid WHERE m.id = :mid LIMIT 1");
    $s->bindValue(':uid', $userId, PDO::PARAM_INT);
    $s->bindValue(':mid', (int) $memberId, PDO::PARAM_INT);
    $s->execute();
    return $s->fetch(PDO::FETCH_OBJ) ? true : false;
}

function listPledges($dbh, $userId) {
    try {
        $pledgeId = isset($_GET['pledge_id']) ? (int) $_GET['pledge_id'] : 0;
        if ($pledgeId > 0) {
            $stmt = $dbh->prepare("
                SELECT p.id, p.user_id, p.member_id, p.amount_pledged, p.amount_paid, p.paying_date, p.created_at, p.updated_at, m.name AS member_name
                FROM pledges p
                INNER JOIN members m ON m.id = p.member_id
                INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid
                WHERE p.id = :pid LIMIT 1
            ");
            $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':pid', $pledgeId, PDO::PARAM_INT);
            $stmt->execute();
            $pledge = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$pledge) { http_response_code(404); echo json_encode(['error' => 'Pledge not found']); return; }
            $pay = $dbh->prepare("SELECT id, pledge_id, amount, paid_at, created_at FROM pledge_payments WHERE pledge_id = :pid ORDER BY paid_at DESC");
            $pay->bindValue(':pid', $pledgeId, PDO::PARAM_INT);
            $pay->execute();
            $pledge->payments = $pay->fetchAll(PDO::FETCH_OBJ);
            echo json_encode(['message' => 'OK', 'data' => $pledge]);
            return;
        }
        $stmt = $dbh->prepare("
            SELECT p.id, p.user_id, p.member_id, p.amount_pledged, p.amount_paid, p.paying_date, p.created_at, p.updated_at,
                   m.name AS member_name
            FROM pledges p
            INNER JOIN members m ON m.id = p.member_id
            INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid
            ORDER BY p.created_at DESC
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_OBJ);
        echo json_encode(['message' => 'OK', 'data' => $rows]);
    } catch (PDOException $e) { error_log('Pledges list: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function createPledge($dbh, $userId, $input) {
    $memberId = isset($input['member_id']) ? (int) $input['member_id'] : 0;
    if ($memberId <= 0 || !ensureMemberBelongsToUser($dbh, $memberId, $userId)) {
        http_response_code(400); echo json_encode(['error' => 'Valid member is required']); return;
    }
    $amountPledged = isset($input['amount_pledged']) ? (float) $input['amount_pledged'] : 0;
    if ($amountPledged < 0) $amountPledged = 0;
    $amountPaid = isset($input['amount_paid']) ? (float) $input['amount_paid'] : 0;
    if ($amountPaid < 0) $amountPaid = 0;
    $payingDate = null;
    if (!empty($input['paying_date'])) {
        $d = date_create(trim($input['paying_date']));
        if ($d) $payingDate = $d->format('Y-m-d');
    }
    try {
        $stmt = $dbh->prepare("INSERT INTO pledges (user_id, member_id, amount_pledged, amount_paid, paying_date) VALUES (:uid, :member_id, :amount_pledged, :amount_paid, :paying_date)");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
        $stmt->bindValue(':amount_pledged', $amountPledged, PDO::PARAM_STR);
        $stmt->bindValue(':amount_paid', $amountPaid, PDO::PARAM_STR);
        $stmt->bindValue(':paying_date', $payingDate, PDO::PARAM_STR);
        $stmt->execute();
        $id = (int) $dbh->lastInsertId();
        $f = $dbh->prepare("SELECT p.id, p.member_id, p.amount_pledged, p.amount_paid, p.paying_date, p.created_at, m.name AS member_name FROM pledges p INNER JOIN members m ON m.id = p.member_id WHERE p.id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        http_response_code(201);
        echo json_encode(['message' => 'Pledge created', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) { error_log('Pledge create: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function updatePledge($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Pledge id required']); return; }
    $check = $dbh->prepare("SELECT p.id FROM pledges p INNER JOIN members m ON m.id = p.member_id INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid WHERE p.id = :id LIMIT 1");
    $check->bindValue(':uid', $userId, PDO::PARAM_INT);
    $check->bindValue(':id', $id, PDO::PARAM_INT);
    $check->execute();
    if (!$check->fetch()) { http_response_code(404); echo json_encode(['error' => 'Pledge not found']); return; }
    $updates = []; $params = [':id' => $id];
    if (array_key_exists('member_id', $input)) { $mid = (int) $input['member_id']; if ($mid > 0 && ensureMemberBelongsToUser($dbh, $mid, $userId)) { $updates[] = 'member_id = :member_id'; $params[':member_id'] = $mid; } }
    if (array_key_exists('amount_pledged', $input)) { $updates[] = 'amount_pledged = :amount_pledged'; $params[':amount_pledged'] = max(0, (float) $input['amount_pledged']); }
    if (array_key_exists('amount_paid', $input)) { $updates[] = 'amount_paid = :amount_paid'; $params[':amount_paid'] = max(0, (float) $input['amount_paid']); }
    if (array_key_exists('paying_date', $input)) { $d = !empty($input['paying_date']) && date_create(trim($input['paying_date'])) ? date_create(trim($input['paying_date']))->format('Y-m-d') : null; $updates[] = 'paying_date = :paying_date'; $params[':paying_date'] = $d; }
    if (count($updates) === 0) {
        $f = $dbh->prepare("SELECT p.id, p.member_id, p.amount_pledged, p.amount_paid, p.paying_date, p.created_at, m.name AS member_name FROM pledges p INNER JOIN members m ON m.id = p.member_id WHERE p.id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        echo json_encode(['message' => 'No changes', 'data' => $f->fetch(PDO::FETCH_OBJ)]); return;
    }
    $sql = "UPDATE pledges SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    $stmt->execute();
    $f = $dbh->prepare("SELECT p.id, p.member_id, p.amount_pledged, p.amount_paid, p.paying_date, p.created_at, m.name AS member_name FROM pledges p INNER JOIN members m ON m.id = p.member_id WHERE p.id = :id LIMIT 1");
    $f->bindValue(':id', $id, PDO::PARAM_INT);
    $f->execute();
    echo json_encode(['message' => 'Pledge updated', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
}

function recordPayment($dbh, $userId, $input) {
    $pledgeId = isset($input['pledge_id']) ? (int) $input['pledge_id'] : 0;
    $amount = isset($input['amount']) ? (float) $input['amount'] : 0;
    $paidAt = isset($input['paid_at']) ? trim((string) $input['paid_at']) : date('Y-m-d');
    if ($pledgeId <= 0 || $amount <= 0) { http_response_code(400); echo json_encode(['error' => 'pledge_id and amount required']); return; }
    $d = date_create($paidAt);
    if (!$d) { http_response_code(400); echo json_encode(['error' => 'Invalid paid_at date']); return; }
    $paidAt = $d->format('Y-m-d');
    try {
        $check = $dbh->prepare("SELECT p.id, p.amount_paid FROM pledges p INNER JOIN members m ON m.id = p.member_id INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid WHERE p.id = :pid LIMIT 1");
        $check->bindValue(':uid', $userId, PDO::PARAM_INT);
        $check->bindValue(':pid', $pledgeId, PDO::PARAM_INT);
        $check->execute();
        $pledge = $check->fetch(PDO::FETCH_OBJ);
        if (!$pledge) { http_response_code(404); echo json_encode(['error' => 'Pledge not found']); return; }
        $stmt = $dbh->prepare("INSERT INTO pledge_payments (pledge_id, amount, paid_at) VALUES (:pledge_id, :amount, :paid_at)");
        $stmt->bindValue(':pledge_id', $pledgeId, PDO::PARAM_INT);
        $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindValue(':paid_at', $paidAt, PDO::PARAM_STR);
        $stmt->execute();
        $payId = (int) $dbh->lastInsertId();
        $newPaid = (float) $pledge->amount_paid + $amount;
        $upd = $dbh->prepare("UPDATE pledges SET amount_paid = :amount_paid WHERE id = :id");
        $upd->bindValue(':amount_paid', $newPaid, PDO::PARAM_STR);
        $upd->bindValue(':id', $pledgeId, PDO::PARAM_INT);
        $upd->execute();
        $row = $dbh->prepare("SELECT id, pledge_id, amount, paid_at, created_at FROM pledge_payments WHERE id = :id LIMIT 1");
        $row->bindValue(':id', $payId, PDO::PARAM_INT);
        $row->execute();
        $paymentRow = $row->fetch(PDO::FETCH_OBJ);
        http_response_code(201);
        echo json_encode(['message' => 'Payment recorded', 'data' => $paymentRow, 'pledge_amount_paid' => $newPaid]);
    } catch (PDOException $e) { error_log('Pledge payment: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function deletePledge($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Pledge id required']); return; }
    try {
        $stmt = $dbh->prepare("DELETE p FROM pledges p INNER JOIN members m ON m.id = p.member_id INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid WHERE p.id = :id");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        if ($stmt->rowCount() === 0) { http_response_code(404); echo json_encode(['error' => 'Pledge not found']); return; }
        echo json_encode(['message' => 'Pledge deleted']);
    } catch (PDOException $e) { error_log('Pledge delete: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}
