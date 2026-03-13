<?php
/**
 * Upload wedding profile photo: bride, groom, or couple.
 * POST multipart/form-data: type = bride|groom|couple, photo = file
 * Saves to uploads/wedding/{user_id}/ and returns URL path for storing in wedding_profiles.
 */
include("connect/header.php");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user_id'];
$allowedTypes = ['bride', 'groom', 'couple'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$type = isset($_POST['type']) ? trim(strtolower($_POST['type'])) : '';
if (!in_array($type, $allowedTypes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'type must be bride, groom, or couple']);
    exit;
}

if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $err = isset($_FILES['photo']['error']) ? (int) $_FILES['photo']['error'] : -1;
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds form MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temp folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file',
        UPLOAD_ERR_EXTENSION => 'Upload blocked by extension',
    ];
    $msg = $messages[$err] ?? 'Upload error (code ' . $err . ')';
    http_response_code(400);
    echo json_encode(['error' => $msg]);
    exit;
}

$file = $_FILES['photo'];
$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!in_array($mime, $allowedMimes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid image type. Use JPEG, PNG, GIF or WebP']);
    exit;
}

$ext = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp'
][$mime] ?? 'jpg';

$baseDir = __DIR__ . '/uploads/wedding/' . $userId;
if (!is_dir($baseDir)) {
    if (!mkdir($baseDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['error' => 'Could not create upload directory']);
        exit;
    }
}

$filename = $type . '.' . $ext;
$path = $baseDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $path)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
}

$relativePath = 'uploads/wedding/' . $userId . '/' . $filename;
echo json_encode([
    'message' => 'Photo uploaded',
    'data' => [
        'url' => $relativePath,
        'type' => $type
    ]
]);
