<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$password = ""; // change if your root has a password
$database = "tanglaw_lms";

$mysqli = @new mysqli($host, $user, $password, $database);

if ($mysqli->connect_errno) {
    echo "Connection failed: (" . $mysqli->connect_errno . ") " . htmlspecialchars($mysqli->connect_error);
    echo "<br><br>Debug info:<br>Host: " . htmlspecialchars($host) . "<br>User: " . htmlspecialchars($user) . "<br>Database: " . htmlspecialchars($database);
    exit;
}

echo "✅ Connected to MySQL successfully. Database `" . htmlspecialchars($database) . "` accessible.<br>";

$mysqli->close();
?>