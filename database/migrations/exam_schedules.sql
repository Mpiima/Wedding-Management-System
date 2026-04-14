-- Exam schedules (per exam type, class, date/time) with subjects & supervisor links
CREATE TABLE IF NOT EXISTS `exam_schedules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `exam_type_id` INT UNSIGNED NOT NULL,
  `class_id` INT UNSIGNED NOT NULL,
  `exam_date` DATE NOT NULL,
  `exam_time` TIME NOT NULL,
  `exam_room` VARCHAR(255) DEFAULT NULL,
  `exam_requirements` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `exam_schedules_school` (`school_number`),
  KEY `exam_schedules_type` (`exam_type_id`),
  KEY `exam_schedules_class` (`class_id`),
  KEY `exam_schedules_date` (`exam_date`),
  CONSTRAINT `exam_schedules_type_fk` FOREIGN KEY (`exam_type_id`) REFERENCES `exam_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_schedules_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_schedule_subjects` (
  `exam_schedule_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`exam_schedule_id`, `subject_id`),
  KEY `ess_subject_idx` (`subject_id`),
  CONSTRAINT `ess_schedule_fk` FOREIGN KEY (`exam_schedule_id`) REFERENCES `exam_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ess_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `exam_schedule_supervisors` (
  `exam_schedule_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`exam_schedule_id`, `user_id`),
  KEY `essup_user_idx` (`user_id`),
  CONSTRAINT `essup_schedule_fk` FOREIGN KEY (`exam_schedule_id`) REFERENCES `exam_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `essup_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
