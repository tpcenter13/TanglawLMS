<?php
include 'conn.php';

echo "=== DEBUG: Teachers Table ===\n\n";

// Check table structure
echo "1. Checking table structure:\n";
$result = $conn->query("DESCRIBE teachers");
if ($result) {
    $columns = [];
    while($row = $result->fetch_assoc()) {
        $columns[] = $row['Field'];
        echo "   - " . $row['Field'] . "\n";
    }
    echo "\n✓ 'level' column exists: " . (in_array('level', $columns) ? "YES" : "NO") . "\n";
} else {
    echo "Error: " . $conn->error . "\n";
}

// Check sample data
echo "\n2. Sample teacher data:\n";
$result = $conn->query("SELECT * FROM teachers LIMIT 1");
if ($result && $result->num_rows > 0) {
    $teacher = $result->fetch_assoc();
    echo json_encode($teacher, JSON_PRETTY_PRINT);
} else {
    echo "No teachers found";
}

// Check all teachers with level
echo "\n\n3. All teachers with their level and provider_id:\n";
$result = $conn->query("SELECT id, id_number, name, level, provider_id FROM teachers ORDER BY id ASC");
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | " . $row['id_number'] . " | " . $row['name'] . " | Level: " . ($row['level'] ?? 'NULL') . " | Provider: " . ($row['provider_id'] ?? 'NULL') . "\n";
    }
}

?>
