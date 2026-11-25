<?php
// Ensures the soft-delete columns exist (mirrors migrations/2026_11_25_admissions_soft_delete.sql).
function ensure_admissions_soft_delete($conn) {
    $r = mysqli_query($conn, "SHOW COLUMNS FROM admissions LIKE 'deleted_at'");
    if ($r && mysqli_num_rows($r) === 0) {
        mysqli_query($conn, "ALTER TABLE admissions
            ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL,
            ADD COLUMN deleted_by INT NULL DEFAULT NULL,
            ADD INDEX idx_admissions_deleted_at (deleted_at)");
    }
}
