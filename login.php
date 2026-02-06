<?php
// ============================================
// INLINE PASSWORD RESET HANDLER
// ============================================
if (isset($_POST['ajax_reset']) && $_POST['ajax_reset'] === '1') {
    ob_start();
    header('Content-Type: application/json');
    
    // Database connection - update these with your credentials
    $db_host = 'localhost';
    $db_user = 'root';           // ← UPDATE THIS
    $db_pass = '';               // ← UPDATE THIS
    $db_name = 'tanglaw_lms';    // ← UPDATE THIS
    
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        if ($conn->connect_error) {
            throw new Exception('Database connection failed');
        }
        
        $email = trim($_POST['email'] ?? '');
        $role = trim($_POST['role'] ?? '');
        
        // Validate
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address');
        }
        
        if (!in_array($role, ['teacher', 'facilitator', 'detainee'])) {
            throw new Exception('Invalid role');
        }
        
        // Find user
        $table = $role . 's';
        $stmt = $conn->prepare("SELECT id, name, email FROM $table WHERE email = ? AND archived = 0 LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'send_email' => false,
                'message' => 'If an account exists, a reset link has been sent.'
            ]);
            exit;
        }
        
        $user = $result->fetch_assoc();
        
        // Create password_resets table if not exists
        $conn->query("
            CREATE TABLE IF NOT EXISTS password_resets (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_role VARCHAR(20) NOT NULL,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL UNIQUE,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Delete old tokens for this user
        $del_stmt = $conn->prepare("DELETE FROM password_resets WHERE user_role = ? AND user_id = ?");
        $del_stmt->bind_param("si", $role, $user['id']);
        $del_stmt->execute();
        
        // Insert new token
        $ins_stmt = $conn->prepare("INSERT INTO password_resets (user_role, user_id, token, expires_at) VALUES (?, ?, ?, ?)");
        $ins_stmt->bind_param("siss", $role, $user['id'], $token, $expires_at);
        $ins_stmt->execute();
        
        // Build reset link - use simple approach
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // Get current page URL and replace login.php with reset_password.php
        $current_url = $_SERVER['PHP_SELF'];
        $base_path = str_replace('\\', '/', dirname($current_url));
        
        // Ensure proper path format
        if ($base_path === '/' || $base_path === '.') {
            $reset_link = $protocol . '://' . $host . '/reset_password.php?token=' . $token;
        } else {
            // Remove trailing slash and add our file
            $base_path = rtrim($base_path, '/');
            $reset_link = $protocol . '://' . $host . $base_path . '/reset_password.php?token=' . $token;
        }
        
        // Extra safety: replace any remaining backslashes
        $reset_link = str_replace('\\', '/', $reset_link);
        
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'send_email' => true,
            'email' => $user['email'],
            'name' => $user['name'],
            'reset_link' => $reset_link,
            'debug' => [
                'protocol' => $protocol,
                'host' => $host,
                'base_path' => $base_path,
                'current_url' => $current_url,
                'final_link' => $reset_link
            ]
        ]);
        exit;
        
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// ============================================
// NORMAL LOGIN HANDLING
// ============================================
session_start();
include("conn.php");

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && (!isset($_POST['action']) || $_POST['action'] !== 'forgot')) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = "❌ Please fill in all required fields.";
    } else {
        $redirect_url = '';
        $found_user = false;
        
        // Check if admin
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['loggedUser'] = [
                'id' => 0,
                'name' => 'Administrator',
                'role' => 'admin'
            ];
            $redirect_url = 'admin_dashboard.php';
            $found_user = true;
        }
        
        // Check if teacher
        if (!$found_user) {
            $stmt = $conn->prepare("SELECT id, name, password FROM teachers WHERE id_number = ? AND archived = 0 LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->bind_result($t_id, $t_name, $t_hash);
                if ($stmt->fetch()) {
                    if (!empty($t_hash)) {
                        if (password_verify($password, $t_hash)) {
                            $_SESSION['loggedUser'] = [ 'id' => $t_id, 'name' => $t_name, 'role' => 'teacher' ];
                            $redirect_url = 'teacher_dashboard.php';
                            $found_user = true;
                        }
                    } else {
                        $_SESSION['loggedUser'] = [ 'id' => $t_id, 'name' => $t_name, 'role' => 'teacher' ];
                        $redirect_url = 'teacher_dashboard.php';
                        $found_user = true;
                    }
                }
                $stmt->close();
            }
        }
        
        // Check if facilitator
        if (!$found_user) {
            $stmt = $conn->prepare("SELECT id, name, password FROM facilitators WHERE id_number = ? AND archived = 0 LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->bind_result($f_id, $f_name, $f_hash);
                if ($stmt->fetch()) {
                    if (!empty($f_hash)) {
                        if (password_verify($password, $f_hash)) {
                            $_SESSION['loggedUser'] = [ 'id' => $f_id, 'name' => $f_name, 'role' => 'facilitator' ];
                            $redirect_url = 'facilitator_dashboard.php';
                            $found_user = true;
                        }
                    } else {
                        $_SESSION['loggedUser'] = [ 'id' => $f_id, 'name' => $f_name, 'role' => 'facilitator' ];
                        $redirect_url = 'facilitator_dashboard.php';
                        $found_user = true;
                    }
                }
                $stmt->close();
            }
        }
        
        // Check if detainee
        if (!$found_user) {
            $stmt = $conn->prepare("SELECT d.id, d.name, d.grade_level, d.school, gl.id AS grade_level_id, d.password
                FROM detainees d
                LEFT JOIN grade_levels gl ON gl.level = d.grade_level
                WHERE d.id_number = ? AND d.archived = 0 LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->bind_result($d_id, $d_name, $d_grade_level, $d_school, $d_grade_level_id, $d_hash);
                if ($stmt->fetch()) {
                    if (!empty($d_hash)) {
                        if (password_verify($password, $d_hash)) {
                            $_SESSION['loggedUser'] = [
                                'id' => $d_id,
                                'name' => $d_name,
                                'grade_level' => $d_grade_level,
                                'grade_level_id' => $d_grade_level_id ? (int)$d_grade_level_id : null,
                                'school' => $d_school,
                                'role' => 'detainee'
                            ];
                            $redirect_url = 'student_dashboard.php';
                            $found_user = true;
                        }
                    } else {
                        $_SESSION['loggedUser'] = [
                            'id' => $d_id,
                            'name' => $d_name,
                            'grade_level' => $d_grade_level,
                            'grade_level_id' => $d_grade_level_id ? (int)$d_grade_level_id : null,
                            'school' => $d_school,
                            'role' => 'detainee'
                        ];
                        $redirect_url = 'student_dashboard.php';
                        $found_user = true;
                    }
                }
                $stmt->close();
            }
        }
        
        if ($found_user) {
            $success = "Login successful! Redirecting...";
            header("refresh:1.5;url=$redirect_url");
        } else {
            $error = "❌ Invalid username or password.";
            error_log("Login failed for username={$username}");
        }
    }
}

$conn->close();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Login - Tanglaw LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- EmailJS SDK -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: backgroundMove 20s linear infinite;
        }
        
        @keyframes backgroundMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        .login-container {
            width: 100%;
            max-width: 1200px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: white;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            position: relative;
            z-index: 1;
        }
        
        .login-left {
            padding: 60px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .logo-section {
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .app-title {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 16px;
            background: linear-gradient(135deg, #ffffff 0%, #e0e7ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -1px;
        }
        
        .app-subtitle {
            font-size: 18px;
            opacity: 0.9;
            font-weight: 500;
            margin-bottom: 40px;
        }
        
        .partner-logos {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 40px;
            width: 100%;
            max-width: 420px;
        }
        
        .partner-logo {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            aspect-ratio: 1;
        }
        
        .partner-logo:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.4);
            transform: translateY(-5px);
        }
        
        .partner-logo img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .login-right {
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-header {
            margin-bottom: 40px;
        }
        
        .login-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .login-header p {
            font-size: 16px;
            color: #64748b;
        }
        
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }
        
        .form-group label .required {
            color: #ef4444;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: #f8fafc;
        }
        
        .form-group input.with-icon {
            padding-left: 48px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .form-group select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 20px;
            padding-right: 48px;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.2s;
            padding: 8px;
        }
        
        .password-toggle:hover {
            color: #475569;
        }
        
        .login-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.5);
        }
        
        .login-btn:active {
            transform: translateY(0);
        }
        
        .login-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .forgot-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .forgot-link a {
            font-size: 14px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }
        
        .forgot-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(8px);
            padding: 20px;
        }
        
        .modal.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: white;
            padding: 40px;
            border-radius: 20px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalSlide 0.3s ease;
        }
        
        @keyframes modalSlide {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-content h3 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .modal-content .subtitle {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 32px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 12px;
            margin-top: 32px;
        }
        
        .modal-buttons button {
            flex: 1;
            padding: 14px;
            border-radius: 12px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        
        .btn-cancel {
            background: #f1f5f9;
            color: #475569;
        }
        
        .btn-cancel:hover {
            background: #e2e8f0;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.5);
        }
        
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .info-box {
            background: #f0f9ff;
            border: 2px solid #bae6fd;
            border-radius: 12px;
            padding: 16px;
            margin-top: 20px;
            display: flex;
            gap: 12px;
            font-size: 13px;
            color: #0c4a6e;
        }
        
        @media (max-width: 968px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }
            
            .login-left {
                display: none;
            }
            
            .login-right {
                padding: 40px 30px;
            }
        }
        
        @media (max-width: 480px) {
            .login-right {
                padding: 30px 20px;
            }
            
            .login-header h1 {
                font-size: 24px;
            }
            
            .modal-content {
                padding: 30px 24px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-left">
        <div class="logo-section">
            <h1 class="app-title">TANGLAW LEARN</h1>
            <p class="app-subtitle">Youth Rehabilitation Center Learning Management System</p>
            
            <div class="partner-logos">
                <div class="partner-logo">
                    <img src="Bulacan_Seal.png" alt="Province of Bulacan">
                </div>
                <div class="partner-logo">
                    <img src="tangllaw_logo.png" alt="Tanglaw ng Masa">
                </div>
                <div class="partner-logo">
                    <img src="marcello_logo.png" alt="Marcelo H. Del Pilar">
                </div>
            </div>
        </div>
    </div>
    
    <div class="login-right">
        <div class="login-header">
            <h1>Welcome Back! 👋</h1>
            <p>Please sign in to your account to continue</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <span>❌</span>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <span><?= $success ?></span>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username / ID Number <span class="required">*</span></label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </span>
                    <input type="text" id="username" name="username" class="with-icon" placeholder="Enter your username or ID number" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password <span class="required">*</span></label>
                <div class="password-wrapper">
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                            </svg>
                        </span>
                        <input type="password" id="password" name="password" class="with-icon" placeholder="Enter your password" required>
                    </div>
                    <span class="password-toggle" onclick="togglePassword()">
                        <svg id="eyeIcon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </span>
                </div>
            </div>
            
            <button type="submit" class="login-btn">
                <span>Sign In</span>
            </button>
            
            <div class="forgot-link">
                <a href="#" onclick="openForgotModal(); return false;">Forgot your password?</a>
            </div>
        </form>
    </div>
</div>

<!-- Forgot Password Modal -->
<div id="forgotModal" class="modal">
    <div class="modal-content">
        <h3>🔐 Reset Password</h3>
        <p class="subtitle">Enter your details to receive a password reset link</p>
        
        <form id="forgotForm" onsubmit="handleForgotPassword(event)">
            <div class="form-group">
                <label for="forgot_role">Your Role</label>
                <select name="forgot_role" id="forgot_role" required>
                    <option value="">Select your role</option>
                    <option value="teacher">👨‍🏫 Teacher</option>
                    <option value="facilitator">👥 Facilitator</option>
                    <option value="detainee">👨‍🎓 Student</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="forgot_email">Email Address</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                            <polyline points="22,6 12,13 2,6"/>
                        </svg>
                    </span>
                    <input type="email" name="forgot_email" id="forgot_email" class="with-icon" placeholder="your.email@example.com" required>
                </div>
            </div>
            
            <div class="info-box">
                <span>ℹ️</span>
                <p>A password reset link will be sent to your email address. The link will expire in 1 hour.</p>
            </div>
            
            <div id="resetStatusMessage" style="display: none; margin-top: 15px;"></div>
            
            <div class="modal-buttons">
                <button type="button" class="btn-cancel" onclick="closeForgotModal()">Cancel</button>
                <button type="submit" class="btn-submit" id="forgotSubmitBtn">Send Reset Link</button>
            </div>
        </form>
    </div>
</div>

<script>
// EmailJS Configuration
const EMAILJS_PUBLIC_KEY = "bjKEcCXpriGPTWoIB";
const EMAILJS_SERVICE_ID = "service_4viy27k";
const EMAILJS_TEMPLATE_ID = "template_1axzswl";

// Initialize EmailJS
emailjs.init(EMAILJS_PUBLIC_KEY);

function togglePassword() {
    const pwd = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");

    if (pwd.type === "password") {
        pwd.type = "text";
        icon.innerHTML = `
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/>
            <line x1="1" y1="1" x2="23" y2="23"/>
        `;
    } else {
        pwd.type = "password";
        icon.innerHTML = `
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
            <circle cx="12" cy="12" r="3"/>
        `;
    }
}

function openForgotModal() {
    document.getElementById('forgotModal').classList.add('show');
}

function closeForgotModal() {
    document.getElementById('forgotModal').classList.remove('show');
    // Reset form
    document.getElementById('forgotForm').reset();
    document.getElementById('resetStatusMessage').style.display = 'none';
}

function showMessage(message, type) {
    const statusDiv = document.getElementById('resetStatusMessage');
    statusDiv.className = 'alert alert-' + type;
    statusDiv.innerHTML = '<span>' + message + '</span>';
    statusDiv.style.display = 'block';
}

async function handleForgotPassword(event) {
    event.preventDefault();
    
    const submitBtn = document.getElementById('forgotSubmitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Disable button and show loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ Processing...';
    
    const formData = new FormData(event.target);
    const email = formData.get('forgot_email');
    const role = formData.get('forgot_role');
    
    console.log('🔄 Starting password reset for:', email, 'Role:', role);
    
    try {
        // Step 1: Request reset token from server (using same login.php file)
        console.log('📡 Sending request to server...');
        
        const response = await fetch(window.location.pathname, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ajax_reset=1&email=${encodeURIComponent(email)}&role=${encodeURIComponent(role)}`
        });
        
        console.log('📥 Response status:', response.status);
        console.log('📥 Response content-type:', response.headers.get('content-type'));
        
        // Check if response is actually JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('❌ Server returned non-JSON response:', text.substring(0, 200));
            showMessage('❌ Server error. Please check console and contact administrator.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            return;
        }
        
        const data = await response.json();
        console.log('📦 Server response:', data);
        
        if (!data.success) {
            showMessage('❌ ' + data.message, 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            return;
        }
        
        // Step 2: If email should be sent
        if (data.send_email) {
            submitBtn.innerHTML = '📧 Sending email...';
            console.log('📧 Sending email to:', data.email);
            console.log('🔗 Reset link:', data.reset_link);
            
            if (data.debug) {
                console.log('🐛 Debug info:', data.debug);
            }
            
            const templateParams = {
                to_email: data.email,
                name: data.name,
                message: data.reset_link,
                email: data.email,
                year: new Date().getFullYear()
            };
            
            console.log('📨 EmailJS params:', templateParams);
            
            // Send via EmailJS
            const emailResponse = await emailjs.send(EMAILJS_SERVICE_ID, EMAILJS_TEMPLATE_ID, templateParams);
            console.log('✅ Email sent successfully:', emailResponse);
            
            showMessage('✅ Password reset link sent successfully! Please check your email.', 'success');
            
            // Close modal after 3 seconds
            setTimeout(() => {
                closeForgotModal();
            }, 3000);
        } else {
            showMessage('ℹ️ ' + data.message, 'info');
        }
        
    } catch (error) {
        console.error('❌ Error details:', error);
        console.error('Error name:', error.name);
        console.error('Error message:', error.message);
        
        if (error.name === 'SyntaxError') {
            showMessage('❌ Server configuration error. Please contact administrator.', 'error');
        } else {
            showMessage('❌ Failed to send reset email. Please try again.', 'error');
        }
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('forgotModal');
    if (event.target === modal) {
        closeForgotModal();
    }
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeForgotModal();
    }
});
</script>

</body>
</html>