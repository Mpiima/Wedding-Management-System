-- WMIS: Users table (reference; applied by migration 001_create_users_table.sql)
-- To apply automatically, run from project root:  php database/migrate.php   or  npm run migrate
-- Ensure database exists and .env has DB_NAME=wmis_db (optional: DB_HOST, DB_USER, DB_PASS)

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username` VARCHAR(255) NOT NULL,
    `firstname` VARCHAR(255) DEFAULT NULL,
    `lastname` VARCHAR(255) DEFAULT NULL,
    `email` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL COMMENT 'Use PHP password_hash() when inserting',
    `role` VARCHAR(50) DEFAULT NULL COMMENT 'e.g. Admin, User',
    `rolenumber` INT DEFAULT NULL,
    `ssid` VARCHAR(255) DEFAULT NULL,
    `powers` TEXT DEFAULT NULL COMMENT 'JSON or comma-separated permissions',
    `position` VARCHAR(100) DEFAULT NULL COMMENT 'e.g. member = excluded from login',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_users_email` (`email`),
    UNIQUE KEY `uq_users_username` (`username`),
    KEY `idx_users_email` (`email`),
    KEY `idx_users_position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Example insert (password is 'password123' hashed with PASSWORD_BCRYPT):
-- INSERT INTO users (username, firstname, lastname, email, password, role) VALUES
-- ('admin', 'Admin', 'User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin');
