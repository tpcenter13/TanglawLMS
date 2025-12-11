<?php
/**
 * EMAIL CONFIGURATION FOR GMAIL SMTP
 * This file configures PHPMailer to send emails via Gmail
 */

// ============================================================
// STEP 1: Replace these with YOUR Gmail credentials
// ============================================================

// Your Gmail address (the account sending emails)
// CHANGE THIS: Replace with your actual Gmail address

if (!defined('MAIL_FROM_EMAIL')) define('MAIL_FROM_EMAIL', 'emmanuelnatsu456@gmail.com');
if (!defined('MAIL_FROM_NAME')) define('MAIL_FROM_NAME', 'Tanglaw LMS');

// Gmail SMTP Server Settings
if (!defined('MAIL_SMTP_HOST')) define('MAIL_SMTP_HOST', 'smtp.gmail.com');
if (!defined('MAIL_SMTP_PORT')) define('MAIL_SMTP_PORT', 587);                              // TLS Port (recommended)
if (!defined('MAIL_SMTP_USERNAME')) define('MAIL_SMTP_USERNAME', 'emmanuelnatsu456@gmail.com');  // Same as MAIL_FROM_EMAIL - CHANGE THIS
if (!defined('MAIL_SMTP_PASSWORD')) define('MAIL_SMTP_PASSWORD', 'natsu4567');                  // App Password (see below) - CHANGE THIS

// ============================================================
// HOW TO GENERATE A GMAIL APP PASSWORD
// ============================================================
// 
// 1. Go to https://myaccount.google.com/security in a browser
// 2. Make sure 2-Step Verification is ENABLED
//    - If not enabled, enable it now (click "2-Step Verification")
// 3. After 2-Step is enabled, go to https://myaccount.google.com/apppasswords
// 4. Select "Mail" as the app and "Windows Computer" as the device
// 5. Gmail will generate a 16-character password (with spaces)
//    Example: jkhd tjsa oiaw mkjc
// 6. Copy this password and replace 'your-app-password' above
//
// IMPORTANT: 
// - Use the 16-character APP PASSWORD, NOT your regular Gmail password
// - The APP PASSWORD will NOT work without 2-Step Verification enabled
// 
// ============================================================

/**
 * Test your configuration:
 * 1. Start XAMPP (Apache + MySQL)
 * 2. Go to: http://localhost/tanglaw%20(1)/tanglaw/tanglawelearning/test_email.php
 * 3. Enter your test email and click "Send Test Email"
 * 4. Check your inbox for the test email
 */

?>

