<?php
// Small shared helpers for admin pages (session must already be started).

function admin_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function admin_verify_csrf($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

// Logged in AND still an active admin with a permitted role (re-checked in the database).
function admin_can_manage($conn) {
    if (empty($_SESSION['admin_logged_in'])) return false;
    $id = (int)($_SESSION['admin_id'] ?? 0);
    if ($id <= 0) return false;
    $stmt = mysqli_prepare($conn, "SELECT role FROM admin_users WHERE id = ? AND status = 'active'");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $row && in_array($row['role'], ['admin', 'super_admin'], true);
}
