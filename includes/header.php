<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo SITE_NAME; ?> - Quality Education for a Bright Future">
    <meta name="keywords" content="education, school, learning, kohsar education">
    <meta name="author" content="<?php echo SITE_NAME; ?>">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo getSiteName(); ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="css/style.css">

    <!-- Dynamic Theme Colors -->
    <style>
        <?php echo getThemeCSS(); ?>
    </style>

    <!-- Font Awesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo getLogoPath(); ?>">
</head>
<body>
    <!-- Header: two tiers (white brand/contact bar, blue centred menu below), sticky on every page. -->
    <?php
    $hx_page   = basename($_SERVER['SCRIPT_NAME']);
    $hx_phone  = trim(explode(',', getSitePhone())[0]);
    $hx_email  = getSiteEmail();
    $hx_social = array_filter(getSocialMedia());
    list($hx_result_href, $hx_result_new_tab) = getResultLink();
    ?>
    <header class="hx-header" id="site-header">
        <div class="hx-topbar">
            <div class="container hx-topbar-inner">
                <a class="hx-brand" href="index.php" aria-label="<?php echo htmlspecialchars(getSiteName()); ?> - Home">
                    <img src="<?php echo getLogoPath(); ?>" alt="" class="hx-brand-logo" width="56" height="56" onerror="this.style.display='none'">
                    <span class="hx-brand-text">
                        <strong><?php echo htmlspecialchars(getSiteName()); ?></strong>
                        <small><?php echo htmlspecialchars(getSetting('header_tagline', 'Katlang, District Mardan')); ?></small>
                    </span>
                </a>

                <div class="hx-info-group">
                    <?php if ($hx_phone !== ''): ?>
                    <a class="hx-info" href="tel:<?php echo htmlspecialchars(preg_replace('/[^+0-9]/', '', $hx_phone)); ?>">
                        <span class="hx-info-icon"><i class="fas fa-phone" aria-hidden="true"></i></span>
                        <span><small>Call Us</small><strong><?php echo htmlspecialchars($hx_phone); ?></strong></span>
                    </a>
                    <?php endif; ?>
                    <?php if ($hx_email !== ''): ?>
                    <a class="hx-info" href="mailto:<?php echo htmlspecialchars($hx_email); ?>">
                        <span class="hx-info-icon"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                        <span><small>Email Us</small><strong><?php echo htmlspecialchars($hx_email); ?></strong></span>
                    </a>
                    <?php endif; ?>
                </div>

                <div class="hx-actions">
                    <?php if ($hx_social): ?>
                    <nav class="hx-social" aria-label="Social media">
                        <?php foreach (['facebook' => 'fa-facebook-f', 'instagram' => 'fa-instagram', 'youtube' => 'fa-youtube', 'twitter' => 'fa-twitter'] as $net => $ico):
                            if (empty($hx_social[$net])) continue; ?>
                        <a href="<?php echo htmlspecialchars($hx_social[$net]); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo ucfirst($net); ?>"><i class="fab <?php echo $ico; ?>" aria-hidden="true"></i></a>
                        <?php endforeach; ?>
                    </nav>
                    <?php endif; ?>

                    <a class="hx-apply-btn" href="admission.php"><i class="fas fa-pen-to-square" aria-hidden="true"></i> Apply Online</a>

                    <button type="button" class="hx-toggle" id="hx-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="hx-nav">
                        <span class="hx-toggle-bar"></span>
                        <span class="hx-toggle-bar"></span>
                        <span class="hx-toggle-bar"></span>
                    </button>
                </div>
            </div>
        </div>

        <div class="hx-backdrop" id="hx-backdrop"></div>

        <nav class="hx-navbar" aria-label="Primary navigation">
            <div class="container hx-navbar-inner">
                <div class="hx-nav" id="hx-nav">
                    <a href="index.php"<?php echo $hx_page === 'index.php' ? ' class="is-active" aria-current="page"' : ''; ?>>Home</a>
                    <a href="about.php"<?php echo $hx_page === 'about.php' ? ' class="is-active" aria-current="page"' : ''; ?>>About Us</a>
                    <a href="courses.php"<?php echo $hx_page === 'courses.php' ? ' class="is-active" aria-current="page"' : ''; ?>>Courses</a>
                    <a href="admission.php"<?php echo $hx_page === 'admission.php' ? ' class="is-active" aria-current="page"' : ''; ?>>Admission</a>
                    <a href="faculty.php"<?php echo $hx_page === 'faculty.php' ? ' class="is-active" aria-current="page"' : ''; ?>>Faculty</a>
                    <a href="<?php echo htmlspecialchars($hx_result_href); ?>"<?php echo $hx_result_new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>Result<?php if ($hx_result_new_tab): ?> <i class="fas fa-external-link-alt hx-ext" aria-hidden="true"></i><span class="hx-sr">(opens in a new tab)</span><?php endif; ?></a>

                    <?php $hx_res_on = in_array($hx_page, ['downloads.php', 'alumni.php', 'examination.php', 'events.php'], true); ?>
                    <div class="hx-dropdown<?php echo $hx_res_on ? ' is-active' : ''; ?>">
                        <span class="hx-dropdown-trigger" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false">Resources <i class="fas fa-chevron-down hx-caret" aria-hidden="true"></i></span>
                        <div class="hx-dropdown-panel">
                            <a href="downloads.php"<?php echo $hx_page === 'downloads.php' ? ' class="is-active" aria-current="page"' : ''; ?>><i class="fas fa-download" aria-hidden="true"></i> Downloads</a>
                            <a href="alumni.php"<?php echo $hx_page === 'alumni.php' ? ' class="is-active" aria-current="page"' : ''; ?>><i class="fas fa-user-graduate" aria-hidden="true"></i> Alumni</a>
                            <a href="examination.php"<?php echo $hx_page === 'examination.php' ? ' class="is-active" aria-current="page"' : ''; ?>><i class="fas fa-file-alt" aria-hidden="true"></i> Examination</a>
                            <a href="events.php"<?php echo $hx_page === 'events.php' ? ' class="is-active" aria-current="page"' : ''; ?>><i class="fas fa-calendar-alt" aria-hidden="true"></i> Events</a>
                        </div>
                    </div>

                    <a href="gallery.php"<?php echo $hx_page === 'gallery.php' ? ' class="is-active" aria-current="page"' : ''; ?>>Gallery</a>
                    <a href="contact.php"<?php echo $hx_page === 'contact.php' ? ' class="is-active" aria-current="page"' : ''; ?>>Contact</a>

                    <a class="btn btn-primary hx-drawer-apply" href="admission.php">Apply Online</a>
                </div>
            </div>
        </nav>
    </header>

    <script>
    // Header: shadow on scroll, mobile drawer + dropdowns
    (function () {
        var header = document.getElementById('site-header');
        var toggle = document.getElementById('hx-toggle');
        var nav = document.getElementById('hx-nav');
        var backdrop = document.getElementById('hx-backdrop');
        var DRAWER_MAX = 900;   // keep in sync with the @media (max-width: 900px) header rules in css/style.css

        function shadow() { header.classList.toggle('is-scrolled', window.scrollY > 10); }
        shadow();
        window.addEventListener('scroll', shadow, { passive: true });
        if (!toggle || !nav) return;

        function setOpen(open) {
            nav.classList.toggle('is-open', open);
            toggle.classList.toggle('is-open', open);
            if (backdrop) backdrop.classList.toggle('is-open', open);
            header.classList.toggle('nav-open', open);   // lifts the brand bar so the close (X) button stays visible
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Toggle menu');
            document.body.style.overflow = open ? 'hidden' : '';
            if (!open) nav.querySelectorAll('.hx-dropdown').forEach(function (d) { d.classList.remove('is-open'); });
        }
        toggle.addEventListener('click', function () { setOpen(!nav.classList.contains('is-open')); });
        if (backdrop) backdrop.addEventListener('click', function () { setOpen(false); });

        // Dropdown groups expand inline inside the drawer; on desktop they open on hover/focus (CSS)
        nav.querySelectorAll('.hx-dropdown-trigger').forEach(function (trigger) {
            var group = trigger.closest('.hx-dropdown');
            function toggleGroup() {
                if (window.innerWidth > DRAWER_MAX) return;
                var open = !group.classList.contains('is-open');
                group.classList.toggle('is-open', open);
                trigger.setAttribute('aria-expanded', String(open));
            }
            trigger.addEventListener('click', toggleGroup);
            trigger.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleGroup(); }
            });
        });

        // Close the drawer after choosing a link, on Escape, or when resizing up to desktop
        nav.querySelectorAll('a').forEach(function (a) { a.addEventListener('click', function () { setOpen(false); }); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && nav.classList.contains('is-open')) { setOpen(false); toggle.focus(); } });
        window.addEventListener('resize', function () { if (window.innerWidth > DRAWER_MAX) setOpen(false); });

        // Open the current page's group inside the drawer
        if (window.innerWidth <= DRAWER_MAX) {
            var active = nav.querySelector('.hx-dropdown.is-active');
            if (active) active.classList.add('is-open');
        }
    })();
    </script>
