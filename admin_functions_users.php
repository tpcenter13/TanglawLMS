<?php
/**
 * ADMIN FUNCTIONS - User Management
 * Handles Teachers, Facilitators, and Detainees
 * UPDATED WITH PASSWORD AND ENROLLMENT VALIDATION
 */

// Load PHPMailer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// ============== USER MANAGEMENT - TEACHERS ==============

/**
 * Helper: check if a table has a column
 */
function hasColumn($conn, $table, $column) {
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $res && $res->num_rows > 0;
}

/**
 * Ensure the given table has a `password` column; if missing, add it.
 */
function ensurePasswordColumn($conn, $table) {
    if (hasColumn($conn, $table, 'password')) return true;
    $tableEsc = $conn->real_escape_string($table);
    $sql = "ALTER TABLE `$tableEsc` ADD COLUMN `password` VARCHAR(255) DEFAULT NULL AFTER `email`";
    return $conn->query($sql);
}

/**
 * Ensure detainees table has a `school` column
 */
function ensureSchoolColumn($conn) {
    if (hasColumn($conn, 'detainees', 'school')) return true;
    $sql = "ALTER TABLE `detainees` ADD COLUMN `school` VARCHAR(100) DEFAULT NULL AFTER `grade_level`";
    return $conn->query($sql);
}

/**
 * Generate a random password string
 */
function generateRandomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+-=';
    $max = strlen($chars) - 1;
    $pw = '';
    for ($i = 0; $i < $length; $i++) {
        $pw .= $chars[random_int(0, $max)];
    }
    return $pw;
}

/**
 * Send account notification to user via Gmail SMTP using PHPMailer
 */
function sendUserNotification($toEmail, $role, $id_number, $name, $password = null) {
    if (empty($toEmail)) {
        return false;
    }

    // Build subject and HTML body
    $subject = "Tanglaw LMS - Account Created";
    $bodyHtml = "<p>Hi " . htmlspecialchars($name) . ",</p>";
    $bodyHtml .= "<p>An account has been created for you on <strong>Tanglaw LMS</strong> as <strong>" . htmlspecialchars($role) . "</strong>.</p>";
    $bodyHtml .= "<p><strong>Login details:</strong><br>Username (ID Number): <strong>" . htmlspecialchars($id_number) . "</strong><br>";
    if (!empty($password)) {
        $bodyHtml .= "Password: <strong>" . htmlspecialchars($password) . "</strong><br>";
    } else {
        $bodyHtml .= "Password: <em>set by administrator</em><br>";
    }
    $bodyHtml .= "</p>";
    $bodyHtml .= "<p>Please login at: <a href=\"login.php\">Tanglaw LMS</a></p>";
    $bodyHtml .= "<p>Regards,<br/><strong>Tanglaw LMS</strong></p>";

    // Try to send via PHPMailer helper if available
    if (function_exists('sendViaPHPMailer')) {
        try {
            if (sendViaPHPMailer($toEmail, $subject, $bodyHtml)) {
                return true;
            }
        } catch (Exception $e) {
            error_log("sendUserNotification sendViaPHPMailer failed: " . $e->getMessage());
        }
    }

    // Attempt to require Composer autoload (if not already loaded) and try again
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        if (function_exists('sendViaPHPMailer')) {
            try {
                if (sendViaPHPMailer($toEmail, $subject, $bodyHtml)) {
                    return true;
                }
            } catch (Exception $e) {
                error_log("sendUserNotification sendViaPHPMailer after autoload failed: " . $e->getMessage());
            }
        }
    }

    // Fallback to built-in mail()
    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: Tanglaw LMS <no-reply@localhost>" . "\r\n";
    return @mail($toEmail, $subject, $bodyHtml, $headers);
}

/**
 * Send email via PHPMailer with Gmail SMTP
 */
function sendViaPHPMailer($toEmail, $subject, $bodyHtml) {
    if (!class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
        error_log("PHPMailer class not found");
        return false;
    }

    if (!file_exists('config_email.php')) {
        error_log("config_email.php not found in sendViaPHPMailer");
        return false;
    }
    include('config_email.php');

    try {
        /** @var \PHPMailer\PHPMailer\PHPMailer $mail */
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = MAIL_SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_SMTP_USERNAME;
        $mail->Password   = MAIL_SMTP_PASSWORD;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = MAIL_SMTP_PORT;
        $mail->SMTPDebug  = 2;
        $mail->Debugoutput = 'html';

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = strip_tags($bodyHtml);

        if ($mail->send()) {
            error_log("Email sent successfully to $toEmail");
            return true;
        } else {
            error_log("PHPMailer failed to send: " . $mail->ErrorInfo);
            return false;
        }
    } catch (Exception $e) {
        error_log("PHPMailer exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Add Teacher
 */
function addTeacher($conn, $id_number, $name, $email, $position, $provider_id = null, $level = null, $profile_file = null, $adminPassword = null) {
    // ===== VALIDATION: Password length =====
    if (!empty($adminPassword) && strlen($adminPassword) < 8) {
        return ['success' => false, 'message' => '❌ Password must be at least 8 characters long'];
    }

    // ===== VALIDATION: Required provider_id for teachers =====
    if (empty($provider_id)) {
        return ['success' => false, 'message' => '❌ School/Provider is required for teachers'];
    }

    // Check for duplicate ID Number
    $checkStmt = $conn->prepare("SELECT id FROM teachers WHERE id_number = ?");
    $checkStmt->bind_param("s", $id_number);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ ID Number already exists'];
    }
    $checkStmt->close();

    // Check for duplicate name
    $checkStmt = $conn->prepare("SELECT id FROM teachers WHERE LOWER(name) = LOWER(?)");
    $checkStmt->bind_param("s", $name);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ Teacher name already exists'];
    }
    $checkStmt->close();

    // Check for duplicate email (if provided)
    if (!empty($email)) {
        $checkStmt = $conn->prepare("SELECT id FROM teachers WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => '❌ Email already exists'];
        }
        $checkStmt->close();
    }

    // Validate provider_id if provided
    if (!empty($provider_id) && hasColumn($conn, 'teachers', 'provider_id')) {
        $checkStmt = $conn->prepare("SELECT id FROM providers WHERE id = ?");
        $checkStmt->bind_param("i", $provider_id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows === 0) {
            return ['success' => false, 'message' => '❌ Invalid provider selected. Provider does not exist.'];
        }
        $checkStmt->close();
    }

    // Ensure password column exists and set password for the new account
    ensurePasswordColumn($conn, 'teachers');
    $plainPassword  = !empty($adminPassword) ? $adminPassword : generateRandomPassword(10);
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    // Build insert dynamically depending on columns available
    $cols         = ['id_number', 'name', 'email', 'position', 'password'];
    $placeholders = ['?', '?', '?', '?', '?'];
    $types        = 'sssss';
    $values       = [$id_number, $name, $email, $position, $hashedPassword];

    if (hasColumn($conn, 'teachers', 'provider_id') && !empty($provider_id)) {
        $cols[]         = 'provider_id';
        $placeholders[] = '?';
        $types         .= 'i';
        $values[]       = (int)$provider_id;
    }

    if (hasColumn($conn, 'teachers', 'level') && $level !== null) {
        $cols[]         = 'level';
        $placeholders[] = '?';
        $types         .= 's';
        $values[]       = $level;
    }

    if (hasColumn($conn, 'teachers', 'profile_file') && $profile_file !== null) {
        $cols[]         = 'profile_file';
        $placeholders[] = '?';
        $types         .= 's';
        $values[]       = $profile_file;
    }

    $sql  = "INSERT INTO teachers (" . implode(",", $cols) . ") VALUES (" . implode(",", $placeholders) . ")";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $bind_names   = [];
        $bind_names[] = &$types;
        for ($i = 0; $i < count($values); $i++) {
            $bind_names[] = &$values[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    if ($stmt && $stmt->execute()) {
        $stmt->close();
        // Send notification email
        if (!empty($email)) {
            sendUserNotification($email, 'Teacher', $id_number, $name, $plainPassword);
        }
        // ✅ FIX: return all fields needed for welcome email
        return [
            'success'   => true,
            'message'   => '✅ Teacher added successfully',
            'email'     => $email,
            'name'      => $name,
            'id_number' => $id_number,
            'password'  => $plainPassword,
        ];
    } else {
        if ($stmt) { $stmt->close(); }
        return ['success' => false, 'message' => '❌ Error adding teacher: ' . $conn->error];
    }
}

/**
 * Edit Teacher
 */
function editTeacher($conn, $teacher_id, $id_number, $name, $email, $position, $provider_id = null, $level = null, $profile_file = null, $newPassword = null) {
    if (!empty($newPassword) && strlen($newPassword) < 8) {
        return ['success' => false, 'message' => '❌ Password must be at least 8 characters long'];
    }

    if (empty($provider_id)) {
        return ['success' => false, 'message' => '❌ School/Provider is required for teachers'];
    }

    $checkStmt = $conn->prepare("SELECT id FROM teachers WHERE id_number = ? AND id != ?");
    $checkStmt->bind_param("si", $id_number, $teacher_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ ID Number already exists'];
    }
    $checkStmt->close();

    $checkStmt = $conn->prepare("SELECT id FROM teachers WHERE LOWER(name) = LOWER(?) AND id != ?");
    $checkStmt->bind_param("si", $name, $teacher_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ Teacher name already exists'];
    }
    $checkStmt->close();

    if (!empty($email)) {
        $checkStmt = $conn->prepare("SELECT id FROM teachers WHERE email = ? AND id != ?");
        $checkStmt->bind_param("si", $email, $teacher_id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => '❌ Email already exists'];
        }
        $checkStmt->close();
    }

    $sets   = ['id_number = ?', 'name = ?', 'email = ?', 'position = ?'];
    $types  = 'ssss';
    $values = [$id_number, $name, $email, $position];

    if (hasColumn($conn, 'teachers', 'provider_id')) {
        $sets[]   = 'provider_id = ?';
        $types   .= 'i';
        $values[] = !empty($provider_id) ? (int)$provider_id : 0;
    }

    if (hasColumn($conn, 'teachers', 'level')) {
        $sets[]   = 'level = ?';
        $types   .= 's';
        $values[] = !empty($level) ? $level : '';
    }

    if (hasColumn($conn, 'teachers', 'profile_file') && $profile_file !== null) {
        $sets[]   = 'profile_file = ?';
        $types   .= 's';
        $values[] = $profile_file;
    }

    $sql      = "UPDATE teachers SET " . implode(', ', $sets) . " WHERE id = ?";
    $values[] = $teacher_id;
    $types   .= 'i';

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $bind_names   = [];
        $bind_names[] = &$types;
        for ($i = 0; $i < count($values); $i++) {
            $bind_names[] = &$values[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind_names);
    }

    if ($stmt && $stmt->execute()) {
        $stmt->close();
        if (!empty($newPassword)) {
            setUserPassword($conn, 'teacher', $teacher_id, $newPassword);
        }
        return ['success' => true, 'message' => '✅ Teacher updated successfully'];
    } else {
        if ($stmt) { $stmt->close(); }
        return ['success' => false, 'message' => '❌ Error updating teacher'];
    }
}

/**
 * Archive Teacher (soft delete)
 */
function archiveTeacher($conn, $teacher_id) {
    $stmt = $conn->prepare("UPDATE teachers SET archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $teacher_id);

    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => '✅ Teacher archived successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => '❌ Error archiving teacher'];
    }
}

/**
 * Get All Teachers (active only)
 */
function getAllTeachers($conn, $includeArchived = false) {
    if (hasColumn($conn, 'teachers', 'provider_id')) {
        $query = $includeArchived
            ? "SELECT t.*, p.name AS provider_name, t.provider_id FROM teachers t LEFT JOIN providers p ON t.provider_id = p.id ORDER BY t.name ASC"
            : "SELECT t.*, p.name AS provider_name, t.provider_id FROM teachers t LEFT JOIN providers p ON t.provider_id = p.id WHERE t.archived = 0 ORDER BY t.name ASC";
    } else {
        $query = $includeArchived
            ? "SELECT * FROM teachers ORDER BY name ASC"
            : "SELECT * FROM teachers WHERE archived = 0 ORDER BY name ASC";
    }
    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// ============== USER MANAGEMENT - FACILITATORS ==============

/**
 * Add Facilitator
 */
function addFacilitator($conn, $id_number, $name, $email, $position, $employment_status, $adminPassword = null) {
    if (!empty($adminPassword) && strlen($adminPassword) < 8) {
        return ['success' => false, 'message' => '❌ Password must be at least 8 characters long'];
    }

    $checkStmt = $conn->prepare("SELECT id FROM facilitators WHERE id_number = ?");
    $checkStmt->bind_param("s", $id_number);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ ID Number already exists'];
    }
    $checkStmt->close();

    $checkStmt = $conn->prepare("SELECT id FROM facilitators WHERE LOWER(name) = LOWER(?)");
    $checkStmt->bind_param("s", $name);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ Facilitator name already exists'];
    }
    $checkStmt->close();

    if (!empty($email)) {
        $checkStmt = $conn->prepare("SELECT id FROM facilitators WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => '❌ Email already exists'];
        }
        $checkStmt->close();
    }

    ensurePasswordColumn($conn, 'facilitators');
    $plainPassword  = !empty($adminPassword) ? $adminPassword : generateRandomPassword(10);
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO facilitators (id_number, name, email, position, employment_status, password) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $id_number, $name, $email, $position, $employment_status, $hashedPassword);

    if ($stmt->execute()) {
        $stmt->close();
        if (!empty($email)) {
            sendUserNotification($email, 'Facilitator', $id_number, $name, $plainPassword);
        }
        // ✅ FIX: return all fields needed for welcome email
        return [
            'success'   => true,
            'message'   => '✅ Facilitator added successfully',
            'email'     => $email,
            'name'      => $name,
            'id_number' => $id_number,
            'password'  => $plainPassword,
        ];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => '❌ Error adding facilitator'];
    }
}

/**
 * Edit Facilitator
 */
function editFacilitator($conn, $facilitator_id, $id_number, $name, $email, $position, $employment_status, $newPassword = null) {
    if (!empty($newPassword) && strlen($newPassword) < 8) {
        return ['success' => false, 'message' => '❌ Password must be at least 8 characters long'];
    }

    $checkStmt = $conn->prepare("SELECT id FROM facilitators WHERE id_number = ? AND id != ?");
    $checkStmt->bind_param("si", $id_number, $facilitator_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ ID Number already exists'];
    }
    $checkStmt->close();

    $checkStmt = $conn->prepare("SELECT id FROM facilitators WHERE LOWER(name) = LOWER(?) AND id != ?");
    $checkStmt->bind_param("si", $name, $facilitator_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ Facilitator name already exists'];
    }
    $checkStmt->close();

    if (!empty($email)) {
        $checkStmt = $conn->prepare("SELECT id FROM facilitators WHERE email = ? AND id != ?");
        $checkStmt->bind_param("si", $email, $facilitator_id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => '❌ Email already exists'];
        }
        $checkStmt->close();
    }

    $stmt = $conn->prepare("UPDATE facilitators SET id_number = ?, name = ?, email = ?, position = ?, employment_status = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $id_number, $name, $email, $position, $employment_status, $facilitator_id);

    if ($stmt->execute()) {
        $stmt->close();
        if (!empty($newPassword)) {
            setUserPassword($conn, 'facilitator', $facilitator_id, $newPassword);
        }
        return ['success' => true, 'message' => '✅ Facilitator updated successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => '❌ Error updating facilitator'];
    }
}

/**
 * Archive Facilitator
 */
function archiveFacilitator($conn, $facilitator_id) {
    $stmt = $conn->prepare("UPDATE facilitators SET archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $facilitator_id);

    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => '✅ Facilitator archived successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => '❌ Error archiving facilitator'];
    }
}

/**
 * Get All Facilitators (active only)
 */
function getAllFacilitators($conn, $includeArchived = false) {
    $query = $includeArchived
        ? "SELECT * FROM facilitators ORDER BY name ASC"
        : "SELECT * FROM facilitators WHERE archived = 0 ORDER BY name ASC";
    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

// ============== USER MANAGEMENT - DETAINEES ==============

/**
 * Add Detainee
 */
function addDetainee($conn, $id_number, $name, $email, $grade_level, $school = null, $adminPassword = null) {
    if (!empty($adminPassword) && strlen($adminPassword) < 8) {
        return ['success' => false, 'message' => '❌ Password must be at least 8 characters long'];
    }

    if (empty($school)) {
        return ['success' => false, 'message' => '❌ School is required for students'];
    }

    $checkStmt = $conn->prepare("SELECT id FROM detainees WHERE id_number = ?");
    $checkStmt->bind_param("s", $id_number);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ ID Number already exists'];
    }
    $checkStmt->close();

    $checkStmt = $conn->prepare("SELECT id FROM detainees WHERE LOWER(name) = LOWER(?)");
    $checkStmt->bind_param("s", $name);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ Detainee name already exists'];
    }
    $checkStmt->close();

    if (!empty($email)) {
        $checkStmt = $conn->prepare("SELECT id FROM detainees WHERE email = ?");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => '❌ Email already exists'];
        }
        $checkStmt->close();
    }

    ensurePasswordColumn($conn, 'detainees');
    ensureSchoolColumn($conn);
    $plainPassword  = !empty($adminPassword) ? $adminPassword : generateRandomPassword(8);
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    if (hasColumn($conn, 'detainees', 'school')) {
        $stmt = $conn->prepare("INSERT INTO detainees (id_number, name, email, grade_level, school, password) VALUES (?, ?, NULLIF(?,''), ?, ?, ?)");
        $stmt->bind_param("ssssss", $id_number, $name, $email, $grade_level, $school, $hashedPassword);
    } else {
        $stmt = $conn->prepare("INSERT INTO detainees (id_number, name, email, grade_level, password) VALUES (?, ?, NULLIF(?,''), ?, ?)");
        $stmt->bind_param("sssss", $id_number, $name, $email, $grade_level, $hashedPassword);
    }

    if ($stmt->execute()) {
        $stmt->close();
        if (!empty($email)) {
            sendUserNotification($email, 'Student', $id_number, $name, $plainPassword);
        }
        // ✅ FIX: return all fields needed for welcome email
        return [
            'success'   => true,
            'message'   => '✅ Student added successfully',
            'email'     => $email,
            'name'      => $name,
            'id_number' => $id_number,
            'password'  => $plainPassword,
        ];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => '❌ Error adding detainee'];
    }
}

/**
 * Edit Detainee
 */
function editDetainee($conn, $detainee_id, $id_number, $name, $email, $grade_level, $school = null, $newPassword = null) {
    if (!empty($newPassword) && strlen($newPassword) < 8) {
        return ['success' => false, 'message' => '❌ Password must be at least 8 characters long'];
    }

    if (empty($school)) {
        return ['success' => false, 'message' => '❌ School is required for students'];
    }

    $checkStmt = $conn->prepare("SELECT id FROM detainees WHERE id_number = ? AND id != ?");
    $checkStmt->bind_param("si", $id_number, $detainee_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ ID Number already exists'];
    }
    $checkStmt->close();

    $checkStmt = $conn->prepare("SELECT id FROM detainees WHERE LOWER(name) = LOWER(?) AND id != ?");
    $checkStmt->bind_param("si", $name, $detainee_id);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => '❌ Detainee name already exists'];
    }
    $checkStmt->close();

    if (!empty($email)) {
        $checkStmt = $conn->prepare("SELECT id FROM detainees WHERE email = ? AND id != ?");
        $checkStmt->bind_param("si", $email, $detainee_id);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => '❌ Email already exists'];
        }
        $checkStmt->close();
    }

    ensureSchoolColumn($conn);
    if (hasColumn($conn, 'detainees', 'school')) {
        $stmt = $conn->prepare("UPDATE detainees SET id_number = ?, name = ?, email = NULLIF(?,''), grade_level = ?, school = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $id_number, $name, $email, $grade_level, $school, $detainee_id);
    } else {
        $stmt = $conn->prepare("UPDATE detainees SET id_number = ?, name = ?, email = NULLIF(?,''), grade_level = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $id_number, $name, $email, $grade_level, $detainee_id);
    }

    if ($stmt->execute()) {
        $stmt->close();
        if (!empty($newPassword)) {
            setUserPassword($conn, 'detainee', $detainee_id, $newPassword);
        }
        return ['success' => true, 'message' => '✅ Detainee updated successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => '❌ Error updating detainee'];
    }
}

/**
 * Archive Detainee
 */
function archiveDetainee($conn, $detainee_id) {
    $stmt = $conn->prepare("UPDATE detainees SET archived = 1 WHERE id = ?");
    $stmt->bind_param("i", $detainee_id);

    if ($stmt->execute()) {
        $stmt->close();
        return ['success' => true, 'message' => '✅ Detainee archived successfully'];
    } else {
        $stmt->close();
        return ['success' => false, 'message' => '❌ Error archiving detainee'];
    }
}

/**
 * Get All Detainees (active only)
 */
function getAllDetainees($conn, $includeArchived = false) {
    $query = $includeArchived
        ? "SELECT * FROM detainees ORDER BY name ASC"
        : "SELECT * FROM detainees WHERE archived = 0 ORDER BY name ASC";
    $result = $conn->query($query);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Bulk-generate passwords for users who don't have one and email them.
 */
function bulkGenerateMissingPasswords($conn) {
    $results = ['teachers' => 0, 'facilitators' => 0, 'detainees' => 0];

    // Teachers
    if (hasColumn($conn, 'teachers', 'email')) {
        ensurePasswordColumn($conn, 'teachers');
        $res = $conn->query("SELECT id, id_number, name, email, password FROM teachers WHERE archived = 0");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                if (empty($r['password']) && !empty($r['email'])) {
                    $pw   = generateRandomPassword(10);
                    $hash = password_hash($pw, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE teachers SET password = ? WHERE id = ?");
                    $stmt->bind_param('si', $hash, $r['id']);
                    if ($stmt->execute()) {
                        sendUserNotification($r['email'], 'Teacher', $r['id_number'], $r['name'], $pw);
                        $results['teachers']++;
                    }
                    $stmt->close();
                }
            }
        }
    }

    // Facilitators
    if (hasColumn($conn, 'facilitators', 'email')) {
        ensurePasswordColumn($conn, 'facilitators');
        $res = $conn->query("SELECT id, id_number, name, email, password FROM facilitators WHERE archived = 0");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                if (empty($r['password']) && !empty($r['email'])) {
                    $pw   = generateRandomPassword(10);
                    $hash = password_hash($pw, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE facilitators SET password = ? WHERE id = ?");
                    $stmt->bind_param('si', $hash, $r['id']);
                    if ($stmt->execute()) {
                        sendUserNotification($r['email'], 'Facilitator', $r['id_number'], $r['name'], $pw);
                        $results['facilitators']++;
                    }
                    $stmt->close();
                }
            }
        }
    }

    // Detainees
    if (hasColumn($conn, 'detainees', 'email')) {
        ensurePasswordColumn($conn, 'detainees');
        $res = $conn->query("SELECT id, id_number, name, email, password FROM detainees WHERE archived = 0");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                if (empty($r['password']) && !empty($r['email'])) {
                    $pw   = generateRandomPassword(8);
                    $hash = password_hash($pw, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE detainees SET password = ? WHERE id = ?");
                    $stmt->bind_param('si', $hash, $r['id']);
                    if ($stmt->execute()) {
                        sendUserNotification($r['email'], 'Detainee', $r['id_number'], $r['name'], $pw);
                        $results['detainees']++;
                    }
                    $stmt->close();
                }
            }
        }
    }

    return ['success' => true, 'message' => 'Passwords generated', 'details' => $results];
}

/** Password reset token table and helpers */
function ensurePasswordResetsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_role VARCHAR(32) NOT NULL,
        user_id INT NOT NULL,
        token VARCHAR(128) NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    return $conn->query($sql);
}

function createPasswordResetToken($conn, $role, $user_id) {
    ensurePasswordResetsTable($conn);
    $token   = bin2hex(random_bytes(24));
    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
    $stmt    = $conn->prepare("INSERT INTO password_resets (user_role, user_id, token, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('siss', $role, $user_id, $token, $expires);
    if ($stmt->execute()) {
        $stmt->close();
        return $token;
    }
    return false;
}

function sendPasswordResetEmail($toEmail, $name, $token) {
    if (empty($toEmail) || empty($token)) return false;
    if (!file_exists('config_email.php')) {
        $from = 'no-reply@localhost';
    } else {
        include 'config_email.php';
        $from = defined('MAIL_FROM_EMAIL') ? MAIL_FROM_EMAIL : 'no-reply@localhost';
    }
    $link    = (isset($_SERVER['HTTP_HOST']) ? ('http://' . $_SERVER['HTTP_HOST']) : 'http://localhost') . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . urlencode($token);
    $subject = 'Tanglaw LMS - Password Reset';
    $body    = "<p>Hello " . htmlspecialchars($name) . ",</p>";
    $body   .= "<p>Click the link below to reset your password (valid 1 hour):<br><a href=\"" . htmlspecialchars($link) . "\">Reset password</a></p>";
    $body   .= "<p>If you did not request this, ignore this email.</p>";

    if (file_exists('vendor/autoload.php')) {
        return sendViaPHPMailer($toEmail, $subject, $body);
    }
    $headers  = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= "From: Tanglaw LMS <" . $from . ">" . "\r\n";
    return @mail($toEmail, $subject, $body, $headers);
}

function setUserPassword($conn, $role, $user_id, $plainPassword) {
    if (strlen($plainPassword) < 8) {
        return false;
    }
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    if ($role === 'teacher') {
        $stmt = $conn->prepare("UPDATE teachers SET password = ? WHERE id = ?");
    } elseif ($role === 'facilitator') {
        $stmt = $conn->prepare("UPDATE facilitators SET password = ? WHERE id = ?");
    } elseif ($role === 'detainee') {
        $stmt = $conn->prepare("UPDATE detainees SET password = ? WHERE id = ?");
    } else {
        return false;
    }
    $stmt->bind_param('si', $hash, $user_id);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}