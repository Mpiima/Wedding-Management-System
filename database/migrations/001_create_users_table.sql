-- WMIS: Users table
-- Migration: 001_create_users_table

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
