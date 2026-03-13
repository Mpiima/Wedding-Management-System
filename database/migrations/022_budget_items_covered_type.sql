-- Migration: 022_budget_items_covered_type
-- When status = COVERED, covered_type: DEFAULT = Covered by default (not from contributions), FROM_CONTRIBUTIONS = Covered from contributions

ALTER TABLE `budget_items`
ADD COLUMN `covered_type` VARCHAR(30) NULL DEFAULT NULL
COMMENT 'DEFAULT or FROM_CONTRIBUTIONS, only when status is COVERED'
AFTER `status`;
