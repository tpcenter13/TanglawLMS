# Install PHPMailer for Gmail SMTP Support

This script installs PHPMailer so the Tanglaw LMS can send account notifications via Gmail SMTP.

## Quick Start (Windows PowerShell)

1. Open PowerShell as Administrator
2. Navigate to the project:
   ```powershell
   cd "C:\xampp\htdocs\tanglaw (1)\tanglaw\tanglawelearning"
   ```

3. Install PHPMailer using Composer:
   ```powershell
   composer require phpmailer/phpmailer
   ```

## If Composer is not installed

1. Run the provided batch file:
   ```powershell
   .\install_phpmailer.bat
   ```

   Or manually install Composer:
   ```powershell
   # Download and run Composer installer
   Invoke-WebRequest -Uri "https://getcomposer.org/installer" -OutFile "composer-setup.php"
   php composer-setup.php
   
   # Then install PHPMailer
   php composer.phar require phpmailer/phpmailer
   ```

## Configure Gmail SMTP

1. Edit `config_email.php` with your Gmail credentials:
   - `MAIL_FROM_EMAIL` = your Gmail address
   - `MAIL_SMTP_USERNAME` = your Gmail address
   - `MAIL_SMTP_PASSWORD` = your Gmail App Password (NOT your regular password)

2. Get your Gmail App Password:
   - Go to https://myaccount.google.com/security
   - Enable 2-Factor Authentication (if not already enabled)
   - Go to App Passwords (https://myaccount.google.com/apppasswords)
   - Select "Mail" and "Windows Computer"
   - Copy the 16-character password
   - Paste it in `config_email.php` as `MAIL_SMTP_PASSWORD`

## Test Email Sending

1. Start XAMPP (Apache + MySQL)
2. Login to admin: http://localhost/tanglaw%20(1)/tanglaw/tanglawelearning/login.php
   - Username: `admin`
   - Password: `admin123`
3. Go to **Teachers** tab
4. Fill in the form with a test Gmail address you can check
5. Click "Add Teacher"
6. Check the email inbox — you should receive the account notification

## Troubleshooting

- **"Class not found" error**: PHPMailer not installed. Run `composer require phpmailer/phpmailer`
- **"Failed to connect" error**: Check that your Gmail SMTP credentials are correct in `config_email.php`
- **"SMTP connection refused"**: Ensure your firewall allows outbound SMTP (port 587)
- **Email not sent**: Check `php_errors.log` in the project folder for error messages

## Files Created/Modified

- **config_email.php** - Email configuration (add your Gmail credentials here)
- **admin_functions_users.php** - Updated `sendUserNotification()` to use PHPMailer
- **install_phpmailer.bat** - Automated installer script
