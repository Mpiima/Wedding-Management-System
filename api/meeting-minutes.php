<?php
/**
 * Meeting minutes API: list, create, update, delete.
 */
include("connect/header.php");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) { http_response_code(401); echo json_encode(['error' => 'Unauthorized']); exit; }
$userId = (int) $_SESSION['user_id'];

switch ($method) {
    case 'GET': listMinutes($dbh, $userId); break;
    case 'POST': createMinutes($dbh, $userId, $input); break;
    case 'PUT': updateMinutes($dbh, $userId, $input); break;
    case 'DELETE': deleteMinutes($dbh, $userId, $input); break;
    default: http_response_code(405); echo json_encode(['error' => 'Method not allowed']);
}

function listMinutes($dbh, $userId) {
    try {
        $stmt = $dbh->prepare("SELECT id, user_id, title, meeting_date, content, created_at, updated_at FROM meeting_minutes WHERE user_id = :uid ORDER BY meeting_date DESC, created_at DESC");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['message' => 'OK', 'data' => $stmt->fetchAll(PDO::FETCH_OBJ)]);
    } catch (PDOException $e) { error_log('Meeting minutes list: ' . $e->getMessage()); http_response_code(500); echo json_encode(['error' => 'An error occurred']); }
}

function createMinutes($dbh, $userId, $input) {
    $title = isset($input['title']) ? trim((string) $input['title']) : '';
    if ($title === '') { http_response_code(400); echo json_encode(['error' => 'Title required']); return; }
    $date = isset($input['meeting_date']) ? trim((string) $input['meeting_date']) : date('Y-m-d');
    $d = date_create($date);
    if (!$d) { http_response_code(400); echo json_encode(['error' => 'Invalid date']); return; }
    $date = $d->format('Y-m-d');
    $content = isset($input['content']) ? (string) $input['content'] : null;
    try {
        $stmt = $dbh->prepare("INSERT INTO meeting_minutes (user_id, title, meeting_date, content) VALUES (:uid, :title, :meeting_date, :content)");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':title', $title, PDO::PARAM_STR);
        $stmt->bindValue(':meeting_date', $date, PDO::PARAM_STR);
        $stmt->bindValue(':content', $content, PDO::PARAM_STR);
        $stmt->execute();
        $id = (int) $dbh->lastInsertId();
        $f = $dbh->prepare("SELECT id, user_id, title, meeting_date, content, created_at, updated_at FROM meeting_minutes WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        http_response_code(201);
        echo json_encode(['message' => 'Created', 'data' => $f->fetch(PDO::FETCH_OBJ)]);
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
    if (array_key_exists('title', $input)) { $updates[] = 'title = :title'; $params[':title'] = trim((string) $input['title']); }
    if (array_key_exists('meeting_date', $input)) { $d = date_create(trim($input['meeting_date'])); if ($d) { $updates[] = 'meeting_date = :meeting_date'; $params[':meeting_date'] = $d->format('Y-m-d'); } }
    if (array_key_exists('content', $input)) { $updates[] = 'content = :content'; $params[':content'] = (string) $input['content']; }
    if (count($updates) === 0) {
        $f = $dbh->prepare("SELECT id, user_id, title, meeting_date, content, created_at, updated_at FROM meeting_minutes WHERE id = :id LIMIT 1");
        $f->bindValue(':id', $id, PDO::PARAM_INT);
        $f->execute();
        echo json_encode(['message' => 'No changes', 'data' => $f->fetch(PDO::FETCH_OBJ)]); return;
    }
    $sql = "UPDATE meeting_minutes SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $dbh->prepare($sql);
    foreach ($params as $k => $v) $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
    $stmt->execute();
    $f = $dbh->prepare("SELECT id, user_id, title, meeting_date, content, created_at, updated_at FROM meeting_minutes WHERE id = :id LIMIT 1");
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
