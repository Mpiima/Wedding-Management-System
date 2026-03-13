<?php
/**
 * WMIS migration runner – runs pending SQL files in database/migrations/
 * Usage: php database/migrate.php   (from project root)
 *    or: php migrate.php            (from database/ folder)
 */

$projectRoot = is_file(__DIR__ . '/../.env') ? __DIR__ . '/..' : __DIR__;
chdir($projectRoot);

// Load .env into $_ENV (optional; connect.php uses getenv with fallbacks)
if (is_file($projectRoot . '/.env')) {
    $lines = file($projectRoot . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', $line, $m)) {
            $key = trim($m[1]);
            $val = trim($m[2], " \t\"'");
            if (!getenv($key)) putenv("$key=$val");
            $_ENV[$key] = $val;
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
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    fwrite(STDERR, "Database connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

$migrationsDir = $projectRoot . '/database/migrations';
if (!is_dir($migrationsDir)) {
    fwrite(STDERR, "Migrations directory not found: $migrationsDir\n");
    exit(1);
}

// Create migrations tracking table
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `migrations` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(255) NOT NULL,
        `run_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_migrations_name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$stmt = $pdo->prepare("INSERT IGNORE INTO migrations (name) VALUES (:name)");

$files = glob($migrationsDir . '/*.sql');
sort($files);
$run = 0;

foreach ($files as $path) {
    $name = basename($path);
    $exists = $pdo->query("SELECT 1 FROM migrations WHERE name = " . $pdo->quote($name))->fetch();
    if ($exists) continue;

    $sql = file_get_contents($path);
    if ($sql === false) {
        fwrite(STDERR, "Could not read: $path\n");
        exit(1);
    }

    // Run each statement (split by semicolon; strip comments so semicolons in comments don't break split)
    $sql = preg_replace('/^\s*--[^\n]*/m', '', $sql);
    $sql = preg_replace('/^\s*\/\*.*?\*\//ms', '', $sql);
    $statements = array_filter(array_map('trim', explode(';', $sql)), function ($s) {
        return $s !== '';
    });

    foreach ($statements as $statement) {
        if (trim($statement) === '') continue;
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            $code = (string) $e->getCode();
            $msg = $e->getMessage();
            if (($code === '42S21' || $code === '1060') && strpos($msg, 'Duplicate column') !== false) continue;
            if (($code === '42S21' || $code === '1061') && (strpos($msg, 'Duplicate key') !== false || strpos($msg, 'duplicate key') !== false)) continue;
            if ($code === '1091' && (strpos($msg, 'check that column') !== false || strpos($msg, 'check that key') !== false)) continue;
            if (($code === '1022' || $code === '1826') && strpos($msg, 'Duplicate') !== false) continue;
            throw $e;
        }
    }

    $stmt->execute(['name' => $name]);
    $run++;
    echo "Ran: $name\n";
}

if ($run === 0) {
    echo "No new migrations.\n";
} else {
    echo "Done. $run migration(s) applied.\n";
}
