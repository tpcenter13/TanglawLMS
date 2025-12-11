<?php

$host = "localhost";        
$user = "root";           
$password = "";            
$database = "tanglaw_lms";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");
// Ensure activity_submissions table exists (lightweight, safe to run on each request)
$createActivitySubmissions = "CREATE TABLE IF NOT EXISTS activity_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    module_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    comments TEXT,
    status VARCHAR(50) DEFAULT 'submitted',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if (! $conn->query($createActivitySubmissions)) {
    error_log('Failed to ensure activity_submissions table: ' . $conn->error);
}

// Ensure password_changes table exists to track password change history
$createPasswordChanges = "CREATE TABLE IF NOT EXISTS password_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role VARCHAR(50) NOT NULL,
    user_id INT NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (role, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
if (! $conn->query($createPasswordChanges)) {
    error_log('Failed to ensure password_changes table: ' . $conn->error);
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if(!isset($_SESSION['loggedUser'])) {
    // Redirect to login if not on login page
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page != 'login.php') {
        header("Location: login.php");
        exit();
    }
} else {
    $loggedUser = $_SESSION['loggedUser'];
}
?>

