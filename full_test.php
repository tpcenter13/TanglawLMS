<?php
include 'conn.php';
include 'admin_functions_users.php';

// STEP 1: Ensure level column exists
$checkCol = $conn->query("SHOW COLUMNS FROM teachers LIKE 'level'");
if (!$checkCol || $checkCol->num_rows == 0) {
    echo "Creating 'level' column...\n";
    if ($conn->query("ALTER TABLE teachers ADD COLUMN `level` VARCHAR(100) DEFAULT NULL AFTER `position`")) {
        echo "✓ Column 'level' created successfully\n";
    } else {
        echo "✗ Error creating column: " . $conn->error . "\n";
    }
}

// STEP 2: Test update with actual editTeacher function
echo "\nTesting editTeacher function...\n";

$test_teacher_id = 1;
$test_id_number = "T001";
$test_name = "Juan Dela Cruz";
$test_email = "juan@test.com";
$test_position = "English Teacher";
$test_provider_id = 1;
$test_level = "Senior High School";

$result = editTeacher(
    $conn,
    $test_teacher_id,
    $test_id_number,
    $test_name,
    $test_email,
    $test_position,
    $test_provider_id,
    $test_level,
    null
);

echo "Result: " . json_encode($result) . "\n";

// STEP 3: Verify the data was saved
echo "\nVerifying saved data...\n";
$verify = $conn->query("SELECT id, name, level, provider_id FROM teachers WHERE id = 1");
$row = $verify->fetch_assoc();
echo "Data in DB: " . json_encode($row) . "\n";

// STEP 4: Check getAllTeachers function
echo "\nChecking getAllTeachers function...\n";
$all_teachers = getAllTeachers($conn);
if (count($all_teachers) > 0) {
    echo "First teacher: " . json_encode($all_teachers[0]) . "\n";
}

$conn->close();
?>
