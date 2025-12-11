# Gmail Email Setup Guide - Tanglaw LMS

✅ **PHPMailer is installed and working!**

## Quick Setup Steps

### Step 1: Enable Gmail 2-Step Verification
1. Go to https://myaccount.google.com/security
2. Click **2-Step Verification** on the left sidebar
3. Follow the prompts to enable it (you'll need your phone)
4. **Important:** 2-Step Verification MUST be enabled before generating an app password

### Step 2: Generate Gmail App Password
1. After 2-Step is enabled, go to https://myaccount.google.com/apppasswords
2. Select:
   - App: **Mail**
   - Device: **Windows Computer**
3. Gmail will generate a **16-character password** with spaces (like: `jkhd tjsa oiaw mkjc`)
4. **Copy the entire password** (including spaces)

### Step 3: Configure Tanglaw LMS
1. Open `config_email.php` in VS Code
2. Replace these values:
   ```php
   define('MAIL_FROM_EMAIL', 'your-email@gmail.com');    // Your Gmail address
   define('MAIL_SMTP_USERNAME', 'your-email@gmail.com');  // Same Gmail
   define('MAIL_SMTP_PASSWORD', 'jkhd tjsa oiaw mkjc');  // Paste the 16-char password here
   ```

### Step 4: Test Email Sending
1. Start XAMPP (Apache + MySQL)
2. Go to: `http://localhost/tanglaw%20(1)/tanglaw/tanglawelearning/test_email.php`
3. Enter a test email address
4. Click "Send Test Email"
5. Check your inbox (or spam folder) for the test email

### Step 5: Send Welcome Emails to Users
1. Log in to admin: `http://localhost/tanglaw%20(1)/tanglaw/tanglawelearning/login.php`
   - Username: `admin`
   - Password: `admin123`
2. Go to the **Users/Teachers** tab
3. Add a new teacher with their email
4. The system will automatically send them a welcome email

## Testing Individual Emails

### In PHP Code
```php
// Add this to test email sending in any PHP file:
require 'vendor/autoload.php';
require 'config_email.php';
require 'admin_functions_users.php';

sendUserNotification('test@example.com', 'Teacher', 'ID123', 'John Doe');
```

### Files Involved
- `vendor/autoload.php` - PHPMailer loader
- `vendor/phpmailer/phpmailer/src/PHPMailer.php` - Main PHPMailer class
- `config_email.php` - Gmail credentials (update this!)
- `admin_functions_users.php` - Email sending functions
- `test_phpmailer.php` - Installation test
- `test_email.php` - Web-based email tester

## Troubleshooting

### "Failed to connect to SMTP server"
- Double-check your Gmail App Password (not your regular password)
- Verify 2-Step Verification is enabled on your Gmail
- Check firewall/antivirus isn't blocking port 587

### "Email not sent but no error"
- Check that `MAIL_FROM_EMAIL` matches `MAIL_SMTP_USERNAME`
- Verify the recipient email is valid
- Check Gmail's "Activity" page to see if login attempts were rejected

### "SMTP connection refused"
- Ensure your computer has internet connection
- Check that the firewall allows outbound traffic on port 587
- Try using port 465 with SSL instead:
  ```php
  define('MAIL_SMTP_PORT', 465);
  $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
  ```

### "App passwords not showing in Google"
- Make sure you're logged in to the right Gmail account
- Verify 2-Step Verification is fully enabled (not just "turned on")
- Some Google Workspace accounts don't support app passwords (contact your admin)

## Installed Components

- ✅ PHPMailer 7.0.1 (manual installation)
- ✅ Custom autoloader (vendor/autoload.php)
- ✅ Composer support (global composer on system PATH)
- ✅ Gmail SMTP configuration ready
- ✅ Email functions integrated in admin_functions_users.php

## Email Functions Available

```php
// Send email to user after account creation
sendUserNotification($email, $role, $id_number, $name);

// Used internally by:
addTeacher($conn, $id_number, $name, $email, $position);
addFacilitator($conn, $id_number, $name, $email, $position, $status);
addDetainee($conn, $id_number, $name, $email, $grade_level);
```

---

**Next Steps:**
1. Get your Gmail App Password from Step 2
2. Update `config_email.php` with your Gmail credentials
3. Run the test at `test_email.php`
4. Start sending welcome emails to your users!

