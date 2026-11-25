<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/admin_auth.php';
require_once '../includes/matric_results.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
ensure_matric_results_schema($conn);
$csrf = admin_csrf_token();

$flash = $_SESSION['flash_matric'] ?? null;
unset($_SESSION['flash_matric']);

function matric_year_valid($y) {
    return $y >= MATRIC_YEAR_MIN && $y <= MATRIC_YEAR_MAX;
}
function matric_doc_for_year($conn, $year) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM board_results WHERE board_type = 'Matric' AND year = ? AND file_path IS NOT NULL AND file_path <> '' LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $year);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $msg = ['error', 'Unknown action.'];

    if (!admin_verify_csrf($_POST['csrf_token'] ?? '')) {
        $msg = ['error', 'Security check failed. Please reload the page and try again.'];
    } elseif (!admin_can_manage($conn)) {
        $msg = ['error', 'You are not authorized to manage results.'];
    } elseif ($action === 'add') {
        $year = (int)($_POST['year'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        if (!matric_year_valid($year)) {
            $msg = ['error', 'Please select a year between ' . MATRIC_YEAR_MIN . ' and ' . MATRIC_YEAR_MAX . '.'];
        } elseif (mb_strlen($title) > 150) {
            $msg = ['error', 'Title is too long (maximum 150 characters).'];
        } elseif (matric_doc_for_year($conn, $year)) {
            $msg = ['error', "A result document for $year already exists. Use Update below to change or replace it."];
        } else {
            if ($title === '') $title = "Matriculation Result $year";
            list($ok, $res) = matric_store_upload($_FILES['document'] ?? [], $year);
            if (!$ok) {
                $msg = ['error', $res];
            } else {
                $btype = 'Matric';
                $empty = '';
                $stmt = mysqli_prepare($conn, "INSERT INTO board_results (title, board_type, year, image_path, file_path, original_name, file_type, file_size, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                mysqli_stmt_bind_param($stmt, 'ssissssi', $title, $btype, $year, $empty, $res['file_path'], $res['original_name'], $res['file_type'], $res['file_size']);
                if (mysqli_stmt_execute($stmt)) {
                    $msg = ['success', "Result for $year uploaded successfully."];
                } else {
                    matric_delete_stored_file($res['file_path']);
                    $msg = ['error', 'Database error while saving the result.'];
                }
            }
        }
    } elseif (in_array($action, ['update', 'delete', 'toggle'], true)) {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = mysqli_prepare($conn, "SELECT * FROM board_results WHERE id = ? AND board_type = 'Matric' AND file_path IS NOT NULL AND file_path <> ''");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if (!$row) {
            $msg = ['error', 'Result document not found.'];
        } elseif ($action === 'delete') {
            $stmt = mysqli_prepare($conn, "DELETE FROM board_results WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            if (mysqli_stmt_execute($stmt)) {
                matric_delete_stored_file($row['file_path']);
                $msg = ['success', 'Result for ' . (int)$row['year'] . ' deleted.'];
            } else {
                $msg = ['error', 'Could not delete the result.'];
            }
        } elseif ($action === 'toggle') {
            $stmt = mysqli_prepare($conn, "UPDATE board_results SET status = IF(status='active','inactive','active') WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $msg = ['success', 'Visibility updated.'];
        } else { // update title and/or replace file
            $title = trim($_POST['title'] ?? '');
            if ($title === '' || mb_strlen($title) > 150) {
                $msg = ['error', 'Title is required (maximum 150 characters).'];
            } else {
                $new = null;
                $has_file = isset($_FILES['document']) && ($_FILES['document']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
                $err = null;
                if ($has_file) {
                    list($ok, $res) = matric_store_upload($_FILES['document'], (int)$row['year']);
                    if ($ok) $new = $res; else $err = $res;
                }
                if ($err !== null) {
                    $msg = ['error', $err];
                } else {
                    if ($new) {
                        $stmt = mysqli_prepare($conn, "UPDATE board_results SET title = ?, file_path = ?, original_name = ?, file_type = ?, file_size = ? WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, 'ssssii', $title, $new['file_path'], $new['original_name'], $new['file_type'], $new['file_size'], $id);
                    } else {
                        $stmt = mysqli_prepare($conn, "UPDATE board_results SET title = ? WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, 'si', $title, $id);
                    }
                    if (mysqli_stmt_execute($stmt)) {
                        if ($new) matric_delete_stored_file($row['file_path']);
                        $msg = ['success', 'Result for ' . (int)$row['year'] . ($new ? ' updated and file replaced.' : ' updated.')];
                    } else {
                        if ($new) matric_delete_stored_file($new['file_path']);
                        $msg = ['error', 'Database error while updating.'];
                    }
                }
            }
        }
    }
    $_SESSION['flash_matric'] = $msg;
    header('Location: manage_matric_results.php');
    exit;
}

// Load existing documents
$docs = [];
$res = mysqli_query($conn, "SELECT * FROM board_results WHERE board_type = 'Matric' AND file_path IS NOT NULL AND file_path <> '' ORDER BY year DESC, id DESC");
while ($res && ($r = mysqli_fetch_assoc($res))) $docs[] = $r;
$have_years = array_map(function ($d) { return (int)$d['year']; }, $docs);
$missing_years = [];
for ($y = MATRIC_YEAR_MAX; $y >= MATRIC_YEAR_MIN; $y--) if (!in_array($y, $have_years, true)) $missing_years[] = $y;
$inp = 'width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matric Results - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .mr-grid { display: grid; grid-template-columns: 1fr 2fr 2fr; gap: 15px; margin-bottom: 15px; }
        .mr-doc { border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #fff; }
        .mr-doc-grid { display: grid; grid-template-columns: 90px 2fr 2fr auto; gap: 12px; align-items: end; }
        .mr-year { font-size: 24px; font-weight: 700; color: var(--primary-color); }
        .mr-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; align-items: center; }
        .mr-actions form { margin: 0; }
        .mr-btn { padding: 6px 12px; font-size: 13px; border: 0; cursor: pointer; text-decoration: none; display: inline-block; }
        .mr-badge { color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 11px; }
        @media (max-width: 800px) { .mr-grid, .mr-doc-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body style="background: var(--bg-light);">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 10px;">
            <h1 style="color: var(--primary-color);">Matriculation Results <?php echo MATRIC_YEAR_MIN; ?>–<?php echo MATRIC_YEAR_MAX; ?></h1>
            <div>
                <a href="manage_board_results.php" class="btn btn-primary" style="background: #6c757d;">Board Result Images</a>
                <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>

        <?php if ($flash): ?>
            <div role="<?php echo $flash[0] === 'success' ? 'status' : 'alert'; ?>" style="background: <?php echo $flash[0] === 'success' ? '#28a745' : '#dc3545'; ?>; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;"><?php echo htmlspecialchars($flash[1]); ?></div>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">Upload a Result Document</h2>
            <?php if ($missing_years): ?>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="action" value="add">
                <div class="mr-grid">
                    <div>
                        <label for="mr_year" style="display: block; margin-bottom: 5px; font-weight: bold;">Year:</label>
                        <select id="mr_year" name="year" required style="<?php echo $inp; ?>">
                            <?php foreach ($missing_years as $y): ?><option value="<?php echo $y; ?>"><?php echo $y; ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="mr_title" style="display: block; margin-bottom: 5px; font-weight: bold;">Result Title:</label>
                        <input id="mr_title" type="text" name="title" maxlength="150" placeholder="e.g., Matric Annual Result 2024 (blank = default)" style="<?php echo $inp; ?>">
                    </div>
                    <div>
                        <label for="mr_file" style="display: block; margin-bottom: 5px; font-weight: bold;">File (PDF, DOC, DOCX – max 10 MB):</label>
                        <input id="mr_file" type="file" name="document" required accept=".pdf,.doc,.docx" style="<?php echo $inp; ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Save</button>
            </form>
            <?php else: ?>
                <p style="color: var(--text-light);">All years have a document. Use Update or Delete below to change one.</p>
            <?php endif; ?>
            <p style="color: var(--text-light); margin-top: 15px; font-size: 14px;">
                Years without a document: <?php echo $missing_years ? htmlspecialchars(implode(', ', $missing_years)) : 'none'; ?>.
                The public page shows "Result not available" for those years.
            </p>
        </div>

        <h2 style="color: var(--primary-color); margin-bottom: 15px;">Uploaded Documents (<?php echo count($docs); ?>)</h2>
        <?php if (!$docs): ?>
            <div class="card"><p style="color: var(--text-light);">No result documents uploaded yet.</p></div>
        <?php endif; ?>
        <?php foreach ($docs as $d): $on = $d['status'] === 'active'; ?>
            <div class="mr-doc">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
                    <div class="mr-doc-grid">
                        <div class="mr-year"><?php echo (int)$d['year']; ?></div>
                        <div>
                            <label for="t<?php echo (int)$d['id']; ?>" style="display: block; margin-bottom: 5px; font-weight: bold;">Title:</label>
                            <input id="t<?php echo (int)$d['id']; ?>" type="text" name="title" required maxlength="150" value="<?php echo htmlspecialchars($d['title']); ?>" style="<?php echo $inp; ?>">
                        </div>
                        <div>
                            <label for="f<?php echo (int)$d['id']; ?>" style="display: block; margin-bottom: 5px; font-weight: bold;">Replace file (optional):</label>
                            <input id="f<?php echo (int)$d['id']; ?>" type="file" name="document" accept=".pdf,.doc,.docx" style="<?php echo $inp; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary mr-btn"><i class="fas fa-save"></i> Update</button>
                    </div>
                </form>
                <div class="mr-actions">
                    <span class="mr-badge" style="background: <?php echo $on ? '#28a745' : '#dc3545'; ?>;"><?php echo $on ? 'Visible' : 'Hidden'; ?></span>
                    <span style="color: var(--text-light); font-size: 13px;"><?php echo strtoupper(htmlspecialchars($d['file_type'])); ?> · <?php echo matric_format_size((int)$d['file_size']); ?> · <?php echo htmlspecialchars($d['original_name'] ?? ''); ?></span>
                    <a href="../download_result.php?id=<?php echo (int)$d['id']; ?>" class="btn btn-primary mr-btn"><i class="fas fa-download"></i> Download</a>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
                        <button type="submit" class="btn btn-primary mr-btn" style="background: #6c757d;"><i class="fas fa-eye<?php echo $on ? '-slash' : ''; ?>"></i> <?php echo $on ? 'Hide' : 'Show'; ?></button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Delete the <?php echo (int)$d['year']; ?> result document? The file will be permanently removed.')">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$d['id']; ?>">
                        <button type="submit" class="btn btn-primary mr-btn" style="background: #dc3545;"><i class="fas fa-trash"></i> Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
