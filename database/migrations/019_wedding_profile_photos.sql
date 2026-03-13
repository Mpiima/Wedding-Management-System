-- Add photo URL columns for bride, groom, and couple (both) images
-- Migration: 019_wedding_profile_photos

ALTER TABLE `wedding_profiles`
    ADD COLUMN `bride_photo` VARCHAR(500) DEFAULT NULL AFTER `venue_address`,
    ADD COLUMN `groom_photo` VARCHAR(500) DEFAULT NULL AFTER `bride_photo`,
    ADD COLUMN `couple_photo` VARCHAR(500) DEFAULT NULL AFTER `groom_photo`;
