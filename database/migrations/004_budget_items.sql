-- WMIS: Budget items (per category). Category total = sum(items.cost). Progress = COVERED / total.
-- Migration: 004_budget_items

CREATE TABLE IF NOT EXISTS `budget_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `budget_category_id` INT UNSIGNED NOT NULL,
    `item_name` VARCHAR(255) NOT NULL,
    `quantity` DECIMAL(12,2) NOT NULL DEFAULT 1.00,
    `unit_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'quantity * unit_amount',
    `status` VARCHAR(20) NOT NULL DEFAULT 'NOT_COVERED' COMMENT 'COVERED | NOT_COVERED',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_budget_items_category` (`budget_category_id`),
    CONSTRAINT `fk_budget_items_category` FOREIGN KEY (`budget_category_id`) REFERENCES `budget_categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
