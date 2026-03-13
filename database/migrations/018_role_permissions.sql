-- WMIS: Role permissions. JSON array of permission keys for each role (e.g. budget.add, contributions.edit).
-- Migration: 018_role_permissions

ALTER TABLE `roles`
    ADD COLUMN `permissions` TEXT DEFAULT NULL COMMENT 'JSON array of permission keys' AFTER `sort_order`;
