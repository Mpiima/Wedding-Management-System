-- Fees & Billing module (MySQL 5.7+ / MariaDB)
-- Run after core schema: mysql -u root -p schpro < database/migrations/finance_billing.sql

USE `schpro`;

-- ---------------------------------------------------------------------------
-- Reusable fee templates (Primary bundle, Secondary bundle, …)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fee_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fee_templates_school_idx` (`school_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fee_template_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `item_type` ENUM('mandatory','optional') NOT NULL DEFAULT 'mandatory',
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fee_template_items_tpl_idx` (`template_id`),
  CONSTRAINT `fee_template_items_tpl_fk` FOREIGN KEY (`template_id`) REFERENCES `fee_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Fee structure: per year + period + set of classes + line items
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fee_structures` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `academic_year_id` INT UNSIGNED NOT NULL,
  `study_period_id` INT UNSIGNED NOT NULL,
  `template_id` INT UNSIGNED DEFAULT NULL COMMENT 'Optional source template',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fee_structures_school_idx` (`school_number`),
  KEY `fee_structures_year_period_idx` (`academic_year_id`, `study_period_id`),
  CONSTRAINT `fee_structures_year_fk` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structures_period_fk` FOREIGN KEY (`study_period_id`) REFERENCES `study_periods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fee_structures_tpl_fk` FOREIGN KEY (`template_id`) REFERENCES `fee_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fee_structure_classes` (
  `fee_structure_id` INT UNSIGNED NOT NULL,
  `class_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`fee_structure_id`, `class_id`),
  KEY `fsc_class_idx` (`class_id`),
  CONSTRAINT `fsc_structure_fk` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fsc_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fee_structure_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fee_structure_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `item_type` ENUM('mandatory','optional') NOT NULL DEFAULT 'mandatory',
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fee_structure_items_fs_idx` (`fee_structure_id`),
  CONSTRAINT `fee_structure_items_fs_fk` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Student credit wallet (overpayments)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_credit_wallet` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `balance` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `credit_wallet_student_school` (`school_number`, `student_id`),
  CONSTRAINT `credit_wallet_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Invoices (one per student per study period)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `invoice_number` VARCHAR(32) NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `academic_year_id` INT UNSIGNED NOT NULL,
  `study_period_id` INT UNSIGNED NOT NULL,
  `class_id` INT UNSIGNED NOT NULL,
  `fee_structure_id` INT UNSIGNED DEFAULT NULL,
  `subtotal` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `discount_percent` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  `credit_applied` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `amount_paid` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `balance_due` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `payments_started` TINYINT(1) NOT NULL DEFAULT 0,
  `locked` TINYINT(1) NOT NULL DEFAULT 0,
  `due_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` VARCHAR(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number_school` (`school_number`, `invoice_number`),
  UNIQUE KEY `invoice_student_period` (`student_id`, `academic_year_id`, `study_period_id`),
  KEY `invoices_school_status_idx` (`school_number`, `status`),
  KEY `invoices_period_idx` (`study_period_id`),
  CONSTRAINT `inv_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inv_year_fk` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `inv_period_fk` FOREIGN KEY (`study_period_id`) REFERENCES `study_periods` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `inv_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `inv_fs_fk` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_lines` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `line_source` ENUM('structure','adjustment','discount','penalty') NOT NULL DEFAULT 'structure',
  `amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `item_type` ENUM('mandatory','optional') NOT NULL DEFAULT 'mandatory',
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `invoice_lines_inv_idx` (`invoice_id`),
  CONSTRAINT `invoice_lines_inv_fk` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_installments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` INT UNSIGNED NOT NULL,
  `sequence_no` INT NOT NULL DEFAULT 1,
  `due_date` DATE NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `amount_paid` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `invoice_inst_inv_idx` (`invoice_id`),
  CONSTRAINT `invoice_inst_inv_fk` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_adjustments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` INT UNSIGNED NOT NULL,
  `label` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `reason` VARCHAR(500) DEFAULT NULL,
  `created_by` VARCHAR(128) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inv_adj_inv_idx` (`invoice_id`),
  CONSTRAINT `inv_adj_inv_fk` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `invoice_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(14,2) NOT NULL,
  `method` ENUM('cash','mobile_money','bank','card','other') NOT NULL DEFAULT 'cash',
  `reference` VARCHAR(128) DEFAULT NULL,
  `paid_at` DATE NOT NULL,
  `receipt_number` VARCHAR(32) NOT NULL,
  `notes` VARCHAR(500) DEFAULT NULL,
  `recorded_by` VARCHAR(128) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `payments_inv_idx` (`invoice_id`),
  KEY `payments_student_idx` (`student_id`),
  KEY `payments_school_date_idx` (`school_number`, `paid_at`),
  CONSTRAINT `payments_inv_fk` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `payments_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `finance_notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `channel` VARCHAR(32) NOT NULL DEFAULT 'in_app',
  `event_key` VARCHAR(64) NOT NULL,
  `message` VARCHAR(500) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fin_notif_student_idx` (`student_id`),
  CONSTRAINT `fin_notif_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `finance_audit_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `entity_type` VARCHAR(32) NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(64) NOT NULL,
  `detail` VARCHAR(500) DEFAULT NULL,
  `actor` VARCHAR(128) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fin_audit_school_idx` (`school_number`, `entity_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
