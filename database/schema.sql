-- SchPro360 — core tables for API auth (MySQL 5.7+ / MariaDB)
-- Run in phpMyAdmin or: mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS `schpro` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `schpro`;

-- ---------------------------------------------------------------------------
-- users — referenced by api/auth/login.php
-- Required columns: email, password, position (login excludes position = 'member')
-- plus: username, firstname, lastname, role, rolenumber, ssid, powers
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `firstname` VARCHAR(100) NOT NULL DEFAULT '',
  `lastname` VARCHAR(100) NOT NULL DEFAULT '',
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL COMMENT 'PHP password_hash / password_verify',
  `role` VARCHAR(64) NOT NULL DEFAULT 'admin',
  `rolenumber` VARCHAR(64) NOT NULL DEFAULT '',
  `ssid` VARCHAR(64) NOT NULL DEFAULT '' COMMENT 'School / tenant identifier',
  `powers` VARCHAR(500) NOT NULL DEFAULT '' COMMENT 'Comma-separated permission keys',
  `position` VARCHAR(64) NOT NULL DEFAULT 'staff' COMMENT 'Login blocked when value is member',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_position_idx` (`position`),
  KEY `users_ssid_idx` (`ssid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password for all seed rows: demo123 (bcrypt). Safe to re-run (skips duplicates).
INSERT IGNORE INTO `users`
  (`username`, `firstname`, `lastname`, `email`, `password`, `role`, `rolenumber`, `ssid`, `powers`, `position`)
VALUES
  (
    'superadmin',
    'Super',
    'Admin',
    'superadmin@schpro360.local',
    '$2y$10$BsBz/sStGAEAbPFkoWMHgeFDPLBuBJMnBdAxMCbqlS/.W25iTKBza',
    'super_admin',
    '1',
    '0',
    '*',
    'admin'
  ),
  (
    'schooladmin',
    'School',
    'Admin',
    'admin@schpro360.local',
    '$2y$10$BsBz/sStGAEAbPFkoWMHgeFDPLBuBJMnBdAxMCbqlS/.W25iTKBza',
    'admin',
    '2',
    '1',
    'students.view,finance.view,settings.view',
    'admin'
  ),
  (
    'student1',
    'Demo',
    'Student',
    'student@schpro360.local',
    '$2y$10$BsBz/sStGAEAbPFkoWMHgeFDPLBuBJMnBdAxMCbqlS/.W25iTKBza',
    'student',
    '3',
    '1',
    'portal.view',
    'student'
  ),
  (
    'parent1',
    'Demo',
    'Parent',
    'parent@schpro360.local',
    '$2y$10$BsBz/sStGAEAbPFkoWMHgeFDPLBuBJMnBdAxMCbqlS/.W25iTKBza',
    'parent',
    '4',
    '1',
    'portal.view',
    'parent'
  );

-- ---------------------------------------------------------------------------
-- subscription_plans — Super Admin (api/super-admin/subscription-plans.php)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `subscription_plans` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `number` VARCHAR(64) NOT NULL COMMENT 'Unique public UID (generated on create)',
  `code` VARCHAR(64) NULL DEFAULT NULL COMMENT 'Optional unique slug e.g. starter',
  `name` VARCHAR(255) NOT NULL,
  `price_monthly` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `features_json` TEXT NULL COMMENT 'JSON array of feature strings',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscription_plans_number_unique` (`number`),
  UNIQUE KEY `subscription_plans_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `subscription_plans` (`number`, `code`, `name`, `price_monthly`, `features_json`, `is_active`, `sort_order`)
VALUES
  ('SP-SEED-STARTER-0001', 'starter', 'Starter', 0.00, '["Up to 100 students","Email support"]', 1, 1),
  ('SP-SEED-PRO-0002', 'professional', 'Professional', 4999.00, '["Unlimited students","Priority support","Reports"]', 1, 2),
  ('SP-SEED-ENT-0003', 'enterprise', 'Enterprise', 19999.00, '["Dedicated manager","SLA","Custom integrations"]', 1, 3);

-- ---------------------------------------------------------------------------
-- school_onboarding — registration, approval lifecycle, and setup completion
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `school_onboarding` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL COMMENT 'Unique school UID shared with users.ssid',
  `school_name` VARCHAR(255) NOT NULL,
  `admin_name` VARCHAR(255) NOT NULL,
  `admin_email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(64) DEFAULT NULL,
  `plan_code` VARCHAR(64) DEFAULT 'professional',
  `curriculum_code` ENUM('local_based','cambridge_international') NOT NULL DEFAULT 'local_based',
  `expected_students` INT DEFAULT 0,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `rejection_reason` VARCHAR(500) DEFAULT NULL,
  `approved_by` VARCHAR(64) DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `setup_completed` TINYINT(1) NOT NULL DEFAULT 0,
  `logo_url` VARCHAR(500) DEFAULT NULL,
  `school_motto` VARCHAR(255) DEFAULT NULL,
  `about_info` TEXT DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `contact_email` VARCHAR(255) DEFAULT NULL,
  `contact_phone` VARCHAR(64) DEFAULT NULL,
  `principal_name` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_onboarding_school_number_unique` (`school_number`),
  UNIQUE KEY `school_onboarding_admin_email_unique` (`admin_email`),
  KEY `school_onboarding_status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- ERP Academic module (school-scoped via school_number = users.ssid)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `academic_years` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `name` VARCHAR(128) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `academic_years_school_idx` (`school_number`),
  KEY `academic_years_school_active_idx` (`school_number`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `study_periods` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `academic_year_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(128) NOT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 0,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `study_periods_year_idx` (`academic_year_id`),
  KEY `study_periods_school_idx` (`school_number`),
  CONSTRAINT `study_periods_year_fk` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `exam_schedule_marks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `exam_schedule_id` INT UNSIGNED NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `score` DECIMAL(10,2) DEFAULT NULL,
  `teacher_comment` TEXT,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exam_marks_schedule_student_subject` (`exam_schedule_id`, `student_id`, `subject_id`),
  KEY `exam_marks_school` (`school_number`),
  KEY `exam_marks_student` (`student_id`),
  KEY `exam_marks_subject_idx` (`subject_id`),
  CONSTRAINT `exam_marks_schedule_fk` FOREIGN KEY (`exam_schedule_id`) REFERENCES `exam_schedules` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_marks_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `exam_marks_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `levels` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `name` VARCHAR(128) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `levels_school_idx` (`school_number`),
  UNIQUE KEY `levels_school_name_unique` (`school_number`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `classes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `level_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(128) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `max_students` INT UNSIGNED NOT NULL DEFAULT 40,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `classes_school_idx` (`school_number`),
  KEY `classes_level_idx` (`level_id`),
  UNIQUE KEY `classes_level_name_unique` (`level_id`, `name`),
  CONSTRAINT `classes_level_fk` FOREIGN KEY (`level_id`) REFERENCES `levels` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `streams` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `name` VARCHAR(64) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `streams_school_idx` (`school_number`),
  UNIQUE KEY `streams_school_name_unique` (`school_number`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `class_streams` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_id` INT UNSIGNED NOT NULL,
  `stream_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_streams_unique` (`class_id`, `stream_id`),
  KEY `class_streams_stream_idx` (`stream_id`),
  CONSTRAINT `class_streams_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_streams_stream_fk` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `subjects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `name` VARCHAR(128) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `subjects_school_idx` (`school_number`),
  UNIQUE KEY `subjects_school_name_unique` (`school_number`, `name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `class_subjects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `class_subjects_unique` (`class_id`, `subject_id`),
  KEY `class_subjects_subject_idx` (`subject_id`),
  CONSTRAINT `class_subjects_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `class_subjects_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `school_erp_wizard` (
  `school_number` VARCHAR(64) NOT NULL,
  `current_step` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `completed_at` DATETIME DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`school_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Admissions & enrollment (school-scoped)
-- ---------------------------------------------------------------------------
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
  KEY `applicants_dup_idx` (`school_number`, `parent_phone`, `first_name`, `last_name`),
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

CREATE TABLE IF NOT EXISTS `attendance_records` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `academic_year_id` INT UNSIGNED NOT NULL,
  `study_period_id` INT UNSIGNED NOT NULL,
  `attendance_date` DATE NOT NULL,
  `class_id` INT UNSIGNED NOT NULL,
  `stream_id` INT UNSIGNED DEFAULT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `status` ENUM('present','absent','late','half_day','excused') NOT NULL DEFAULT 'present',
  `remarks` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_by_user_id` INT UNSIGNED DEFAULT NULL,
  `updated_by_user_id` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `att_unique_student_day_class` (`school_number`,`student_id`,`class_id`,`academic_year_id`,`study_period_id`,`attendance_date`),
  KEY `att_school_date` (`school_number`,`attendance_date`),
  KEY `att_class_date` (`school_number`,`class_id`,`attendance_date`),
  KEY `att_student_period` (`school_number`,`student_id`,`academic_year_id`,`study_period_id`),
  CONSTRAINT `att_year_fk` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `att_period_fk` FOREIGN KEY (`study_period_id`) REFERENCES `study_periods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `att_class_fk` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `att_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `att_stream_fk` FOREIGN KEY (`stream_id`) REFERENCES `streams` (`id`) ON DELETE SET NULL,
  CONSTRAINT `att_created_user_fk` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `att_updated_user_fk` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `school_attendance_records` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `school_number` VARCHAR(64) NOT NULL,
  `academic_year_id` INT UNSIGNED NOT NULL,
  `study_period_id` INT UNSIGNED NOT NULL,
  `attendance_date` DATE NOT NULL,
  `student_id` INT UNSIGNED NOT NULL,
  `check_in_time` TIME DEFAULT NULL,
  `check_out_time` TIME DEFAULT NULL,
  `remarks` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_by_user_id` INT UNSIGNED DEFAULT NULL,
  `updated_by_user_id` INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `school_att_unique_student_day` (`school_number`,`student_id`,`academic_year_id`,`study_period_id`,`attendance_date`),
  KEY `school_att_date_idx` (`school_number`,`attendance_date`),
  KEY `school_att_student_idx` (`school_number`,`student_id`,`academic_year_id`,`study_period_id`),
  CONSTRAINT `school_att_year_fk` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE CASCADE,
  CONSTRAINT `school_att_period_fk` FOREIGN KEY (`study_period_id`) REFERENCES `study_periods` (`id`) ON DELETE CASCADE,
  CONSTRAINT `school_att_student_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `school_att_created_user_fk` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `school_att_updated_user_fk` FOREIGN KEY (`updated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
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
