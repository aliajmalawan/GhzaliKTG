-- ============================================================================
-- Ghazali School & College Katlang - LIVE DATABASE UPDATE (cPanel / phpMyAdmin)
-- Works on MySQL 5.7 / 8.x and MariaDB. Safe to run more than once.
-- It only ADDS missing columns / settings; it never drops or changes existing data.
--
-- How to run: phpMyAdmin > select the live database > SQL tab > paste all > Go.
-- (Take a backup first: Export > Quick > Go.)
-- ============================================================================


-- 1) admissions: deleted_at (soft delete)
SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `admissions` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL',
  'SELECT ''admissions.deleted_at already exists'' AS info')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admissions' AND COLUMN_NAME = 'deleted_at');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) admissions: deleted_by
SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `admissions` ADD COLUMN `deleted_by` INT NULL DEFAULT NULL',
  'SELECT ''admissions.deleted_by already exists'' AS info')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admissions' AND COLUMN_NAME = 'deleted_by');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3) admissions: index on deleted_at
SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `admissions` ADD INDEX `idx_admissions_deleted_at` (`deleted_at`)',
  'SELECT ''index already exists'' AS info')
  FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admissions' AND INDEX_NAME = 'idx_admissions_deleted_at');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- 4) board_results: file_path
SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `board_results` ADD COLUMN `file_path` VARCHAR(255) NULL DEFAULT NULL',
  'SELECT ''board_results.file_path already exists'' AS info')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'board_results' AND COLUMN_NAME = 'file_path');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5) board_results: original_name
SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `board_results` ADD COLUMN `original_name` VARCHAR(255) NULL DEFAULT NULL',
  'SELECT ''board_results.original_name already exists'' AS info')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'board_results' AND COLUMN_NAME = 'original_name');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6) board_results: file_type
SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `board_results` ADD COLUMN `file_type` VARCHAR(10) NULL DEFAULT NULL',
  'SELECT ''board_results.file_type already exists'' AS info')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'board_results' AND COLUMN_NAME = 'file_type');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7) board_results: file_size
SET @q = (SELECT IF(COUNT(*) = 0,
  'ALTER TABLE `board_results` ADD COLUMN `file_size` INT NULL DEFAULT NULL',
  'SELECT ''board_results.file_size already exists'' AS info')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'board_results' AND COLUMN_NAME = 'file_size');
PREPARE stmt FROM @q; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- 8) Settings (inserted only if missing; existing values are kept)
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`)
VALUES ('result_portal_url', 'https://apps.eduportal.pk/Production/', 'text')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`)
VALUES ('header_tagline', 'Katlang, District Mardan', 'text')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;


-- ============================================================================
-- OPTIONAL (leave commented until you have the real values)
-- ============================================================================
-- INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`)
-- VALUES ('play_store_url', 'PASTE_VERIFIED_GOOGLE_PLAY_URL_HERE', 'text')
-- ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
--
-- INSERT INTO `notifications` (`title`, `link`, `display_order`, `status`)
-- VALUES ('YOUR ANNOUNCEMENT TEXT HERE', 'admission.php', 1, 'active');
