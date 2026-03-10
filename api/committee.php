<?php
/**
 * Committee API: list members with assigned roles; assign role to member; unassign.
 * GET    = list committee (members with their roles)
 * POST   = assign role to member (member_id, role_id)
 * DELETE = unassign role from member (member_id, role_id in body)
 */
include("connect/header.php");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

switch ($method) {
    case 'GET': listCommittee($dbh, $userId); break;
    case 'POST': assignRole($dbh, $userId, $input); break;
    case 'DELETE': unassignRole($dbh, $userId, $input); break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
}

function listCommittee($dbh, $userId) {
    try {
        $stmt = $dbh->prepare("
            SELECT m.id AS member_id, m.name AS member_name, m.email, m.phone, m.group_category_id,
                   g.name AS group_category_name
            FROM members m
            INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :user_id
            ORDER BY m.name ASC
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $members = $stmt->fetchAll(PDO::FETCH_OBJ);
        $memberIds = array_column($members, 'member_id');
        $rolesByMember = [];
        if (count($memberIds) > 0) {
            $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
            $r = $dbh->prepare("
                SELECT mr.member_id, r.id AS role_id, r.name AS role_name
                FROM member_roles mr
                INNER JOIN roles r ON r.id = mr.role_id AND r.user_id = ?
                WHERE mr.member_id IN ($placeholders)
            ");
            $r->execute(array_merge([$userId], $memberIds));
            while ($row = $r->fetch(PDO::FETCH_OBJ)) {
                if (!isset($rolesByMember[$row->member_id])) $rolesByMember[$row->member_id] = [];
                $rolesByMember[$row->member_id][] = ['id' => $row->role_id, 'name' => $row->role_name];
            }
        }
        foreach ($members as $m) {
            $m->roles = isset($rolesByMember[$m->member_id]) ? $rolesByMember[$m->member_id] : [];
        }
        // Committee = only members that have at least one role
        $members = array_values(array_filter($members, function ($m) {
            return !empty($m->roles);
        }));
        echo json_encode(['message' => 'OK', 'data' => $members]);
    } catch (PDOException $e) {
        error_log('Committee list: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function assignRole($dbh, $userId, $input) {
    $memberId = isset($input['member_id']) ? (int) $input['member_id'] : 0;
    $roleId = isset($input['role_id']) ? (int) $input['role_id'] : 0;
    if ($memberId <= 0 || $roleId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'member_id and role_id are required']);
        return;
    }
    try {
        $checkMember = $dbh->prepare("SELECT m.id FROM members m INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid WHERE m.id = :mid LIMIT 1");
        $checkMember->bindValue(':uid', $userId, PDO::PARAM_INT);
        $checkMember->bindValue(':mid', $memberId, PDO::PARAM_INT);
        $checkMember->execute();
        if (!$checkMember->fetch()) { http_response_code(404); echo json_encode(['error' => 'Member not found']); return; }
        $checkRole = $dbh->prepare("SELECT id FROM roles WHERE id = :id AND user_id = :uid LIMIT 1");
        $checkRole->bindValue(':id', $roleId, PDO::PARAM_INT);
        $checkRole->bindValue(':uid', $userId, PDO::PARAM_INT);
        $checkRole->execute();
        if (!$checkRole->fetch()) { http_response_code(404); echo json_encode(['error' => 'Role not found']); return; }
        $ins = $dbh->prepare("INSERT IGNORE INTO member_roles (member_id, role_id) VALUES (:member_id, :role_id)");
        $ins->bindValue(':member_id', $memberId, PDO::PARAM_INT);
        $ins->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $ins->execute();
        if ($ins->rowCount() === 0) {
            echo json_encode(['message' => 'Already assigned']);
            return;
        }
        http_response_code(201);
        echo json_encode(['message' => 'Role assigned']);
    } catch (PDOException $e) {
        error_log('Committee assign: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}

function unassignRole($dbh, $userId, $input) {
    $memberId = isset($input['member_id']) ? (int) $input['member_id'] : 0;
    $roleId = isset($input['role_id']) ? (int) $input['role_id'] : 0;
    if ($memberId <= 0 || $roleId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'member_id and role_id are required']);
        return;
    }
    try {
        $stmt = $dbh->prepare("
            DELETE mr FROM member_roles mr
            INNER JOIN members m ON m.id = mr.member_id
            INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid
            INNER JOIN roles r ON r.id = mr.role_id AND r.user_id = :uid
            WHERE mr.member_id = :mid AND mr.role_id = :rid
        ");
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':mid', $memberId, PDO::PARAM_INT);
        $stmt->bindValue(':rid', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['message' => 'Role unassigned']);
    } catch (PDOException $e) {
        error_log('Committee unassign: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred']);
    }
}
