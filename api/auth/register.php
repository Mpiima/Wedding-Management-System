<?php
include("../connect/header.php");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($method !== 'POST') {
    echo json_encode(['message' => 'Invalid request method']);
    exit;
}

function genSchoolNumber($dbh)
{
    for ($i = 0; $i < 12; $i++) {
        $v = 'SCH-' . strtoupper(bin2hex(random_bytes(4)));
        $s = $dbh->prepare("SELECT id FROM school_onboarding WHERE school_number = :n LIMIT 1");
        $s->execute([':n' => $v]);
        if (!$s->fetch(PDO::FETCH_ASSOC)) return $v;
    }
    return 'SCH-' . time();
}

try {
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON body', 'data' => '']);
        exit;
    }

    $schoolName = trim((string)($input['schoolName'] ?? ''));
    $adminName = trim((string)($input['adminName'] ?? ''));
    $email = strtolower(trim((string)($input['email'] ?? '')));
    $phone = trim((string)($input['phone'] ?? ''));
    $plan = trim((string)($input['plan'] ?? 'professional'));
    $curriculum = trim((string)($input['curriculum'] ?? 'local_based'));
    $students = (int)($input['students'] ?? 0);
    $password = (string)($input['password'] ?? '');

    if ($schoolName === '' || $adminName === '' || $email === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => 'School name, admin name, email and password are required', 'data' => '']);
        exit;
    }

    $allowedCurriculum = ['local_based', 'cambridge_international'];
    if (!in_array($curriculum, $allowedCurriculum, true)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid curriculum selection', 'data' => '']);
        exit;
    }

    $exists = $dbh->prepare("SELECT id FROM school_onboarding WHERE admin_email = :email LIMIT 1");
    $exists->execute([':email' => $email]);
    if ($exists->fetch(PDO::FETCH_ASSOC)) {
        http_response_code(409);
        echo json_encode(['error' => 'A registration with this email already exists', 'data' => '']);
        exit;
    }

    $schoolNumber = genSchoolNumber($dbh);
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $ins = $dbh->prepare(
        "INSERT INTO school_onboarding
         (school_number, school_name, admin_name, admin_email, phone, plan_code, curriculum_code, expected_students, status, setup_completed)
         VALUES
         (:school_number, :school_name, :admin_name, :admin_email, :phone, :plan_code, :curriculum_code, :expected_students, 'pending', 0)"
    );
    $ins->execute([
        ':school_number' => $schoolNumber,
        ':school_name' => $schoolName,
        ':admin_name' => $adminName,
        ':admin_email' => $email,
        ':phone' => $phone,
        ':plan_code' => $plan,
        ':curriculum_code' => $curriculum,
        ':expected_students' => $students
    ]);

    // Create a login account but keep blocked until approval.
    $parts = preg_split('/\s+/', $adminName);
    $firstname = $parts[0] ?? $adminName;
    $lastname = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
    $username = strstr($email, '@', true) ?: $email;

    $usr = $dbh->prepare(
        "INSERT INTO users
         (username, firstname, lastname, email, password, role, rolenumber, ssid, powers, position)
         VALUES
         (:username, :firstname, :lastname, :email, :password, 'admin', :rolenumber, :ssid, '*', 'pending-approval')"
    );
    $usr->execute([
        ':username' => $username,
        ':firstname' => $firstname,
        ':lastname' => $lastname,
        ':email' => $email,
        ':password' => $hash,
        ':rolenumber' => $schoolNumber,
        ':ssid' => $schoolNumber
    ]);

    echo json_encode([
        'message' => 'Registration submitted. Waiting for super admin approval.',
        'data' => [
            'school_number' => $schoolNumber,
            'status' => 'pending'
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage(), 'data' => '']);
}

