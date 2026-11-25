-- Soft delete support for admission applications (run once).
-- The admin page also adds these columns automatically if they are missing.
ALTER TABLE admissions
  ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL,
  ADD COLUMN deleted_by INT NULL DEFAULT NULL,
  ADD INDEX idx_admissions_deleted_at (deleted_at);
