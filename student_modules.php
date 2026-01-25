<?php
include 'conn.php';
include 'header.php';

// --- BAGONG CODE DITO (FIX para sa Line 8) ---
// I-check kung may laman ang $loggedUser at kung ito ay isang array.
// Kung wala, itapon ang user sa login page o magbigay ng default value.
if (!isset($loggedUser) || !is_array($loggedUser)) {
    // Kung hindi naka-log in ang user, i-redirect sila
    // Palitan ang 'login.php' ng tamang path mo.
    // header('Location: login.php');
    // exit;

    // O kaya, magbigay ng 'default' na grade level para makita ang page (DEPENDS sa gusto mo)
    // Tandaan: Ang paggamit ng 'header' redirect ang mas ligtas.
    $loggedUser = ['grade_level' => 'Default Grade (e.g., Grade 7)']; 
}
// --- END NG BAGONG CODE ---

echo '<div class="sidebar-backdrop" id="sidebarBackdrop"></div>';
include 'sidebar.php';
echo '<div class="main-content">';

// HINDI NA MAG-WA-WARNING DITO dahil may check na sa taas
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
/* ... (Style code mo) ... */
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
</script>