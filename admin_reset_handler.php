<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

session_start();
header('Content-Type: application/json');

require_once 'conn.php';
require_once 'admin_functions_users.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'generate_reset_token') {
    $role = $_POST['role'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);
    
    if (!$role || !$user_id) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }
    
    // Get user data based on role
    $table = '';
    if ($role === 'teacher') {
        $table = 'teachers';
    } elseif ($role === 'facilitator') {
        $table = 'facilitators';
    } elseif ($role === 'detainee') {
        $table = 'detainees';
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        exit;
    }
    
    $stmt = $conn->prepare("SELECT id, name, email FROM $table WHERE id = ? AND archived = 0 LIMIT 1");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$user || empty($user['email'])) {
        echo json_encode(['success' => false, 'message' => 'User not found or no email']);
        exit;
    }
    
    // Generate token using your existing function
    $token = createPasswordResetToken($conn, $role, $user_id);
    
    if ($token) {
        echo json_encode([
            'success' => true,
            'token' => $token,
            'email' => $user['email'],
            'name' => $user['name']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to generate token']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>