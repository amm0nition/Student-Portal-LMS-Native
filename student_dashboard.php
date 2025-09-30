<?php
// File: student_dashboard.php (Full Script with Override Logic)

session_start();
require_once 'db_connect.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== 'student') {
    header("Location: login.php");
    exit;
}

// --- Initialize variables ---
$student_name = htmlspecialchars($_SESSION["full_name"]);
$student_class_id = isset($_SESSION["class_id"]) ? (int)$_SESSION["class_id"] : 0;
$all_events = [];
$is_day_off = false;
$day_off_reason = '';

// --- Date Selection & Navigation Logic ---
$selected_date_str = $_GET['date'] ?? date('Y-m-d');
$selected_date_obj = new DateTime($selected_date_str);

$prev_month_obj = (clone $selected_date_obj)->modify('first day of last month');
$next_month_obj = (clone $selected_date_obj)->modify('first day of next month');
$prev_month_link = '?date=' . $prev_month_obj->format('Y-m-d');
$next_month_link = '?date=' . $next_month_obj->format('Y-m-d');

$selected_year = $selected_date_obj->format('Y');
$selected_month = $selected_date_obj->format('m');
$selected_day = $selected_date_obj->format('j');
$selected_day_of_week = $selected_date_obj->format('N'); // 1=Mon, 7=Sun

// --- Query 1: Fetch ONE-TIME events first to check for holidays ---
$one_time_sql = "
    SELECT title, description, event_date, start_time, end_time
    FROM calendar_events
    WHERE (class_id = ? OR class_id = 0) AND event_date = ?
";
$stmt_one = $conn->prepare($one_time_sql);
$stmt_one->bind_param("is", $student_class_id, $selected_date_str);
$stmt_one->execute();
$one_time_result = $stmt_one->get_result();
while ($row = $one_time_result->fetch_assoc()) {
    // ** NEW: Check if this is an all-day, blocking event (like a holiday) **
    if (empty($row['start_time'])) {
        $is_day_off = true;
        $day_off_reason = $row['title'];
        $all_events = [$row]; // If it's a holiday, we only need this one event
        break; // Stop processing other events for this day
    }
    $row['type'] = 'event';
    $all_events[] = $row;
}

// --- Query 2: Fetch RECURRING classes ONLY if it's not a day off ---
if (!$is_day_off) {
    $recurring_sql = "
        SELECT sc.start_time, sc.end_time, sub.subject_name, t.full_name AS teacher_name
        FROM schedule sc
        JOIN subjects sub ON sc.subject_id = sub.id
        JOIN auth_teacher t ON sc.teacher_id = t.id
        WHERE sc.class_id = ? AND sc.day_of_week = ?
    ";
    $stmt_rec = $conn->prepare($recurring_sql);
    $stmt_rec->bind_param("ii", $student_class_id, $selected_day_of_week);
    $stmt_rec->execute();
    $recurring_result = $stmt_rec->get_result();
    while ($row = $recurring_result->fetch_assoc()) {
        $row['type'] = 'class';
        $all_events[] = $row;
    }

    // --- Sort the combined schedule by start time ---
    usort($all_events, function ($a, $b) {
        if ($a['start_time'] == $b['start_time']) return 0;
        return ($a['start_time'] < $b['start_time']) ? -1 : 1;
    });
}


// --- Fetch Announcements ---
$announcement_sql = "
    SELECT title, content, posted_by, created_at FROM announcements 
    WHERE class_id = ? OR class_id = 0 ORDER BY created_at DESC";
$stmt_ann = $conn->prepare($announcement_sql);
$stmt_ann->bind_param("i", $student_class_id);
$stmt_ann->execute();
$announcement_result = $stmt_ann->get_result();


// --- Calendar and Header Logic ---
$today_str = date('Y-m-d');
$num_days_in_month = (int)$selected_date_obj->format('t');
$first_day_of_month_obj = new DateTime("$selected_year-$selected_month-01");
$first_day_of_month_weekday = (int)$first_day_of_month_obj->format('w'); 
$header_date_string = $selected_date_obj->format('l, d F Y');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

    <nav class="dashboard-nav">
        <div class="nav-logo"><h3>Student Portal</h3></div>
        <ul class="nav-links">
            <li><a href="student_dashboard.php" class="active">Home</a></li>
            <li><a href="#">Courses</a></li>
            <li><a href="profile.php">Profile</a></li>
        </ul>
        <div class="nav-logout"><a href="logout.php">Logout</a></div>
    </nav>

    <div class="dashboard-container">
        <header class="dashboard-header">
            <h1>Welcome back, <?php echo $student_name; ?>!</h1>
            <p>Here’s a summary of your activities for <?php echo $header_date_string; ?>.</p>
        </header>

        <main class="dashboard-grid">
            <section class="grid-main-content">
                <div class="tile">
                    <h2>Recent Announcements</h2>
                    <?php if ($announcement_result->num_rows > 0): while($announcement = $announcement_result->fetch_assoc()): ?>
                        <div class="announcement">
                            <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                            <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                            <small>Posted by <?php echo htmlspecialchars($announcement['posted_by']); ?> - <?php echo date('d M Y', strtotime($announcement['created_at'])); ?></small>
                        </div>
                    <?php endwhile; else: ?><p class="empty-state">No recent announcements.</p><?php endif; ?>
                </div>

                <div class="tile tile-large">
                    <h2>Schedule for <?php echo $selected_date_obj->format('l, d M'); ?></h2>
                    <!-- ** NEW: Main display logic ** -->
                    <?php if ($is_day_off): ?>
                        <div class="schedule-holiday">
                            <span class="holiday-icon">🎉</span>
                            <span class="holiday-title"><?php echo htmlspecialchars($day_off_reason); ?></span>
                            <span class="holiday-desc">All recurring activities are suspended.</span>
                        </div>
                    <?php elseif (!empty($all_events)): ?>
                        <div class="schedule-list">
                        <?php foreach($all_events as $item): ?>
                            <?php if ($item['type'] === 'class'): ?>
                                <div class="schedule-item class-type">
                                    <div class="schedule-time"><?php echo date('H:i', strtotime($item['start_time'])); ?> - <?php echo date('H:i', strtotime($item['end_time'])); ?></div>
                                    <div class="schedule-details">
                                        <span class="schedule-subject"><?php echo htmlspecialchars($item['subject_name']); ?></span>
                                        <span class="schedule-teacher"><?php echo htmlspecialchars($item['teacher_name']); ?></span>
                                    </div>
                                </div>
                            <?php elseif ($item['type'] === 'event'): ?>
                                 <div class="schedule-item event-type">
                                    <div class="schedule-time"><?php echo date('H:i', strtotime($item['start_time'])); if (!empty($item['end_time'])) { echo ' - ' . date('H:i', strtotime($item['end_time'])); } ?></div>
                                    <div class="schedule-details">
                                        <span class="schedule-subject"><?php echo htmlspecialchars($item['title']); ?></span>
                                        <span class="schedule-teacher"><?php echo htmlspecialchars($item['description']); ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="empty-state">No classes or events scheduled for this day.</p>
                    <?php endif; ?>
                </div>
            </section>
            
            <aside class="grid-sidebar">
                <div class="tile"><h2>Upcoming Assignments</h2><p class="empty-state">You have no upcoming assignments.</p></div>
                <div class="tile">
                    <div class="calendar-header">
                        <a href="<?php echo $prev_month_link; ?>" class="calendar-nav">&lt;</a>
                        <h2><?php echo $selected_date_obj->format('F Y'); ?></h2>
                        <a href="<?php echo $next_month_link; ?>" class="calendar-nav">&gt;</a>
                    </div>
                    <table class="calendar-table">
                        <thead><tr><th>S</th><th>M</th><th>T</th><th>W</th><th>T</th><th>F</th><th>S</th></tr></thead>
                        <tbody><tr>
                            <?php
                                for ($i = 0; $i < $first_day_of_month_weekday; $i++) echo "<td></td>";
                                for ($day = 1; $day <= $num_days_in_month; $day++) {
                                    $current_loop_date_str = "$selected_year-$selected_month-" . str_pad($day, 2, '0', STR_PAD_LEFT);
                                    $class = '';
                                    if ($current_loop_date_str == $today_str) $class = 'today';
                                    if ($day == $selected_day && $selected_date_str >= "$selected_year-$selected_month-01" && $selected_date_str <= "$selected_year-$selected_month-$num_days_in_month") $class = 'selected';
                                    echo "<td class='{$class}'><a href='?date={$current_loop_date_str}'>{$day}</a></td>";
                                    if (($day + $first_day_of_month_weekday) % 7 == 0) echo "</tr><tr>";
                                }
                                while (($day + $first_day_of_month_weekday - 1) % 7 != 0) { echo "<td></td>"; $day++; }
                            ?>
                        </tr></tbody>
                    </table>
                </div>
            </aside>
        </main>
    </div>
</body>
</html>