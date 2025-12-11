<?php
include 'conn.php';
include 'header.php';
echo '<div class="sidebar-backdrop" id="sidebarBackdrop"></div>';
include 'sidebar.php';
echo '<div class="main-content">';

$studentGrade = $loggedUser['grade_level'];

// Resolve grade text to grade_levels.id
$gradeId = null;
$gstmt = $conn->prepare("SELECT id FROM grade_levels WHERE level = ? LIMIT 1");
if ($gstmt) {
    $gstmt->bind_param("s", $studentGrade);
    $gstmt->execute();
    $gres = $gstmt->get_result();
    if ($gres && $gr = $gres->fetch_assoc()) { $gradeId = (int)$gr['id']; }
    $gstmt->close();
}

// Get modules for this student's grade level using prepared statement (by id)
$result = null;
if ($gradeId !== null) {
    $stmt = $conn->prepare("SELECT id, title, file_path FROM modules WHERE grade_level_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $gradeId);
        $stmt->execute();
        $result = $stmt->get_result();
    }
}
if ($result === null) {
    // empty result-like object
    $result = new class { public $num_rows = 0; public function fetch_assoc(){return false;} };
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
    .page-header + div {
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
    .section .card { margin-top:20px; margin-bottom:20px }
    .section .grid { margin-top:20px; margin-bottom:20px }
    /* Center grid items and constrain module cards so they don't stretch */
    .section.active .grid { justify-content: center; }
    .module-card { max-width: 420px; margin: 0 auto; }
</style>

<div class="page-header">
    <h1>📘 My Modules</h1>
</div>

<div class="container" style="margin-top: 100px;">
    <div style="display:flex; gap:12px; align-items:center; margin-bottom:12px;">
        <a class="btn" href="student_dashboard.php">🧭 Go to Dashboard</a>
        <a class="btn secondary" href="my_submissions.php">📥 View my Activity Submissions</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="grid">
        <?php while($row = $result->fetch_assoc()): ?>
            <div class="module-card card">
                <strong><?= htmlspecialchars($row['title']); ?></strong><br>
                <a href="<?= htmlspecialchars($row['file_path']); ?>" target="_blank">📖 Read Module</a>
                <br>
                <a class="btn" href="submit_activity.php?module_id=<?= $row['id'] ?>">📨 Submit Activity Sheet</a>
            </div>
        <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No modules available for your grade level.</p>
    <?php endif; ?>
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
</script><?php include 'footer.php'; ?>
