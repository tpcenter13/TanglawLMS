<?php
/**
 * SETUP VERIFICATION SCRIPT
 * Check if all components are ready for account creation with email notifications
 */

echo "<h2>🎓 Tanglaw LMS - Setup Verification</h2>";
echo "<hr>";

// 1. Check PHP version
echo "<h3>1. PHP Version</h3>";
$phpVersion = phpversion();
echo "PHP Version: <strong>$phpVersion</strong> ";
echo (version_compare($phpVersion, '7.0.0', '>=')) ? "✅ OK" : "❌ Needs PHP 7.0+";
echo "<br><br>";

// 2. Check Database Connection
echo "<h3>2. Database Connection</h3>";
$host = "localhost";
$user = "root";
$password = "";
$database = "tanglaw_lms";

$conn = @new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    echo "❌ MySQL Connection FAILED: " . htmlspecialchars($conn->connect_error) . "<br>";
    echo "<em>Please start MySQL in XAMPP and ensure the tanglaw_lms database exists.</em>";
} else {
    echo "✅ MySQL connected to database: <strong>tanglaw_lms</strong><br>";
    
    // Check if email columns exist
    echo "<h4>Checking email columns...</h4>";
    $tables = ['teachers', 'facilitators', 'detainees'];
    foreach ($tables as $table) {
        $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE 'email'");
        if ($res && $res->num_rows > 0) {
            echo "✅ Table <code>$table</code> has email column<br>";
        } else {
            echo "❌ Table <code>$table</code> missing email column<br>";
            echo "<em>Run: <a href=\"migrate_add_email_columns.php\">migrate_add_email_columns.php</a></em><br>";
        }
    }
    echo "<br>";
    $conn->close();
}

// 3. Check PHPMailer Installation
echo "<h3>3. PHPMailer Installation</h3>";
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "✅ PHPMailer installed and autoloader found<br>";
    } else {
        echo "⚠️ Autoloader exists but PHPMailer class not found<br>";
    }
} else {
    echo "❌ PHPMailer not installed<br>";
    echo "<em>Run in PowerShell: <code>composer require phpmailer/phpmailer</code></em><br>";
    echo "Or run: <a href=\"install_phpmailer.bat\">install_phpmailer.bat</a><br>";
}
echo "<br>";

// 4. Check Email Configuration
echo "<h3>4. Email Configuration</h3>";
if (file_exists('config_email.php')) {
    @include('config_email.php');
    if (defined('MAIL_FROM_EMAIL')) {
        echo "✅ config_email.php found<br>";
        echo "From Email: <code>" . htmlspecialchars(MAIL_FROM_EMAIL) . "</code><br>";
        
        // Check if credentials are still default
        if (MAIL_FROM_EMAIL === 'your-email@gmail.com') {
            echo "⚠️ Email credentials are NOT configured<br>";
            echo "<em>Edit <a href=\"config_email.php\">config_email.php</a> with your Gmail account</em><br>";
        } else {
            echo "✅ Email credentials appear to be configured<br>";
        }
    } else {
        echo "❌ config_email.php found but not configured properly<br>";
    }
} else {
    echo "❌ config_email.php not found<br>";
}
echo "<br>";

// 5. Check Required Files
echo "<h3>5. Required Files</h3>";
$files = [
    'login.php' => 'Login page',
    'admin_dashboard.php' => 'Admin dashboard',
    'admin_functions_users.php' => 'User management functions',
    'config_email.php' => 'Email configuration',
    'vendor/autoload.php' => 'Composer autoloader (for PHPMailer)',
];

foreach ($files as $file => $desc) {
    if (file_exists($file)) {
        echo "✅ $desc (<code>$file</code>)<br>";
    } else {
        echo "❌ $desc (<code>$file</code>) NOT FOUND<br>";
    }
}
echo "<br>";

// 6. Next Steps
echo "<h3>✅ Next Steps</h3>";
echo "<ol>";
echo "<li>Start XAMPP (Apache + MySQL)</li>";
echo "<li>If using email: Install PHPMailer: <code>composer require phpmailer/phpmailer</code></li>";
echo "<li>Edit <code>config_email.php</code> with your Gmail credentials</li>";
echo "<li><a href=\"login.php\">Go to Login Page</a></li>";
echo "<li>Login as admin (<code>admin</code> / <code>admin123</code>)</li>";
echo "<li>Create test users in Teachers/Facilitators/Detainees tabs with email addresses</li>";
echo "<li>Check your email inbox for notifications</li>";
echo "</ol>";
?>
