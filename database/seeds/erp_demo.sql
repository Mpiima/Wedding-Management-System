-- Optional demo data for ERP module (school_number must match a real users.ssid).
-- Run once after erp_academic_module.sql. Adjust @school if needed.

USE `schpro`;

SET @school = '1';

INSERT INTO `academic_years` (`school_number`, `name`, `start_date`, `end_date`, `is_active`)
VALUES (@school, '2025 / 2026', '2025-01-15', '2025-12-15', 1);

SET @yid = LAST_INSERT_ID();

INSERT INTO `study_periods` (`school_number`, `academic_year_id`, `name`, `start_date`, `end_date`, `is_active`, `sort_order`)
VALUES
  (@school, @yid, 'Term 1', '2025-01-15', '2025-04-15', 1, 0),
  (@school, @yid, 'Term 2', '2025-05-01', '2025-08-31', 0, 1),
  (@school, @yid, 'Term 3', '2025-09-01', '2025-12-15', 0, 2);

INSERT IGNORE INTO `levels` (`school_number`, `name`, `sort_order`) VALUES
  (@school, 'Primary', 0),
  (@school, 'Secondary', 1);

SET @lid = (SELECT id FROM levels WHERE school_number = @school AND name = 'Primary' LIMIT 1);

INSERT IGNORE INTO `classes` (`school_number`, `level_id`, `name`, `sort_order`)
SELECT @school, @lid, CONCAT('P', n), n - 1 FROM (
  SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7
) AS t;

INSERT IGNORE INTO `streams` (`school_number`, `name`, `sort_order`) VALUES
  (@school, 'A', 0),
  (@school, 'B', 1);

INSERT IGNORE INTO `subjects` (`school_number`, `name`, `sort_order`) VALUES
  (@school, 'English', 0),
  (@school, 'Mathematics', 1),
  (@school, 'Science', 2);
