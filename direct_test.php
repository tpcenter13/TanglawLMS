<?php
include 'conn.php';

echo "=== DIRECT DATABASE TEST ===\n\n";

// First, ensure level column exists
$checkCol = $conn->query("SHOW COLUMNS FROM teachers LIKE 'level'");
if (!$checkCol || $checkCol->num_rows == 0) {
    echo "Creating 'level' column...\n";
    $conn->query("ALTER TABLE teachers ADD COLUMN `level` VARCHAR(100) DEFAULT NULL AFTER `position`");
}

// Get first teacher
$result = $conn->query("SELECT id, name FROM teachers LIMIT 1");
if ($result && $result->num_rows > 0) {
    $teacher = $result->fetch_assoc();
    $teacher_id = $teacher['id'];
    $teacher_name = $teacher['name'];
    
    echo "1. Current data for teacher ID $teacher_id ($teacher_name):\n";
    $result = $conn->query("SELECT id, name, level, provider_id FROM teachers WHERE id = $teacher_id");
    $row = $result->fetch_assoc();
    echo "   Before: " . json_encode($row) . "\n\n";
    
    // Direct update
    echo "2. Updating directly with SQL...\n";
    $new_level = "College";
    $new_provider = 2;
    $sql = "UPDATE teachers SET level = '$new_level', provider_id = $new_provider WHERE id = $teacher_id";
    echo "   SQL: " . $sql . "\n";
    $conn->query($sql);
    
    // Check result
    echo "\n3. After update:\n";
    $result = $conn->query("SELECT id, name, level, provider_id FROM teachers WHERE id = $teacher_id");
    $row = $result->fetch_assoc();
    echo "   After: " . json_encode($row) . "\n";
    
    // Now test with prepared statement
    echo "\n4. Testing prepared statement update...\n";
    $test_level = "Senior High School";
    $test_provider = 1;
    $stmt = $conn->prepare("UPDATE teachers SET level = ?, provider_id = ? WHERE id = ?");
    $stmt->bind_param("sii", $test_level, $test_provider, $teacher_id);
    if ($stmt->execute()) {
        echo "   ✓ Prepared statement executed\n";
    } else {
        echo "   ✗ Error: " . $stmt->error . "\n";
    }
    $stmt->close();
    
    // Check result
    echo "\n5. After prepared statement update:\n";
    $result = $conn->query("SELECT id, name, level, provider_id FROM teachers WHERE id = $teacher_id");
    $row = $result->fetch_assoc();
    echo "   Final: " . json_encode($row) . "\n";
}

$conn->close();
?>
