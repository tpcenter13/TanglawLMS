<?php
include 'conn.php';

echo "=== TESTING FORM DATA ===\n\n";

// Simulate what would be passed
$_POST['teacher_id'] = '1';
$_POST['id_number'] = 'T001';
$_POST['name'] = 'Juan Dela Cruz';
$_POST['email'] = 'juan@example.com';
$_POST['position'] = 'English Teacher';
$_POST['provider_id'] = '1';
$_POST['level'] = 'High School';

echo "POST data:\n";
var_dump($_POST);

echo "\n\nTesting editTeacher function:\n";
include 'admin_functions_users.php';

$result = editTeacher(
    $conn,
    $_POST['teacher_id'],
    $_POST['id_number'],
    $_POST['name'],
    $_POST['email'],
    $_POST['position'],
    $_POST['provider_id'] ?? null,
    $_POST['level'] ?? null,
    null
);

echo "\nResult:\n";
var_dump($result);

// Now check what's in the database
echo "\n\nVerifying database:\n";
$stmt = $conn->prepare("SELECT id, name, level, provider_id FROM teachers WHERE id = ?");
$stmt->bind_param("i", $_POST['teacher_id']);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
echo json_encode($row, JSON_PRETTY_PRINT);

$conn->close();
?>
