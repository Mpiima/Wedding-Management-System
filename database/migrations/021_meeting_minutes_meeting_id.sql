-- Refactor meeting_minutes: link to meetings via meeting_id. Minutes are for an existing meeting.
-- Migration: 021_meeting_minutes_meeting_id
-- Run after 020_meetings. Uses plain SQL only. Migrate.php ignores "Duplicate column/key" so safe to re-run if 021 failed partway.

ALTER TABLE `meeting_minutes` ADD COLUMN `meeting_id` INT UNSIGNED NULL DEFAULT NULL AFTER `user_id`;
ALTER TABLE `meeting_minutes` ADD KEY `idx_meeting_minutes_meeting_id` (`meeting_id`);

ALTER TABLE `meetings` ADD COLUMN `source_mm_id` INT UNSIGNED NULL DEFAULT NULL AFTER `agenda`;

INSERT INTO `meetings` (`user_id`, `title`, `meeting_date`, `agenda`, `source_mm_id`)
SELECT `user_id`, `title`, `meeting_date`, COALESCE(`content`, ''), `id` FROM `meeting_minutes` WHERE `meeting_id` IS NULL;

UPDATE `meeting_minutes` m
INNER JOIN `meetings` m2 ON m2.`source_mm_id` = m.`id`
SET m.`meeting_id` = m2.`id`;

ALTER TABLE `meetings` DROP COLUMN `source_mm_id`;

ALTER TABLE `meeting_minutes` DROP COLUMN `title`;
ALTER TABLE `meeting_minutes` DROP COLUMN `meeting_date`;

ALTER TABLE `meeting_minutes` ADD CONSTRAINT `fk_meeting_minutes_meeting` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`) ON DELETE CASCADE;
ALTER TABLE `meeting_minutes` MODIFY COLUMN `meeting_id` INT UNSIGNED NOT NULL;
