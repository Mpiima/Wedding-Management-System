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
    if (!isset($input['password'])) {
        echo json_encode(['error' => 'Password is required']);
        return;
    }

    $identifier = isset($input['email']) ? trim((string) $input['email']) : '';
    if ($identifier === '' && isset($input['login'])) {
        $identifier = trim((string) $input['login']);
    }
    if ($identifier === '') {
        echo json_encode(['error' => 'Email or portal username is required']);
        return;
    }

    $password = $input['password'];

    try {
        // Match by email or portal username (e.g. ADM12345)
        $query = $dbh->prepare("SELECT * FROM users WHERE (email = :id OR username = :id2) AND position != 'member' AND position != 'rejected' LIMIT 1");
        $query->execute([':id' => $identifier, ':id2' => $identifier]);
        
        $user = $query->fetchObject();
        
        // If user exists and passwords match
        if ($user && password_verify($password, $user->password)) {
            if ($user->position === 'pending-approval') {
                echo json_encode([
                    'error' => 'Your school account is pending super admin approval.',
                    'data' => ''
                ]);
                return;
            }

            $onboarding = null;
            $setupCompleted = false;
            $onboardingStatus = null;
            if (!empty($user->ssid)) {
                $s = $dbh->prepare("SELECT school_name, status, setup_completed FROM school_onboarding WHERE school_number = :ssid LIMIT 1");
                $s->execute([':ssid' => $user->ssid]);
                $onboarding = $s->fetchObject();
                if ($onboarding) {
                    $setupCompleted = ((int)$onboarding->setup_completed) === 1;
                    $onboardingStatus = $onboarding->status;
                }
                if ($onboardingStatus === 'rejected') {
                    echo json_encode([
                        'error' => 'Your school account was rejected. Contact support.',
                        'data' => ''
                    ]);
                    return;
                }
                if ($onboardingStatus === 'pending') {
                    echo json_encode([
                        'error' => 'Your school account is pending super admin approval.',
                        'data' => ''
                    ]);
                    return;
                }
            }

            // Set session variables
            $_SESSION['user_id'] = (int) $user->id;
            $_SESSION["username"] = $user->username; 
            $_SESSION["firstname"] = $user->firstname;
            $_SESSION["lastname"] = $user->lastname;
            $_SESSION["role"] = $user->role;
            $_SESSION["rolenumber"] = $user->rolenumber;
            $_SESSION["ssid"] = $user->ssid;
            $_SESSION["powers"] = $user->powers;

            $studentId = null;
            if (isset($user->student_id) && (int) $user->student_id > 0) {
                $studentId = (int) $user->student_id;
                $_SESSION['portal_student_id'] = $studentId;
            } else {
                unset($_SESSION['portal_student_id']);
            }

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
                    'student_id' => $studentId,
                    'school_name' => $onboarding ? $onboarding->school_name : null,
                    'onboarding_status' => $onboardingStatus,
                    'setup_completed' => $setupCompleted,
                    'userProfile' => $user
                ]
            ]);
        } else {
            // Failed login response
            echo json_encode([
                'error' => 'Invalid username or password',
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
