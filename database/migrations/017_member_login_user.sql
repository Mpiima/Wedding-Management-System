-- WMIS: Link member to a user account for login (when member is assigned a role).
-- Migration: 017_member_login_user

ALTER TABLE `members`
    ADD COLUMN `login_user_id` INT UNSIGNED NULL DEFAULT NULL AFTER `user_id`,
    ADD KEY `idx_members_login_user_id` (`login_user_id`),
    ADD CONSTRAINT `fk_members_login_user` FOREIGN KEY (`login_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
