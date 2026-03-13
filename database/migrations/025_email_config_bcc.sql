-- Migration: 025_email_config_bcc
-- BCC/copy-to addresses (comma-separated) for all outgoing emails e.g. pledge@zinitechnology.com

ALTER TABLE `email_config`
ADD COLUMN `bcc_emails` TEXT NULL DEFAULT NULL
COMMENT 'Comma-separated emails to BCC on all outgoing emails'
AFTER `from_name`;
