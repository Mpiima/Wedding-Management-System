<?php
include("../connect/header.php");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($method !== 'GET') {
    echo json_encode(['message' => 'Invalid request method']);
    exit;
}

if (empty($_SESSION['username']) && empty($_SESSION['ssid']) && empty($_SESSION['firstname'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated', 'data' => '']);
    exit;
}

try {
    $profile = [
        'username' => $_SESSION['username'] ?? null,
        'firstname' => $_SESSION['firstname'] ?? null,
        'lastname' => $_SESSION['lastname'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'number' => $_SESSION['rolenumber'] ?? null,
        'tenant_id' => ($_SESSION['role'] ?? '') === 'super_admin' ? 0 : 1,
        'permissions' => ['*'],
        'ssid' => $_SESSION['ssid'] ?? null,
    ];

    $ssid = $profile['ssid'];
    if (!empty($ssid)) {
      $s = $dbh->prepare("SELECT school_name, status, setup_completed, logo_url, school_motto, about_info, address, contact_email, contact_phone, principal_name
                          FROM school_onboarding WHERE school_number = :ssid LIMIT 1");
      $s->execute([':ssid' => $ssid]);
      $row = $s->fetch(PDO::FETCH_ASSOC);
      if ($row) {
          $profile['school_name'] = $row['school_name'];
          $profile['onboarding_status'] = $row['status'];
          $profile['setup_completed'] = (int)$row['setup_completed'] === 1;
          $profile['logo_url'] = $row['logo_url'];
          $profile['school_motto'] = $row['school_motto'];
          $profile['about_info'] = $row['about_info'];
          $profile['address'] = $row['address'];
          $profile['contact_email'] = $row['contact_email'];
          $profile['contact_phone'] = $row['contact_phone'];
          $profile['principal_name'] = $row['principal_name'];
      }
    }

    echo json_encode(['data' => $profile]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage(), 'data' => '']);
}

