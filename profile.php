<?php
// File: profile.php

session_start();
require_once 'db_connect.php';
date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION["loggedin"]) || $_SESSION["user_type"] !== 'student') {
    header("Location: login.php");
    exit;
}

$student_session_id = $_SESSION["id"];
$student_name = htmlspecialchars($_SESSION["full_name"]);

// --- Fetch detailed student profile information ---
$profile_sql = "
    SELECT s.full_name, s.student_id, s.email, s.major, c.class_name 
    FROM auth_student s
    LEFT JOIN classes c ON s.class_id = c.id
    WHERE s.id = ?
";
$stmt = $conn->prepare($profile_sql);
$stmt->bind_param("i", $student_session_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    echo "Error: Student profile not found.";
    exit;
}

// --- Helper to get initials from name for the avatar ---
$name_parts = explode(' ', trim($student['full_name']));
$first_initial = $name_parts[0][0] ?? '';
$last_initial = count($name_parts) > 1 ? end($name_parts)[0] : '';
$initials = strtoupper($first_initial . $last_initial);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body class="dashboard-body">

    <nav class="dashboard-nav">
        <div class="nav-logo">
            <h3>Student Portal</h3>
        </div>
        <ul class="nav-links">
            <li><a href="student_dashboard.php">Home</a></li>
            <li><a href="#">Courses</a></li>
            <li><a href="profile.php" class="active">Profile</a></li>
        </ul>
        <div class="nav-logout">
            <a href="logout.php">Logout</a>
        </div>
    </nav>

    <div class="dashboard-container">
        <header class="dashboard-header">
            <h1>My Profile</h1>
            <p>Your personal and academic details.</p>
        </header>

        <main class="profile-grid">
            <!-- Left Column: Profile Card -->
            <div class="tile profile-card">
                <div class="profile-avatar">
                    <span><?php echo $initials; ?></span>
                </div>
                <h2 class="profile-name"><?php echo htmlspecialchars($student['full_name']); ?></h2>
                <p class="profile-id"><?php echo htmlspecialchars($student['student_id']); ?></p>
                <span class="status-badge">Active Student</span>
            </div>

            <!-- Right Column: Details & Settings -->
            <div class="profile-details-panel">
                <div class="tile">
                    <h2>Academic Information</h2>
                    <div class="profile-details">
                        <div class="detail-item">
                            <span class="detail-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            </span>
                            <span class="detail-label">Major</span>
                            <span class="detail-value"><?php echo htmlspecialchars($student['major'] ?? 'Not set'); ?></span>
                        </div>
                        <div class="detail-item">
                             <span class="detail-icon">
                               <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            </span>
                            <span class="detail-label">Class</span>
                            <span class="detail-value"><?php echo htmlspecialchars($student['class_name'] ?? 'Not assigned'); ?></span>
                        </div>
                         <div class="detail-item">
                            <span class="detail-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            </span>
                            <span class="detail-label">Email Address</span>
                            <span class="detail-value"><?php echo htmlspecialchars($student['email']); ?></span>
                        </div>
                    </div>
                </div>
                 <div class="tile">
                    <h2>Account Settings</h2>
                    <form class="settings-form">
                        <div class="input-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password">
                        </div>
                        <div class="input-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password">
                        </div>
                        <button type="submit" class="button-primary" disabled>Save Changes</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

</body>
</html>