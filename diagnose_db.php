<?php
include 'conn.php';

echo "=== DATABASE DIAGNOSIS ===\n\n";

// 1. Check if level column exists
echo "1. Checking if 'level' column exists in teachers table...\n";
$result = $conn->query("SHOW COLUMNS FROM teachers LIKE 'level'");
if ($result && $result->num_rows > 0) {
    echo "✓ Column EXISTS\n";
    $col = $result->fetch_assoc();
    echo "   Type: " . $col['Type'] . "\n";
    echo "   Null: " . $col['Null'] . "\n";
    echo "   Default: " . ($col['Default'] ?? 'NULL') . "\n";
} else {
    echo "✗ Column DOES NOT EXIST - Creating it now...\n";
    $sql = "ALTER TABLE teachers ADD COLUMN `level` VARCHAR(100) DEFAULT NULL AFTER `position`";
    if ($conn->query($sql)) {
        echo "✓ Column created successfully\n";
    } else {
        echo "✗ Error creating column: " . $conn->error . "\n";
    }
}

// 2. Check all columns in teachers table
echo "\n2. All columns in teachers table:\n";
$result = $conn->query("DESCRIBE teachers");
while($row = $result->fetch_assoc()) {
    echo "   - " . $row['Field'] . " (" . $row['Type'] . ")\n";
}

// 3. Test saving a level value
echo "\n3. Testing save/retrieve of level value...\n";
$test_id = 1;
$test_level = "High School";

// Update
$stmt = $conn->prepare("UPDATE teachers SET level = ? WHERE id = ?");
$stmt->bind_param("si", $test_level, $test_id);
if ($stmt->execute()) {
    echo "✓ Update successful\n";
} else {
    echo "✗ Update failed: " . $stmt->error . "\n";
}
$stmt->close();

// Retrieve
$stmt = $conn->prepare("SELECT id, name, level FROM teachers WHERE id = ?");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo "   Retrieved - Name: " . $row['name'] . ", Level: " . ($row['level'] ?? 'NULL') . "\n";
$stmt->close();

// 4. Check all teachers
echo "\n4. All teachers with their level and provider_id:\n";
$result = $conn->query("SELECT id, id_number, name, level, provider_id FROM teachers");
while($row = $result->fetch_assoc()) {
    echo "   [" . $row['id'] . "] " . $row['id_number'] . " - " . $row['name'] . 
         " | Level: " . ($row['level'] ?? 'NULL') . 
         " | Provider: " . ($row['provider_id'] ?? 'NULL') . "\n";
}

echo "\nDiagnosis complete.\n";
$conn->close();
?>
