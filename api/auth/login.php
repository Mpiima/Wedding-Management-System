<?php
include("../connect/header.php");

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
    if (!isset($input['email']) || !isset($input['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password are required']);
        return;
    }

    $email = trim($input['email']);
    $password = $input['password'];

    if ($email === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Email is required']);
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email format']);
        return;
    }

    if (!is_string($password) || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Password is required']);
        return;
    }

    try {
        $stmt = $dbh->prepare("SELECT id, username, firstname, lastname, email, role, rolenumber, ssid, powers, password FROM users WHERE email = :email AND (position IS NULL OR position != 'member') LIMIT 1");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
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

            $token = session_id();

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
                    'userProfile' => $user,
                ],
            ]);
        } else {
            http_response_code(401);
            echo json_encode([
                'error' => 'Invalid email or password',
                'data'  => null,
            ]);
        }
    } catch (PDOException $e) {
        error_log('Login DB error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'An error occurred. Please try again.']);
    }
}
