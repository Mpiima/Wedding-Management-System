-- Run once if `school_onboarding` already exists without `curriculum_code`.
-- mysql -u root -p schpro < database/migrations/add_school_curriculum_code.sql

USE `schpro`;

ALTER TABLE `school_onboarding`
  ADD COLUMN `curriculum_code` ENUM('local_based','cambridge_international') NOT NULL DEFAULT 'local_based' AFTER `plan_code`;

