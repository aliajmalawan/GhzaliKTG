<?php
session_start();
require_once '../includes/config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $success = true;

    $settings_error = '';
    if (isset($_POST['result_portal_url'])) {
        $_POST['result_portal_url'] = trim($_POST['result_portal_url']);
        if ($_POST['result_portal_url'] !== '' && !isValidSecureUrl($_POST['result_portal_url'])) {
            $settings_error = 'Error: Result Portal URL must be a valid secure address starting with https:// (it was not saved; other settings were saved).';
            unset($_POST['result_portal_url']);
        }
    }

    $store_notes = [];
    if (isset($_POST['play_store_url'])) {
        $_POST['play_store_url'] = trim($_POST['play_store_url']);
        $u = $_POST['play_store_url'];
        if ($u !== '' && !isValidPlayStoreUrl($u)) {
            $settings_error .= ' Error: Google Play URL must look like https://play.google.com/store/apps/details?id=your.app.id (not saved).';
            unset($_POST['play_store_url']);
        } elseif ($u !== '') {
            list($reach, $why) = checkUrlReachable($u);
            if ($reach === false) {
                $settings_error .= ' Error: Google Play link not saved - ' . $why;
                unset($_POST['play_store_url']);
            } elseif ($reach === null) {
                $store_notes[] = 'Google Play link saved, but ' . lcfirst($why);
            }
        }
    }
    if (isset($_POST['app_store_url'])) {
        $_POST['app_store_url'] = trim($_POST['app_store_url']);
        if ($_POST['app_store_url'] !== '' && !isValidAppStoreUrl($_POST['app_store_url'])) {
            $settings_error .= ' Error: App Store URL must look like https://apps.apple.com/.../app/... (not saved).';
            unset($_POST['app_store_url']);
        }
    }

    foreach ($_POST as $key => $value) {
        if ($key !== 'submit') {
            $key_escaped = mysqli_real_escape_string($conn, $key);
            $value_escaped = mysqli_real_escape_string($conn, $value);

            // Check if setting exists
            $check_query = "SELECT id FROM settings WHERE setting_key = '$key_escaped'";
            $check_result = mysqli_query($conn, $check_query);

            if (mysqli_num_rows($check_result) > 0) {
                // Update existing setting
                $update_query = "UPDATE settings SET setting_value = '$value_escaped' WHERE setting_key = '$key_escaped'";
                if (!mysqli_query($conn, $update_query)) {
                    $success = false;
                }
            } else {
                // Insert new setting
                $insert_query = "INSERT INTO settings (setting_key, setting_value) VALUES ('$key_escaped', '$value_escaped')";
                if (!mysqli_query($conn, $insert_query)) {
                    $success = false;
                }
            }
        }
    }

    if ($settings_error) {
        $message = $settings_error;
    } elseif ($success) {
        $message = "Settings saved successfully!" . ($store_notes ? ' Note: ' . implode(' ', $store_notes) : '');
    } else {
        $message = "Error saving some settings.";
    }
}

// Load settings using helper function
loadSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .settings-section h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--accent-color);
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 15px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body style="background: var(--bg-light);">
    <div class="container" style="padding: 30px 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="color: var(--primary-color);"><i class="fas fa-cog"></i> Site Settings</h1>
            <a href="dashboard.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>

        <?php if ($message): ?>
            <div style="background: <?php echo strpos($message, 'Error') !== false ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo strpos($message, 'Error') !== false ? '#721c24' : '#155724'; ?>; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <!-- General Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-info-circle"></i> General Information</h3>

                <div class="form-group">
                    <label>Site Name *</label>
                    <input type="text" name="site_name" required value="<?php echo htmlspecialchars(getSetting('site_name', 'Fort Education System')); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Site Email *</label>
                        <input type="email" name="site_email" required value="<?php echo htmlspecialchars(getSetting('site_email', 'info@fort.edu.pk')); ?>">
                    </div>

                    <div class="form-group">
                        <label>Site Phone *</label>
                        <input type="text" name="site_phone" required value="<?php echo htmlspecialchars(getSetting('site_phone', '0306-1345242')); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label>Site Address *</label>
                    <textarea name="site_address" required rows="3"><?php echo htmlspecialchars(getSetting('site_address', 'Basti Malook, Multan, Pakistan')); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Office Hours</label>
                    <input type="text" name="office_hours" value="<?php echo htmlspecialchars(getSetting('office_hours', 'Mon - Sat: 8:00 AM - 5:00 PM')); ?>" placeholder="e.g., Mon - Sat: 8:00 AM - 5:00 PM">
                </div>
            </div>

            <!-- Social Media Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-share-alt"></i> Social Media Links</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fab fa-facebook"></i> Facebook URL</label>
                        <input type="url" name="facebook_url" value="<?php echo htmlspecialchars(getSetting('facebook_url')); ?>" placeholder="https://facebook.com/yourpage">
                    </div>

                    <div class="form-group">
                        <label><i class="fab fa-instagram"></i> Instagram URL</label>
                        <input type="url" name="instagram_url" value="<?php echo htmlspecialchars(getSetting('instagram_url')); ?>" placeholder="https://instagram.com/yourpage">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fab fa-youtube"></i> YouTube URL</label>
                        <input type="url" name="youtube_url" value="<?php echo htmlspecialchars(getSetting('youtube_url')); ?>" placeholder="https://youtube.com/yourchannel">
                    </div>

                    <div class="form-group">
                        <label><i class="fab fa-twitter"></i> Twitter URL</label>
                        <input type="url" name="twitter_url" value="<?php echo htmlspecialchars(getSetting('twitter_url')); ?>" placeholder="https://twitter.com/yourpage">
                    </div>
                </div>
            </div>

            <!-- Home Page Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-home"></i> Home Page Content</h3>

                <div class="form-group">
                    <label>Hero Section Title</label>
                    <input type="text" name="hero_title" value="<?php echo htmlspecialchars(getSetting('hero_title', 'Lighting the Candle of Knowledge')); ?>" placeholder="Main heading on home page">
                </div>

                <div class="form-group">
                    <label>Hero Section Description</label>
                    <textarea name="hero_description" rows="3" placeholder="Description text below the main heading"><?php echo htmlspecialchars(getSetting('hero_description', 'Empowering students with quality education and nurturing their potential to become future leaders.')); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Hero Button Text</label>
                    <input type="text" name="hero_button_text" value="<?php echo htmlspecialchars(getSetting('hero_button_text', 'Apply Now')); ?>" placeholder="Button text">
                </div>

                <div class="form-group">
                    <label>Hero Button Link</label>
                    <input type="text" name="hero_button_link" value="<?php echo htmlspecialchars(getSetting('hero_button_link', 'admission.php')); ?>" placeholder="e.g., admission.php or courses.php">
                </div>
            </div>

            <!-- Statistics Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-chart-bar"></i> Statistics Section</h3>

                <div class="form-row">
                    <div class="form-group">
                        <label>Active Students Count</label>
                        <input type="number" name="stats_students" value="<?php echo htmlspecialchars(getSetting('stats_students', '500')); ?>" placeholder="e.g., 500">
                    </div>

                    <div class="form-group">
                        <label>Expert Teachers Count</label>
                        <input type="number" name="stats_teachers" value="<?php echo htmlspecialchars(getSetting('stats_teachers', '50')); ?>" placeholder="e.g., 50">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Courses Offered Count</label>
                        <input type="number" name="stats_courses" value="<?php echo htmlspecialchars(getSetting('stats_courses', '15')); ?>" placeholder="e.g., 15">
                    </div>

                    <div class="form-group">
                        <label>Years of Excellence</label>
                        <input type="number" name="stats_years" value="<?php echo htmlspecialchars(getSetting('stats_years', '10')); ?>" placeholder="e.g., 10">
                    </div>
                </div>
            </div>

            <!-- About Page Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-info"></i> About Page Content</h3>

                <div class="form-group">
                    <label>About Us Description</label>
                    <textarea name="about_description" rows="4" placeholder="Main description on About page"><?php echo htmlspecialchars(getSetting('about_description', 'Fort Education System is a leading educational institution committed to excellence in education...')); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Mission Statement</label>
                    <textarea name="mission_statement" rows="3" placeholder="Organization mission"><?php echo htmlspecialchars(getSetting('mission_statement', 'To provide quality education and empower students to achieve their full potential.')); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Vision Statement</label>
                    <textarea name="vision_statement" rows="3" placeholder="Organization vision"><?php echo htmlspecialchars(getSetting('vision_statement', 'To be a center of educational excellence that nurtures future leaders and innovators.')); ?></textarea>
                </div>
            </div>

            <!-- Footer Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-shoe-prints"></i> Footer Content</h3>

                <div class="form-group">
                    <label>Footer About Text</label>
                    <textarea name="footer_about_text" rows="3" placeholder="Short description in footer"><?php echo htmlspecialchars(getSetting('footer_about_text', 'Fort Education System is dedicated to providing quality education and nurturing the potential of every student.')); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Copyright Text</label>
                    <input type="text" name="copyright_text" value="<?php echo htmlspecialchars(getSetting('copyright_text', 'Fort Education System. All rights reserved.')); ?>" placeholder="Copyright text in footer">
                </div>
            </div>

            <!-- Contact Settings -->
            <div class="settings-section">
                <h3><i class="fas fa-map-marker-alt"></i> Contact Information</h3>

                <div class="form-group">
                    <label>Google Maps Embed Code (Optional)</label>
                    <textarea name="google_maps_embed" rows="3" placeholder="Paste Google Maps embed iframe code here"><?php echo htmlspecialchars(getSetting('google_maps_embed')); ?></textarea>
                    <small style="color: var(--text-light); display: block; margin-top: 5px;">
                        Get embed code from Google Maps → Share → Embed a map
                    </small>
                </div>

                <div class="form-group">
                    <label>WhatsApp Number (for quick contact)</label>
                    <input type="text" name="whatsapp_number" value="<?php echo htmlspecialchars(getSetting('whatsapp_number')); ?>" placeholder="e.g., +923061345242">
                    <small style="color: var(--text-light); display: block; margin-top: 5px;">
                        Include country code (e.g., +92 for Pakistan)
                    </small>
                </div>
            </div>

            <!-- Result Portal -->
            <div class="settings-section">
                <h3><i class="fas fa-poll"></i> Result Portal (Result Software)</h3>

                <div class="form-group">
                    <label for="result_portal_url">Result Software URL</label>
                    <input type="url" id="result_portal_url" name="result_portal_url" value="<?php echo htmlspecialchars(getResultPortalUrl()); ?>" placeholder="https://..." pattern="https://.*">
                    <small style="color: var(--text-light); display: block; margin-top: 5px;">
                        Opens in a new tab from the "Result" menu item and the Examination page. Must start with https://. Leave empty to hide the portal link (the menu item then opens the Examination page's results section).
                    </small>
                </div>
            </div>

            <!-- Mobile App Links -->
            <div class="settings-section">
                <h3><i class="fas fa-mobile-alt"></i> Mobile App Links</h3>

                <div class="form-group">
                    <label for="play_store_url">Google Play URL</label>
                    <input type="url" id="play_store_url" name="play_store_url" value="<?php echo htmlspecialchars(getPlayStoreUrl()); ?>" placeholder="https://play.google.com/store/apps/details?id=...">
                    <small style="color: var(--text-light); display: block; margin-top: 5px;">
                        Paste the official Play Store page address. It is checked with Google Play when you save, and rejected if the page does not exist. Leave empty to show a "Coming Soon" button.
                    </small>
                </div>

                <div class="form-group">
                    <label for="app_store_url">Apple App Store URL (optional)</label>
                    <input type="url" id="app_store_url" name="app_store_url" value="<?php echo htmlspecialchars(getAppStoreUrl()); ?>" placeholder="https://apps.apple.com/.../app/...">
                    <small style="color: var(--text-light); display: block; margin-top: 5px;">
                        Leave empty if there is no iOS app; the button then shows "Coming Soon".
                    </small>
                </div>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <button type="submit" name="submit" id="saveSettingsBtn" class="btn btn-primary" style="padding: 15px 40px; font-size: 16px;">
                    <i class="fas fa-save"></i> Save All Settings
                </button>
            </div>
        </form>
        <script>
        document.querySelector('form[method="POST"]').addEventListener('submit', function () {
            var b = document.getElementById('saveSettingsBtn');
            // Delay disabling so the 'submit' field is still included in the POST
            setTimeout(function () { b.disabled = true; b.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving and verifying links...'; }, 0);
        });
        </script>
    </div>
</body>
</html>
