<?php
/**
 * COMPLETE SETUP GUIDE
 * Step-by-step instructions for admin account creation with email notifications
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Tanglaw LMS - Setup Guide</title>
    <style>
        * { margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        h1 {
            color: #667eea;
            margin-bottom: 30px;
            text-align: center;
        }
        h2 {
            color: #764ba2;
            margin-top: 30px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            padding-left: 15px;
        }
        .step {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .step-title {
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }
        code {
            background: #2d3748;
            color: #68d391;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .code-block {
            background: #2d3748;
            color: #68d391;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .link-btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            margin: 10px 5px 10px 0;
            font-weight: bold;
        }
        .link-btn:hover {
            background: #764ba2;
        }
        .warning {
            background: #fff5e6;
            border-left: 4px solid #ff9800;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            color: #e65100;
        }
        .success {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            color: #2e7d32;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table td, table th {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        table th {
            background: #667eea;
            color: white;
        }
        table tr:nth-child(even) {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🎓 Tanglaw LMS - Complete Setup Guide</h1>

    <h2>📋 Overview</h2>
    <p>When an admin creates a user account (Teacher, Facilitator, or Detainee), the system will:</p>
    <ol style="margin-left: 20px; margin-top: 10px;">
        <li>Save the account to the database with a unique ID Number</li>
        <li>Send an email to the user with their login credentials</li>
        <li>User receives email and uses the ID Number to login</li>
    </ol>

    <h2>🔧 Step 1: Add Email Columns to Database</h2>
    <div class="step">
        <div class="step-title">Run the migration script:</div>
        <p>Open this URL in your browser:</p>
        <p><a href="migrate_add_email_columns.php" class="link-btn">🔄 Run Migration</a></p>
        <p style="margin-top: 10px; font-size: 12px; color: #666;">This adds the email column to teachers, facilitators, and detainees tables.</p>
    </div>

    <h2>📧 Step 2: Configure Gmail SMTP</h2>
    <div class="step">
        <div class="step-title">Edit config_email.php:</div>
        <p>You need to add your Gmail account and App Password.</p>
        
        <h3 style="margin-top: 15px; margin-bottom: 10px;">Get your Gmail App Password:</h3>
        <ol style="margin-left: 20px;">
            <li>Go to <strong>https://myaccount.google.com/security</strong></li>
            <li>Enable <strong>2-Factor Authentication</strong> (if not already enabled)</li>
            <li>Go to <strong>App passwords</strong> (https://myaccount.google.com/apppasswords)</li>
            <li>Select <strong>"Mail"</strong> and <strong>"Windows Computer"</strong></li>
            <li>Copy the 16-character password (with spaces)</li>
        </ol>

        <h3 style="margin-top: 15px; margin-bottom: 10px;">Edit config_email.php:</h3>
        <p>Replace these lines:</p>
        <div class="code-block">
define('MAIL_FROM_EMAIL', 'your-gmail@gmail.com');     // Your Gmail
define('MAIL_SMTP_USERNAME', 'your-gmail@gmail.com');  // Your Gmail
define('MAIL_SMTP_PASSWORD', 'xxxx xxxx xxxx xxxx');  // Your 16-char App Password
        </div>

        <p style="margin-top: 10px;"><strong>Example:</strong></p>
        <div class="code-block">
define('MAIL_FROM_EMAIL', 'john.doe@gmail.com');
define('MAIL_SMTP_USERNAME', 'john.doe@gmail.com');
define('MAIL_SMTP_PASSWORD', 'jkhd tjsa oiaw mkjc');
        </div>
    </div>

    <h2>✅ Step 3: Test Email Configuration</h2>
    <div class="step">
        <div class="step-title">Verify email setup:</div>
        <p><a href="test_email.php" class="link-btn">📧 Test Email Sending</a></p>
        <p style="margin-top: 10px; font-size: 12px; color: #666;">This will show if everything is configured correctly and let you send a test email.</p>
    </div>

    <h2>👨‍💼 Step 4: Login as Admin</h2>
    <div class="step">
        <div class="step-title">Access the admin dashboard:</div>
        <p><a href="login.php" class="link-btn">🔐 Go to Login</a></p>
        <p style="margin-top: 10px;">
            <strong>Username:</strong> <code>admin</code><br>
            <strong>Password:</strong> <code>admin123</code>
        </p>
    </div>

    <h2>➕ Step 5: Create User Accounts</h2>
    <div class="step">
        <div class="step-title">Create a teacher, facilitator, or detainee:</div>
        <ol style="margin-left: 20px;">
            <li>Login as admin</li>
            <li>Go to <strong>Teachers</strong>, <strong>Facilitators</strong>, or <strong>Detainees</strong> tab</li>
            <li>Fill in the form:
                <table>
                    <tr>
                        <th>Field</th>
                        <th>Example</th>
                        <th>Notes</th>
                    </tr>
                    <tr>
                        <td>ID Number</td>
                        <td><code>T001</code></td>
                        <td>Unique identifier (used to login)</td>
                    </tr>
                    <tr>
                        <td>Name</td>
                        <td><code>Juan Dela Cruz</code></td>
                        <td>Full name of the user</td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td><code>juan@gmail.com</code></td>
                        <td>Where notification email is sent</td>
                    </tr>
                    <tr>
                        <td>Position/Grade</td>
                        <td><code>Math Teacher</code></td>
                        <td>Position (teachers/facilitators) or Grade Level (detainees)</td>
                    </tr>
                </table>
            </li>
            <li>Click <strong>"Add [Role]"</strong> button</li>
            <li>✅ Account created and email sent automatically!</li>
        </ol>
    </div>

    <h2>📬 Step 6: User Receives Email</h2>
    <div class="success">
        <strong>✅ Email Content:</strong><br>
        The user will receive an email with:
        <ul style="margin-left: 20px; margin-top: 10px;">
            <li>Account creation confirmation</li>
            <li>Their <strong>ID Number</strong> (username for login)</li>
            <li>Link to login page</li>
            <li>Note: No password for demo (just ID Number to login)</li>
        </ul>
    </div>

    <h2>🔑 Step 7: User Logins</h2>
    <div class="step">
        <div class="step-title">The user uses ID Number to login:</div>
        <p>Open the login page: <a href="login.php">http://localhost/tanglaw.../login.php</a></p>
        <ol style="margin-left: 20px;">
            <li>Select role (Teacher/Facilitator/Detainee)</li>
            <li>Enter <strong>ID Number</strong> received in email</li>
            <li>No password required for demo</li>
            <li>✅ Logged in!</li>
        </ol>
    </div>

    <h2>⚠️ Troubleshooting</h2>
    <div class="warning">
        <strong>❌ Email not sending?</strong>
        <ul style="margin-left: 20px; margin-top: 10px;">
            <li>Check config_email.php has correct Gmail and App Password</li>
            <li>Verify PHPMailer installed: <code>composer require phpmailer/phpmailer</code></li>
            <li>Check spam/promotions folder in Gmail</li>
            <li>Run <a href="test_email.php">test_email.php</a> to diagnose</li>
        </ul>
    </div>

    <div class="warning">
        <strong>❌ "Unknown column 'email'" error?</strong>
        <ul style="margin-left: 20px; margin-top: 10px;">
            <li>Run the migration: <a href="migrate_add_email_columns.php">migrate_add_email_columns.php</a></li>
        </ul>
    </div>

    <h2>🎯 Summary</h2>
    <div class="success">
        <strong>✅ System Flow:</strong>
        <ol style="margin-left: 20px;">
            <li>Admin creates account with ID Number + Email</li>
            <li>System sends email with login credentials</li>
            <li>User receives email and login details</li>
            <li>User logins with ID Number (no password demo)</li>
            <li>✅ Done!</li>
        </ol>
    </div>

</div>
</body>
</html>
