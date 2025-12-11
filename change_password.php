<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/admin_functions_users.php';

$message = '';
$messageType = '';

// Check if user is logged in
if (!isset($_SESSION['loggedUser'])) {
    header("Location: login.php");
    exit();
}

$loggedUser = $_SESSION['loggedUser'];
$role = $loggedUser['role'];
$userId = $loggedUser['id'];
$userName = $loggedUser['name'] ?? 'User';

// Handle password change submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $newPassword2 = $_POST['new_password2'] ?? '';

    // Validate inputs
    if (empty($currentPassword)) {
        $message = 'Current password is required.';
        $messageType = 'error';
    } elseif (empty($newPassword)) {
        $message = 'New password is required.';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = 'New password must be at least 6 characters.';
        $messageType = 'error';
    } elseif ($newPassword !== $newPassword2) {
        $message = 'New passwords do not match.';
        $messageType = 'error';
    } elseif ($currentPassword === $newPassword) {
        $message = 'New password must be different from current password.';
        $messageType = 'error';
    } else {
        // Get user's current password hash based on role
        $table = '';
        if ($role === 'teacher') {
            $table = 'teachers';
        } elseif ($role === 'facilitator') {
            $table = 'facilitators';
        } elseif ($role === 'detainee') {
            $table = 'detainees';
        } elseif ($role === 'admin') {
            // For admin, check hardcoded credentials
            // Admin login uses hardcoded 'admin / admin123' as default
            // For now, reject password change since it's hardcoded
            $message = '❌ Admin credentials are hardcoded. Contact system administrator to change admin password.';
            $messageType = 'error';
            $table = '';
        }

        if (!empty($table)) {
            $stmt = $conn->prepare("SELECT password FROM $table WHERE id = ? LIMIT 1");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            $user = $res->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($currentPassword, $user['password'])) {
                // Current password is correct, update to new password
                $hashedNewPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE $table SET password = ? WHERE id = ?");
                $updateStmt->bind_param('si', $hashedNewPassword, $userId);

                if ($updateStmt->execute()) {
                    $updateStmt->close();
                    
                    // Log the password change
                    $logStmt = $conn->prepare("INSERT INTO password_changes (role, user_id) VALUES (?, ?)");
                    $logStmt->bind_param('si', $role, $userId);
                    $logStmt->execute();
                    $logStmt->close();
                    
                    $message = '✅ Password changed successfully.';
                    $messageType = 'success';
                } else {
                    $message = '❌ Failed to update password.';
                    $messageType = 'error';
                    $updateStmt->close();
                }
            } else {
                $message = '❌ Current password is incorrect.';
                $messageType = 'error';
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Change Password - Tanglaw LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
            background: #f3f4f6;
            padding: 20px;
        }
        .container-change {
            max-width: 500px;
            margin: 40px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .container-change h2 {
            margin-top: 0;
            color: #1f2937;
            font-size: 24px;
            margin-bottom: 10px;
        }
        .container-change .subtitle {
            color: #6b7280;
            margin-bottom: 24px;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 18px;
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 6px;
            color: #374151;
            font-size: 14px;
        }
        .form-group input {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        button {
            background: #2563eb;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            width: 100%;
            margin-top: 8px;
        }
        button:hover {
            background: #1d4ed8;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #2563eb;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container-change">
    <h2>Change Password</h2>
    <p class="subtitle">Update your account password</p>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>

        <div class="form-group">
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required>
        </div>

        <div class="form-group">
            <label for="new_password2">Confirm New Password</label>
            <input type="password" id="new_password2" name="new_password2" required>
        </div>

        <button type="submit">Change Password</button>
    </form>

    <?php
    // Determine which dashboard to link back to
    $dashboardLink = '';
    if ($role === 'admin') {
        $dashboardLink = 'admin_dashboard.php';
    } elseif ($role === 'teacher') {
        $dashboardLink = 'teacher_dashboard.php';
    } elseif ($role === 'facilitator') {
        $dashboardLink = 'facilitator_dashboard.php';
    } elseif ($role === 'detainee') {
        $dashboardLink = 'student_dashboard.php';
    }
    ?>
    <?php if ($dashboardLink): ?>
        <a href="<?= $dashboardLink ?>" class="back-link">← Back to Dashboard</a>
    <?php endif; ?>
</div>

</body>
</html>
