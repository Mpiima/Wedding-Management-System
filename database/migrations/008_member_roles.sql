-- WMIS: Committee = assign roles to members (many-to-many).
-- Migration: 008_member_roles

CREATE TABLE IF NOT EXISTS `member_roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `member_id` INT UNSIGNED NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_member_roles` (`member_id`, `role_id`),
    KEY `idx_member_roles_member_id` (`member_id`),
    KEY `idx_member_roles_role_id` (`role_id`),
    CONSTRAINT `fk_member_roles_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_member_roles_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
