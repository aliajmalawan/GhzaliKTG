<?php
/**
 * News / Notification bar (scrolling ticker). Data source: `notifications` table
 * (managed in AdminCP/manage_notifications.php).
 */

// Only allow safe link targets: relative paths, http(s), mailto, tel. Returns '' if unsafe/empty.
function news_bar_safe_link($link) {
    $link = trim((string)$link);
    if ($link === '' || preg_match('/[\x00-\x1F\x7F]/', $link)) return '';
    if (preg_match('#^(https?://|mailto:|tel:)#i', $link)) return $link;
    if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $link)) return '';   // any other scheme (javascript:, data:, ...)
    if (strpos($link, '//') === 0) return '';
    return $link;
}

function render_news_bar($conn) {
    $res = mysqli_query($conn, "SELECT title, link FROM notifications WHERE status = 'active' ORDER BY display_order ASC, created_at DESC LIMIT 10");
    if (!$res || mysqli_num_rows($res) === 0) return;

    $items = [];
    $chars = 0;
    while ($n = mysqli_fetch_assoc($res)) {
        $title = htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8');
        $link = news_bar_safe_link($n['link']);
        $chars += mb_strlen($n['title']) + 6;
        $inner = '<span class="news-bar-dot" aria-hidden="true"></span>' . $title;
        if ($link !== '') {
            $external = preg_match('#^https?://#i', $link) ? ' target="_blank" rel="noopener noreferrer"' : '';
            $items[] = '<a class="news-bar-item" href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '"' . $external . '>' . $inner . '</a>';
        } else {
            $items[] = '<span class="news-bar-item">' . $inner . '</span>';
        }
    }
    $group = implode('', $items);
    $duration = max(25, (int)round($chars * 2 * 0.2));   // seconds for one half-cycle; longer text scrolls longer
    ?>
<section class="news-bar" id="newsBar" aria-label="Latest news and announcements" style="--news-duration: <?php echo $duration; ?>s;">
    <div class="news-bar-label"><i class="fas fa-bullhorn" aria-hidden="true"></i><span class="news-bar-label-text">Latest News</span></div>
    <div class="news-bar-viewport">
        <div class="news-bar-track">
            <div class="news-bar-group"><?php echo $group; ?></div>
            <div class="news-bar-group" aria-hidden="true"><?php echo str_replace('<a ', '<a tabindex="-1" ', $group); ?></div>
            <div class="news-bar-group" aria-hidden="true"><?php echo str_replace('<a ', '<a tabindex="-1" ', $group); ?></div>
            <div class="news-bar-group" aria-hidden="true"><?php echo str_replace('<a ', '<a tabindex="-1" ', $group); ?></div>
        </div>
    </div>
    <button type="button" class="news-bar-toggle" id="newsBarToggle" aria-pressed="false" aria-label="Pause news ticker" title="Pause / play">
        <i class="fas fa-pause" aria-hidden="true"></i>
    </button>
</section>
<style>
.news-bar { display: flex; align-items: stretch; background: var(--accent-color); color: var(--text-dark); overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.12); position: relative; z-index: 1; }
.news-bar-label { flex: none; display: flex; align-items: center; gap: 8px; background: var(--primary-color); color: #fff; padding: 0 20px; font-weight: 700; font-size: 14px; letter-spacing: .5px; text-transform: uppercase; white-space: nowrap; }
.news-bar-viewport { flex: 1; min-width: 0; overflow: hidden; display: flex; align-items: center; }
.news-bar-track { display: flex; width: max-content; animation: news-bar-scroll var(--news-duration, 30s) linear infinite; }
.news-bar-group { display: flex; flex: none; min-width: 60vw; }
.news-bar-item { display: inline-flex; align-items: center; padding: 12px 36px 12px 0; margin-left: 28px; font-size: 15px; font-weight: 600; line-height: 1.3; white-space: nowrap; color: var(--text-dark); text-decoration: none; }
a.news-bar-item:hover, a.news-bar-item:focus-visible { text-decoration: underline; color: var(--primary-color); }
a.news-bar-item:focus-visible, .news-bar-toggle:focus-visible { outline: 3px solid var(--primary-color); outline-offset: -3px; }
.news-bar-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--primary-color); margin-right: 12px; flex: none; }
.news-bar-toggle { flex: none; width: 44px; border: 0; background: rgba(11, 77, 162, 0.12); color: var(--primary-color); cursor: pointer; font-size: 14px; }
.news-bar-toggle:hover { background: rgba(11, 77, 162, 0.22); }
.news-bar:hover .news-bar-track, .news-bar:focus-within .news-bar-track, .news-bar.is-paused .news-bar-track { animation-play-state: paused; }
@keyframes news-bar-scroll { from { transform: translateX(0); } to { transform: translateX(-50%); } }
@media (max-width: 576px) {
    .news-bar-label { padding: 0 14px; }
    .news-bar-label-text { display: none; }
    .news-bar-item { font-size: 14px; padding: 10px 28px 10px 0; margin-left: 18px; }
    .news-bar-toggle { width: 40px; }
}
@media (prefers-reduced-motion: reduce) {
    .news-bar-track { animation: none; }
    .news-bar-viewport { overflow-x: auto; }
    .news-bar-group[aria-hidden="true"], .news-bar-toggle { display: none; }
}
</style>
<script>
(function () {
    var bar = document.getElementById('newsBar'), btn = document.getElementById('newsBarToggle');
    if (!bar || !btn) return;
    btn.addEventListener('click', function () {
        var paused = bar.classList.toggle('is-paused');
        btn.setAttribute('aria-pressed', paused ? 'true' : 'false');
        btn.setAttribute('aria-label', paused ? 'Play news ticker' : 'Pause news ticker');
        btn.firstElementChild.className = paused ? 'fas fa-play' : 'fas fa-pause';
    });
})();
</script>
<?php
}
