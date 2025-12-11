<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Gmail Setup Helper - Tanglaw LMS</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #1e40af; }
        h2 { color: #2563eb; margin-top: 30px; }
        .step {
            background: #f0f9ff;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #2563eb;
            border-radius: 4px;
        }
        .step h3 { margin-top: 0; }
        .code-box {
            background: #f4f4f4;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            overflow-x: auto;
            margin: 10px 0;
        }
        .warning {
            background: #fef3c7;
            padding: 15px;
            border-left: 4px solid #eab308;
            border-radius: 4px;
            margin: 15px 0;
        }
        .success {
            background: #dcfce7;
            padding: 15px;
            border-left: 4px solid #22c55e;
            border-radius: 4px;
            margin: 15px 0;
        }
        .error {
            background: #fee2e2;
            padding: 15px;
            border-left: 4px solid #ef4444;
            border-radius: 4px;
            margin: 15px 0;
        }
        button {
            background: #2563eb;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover { background: #1d4ed8; }
        a { color: #2563eb; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .checklist {
            list-style: none;
            padding: 0;
        }
        .checklist li {
            padding: 8px 0;
            padding-left: 30px;
            position: relative;
        }
        .checklist li:before {
            content: "☐";
            position: absolute;
            left: 0;
            font-size: 20px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🔑 Gmail App Password Setup</h1>
    
    <div class="warning">
        <strong>⚠️ SMTP Authentication Failed</strong>
        <p>Your Gmail configuration isn't working yet. Follow these steps to get your App Password.</p>
    </div>

    <h2>Step 1: Check 2-Step Verification</h2>
    <div class="step">
        <h3>Is 2-Step Verification Enabled?</h3>
        <p>Gmail requires 2-Step Verification to generate App Passwords. Here's how to check:</p>
        <ol>
            <li>Go to <strong><a href="https://myaccount.google.com/security" target="_blank">https://myaccount.google.com/security</a></strong></li>
            <li>Look for <strong>"2-Step Verification"</strong> in the left sidebar</li>
            <li>If it says <strong>"Off"</strong> or <strong>"Not set up"</strong>:
                <ul>
                    <li>Click it</li>
                    <li>Follow the prompts (you'll need your phone)</li>
                    <li>Wait 5-10 minutes after enabling before continuing</li>
                </ul>
            </li>
            <li>If it says <strong>"On"</strong>, you're ready for Step 2</li>
        </ol>
    </div>

    <h2>Step 2: Generate App Password</h2>
    <div class="step">
        <h3>Get Your 16-Character Password</h3>
        <p>After 2-Step Verification is enabled:</p>
        <ol>
            <li>Go to <strong><a href="https://myaccount.google.com/apppasswords" target="_blank">https://myaccount.google.com/apppasswords</a></strong></li>
            <li>Select from the dropdowns:
                <ul>
                    <li>App: <strong>Mail</strong></li>
                    <li>Device: <strong>Windows Computer</strong></li>
                </ul>
            </li>
            <li>Click <strong>"Generate"</strong></li>
            <li>Google will show a 16-character password in the yellow box</li>
            <li><strong>COPY THE ENTIRE PASSWORD</strong> (including spaces)</li>
        </ol>
        <p style="font-size: 14px; color: #666;">
            <em>Example of what you'll see: <code>jkhd tjsa oiaw mkjc</code></em>
        </p>
    </div>

    <h2>Step 3: Update config_email.php</h2>
    <div class="step">
        <h3>Paste Your Password Into the Config</h3>
        <p>Now update your Tanglaw LMS configuration:</p>
        <ol>
            <li>Open VS Code</li>
            <li>Find and open: <code>config_email.php</code></li>
            <li>Find this line (around line 12):
                <div class="code-box">define('MAIL_FROM_EMAIL', 'tanglaw.lms@gmail.com');</div>
                Replace <code>tanglaw.lms@gmail.com</code> with <strong>YOUR Gmail address</strong>
            </li>
            <li>Find this line (around line 18):
                <div class="code-box">define('MAIL_SMTP_USERNAME', 'tanglaw.lms@gmail.com');</div>
                Replace with <strong>YOUR Gmail address</strong> (same as above)
            </li>
            <li>Find this line (around line 19):
                <div class="code-box">define('MAIL_SMTP_PASSWORD', 'vwxy lmno pqrs tuvw');</div>
                Replace <code>vwxy lmno pqrs tuvw</code> with the 16-character password from Step 2
            </li>
            <li><strong>Save the file</strong> (Ctrl+S)</li>
        </ol>
    </div>

    <h2>Step 4: Test Email Sending</h2>
    <div class="step">
        <h3>Verify It Works</h3>
        <ol>
            <li>Start XAMPP (Apache + MySQL)</li>
            <li>Go to: <a href="test_email.php" target="_blank"><strong>test_email.php</strong></a></li>
            <li>Enter a test email address</li>
            <li>Click <strong>"Send Test Email"</strong></li>
            <li>Check your inbox (and spam folder) for the email</li>
        </ol>
    </div>

    <div class="success">
        <strong>✅ Once You See The Test Email:</strong>
        <p>Your email setup is working! You can now:</p>
        <ul>
            <li>Add Teachers and they'll receive welcome emails</li>
            <li>Add Facilitators and they'll receive welcome emails</li>
            <li>Add Detainees and they'll receive welcome emails</li>
        </ul>
    </div>

    <h2>Troubleshooting</h2>
    
    <div class="step">
        <h3>❌ "2-Step Verification Not Available"</h3>
        <p>You might be using a Google Workspace account (business Gmail). Contact your Google Workspace admin about enabling App Passwords.</p>
    </div>

    <div class="step">
        <h3>❌ "SMTP Authentication Failed"</h3>
        <p>Double-check:</p>
        <ul>
            <li>✓ 2-Step Verification is <strong>ON</strong> (not just attempted)</li>
            <li>✓ You copied the full 16-character password (including spaces)</li>
            <li>✓ MAIL_FROM_EMAIL matches MAIL_SMTP_USERNAME</li>
            <li>✓ You saved the file after editing</li>
            <li>✓ Wait 10 minutes after enabling 2-Step before trying</li>
        </ul>
    </div>

    <div class="step">
        <h3>❌ "Could not connect to SMTP server"</h3>
        <p>Check your internet connection and firewall. Some networks block port 587. Try port 465 instead in config_email.php:</p>
        <div class="code-box">
define('MAIL_SMTP_PORT', 465);<br>
// Then in admin_functions_users.php, change the encryption to SMTPS
        </div>
    </div>

    <h2>Quick Links</h2>
    <ul>
        <li><a href="https://myaccount.google.com/security" target="_blank">Google Account Security Settings</a></li>
        <li><a href="https://myaccount.google.com/apppasswords" target="_blank">Generate App Password (2-Step must be ON!)</a></li>
        <li><a href="test_email.php">Test Email Page</a></li>
        <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
    </ul>

    <hr>
    <p style="font-size: 12px; color: #666;">
        <strong>Need help?</strong> Check that:<br>
        • You're logged into the correct Gmail account<br>
        • 2-Step Verification is fully enabled (not pending)<br>
        • You waited at least 10 minutes after enabling 2-Step<br>
        • The app password is 16 characters with spaces
    </p>
</div>
</body>
</html>
