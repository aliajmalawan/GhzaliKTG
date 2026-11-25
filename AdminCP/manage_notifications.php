<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/notification_bar.php'; // news_bar_safe_link()

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if (!hash_equals($csrf, (string)($_POST['csrf_token'] ?? ''))) {
        $error = 'Security check failed. Please reload the page and try again.';
    } elseif ($action === 'add' || $action === 'update') {
        $title = trim($_POST['title'] ?? '');
        $link_raw = trim($_POST['link'] ?? '');
        $link = news_bar_safe_link($link_raw);
        $order = (int)($_POST['display_order'] ?? 0);
        $status = (($_POST['status'] ?? 'active') === 'inactive') ? 'inactive' : 'active';

        if ($title === '' || mb_strlen($title) > 255) {
            $error = 'Title is required (maximum 255 characters).';
        } elseif ($link_raw !== '' && $link === '') {
            $error = 'Link is not valid. Use a page like "admission.php" or a full http(s):// address.';
        } elseif (mb_strlen($link) > 255) {
            $error = 'Link is too long (maximum 255 characters).';
        } elseif ($action === 'add') {
            $stmt = mysqli_prepare($conn, "INSERT INTO notifications (title, link, display_order, status) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssis', $title, $link, $order, $status);
            $message = mysqli_stmt_execute($stmt) ? 'Announcement added successfully!' : '';
            $error = $message ? '' : 'Could not add the announcement.';
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE notifications SET title = ?, link = ?, display_order = ?, status = ? WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'ssisi', $title, $link, $order, $status, $id);
            $message = mysqli_stmt_execute($stmt) ? 'Announcement updated successfully!' : '';
            $error = $message ? '' : 'Could not update the announcement.';
        }
    } elseif ($action === 'delete' && $id > 0) {
        $stmt = mysqli_prepare($conn, "DELETE FROM notifications WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $message = 'Announcement deleted.';
    } elseif ($action === 'toggle' && $id > 0) {
        $stmt = mysqli_prepare($conn, "UPDATE notifications SET status = IF(status='active','inactive','active') WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $message = 'Status updated.';
    }
    if ($error === '') {
        $_SESSION['flash_msg'] = $message;
        header('Location: manage_notifications.php');
        exit;
    }
}

if (!empty($_SESSION['flash_msg'])) {
    $message = $_SESSION['flash_msg'];
    unset($_SESSION['flash_msg']);
}

// Edit mode
$edit = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $stmt = mysqli_prepare($conn, "SELECT * FROM notifications WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $eid);
    mysqli_stmt_execute($stmt);
    $edit = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt)) ?: null;
}
// Keep submitted values when validation failed
$f_title = $_POST['title'] ?? ($edit['title'] ?? '');
$f_link = $_POST['link'] ?? ($edit['link'] ?? '');
$f_order = $_POST['display_order'] ?? ($edit['display_order'] ?? 0);
$f_status = $_POST['status'] ?? ($edit['status'] ?? 'active');
$editing_id = $edit ? (int)$edit['id'] : (int)($_POST['id'] ?? 0);
$is_edit = $edit !== null || (($_POST['action'] ?? '') === 'update');

$notifications = mysqli_query($conn, "SELECT * FROM notifications ORDER BY display_order ASC, created_at DESC");
$inp = 'width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage News Bar - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .nf-grid { display: grid; grid-template-columns: 3fr 2fr 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .nf-inline { display: inline; margin: 0; }
        .nf-btn { padding: 5px 10px; font-size: 12px; margin-right: 5px; border: 0; cursor: pointer; text-decoration: none; display: inline-block; }
        @media (max-width: 768px) { .nf-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body style="background: var(--bg-light);">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 10px;">
            <h1 style="color: var(--primary-color);">Manage News / Notification Bar</h1>
            <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div role="status" style="background: #28a745; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div role="alert" style="background: #dc3545; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 30px;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;"><?php echo $is_edit ? 'Edit Announcement' : 'Add New Announcement'; ?></h2>
            <form method="POST" action="manage_notifications.php">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'add'; ?>">
                <?php if ($is_edit): ?><input type="hidden" name="id" value="<?php echo $editing_id; ?>"><?php endif; ?>
                <div class="nf-grid">
                    <div>
                        <label for="nf_title" style="display: block; margin-bottom: 5px; font-weight: bold;">Title:</label>
                        <input id="nf_title" type="text" name="title" required maxlength="255" value="<?php echo htmlspecialchars($f_title); ?>" placeholder="e.g., Admissions Open for Session 2025" style="<?php echo $inp; ?>">
                    </div>
                    <div>
                        <label for="nf_link" style="display: block; margin-bottom: 5px; font-weight: bold;">Link (Optional):</label>
                        <input id="nf_link" type="text" name="link" maxlength="255" value="<?php echo htmlspecialchars($f_link); ?>" placeholder="admission.php or https://..." style="<?php echo $inp; ?>">
                    </div>
                    <div>
                        <label for="nf_order" style="display: block; margin-bottom: 5px; font-weight: bold;">Order:</label>
                        <input id="nf_order" type="number" name="display_order" value="<?php echo (int)$f_order; ?>" style="<?php echo $inp; ?>">
                    </div>
                    <div>
                        <label for="nf_status" style="display: block; margin-bottom: 5px; font-weight: bold;">Status:</label>
                        <select id="nf_status" name="status" style="<?php echo $inp; ?>">
                            <option value="active" <?php echo $f_status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $f_status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-<?php echo $is_edit ? 'save' : 'plus'; ?>"></i> <?php echo $is_edit ? 'Save Changes' : 'Add Announcement'; ?></button>
                <?php if ($is_edit): ?><a href="manage_notifications.php" class="btn btn-primary" style="background: #6c757d;">Cancel</a><?php endif; ?>
            </form>
        </div>

        <div class="card" style="overflow-x: auto;">
            <h2 style="color: var(--primary-color); margin-bottom: 20px;">All Announcements</h2>
            <p style="color: var(--text-light); margin-bottom: 15px;">Active announcements (up to 10, lowest order first) scroll in the bar under the homepage menu.</p>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: var(--primary-color); color: white;">
                        <th style="padding: 15px; text-align: left;">Title</th>
                        <th style="padding: 15px; text-align: left;">Link</th>
                        <th style="padding: 15px; text-align: left;">Order</th>
                        <th style="padding: 15px; text-align: left;">Status</th>
                        <th style="padding: 15px; text-align: left;">Created</th>
                        <th style="padding: 15px; text-align: left;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($notifications && mysqli_num_rows($notifications) > 0) {
                        while ($n = mysqli_fetch_assoc($notifications)) {
                            $nid = (int)$n['id'];
                            $on = $n['status'] == 'active';
                            echo '<tr style="border-bottom: 1px solid #e0e0e0;">';
                            echo '<td style="padding: 15px;">' . htmlspecialchars($n['title']) . '</td>';
                            echo '<td style="padding: 15px;">' . ($n['link'] ? htmlspecialchars($n['link']) : '-') . '</td>';
                            echo '<td style="padding: 15px;">' . (int)$n['display_order'] . '</td>';
                            echo '<td style="padding: 15px;"><span style="background: ' . ($on ? '#28a745' : '#dc3545') . '; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;">' . ucfirst($n['status']) . '</span></td>';
                            echo '<td style="padding: 15px;">' . date('M d, Y', strtotime($n['created_at'])) . '</td>';
                            echo '<td style="padding: 15px; white-space: nowrap;">';
                            echo '<a href="?edit=' . $nid . '" class="btn btn-primary nf-btn" title="Edit" aria-label="Edit announcement"><i class="fas fa-pen"></i></a>';
                            echo '<form method="POST" class="nf-inline"><input type="hidden" name="csrf_token" value="' . $csrf . '"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="' . $nid . '">';
                            echo '<button type="submit" class="btn btn-primary nf-btn" title="' . ($on ? 'Deactivate' : 'Activate') . '" aria-label="' . ($on ? 'Deactivate' : 'Activate') . ' announcement"><i class="fas fa-toggle-' . ($on ? 'on' : 'off') . '"></i></button></form>';
                            echo '<form method="POST" class="nf-inline" onsubmit="return confirm(\'Delete this announcement?\')"><input type="hidden" name="csrf_token" value="' . $csrf . '"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . $nid . '">';
                            echo '<button type="submit" class="btn btn-primary nf-btn" style="background: #dc3545;" title="Delete" aria-label="Delete announcement"><i class="fas fa-trash"></i></button></form>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6" style="padding: 30px; text-align: center; color: var(--text-light);">No announcements yet</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
