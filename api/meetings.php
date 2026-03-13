<?php
/**
 * Meetings API: list, create, update, delete.
 * Meetings have: title, meeting_date, agenda. Minutes are created separately (meeting-minutes.php) for a meeting.
 */
include("connect/header.php");
include_once(__DIR__ . '/lib/EmailHelper.php');
include_once(__DIR__ . '/lib/EmailTemplates.php');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
$userId = (int) $_SESSION['user_id'];
$scopeUserId = isset($scopeUserId) ? (int) $scopeUserId : $userId;

switch ($method) {
    case 'GET': listMeetings($dbh, $scopeUserId); break;
    case 'POST': createMeeting($dbh, $scopeUserId, $input); break;
    case 'PUT': updateMeeting($dbh, $scopeUserId, $input); break;
    case 'DELETE': deleteMeeting($dbh, $scopeUserId, $input); break;
    default: http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
}

function listMeetings($dbh, $userId) {
    try {
        $stmt = $dbh->prepare("SELECT id, user_id, title, meeting_date, agenda, created_at, updated_at FROM meetings WHERE user_id = :uid ORDER BY meeting_date DESC, created_at DESC");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['message' => 'OK', 'data' => $stmt->fetchAll(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) { error_log('Meetings list: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function createMeeting($dbh, $userId, $input) {
    $title = isset($input['title']) ? trim((string) $input['title']) : '';
    if ($title === '') { http_response_code(400); echo json_encode(['error' => 'Title required']); return; }
    $date = isset($input['meeting_date']) ? trim((string) $input['meeting_date']) : date('Y-m-d');
    $d = date_create($date);
    if (!$d) { http_response_code(400); echo json_encode(['error' => 'Invalid date']); return; }
    $date = $d->format('Y-m-d');
    $agenda = isset($input['agenda']) ? (string) $input['agenda'] : null;
    try {
        $stmt = $dbh->prepare("INSERT INTO meetings (user_id, title, meeting_date, agenda) VALUES (:uid, :title, :meeting_date, :agenda)");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':meeting_date', $date, PDO::PARAM_STR);
        $stmt->bindValue(':agenda', $agenda, PDO::PARAM_STR);
        $stmt->execute();
        $id = (int) $dbh->lastInsertId();
        $f = $dbh->prepare("SELECT id, user_id, title, meeting_date, agenda, created_at, updated_at FROM meetings WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        $data = $f->fetch(PDO::FETCH_OBJ);
        try {
            if (function_exists('wmis_send_email') && function_exists('wmis_email_meeting_created')) {
                $stmtM = $dbh->prepare("SELECT m.id, m.name, m.email FROM members m INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid WHERE TRIM(COALESCE(m.email, '')) != '' AND m.email IS NOT NULL");
                $stmtM->bindValue(':uid', $userId, PDO::PARAM_INT);
                $stmtM->execute();
                while ($row = $stmtM->fetch(PDO::FETCH_OBJ)) {
                    if (filter_var(trim($row->email), FILTER_VALIDATE_EMAIL)) {
                        $tpl = wmis_email_meeting_created($row->name, $data->title, $data->meeting_date, '');
                        wmis_send_email($dbh, trim($row->email), $tpl['subject'], $tpl['body']);
                    }
                }
            }
        } catch (Exception $e) { error_log('Meeting email: ' . $e->getMessage()); }
        http_response_code(201);
        echo json_encode(['message' => 'Created', 'data' => $data]);
    } catch (PDOException $e) { error_log('Meeting create: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function updateMeeting($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Id required']); return; }
    $check = $dbh->prepare("SELECT id FROM meetings WHERE id = :id AND user_id = :uid LIMIT 1");
    $check->bindValue(':id', $id, PDO::PARAM_INT);
    $check->bindValue(':uid', $userId, PDO::PARAM_INT);
    $check->execute();
    if (!$check->fetch()) { http_response_code(404); echo json_encode(['error' => 'Not found']); return; }
    $updates = []; $params = [':id' => $id];
    if (array_key_exists('title', $input)) { $updates[] = 'title = :title'; $params[':title'] = trim((string) $input['title']); }
    if (array_key_exists('meeting_date', $input)) { $d = date_create(trim($input['meeting_date'])); if ($d) { $updates[] = 'meeting_date = :meeting_date'; $params[':meeting_date'] = $d->format('Y-m-d'); } }
    if (array_key_exists('agenda', $input)) { $updates[] = 'agenda = :agenda'; $params[':agenda'] = (string) $input['agenda']; }
    if (count($updates) === 0) {
        $f = $dbh->prepare("SELECT id, user_id, title, meeting_date, agenda, created_at, updated_at FROM meetings WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        echo json_encode(['message' => 'No changes', 'data' => $f->fetch(PDO::FETCH_OBJ)]); return;
    }
    $sql = "UPDATE meetings SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    $stmt->execute();
    $f = $dbh->prepare("SELECT id, user_id, title, meeting_date, agenda, created_at, updated_at FROM meetings WHERE id = :id LIMIT 1");
    $f->bindValue(':id', $id, PDO::PARAM_INT);
    $f->execute();
    echo json_encode(['message' => 'Updated', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
}

function deleteMeeting($dbh, $userId, $input) {
    $id = isset($input['id']) ? (int) $input['id'] : 0;
    if ($id <= 0) { http_response_code(400); echo json_encode(['error' => 'Id required']); return; }
    $stmt = $dbh->prepare("DELETE FROM meetings WHERE id = :id AND user_id = :uid");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->rowCount() === 0) { http_response_code(404); echo json_encode(['error' => 'Not found']); return; }
    echo json_encode(['message' => 'Deleted']);
}
