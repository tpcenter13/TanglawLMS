<?php
require_once __DIR__ . '/conn.php';
require_once __DIR__ . '/admin_functions_users.php';

$token = $_GET['token'] ?? null;
$message = '';

if (!$token) {
    $message = 'Missing token.';
} else {
    $conn = open_db();
    ensurePasswordResetsTable($conn);
    $stmt = $conn->prepare("SELECT id, role, user_id, expires_at, used FROM password_resets WHERE token = ? LIMIT 1");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    if (!$row) {
        $message = 'Invalid token.';
    } else if ($row['used']) {
        $message = 'This reset link has already been used.';
    } else if (strtotime($row['expires_at']) < time()) {
        $message = 'This reset link has expired.';
    } else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        if (strlen($password) < 6) {
            $message = 'Password must be at least 6 characters.';
        } else if ($password !== $password2) {
            $message = 'Passwords do not match.';
        } else {
            $ok = setUserPassword($conn, $row['role'], $row['user_id'], $password);
            if ($ok) {
                $u = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
                $u->bind_param('i', $row['id']);
                $u->execute();
                $message = 'Password updated successfully. You may now login.';
            } else {
                $message = 'Failed to set password.';
            }
        }
    }
    $stmt->close();
    $conn->close();
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reset password</title>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;padding:24px}
        .box{max-width:520px;margin:30px auto;background:#fff;padding:18px;border-radius:8px;border:1px solid #e5e7eb}
        input{display:block;width:100%;padding:8px;margin:8px 0;border:1px solid #cbd5e1;border-radius:6px}
        button{background:#2563eb;color:#fff;padding:8px 12px;border-radius:6px;border:0}
        .msg{margin:10px 0;color:#111}
    </style>
</head>
<body>
<div class="box">
    <h2>Reset Password</h2>
    <?php if ($message): ?>
        <div class="msg"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($token && empty($message) || ($_SERVER['REQUEST_METHOD']==='GET' && !$message)): ?>
    <form method="POST">
        <label>New password</label>
        <input type="password" name="password" required>
        <label>Confirm password</label>
        <input type="password" name="password2" required>
        <button type="submit">Set password</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
