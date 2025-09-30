<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal Login</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <form action="auth.php" method="post" class="login-form">
            <h2>Student Portal LMS</h2>
            <p>Please sign in to continue</p>

            <?php 
                // Check if a login error message exists in the session and display it.
                if (isset($_SESSION['login_error'])) {
                    echo '<div class="error-message">' . htmlspecialchars($_SESSION['login_error']) . '</div>';
                    unset($_SESSION['login_error']); // Clear the error message after displaying it.
                }
            ?>

            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="input-group">
                <label for="user_type">Login As</label>
                <select id="user_type" name="user_type" required>
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>

