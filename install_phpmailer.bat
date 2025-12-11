@echo off
REM Install PHPMailer via Composer
REM Run this from PowerShell: cd "C:\xampp\htdocs\tanglaw (1)\tanglaw\tanglawelearning" && composer require phpmailer/phpmailer

echo Installing PHPMailer...
cd /d "%~dp0"

REM Check if composer is installed
where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo Composer not found. Downloading and installing...
    REM Download composer installer
    powershell -NoProfile -ExecutionPolicy Bypass -Command "& {[Net.ServicePointManager]::SecurityProtocol = [Net.ServicePointManager]::SecurityProtocol -bor [Net.SecurityProtocolType]::Tls12; (New-Object System.Net.WebClient).DownloadFile('https://getcomposer.org/installer', 'composer-setup.php')}"
    php composer-setup.php --install-dir=. --filename=composer.exe
    del composer-setup.php
)

composer require phpmailer/phpmailer

echo PHPMailer installed successfully!
echo.
echo Next steps:
echo 1. Edit config_email.php and add your Gmail credentials
echo 2. Login as admin and create a test account with an email address
pause
