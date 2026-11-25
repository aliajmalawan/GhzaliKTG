<?php
/**
 * Settings Helper Functions
 * Use these functions to fetch dynamic settings from database
 */

// Global settings cache
$_settings_cache = null;

/**
 * Load all settings from database into cache
 */
function loadSettings() {
    global $conn, $_settings_cache;

    if ($_settings_cache !== null) {
        return $_settings_cache;
    }

    $_settings_cache = [];
    $result = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $_settings_cache[$row['setting_key']] = $row['setting_value'];
        }
    }

    return $_settings_cache;
}

/**
 * Get a setting value by key
 * @param string $key Setting key
 * @param string $default Default value if setting not found
 * @return string Setting value
 */
function getSetting($key, $default = '') {
    global $_settings_cache;

    if ($_settings_cache === null) {
        loadSettings();
    }

    return isset($_settings_cache[$key]) ? $_settings_cache[$key] : $default;
}

/**
 * Get site name
 */
function getSiteName() {
    return getSetting('site_name', SITE_NAME);
}

/**
 * Get site email
 */
function getSiteEmail() {
    return getSetting('site_email', 'info@shaheenschool.com');
}

/**
 * Get site phone
 */
function getSitePhone() {
    return getSetting('site_phone', '0306-1345242');
}

/**
 * Get site address
 */
function getSiteAddress() {
    return getSetting('site_address', 'Basti Malook, Multan, Pakistan');
}

/**
 * Get office hours
 */
function getOfficeHours() {
    return getSetting('office_hours', 'Mon - Sat: 8:00 AM - 5:00 PM');
}

/**
 * Get social media URLs
 */
function getSocialMedia() {
    return [
        'facebook' => getSetting('facebook_url'),
        'instagram' => getSetting('instagram_url'),
        'youtube' => getSetting('youtube_url'),
        'twitter' => getSetting('twitter_url')
    ];
}

/**
 * Get hero section content
 */
function getHeroContent() {
    return [
        'title' => getSetting('hero_title', 'Lighting the Candle of Knowledge'),
        'description' => getSetting('hero_description', 'Empowering students with quality education and nurturing their potential to become future leaders.'),
        'button_text' => getSetting('hero_button_text', 'Apply Now'),
        'button_link' => getSetting('hero_button_link', 'admission.php'),
        'image' => getSetting('hero_image', 'images/fortschool.jpg')
    ];
}

/**
 * Get statistics
 */
function getStatistics() {
    return [
        'students' => getSetting('stats_students', '500'),
        'teachers' => getSetting('stats_teachers', '50'),
        'courses' => getSetting('stats_courses', '15'),
        'years' => getSetting('stats_years', '10')
    ];
}

/**
 * Get about page content
 */
function getAboutContent() {
    return [
        'description' => getSetting('about_description', SITE_NAME . ' is a leading educational institution committed to excellence in education...'),
        'mission' => getSetting('mission_statement', 'To provide quality education and empower students to achieve their full potential.'),
        'vision' => getSetting('vision_statement', 'To be a center of educational excellence that nurtures future leaders and innovators.')
    ];
}

/**
 * Get footer content
 */
function getFooterContent() {
    return [
        'about_text' => getSetting('footer_about_text', SITE_NAME . ' is dedicated to providing quality education and nurturing the potential of every student.'),
        'copyright' => getSetting('copyright_text', SITE_NAME . '. All rights reserved.')
    ];
}

/**
 * Get leadership messages
 */
function getLeadershipMessages() {
    global $conn;

    $query = "SELECT * FROM leadership WHERE status = 'active' ORDER BY display_order ASC";
    $result = mysqli_query($conn, $query);

    $leaders = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $leaders[] = $row;
        }
    }

    return $leaders;
}

/**
 * Get hero carousel slides
 */
function getHeroCarouselSlides() {
    global $conn;

    $query = "SELECT * FROM hero_carousel WHERE status = 'active' ORDER BY display_order ASC";
    $result = mysqli_query($conn, $query);

    $slides = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $slides[] = $row;
        }
    }

    return $slides;
}

/**
 * Result software / online result portal URL (admin: Site Settings > Result Portal).
 * Only secure https:// URLs are returned. Returns '' when intentionally cleared or invalid,
 * in which case the site falls back to the Examination page.
 */
define('RESULT_PORTAL_DEFAULT_URL', 'https://apps.eduportal.pk/Production/');

function isValidSecureUrl($url) {
    return $url !== '' && preg_match('#^https://#i', $url) && filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function getResultPortalUrl() {
    global $_settings_cache;
    if ($_settings_cache === null) loadSettings();
    $url = array_key_exists('result_portal_url', $_settings_cache) ? trim($_settings_cache['result_portal_url']) : RESULT_PORTAL_DEFAULT_URL;
    return isValidSecureUrl($url) ? $url : '';
}

/**
 * Mobile app store links (admin: Site Settings > Mobile App Links).
 * No default URLs: until an admin saves a valid one, the button is shown as "Coming Soon".
 */
function isValidPlayStoreUrl($url) {
    $p = parse_url($url);
    if (!$p || strtolower($p['scheme'] ?? '') !== 'https' || strtolower($p['host'] ?? '') !== 'play.google.com') return false;
    if (($p['path'] ?? '') !== '/store/apps/details') return false;
    parse_str($p['query'] ?? '', $q);
    return isset($q['id']) && preg_match('/^[A-Za-z][A-Za-z0-9_]*(\.[A-Za-z0-9_]+)+$/', $q['id']);
}

function isValidAppStoreUrl($url) {
    $p = parse_url($url);
    return $p && strtolower($p['scheme'] ?? '') === 'https' && in_array(strtolower($p['host'] ?? ''), ['apps.apple.com', 'itunes.apple.com'], true)
        && preg_match('#/app/#', $p['path'] ?? '');
}

function getPlayStoreUrl() {
    $u = trim(getSetting('play_store_url'));
    return ($u !== '' && isValidPlayStoreUrl($u)) ? $u : '';
}

function getAppStoreUrl() {
    $u = trim(getSetting('app_store_url'));
    return ($u !== '' && isValidAppStoreUrl($u)) ? $u : '';
}

/**
 * Check that a Play Store page really exists. Returns [true|false|null, message]; null = could not check.
 */
function checkUrlReachable($url) {
    if (!function_exists('curl_init')) return [null, 'Could not verify (cURL not available).'];
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 10, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_USERAGENT => 'Mozilla/5.0 (link check)', CURLOPT_NOBODY => false]);
    curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 0) return [null, 'Could not reach the store to verify the link (network issue).'];
    if ($code >= 200 && $code < 300) return [true, 'Link verified.'];
    if ($code === 404 || $code === 410) return [false, 'The store reports that this page does not exist (HTTP ' . $code . ').'];
    return [null, 'Store returned HTTP ' . $code . '; could not verify.'];
}

/**
 * Target for the "Result" menu item: [href, opensNewTab]
 */
function getResultLink() {
    $url = getResultPortalUrl();
    return $url !== '' ? [$url, true] : ['examination.php#results', false];
}
?>
