<?php
/**
 * Contributions API: list, create, update, delete. Member, amount, date.
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
        if (!wmis_has_permission($dbh, 'contributions.view')) {
            http_response_code(403); echo json_encode(['error' => 'You do not have permission to view contributions']); exit;
        }
        listContributions($dbh, $scopeUserId);
        break;
    case 'POST':
        if (!wmis_has_permission($dbh, 'contributions.add')) {
            http_response_code(403); echo json_encode(['error' => 'You do not have permission to add contributions']); exit;
        }
        createContribution($dbh, $scopeUserId, $input);
        break;
    case 'PUT':
        if (!wmis_has_permission($dbh, 'contributions.edit')) {
            http_response_code(403); echo json_encode(['error' => 'You do not have permission to edit contributions']); exit;
        }
        updateContribution($dbh, $scopeUserId, $input);
        break;
    case 'DELETE':
        if (!wmis_has_permission($dbh, 'contributions.delete')) {
            http_response_code(403); echo json_encode(['error' => 'You do not have permission to delete contributions']); exit;
        }
        deleteContribution($dbh, $scopeUserId, $input);
        break;
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
        $direct = $dbh->prepare("
            SELECT c.id, c.user_id, c.member_id, c.amount, c.contribution_date AS date, c.created_at, m.name AS member_name
            FROM contributions c
            INNER JOIN members m ON m.id = c.member_id
            INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid
            ORDER BY c.contribution_date DESC, c.created_at DESC
        ");
        $direct->bindValue(':uid', $userId, PDO::PARAM_INT);
        $direct->execute();
        $directRows = $direct->fetchAll(PDO::FETCH_OBJ);
        foreach ($directRows as $r) {
            $r->type = 'direct';
            $r->pledge_id = null;
        }

        $pledge = $dbh->prepare("
            SELECT pp.id, pp.pledge_id, pp.amount, pp.paid_at AS date, pp.created_at, p.member_id, m.name AS member_name
            FROM pledge_payments pp
            INNER JOIN pledges p ON p.id = pp.pledge_id AND p.user_id = :uid
            INNER JOIN members m ON m.id = p.member_id
            INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid2
            ORDER BY pp.paid_at DESC, pp.created_at DESC
        ");
        $pledge->bindValue(':uid', $userId, PDO::PARAM_INT);
        $pledge->bindValue(':uid2', $userId, PDO::PARAM_INT);
        $pledge->execute();
        $pledgeRows = $pledge->fetchAll(PDO::FETCH_OBJ);
        foreach ($pledgeRows as $r) {
            $r->type = 'pledge';
        }

        $merged = array_merge($directRows, $pledgeRows);
        usort($merged, function ($a, $b) {
            $da = $a->date ?? '';
            $db = $b->date ?? '';
            if ($da !== $db) return strcmp($db, $da);
            return strcmp($b->created_at ?? '', $a->created_at ?? '');
        });
        echo json_encode(['message' => 'OK', 'data' => $merged]);
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
        $data = $f->fetch(PDO::FETCH_OBJ);
        $data->type = 'direct';
        $data->date = $data->contribution_date;
        $data->pledge_id = null;
        try {
            if (function_exists('wmis_send_email') && function_exists('wmis_email_contribution_made')) {
                $m = $dbh->prepare("SELECT name, email FROM members WHERE id = :id LIMIT 1");
                $m->bindValue(':id', $data->member_id, PDO::PARAM_INT);
                $m->execute();
                $mem = $m->fetch(PDO::FETCH_OBJ);
                if ($mem && trim($mem->email ?? '') !== '' && filter_var(trim($mem->email), FILTER_VALIDATE_EMAIL)) {
                    $tpl = wmis_email_contribution_made($mem->name, $data->amount, $data->contribution_date);
                    wmis_send_email($dbh, trim($mem->email), $tpl['subject'], $tpl['body']);
                }
            }
        } catch (Exception $e) { error_log('Contribution email: ' . $e->getMessage()); }
        http_response_code(201);
        echo json_encode(['message' => 'Contribution created', 'data' => $data]);
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
        $data = $f->fetch(PDO::FETCH_OBJ);
        $data->type = 'direct';
        $data->date = $data->contribution_date;
        $data->pledge_id = null;
        echo json_encode(['message' => 'No changes', 'data' => $data]); return;
    }
    $sql = "UPDATE contributions SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    $stmt->execute();
    $f = $dbh->prepare("SELECT c.id, c.member_id, c.amount, c.contribution_date, c.created_at, m.name AS member_name FROM contributions c INNER JOIN members m ON m.id = c.member_id WHERE c.id = :id LIMIT 1");
    $f->bindValue(':id', $id, PDO::PARAM_INT);
    $f->execute();
    $data = $f->fetch(PDO::FETCH_OBJ);
    $data->type = 'direct';
    $data->date = $data->contribution_date;
    $data->pledge_id = null;
    echo json_encode(['message' => 'Updated', 'data' => $data]);
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
