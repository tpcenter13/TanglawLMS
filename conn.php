<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = "localhost";        
$user = "root";           
$password = "";            
$database = "tanglaw_lms";
$port = 3306; // ✅ add this

$conn = new mysqli($host, $user, $password, $database, $port);

if ($conn->connect_error) die("❌ Connection failed: " . $conn->connect_error);

if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['loggedUser'])) {
    $current_page = basename($_SERVER['PHP_SELF']);
    if ($current_page != 'login.php') header("Location: login.php");
}
?>
