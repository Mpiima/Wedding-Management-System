<?php
include("../connect/header.php");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0); // Handle CORS preflight request
}

switch ($method) {
    case 'POST':
        handleLogin($dbh, $input);
        break;
    default:
        echo json_encode(['message' => 'Invalid request method']);
        break;
}

function handleLogin($dbh, $input) {
    if (!isset($input['email']) || !isset($input['password'])) {
        echo json_encode(['error' => 'Email and password are required']);
        return;
    }

    $email = $input['email'];
    $password = $input['password'];

    try {
        // Prepare the SQL query to fetch the user by email
        $query = $dbh->prepare("SELECT * FROM users WHERE email = :email  AND position != 'member' LIMIT 1");
        $query->bindParam(':email', $email);
        $query->execute();
        
        $user = $query->fetchObject();
        
        // If user exists and passwords match
        if ($user && password_verify($password, $user->password)) {
            // Set session variables
            $_SESSION["username"] = $user->username; 
            $_SESSION["firstname"] = $user->firstname;
            $_SESSION["lastname"] = $user->lastname;
            $_SESSION["role"] = $user->role;
            $_SESSION["rolenumber"] = $user->rolenumber;
            $_SESSION["ssid"] = $user->ssid;
            $_SESSION["powers"] = $user->powers;

            // Success response with user data
            echo json_encode([
                'message' => 'Login Successful',
                'data' => [
                    'username' => $user->username,
                    'firstname' => $user->firstname,
                    'lastname' => $user->lastname,
                    'email' => $user->email,
                    'role' => $user->role,
                    'rolenumber' => $user->rolenumber,
                    'ssid' => $user->ssid,
                    'userProfile' => $user
                ]
            ]);
        } else {
            // Failed login response
            echo json_encode([
                'error' => 'Invalid email or password',
                'data' => ''
            ]);
        }
    } catch (Exception $e) {
        // Handle exception and error logging
        echo json_encode([
            'error' => 'An error occurred: ' . $e->getMessage(),
            'data' => ''
        ]);
    }
}
?>
