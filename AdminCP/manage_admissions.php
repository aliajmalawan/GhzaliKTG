<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/admissions_schema.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
ensure_admissions_soft_delete($conn);

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Server-side authorization: active admin with a permitted role (re-checked in the database)
function can_manage_admissions($conn) {
    $id = (int)($_SESSION['admin_id'] ?? 0);
    if ($id <= 0) return false;
    $stmt = mysqli_prepare($conn, "SELECT role FROM admin_users WHERE id = ? AND status = 'active'");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    return $row && in_array($row['role'], ['admin', 'super_admin'], true);
}

$allowed_status = ['pending', 'approved', 'rejected'];
$status_filter = (isset($_REQUEST['status']) && in_array($_REQUEST['status'], $allowed_status, true)) ? $_REQUEST['status'] : '';
$page = max(1, (int)($_REQUEST['page'] ?? 1));

function list_url($status, $page) {
    $q = [];
    if ($status !== '') $q['status'] = $status;
    if ($page > 1) $q['page'] = $page;
    return 'manage_admissions.php' . ($q ? '?' . http_build_query($q) : '');
}

// Handle POST actions (delete / approve / reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';
    if (!hash_equals($csrf, (string)$token)) {
        $_SESSION['flash'] = ['error', 'Security check failed (invalid or expired form). Please try again.'];
    } elseif (!can_manage_admissions($conn)) {
        $_SESSION['flash'] = ['error', 'You are not authorized to modify admissions.'];
    } elseif ($action === 'delete') {
        $raw = $_POST['ids'] ?? [];
        $ids = [];
        if (is_array($raw)) {
            foreach ($raw as $v) {
                if (is_string($v) && ctype_digit($v) && (int)$v > 0) $ids[(int)$v] = true;
            }
        }
        $ids = array_keys($ids);
        if (!$ids) {
            $_SESSION['flash'] = ['error', 'No valid records were selected.'];
        } elseif (count($ids) > 200) {
            $_SESSION['flash'] = ['error', 'Too many records selected at once (maximum 200).'];
        } else {
            $admin_id = (int)$_SESSION['admin_id'];
            $in = implode(',', array_fill(0, count($ids), '?'));
            $stmt = mysqli_prepare($conn, "UPDATE admissions SET deleted_at = NOW(), deleted_by = ? WHERE deleted_at IS NULL AND id IN ($in)");
            mysqli_stmt_bind_param($stmt, 'i' . str_repeat('i', count($ids)), $admin_id, ...$ids);
            if (mysqli_stmt_execute($stmt)) {
                $n = mysqli_stmt_affected_rows($stmt);
                $_SESSION['flash'] = $n > 0
                    ? ['success', $n . ' admission record' . ($n > 1 ? 's' : '') . ' deleted successfully.']
                    : ['error', 'No matching records found (already deleted?).'];
            } else {
                $_SESSION['flash'] = ['error', 'Could not delete the selected records. Please try again.'];
            }
        }
    } elseif (in_array($action, ['approve', 'reject'], true)) {
        $id = (int)($_POST['id'] ?? 0);
        $new = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = mysqli_prepare($conn, "UPDATE admissions SET status = ? WHERE id = ? AND deleted_at IS NULL");
        mysqli_stmt_bind_param($stmt, 'si', $new, $id);
        mysqli_stmt_execute($stmt);
    }
    header('Location: ' . list_url($status_filter, $page));
    exit;
}

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

// Count + paginate
$per_page = 25;
$where = "deleted_at IS NULL" . ($status_filter !== '' ? " AND status = '" . mysqli_real_escape_string($conn, $status_filter) . "'" : '');
$total = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM admissions WHERE $where"))['c'];
$pages = max(1, (int)ceil($total / $per_page));
$page = min($page, $pages);
$offset = ($page - 1) * $per_page;
$admissions = mysqli_query($conn, "SELECT * FROM admissions WHERE $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Admissions - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .adm-alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; color: #fff; }
        .adm-alert.success { background: #28a745; } .adm-alert.error { background: #dc3545; }
        .adm-toolbar { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between; margin-bottom: 15px; }
        .adm-toolbar select { padding: 8px 10px; border: 1px solid #ccc; border-radius: 5px; }
        .adm-btn-del { background: #dc3545 !important; border: 0; cursor: pointer; }
        .adm-btn-del:disabled { opacity: .5; cursor: not-allowed; }
        .adm-pager { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin-top: 20px; }
        .adm-pager a, .adm-pager span { padding: 6px 12px; border: 1px solid #ccc; border-radius: 4px; text-decoration: none; background: #fff; color: var(--primary-color); }
        .adm-pager .cur { background: var(--primary-color); color: #fff; }
        @media (max-width: 600px) { .adm-toolbar { flex-direction: column; align-items: stretch; } }
    </style>
</head>
<body style="background: var(--bg-light);">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 10px;">
            <h1 style="color: var(--primary-color);">Manage Admissions</h1>
            <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if ($flash): ?>
            <div class="adm-alert <?php echo $flash[0] === 'success' ? 'success' : 'error'; ?>" role="alert"><?php echo htmlspecialchars($flash[1]); ?></div>
        <?php endif; ?>

        <form method="post" id="bulkForm" action="<?php echo htmlspecialchars(list_url($status_filter, $page)); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
            <input type="hidden" name="page" value="<?php echo $page; ?>">

            <div class="adm-toolbar">
                <div>
                    <label for="statusFilter">Status: </label>
                    <select id="statusFilter" onchange="location.href='manage_admissions.php' + (this.value ? '?status=' + this.value : '')">
                        <option value="">All</option>
                        <?php foreach ($allowed_status as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo $status_filter === $s ? 'selected' : ''; ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span style="color: var(--text-light); margin-left: 10px;"><?php echo $total; ?> record<?php echo $total == 1 ? '' : 's'; ?></span>
                </div>
                <button type="button" id="deleteSelected" class="btn btn-primary adm-btn-del" style="padding: 8px 14px;" disabled>
                    <i class="fas fa-trash"></i> Delete Selected (<span id="selCount">0</span>)
                </button>
            </div>

            <div class="card" style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--primary-color); color: white;">
                            <th style="padding: 15px; width: 40px;"><input type="checkbox" id="selectAll" aria-label="Select all"></th>
                            <th style="padding: 15px; text-align: left;">ID</th>
                            <th style="padding: 15px; text-align: left;">Student Name</th>
                            <th style="padding: 15px; text-align: left;">Father Name</th>
                            <th style="padding: 15px; text-align: left;">Phone</th>
                            <th style="padding: 15px; text-align: left;">Course</th>
                            <th style="padding: 15px; text-align: left;">Date</th>
                            <th style="padding: 15px; text-align: left;">Status</th>
                            <th style="padding: 15px; text-align: left;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($admissions && mysqli_num_rows($admissions) > 0) {
                            while ($admission = mysqli_fetch_assoc($admissions)) {
                                $id = (int)$admission['id'];
                                $status_color = $admission['status'] == 'approved' ? '#28a745' : ($admission['status'] == 'rejected' ? '#dc3545' : '#ffc107');
                                $name = htmlspecialchars($admission['student_name'], ENT_QUOTES);
                                echo '<tr style="border-bottom: 1px solid #e0e0e0;">';
                                echo '<td style="padding: 15px;"><input type="checkbox" class="rowCheck" name="ids[]" value="' . $id . '" aria-label="Select #' . $id . '"></td>';
                                echo '<td style="padding: 15px;">#' . $id . '</td>';
                                echo '<td style="padding: 15px;">' . $name . '</td>';
                                echo '<td style="padding: 15px;">' . htmlspecialchars($admission['father_name']) . '</td>';
                                echo '<td style="padding: 15px;">' . htmlspecialchars($admission['phone']) . '</td>';
                                echo '<td style="padding: 15px;">' . htmlspecialchars($admission['course_id']) . '</td>';
                                echo '<td style="padding: 15px;">' . date('M d, Y', strtotime($admission['created_at'])) . '</td>';
                                echo '<td style="padding: 15px;"><span style="background: ' . $status_color . '; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px;">' . ucfirst($admission['status']) . '</span></td>';
                                echo '<td style="padding: 15px; white-space: nowrap;">';
                                if ($admission['status'] == 'pending') {
                                    echo '<button type="button" class="btn btn-primary rowAction" data-action="approve" data-id="' . $id . '" style="padding: 5px 10px; font-size: 12px; margin-right: 5px; border: 0; cursor: pointer;">Approve</button>';
                                    echo '<button type="button" class="btn btn-primary rowAction" data-action="reject" data-id="' . $id . '" style="padding: 5px 10px; font-size: 12px; background: #dc3545; margin-right: 5px; border: 0; cursor: pointer;">Reject</button>';
                                }
                                echo '<button type="button" class="btn btn-primary adm-btn-del rowDelete" data-id="' . $id . '" data-name="' . $name . '" style="padding: 5px 10px; font-size: 12px;" title="Delete"><i class="fas fa-trash"></i> Delete</button>';
                                echo '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="9" style="padding: 30px; text-align: center; color: var(--text-light);">No admission applications found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </form>

        <?php if ($pages > 1): ?>
            <div class="adm-pager">
                <?php for ($p = 1; $p <= $pages; $p++): ?>
                    <?php if ($p == $page): ?><span class="cur"><?php echo $p; ?></span>
                    <?php else: ?><a href="<?php echo htmlspecialchars(list_url($status_filter, $p)); ?>"><?php echo $p; ?></a><?php endif; ?>
                <?php endfor; ?>
            </div>
        <?php endif; ?>

        <!-- Single-record action form (approve / reject) -->
        <form method="post" id="rowForm" action="<?php echo htmlspecialchars(list_url($status_filter, $page)); ?>" style="display:none;">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="action" id="rowFormAction">
            <input type="hidden" name="id" id="rowFormId">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
            <input type="hidden" name="page" value="<?php echo $page; ?>">
        </form>
    </div>

    <script>
    (function () {
        var form = document.getElementById('bulkForm');
        var all = document.getElementById('selectAll');
        var btn = document.getElementById('deleteSelected');
        var count = document.getElementById('selCount');
        var checks = function () { return Array.prototype.slice.call(document.querySelectorAll('.rowCheck')); };

        function refresh() {
            var n = checks().filter(function (c) { return c.checked; }).length;
            count.textContent = n;
            btn.disabled = n === 0;
            all.checked = n > 0 && n === checks().length;
        }
        all.addEventListener('change', function () { checks().forEach(function (c) { c.checked = all.checked; }); refresh(); });
        checks().forEach(function (c) { c.addEventListener('change', refresh); });

        btn.addEventListener('click', function () {
            var n = checks().filter(function (c) { return c.checked; }).length;
            if (n && confirm('Delete ' + n + ' selected admission record(s)? They will be removed from the list.')) form.submit();
        });

        document.querySelectorAll('.rowDelete').forEach(function (b) {
            b.addEventListener('click', function () {
                if (!confirm('Delete the application of "' + b.dataset.name + '"?')) return;
                checks().forEach(function (c) { c.checked = (c.value === b.dataset.id); });
                form.submit();
            });
        });

        document.querySelectorAll('.rowAction').forEach(function (b) {
            b.addEventListener('click', function () {
                if (!confirm((b.dataset.action === 'approve' ? 'Approve' : 'Reject') + ' this admission?')) return;
                document.getElementById('rowFormAction').value = b.dataset.action;
                document.getElementById('rowFormId').value = b.dataset.id;
                document.getElementById('rowForm').submit();
            });
        });
        refresh();
    })();
    </script>
</body>
</html>
