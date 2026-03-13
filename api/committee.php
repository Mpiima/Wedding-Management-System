<?php
/**
 * Committee API: list members with assigned roles; assign role to member; unassign.
 * GET  = list committee (members with their roles)
 * POST = assign or unassign: body { action: 'assign'|'unassign', member_id, role_id }
 */
include("connect/header.php");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$scopeUserId = isset($scopeUserId) ? (int) $scopeUserId : $userId;

switch ($method) {
    case 'GET':
        if (!wmis_has_permission($dbh, 'committee.view')) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to view committee']);
            exit;
        }
        listCommittee($dbh, $scopeUserId);
        break;
    case 'POST':
        $action = isset($input['action']) ? trim((string) $input['action']) : 'assign';
        if ($action === 'unassign') {
            if (!wmis_has_permission($dbh, 'committee.unassign')) {
                http_response_code(403);
                echo json_encode(['error' => 'You do not have permission to unassign roles']);
                exit;
            }
            unassignRole($dbh, $scopeUserId, $input);
        } else {
            if (!wmis_has_permission($dbh, 'committee.assign')) {
                http_response_code(403);
                echo json_encode(['error' => 'You do not have permission to assign roles']);
                exit;
            }
            assignRole($dbh, $scopeUserId, $input);
        }
        break;
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
    $username = isset($input['username']) ? trim((string) $input['username']) : '';
    $password = isset($input['password']) ? $input['password'] : '';

    if ($memberId <= 0 || $roleId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'member_id and role_id are required']);
        return;
    }
    if ($username === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Username is required for login']);
        return;
    }
    if (!is_string($password) || strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['error' => 'Password is required (at least 6 characters)']);
        return;
    }

    try {
        $checkMember = $dbh->prepare("
            SELECT m.id, m.name, m.email, m.login_user_id
            FROM members m
            INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid
            WHERE m.id = :mid LIMIT 1
        ");
        $checkMember->bindValue(':uid', $userId, PDO::PARAM_INT);
        $checkMember->bindValue(':mid', $memberId, PDO::PARAM_INT);
        $checkMember->execute();
        $member = $checkMember->fetch(PDO::FETCH_OBJ);
        if (!$member) {
            http_response_code(404);
            echo json_encode(['error' => 'Member not found']);
            return;
        }

        $checkRole = $dbh->prepare("SELECT id FROM roles WHERE id = :id AND user_id = :uid LIMIT 1");
        $checkRole->bindValue(':id', $roleId, PDO::PARAM_INT);
        $checkRole->bindValue(':uid', $userId, PDO::PARAM_INT);
        $checkRole->execute();
        if (!$checkRole->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Role not found']);
            return;
        }

        $loginUserId = $member->login_user_id ? (int) $member->login_user_id : null;
        $emailForUser = !empty($member->email) ? $member->email : (isset($input['email']) ? trim((string) $input['email']) : null);
        if (!$loginUserId && !$emailForUser) {
            http_response_code(400);
            echo json_encode(['error' => 'Member must have an email, or provide one, to get login access']);
            return;
        }

        if ($loginUserId) {
            // Update existing login user
            $exists = $dbh->prepare("SELECT id FROM users WHERE (username = :u OR email = :e) AND id != :id LIMIT 1");
            $exists->bindValue(':u', $username, PDO::PARAM_STR);
            $exists->bindValue(':e', $emailForUser, PDO::PARAM_STR);
            $exists->bindValue(':id', $loginUserId, PDO::PARAM_INT);
            $exists->execute();
            if ($exists->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Username or email already in use by another account']);
                return;
            }
            $upd = $dbh->prepare("UPDATE users SET username = :username, password = :password, email = :email, updated_at = NOW() WHERE id = :id");
            $upd->bindValue(':username', $username, PDO::PARAM_STR);
            $upd->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
            $upd->bindValue(':email', $emailForUser, PDO::PARAM_STR);
            $upd->bindValue(':id', $loginUserId, PDO::PARAM_INT);
            $upd->execute();
        } else {
            // Create new user for this member
            $exists = $dbh->prepare("SELECT id FROM users WHERE username = :u OR email = :e LIMIT 1");
            $exists->bindValue(':u', $username, PDO::PARAM_STR);
            $exists->bindValue(':e', $emailForUser, PDO::PARAM_STR);
            $exists->execute();
            if ($exists->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Username or email already in use']);
                return;
            }
            $nameParts = preg_split('/\s+/', trim($member->name), 2);
            $first = $nameParts[0] ?? $member->name;
            $last = $nameParts[1] ?? '';
            $ins = $dbh->prepare("
                INSERT INTO users (username, firstname, lastname, email, password, role, position, created_at, updated_at)
                VALUES (:username, :firstname, :lastname, :email, :password, 'Member', 'member', NOW(), NOW())
            ");
            $ins->bindValue(':username', $username, PDO::PARAM_STR);
            $ins->bindValue(':firstname', $first, PDO::PARAM_STR);
            $ins->bindValue(':lastname', $last, PDO::PARAM_STR);
            $ins->bindValue(':email', $emailForUser, PDO::PARAM_STR);
            $ins->bindValue(':password', password_hash($password, PASSWORD_DEFAULT), PDO::PARAM_STR);
            $ins->execute();
            $loginUserId = (int) $dbh->lastInsertId();
            $updMember = $dbh->prepare("UPDATE members SET login_user_id = :lid WHERE id = :mid");
            $updMember->bindValue(':lid', $loginUserId, PDO::PARAM_INT);
            $updMember->bindValue(':mid', $memberId, PDO::PARAM_INT);
            $updMember->execute();
        }

        $ins = $dbh->prepare("INSERT IGNORE INTO member_roles (member_id, role_id) VALUES (:member_id, :role_id)");
        $ins->bindValue(':member_id', $memberId, PDO::PARAM_INT);
        $ins->bindValue(':role_id', $roleId, PDO::PARAM_INT);
        $ins->execute();
        if ($ins->rowCount() === 0) {
            echo json_encode(['message' => 'Already assigned. Login credentials updated.']);
            return;
        }
        http_response_code(201);
        echo json_encode(['message' => 'Role assigned. Member can sign in with the username or email and password you set.']);
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
    $isDev = (getenv('APP_ENV') ?: 'development') === 'development';
    try {
        // Use distinct param names (PDO/MySQL can reject duplicate names with real prepared statements)
        $stmt = $dbh->prepare("
            DELETE FROM member_roles
            WHERE member_id = :mid AND role_id = :rid
            AND EXISTS (
                SELECT 1 FROM members m
                INNER JOIN group_categories g ON g.id = m.group_category_id AND g.user_id = :uid1
                WHERE m.id = :mid1
            )
            AND EXISTS (
                SELECT 1 FROM roles r WHERE r.id = :rid1 AND r.user_id = :uid2
            )
        ");
        $stmt->bindValue(':uid1', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':mid', $memberId, PDO::PARAM_INT);
        $stmt->bindValue(':mid1', $memberId, PDO::PARAM_INT);
        $stmt->bindValue(':rid', $roleId, PDO::PARAM_INT);
        $stmt->bindValue(':rid1', $roleId, PDO::PARAM_INT);
        $stmt->execute();
        echo json_encode(['message' => 'Role unassigned']);
    } catch (PDOException $e) {
        error_log('Committee unassign: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'error' => $isDev ? ('Unassign failed: ' . $e->getMessage()) : 'An error occurred'
        ]);
    }
}
