<?php
include 'conn.php';

// First, ensure the level column exists
$checkColumn = $conn->query("SHOW COLUMNS FROM teachers LIKE 'level'");
if (!$checkColumn || $checkColumn->num_rows == 0) {
    echo "Adding level column...\n";
    $conn->query("ALTER TABLE teachers ADD COLUMN `level` VARCHAR(100) DEFAULT NULL AFTER `position`");
}

// Now test saving and retrieving
echo "Testing update...\n";

// Get the first teacher
$result = $conn->query("SELECT id FROM teachers LIMIT 1");
if ($result && $result->num_rows > 0) {
    $teacher = $result->fetch_assoc();
    $id = $teacher['id'];
    
    // Update with test level
    $test_level = "High School";
    $stmt = $conn->prepare("UPDATE teachers SET level = ? WHERE id = ?");
    $stmt->bind_param("si", $test_level, $id);
    
    if ($stmt->execute()) {
        echo "✓ Updated teacher ID $id with level: $test_level\n";
    } else {
        echo "✗ Error updating: " . $stmt->error . "\n";
    }
    $stmt->close();
    
    // Retrieve to verify
    $stmt = $conn->prepare("SELECT id, name, level FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo "\nAfter update:\n";
    echo "ID: " . $row['id'] . "\n";
    echo "Name: " . $row['name'] . "\n";
    echo "Level: " . ($row['level'] ?? 'NULL') . "\n";
    
    $stmt->close();
} else {
    echo "No teachers found in database";
}

$conn->close();
?>
