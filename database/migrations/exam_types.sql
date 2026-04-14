-- Exam types per academic year + study period (school-scoped)
CREATE TABLE IF NOT EXISTS `exam_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `academic_year_id` INT UNSIGNED NOT NULL,
  `study_period_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(128) NOT NULL,
  `max_score` DECIMAL(10,2) NOT NULL DEFAULT 100.00,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `show_on_report_card` TINYINT(1) NOT NULL DEFAULT 0,
  `report_column_no` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_types_scope_name` (`school_number`,`academic_year_id`,`study_period_id`,`name`),
  KEY `exam_types_year_period` (`academic_year_id`,`study_period_id`),
  KEY `exam_types_school` (`school_number`),
  CONSTRAINT `exam_types_year_fk` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_types_period_fk` FOREIGN KEY (`study_period_id`) REFERENCES `study_periods` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
