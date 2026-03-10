-- Expenditures: item, amount, description
CREATE TABLE IF NOT EXISTS `expenditures` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `item` VARCHAR(255) NOT NULL,
    `amount` DECIMAL(15,2) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `expenditure_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_expenditures_user_id` (`user_id`),
    CONSTRAINT `fk_expenditures_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
