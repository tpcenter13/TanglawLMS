<?php
/**
 * TEST EMAIL SENDING
 * Quick test to verify PHPMailer and Gmail SMTP configuration
 */

$test_passed = true;
$messages = [];

// Step 1: Check Composer autoloader
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
    $messages[] = "✅ Composer autoloader found";
} else {
    $messages[] = "❌ Composer autoloader NOT found (vendor/autoload.php)";
    $test_passed = false;
}

// Step 2: Check PHPMailer class
if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
    $messages[] = "✅ PHPMailer class available";
} else {
    $messages[] = "❌ PHPMailer class NOT found";
    $test_passed = false;
}

// Step 3: Load config
if (file_exists('config_email.php')) {
    include('config_email.php');
    $messages[] = "✅ config_email.php loaded";
    
    if (defined('MAIL_FROM_EMAIL')) {
        $email = MAIL_FROM_EMAIL;
        if ($email === 'your-email@gmail.com') {
            $messages[] = "⚠️ Email NOT configured (still has default value)";
            $test_passed = false;
        } else {
            $messages[] = "✅ Email configured: " . htmlspecialchars($email);
        }
    }
} else {
    $messages[] = "❌ config_email.php NOT found";
    $test_passed = false;
}

// Step 4: Try to send test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $test_passed) {
    $test_email = trim($_POST['test_email'] ?? '');
    
    if (empty($test_email) || !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $messages[] = "❌ Invalid test email address";
    } else {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = MAIL_SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_SMTP_USERNAME;
            $mail->Password   = MAIL_SMTP_PASSWORD;
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = MAIL_SMTP_PORT;

            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            $mail->addAddress($test_email);
            $mail->isHTML(true);
            $mail->Subject = "Tanglaw LMS - Test Email";
            $mail->Body    = "<h2>✅ Email Configuration Working!</h2><p>This is a test email from Tanglaw LMS.</p>";
            $mail->AltBody = "Email Configuration Working! This is a test email from Tanglaw LMS.";

            if ($mail->send()) {
                $messages[] = "✅ TEST EMAIL SENT SUCCESSFULLY to " . htmlspecialchars($test_email);
                $messages[] = "📧 Check your inbox (or spam folder) for the test email";
            } else {
                $messages[] = "❌ Failed to send test email: " . htmlspecialchars($mail->ErrorInfo);
            }
        } catch (Exception $e) {
            $messages[] = "❌ Exception: " . htmlspecialchars($e->getMessage());
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Email Test - Tanglaw LMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #1e40af; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #dcfce7; color: #166534; border-left: 4px solid #22c55e; }
        .error { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .warning { background: #fef3c7; color: #92400e; border-left: 4px solid #eab308; }
        form { margin-top: 20px; padding: 15px; background: #f9fafb; border-radius: 4px; }
        input[type="email"] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; }
        button { background: #2563eb; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background: #1d4ed8; }
        .nav { margin-top: 20px; }
        a { color: #2563eb; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h1>📧 Email Configuration Test</h1>
    
    <h3>Configuration Status</h3>
    <?php foreach ($messages as $msg): ?>
        <?php
        $class = 'message';
        if (strpos($msg, '✅') === 0) $class .= ' success';
        elseif (strpos($msg, '❌') === 0) $class .= ' error';
        elseif (strpos($msg, '⚠️') === 0) $class .= ' warning';
        ?>
        <div class="<?= $class ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endforeach; ?>
    
    <?php if ($test_passed): ?>
        <form method="POST">
            <h3>Send Test Email</h3>
            <p>Enter an email address to test sending:</p>
            <input type="email" name="test_email" placeholder="test@example.com" required>
            <button type="submit">Send Test Email</button>
        </form>
    <?php else: ?>
        <p style="padding: 10px; background: #fee2e2; border-radius: 4px; color: #991b1b;">
            ❌ Please fix the issues above before testing email sending.
        </p>
    <?php endif; ?>
    
    <div class="nav">
        <p>
            <a href="login.php">← Back to Login</a> | 
            <a href="admin_dashboard.php">Go to Admin Dashboard →</a>
        </p>
    </div>
</div>
</body>
</html>
