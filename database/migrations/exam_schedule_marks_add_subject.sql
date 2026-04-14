-- Per-subject marks: add subject_id (replaces one mark per schedule+student).
-- Clears legacy rows without a subject; re-enter marks per subject after running.

DELETE FROM `exam_schedule_marks`;

ALTER TABLE `exam_schedule_marks`
  DROP INDEX `exam_marks_schedule_student`,
  ADD COLUMN `subject_id` INT UNSIGNED NOT NULL AFTER `student_id`,
  ADD UNIQUE KEY `exam_marks_schedule_student_subject` (`exam_schedule_id`, `student_id`, `subject_id`),
  ADD KEY `exam_marks_subject_idx` (`subject_id`),
  ADD CONSTRAINT `exam_marks_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;
