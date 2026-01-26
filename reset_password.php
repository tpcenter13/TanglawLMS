<?php
session_start();
require_once 'conn.php';
require_once 'admin_functions_users.php';

$message = '';
$messageType = '';
$token = $_GET['token'] ?? '';
$validToken = false;
$userData = null;

// Validate token
if (!empty($token)) {
    ensurePasswordResetsTable($conn);
    
    // Check if token exists (removed the expires_at check from query)
    $stmt = $conn->prepare("SELECT user_role, user_id, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $tokenData = $result->fetch_assoc();
        
        // Manual expiry check
        $expiresTimestamp = strtotime($tokenData['expires_at']);
        $nowTimestamp = time();
        
        if ($expiresTimestamp < $nowTimestamp) {
            $message = 'This reset link has expired. Please request a new one.';
            $messageType = 'error';
        } else {
            $role = $tokenData['user_role'];
            $userId = $tokenData['user_id'];
            
            // Get user info
            if ($role === 'teacher') {
                $table = 'teachers';
            } elseif ($role === 'facilitator') {
                $table = 'facilitators';
            } elseif ($role === 'detainee') {
                $table = 'detainees';
            } else {
                $table = '';
            }
            
            if ($table) {
                $stmt2 = $conn->prepare("SELECT name, email FROM $table WHERE id = ?");
                $stmt2->bind_param("i", $userId);
                $stmt2->execute();
                $userData = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
                
                if ($userData) {
                    $validToken = true;
                    $userData['role'] = $role;
                    $userData['user_id'] = $userId;
                }
            }
        }
    } else {
        $message = 'Invalid or expired reset link. Please request a new one.';
        $messageType = 'error';
    }
    $stmt->close();
}

// Handle password reset submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['token'])) {
    $token = $_POST['token'];
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $message = 'Please fill in all fields';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Passwords do not match';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = 'Password must be at least 6 characters';
        $messageType = 'error';
    } else {
        // Validate token again (without expires_at check in query)
        $stmt = $conn->prepare("SELECT user_role, user_id, expires_at FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $tokenData = $result->fetch_assoc();
            
            // Check expiry manually
            if (strtotime($tokenData['expires_at']) < time()) {
                $message = 'This reset link has expired';
                $messageType = 'error';
            } else {
                // Update password
                if (setUserPassword($conn, $tokenData['user_role'], $tokenData['user_id'], $newPassword)) {
                    // Delete used token
                    $stmt2 = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                    $stmt2->bind_param("s", $token);
                    $stmt2->execute();
                    $stmt2->close();
                    
                    $message = 'success';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating password. Please try again.';
                    $messageType = 'error';
                }
            }
        } else {
            $message = 'Invalid or expired reset link';
            $messageType = 'error';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Tanglaw LMS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #667eea;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .user-info {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($message === 'success'): ?>
            <div class="logo">
                <h1>✅ Password Reset!</h1>
                <p>Tanglaw LMS</p>
            </div>
            <div class="message success">
                <strong>Password successfully reset!</strong><br><br>
                You can now login with your new password.
            </div>
            <div class="back-link">
                <a href="login.php">← Go to Login</a>
            </div>
        <?php elseif (!$validToken): ?>
            <div class="logo">
                <h1>❌ Invalid Link</h1>
                <p>Tanglaw LMS</p>
            </div>
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <div class="back-link">
                <a href="request_reset.php">← Request New Reset Link</a>
            </div>
        <?php else: ?>
            <div class="logo">
                <h1>🔐 Set New Password</h1>
                <p>Tanglaw LMS</p>
            </div>
            
            <?php if ($userData): ?>
                <div class="user-info">
                    <strong>Resetting password for:</strong><br>
                    <?php echo htmlspecialchars($userData['name']); ?><br>
                    <small><?php echo htmlspecialchars($userData['email']); ?></small>
                </div>
            <?php endif; ?>
            
            <?php if ($message && $messageType !== 'success'): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter new password" minlength="6">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Confirm new password" minlength="6">
                </div>
                
                <button type="submit" class="btn">
                    Reset Password
                </button>
            </form>
            
            <div class="back-link">
                <a href="login.php">← Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>