<?php
// Resume session from Bearer token if present (for SPA auth)
// Apache often doesn't pass Authorization to PHP; .htaccess sets it, or use getallheaders()
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if ($authHeader === '' && function_exists('getallheaders')) {
    foreach (getallheaders() ?: [] as $name => $value) {
        if (strtolower($name) === 'authorization') {
            $authHeader = $value;
            break;
        }
    }
}
if (preg_match('/^\s*Bearer\s+(\S+)\s*$/i', $authHeader, $m)) {
    $token = $m[1];
    if (ctype_alnum($token) || strlen($token) >= 20) {
        session_id($token);
    }
}

header("Content-Type: application/json");
session_start();
include("connect.php");
error_reporting(1);

$method = $_SERVER['REQUEST_METHOD'];
$rawInput = file_get_contents('php://input');
$input = $rawInput ? json_decode($rawInput, true) : [];
if (!is_array($input)) {
    $input = [];
}

// CORS: allow common dev origins (Vite uses 5173 or 5174)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowed = ['http://localhost:5173', 'http://localhost:5174', 'http://127.0.0.1:5173', 'http://127.0.0.1:5174', 'http://localhost:4000'];
if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400");
?>