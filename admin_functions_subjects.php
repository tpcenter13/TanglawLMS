<?php
/**
 * ADMIN FUNCTIONS - Subject & Grade Level Management
 */

// ============== SUBJECT MANAGEMENT ==============

/**
 * Add Subject
 * @param mysqli $conn
 * @param string $subject_code - Unique subject code
 * @param string $title - Unique subject title
 * @param string $description
 * @return array - ['success' => bool, 'message' => string]
 */
function addSubject($conn, $subject_code, $title, $description, $level = null, $resource_file = null) {
    // Check for duplicate subject code
    $checkStmt = $conn->prepare("SELECT id FROM subjects WHERE LOWER(subject_code) = LOWER(?)");
    $checkStmt->bind_param("s", $subject_code);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ Subject Code already exists'];
    }
    $checkStmt->close();
    
    // Check for duplicate title
    $checkStmt = $conn->prepare("SELECT id FROM subjects WHERE LOWER(title) = LOWER(?)");
    $checkStmt->bind_param("s", $title);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ Subject Title already exists'];
    }
    $checkStmt->close();
    
    // Build insert dynamically to support optional columns like level and resource_file
    $cols = ['subject_code','title','description'];
    $placeholders = ['?','?','?'];
    $types = 'sss';
    $values = [$subject_code, $title, $description];

    if (hasColumn($conn, 'subjects', 'level') && $level !== null) {
        $cols[] = 'level';
        $placeholders[] = '?';
        $types .= 's';
        $values[] = $level;
    }

    $fileCol = null;
    foreach (['resource_file','file_path','subject_file'] as $c) {
        if (hasColumn($conn, 'subjects', $c)) { $fileCol = $c; break; }
    }
    if ($fileCol && $resource_file !== null) {
        $cols[] = $fileCol;
        $placeholders[] = '?';
        $types .= 's';
        $values[] = $resource_file;
    }

    $sql = "INSERT INTO subjects (" . implode(',', $cols) . ") VALUES (" . implode(',', $placeholders) . ")";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $bind_names = [];
        $bind_names[] = & $types;
        for ($i = 0; $i < count($values); $i++) { $bind_names[] = & $values[$i]; }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    if ($stmt && $stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => '✅ Subject added successfully'];
    } else {
        if ($stmt) { $stmt->close(); }
        return ['success' => false, 'message' => '❌ Error adding subject'];
    }
}

/**
 * Edit Subject
 */
function editSubject($conn, $subject_id, $subject_code, $title, $description, $level = null, $resource_file = null) {
    // Check if new code exists elsewhere
    $checkStmt = $conn->prepare("SELECT id FROM subjects WHERE LOWER(subject_code) = LOWER(?) AND id != ?");
    $checkStmt->bind_param("si", $subject_code, $subject_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ Subject Code already exists'];
    }
    $checkStmt->close();
    
    // Check if new title exists elsewhere
    $checkStmt = $conn->prepare("SELECT id FROM subjects WHERE LOWER(title) = LOWER(?) AND id != ?");
    $checkStmt->bind_param("si", $title, $subject_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ Subject Title already exists'];
    }
    $checkStmt->close();
    
    // Build update dynamically
    $sets = ['subject_code = ?', 'title = ?', 'description = ?'];
    $types = 'sss';
    $values = [$subject_code, $title, $description];

    if (hasColumn($conn, 'subjects', 'level') && $level !== null) {
        $sets[] = 'level = ?';
        $types .= 's';
        $values[] = $level;
    }

    $fileCol = null;
    foreach (['resource_file','file_path','subject_file'] as $c) {
        if (hasColumn($conn, 'subjects', $c)) { $fileCol = $c; break; }
    }
    if ($fileCol && $resource_file !== null) {
        $sets[] = "$fileCol = ?";
        $types .= 's';
        $values[] = $resource_file;
    }

    $sql = "UPDATE subjects SET " . implode(', ', $sets) . " WHERE id = ?";
    $values[] = $subject_id;
    $types .= 'i';

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $bind_names = [];
        $bind_names[] = & $types;
        for ($i = 0; $i < count($values); $i++) { $bind_names[] = & $values[$i]; }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    if ($stmt && $stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => '✅ Subject updated successfully'];
    } else {
        if ($stmt) { $stmt->close(); }
        return ['success' => false, 'message' => '❌ Error updating subject'];
    }
}

/**
 * Archive Subject (soft delete)
 */
function archiveSubject($conn, $subject_id) {
    $stmt = $conn->prepare("UPDATE subjects SET archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $subject_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => '✅ Subject archived successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => '❌ Error archiving subject'];
    }
}

/**
 * Get All Subjects (active only)
 */
function getAllSubjects($conn, $includeArchived = false) {
    $query = "SELECT * FROM subjects WHERE archived = 0 ORDER BY title ASC";
    if ($includeArchived) {
        $query = "SELECT * FROM subjects ORDER BY title ASC";
    }
    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// ============== GRADE LEVEL MANAGEMENT ==============

/**
 * Add Grade Level
 * @param mysqli $conn
 * @param string $level - Grade level (e.g., "Grade 7", "Grade 8", etc.)
 * @return array - ['success' => bool, 'message' => string]
 */
function addGradeLevel($conn, $level) {
    // Check for duplicate level
    $checkStmt = $conn->prepare("SELECT id FROM grade_levels WHERE LOWER(level) = LOWER(?)");
    $checkStmt->bind_param("s", $level);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ Grade Level already exists'];
    }
    $checkStmt->close();
    
    $stmt = $conn->prepare("INSERT INTO grade_levels (level) VALUES (?)");
    $stmt->bind_param("s", $level);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => '✅ Grade Level added successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => '❌ Error adding grade level'];
    }
}

/**
 * Edit Grade Level
 */
function editGradeLevel($conn, $grade_level_id, $level) {
    // Check if new level exists elsewhere
    $checkStmt = $conn->prepare("SELECT id FROM grade_levels WHERE LOWER(level) = LOWER(?) AND id != ?");
    $checkStmt->bind_param("si", $level, $grade_level_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ Grade Level already exists'];
    }
    $checkStmt->close();
    
    $stmt = $conn->prepare("UPDATE grade_levels SET level = ? WHERE id = ?");
    $stmt->bind_param("si", $level, $grade_level_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => '✅ Grade Level updated successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => '❌ Error updating grade level'];
    }
}

/**
 * Archive Grade Level (soft delete)
 */
function archiveGradeLevel($conn, $grade_level_id) {
    $stmt = $conn->prepare("UPDATE grade_levels SET archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $grade_level_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => '✅ Grade Level archived successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => '❌ Error archiving grade level'];
    }
}

/**
 * Get All Grade Levels (active only)
 */
function getAllGradeLevels($conn, $includeArchived = false) {
    $query = "SELECT * FROM grade_levels WHERE archived = 0 ORDER BY level ASC";
    if ($includeArchived) {
        $query = "SELECT * FROM grade_levels ORDER BY level ASC";
    }
    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

?>
