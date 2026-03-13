<?php
include("../connect/header.php");
include("../connect/permissions.php");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

switch ($method) {
    case 'POST':
        handleLogin($dbh, $input);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}

function handleLogin($dbh, $input) {
    $login = isset($input['login']) ? trim((string) $input['login']) : (isset($input['email']) ? trim((string) $input['email']) : '');
    $password = $input['password'] ?? '';

    if ($login === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Email or username is required']);
        return;
    }

    if (!is_string($password) || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Password is required']);
        return;
    }

    try {
        // Login by username OR email; allow all users including position = 'member' (committee members)
        $stmt = $dbh->prepare("
            SELECT id, username, firstname, lastname, email, avatar, role, rolenumber, ssid, powers, password, position
            FROM users
            WHERE (email = :login OR username = :login2)
            LIMIT 1
        ");
        $stmt->bindValue(':login', $login, PDO::PARAM_STR);
        $stmt->bindValue(':login2', $login, PDO::PARAM_STR);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_OBJ);

        if ($user && password_verify($password, $user->password)) {
            unset($user->password);

            $_SESSION['user_id'] = $user->id;
            $_SESSION['username'] = $user->username;
            $_SESSION['firstname'] = $user->firstname;
            $_SESSION['lastname'] = $user->lastname;
            $_SESSION['role'] = $user->role;
            $_SESSION['rolenumber'] = $user->rolenumber;
            $_SESSION['ssid'] = $user->ssid;
            $_SESSION['powers'] = $user->powers;

            if (!empty($user->position) && $user->position === 'member') {
                $m = $dbh->prepare("SELECT user_id FROM members WHERE login_user_id = :uid LIMIT 1");
                $m->bindValue(':uid', $user->id, PDO::PARAM_INT);
                $m->execute();
                $memberRow = $m->fetch(PDO::FETCH_OBJ);
                if ($memberRow) {
                    $_SESSION['wedding_owner_id'] = (int) $memberRow->user_id;
                }
            }
            if (!isset($_SESSION['wedding_owner_id'])) {
                $_SESSION['wedding_owner_id'] = $user->id;
            }

            $permissions = (isset($_SESSION['wedding_owner_id']) && $_SESSION['wedding_owner_id'] !== $user->id)
                ? wmis_get_user_permissions($dbh, $user->id)
                : ['*'];

            $token = session_id();
            $userProfile = (object) [
                'username'   => $user->username,
                'firstname'  => $user->firstname,
                'lastname'   => $user->lastname,
                'email'      => $user->email,
                'avatar'     => $user->avatar ?? null,
                'role'       => $user->role,
                'rolenumber' => $user->rolenumber,
                'ssid'       => $user->ssid,
                'powers'     => $user->powers,
                'position'   => $user->position ?? null,
                'permissions' => $permissions,
            ];

            echo json_encode([
                'message' => 'Login successful',
                'token'   => $token,
                'data'    => [
                    'username'    => $user->username,
                    'firstname'   => $user->firstname,
                    'lastname'    => $user->lastname,
                    'email'       => $user->email,
                    'role'        => $user->role,
                    'rolenumber'  => $user->rolenumber,
                    'ssid'        => $user->ssid,
                    'powers'      => $user->powers,
                    'permissions' => $permissions,
                    'userProfile' => $userProfile,
                ],
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'error' => 'Invalid email/username or password',
                'data'  => null,
            ]);
        }
    } catch (PDOException $e) {
        error_log('Login DB error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred. Please try again.']);
    }
}
