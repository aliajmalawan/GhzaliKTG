-- ============================================================================
-- Ghazali School & College Katlang - LIVE DATABASE UPDATE (cPanel / phpMyAdmin)
-- ============================================================================
-- How to run:
--   1. cPanel > phpMyAdmin > select the live database (left side).
--   2. Take a backup first: Export tab > Quick > Go.
--   3. Open the SQL tab, paste this whole file, click Go.
-- Safe to run more than once. It only ADDS columns / settings; it never drops
-- tables or columns and never changes existing records.
-- Requires MariaDB 10.1+ (standard on cPanel). On MySQL 5.7 remove "IF NOT EXISTS".
-- ============================================================================


-- 1) Admissions: soft delete (Delete / Delete Selected in AdminCP > Admissions)
ALTER TABLE `admissions`
  ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `deleted_by` INT NULL DEFAULT NULL;
CREATE INDEX IF NOT EXISTS `idx_admissions_deleted_at` ON `admissions` (`deleted_at`);


-- 2) Board results: PDF / DOC / DOCX document support (AdminCP > Matric Results)
ALTER TABLE `board_results`
  ADD COLUMN IF NOT EXISTS `file_path` VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `original_name` VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `file_type` VARCHAR(10) NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `file_size` INT NULL DEFAULT NULL;


-- 3) Site settings (only inserted if missing; existing values are kept)
--    Result software link given by the client (editable in AdminCP > Site Settings > Result Portal)
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`)
VALUES ('result_portal_url', 'https://apps.eduportal.pk/Production/', 'text')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

--    Small line under the school name in the header
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`)
VALUES ('header_tagline', 'Katlang, District Mardan', 'text')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;


-- ============================================================================
-- OPTIONAL - run only when you have the real values (otherwise leave commented).
-- Until then the site shows "Coming Soon" buttons for the mobile app.
-- It is safer to enter these in AdminCP > Site Settings > Mobile App Links,
-- because that form checks the Google Play link before saving.
-- ============================================================================
-- INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`)
-- VALUES ('play_store_url', 'PASTE_VERIFIED_GOOGLE_PLAY_URL_HERE', 'text')
-- ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- News bar announcement example (the "notifications" table; status: active / inactive).
-- Replace the text with your real announcement before running.
-- INSERT INTO `notifications` (`title`, `link`, `display_order`, `status`)
-- VALUES ('YOUR ANNOUNCEMENT TEXT HERE', 'admission.php', 1, 'active');
