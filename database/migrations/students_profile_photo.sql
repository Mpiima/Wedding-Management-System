-- Student profile: photo + free-text notes (run once per database)
ALTER TABLE `students`
  ADD COLUMN `photo_path` VARCHAR(512) NULL DEFAULT NULL AFTER `guardian_email`,
  ADD COLUMN `bio_notes` TEXT NULL AFTER `photo_path`;
