-- Migration: 024_email_config
-- Single row (id=1) for system-wide SMTP / email configuration (PHPMailer).

CREATE TABLE IF NOT EXISTS `email_config` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `smtp_host` VARCHAR(255) NOT NULL DEFAULT '',
    `smtp_port` INT UNSIGNED NOT NULL DEFAULT 587,
    `smtp_username` VARCHAR(255) NULL DEFAULT NULL,
    `smtp_password` VARCHAR(255) NULL DEFAULT NULL,
    `encryption` VARCHAR(20) NOT NULL DEFAULT 'tls' COMMENT 'none, tls, ssl',
    `from_email` VARCHAR(255) NOT NULL DEFAULT '',
    `from_name` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
