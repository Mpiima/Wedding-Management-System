-- Run once if `subscription_plans` already exists without `number` column.
-- mysql -u root -p schpro < database/migrations/add_subscription_plans_number.sql

USE `schpro`;

ALTER TABLE `subscription_plans`
  ADD COLUMN `number` VARCHAR(64) NULL COMMENT 'Unique public UID' AFTER `id`;

UPDATE `subscription_plans`
SET `number` = CONCAT('SP-MIG-', `id`, '-', UNIX_TIMESTAMP(COALESCE(`created_at`, NOW())))
WHERE `number` IS NULL OR `number` = '';

ALTER TABLE `subscription_plans`
  MODIFY `number` VARCHAR(64) NOT NULL,
  ADD UNIQUE KEY `subscription_plans_number_unique` (`number`);
