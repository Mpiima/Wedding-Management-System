<?php
/**
 * Meeting minutes API: list, create, update, delete.
 * Minutes are for an already-created meeting: select meeting_id and enter content.
 */
include("connect/header.php");
include_once(__DIR__ . '/lib/EmailHelper.php');
include_once(__DIR__ . '/lib/EmailTemplates.php');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
$userId = (int) $_SESSION['user_id'];
$scopeUserId = isset($scopeUserId) ? (int) $scopeUserId : $userId;

switch ($method) {
    case 'GET': listMinutes($dbh, $scopeUserId); break;
    case 'POST': createMinutes($dbh, $scopeUserId, $input); break;
    case 'PUT': updateMinutes($dbh, $scopeUserId, $input); break;
    case 'DELETE': deleteMinutes($dbh, $scopeUserId, $input); break;
    default: http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
}

function listMinutes($dbh, $userId) {
    try {
        $stmt = $dbh->prepare("
            SELECT mm.id, mm.user_id, mm.meeting_id, mm.content, mm.created_at, mm.updated_at,
                   m.title AS meeting_title, m.meeting_date
            FROM meeting_minutes mm
            INNER JOIN meetings m ON m.id = mm.meeting_id AND m.user_id = :uid
            WHERE mm.user_id = :uid2
            ORDER BY m.meeting_date DESC, mm.created_at DESC
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['message' => 'OK', 'data' => $stmt->fetchAll(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) { error_log('Meeting minutes list: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function createMinutes($dbh, $userId, $input) {
    $meetingId = isset($input['meeting_id']) ? (int) $input['meeting_id'] : 0;
    if ($meetingId <= 0) { http_response_code(400); echo json_encode(['error' => 'Please select a meeting']); return; }
    $check = $dbh->prepare("SELECT id FROM meetings WHERE id = :id AND user_id = :uid LIMIT 1");
    $check->bindValue(':id', $meetingId, PDO::PARAM_INT);
    $check->bindValue(':uid', $userId, PDO::PARAM_INT);
    $check->execute();
    if (!$check->fetch()) { http_response_code(404); echo json_encode(['error' => 'Meeting not found']); return; }
    $content = isset($input['content']) ? (string) $input['content'] : null;
    try {
        $stmt = $dbh->prepare("INSERT INTO meeting_minutes (user_id, meeting_id, content) VALUES (:uid, :meeting_id, :content)");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':meeting_id', $meetingId, PDO::PARAM_INT);
        $stmt->bindValue(':content', $content, PDO::PARAM_STR);
        $stmt->execute();
        $id = (int) $dbh->lastInsertId();
        $f = $dbh->prepare("
            SELECT mm.id, mm.user_id, mm.meeting_id, mm.content, mm.created_at, mm.updated_at,
                   m.title AS meeting_title, m.meeting_date
            FROM meeting_minutes mm
            INNER JOIN meetings m ON m.id = mm.meeting_id
            WHERE mm.id = :id LIMIT 1
        ");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        $data = $f->fetch(PDO::FETCH_OBJ);
        try {
            if (function_exists('wmis_send_email') && function_exists('wmis_email_minutes_created')) {
                $summary = $data->content ? (strlen($data->content) > 500 ? substr(strip_tags($data->content), 0, 500) . '…' : strip_tags($data->content)) : '';
                $stmtM = $dbh->prepare("SELECT m.id, m.name, m.email FROM members m INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid WHERE TRIM(COALESCE(m.email, '')) != '' AND m.email IS NOT NULL");
                $stmtM->bindValue(':uid', $userId, PDO::PARAM_INT);
                $stmtM->execute();
                while ($row = $stmtM->fetch(PDO::FETCH_OBJ)) {
                    if (filter_var(trim($row->email), FILTER_VALIDATE_EMAIL)) {
                        $tpl = wmis_email_minutes_created($row->name, $data->meeting_title ?? 'Meeting', $summary);
                        wmis_send_email($dbh, trim($row->email), $tpl['subject'], $tpl['body']);
                    }
                }
            }
        } catch (Exception $e) { error_log('Meeting minutes email: ' . $e->getMessage()); }
        http_response_code(201);
        echo json_encode(['message' => 'Created', 'data' => $data]);
    } catch (PDOException $e) { error_log('Meeting minutes create: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function updateMinutes($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Id required']); return; }
    $check = $dbh->prepare("SELECT id FROM meeting_minutes WHERE id = :id AND user_id = :uid LIMIT 1");
    $check->bindValue(':id', $id, PDO::PARAM_INT);
    $check->bindValue(':uid', $userId, PDO::PARAM_INT);
    $check->execute();
    if (!$check->fetch()) { http_response_code(404); echo json_encode(['error' => 'Not found']); return; }
    $updates = []; $params = [':id' => $id];
    if (array_key_exists('content', $input)) { $updates[] = 'content = :content'; $params[':content'] = (string) $input['content']; }
    if (count($updates) === 0) {
        $f = $dbh->prepare("
            SELECT mm.id, mm.user_id, mm.meeting_id, mm.content, mm.created_at, mm.updated_at,
                   m.title AS meeting_title, m.meeting_date
            FROM meeting_minutes mm
            INNER JOIN meetings m ON m.id = mm.meeting_id
            WHERE mm.id = :id LIMIT 1
        ");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        echo json_encode(['message' => 'No changes', 'data' => $f->fetch(PDO::FETCH_OBJ)]); return;
    }
    $sql = "UPDATE meeting_minutes SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    $stmt->execute();
    $f = $dbh->prepare("
        SELECT mm.id, mm.user_id, mm.meeting_id, mm.content, mm.created_at, mm.updated_at,
               m.title AS meeting_title, m.meeting_date
        FROM meeting_minutes mm
        INNER JOIN meetings m ON m.id = mm.meeting_id
        WHERE mm.id = :id LIMIT 1
    ");
    $f->bindValue(':id', $id, PDO::PARAM_INT);
    $f->execute();
    echo json_encode(['message' => 'Updated', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
}

function deleteMinutes($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Id required']); return; }
    $stmt = $dbh->prepare("DELETE FROM meeting_minutes WHERE id = :id AND user_id = :uid");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() === 0) { http_response_code(404); echo json_encode(['error' => 'Not found']); return; }
    echo json_encode(['message' => 'Deleted']);
}
