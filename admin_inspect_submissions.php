<?php
include 'conn.php';

// admin-only page
if (!isset($_SESSION['loggedUser']) || ($_SESSION['loggedUser']['role'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo "Access denied.";
    exit();
}

// Ensure table exists (conn.php already creates it if missing)
$res = $conn->query("SELECT s.id, s.student_id, s.module_id, s.file_path, s.comments, s.status, s.submitted_at, det.name AS detainee_name, m.title AS module_title
    FROM activity_submissions s
    LEFT JOIN detainees det ON det.id = s.student_id
    LEFT JOIN modules m ON m.id = s.module_id
    ORDER BY s.submitted_at DESC LIMIT 500");

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin — Inspect Submissions</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>.small{font-size:13px;color:#6b7280}</style>
</head>
<body>
<div class="container">
    <h2>🔎 Activity Submissions (last 500)</h2>
    <?php if (!$res || $res->num_rows == 0): ?>
        <p>No submissions found.</p>
    <?php else: ?>
        <table>
            <thead><tr><th>ID</th><th>Detainee</th><th>Module</th><th>File</th><th>Status</th><th>Comments</th><th>Submitted At</th></tr></thead>
            <tbody>
            <?php while ($r = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['id']) ?></td>
                    <td><?= htmlspecialchars($r['detainee_name'] ?? 'Unknown') ?></td>
                    <td><?= htmlspecialchars($r['module_title'] ?? 'Unknown') ?></td>
                    <td><?php if (!empty($r['file_path'])): ?><a href="<?= htmlspecialchars($r['file_path']) ?>" target="_blank">View</a><?php endif; ?></td>
                    <td><?= htmlspecialchars($r['status']) ?></td>
                    <td><?= nl2br(htmlspecialchars($r['comments'])) ?></td>
                    <td><?= htmlspecialchars($r['submitted_at']) ?></td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p style="margin-top:12px;"><a href="admin_dashboard.php">← Back to Admin</a></p>
</div>
</body>
</html>
