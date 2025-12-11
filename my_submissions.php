<?php
include 'conn.php';
include 'header.php';
echo '<div class="sidebar-backdrop" id="sidebarBackdrop"></div>';
include 'sidebar.php';
echo '<div class="main-content">';

$studentId = $loggedUser['id'];

// If the submissions table doesn't exist yet, avoid fatal error and show empty result
$resCheck = $conn->query("SHOW TABLES LIKE 'activity_submissions'");
if ($resCheck && $resCheck->num_rows > 0) {
    $stmt = $conn->prepare("SELECT a.id, a.module_id, a.file_path, a.comments, a.status, a.submitted_at, m.title AS module_title
    FROM activity_submissions a
    LEFT JOIN modules m ON m.id = a.module_id
    WHERE a.student_id = ?
    ORDER BY a.submitted_at DESC");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    // create a simple empty result-like object to keep template logic working
    $res = new class { public $num_rows = 0; public function fetch_assoc(){ return false; } };
}
?>

<style>
    .page-header {
        background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
        color: white;
        padding: 20px;
        border-radius: 0;
        margin: 0 0 0 260px;
        width: calc(100% - 260px);
        position: fixed;
        top: 0;
        left: 0;
        z-index: 300;
    }
    .page-header h1 { margin: 0; font-size: 28px; font-weight: 700; }
    .page-header + .container {
        margin-top: 120px;
        margin-left: 260px;
        max-width: 1200px;
        margin-right: auto;
        padding: 0 20px;
    }
    /* Sections / cards spacing (match other dashboards) */
    .section { display: none; }
    .section.active { display: block; max-width: 100%; margin: 0; }
    .section.active .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap:20px; margin-bottom:24px; }
    .section.active .grid .card { background:white; padding:24px; border-radius:8px; border:1px solid #e5e7eb; box-shadow:0 1px 3px rgba(0,0,0,0.05);} 
    .section h2 { margin-top:0; margin-bottom:24px }
    .section .card { margin-top:120px; margin-bottom:20px }
    .section .grid { margin-top:120px; margin-bottom:20px }
</style>

<div class="page-header">
    <h1>📥 My Activity Submissions</h1>
</div>

<div class="container" style="margin-top: 100px;">
    <?php if ($res->num_rows > 0): ?>
        <table class="card" border="0" cellpadding="8" style="width:100%; max-width:800px; border-collapse:collapse;">
            <tr style="background:#f7f7f7; font-weight:bold;"><td>Module</td><td>File</td><td>Comments</td><td>Status</td><td>Submitted At</td></tr>
            <?php while ($r = $res->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['module_title']); ?></td>
                    <td><a href="<?php echo htmlspecialchars($r['file_path']); ?>" target="_blank">View</a></td>
                    <td><?php echo nl2br(htmlspecialchars($r['comments'])); ?></td>
                    <td><?php echo htmlspecialchars($r['status']); ?></td>
                    <td><?php echo htmlspecialchars($r['submitted_at']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No submissions found.</p>
    <?php endif; ?>

    <hr>
    <p><a href="student_modules.php">← Back to Modules</a></p>
</div>

<?php include 'footer.php'; ?>
</div>
<script>
function toggleSidebar() {
    document.body.classList.toggle('sidebar-open');
}
document.getElementById('sidebarBackdrop')?.addEventListener('click', function() {
    document.body.classList.remove('sidebar-open');
});
</script>
