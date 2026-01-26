<?php
session_start();
require_once 'conn.php';
require_once 'admin_functions_users.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        $message = 'Please enter your email address';
        $messageType = 'error';
    } else {
        // Search for user in all three tables
        $user = null;
        $role = null;
        
        // Check teachers
        $stmt = $conn->prepare("SELECT id, name, email FROM teachers WHERE email = ? AND archived = 0");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $role = 'teacher';
        }
        $stmt->close();
        
        // Check facilitators if not found
        if (!$user) {
            $stmt = $conn->prepare("SELECT id, name, email FROM facilitators WHERE email = ? AND archived = 0");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $role = 'facilitator';
            }
            $stmt->close();
        }
        
        // Check detainees if not found
        if (!$user) {
            $stmt = $conn->prepare("SELECT id, name, email FROM detainees WHERE email = ? AND archived = 0");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $role = 'detainee';
            }
            $stmt->close();
        }
        
        if ($user) {
            // Generate reset token
            $token = createPasswordResetToken($conn, $role, $user['id']);
            
            if ($token) {
                // Store token in session for EmailJS to send
                $_SESSION['reset_token'] = $token;
                $_SESSION['reset_email'] = $user['email'];
                $_SESSION['reset_name'] = $user['name'];
                
                $message = 'success'; // Signal to trigger EmailJS
                $messageType = 'success';
            } else {
                $message = 'Error generating reset token. Please try again.';
                $messageType = 'error';
            }
        } else {
            // Don't reveal if email exists or not for security
            $message = 'If an account exists with that email, a reset link will be sent.';
            $messageType = 'info';
        }
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
        
        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        
        input[type="email"]:focus {
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
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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
        
        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
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
        
        .loading {
            display: none;
            text-align: center;
            margin-top: 10px;
            color: #667eea;
            font-size: 14px;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 10px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <!-- EmailJS SDK -->
    <script src="https://cdn.jsdelivr.net/npm/@emailjs/browser@3/dist/email.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="logo">
            <h1>🔐 Reset Password</h1>
            <p>Tanglaw LMS</p>
        </div>
        
        <?php if ($message && $messageType !== 'success'): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <form id="resetForm" method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       placeholder="Enter your registered email">
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                Send Reset Link
            </button>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                Sending email...
            </div>
        </form>
        
        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>

    <script>
        // IMPORTANT: Replace these with your actual EmailJS credentials
       const EMAILJS_PUBLIC_KEY = "bjKEcCXpriGPTWoIB";
        const EMAILJS_SERVICE_ID = "service_4viy27k";
        const EMAILJS_TEMPLATE_ID = "template_1axzswl";
        
        // Initialize EmailJS
        emailjs.init(EMAILJS_PUBLIC_KEY);
        
        <?php if ($message === 'success'): ?>
        // Send email using EmailJS
        document.addEventListener('DOMContentLoaded', function() {
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loading');
            const form = document.getElementById('resetForm');
            
            submitBtn.disabled = true;
            form.style.display = 'none';
            loading.style.display = 'block';
            
            // Build reset link
            const resetLink = window.location.origin + 
                            window.location.pathname.replace('request_reset.php', 'reset_password.php') + 
                            '?token=<?php echo $_SESSION['reset_token']; ?>';
            
            // Template parameters matching your EmailJS template
            const templateParams = {
                to_email: '<?php echo addslashes($_SESSION['reset_email']); ?>',
                name: '<?php echo addslashes($_SESSION['reset_name']); ?>',
                message: resetLink,
                email: '<?php echo addslashes($_SESSION['reset_email']); ?>',
                year: new Date().getFullYear()
            };
            
            console.log('Sending email with params:', templateParams);
            
            emailjs.send(EMAILJS_SERVICE_ID, EMAILJS_TEMPLATE_ID, templateParams)
                .then(function(response) {
                    console.log('SUCCESS!', response.status, response.text);
                    document.querySelector('.container').innerHTML = `
                        <div class="logo">
                            <h1>✅ Email Sent!</h1>
                            <p>Tanglaw LMS</p>
                        </div>
                        <div class="message success">
                            <strong>Password reset link sent!</strong><br><br>
                            A reset link has been sent to your email address. 
                            Please check your inbox and follow the instructions.<br><br>
                            <small>The link will expire in 1 hour.</small>
                        </div>
                        <div class="back-link">
                            <a href="login.php">← Back to Login</a>
                        </div>
                    `;
                }, function(error) {
                    console.log('FAILED...', error);
                    document.querySelector('.container').innerHTML = `
                        <div class="logo">
                            <h1>❌ Error</h1>
                            <p>Tanglaw LMS</p>
                        </div>
                        <div class="message error">
                            <strong>Failed to send email</strong><br><br>
                            Error: ${error.text || 'Unknown error'}<br><br>
                            Please try again or contact support.
                        </div>
                        <div class="back-link">
                            <a href="request_reset.php">← Try Again</a>
                        </div>
                    `;
                });
        });
        <?php 
        // Clear session variables after use
        unset($_SESSION['reset_token']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_name']);
        endif; 
        ?>
    </script>
</body>
</html>