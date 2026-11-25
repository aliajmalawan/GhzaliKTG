<?php
require_once 'includes/config.php';
require_once 'includes/settings_helper.php';
require_once 'includes/matric_results.php';
ensure_matric_results_schema($conn);
$matric_docs = matric_documents_by_year($conn);

// Fetch active datesheets
$datesheets = mysqli_query($conn, "SELECT * FROM exam_datesheets WHERE status = 'active' ORDER BY exam_year DESC");

// Fetch active board results
$matric_results = mysqli_query($conn, "SELECT * FROM board_results WHERE status = 'active' AND board_type = 'Matric' AND image_path <> '' ORDER BY year DESC, display_order ASC");
$inter_results = mysqli_query($conn, "SELECT * FROM board_results WHERE status = 'active' AND board_type = 'Intermediate' ORDER BY year DESC, display_order ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Examination - <?php echo getSiteName(); ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <!-- Page Header with Background Image -->
    <section class="page-header" style="background-image: linear-gradient(rgba(11, 77, 162, 0.85), rgba(10, 58, 122, 0.85)), url('https://images.unsplash.com/photo-1434030216411-0b793f4b4173?q=80&w=1200'); background-size: cover; background-position: center; background-repeat: no-repeat; padding: 50px 0 60px; text-align: center; color: white; position: relative;">
        <div class="container" style="position: relative; z-index: 2;">
            <div style="display: inline-block; background: rgba(255,255,255,0.1); padding: 15px 35px; border-radius: 10px; backdrop-filter: blur(10px);">
                <i class="fas fa-file-alt" style="font-size: 2.5rem; margin-bottom: 12px; opacity: 0.9;"></i>
                <h1 style="font-size: 2.2rem; margin-bottom: 8px; font-weight: 700; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);">Examination</h1>
                <p style="font-size: 1rem; opacity: 0.95;">Datesheets & Board Results</p>
            </div>
        </div>
        <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 60px; background: linear-gradient(to bottom, transparent, var(--bg-light));"></div>
    </section>

    <!-- Datesheets Section -->
    <section style="padding: 60px 0;">
        <div class="container">
            <h2 style="text-align: center; color: var(--primary-color); margin-bottom: 40px;"><i class="fas fa-calendar-alt"></i> Exam Datesheets</h2>

            <?php
            if ($datesheets && mysqli_num_rows($datesheets) > 0) {
                while ($datesheet = mysqli_fetch_assoc($datesheets)) {
                    echo '<div class="card" style="margin-bottom: 40px;">';
                    echo '<h3 style="color: var(--primary-color); margin-bottom: 20px; text-align: center;">' . htmlspecialchars($datesheet['exam_name']) . '</h3>';

                    // Fetch details
                    $details = mysqli_query($conn, "SELECT * FROM datesheet_details WHERE datesheet_id = {$datesheet['id']} ORDER BY exam_date ASC, class ASC");

                    if ($details && mysqli_num_rows($details) > 0) {
                        // Group by date
                        $dates = [];
                        while ($detail = mysqli_fetch_assoc($details)) {
                            $dates[$detail['exam_date']][] = $detail;
                        }

                        echo '<div style="overflow-x: auto;">';
                        echo '<table style="width: 100%; border-collapse: collapse;">';
                        echo '<thead><tr style="background: var(--primary-color); color: white;">';

                        // Get unique dates
                        $date_keys = array_keys($dates);
                        echo '<th style="padding: 15px; text-align: left; vertical-align: top;">Class</th>';
                        foreach ($date_keys as $date) {
                            $day = $dates[$date][0]['day_name'];
                            echo '<th style="padding: 15px; text-align: center;">' . date('d.m.Y', strtotime($date)) . '<br><small>' . $day . '</small></th>';
                        }

                        echo '</tr></thead><tbody>';

                        // Get unique classes
                        $classes = [];
                        foreach ($dates as $date => $entries) {
                            foreach ($entries as $entry) {
                                $classes[$entry['class']][$date] = $entry['subject'];
                            }
                        }

                        foreach ($classes as $class => $subjects_by_date) {
                            echo '<tr style="border-bottom: 1px solid #e0e0e0;">';
                            echo '<td style="padding: 15px; font-weight: bold; background: #f8f9fa;">' . htmlspecialchars($class) . '</td>';
                            foreach ($date_keys as $date) {
                                echo '<td style="padding: 15px; text-align: center;">';
                                echo isset($subjects_by_date[$date]) ? htmlspecialchars($subjects_by_date[$date]) : '-';
                                echo '</td>';
                            }
                            echo '</tr>';
                        }

                        echo '</tbody></table></div>';
                    } else {
                        echo '<p style="text-align: center; color: var(--text-light);">Schedule not available yet</p>';
                    }

                    echo '</div>';
                }
            } else {
                echo '<div class="card"><p style="text-align: center; color: var(--text-light); padding: 40px;">No datesheets available at the moment</p></div>';
            }
            ?>
        </div>
    </section>

    <!-- Board Results Section -->
    <section id="results" style="padding: 60px 0; background: var(--bg-light);">
        <div class="container">
            <h2 style="text-align: center; color: var(--primary-color); margin-bottom: 40px;"><i class="fas fa-trophy"></i> Board Results</h2>
            <?php if (getResultPortalUrl() !== ''): ?>
            <div style="text-align: center; margin-bottom: 40px;">
                <a href="<?php echo htmlspecialchars(getResultPortalUrl()); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="display: inline-block; padding: 14px 32px;"><i class="fas fa-poll"></i> Check Result Online <i class="fas fa-external-link-alt" style="font-size: 12px;" aria-hidden="true"></i></a>
            </div>
            <?php endif; ?>

            <!-- Intermediate Results -->
            <div style="margin-bottom: 50px;">
                <h3 style="color: var(--primary-color); margin-bottom: 25px; border-left: 5px solid var(--primary-color); padding-left: 15px;">Intermediate Results</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px;">
                    <?php
                    if ($inter_results && mysqli_num_rows($inter_results) > 0) {
                        while ($result = mysqli_fetch_assoc($inter_results)) {
                            echo '<div class="card">';
                            echo '<img src="' . $result['image_path'] . '" style="width: 100%; height: 250px; object-fit: cover; border-radius: 5px; margin-bottom: 15px; cursor: pointer;" onclick="window.open(\'' . $result['image_path'] . '\', \'_blank\')">';
                            echo '<h4 style="color: var(--primary-color); margin-bottom: 10px;">' . htmlspecialchars($result['title']) . '</h4>';
                            echo '<p style="color: var(--text-light);">Year: ' . $result['year'] . '</p>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p style="color: var(--text-light);">No Intermediate results available yet</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Matric Results Archive (year-wise documents) -->
            <div id="matric-results">
                <h3 style="color: var(--primary-color); margin-bottom: 25px; border-left: 5px solid var(--primary-color); padding-left: 15px;">Matriculation Results <?php echo MATRIC_YEAR_MIN; ?>–<?php echo MATRIC_YEAR_MAX; ?></h3>

                <div class="mr-filter" role="group" aria-label="Filter Matric results by year">
                    <label for="mrYearSelect" style="font-weight: 600; color: var(--text-dark);">Select year:</label>
                    <select id="mrYearSelect">
                        <option value="all">All years</option>
                        <?php for ($y = MATRIC_YEAR_MAX; $y >= MATRIC_YEAR_MIN; $y--): ?><option value="<?php echo $y; ?>"><?php echo $y; ?></option><?php endfor; ?>
                    </select>
                    <label class="mr-check"><input type="checkbox" id="mrOnlyAvailable"> Show only available results</label>
                </div>

                <div class="mr-list">
                    <?php for ($y = MATRIC_YEAR_MAX; $y >= MATRIC_YEAR_MIN; $y--): $doc = $matric_docs[$y] ?? null; ?>
                        <div class="mr-row <?php echo $doc ? 'is-available' : 'is-missing'; ?>" data-year="<?php echo $y; ?>" data-available="<?php echo $doc ? '1' : '0'; ?>">
                            <div class="mr-row-year"><?php echo $y; ?></div>
                            <div class="mr-row-title"><?php echo $doc ? htmlspecialchars($doc['title']) : 'Matriculation Result ' . $y; ?></div>
                            <?php if ($doc): ?>
                                <a class="btn btn-primary mr-row-btn" href="download_result.php?id=<?php echo (int)$doc['id']; ?>" aria-label="Download Matric result <?php echo $y; ?> (<?php echo strtoupper(htmlspecialchars($doc['file_type'])); ?>)">
                                    <i class="fas fa-download" aria-hidden="true"></i> Download Result
                                    <small>(<?php echo strtoupper(htmlspecialchars($doc['file_type'])); ?>, <?php echo matric_format_size((int)$doc['file_size']); ?>)</small>
                                </a>
                            <?php else: ?>
                                <span class="mr-row-na"><i class="fas fa-info-circle" aria-hidden="true"></i> Result not available yet</span>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                    <p id="mrEmpty" style="display: none; color: var(--text-light); padding: 15px 20px;">No results available for this selection.</p>
                </div>

                <?php if ($matric_results && mysqli_num_rows($matric_results) > 0): ?>
                <h4 style="color: var(--primary-color); margin: 40px 0 20px;">Result Images</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px;">
                    <?php
                    while ($result = mysqli_fetch_assoc($matric_results)) {
                        echo '<div class="card">';
                        echo '<img src="' . htmlspecialchars($result['image_path']) . '" alt="' . htmlspecialchars($result['title']) . '" style="width: 100%; height: 250px; object-fit: cover; border-radius: 5px; margin-bottom: 15px; cursor: pointer;" onclick="window.open(this.src, \'_blank\')">';
                        echo '<h4 style="color: var(--primary-color); margin-bottom: 10px;">' . htmlspecialchars($result['title']) . '</h4>';
                        echo '<p style="color: var(--text-light);">Year: ' . (int)$result['year'] . '</p>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>

            <style>
                .mr-filter { display: flex; flex-wrap: wrap; gap: 15px; align-items: center; margin-bottom: 20px; }
                .mr-filter select { padding: 10px 14px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; min-width: 140px; }
                .mr-check { display: inline-flex; gap: 8px; align-items: center; color: var(--text-dark); cursor: pointer; }
                .mr-list { background: #fff; border-radius: 10px; box-shadow: var(--shadow); overflow: hidden; }
                .mr-row { display: grid; grid-template-columns: 90px 1fr auto; gap: 15px; align-items: center; padding: 14px 20px; border-bottom: 1px solid #eee; }
                .mr-row-year { font-size: 22px; font-weight: 700; color: var(--primary-color); }
                .mr-row-title { color: var(--text-dark); font-weight: 500; }
                .mr-row-btn { padding: 9px 18px; font-size: 14px; white-space: nowrap; }
                .mr-row-btn small { opacity: .85; margin-left: 4px; }
                .mr-row-na { color: var(--text-light); font-size: 14px; }
                .mr-row.is-missing .mr-row-title { color: var(--text-light); }
                @media (max-width: 600px) {
                    .mr-row { grid-template-columns: 70px 1fr; }
                    .mr-row-btn, .mr-row-na { grid-column: 1 / -1; text-align: center; }
                }
            </style>
            <script>
            (function () {
                var sel = document.getElementById('mrYearSelect'), only = document.getElementById('mrOnlyAvailable'),
                    rows = document.querySelectorAll('.mr-row'), empty = document.getElementById('mrEmpty');
                function apply() {
                    var shown = 0;
                    rows.forEach(function (r) {
                        var ok = (sel.value === 'all' || r.dataset.year === sel.value) && (!only.checked || r.dataset.available === '1');
                        r.style.display = ok ? '' : 'none';
                        if (ok) shown++;
                    });
                    empty.style.display = shown ? 'none' : 'block';
                }
                sel.addEventListener('change', apply);
                only.addEventListener('change', apply);
                var q = (location.search.match(/[?&]year=(\d{4})/) || [])[1];
                if (q && sel.querySelector('option[value="' + q + '"]')) { sel.value = q; }
                apply();
            })();
            </script>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
