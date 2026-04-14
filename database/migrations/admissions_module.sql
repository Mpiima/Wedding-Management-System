-- Admissions & enrollment — run after ERP academic tables exist.
USE `schpro`;

ALTER TABLE `classes` ADD COLUMN `max_students` INT UNSIGNED NOT NULL DEFAULT 40 AFTER `sort_order`;

CREATE TABLE IF NOT EXISTS `students` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `admission_number` VARCHAR(32) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `gender` VARCHAR(16) NOT NULL DEFAULT '',
  `dob` DATE DEFAULT NULL,
  `guardian_name` VARCHAR(255) NOT NULL DEFAULT '',
  `guardian_phone` VARCHAR(64) NOT NULL DEFAULT '',
  `guardian_email` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_admission_school` (`school_number`, `admission_number`),
  KEY `students_school_idx` (`school_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `applicants` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `application_number` VARCHAR(32) NOT NULL,
  `pipeline_status` ENUM('application','review','accepted','enrolled','rejected','waitlist') NOT NULL DEFAULT 'application',
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `gender` VARCHAR(16) NOT NULL DEFAULT '',
  `dob` DATE DEFAULT NULL,
  `parent_name` VARCHAR(255) NOT NULL DEFAULT '',
  `parent_phone` VARCHAR(64) NOT NULL DEFAULT '',
  `parent_email` VARCHAR(255) DEFAULT NULL,
  `applying_level_id` INT UNSIGNED DEFAULT NULL,
  `applying_class_id` INT UNSIGNED DEFAULT NULL,
  `previous_school` VARCHAR(255) DEFAULT NULL,
  `address` TEXT,
  `medical_info` TEXT,
  `applicant_notes` TEXT,
  `internal_notes` TEXT,
  `ready_for_decision` TINYINT(1) NOT NULL DEFAULT 0,
  `flagged_issues` VARCHAR(500) DEFAULT NULL,
  `rejection_reason` VARCHAR(500) DEFAULT NULL,
  `notify_parent_on_reject` TINYINT(1) NOT NULL DEFAULT 0,
  `student_id` INT UNSIGNED DEFAULT NULL,
  `draft_json` JSON DEFAULT NULL,
  `interview_at` DATETIME DEFAULT NULL,
  `interview_status` ENUM('none','scheduled','completed','no_show') NOT NULL DEFAULT 'none',
  `interview_notes` VARCHAR(500) DEFAULT NULL,
  `waitlist_position` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `applicants_app_num_school` (`school_number`, `application_number`),
  KEY `applicants_school_status_idx` (`school_number`, `pipeline_status`),
  KEY `applicants_student_idx` (`student_id`),
  CONSTRAINT `applicants_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applicants_level_fk` FOREIGN KEY (`applying_level_id`) REFERENCES `levels` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applicants_class_fk` FOREIGN KEY (`applying_class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `applicant_status_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `applicant_id` INT UNSIGNED NOT NULL,
  `from_status` VARCHAR(32) DEFAULT NULL,
  `to_status` VARCHAR(32) NOT NULL,
  `actor` VARCHAR(128) DEFAULT NULL,
  `note` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `applicant_status_log_app_idx` (`applicant_id`),
  CONSTRAINT `applicant_status_log_fk` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `applicant_comments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `applicant_id` INT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  `created_by` VARCHAR(128) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `applicant_comments_app_idx` (`applicant_id`),
  CONSTRAINT `applicant_comments_fk` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `applicant_documents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `applicant_id` INT UNSIGNED NOT NULL,
  `doc_type` VARCHAR(64) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `applicant_documents_app_idx` (`applicant_id`),
  CONSTRAINT `applicant_documents_fk` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `academic_year_id` INT UNSIGNED NOT NULL,
  `study_period_id` INT UNSIGNED NOT NULL,
  `class_id` INT UNSIGNED NOT NULL,
  `stream_id` INT UNSIGNED DEFAULT NULL,
  `enrolled_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `enrollments_student_period` (`student_id`, `academic_year_id`, `study_period_id`),
  KEY `enrollments_class_idx` (`class_id`, `academic_year_id`),
  CONSTRAINT `enrollments_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_year_fk` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `enrollments_period_fk` FOREIGN KEY (`study_period_id`) REFERENCES `study_periods` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `enrollments_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `enrollments_stream_fk` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_fee_links` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT UNSIGNED NOT NULL,
  `academic_year_id` INT UNSIGNED NOT NULL,
  `fee_profile` VARCHAR(128) NOT NULL DEFAULT 'class_default',
  `overridden` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_fee_year` (`student_id`, `academic_year_id`),
  CONSTRAINT `student_fee_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_fee_year_fk` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `admission_notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `applicant_id` INT UNSIGNED NOT NULL,
  `channel` VARCHAR(32) NOT NULL DEFAULT 'in_app',
  `event_key` VARCHAR(64) NOT NULL,
  `message` VARCHAR(500) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admission_notif_app_idx` (`applicant_id`),
  CONSTRAINT `admission_notif_fk` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
