<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ghazali_ktg');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// // Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Site Configuration
define('SITE_NAME', 'Ghazali School & College Katlang ');
define('SITE_URL', 'https://ghazaliktg.com/');
define('ADMIN_EMAIL', 'info@ghazaliktg.com');
define('SITE_LOCATION', 'Canal Road Near Dr.Nisar Clinic Katlang Distt. Mardan, Katlang, Khyber Pakhtunkhwa, Pakistan');
define('SITE_CONTACT', '0333-8159104');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include settings helper functions
require_once __DIR__ . '/settings_helper.php';

// Include theme helper functions
require_once __DIR__ . '/theme_helper.php';
?>
