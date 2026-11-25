<?php
/**
 * Matric Results archive (2009-2026) - document support for the existing `board_results` table.
 * Documents (PDF / DOC / DOCX) are stored OUTSIDE direct web access (uploads/matric_results is
 * denied by .htaccess) and are served through download_result.php.
 */

define('MATRIC_YEAR_MIN', 2009);
define('MATRIC_YEAR_MAX', 2026);
define('MATRIC_MAX_BYTES', 10 * 1024 * 1024); // 10 MB
define('MATRIC_UPLOAD_DIR', __DIR__ . '/../uploads/matric_results/');

// Mirrors migrations/2026_11_25_matric_results_documents.sql
function ensure_matric_results_schema($conn) {
    $r = mysqli_query($conn, "SHOW COLUMNS FROM board_results LIKE 'file_path'");
    if ($r && mysqli_num_rows($r) === 0) {
        mysqli_query($conn, "ALTER TABLE board_results
            ADD COLUMN file_path VARCHAR(255) NULL DEFAULT NULL,
            ADD COLUMN original_name VARCHAR(255) NULL DEFAULT NULL,
            ADD COLUMN file_type VARCHAR(10) NULL DEFAULT NULL,
            ADD COLUMN file_size INT NULL DEFAULT NULL");
    }
}

function matric_ext_info() {
    return [
        'pdf'  => ['mimes' => ['application/pdf'], 'sig' => "%PDF-"],
        'doc'  => ['mimes' => ['application/msword', 'application/x-ole-storage', 'application/CDFV2', 'application/vnd.ms-office', 'application/CDFV2-corrupt'], 'sig' => "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"],
        'docx' => ['mimes' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/x-zip-compressed', 'application/octet-stream'], 'sig' => "PK\x03\x04"],
    ];
}

function matric_mime_for_ext($ext) {
    return [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ][$ext] ?? 'application/octet-stream';
}

/**
 * Validate an uploaded file array ($_FILES[x]). Returns [true, ext] or [false, error message].
 */
function matric_validate_upload($f) {
    if (!isset($f['error']) || is_array($f['error'])) return [false, 'Invalid upload.'];
    switch ($f['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_NO_FILE: return [false, 'Please choose a file to upload.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE: return [false, 'File is too large (maximum 10 MB).'];
        default: return [false, 'Upload failed. Please try again.'];
    }
    if (!is_uploaded_file($f['tmp_name'])) return [false, 'Invalid upload.'];
    if ($f['size'] <= 0) return [false, 'The file is empty.'];
    if ($f['size'] > MATRIC_MAX_BYTES) return [false, 'File is too large (maximum 10 MB).'];

    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $info = matric_ext_info();
    if (!isset($info[$ext])) return [false, 'Only PDF, DOC and DOCX files are allowed.'];

    // Real content checks (never trust the browser-supplied type)
    $fh = fopen($f['tmp_name'], 'rb');
    $head = $fh ? fread($fh, 8) : '';
    if ($fh) fclose($fh);
    if (strncmp($head, $info[$ext]['sig'], strlen($info[$ext]['sig'])) !== 0) {
        return [false, 'The file content does not match its extension.'];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($f['tmp_name']);
    if (!in_array($mime, $info[$ext]['mimes'], true)) {
        return [false, 'The file type (' . $mime . ') is not allowed for .' . $ext . '.'];
    }
    if ($ext === 'docx' && class_exists('ZipArchive')) {
        $z = new ZipArchive();
        if ($z->open($f['tmp_name']) !== true || $z->locateName('word/document.xml') === false) {
            return [false, 'This is not a valid Word (.docx) document.'];
        }
        $z->close();
    }
    return [true, $ext];
}

/**
 * Move a validated upload into storage with a unique, server-generated name.
 * Returns [true, ['file_path'=>..., 'original_name'=>..., 'file_type'=>..., 'file_size'=>...]] or [false, error].
 */
function matric_store_upload($f, $year) {
    list($ok, $ext) = matric_validate_upload($f);
    if (!$ok) return [false, $ext];
    if (!is_dir(MATRIC_UPLOAD_DIR) && !mkdir(MATRIC_UPLOAD_DIR, 0755, true)) return [false, 'Upload folder is not available.'];
    $name = 'matric_' . (int)$year . '_' . bin2hex(random_bytes(10)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], MATRIC_UPLOAD_DIR . $name)) return [false, 'Could not save the uploaded file.'];
    @chmod(MATRIC_UPLOAD_DIR . $name, 0644);
    $orig = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($f['name']));
    return [true, ['file_path' => 'uploads/matric_results/' . $name, 'original_name' => mb_substr($orig, 0, 200), 'file_type' => $ext, 'file_size' => (int)$f['size']]];
}

function matric_delete_stored_file($file_path) {
    $base = basename((string)$file_path); // never trust stored paths: only ever delete inside the upload dir
    if ($base !== '' && strpos($base, 'matric_') === 0) {
        $full = MATRIC_UPLOAD_DIR . $base;
        if (is_file($full)) @unlink($full);
    }
}

function matric_format_size($bytes) {
    if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
    return max(1, round($bytes / 1024)) . ' KB';
}

/** Active document records keyed by year. */
function matric_documents_by_year($conn) {
    $docs = [];
    $res = mysqli_query($conn, "SELECT id, title, year, file_type, file_size FROM board_results
        WHERE board_type = 'Matric' AND status = 'active' AND file_path IS NOT NULL AND file_path <> ''
        ORDER BY display_order ASC, id DESC");
    while ($res && ($row = mysqli_fetch_assoc($res))) {
        if (!isset($docs[(int)$row['year']])) $docs[(int)$row['year']] = $row;
    }
    return $docs;
}
