<?php
include 'conn.php';

echo "=== Teachers Table Structure ===\n\n";
$result = $conn->query("DESCRIBE teachers");
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "Error: " . $conn->error;
}

echo "\n=== Sample Teacher Data ===\n";
$result = $conn->query("SELECT id, id_number, name, level, provider_id FROM teachers LIMIT 1");
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo json_encode($row, JSON_PRETTY_PRINT);
    }
} else {
    echo "No teachers found";
}
?>
