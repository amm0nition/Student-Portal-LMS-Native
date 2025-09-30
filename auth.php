<?php
// File: auth.php

session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $user_type = trim($_POST['user_type']);

    $table_name = '';
    $dashboard_page = '';
    switch ($user_type) {
        case 'student':
            $table_name = 'auth_student';
            $dashboard_page = 'student_dashboard.php';
            break;
        case 'teacher':
            $table_name = 'auth_teacher';
            $dashboard_page = 'teacher_dashboard.php';
            break;
        case 'admin':
            $table_name = 'auth_admin';
            $dashboard_page = 'admin_dashboard.php';
            break;
        default:
            $_SESSION['login_error'] = "Invalid user role selected.";
            header("Location: login.php");
            exit();
    }

    $sql = "SELECT * FROM " . $table_name . " WHERE email = ?";
    
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("s", $email);
        
        if($stmt->execute()){
            $result = $stmt->get_result();
            
            if($result->num_rows == 1){
                $user = $result->fetch_assoc();

                if($password === $user['password']){
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id"] = $user['id'];
                    $_SESSION["email"] = $user['email'];
                    $_SESSION["full_name"] = $user['full_name'];
                    $_SESSION["user_type"] = $user_type;

                    // ** NEW: Store class_id for students in the session **
                    if ($user_type === 'student') {
                        $_SESSION["class_id"] = $user['class_id'];
                    }

                    header("Location: " . $dashboard_page);
                    exit();
                } else {
                    $_SESSION['login_error'] = "The password you entered was not valid.";
                    header("Location: login.php");
                    exit();
                }
            } else {
                $_SESSION['login_error'] = "No account found with that email.";
                header("Location: login.php");
                exit();
            }
        } else {
            echo "Oops! Something went wrong. Please try again later.";
        }
        $stmt->close();
    }
    
    $conn->close();

} else {
    header("Location: login.php");
    exit();
}
?>