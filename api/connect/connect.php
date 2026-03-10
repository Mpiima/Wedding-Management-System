<?php
$hostname = getenv('DB_HOST') ?: 'localhost';
$database = getenv('DB_NAME') ?: 'wmis_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

// Only show errors in development
$isDev = (getenv('APP_ENV') ?: 'development') === 'development';
ini_set('display_errors', $isDev ? '1' : '0');
ini_set('display_startup_errors', $isDev ? '1' : '0');
error_reporting($isDev ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_STRICT);

try {
    $dbh = new PDO(
        "mysql:host=$hostname;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES  => false, // use real prepared statements
        ]
    );
} catch (PDOException $e) {
    if ($isDev) {
        echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    } else {
        echo json_encode(['error' => 'Database connection failed']);
    }
    exit;
}
