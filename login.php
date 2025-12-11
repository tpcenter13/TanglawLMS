<?php
session_start();
$host = "localhost";        
$user = "root";           
$password = "";            
$database = "tanglaw_lms";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8");

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle forgot-password requests
    if (isset($_POST['action']) && $_POST['action'] === 'forgot') {
        $forgot_email = trim($_POST['forgot_email'] ?? '');
        $forgot_role = trim($_POST['forgot_role'] ?? '');
        if (empty($forgot_email) || empty($forgot_role)) {
            $error = 'Please provide your email and role for password assistance.';
        } else {
            // Send notification to admin email configured in config_email.php (fallback to no-reply)
            if (file_exists('config_email.php')) {
                include 'config_email.php';
                $adminEmail = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'no-reply@localhost';
            } else {
                $adminEmail = 'no-reply@localhost';
            }
            $subject = "Tanglaw LMS - Password Assistance Request";
            $body = "A user has requested password assistance.<br><br>";
            $body .= "Email: " . htmlspecialchars($forgot_email) . "<br>";
            $body .= "Role: " . htmlspecialchars($forgot_role) . "<br>";
            $body .= "Please contact the user with their account details.<br>";
            $headers  = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
            $headers .= "From: Tanglaw LMS <" . $adminEmail . ">" . "\r\n";
            @mail($adminEmail, $subject, $body, $headers);
            $success = '✅ Your request has been sent to the administrator. They will contact you via email.';
        }
    }
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? '');
    
    // Validate required fields
    if (empty($username) || empty($role)) {
        $error = "Please fill in all required fields.";
    } elseif ($role === 'admin' && empty($password)) {
        $error = "Password is required for admin login.";
    } else {
        $redirect_url = '';
        $found_user = false;
        
        // Check Admin
        if ($role == 'admin') {
            // For demo: admin / admin123
            if ($username === 'admin' && $password === 'admin123') {
                $_SESSION['loggedUser'] = [
                    'id' => 0,
                    'name' => 'Administrator',
                    'role' => 'admin'
                ];
                $redirect_url = 'admin_dashboard.php';
                $found_user = true;
            } else {
                // Invalid admin credentials
                $error = "❌ Invalid admin credentials.";
            }
        }
        
        // Check Teacher
        elseif ($role == 'teacher') {
            $stmt = $conn->prepare("SELECT id, name, password FROM teachers WHERE id_number = ? AND archived = 0 LIMIT 1");
            if (!$stmt) {
                $error = "❌ Database error: " . $conn->error;
            } else {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->bind_result($t_id, $t_name, $t_hash);
                if ($stmt->fetch()) {
                    // If password hash exists, verify. Otherwise allow legacy login without password.
                    if (!empty($t_hash)) {
                        if (empty($password)) {
                            $error = 'Password is required.';
                        } elseif (password_verify($password, $t_hash)) {
                            $_SESSION['loggedUser'] = [ 'id' => $t_id, 'name' => $t_name, 'role' => 'teacher' ];
                            $redirect_url = 'teacher_dashboard.php';
                            $found_user = true;
                        } else {
                            $error = '❌ Invalid credentials.';
                        }
                    } else {
                        // legacy: no password set yet
                        $_SESSION['loggedUser'] = [ 'id' => $t_id, 'name' => $t_name, 'role' => 'teacher' ];
                        $redirect_url = 'teacher_dashboard.php';
                        $found_user = true;
                    }
                }
                $stmt->close();
            }
        }
        
        // Check Facilitator
        elseif ($role == 'facilitator') {
            $stmt = $conn->prepare("SELECT id, name, password FROM facilitators WHERE id_number = ? AND archived = 0 LIMIT 1");
            if (!$stmt) {
                $error = "❌ Database error: " . $conn->error;
            } else {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->bind_result($f_id, $f_name, $f_hash);
                if ($stmt->fetch()) {
                    if (!empty($f_hash)) {
                        if (empty($password)) {
                            $error = 'Password is required.';
                        } elseif (password_verify($password, $f_hash)) {
                            $_SESSION['loggedUser'] = [ 'id' => $f_id, 'name' => $f_name, 'role' => 'facilitator' ];
                            $redirect_url = 'facilitator_dashboard.php';
                            $found_user = true;
                        } else {
                            $error = '❌ Invalid credentials.';
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
        
        // Check Detainee
        elseif ($role == 'detainee') {
            // Try to resolve grade_level_id by joining grade_levels (if mapping exists)
            $stmt = $conn->prepare("SELECT d.id, d.name, d.grade_level, gl.id AS grade_level_id, d.password
                FROM detainees d
                LEFT JOIN grade_levels gl ON gl.level = d.grade_level
                WHERE d.id_number = ? AND d.archived = 0 LIMIT 1");
            if (!$stmt) {
                $error = "❌ Database error: " . $conn->error;
            } else {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->bind_result($d_id, $d_name, $d_grade_level, $d_grade_level_id, $d_hash);
                if ($stmt->fetch()) {
                    if (!empty($d_hash)) {
                        if (empty($password)) {
                            $error = 'Password is required.';
                        } elseif (password_verify($password, $d_hash)) {
                            $_SESSION['loggedUser'] = [
                                'id' => $d_id,
                                'name' => $d_name,
                                'grade_level' => $d_grade_level,
                                'grade_level_id' => $d_grade_level_id ? (int)$d_grade_level_id : null,
                                'role' => 'detainee'
                            ];
                            $redirect_url = 'student_dashboard.php';
                            $found_user = true;
                        } else {
                            $error = '❌ Invalid credentials.';
                        }
                    } else {
                        // legacy: allow login without password
                        $_SESSION['loggedUser'] = [
                            'id' => $d_id,
                            'name' => $d_name,
                            'grade_level' => $d_grade_level,
                            'grade_level_id' => $d_grade_level_id ? (int)$d_grade_level_id : null,
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
            $success = "✅ Login successful! Redirecting...";
            header("refresh:1.5;url=$redirect_url");
        } else {
            // If a more specific error was set (e.g. DB prepare error), keep it.
            if (empty($error)) {
                // Provide role-specific not-found message
                $error = "❌ No " . htmlspecialchars($role) . " found with that ID number.";
            }
            // Log the failed attempt for debugging (no sensitive data)
            error_log("Login failed for role={$role}, username={$username}");
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
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        .login-wrapper {
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 100vh;
            background: #ffffff;
        }
        .login-left {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%);
        }
        .login-right {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
        }
        .logo-space {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            max-width: 400px;
            width: 100%;
        }
        .logo-placeholder {
            background: rgba(255, 255, 255, 0.1);
            border: 2px dashed rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            aspect-ratio: 1;
            color: rgba(255, 255, 255, 0.5);
            font-size: 12px;
            text-align: center;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .logo-placeholder:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.5);
        }
        .logo-placeholder img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        .login-card {
            background: var(--card);
            border: 1px solid #e6eef8;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(14,30,37,0.1);
        }
        .login-card h1 {
            text-align: center;
            color: var(--accent);
            margin-top: 0;
            margin-bottom: 24px;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            font-family: inherit;
        }
        .form-group input::placeholder, .form-group select::placeholder {
            color: #9ca3af;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .login-btn {
            width: 100%;
            padding: 10px;
            background: var(--accent);
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }
        .login-btn:hover {
            background: #1d4ed8;
        }
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .login-footer {
            text-align: center;
            margin-top: 16px;
            font-size: 12px;
            color: var(--muted);
        }
        .role-info {
            background: #f0f9ff;
            border: 1px solid #bfdbfe;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 12px;
            line-height: 1.5;
        }
        @media (max-width: 768px) {
            .login-wrapper {
                grid-template-columns: 1fr;
            }
            .login-right {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="login-wrapper">
    <!-- LEFT COLUMN: CLIENT LOGOS SPACE -->
    <div class="login-right">
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; gap: 40px;">
            <h2 style="color: white; font-size: 32px; margin: 0; text-align: center; font-weight: 700;">TANGLAW LEARN</h2>
            <div class="logo-space">
                <div class="logo-placeholder">
                    <div>
                        <img src="" alt="Logo 1" style="display:none;">
                        <span>Logo 1</span>
                    </div>
                </div>
                <div class="logo-placeholder">
                    <div>
                        <img src="" alt="Logo 2" style="display:none;">
                        <span>Logo 2</span>
                    </div>
                </div>
                <div class="logo-placeholder">
                    <div>
                        <img src="" alt="Logo 3" style="display:none;">
                        <span>Logo 3</span>
                    </div>
                </div>
                <div class="logo-placeholder">
                    <div>
                        <img src="" alt="Logo 4" style="display:none;">
                        <span>Logo 4</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- RIGHT COLUMN: LOGIN FORM -->
    <div class="login-left">
        <div class="login-container">
            <div class="login-card">
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>
                
                <h1>🎓 Tanglaw LMS</h1>
                
                <form method="POST" style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="form-group">
                        <label for="role">Login as</label>
                        <select id="role" name="role" required onchange="updateInfo()">
                            <option value="">Select User Type</option>
                            <option value="admin">🛡️ Admin</option>
                            <option value="teacher">👨‍🏫 Teacher</option>
                            <option value="facilitator">👥 Facilitator</option>
                            <option value="detainee">👨‍🎓 Detainee</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">ID Number or Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter your ID number" required>
                    </div>
                    
                    <div class="form-group" id="password-group" style="display: none; flex-direction: column;">
                        <label for="password">Password <span style="color: #ef4444;">*</span></label>
                        <input type="password" id="password" name="password" placeholder="Enter your password">
                    </div>
                    
                    <!-- role-help removed per request -->
                    
                    <button type="submit" class="login-btn">Login</button>
                    <div style="text-align:center; margin-top:8px;">
                        <a href="#" onclick="openForgot(); return false;" style="font-size:13px; color:#2563eb;">Forgot password?</a>
                    </div>
                </form>
                
                <div class="login-footer"></div>
            </div>
        </div>
    </div>
</div>

<script>
function updateInfo() {
    const role = document.getElementById('role').value;
    const passwordGroup = document.getElementById('password-group');
    const passwordInput = document.getElementById('password');

    // Show password field for all roles (admin, teacher, facilitator, detainee)
    if (role === '') {
        passwordGroup.style.display = 'none';
        passwordInput.value = '';
    } else {
        passwordGroup.style.display = 'flex';
    }
}
</script>

<!-- Forgot password modal -->
<div id="forgotModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); align-items:center; justify-content:center; z-index:9999;"> 
    <div style="background:white; padding:20px; border-radius:8px; width:320px;">
        <h3 style="margin-top:0">Forgot Password</h3>
        <form method="POST" onsubmit="return submitForgot(this);">
            <input type="hidden" name="action" value="forgot">
            <div style="margin-bottom:8px;"><label>Role</label><br>
                <select name="forgot_role" required>
                    <option value="teacher">Teacher</option>
                    <option value="facilitator">Facilitator</option>
                    <option value="detainee">Detainee</option>
                </select>
            </div>
            <div style="margin-bottom:8px;"><label>Email</label><br>
                <input type="email" name="forgot_email" required style="width:100%; padding:8px;">
            </div>
            <div style="display:flex; gap:8px; justify-content:flex-end;">
                <button type="button" onclick="closeForgot()" style="background:#eee;padding:8px;border-radius:6px;border:0;">Cancel</button>
                <button type="submit" style="background:#2563eb;color:white;padding:8px;border-radius:6px;border:0;">Send</button>
            </div>
        </form>
    </div>
</div>

<script>
function openForgot() { document.getElementById('forgotModal').style.display = 'flex'; }
function closeForgot(){ document.getElementById('forgotModal').style.display = 'none'; }
function submitForgot(form){
    // simple client-side UX
    var btn = form.querySelector('button[type=submit]');
    btn.disabled = true; btn.innerText = 'Sending...';
    return true; // allow normal submit
}
</script>

</body>
</html>

