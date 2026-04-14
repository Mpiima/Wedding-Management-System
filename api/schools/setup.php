<?php
include("../connect/header.php");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

if ($method !== 'POST') {
    echo json_encode(['message' => 'Invalid request method']);
    exit;
}

if (empty($_SESSION['ssid'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthenticated', 'data' => '']);
    exit;
}

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON body', 'data' => '']);
    exit;
}

$ssid = (string)$_SESSION['ssid'];
$payload = [
    ':ssid' => $ssid,
    ':logo_url' => trim((string)($input['logoUrl'] ?? '')),
    ':school_motto' => trim((string)($input['schoolMotto'] ?? '')),
    ':about_info' => trim((string)($input['aboutInfo'] ?? '')),
    ':address' => trim((string)($input['address'] ?? '')),
    ':contact_email' => trim((string)($input['contactEmail'] ?? '')),
    ':contact_phone' => trim((string)($input['contactPhone'] ?? '')),
    ':principal_name' => trim((string)($input['principalName'] ?? ''))
];

try {
    $check = $dbh->prepare("SELECT id, status FROM school_onboarding WHERE school_number = :ssid LIMIT 1");
    $check->execute([':ssid' => $ssid]);
    $school = $check->fetch(PDO::FETCH_ASSOC);
    if (!$school) {
        http_response_code(404);
        echo json_encode(['error' => 'School onboarding record not found', 'data' => '']);
        exit;
    }
    if ($school['status'] !== 'approved') {
        http_response_code(403);
        echo json_encode(['error' => 'School is not approved yet', 'data' => '']);
        exit;
    }

    $up = $dbh->prepare(
        "UPDATE school_onboarding
         SET logo_url = :logo_url,
             school_motto = :school_motto,
             about_info = :about_info,
             address = :address,
             contact_email = :contact_email,
             contact_phone = :contact_phone,
             principal_name = :principal_name,
             setup_completed = 1
         WHERE school_number = :ssid"
    );
    $up->execute($payload);

    echo json_encode(['message' => 'School setup completed successfully', 'data' => ['setup_completed' => true]]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage(), 'data' => '']);
}

