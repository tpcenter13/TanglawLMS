<?php
include 'conn.php';

if (!isset($_SESSION['loggedUser'])) {
    header("Location: login.php");
    exit();
}

$loggedUser = $_SESSION['loggedUser'];
$studentId = (int)$loggedUser['id'];

include 'header.php';
include 'sidebar.php';

$resCheck = $conn->query("SHOW TABLES LIKE 'activity_submissions'");
if ($resCheck && $resCheck->num_rows > 0) {
    $stmt = $conn->prepare("SELECT a.id, a.module_id, a.file_path, a.comments, a.status, a.grade, a.submitted_at, m.title AS module_title
        FROM activity_submissions a
        LEFT JOIN modules m ON m.id = a.module_id
        WHERE a.student_id = ?
        ORDER BY a.submitted_at DESC");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = new class { public $num_rows = 0; public function fetch_assoc(){ return false; } };
}
?>

<div class="student-header">
    <div class="student-header-content">
        <div>
            <h1>My Activity Submissions</h1>
            <p class="header-meta">Track your latest uploads</p>
        </div>
        <div>
            <a href="student_modules.php">Modules</a>
        </div>
    </div>
</div>

<div class="main-container">
    <h2 class="section-title">All Submissions</h2>

    <div class="submissions-card">
        <?php if ($res->num_rows > 0): ?>
            <div class="table-wrap">
                <table class="submissions-table">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>File</th>
                            <th>Comments</th>
                            <th>Status</th>
                            <th>Grade</th>
                            <th>Submitted At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($r = $res->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($r['module_title']); ?></td>
                                <td><a href="<?php echo htmlspecialchars($r['file_path']); ?>" target="_blank">View File</a></td>
                                <td><?php echo nl2br(htmlspecialchars($r['comments'])); ?></td>
                                <td>
                                    <?php
                                        $statusLabel = ucfirst($r['status']);
                                        if ($r['status'] === 'submitted') $statusLabel = 'Awaiting Teacher';
                                    ?>
                                    <span class="status-<?php echo htmlspecialchars($r['status']); ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($r['grade'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($r['submitted_at']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p>No submissions found. Start submitting your activities!</p>
            </div>
        <?php endif; ?>
    </div>

    <a href="student_modules.php" class="back-link">Back to Modules</a>
</div>

<script>
function toggleSidebar() {
    const body = document.body;
    const backdrop = document.getElementById('sidebarBackdrop');

    if (window.innerWidth <= 900) {
        body.classList.toggle('sidebar-open');
    } else {
        body.classList.toggle('sidebar-collapsed');
    }

    if (backdrop) {
        backdrop.style.display = body.classList.contains('sidebar-open') ? 'block' : 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.body.classList.remove('sidebar-open');
    document.body.classList.remove('sidebar-collapsed');
});
</script>

<?php include 'footer.php'; ?>
