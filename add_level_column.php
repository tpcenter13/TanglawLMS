<?php
include 'conn.php';

echo "=== Adding 'level' column to teachers table ===\n\n";

// Check if column already exists
$result = $conn->query("SHOW COLUMNS FROM teachers LIKE 'level'");
if ($result && $result->num_rows > 0) {
    echo "✓ Column 'level' already exists in teachers table\n";
} else {
    // Add the level column
    $sql = "ALTER TABLE teachers ADD COLUMN level VARCHAR(100) DEFAULT NULL AFTER position";
    if ($conn->query($sql)) {
        echo "✓ Successfully added 'level' column to teachers table\n";
    } else {
        echo "✗ Error adding column: " . $conn->error . "\n";
    }
}

echo "\n=== Verifying teachers table structure ===\n";
$result = $conn->query("DESCRIBE teachers");
if ($result) {
    echo "Columns in teachers table:\n";
    while($row = $result->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error: " . $conn->error;
}

echo "\nDone!\n";
?>
