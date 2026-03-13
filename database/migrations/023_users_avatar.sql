-- Migration: 023_users_avatar
-- Profile picture or avatar for logged-in user

ALTER TABLE `users`
ADD COLUMN `avatar` VARCHAR(500) NULL DEFAULT NULL
COMMENT 'Relative path to uploaded avatar image'
AFTER `email`;
