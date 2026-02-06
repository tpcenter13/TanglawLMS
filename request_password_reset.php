<?php
/**
 * Standalone Password Reset Handler
 * No external dependencies - all code self-contained
 */

// Start output buffering to prevent any accidental output
ob_start();

// Start session
session_start();

// Set JSON header FIRST
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Disable error display
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// ============================================
// DATABASE CONNECTION
// ============================================
// TODO: Update these with your actual database credentials
$db_host = 'localhost';
$db_user = 'root';  // ← CHANGE THIS
$db_pass = '';      // ← CHANGE THIS  
$db_name = 'tanglaw_lms';  // ← CHANGE THIS

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Database connection error. Please contact administrator.'
    ]);
    exit;
}

// ============================================
// VALIDATE REQUEST METHOD
// ============================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// ============================================
// GET AND VALIDATE INPUT
// ============================================
$email = trim($_POST['email'] ?? '');
$role = trim($_POST['role'] ?? '');

// Validate email
if (empty($email)) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Email address is required'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email address format'
    ]);
    exit;
}

// Validate role
if (empty($role)) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Role is required'
    ]);
    exit;
}

// Determine table based on role
$table = '';
$allowed_roles = ['teacher', 'facilitator', 'detainee'];

if (!in_array($role, $allowed_roles)) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Invalid role selected'
    ]);
    exit;
}

$table = $role === 'teacher' ? 'teachers' : 
         ($role === 'facilitator' ? 'facilitators' : 'detainees');

// ============================================
// FIND USER BY EMAIL
// ============================================
try {
    $stmt = $conn->prepare("SELECT id, name, email FROM $table WHERE email = ? AND archived = 0 LIMIT 1");
    
    if (!$stmt) {
        throw new Exception('Database query preparation failed');
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // User not found - return generic message for security
        // Don't reveal whether email exists or not
        $stmt->close();
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'send_email' => false,
            'message' => 'If an account exists with that email, a reset link has been sent.'
        ]);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Error searching for user account'
    ]);
    exit;
}

// ============================================
// ENSURE PASSWORD_RESETS TABLE EXISTS
// ============================================
try {
    $create_table_sql = "
    CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_role VARCHAR(20) NOT NULL,
        user_id INT NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_token (token),
        INDEX idx_expires (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    $conn->query($create_table_sql);
    
} catch (Exception $e) {
    // Table might already exist, continue
}

// ============================================
// GENERATE PASSWORD RESET TOKEN
// ============================================
try {
    // Generate secure random token
    $token = bin2hex(random_bytes(32));
    
    // Set expiration (1 hour from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Delete any existing tokens for this user
    $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE user_role = ? AND user_id = ?");
    $delete_stmt->bind_param("si", $role, $user['id']);
    $delete_stmt->execute();
    $delete_stmt->close();
    
    // Insert new token
    $insert_stmt = $conn->prepare(
        "INSERT INTO password_resets (user_role, user_id, token, expires_at) VALUES (?, ?, ?, ?)"
    );
    
    if (!$insert_stmt) {
        throw new Exception('Failed to prepare token insert');
    }
    
    $insert_stmt->bind_param("siss", $role, $user['id'], $token, $expires_at);
    
    if (!$insert_stmt->execute()) {
        throw new Exception('Failed to save reset token');
    }
    
    $insert_stmt->close();
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Failed to generate reset token. Please try again.'
    ]);
    exit;
}

// ============================================
// BUILD RESET LINK
// ============================================
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']);

// Remove trailing slash if present
$path = rtrim($path, '/');

$reset_link = $protocol . '://' . $host . $path . '/reset_password.php?token=' . $token;

// ============================================
// RETURN SUCCESS RESPONSE
// ============================================
ob_end_clean();

echo json_encode([
    'success' => true,
    'send_email' => true,
    'email' => $user['email'],
    'name' => $user['name'],
    'reset_link' => $reset_link,
    'message' => 'Password reset email will be sent'
], JSON_UNESCAPED_SLASHES);

exit;
?>