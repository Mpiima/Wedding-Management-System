<?php
/**
 * WMIS seed – creates default admin user if missing.
 * Run once after migrations:  php database/seed.php
 * Login: admin@example.com / password123
 */

$projectRoot = is_file(__DIR__ . '/../.env') ? __DIR__ . '/..' : __DIR__;
chdir($projectRoot);

if (is_file($projectRoot . '/.env')) {
    $lines = file($projectRoot . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $m)) {
            $key = trim($m[1]);
            $val = trim($m[2], " \t\"'");
            if (!getenv($key)) putenv("$key=$val");
        }
    }
}

$host     = getenv('DB_HOST') ?: 'localhost';
$database = getenv('DB_NAME') ?: 'wmis_db';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "Database connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

$email = 'admin@example.com';
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
$stmt->execute(['email' => $email]);
if ($stmt->fetch()) {
    echo "Admin user already exists ($email).\n";
    exit(0);
}

$passwordHash = password_hash('password123', PASSWORD_BCRYPT);
$stmt = $pdo->prepare("
    INSERT INTO users (username, firstname, lastname, email, password, role)
    VALUES (:username, :firstname, :lastname, :email, :password, :role)
");
$stmt->execute([
    'username'  => 'admin',
    'firstname' => 'Admin',
    'lastname'  => 'User',
    'email'     => $email,
    'password'  => $passwordHash,
    'role'      => 'Admin',
]);

echo "Admin user created: $email / password123\n";
