-- Document (PDF/DOC/DOCX) support for Matric results (run once).
-- The admin and public pages also add these columns automatically if they are missing.
ALTER TABLE board_results
  ADD COLUMN file_path VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN original_name VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN file_type VARCHAR(10) NULL DEFAULT NULL,
  ADD COLUMN file_size INT NULL DEFAULT NULL;
