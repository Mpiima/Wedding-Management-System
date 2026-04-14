<?php
include("../connect/header.php");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

function requireSuperAdmin()
{
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'super_admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Super admin access required', 'data' => '']);
        exit;
    }
}

function toRow($r)
{
    $curr = (string)($r['curriculum_code'] ?? 'local_based');
    $currLabel = $curr === 'cambridge_international'
        ? 'Cambridge International Curriculum'
        : 'Local Based Curriculum';
    return [
        'id' => (string)$r['id'],
        'schoolNumber' => (string)$r['school_number'],
        'name' => $r['school_name'],
        'contactEmail' => $r['admin_email'],
        'phone' => $r['phone'],
        'planId' => $r['plan_code'],
        'curriculumId' => $curr,
        'curriculum' => $currLabel,
        'status' => ucfirst($r['status']),
        'studentsCount' => (int)$r['expected_students'],
        'setupCompleted' => (int)$r['setup_completed'] === 1,
        'createdAt' => $r['created_at'],
        'rejectionReason' => $r['rejection_reason']
    ];
}

function listSchools($dbh)
{
    requireSuperAdmin();
    $s = $dbh->query("SELECT * FROM school_onboarding ORDER BY id DESC");
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);
    $data = [];
    foreach ($rows as $r) $data[] = toRow($r);
    echo json_encode(['data' => $data]);
}

function setSchoolStatus($dbh, $input)
{
    requireSuperAdmin();
    $id = trim((string)($input['id'] ?? ''));
    $action = strtolower(trim((string)($input['action'] ?? '')));
    $reason = trim((string)($input['reason'] ?? ''));
    if ($id === '' || ($action !== 'approve' && $action !== 'reject')) {
        http_response_code(400);
        echo json_encode(['error' => 'id and action(approve|reject) are required', 'data' => '']);
        return;
    }

    $status = $action === 'approve' ? 'approved' : 'rejected';
    $up = $dbh->prepare(
        "UPDATE school_onboarding
         SET status = :status,
             rejection_reason = :reason,
             approved_by = :approved_by,
             approved_at = NOW()
         WHERE id = :id"
    );
    $up->execute([
        ':status' => $status,
        ':reason' => ($action === 'reject' ? $reason : null),
        ':approved_by' => (string)($_SESSION['rolenumber'] ?? 'SUPER-ADMIN'),
        ':id' => (int)$id
    ]);
    if ($up->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['error' => 'School not found', 'data' => '']);
        return;
    }

    // Mirror onboarding status to login account.
    $find = $dbh->prepare("SELECT school_number FROM school_onboarding WHERE id = :id LIMIT 1");
    $find->execute([':id' => (int)$id]);
    $row = $find->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $pos = $action === 'approve' ? 'admin' : 'rejected';
        $usr = $dbh->prepare("UPDATE users SET position = :pos WHERE ssid = :ssid");
        $usr->execute([':pos' => $pos, ':ssid' => $row['school_number']]);
    }

    echo json_encode(['message' => 'School status updated', 'data' => ['id' => $id, 'status' => $status]]);
}

function updateSchool($dbh, $input)
{
    requireSuperAdmin();
    $id = trim((string)($input['id'] ?? ''));
    if ($id === '') {
        http_response_code(400);
        echo json_encode(['error' => 'id is required', 'data' => '']);
        return;
    }

    $fields = [];
    $params = [':id' => (int)$id];
    if (array_key_exists('schoolName', $input)) { $fields[] = "school_name = :school_name"; $params[':school_name'] = trim((string)$input['schoolName']); }
    if (array_key_exists('phone', $input)) { $fields[] = "phone = :phone"; $params[':phone'] = trim((string)$input['phone']); }
    if (array_key_exists('planId', $input)) { $fields[] = "plan_code = :plan_code"; $params[':plan_code'] = trim((string)$input['planId']); }
    if (array_key_exists('curriculumId', $input)) {
        $curriculum = trim((string)$input['curriculumId']);
        $allowed = ['local_based', 'cambridge_international'];
        if (!in_array($curriculum, $allowed, true)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid curriculumId', 'data' => '']);
            return;
        }
        $fields[] = "curriculum_code = :curriculum_code";
        $params[':curriculum_code'] = $curriculum;
    }
    if (array_key_exists('studentsCount', $input)) { $fields[] = "expected_students = :expected_students"; $params[':expected_students'] = (int)$input['studentsCount']; }

    if (count($fields) === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'No fields to update', 'data' => '']);
        return;
    }

    $sql = "UPDATE school_onboarding SET " . implode(", ", $fields) . " WHERE id = :id";
    $st = $dbh->prepare($sql);
    $st->execute($params);
    echo json_encode(['message' => 'School updated', 'data' => ['id' => $id]]);
}

function deleteSchool($dbh, $id)
{
    requireSuperAdmin();
    $raw = trim((string)$id);
    if ($raw === '') {
        http_response_code(400);
        echo json_encode(['error' => 'id is required', 'data' => '']);
        return;
    }
    $sel = $dbh->prepare("SELECT school_number FROM school_onboarding WHERE id = :id LIMIT 1");
    $sel->execute([':id' => (int)$raw]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'School not found', 'data' => '']);
        return;
    }

    $dbh->prepare("DELETE FROM school_onboarding WHERE id = :id")->execute([':id' => (int)$raw]);
    $dbh->prepare("DELETE FROM users WHERE ssid = :ssid")->execute([':ssid' => $row['school_number']]);
    echo json_encode(['message' => 'School deleted', 'data' => ['id' => $raw]]);
}

switch ($method) {
    case 'GET':
        listSchools($dbh);
        break;
    case 'POST':
        setSchoolStatus($dbh, is_array($input) ? $input : []);
        break;
    case 'PUT':
        updateSchool($dbh, is_array($input) ? $input : []);
        break;
    case 'DELETE':
        $id = $_GET['id'] ?? (is_array($input) ? ($input['id'] ?? '') : '');
        deleteSchool($dbh, $id);
        break;
    default:
        echo json_encode(['message' => 'Invalid request method']);
        break;
}

