-- WMIS: Wedding profiles (one per user)
-- Migration: 002_wedding_profiles

CREATE TABLE IF NOT EXISTS `wedding_profiles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `bride_name` VARCHAR(255) DEFAULT NULL,
    `groom_name` VARCHAR(255) DEFAULT NULL,
    `wedding_date` DATE DEFAULT NULL,
    `venue_name` VARCHAR(255) DEFAULT NULL,
    `venue_address` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_wedding_profiles_user_id` (`user_id`),
    KEY `idx_wedding_profiles_user_id` (`user_id`),
    CONSTRAINT `fk_wedding_profiles_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
