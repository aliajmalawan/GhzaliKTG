<?php
// Serves a Matric result document by id. Files are not directly web-accessible.
require_once 'includes/config.php';
require_once 'includes/matric_results.php';
ensure_matric_results_schema($conn);

$id = (isset($_GET['id']) && ctype_digit($_GET['id'])) ? (int)$_GET['id'] : 0;
$is_admin = !empty($_SESSION['admin_logged_in']);

$row = null;
if ($id > 0) {
    $stmt = mysqli_prepare($conn, "SELECT year, file_path, file_type, status FROM board_results WHERE id = ? AND board_type = 'Matric' AND file_path IS NOT NULL AND file_path <> ''");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}
$full = $row ? MATRIC_UPLOAD_DIR . basename($row['file_path']) : '';

if (!$row || ($row['status'] !== 'active' && !$is_admin) || !is_file($full) || !isset(matric_ext_info()[$row['file_type']])) {
    http_response_code(404);
    echo 'Result document not found.';
    exit;
}

$download_name = 'Matric-Result-' . (int)$row['year'] . '.' . $row['file_type'];
header('Content-Type: ' . matric_mime_for_ext($row['file_type']));
header('Content-Disposition: attachment; filename="' . $download_name . '"');
header('Content-Length: ' . filesize($full));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
readfile($full);
exit;
