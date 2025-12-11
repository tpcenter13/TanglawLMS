<?php
/**
 * ADMIN FUNCTIONS - Provider Management
 */

// ============== PROVIDER MANAGEMENT ==============

/**
 * Add Provider
 * @param mysqli $conn
 * @param string $id_number - Unique ID Number
 * @param string $name - Unique provider name
 * @param string $provider_type - Type (Marcelo, St. Martin, DepEd ALS, etc.)
 * @return array - ['success' => bool, 'message' => string]
 */
function addProvider($conn, $id_number, $name, $provider_type) {
    // Check for duplicate ID Number
    $checkStmt = $conn->prepare("SELECT id FROM providers WHERE id_number = ?");
    $checkStmt->bind_param("s", $id_number);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ ID Number already exists'];
    }
    $checkStmt->close();
    
    // Check for duplicate name
    $checkStmt = $conn->prepare("SELECT id FROM providers WHERE LOWER(name) = LOWER(?)");
    $checkStmt->bind_param("s", $name);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ Provider name already exists'];
    }
    $checkStmt->close();
    
    $stmt = $conn->prepare("INSERT INTO providers (id_number, name, provider_type) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $id_number, $name, $provider_type);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => '✅ Provider added successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => '❌ Error adding provider'];
    }
}

/**
 * Edit Provider
 */
function editProvider($conn, $provider_id, $id_number, $name, $provider_type) {
    // Check if new ID Number exists elsewhere
    $checkStmt = $conn->prepare("SELECT id FROM providers WHERE id_number = ? AND id != ?");
    $checkStmt->bind_param("si", $id_number, $provider_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ ID Number already exists'];
    }
    $checkStmt->close();
    
    // Check if new name exists elsewhere
    $checkStmt = $conn->prepare("SELECT id FROM providers WHERE LOWER(name) = LOWER(?) AND id != ?");
    $checkStmt->bind_param("si", $name, $provider_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ Provider name already exists'];
    }
    $checkStmt->close();
    
    $stmt = $conn->prepare("UPDATE providers SET id_number = ?, name = ?, provider_type = ? WHERE id = ?");
    $stmt->bind_param("sssi", $id_number, $name, $provider_type, $provider_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => '✅ Provider updated successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => '❌ Error updating provider'];
    }
}

/**
 * Archive Provider (soft delete)
 */
function archiveProvider($conn, $provider_id) {
    $stmt = $conn->prepare("UPDATE providers SET archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $provider_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => '✅ Provider archived successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => '❌ Error archiving provider'];
    }
}

/**
 * Get All Providers (active only)
 */
function getAllProviders($conn, $includeArchived = false) {
    $query = "SELECT * FROM providers WHERE archived = 0 ORDER BY name ASC";
    if ($includeArchived) {
        $query = "SELECT * FROM providers ORDER BY name ASC";
    }
    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

?>
