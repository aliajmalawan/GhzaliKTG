<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Handle Add New Slide
if (isset($_POST['add_slide'])) {
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $subtitle = mysqli_real_escape_string($conn, $_POST['subtitle']);
    $text_color = mysqli_real_escape_string($conn, $_POST['text_color']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Handle image upload
    $image_path = '';
    if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] == 0) {
        $upload_dir = '../images/hero/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['slide_image']['type'], $allowed_types) && $_FILES['slide_image']['size'] <= 10 * 1024 * 1024) {
            $extension = pathinfo($_FILES['slide_image']['name'], PATHINFO_EXTENSION);
            $filename = 'slide_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['slide_image']['tmp_name'], $filepath)) {
                $image_path = 'images/hero/' . $filename;
            }
        } else {
            $error_message = "Invalid file type or size too large (max 10MB).";
        }
    } else {
        $error_message = "Please upload an image.";
    }

    if ($image_path && !$error_message) {
        $query = "INSERT INTO hero_carousel (image_path, title, subtitle, text_color, display_order, status)
                  VALUES ('$image_path', '$title', '$subtitle', '$text_color', $display_order, '$status')";

        if (mysqli_query($conn, $query)) {
            $success_message = "Carousel slide added successfully!";
        } else {
            $error_message = "Error adding slide: " . mysqli_error($conn);
        }
    }
}

// Handle Edit Slide
if (isset($_POST['edit_slide'])) {
    $id = intval($_POST['slide_id']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $subtitle = mysqli_real_escape_string($conn, $_POST['subtitle']);
    $text_color = mysqli_real_escape_string($conn, $_POST['text_color']);
    $display_order = intval($_POST['display_order']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Get current data
    $current_result = mysqli_query($conn, "SELECT image_path FROM hero_carousel WHERE id = $id");
    $current_data = mysqli_fetch_assoc($current_result);
    $image_path = $current_data['image_path'];

    // Handle image upload
    if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] == 0) {
        $upload_dir = '../images/hero/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($_FILES['slide_image']['type'], $allowed_types) && $_FILES['slide_image']['size'] <= 10 * 1024 * 1024) {
            $extension = pathinfo($_FILES['slide_image']['name'], PATHINFO_EXTENSION);
            $filename = 'slide_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($_FILES['slide_image']['tmp_name'], $filepath)) {
                // Delete old image
                if ($image_path && file_exists('../' . $image_path)) {
                    unlink('../' . $image_path);
                }
                $image_path = 'images/hero/' . $filename;
            }
        }
    }

    $query = "UPDATE hero_carousel SET
              image_path = '$image_path',
              title = '$title',
              subtitle = '$subtitle',
              text_color = '$text_color',
              display_order = $display_order,
              status = '$status'
              WHERE id = $id";

    if (mysqli_query($conn, $query)) {
        $success_message = "Carousel slide updated successfully!";
    } else {
        $error_message = "Error updating slide: " . mysqli_error($conn);
    }
}

// Handle Delete Slide
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);

    // Get file path before deleting
    $result = mysqli_query($conn, "SELECT image_path FROM hero_carousel WHERE id = $id");
    $data = mysqli_fetch_assoc($result);

    // Delete from database
    if (mysqli_query($conn, "DELETE FROM hero_carousel WHERE id = $id")) {
        // Delete file
        if ($data['image_path'] && file_exists('../' . $data['image_path'])) {
            unlink('../' . $data['image_path']);
        }
        $success_message = "Carousel slide deleted successfully!";
    } else {
        $error_message = "Error deleting slide: " . mysqli_error($conn);
    }
}

// Handle Quick Status Toggle
if (isset($_GET['toggle_status'])) {
    $id = intval($_GET['toggle_status']);
    $current_result = mysqli_query($conn, "SELECT status FROM hero_carousel WHERE id = $id");
    $current_data = mysqli_fetch_assoc($current_result);
    $new_status = ($current_data['status'] == 'active') ? 'inactive' : 'active';

    mysqli_query($conn, "UPDATE hero_carousel SET status = '$new_status' WHERE id = $id");
    $success_message = "Status updated successfully!";
}

// Get all carousel slides
$slides_query = "SELECT * FROM hero_carousel ORDER BY display_order ASC, id ASC";
$slides_result = mysqli_query($conn, $slides_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hero Carousel - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 260px;
            background: linear-gradient(135deg, #0B4DA2 0%, #0a3a7a 100%);
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 25px 20px;
            background: rgba(255,255,255,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            color: white;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .sidebar-header p {
            color: rgba(255,255,255,0.8);
            font-size: 13px;
        }

        .sidebar-menu {
            padding: 20px 0;
            padding-bottom: 30px;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            border-left-color: #F9C900;
        }

        .sidebar-menu a.active {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: #F9C900;
        }

        .sidebar-menu a i {
            margin-right: 12px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        .menu-divider {
            height: 1px;
            background: rgba(255,255,255,0.2);
            margin: 15px 20px;
        }

        .logout-section {
            padding: 0 0 20px 0;
        }

        .logout-section a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .logout-section a:hover {
            background: rgba(255,255,255,0.1);
            color: #ff6b6b;
            border-left-color: #ff6b6b;
        }

        .logout-section a i {
            margin-right: 12px;
            font-size: 18px;
            width: 20px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            padding: 30px;
            min-height: 100vh;
        }

        .admin-header {
            background: white;
            padding: 25px 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            color: #0B4DA2;
            font-size: 28px;
        }

        .admin-header h1 i {
            margin-right: 10px;
        }

        .btn-primary {
            padding: 12px 25px;
            background: #0B4DA2;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #083a7a;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        .carousel-table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .carousel-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .carousel-table th {
            background: #0B4DA2;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }

        .carousel-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .carousel-table tr:hover {
            background: #f8f9fa;
        }

        .slide-preview {
            width: 120px;
            height: 70px;
            border-radius: 5px;
            object-fit: cover;
            border: 2px solid #F9C900;
        }

        .action-btn {
            padding: 6px 12px;
            margin: 0 3px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: #0B4DA2;
            color: white;
        }

        .btn-edit:hover {
            background: #083a7a;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .btn-toggle {
            background: #28a745;
            color: white;
        }

        .btn-toggle:hover {
            background: #218838;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .form-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }

        .form-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn-submit {
            flex: 1;
            padding: 12px;
            background: #0B4DA2;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }

        .btn-submit:hover {
            background: #083a7a;
        }

        .btn-cancel {
            flex: 1;
            padding: 12px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .preview-img {
            max-width: 300px;
            margin-top: 10px;
            border-radius: 10px;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #0B4DA2;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .info-box strong {
            color: #0B4DA2;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><?php echo getSiteName(); ?></h2>
            <p>Admin Panel</p>
        </div>

        <div class="sidebar-menu">
            <a href="dashboard.php">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="analytics.php">
                <i class="fas fa-chart-line"></i>
                <span>Website Analytics</span>
            </a>
            <a href="manage_courses.php">
                <i class="fas fa-book"></i>
                <span>Courses</span>
            </a>
            <a href="manage_faculty.php">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Faculty</span>
            </a>
            <a href="manage_admissions.php">
                <i class="fas fa-user-graduate"></i>
                <span>Admissions</span>
            </a>
            <a href="manage_contacts.php">
                <i class="fas fa-envelope"></i>
                <span>Contact Messages</span>
            </a>
            <a href="manage_events.php">
                <i class="fas fa-calendar-alt"></i>
                <span>Events</span>
            </a>
            <a href="manage_news.php">
                <i class="fas fa-newspaper"></i>
                <span>News</span>
            </a>
            <a href="manage_gallery.php">
                <i class="fas fa-images"></i>
                <span>Gallery</span>
            </a>
            <a href="manage_downloads.php">
                <i class="fas fa-download"></i>
                <span>Downloads</span>
            </a>
            <a href="manage_alumni.php">
                <i class="fas fa-user-graduate"></i>
                <span>Alumni</span>
            </a>
            <a href="manage_alumni_reviews.php">
                <i class="fas fa-star"></i>
                <span>Alumni Reviews</span>
            </a>
            <a href="manage_datesheets.php">
                <i class="fas fa-calendar-check"></i>
                <span>Exam Datesheets</span>
            </a>
            <a href="manage_board_results.php">
                <i class="fas fa-trophy"></i>
                <span>Board Results</span>
            </a>
            <a href="manage_notifications.php">
                <i class="fas fa-bullhorn"></i>
                <span>Notifications</span>
            </a>
            <a href="manage_leadership.php">
                <i class="fas fa-users"></i>
                <span>Leadership Messages</span>
            </a>
            <a href="manage_hero_carousel.php" class="active">
                <i class="fas fa-images"></i>
                <span>Hero Carousel</span>
            </a>
            <a href="settings.php">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="theme_manager.php">
                <i class="fas fa-palette"></i>
                <span>Theme Manager</span>
            </a>
        </div>

        <div class="menu-divider"></div>

        <div class="logout-section">
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="admin-header">
            <h1><i class="fas fa-images"></i> Manage Hero Carousel</h1>
            <button class="btn-primary" onclick="openAddModal()">
                <i class="fas fa-plus"></i> Add New Slide
            </button>
        </div>

        <div class="info-box">
            <strong><i class="fas fa-info-circle"></i> Carousel Info:</strong> Hero section pe rotating images show hongi. Recommended size: <strong>1920x600 pixels</strong> (widescreen). Maximum 10MB per image.
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="carousel-table">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Image Preview</th>
                        <th>Title</th>
                        <th>Subtitle</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($slides_result && mysqli_num_rows($slides_result) > 0): ?>
                        <?php while ($slide = mysqli_fetch_assoc($slides_result)): ?>
                            <tr>
                                <td><strong><?php echo $slide['display_order']; ?></strong></td>
                                <td>
                                    <img src="../<?php echo htmlspecialchars($slide['image_path']); ?>" alt="Slide" class="slide-preview">
                                </td>
                                <td><strong><?php echo htmlspecialchars($slide['title']); ?></strong></td>
                                <td><?php echo htmlspecialchars(substr($slide['subtitle'], 0, 50)); ?>...</td>
                                <td>
                                    <span class="status-badge status-<?php echo $slide['status']; ?>">
                                        <?php echo ucfirst($slide['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="action-btn btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($slide), ENT_QUOTES, 'UTF-8'); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="?toggle_status=<?php echo $slide['id']; ?>" class="action-btn btn-toggle" onclick="return confirm('Toggle status for this slide?')">
                                        <i class="fas fa-toggle-on"></i> Toggle
                                    </a>
                                    <a href="?delete_id=<?php echo $slide['id']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this slide?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                No carousel slides found. Click "Add New Slide" to create one.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Slide Modal -->
    <div id="addModal" class="form-modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 25px; color: #0B4DA2;">
                <i class="fas fa-plus-circle"></i> Add New Carousel Slide
            </h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Slide Image * (Widescreen)</label>
                    <input type="file" name="slide_image" accept="image/*" required onchange="previewImage(this, 'add_image_preview')">
                    <small style="color: #666;">Recommended: 1920x600 pixels. Max size: 10MB</small>
                    <img id="add_image_preview" class="preview-img" style="display: none;">
                </div>

                <div class="form-group">
                    <label>Title (Optional)</label>
                    <input type="text" name="title" placeholder="e.g., Welcome to Shaheen Public High School">
                </div>

                <div class="form-group">
                    <label>Subtitle (Optional)</label>
                    <textarea name="subtitle" placeholder="e.g., Empowering students with quality education"></textarea>
                </div>

                <div class="form-group">
                    <label>Text Color</label>
                    <input type="color" name="text_color" value="#ffffff" style="width: 100px; height: 40px; cursor: pointer;">
                    <small style="color: #666;">Choose text color for title and subtitle</small>
                </div>

                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" value="0" min="0">
                    <small style="color: #666;">Lower numbers appear first</small>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" name="add_slide" class="btn-submit">
                        <i class="fas fa-save"></i> Add Slide
                    </button>
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Slide Modal -->
    <div id="editModal" class="form-modal">
        <div class="modal-content">
            <h2 style="margin-bottom: 25px; color: #0B4DA2;">
                <i class="fas fa-edit"></i> Edit Carousel Slide
            </h2>
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="slide_id" id="edit_slide_id">

                <div class="form-group">
                    <label>Slide Image (Leave empty to keep current)</label>
                    <input type="file" name="slide_image" accept="image/*" onchange="previewImage(this, 'edit_image_preview')">
                    <small style="color: #666;">Recommended: 1920x600 pixels. Max size: 10MB</small>
                    <img id="edit_image_preview" class="preview-img">
                </div>

                <div class="form-group">
                    <label>Title (Optional)</label>
                    <input type="text" name="title" id="edit_title">
                </div>

                <div class="form-group">
                    <label>Subtitle (Optional)</label>
                    <textarea name="subtitle" id="edit_subtitle"></textarea>
                </div>

                <div class="form-group">
                    <label>Text Color</label>
                    <input type="color" name="text_color" id="edit_text_color" value="#ffffff" style="width: 100px; height: 40px; cursor: pointer;">
                    <small style="color: #666;">Choose text color for title and subtitle</small>
                </div>

                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" id="edit_display_order" min="0">
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="edit_status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" name="edit_slide" class="btn-submit">
                        <i class="fas fa-save"></i> Update Slide
                    </button>
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('active');
        }

        function openEditModal(slide) {
            try {
                console.log('Opening edit modal for slide:', slide);

                document.getElementById('edit_slide_id').value = slide.id;
                document.getElementById('edit_title').value = slide.title || '';
                document.getElementById('edit_subtitle').value = slide.subtitle || '';
                document.getElementById('edit_text_color').value = slide.text_color || '#ffffff';
                document.getElementById('edit_display_order').value = slide.display_order;
                document.getElementById('edit_status').value = slide.status;

                // Show current image
                const imagePreview = document.getElementById('edit_image_preview');
                if (slide.image_path) {
                    imagePreview.src = '../' + slide.image_path;
                    imagePreview.style.display = 'block';
                } else {
                    imagePreview.style.display = 'none';
                }

                document.getElementById('editModal').classList.add('active');
            } catch (error) {
                console.error('Error opening edit modal:', error);
                alert('Error opening edit form. Please check browser console for details.');
            }
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('form-modal')) {
                event.target.classList.remove('active');
            }
        };
    </script>
</body>
</html>
