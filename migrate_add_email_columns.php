<?php
/**
 * MIGRATION: Add email columns to user tables
 * Run this script to migrate existing database
 */

$host = "localhost";
$user = "root";
$password = "";
$database = "tanglaw_lms";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die('<h2>❌ Connection Failed</h2>' . htmlspecialchars($conn->connect_error));
}

echo "<h2>🔄 Adding Email Columns Migration</h2>";
echo "<hr>";

$tables = [
    'teachers' => [
        'check' => "SHOW COLUMNS FROM teachers LIKE 'email'",
        'add' => "ALTER TABLE teachers ADD COLUMN email VARCHAR(150) UNIQUE AFTER name"
    ],
    'facilitators' => [
        'check' => "SHOW COLUMNS FROM facilitators LIKE 'email'",
        'add' => "ALTER TABLE facilitators ADD COLUMN email VARCHAR(150) UNIQUE AFTER name"
    ],
    'detainees' => [
        'check' => "SHOW COLUMNS FROM detainees LIKE 'email'",
        'add' => "ALTER TABLE detainees ADD COLUMN email VARCHAR(150) UNIQUE AFTER name"
    ]
];

$success_count = 0;

foreach ($tables as $table => $sqls) {
    echo "<h3>Checking $table table...</h3>";
    
    $res = $conn->query($sqls['check']);
    if ($res && $res->num_rows > 0) {
        echo "✅ Table <code>$table</code> already has email column<br>";
        $success_count++;
    } else {
        echo "⏳ Adding email column to <code>$table</code>... ";
        if ($conn->query($sqls['add'])) {
            echo "✅ SUCCESS<br>";
            $success_count++;
        } else {
            echo "❌ FAILED: " . htmlspecialchars($conn->error) . "<br>";
        }
    }
}

echo "<hr>";
echo "<h2>✅ Migration Complete ($success_count/3 tables)</h2>";

if ($success_count === 3) {
    echo "<p>✅ All email columns added successfully!</p>";
    echo "<p><a href='login.php'>← Go to Login Page</a></p>";
} else {
    echo "<p>❌ Some migrations failed. Check the errors above.</p>";
}

$conn->close();
?>