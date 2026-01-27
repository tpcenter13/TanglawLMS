<?php
session_start();
include("conn.php");

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle forgot-password requests
    if (isset($_POST['action']) && $_POST['action'] === 'forgot') {
        $forgot_email = trim($_POST['forgot_email'] ?? '');
        $forgot_role = trim($_POST['forgot_role'] ?? '');
        
        if (empty($forgot_email) || empty($forgot_role)) {
            $error = '❌ Please provide your email and role for password assistance.';
        } else {
            // Validate email format
            if (!filter_var($forgot_email, FILTER_VALIDATE_EMAIL)) {
                $error = '❌ Please provide a valid email address.';
            } else {
                // Check if user exists with this email and role
                $user_exists = false;
                $user_id = null;
                
                if ($forgot_role == 'teacher') {
                    $stmt = $conn->prepare("SELECT id, email FROM teachers WHERE email = ? AND archived = 0 LIMIT 1");
                } elseif ($forgot_role == 'facilitator') {
                    $stmt = $conn->prepare("SELECT id, email FROM facilitators WHERE email = ? AND archived = 0 LIMIT 1");
                } else {
                    $stmt = $conn->prepare("SELECT id, email FROM detainees WHERE email = ? AND archived = 0 LIMIT 1");
                }
                
                if ($stmt) {
                    $stmt->bind_param("s", $forgot_email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $user_data = $result->fetch_assoc();
                        $user_exists = true;
                        $user_id = $user_data['id'];
                    }
                    $stmt->close();
                }
                
                if (!$user_exists) {
                    $error = '❌ No ' . htmlspecialchars($forgot_role) . ' account found with this email address.';
                } else {
                    // Insert password reset request
                    $insert_stmt = $conn->prepare("INSERT INTO password_reset_requests (email, role, user_id, status) VALUES (?, ?, ?, 'pending')");
                    
                    if ($insert_stmt) {
                        $insert_stmt->bind_param("ssi", $forgot_email, $forgot_role, $user_id);
                        
                        if ($insert_stmt->execute()) {
                            $success = '✅ Your password reset request has been sent to the administrator. They will contact you via email soon.';
                        } else {
                            $error = '❌ Failed to submit request. Please try again.';
                        }
                        
                        $insert_stmt->close();
                    } else {
                        $error = '❌ System error. Please contact support.';
                    }
                }
            }
        }
    }
    
    // Handle regular login
    if (!isset($_POST['action']) || $_POST['action'] !== 'forgot') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = trim($_POST['role'] ?? '');
        
        // Validate required fields
        if (empty($username) || empty($role)) {
            $error = "❌ Please fill in all required fields.";
        } elseif ($role === 'admin' && empty($password)) {
            $error = "❌ Password is required for admin login.";
        } else {
            $redirect_url = '';
            $found_user = false;
            
            // Check Admin
            if ($role == 'admin') {
                if ($username === 'admin' && $password === 'admin123') {
                    $_SESSION['loggedUser'] = [
                        'id' => 0,
                        'name' => 'Administrator',
                        'role' => 'admin'
                    ];
                    $redirect_url = 'admin_dashboard.php';
                    $found_user = true;
                } else {
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
                        if (!empty($t_hash)) {
                            if (empty($password)) {
                                $error = '❌ Password is required.';
                            } elseif (password_verify($password, $t_hash)) {
                                $_SESSION['loggedUser'] = [ 'id' => $t_id, 'name' => $t_name, 'role' => 'teacher' ];
                                $redirect_url = 'teacher_dashboard.php';
                                $found_user = true;
                            } else {
                                $error = '❌ Invalid credentials.';
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
                                $error = '❌ Password is required.';
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
                                $error = '❌ Password is required.';
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
                if (empty($error)) {
                    $error = "❌ No " . htmlspecialchars($role) . " found with that ID number.";
                }
                error_log("Login failed for role={$role}, username={$username}");
            }
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
            background: #003049;
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
        @media (max-width: 768px) {
            .login-wrapper {
                grid-template-columns: 1fr;
            }
            .login-right {
                display: none;
            }
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(4px);
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 28px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .modal-content h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #1f2937;
            font-size: 20px;
        }
        
        .modal-content .form-group {
            margin-bottom: 16px;
        }
        
        .modal-content label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        
        .modal-content input,
        .modal-content select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .modal-content input:focus,
        .modal-content select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .modal-buttons button {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .btn-cancel {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-cancel:hover {
            background: #e5e7eb;
        }
        
        .btn-submit {
            background: #2563eb;
            color: white;
        }
        
        .btn-submit:hover {
            background: #1d4ed8;
        }
        
        .forgot-link {
            text-align: center;
            margin-top: 12px;
        }
        
        .forgot-link a {
            font-size: 13px;
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
        }
        
        .forgot-link a:hover {
            text-decoration: underline;
        }
        
        .password-field-wrapper {
            position: relative;
        }
        
        .password-field-wrapper input {
            padding-right: 45px;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            user-select: none;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
        }
        
        .password-toggle:hover {
            color: #374151;
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <div class="login-right">
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; gap: 40px;">
            <h2 style="color: white; font-size: 32px; margin: 0; text-align: center; font-weight: 700;">TANGLAW LEARN</h2>
            <div class="logo-space">
                <div class="logo-placeholder">
                    <div>
                        <img src="Bulacan_Seal.png" alt="Logo 1 - Official Seal of the Province of Bulacan">
                        <span>Province of Bulacan Seal</span>
                    </div>
                </div>
                <div class="logo-placeholder">
                    <div>
                        <img src="tangllaw_logo.png" alt="Logo 2 - Tanglaw ng Masa Youth Rehabilitation Center">
                        <span>Tanglaw ng Masa Youth Rehabilitation Center</span>
                    </div>
                </div>
                <div class="logo-placeholder">
                    <div>
                        <img src="marcello_logo.png" alt="Logo 3 - Pambansang Mataas na Paaralang Marcelo H. Del Pilar">
                        <span>Pambansang Mataas na Paaralang Marcelo H. Del Pilar</span>
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
    
    <div class="login-left">
        <div class="login-container">
            <div class="login-card">
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                <?php endif; ?>
                
                <h1>🎓 Tanglaw Learn</h1>
                
                <form method="POST" style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="form-group">
                        <label for="role">Login as</label>
                        <select id="role" name="role" required onchange="updateInfo()">
                            <option value="">Select User Type</option>
                            <option value="admin">👨‍💼 Admin</option>
                            <option value="teacher">👨‍🏫 Teacher</option>
                            <option value="facilitator">👥 Facilitator</option>
                            <option value="detainee">👨‍🎓 Student</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">ID Number or Username</label>
                        <input type="text" id="username" name="username" placeholder="Enter your ID number" required>
                    </div>

                    <div class="form-group password-field-wrapper" id="password-group" style="display: none;">
                        <label for="password">Password <span style="color: #ef4444;">*</span></label>
                        <input type="password" id="password" name="password" placeholder="Enter your password">
                        <span class="password-toggle" onclick="togglePassword()">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </span>
                    </div>
                    
                    <button type="submit" class="login-btn">Login</button>
                    
                    <div class="forgot-link">
                        <a href="#" onclick="openForgotModal(); return false;">Forgot password?</a>
                    </div>
                </form>
                
                <div class="login-footer"></div>
            </div>
        </div>
    </div>
</div>

<!-- Forgot Password Modal -->
<div id="forgotModal" class="modal">
    <div class="modal-content">
        <h3>🔐 Forgot Password</h3>
        <form method="POST" id="forgotForm">
            <input type="hidden" name="action" value="forgot">
            
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
                <input type="email" name="forgot_email" id="forgot_email" placeholder="your.email@example.com" required>
            </div>
            
            <p style="font-size: 13px; color: #6b7280; margin-top: 12px;">
                ℹ️ Your request will be sent to the administrator who will help you reset your password.
            </p>
            
            <div class="modal-buttons">
                <button type="button" class="btn-cancel" onclick="closeForgotModal()">Cancel</button>
                <button type="submit" class="btn-submit">Send Request</button>
            </div>
        </form>
    </div>
</div>

<script>
function updateInfo() {
    const role = document.getElementById('role').value;
    const passwordGroup = document.getElementById('password-group');
    const passwordInput = document.getElementById('password');

    if (role === '') {
        passwordGroup.style.display = 'none';
        passwordInput.value = '';
    } else {
        passwordGroup.style.display = 'block';
    }
}

function togglePassword() {
    const pwd = document.getElementById("password");
    const icon = document.getElementById("eyeIcon");

    if (pwd.type === "password") {
        pwd.type = "text";
        icon.innerHTML = `
            <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7
                     a21.8 21.8 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 5
                     c7 0 11 7 11 7a21.8 21.8 0 0 1-5.06 5.94M15 12
                     a3 3 0 1 1-3-3"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
        `;
    } else {
        pwd.type = "password";
        icon.innerHTML = `
            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
            <circle cx="12" cy="12" r="3"/>
        `;
    }
}

function openForgotModal() {
    document.getElementById('forgotModal').classList.add('show');
}

function closeForgotModal() {
    document.getElementById('forgotModal').classList.remove('show');
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('forgotModal');
    if (event.target === modal) {
        closeForgotModal();
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeForgotModal();
    }
});
</script>

</body>
</html>