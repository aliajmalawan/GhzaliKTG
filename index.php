<?php
require_once 'includes/config.php';
$page_title = 'Home';
?>
<?php include 'includes/header.php'; ?>

<!-- News / Notification Bar -->
<?php require_once 'includes/notification_bar.php'; render_news_bar($conn); ?>

<!-- Hero Carousel Section -->
<?php $carousel_slides = getHeroCarouselSlides(); ?>
<section class="hero-carousel" style="position: relative; min-height: 600px; overflow: hidden;">
    <?php if (!empty($carousel_slides)): ?>
        <?php foreach ($carousel_slides as $index => $slide): ?>
            <div class="carousel-slide <?php echo $index === 0 ? 'active' : ''; ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(rgba(11, 77, 162, 0.5), rgba(10, 58, 122, 0.6)), url('<?php echo htmlspecialchars($slide['image_path']); ?>') center/cover no-repeat; min-height: 600px; display: flex; align-items: center; opacity: <?php echo $index === 0 ? '1' : '0'; ?>; transition: opacity 1.5s ease-in-out;">
                <div class="container" style="position: relative; z-index: 2; text-align: center;">
                    <div class="hero-content" style="max-width: 900px; margin: 0 auto;">
                        <h1 style="font-size: 56px; font-weight: bold; margin-bottom: 20px; color: <?php echo htmlspecialchars($slide['text_color'] ?? '#ffffff'); ?>; text-shadow: 2px 2px 12px rgba(0,0,0,0.7); animation: fadeInUp 1s;">
                            <?php echo htmlspecialchars($slide['title'] ?: 'Welcome to ' . getSiteName()); ?>
                        </h1>
                        <?php if (!empty($slide['subtitle'])): ?>
                            <p class="tagline" style="font-size: 28px; margin-bottom: 25px; color: <?php echo htmlspecialchars($slide['text_color'] ?? '#ffffff'); ?>; text-shadow: 2px 2px 10px rgba(0,0,0,0.7); animation: fadeInUp 1.2s;">
                                <?php echo htmlspecialchars($slide['subtitle']); ?>
                            </p>
                        <?php endif; ?>
                        <div class="btn-group" style="justify-content: center; gap: 20px; animation: fadeInUp 1.4s;">
                            <a href="admission.php" class="btn btn-primary" style="padding: 15px 40px; font-size: 18px;">Apply Now</a>
                            <a href="about.php" class="btn btn-outline" style="padding: 15px 40px; font-size: 18px;">Learn More</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Carousel Navigation Dots -->
        <div class="carousel-indicators" style="position: absolute; bottom: 30px; left: 50%; transform: translateX(-50%); z-index: 10; display: flex; gap: 12px;">
            <?php foreach ($carousel_slides as $index => $slide): ?>
                <button class="carousel-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>" style="width: 12px; height: 12px; border-radius: 50%; border: 2px solid white; background: <?php echo $index === 0 ? 'white' : 'transparent'; ?>; cursor: pointer; transition: all 0.3s;"></button>
            <?php endforeach; ?>
        </div>

        <!-- Carousel Navigation Arrows -->
        <button class="carousel-prev" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); z-index: 10; background: rgba(255,255,255,0.3); border: none; color: white; width: 50px; height: 50px; border-radius: 50%; font-size: 24px; cursor: pointer; transition: all 0.3s;">&lt;</button>
        <button class="carousel-next" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); z-index: 10; background: rgba(255,255,255,0.3); border: none; color: white; width: 50px; height: 50px; border-radius: 50%; font-size: 24px; cursor: pointer; transition: all 0.3s;">&gt;</button>
    <?php else: ?>
        <!-- Fallback to static hero if no carousel slides -->
        <?php $hero = getHeroContent(); ?>
        <div style="background: linear-gradient(rgba(11, 77, 162, 0.5), rgba(10, 58, 122, 0.6)), url('<?php echo htmlspecialchars($hero['image']); ?>') center/cover no-repeat; min-height: 600px; display: flex; align-items: center;">
            <div class="container" style="position: relative; z-index: 2; text-align: center;">
                <div class="hero-content" style="max-width: 900px; margin: 0 auto;">
                    <h1 style="font-size: 56px; font-weight: bold; margin-bottom: 20px; text-shadow: 2px 2px 12px rgba(0,0,0,0.7);">Welcome to <?php echo getSiteName(); ?></h1>
                    <p class="tagline" style="font-size: 28px; margin-bottom: 25px; text-shadow: 2px 2px 10px rgba(0,0,0,0.7);"><?php echo htmlspecialchars($hero['title']); ?></p>
                    <p style="font-size: 20px; margin-bottom: 40px; line-height: 1.8; text-shadow: 2px 2px 8px rgba(0,0,0,0.7);"><?php echo htmlspecialchars($hero['description']); ?></p>
                    <div class="btn-group" style="justify-content: center; gap: 20px;">
                        <a href="<?php echo htmlspecialchars($hero['button_link']); ?>" class="btn btn-primary" style="padding: 15px 40px; font-size: 18px;"><?php echo htmlspecialchars($hero['button_text']); ?></a>
                        <a href="about.php" class="btn btn-outline" style="padding: 15px 40px; font-size: 18px;">Learn More</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<!-- Carousel JavaScript -->
<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.carousel-prev:hover, .carousel-next:hover {
    background: rgba(255,255,255,0.5) !important;
}

.carousel-dot:hover {
    background: rgba(255,255,255,0.7) !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const slides = document.querySelectorAll('.carousel-slide');
    const dots = document.querySelectorAll('.carousel-dot');
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    let currentSlide = 0;
    const totalSlides = slides.length;

    if (totalSlides <= 1) return; // No need for carousel if only one slide

    function showSlide(index) {
        // Hide all slides
        slides.forEach(slide => {
            slide.style.opacity = '0';
            slide.classList.remove('active');
        });

        // Remove active from all dots
        dots.forEach(dot => {
            dot.style.background = 'transparent';
            dot.classList.remove('active');
        });

        // Show current slide
        slides[index].style.opacity = '1';
        slides[index].classList.add('active');
        dots[index].style.background = 'white';
        dots[index].classList.add('active');
    }

    function nextSlide() {
        currentSlide = (currentSlide + 1) % totalSlides;
        showSlide(currentSlide);
    }

    function prevSlide() {
        currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
        showSlide(currentSlide);
    }

    // Auto advance carousel every 5 seconds
    let autoPlay = setInterval(nextSlide, 5000);

    // Next button
    if (nextBtn) {
        nextBtn.addEventListener('click', function() {
            nextSlide();
            clearInterval(autoPlay);
            autoPlay = setInterval(nextSlide, 5000);
        });
    }

    // Previous button
    if (prevBtn) {
        prevBtn.addEventListener('click', function() {
            prevSlide();
            clearInterval(autoPlay);
            autoPlay = setInterval(nextSlide, 5000);
        });
    }

    // Dot navigation
    dots.forEach((dot, index) => {
        dot.addEventListener('click', function() {
            currentSlide = index;
            showSlide(currentSlide);
            clearInterval(autoPlay);
            autoPlay = setInterval(nextSlide, 5000);
        });
    });

    // Pause on hover
    const carouselSection = document.querySelector('.hero-carousel');
    if (carouselSection) {
        carouselSection.addEventListener('mouseenter', () => {
            clearInterval(autoPlay);
        });

        carouselSection.addEventListener('mouseleave', () => {
            autoPlay = setInterval(nextSlide, 5000);
        });
    }
});
</script>

<!-- Stats Section -->
<?php $stats = getStatistics(); ?>
<section class="stats">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <h3><?php echo htmlspecialchars($stats['students']); ?>+</h3>
                <p>Active Students</p>
            </div>
            <div class="stat-item">
                <h3><?php echo htmlspecialchars($stats['teachers']); ?>+</h3>
                <p>Expert Teachers</p>
            </div>
            <div class="stat-item">
                <h3><?php echo htmlspecialchars($stats['courses']); ?>+</h3>
                <p>Courses Offered</p>
            </div>
            <div class="stat-item">
                <h3><?php echo htmlspecialchars($stats['years']); ?>+</h3>
                <p>Years of Excellence</p>
            </div>
        </div>
    </div>
</section>

<!-- Digital School Management System -->
<section class="bg-light">
    <div class="container">
        <div class="section-header">
            <h2>
                <i class="fas fa-laptop-code" style="color: var(--accent-color); margin-right: 15px;"></i>
                Complete Digital School Management System
            </h2>
            <p style="font-size: 18px; max-width: 800px; margin: 0 auto;"><?php echo SITE_NAME; ?> is fully digitized - Experience modern education management with cutting-edge technology</p>
        </div>

        <!-- Features Grid - Compact Style -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 40px;">
            <!-- Exam Results -->
            <div style="display: flex; align-items: center; gap: 15px; background: white; padding: 18px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); transition: all 0.3s ease; cursor: pointer; border-left: 4px solid #0B4DA2;" onmouseover="this.style.transform='translateX(5px)'; this.style.boxShadow='0 5px 20px rgba(11,77,162,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='0 2px 12px rgba(0,0,0,0.08)'">
                <div style="min-width: 50px; height: 50px; background: linear-gradient(135deg, #0B4DA2, #1976D2); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-chart-line" style="font-size: 22px; color: white;"></i>
                </div>
                <div>
                    <h4 style="color: var(--primary-color); margin: 0 0 5px 0; font-size: 16px; font-weight: 600;">Online Exam Results</h4>
                    <p style="color: var(--text-light); font-size: 13px; margin: 0; line-height: 1.3;">Instant results & analysis</p>
                </div>
            </div>

            <!-- Attendance -->
            <div style="display: flex; align-items: center; gap: 15px; background: white; padding: 18px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); transition: all 0.3s ease; cursor: pointer; border-left: 4px solid #28a745;" onmouseover="this.style.transform='translateX(5px)'; this.style.boxShadow='0 5px 20px rgba(40,167,69,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='0 2px 12px rgba(0,0,0,0.08)'">
                <div style="min-width: 50px; height: 50px; background: linear-gradient(135deg, #28a745, #20c997); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-check" style="font-size: 22px; color: white;"></i>
                </div>
                <div>
                    <h4 style="color: var(--primary-color); margin: 0 0 5px 0; font-size: 16px; font-weight: 600;">Digital Attendance</h4>
                    <p style="color: var(--text-light); font-size: 13px; margin: 0; line-height: 1.3;">Real-time tracking</p>
                </div>
            </div>

            <!-- WhatsApp/SMS -->
            <div style="display: flex; align-items: center; gap: 15px; background: white; padding: 18px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); transition: all 0.3s ease; cursor: pointer; border-left: 4px solid #25D366;" onmouseover="this.style.transform='translateX(5px)'; this.style.boxShadow='0 5px 20px rgba(37,211,102,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='0 2px 12px rgba(0,0,0,0.08)'">
                <div style="min-width: 50px; height: 50px; background: linear-gradient(135deg, #25D366, #128C7E); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fab fa-whatsapp" style="font-size: 22px; color: white;"></i>
                </div>
                <div>
                    <h4 style="color: var(--primary-color); margin: 0 0 5px 0; font-size: 16px; font-weight: 600;">WhatsApp & SMS</h4>
                    <p style="color: var(--text-light); font-size: 13px; margin: 0; line-height: 1.3;">Auto alerts to parents</p>
                </div>
            </div>

            <!-- Parent Complaints -->
            <div style="display: flex; align-items: center; gap: 15px; background: white; padding: 18px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); transition: all 0.3s ease; cursor: pointer; border-left: 4px solid #f39c12;" onmouseover="this.style.transform='translateX(5px)'; this.style.boxShadow='0 5px 20px rgba(243,156,18,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='0 2px 12px rgba(0,0,0,0.08)'">
                <div style="min-width: 50px; height: 50px; background: linear-gradient(135deg, #f39c12, #e67e22); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-comments" style="font-size: 22px; color: white;"></i>
                </div>
                <div>
                    <h4 style="color: var(--primary-color); margin: 0 0 5px 0; font-size: 16px; font-weight: 600;">Parent Complaints</h4>
                    <p style="color: var(--text-light); font-size: 13px; margin: 0; line-height: 1.3;">Quick resolution system</p>
                </div>
            </div>

            <!-- Fee History -->
            <div style="display: flex; align-items: center; gap: 15px; background: white; padding: 18px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); transition: all 0.3s ease; cursor: pointer; border-left: 4px solid #9b59b6;" onmouseover="this.style.transform='translateX(5px)'; this.style.boxShadow='0 5px 20px rgba(155,89,182,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='0 2px 12px rgba(0,0,0,0.08)'">
                <div style="min-width: 50px; height: 50px; background: linear-gradient(135deg, #9b59b6, #8e44ad); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-wallet" style="font-size: 22px; color: white;"></i>
                </div>
                <div>
                    <h4 style="color: var(--primary-color); margin: 0 0 5px 0; font-size: 16px; font-weight: 600;">Fee History</h4>
                    <p style="color: var(--text-light); font-size: 13px; margin: 0; line-height: 1.3;">Online fee management</p>
                </div>
            </div>

            <!-- Digital Diaries -->
            <div style="display: flex; align-items: center; gap: 15px; background: white; padding: 18px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); transition: all 0.3s ease; cursor: pointer; border-left: 4px solid #e74c3c;" onmouseover="this.style.transform='translateX(5px)'; this.style.boxShadow='0 5px 20px rgba(231,76,60,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='0 2px 12px rgba(0,0,0,0.08)'">
                <div style="min-width: 50px; height: 50px; background: linear-gradient(135deg, #e74c3c, #c0392b); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-book-open" style="font-size: 22px; color: white;"></i>
                </div>
                <div>
                    <h4 style="color: var(--primary-color); margin: 0 0 5px 0; font-size: 16px; font-weight: 600;">Digital Diaries</h4>
                    <p style="color: var(--text-light); font-size: 13px; margin: 0; line-height: 1.3;">Online homework</p>
                </div>
            </div>

            <!-- Timetable -->
            <div style="display: flex; align-items: center; gap: 15px; background: white; padding: 18px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); transition: all 0.3s ease; cursor: pointer; border-left: 4px solid #00bcd4;" onmouseover="this.style.transform='translateX(5px)'; this.style.boxShadow='0 5px 20px rgba(0,188,212,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='0 2px 12px rgba(0,0,0,0.08)'">
                <div style="min-width: 50px; height: 50px; background: linear-gradient(135deg, #00bcd4, #0097a7); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-calendar-alt" style="font-size: 22px; color: white;"></i>
                </div>
                <div>
                    <h4 style="color: var(--primary-color); margin: 0 0 5px 0; font-size: 16px; font-weight: 600;">Smart Timetable</h4>
                    <p style="color: var(--text-light); font-size: 13px; margin: 0; line-height: 1.3;">Dynamic schedules</p>
                </div>
            </div>

            <!-- PTM Notes -->
            <div style="display: flex; align-items: center; gap: 15px; background: white; padding: 18px; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); transition: all 0.3s ease; cursor: pointer; border-left: 4px solid #ff9800;" onmouseover="this.style.transform='translateX(5px)'; this.style.boxShadow='0 5px 20px rgba(255,152,0,0.2)'" onmouseout="this.style.transform='translateX(0)'; this.style.boxShadow='0 2px 12px rgba(0,0,0,0.08)'">
                <div style="min-width: 50px; height: 50px; background: linear-gradient(135deg, #ff9800, #f57c00); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-clipboard-list" style="font-size: 22px; color: white;"></i>
                </div>
                <div>
                    <h4 style="color: var(--primary-color); margin: 0 0 5px 0; font-size: 16px; font-weight: 600;">PTM Notes</h4>
                    <p style="color: var(--text-light); font-size: 13px; margin: 0; line-height: 1.3;">Meeting & feedback</p>
                </div>
            </div>
        </div>

        <!-- Mobile App CTA - Compact Horizontal -->
        <div style="background: linear-gradient(135deg, #0B4DA2 0%, #1976D2 100%); border-radius: 20px; padding: 35px; box-shadow: 0 15px 40px rgba(11, 77, 162, 0.3); display: flex; align-items: center; gap: 30px; flex-wrap: wrap;">
            <div style="flex: 0 0 auto;">
                <i class="fas fa-mobile-alt" style="font-size: 70px; color: var(--accent-color);"></i>
            </div>
            <div style="flex: 1 1 300px;">
                <h3 style="color: white; font-size: 26px; margin: 0 0 10px 0; font-weight: 700;">Download Our Mobile App</h3>
                <p style="color: rgba(255,255,255,0.9); font-size: 15px; margin: 0; line-height: 1.6;">
                    Access all school features on your smartphone! Stay connected anytime, anywhere.
                </p>
            </div>
            <div style="flex: 0 0 auto; display: flex; gap: 12px; flex-wrap: wrap;">
                <?php
                $store_btn = 'display: inline-flex; align-items: center; gap: 10px; background: #000; color: white; padding: 12px 24px; border-radius: 10px; text-decoration: none; font-size: 14px; font-weight: 600; transition: transform 0.3s ease;';
                $play_url = getPlayStoreUrl();
                $apple_url = getAppStoreUrl();
                ?>
                <?php if ($play_url !== ''): ?>
                <a href="<?php echo htmlspecialchars($play_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="Get the Ghazali School app on Google Play (opens in a new tab)" style="<?php echo $store_btn; ?>" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <i class="fab fa-google-play" style="font-size: 26px;" aria-hidden="true"></i>
                    <div style="text-align: left;">
                        <div style="font-size: 9px; font-weight: 400; opacity: 0.8;">GET IT ON</div>
                        <div style="font-size: 15px;">Google Play</div>
                    </div>
                </a>
                <?php else: ?>
                <span role="link" aria-disabled="true" aria-label="Google Play app coming soon" title="Coming Soon" style="<?php echo $store_btn; ?> opacity: 0.5; cursor: not-allowed;">
                    <i class="fab fa-google-play" style="font-size: 26px;" aria-hidden="true"></i>
                    <div style="text-align: left;">
                        <div style="font-size: 9px; font-weight: 400; opacity: 0.8;">COMING SOON ON</div>
                        <div style="font-size: 15px;">Google Play</div>
                    </div>
                </span>
                <?php endif; ?>
                <?php if ($apple_url !== ''): ?>
                <a href="<?php echo htmlspecialchars($apple_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="Download the Ghazali School app on the App Store (opens in a new tab)" style="<?php echo $store_btn; ?>" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                    <i class="fab fa-apple" style="font-size: 30px;" aria-hidden="true"></i>
                    <div style="text-align: left;">
                        <div style="font-size: 9px; font-weight: 400; opacity: 0.8;">Download on the</div>
                        <div style="font-size: 15px;">App Store</div>
                    </div>
                </a>
                <?php else: ?>
                <span role="link" aria-disabled="true" aria-label="App Store app coming soon" title="Coming Soon" style="<?php echo $store_btn; ?> opacity: 0.5; cursor: not-allowed;">
                    <i class="fab fa-apple" style="font-size: 30px;" aria-hidden="true"></i>
                    <div style="text-align: left;">
                        <div style="font-size: 9px; font-weight: 400; opacity: 0.8;">Coming soon on the</div>
                        <div style="font-size: 15px;">App Store</div>
                    </div>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/leadership_section.php'; ?>

<!-- Why Choose Us Section -->
<section class="bg-light">
    <div class="container">
        <div class="section-header">
            <h2>Why Choose <?php echo SITE_NAME; ?>?</h2>
            <p>Excellence in education with a student-centered approach</p>
        </div>
        <div class="card-grid">
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h3>Expert Faculty</h3>
                <p>Learn from highly qualified and experienced teachers dedicated to student success.</p>
            </div>
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h3>Modern Facilities</h3>
                <p>State-of-the-art classrooms and learning resources for an enhanced educational experience.</p>
            </div>
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Small Class Sizes</h3>
                <p>Personalized attention with optimal student-teacher ratios for better learning outcomes.</p>
            </div>
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3>Quality Education</h3>
                <p>Curriculum designed to meet modern educational standards and prepare students for the future.</p>
            </div>
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Proven Results</h3>
                <p>Track record of excellent exam results and successful student placements.</p>
            </div>
            <div class="card">
                <div class="card-icon">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <h3>Affordable Fees</h3>
                <p>Quality education at competitive rates with flexible payment options available.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section style="background: linear-gradient(135deg, var(--primary-color) 0%, #0a3a7a 100%); color: white; padding: 60px 0;">
    <div class="container text-center">
        <h2 style="font-size: 36px; margin-bottom: 20px;">Ready to Start Your Educational Journey?</h2>
        <p style="font-size: 18px; margin-bottom: 30px;">Join <?php echo SITE_NAME; ?> today and unlock your potential</p>
        <div class="btn-group" style="justify-content: center;">
            <a href="admission.php" class="btn btn-primary">Apply for Admission</a>
            <a href="contact.php" class="btn btn-outline">Contact Us</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
