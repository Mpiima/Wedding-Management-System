-- Invited guests – for invitation cards and RSVP
CREATE TABLE IF NOT EXISTS `invited_guests` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `side` VARCHAR(50) DEFAULT NULL COMMENT 'Bride, Groom, Both',
    `email` VARCHAR(255) DEFAULT NULL,
    `phone` VARCHAR(100) DEFAULT NULL,
    `status` VARCHAR(20) NOT NULL DEFAULT 'Pending' COMMENT 'Pending, Confirmed, Declined',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_invited_guests_user_id` (`user_id`),
    CONSTRAINT `fk_invited_guests_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
