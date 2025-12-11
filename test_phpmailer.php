<?php
/**
 * Test PHPMailer Installation
 */

echo "Testing PHPMailer installation...\n\n";

// Test 1: Check vendor autoload
echo "Test 1: Loading autoloader...\n";
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
    echo "✓ Autoloader loaded\n";
} else {
    echo "✗ Autoloader NOT found\n";
    exit;
}

// Test 2: Check if PHPMailer class exists
echo "\nTest 2: Checking PHPMailer class...\n";
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "✓ PHPMailer class found\n";
} else {
    echo "✗ PHPMailer class NOT found\n";
    echo "Trying direct include...\n";
    $file = __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
    if (file_exists($file)) {
        require $file;
        echo "✓ Loaded via direct include\n";
    }
}

// Test 3: Try to create instance
echo "\nTest 3: Creating PHPMailer instance...\n";
try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    echo "✓ PHPMailer instance created successfully\n";
    echo "  PHPMailer version: " . $mail->Version . "\n";
} catch (Exception $e) {
    echo "✗ Failed to create instance: " . $e->getMessage() . "\n";
}

// Test 4: Check config
echo "\nTest 4: Checking email config...\n";
if (file_exists('config_email.php')) {
    include 'config_email.php';
    echo "✓ config_email.php loaded\n";
    echo "  MAIL_FROM_EMAIL: " . MAIL_FROM_EMAIL . "\n";
    echo "  MAIL_SMTP_HOST: " . MAIL_SMTP_HOST . "\n";
} else {
    echo "✗ config_email.php NOT found\n";
}

echo "\n✓ All tests completed!\n";
?>
