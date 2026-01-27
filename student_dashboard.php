<?php
include 'conn.php';
include 'header.php';
session_start();

// FIX: Ensure logged user exists
if (!isset($_SESSION['loggedUser'])) {
    header("Location: login.php");
    exit();
}

$loggedUser = $_SESSION['loggedUser'];
$student = $loggedUser;
$studentId = $student['id'];
$grade = $student['grade_level'];

include 'sidebar.php';

// Resolve grade_level text to grade_levels.id (modules use grade_level_id FK)
$gradeId = null;
$gstmt = $conn->prepare("SELECT id FROM grade_levels WHERE level = ? LIMIT 1");
if ($gstmt) {
    $gstmt->bind_param("s", $grade);
    $gstmt->execute();
    $gres = $gstmt->get_result();
    if ($gres && $rowg = $gres->fetch_assoc()) {
        $gradeId = (int)$rowg['id'];
    }
    $gstmt->close();
}

// Check if activity_submissions table exists
$hasSubmissionsTable = false;
$resCheck = $conn->query("SHOW TABLES LIKE 'activity_submissions'");
if ($resCheck && $resCheck->num_rows > 0) {
    $hasSubmissionsTable = true;
}

// Modules count and list
$moduleCount = 0;
$modules = null;
if ($gradeId !== null) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM modules WHERE grade_level_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $gradeId);
        $stmt->execute();
        $countRes = $stmt->get_result()->fetch_assoc();
        $moduleCount = $countRes['cnt'] ?? 0;
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT id, title, file_path FROM modules WHERE grade_level_id = ? ORDER BY id DESC LIMIT 5");
    if ($stmt) {
        $stmt->bind_param("i", $gradeId);
        $stmt->execute();
        $modules = $stmt->get_result();
    }
} else {
    $modules = new class { public $num_rows = 0; public function fetch_assoc(){return false;} };
}

// Module progress statistics
$completedCount = 0;
$inProgressCount = 0;
if ($gradeId !== null) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM module_progress WHERE student_id = ? AND status = 'completed'");
    if ($stmt) {
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $completedCount = $res['cnt'] ?? 0;
        $stmt->close();
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM module_progress WHERE student_id = ? AND status IN ('reading', 'completed')");
    if ($stmt) {
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $inProgressCount = $res['cnt'] ?? 0;
        $stmt->close();
    }
}

// Submissions count and recent
$submissionCount = 0;
$recentSubmissions = [];
if ($hasSubmissionsTable) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM activity_submissions WHERE student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $sc = $stmt->get_result()->fetch_assoc();
    $submissionCount = $sc['cnt'] ?? 0;

    $stmt = $conn->prepare("SELECT a.id, a.module_id, a.file_path, a.comments, a.status, a.submitted_at, m.title AS module_title
        FROM activity_submissions a
        LEFT JOIN modules m ON m.id = a.module_id
        WHERE a.student_id = ?
        ORDER BY a.submitted_at DESC
        LIMIT 5");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $recentSubmissions = $stmt->get_result();
}

echo '<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>';
?>

<style>
/* ===== GLOBAL ===== */
body {
    background: #f3f4f6;
    margin: 0;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
}

/* ===== FIXED HEADER (RESPECTS SIDEBAR) ===== */
.student-header {
    position: fixed;
    top: 0;
    left: 260px;
    width: calc(100% - 260px);
    background: linear-gradient(135deg, #f59e0b, #f97316);
    color: white;
    padding: 18px 32px;
    z-index: 150;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.student-header-content {
    max-width: 1400px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.student-header h1 {
    margin: 0;
    font-size: 28px;
    font-weight: 800;
}

.student-header p {
    margin: 0;
    font-size: 14px;
    opacity: 0.95;
}

.student-header a {
    color: white;
    text-decoration: none;
    font-weight: 600;
}

.student-header a:hover {
    text-decoration: underline;
}

/* ===== MAIN CONTAINER ===== */
.main-container {
    padding: 120px 32px 64px;
    max-width: 1400px;
    margin: 0 auto;
}

/* ===== SECTION TITLES ===== */
.section-title {
    font-size: 22px;
    font-weight: 700;
    margin: 40px 0 20px;
    color: #1f2937;
    border-bottom: 3px solid #f59e0b;
    padding-bottom: 8px;
}

.section-title:first-of-type {
    margin-top: 0;
}

/* ===== STATS GRID ===== */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 24px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,.12);
}

.stat-card .kpi {
    font-size: 36px;
    font-weight: 900;
    background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 8px;
}

.stat-card .label {
    font-size: 13px;
    color: #6b7280;
    font-weight: 600;
}

/* ===== CONTENT GRID ===== */
.content-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.card {
    background: #fff;
    border-radius: 14px;
    padding: 28px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
}

.card h3 {
    margin: 0 0 20px 0;
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    padding-bottom: 12px;
    border-bottom: 2px solid #f59e0b;
}

.card ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

.card li {
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f3f4f6;
}

.card li:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.card li strong {
    color: #1f2937;
    display: block;
    margin-bottom: 8px;
    font-size: 15px;
}

.card p {
    margin: 0;
    color: #6b7280;
    line-height: 1.6;
}

.button-secondary {
    display: inline-block;
    margin: 4px 8px 4px 0;
    padding: 6px 14px;
    background: #dbeafe;
    color: #1e40af;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s;
}

.button-secondary:hover {
    background: #bfdbfe;
}

.button-action {
    display: inline-block;
    margin-top: 16px;
    padding: 10px 18px;
    background: #f59e0b;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: background 0.2s;
}

.button-action:hover {
    background: #d97706;
}

.submission-item {
    padding: 14px;
    background: #f9fafb;
    border-radius: 8px;
    border-left: 4px solid #f59e0b;
    margin-bottom: 12px;
}

.submission-item:last-child {
    margin-bottom: 0;
}

.submission-item strong {
    color: #1f2937;
    display: block;
    margin-bottom: 6px;
    font-size: 14px;
}

.submission-item small {
    color: #9ca3af;
    display: block;
    margin-top: 6px;
    font-size: 12px;
}

.status-pending {
    color: #f59e0b;
    font-weight: 600;
}

.status-approved {
    color: #10b981;
    font-weight: 600;
}

.status-rejected {
    color: #ef4444;
    font-weight: 600;
}

/* ===== RESPONSIVE FIX ===== */
@media (max-width: 900px) {
    .student-header {
        left: 0;
        width: 100%;
    }
    .main-container {
        padding: 110px 16px 48px;
    }
    .content-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- ===== HEADER ===== -->
<div class="student-header">
    <div class="student-header-content">
        <h1>🎓 Tanglaw Learn</h1>
        <p>Welcome, <?= htmlspecialchars($student['name']) ?> | <a href="logout.php">Logout</a></p>
    </div>
</div>

<div class="main-container">

    <!-- Learning Materials -->
    <h2 class="section-title">📚 Learning Materials</h2>
    <div class="content-grid">
        <div class="card">
            <h3>📖 Recent Modules</h3>
            <?php if ($modules && $modules->num_rows > 0): ?>
                <ul>
                    <?php while ($m = $modules->fetch_assoc()): ?>
                        <li>
                            <strong><?= htmlspecialchars($m['title']) ?></strong>
                            <a href="<?= htmlspecialchars($m['file_path']) ?>" target="_blank" class="button-secondary">📖 Read</a>
                            <a href="submit_activity.php?module_id=<?= $m['id'] ?>" class="button-secondary">📝 Submit</a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No modules available for your grade level yet.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>📤 Recent Submissions</h3>
            <?php if (!$hasSubmissionsTable): ?>
                <p>No submissions yet. Get started now!</p>
                <a href="submit_activity.php" class="button-action">Submit Your First Activity →</a>
            <?php elseif ($recentSubmissions && $recentSubmissions->num_rows > 0): ?>
                <ul style="list-style: none; padding: 0; margin: 0;">
                    <?php while ($s = $recentSubmissions->fetch_assoc()): ?>
                        <li class="submission-item">
                            <strong><?= htmlspecialchars($s['module_title'] ?? 'Unknown Module') ?></strong>
                            <div>Status: <span class="status-<?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars(ucfirst($s['status'])) ?></span></div>
                            <small><?= htmlspecialchars($s['submitted_at']) ?></small>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No submissions yet.</p>
                <a href="submit_activity.php" class="button-action">Get Started →</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="kpi"><?= $completedCount ?></div>
        <div class="label">✅ Completed Modules</div>
    </div>
    <div class="card">
        <div class="kpi"><?= round($moduleCount > 0 ? ($completedCount / $moduleCount) * 100 : 0) ?>%</div>
        <div class="label">📊 Progress</div>
    </div>

    <!-- Quick Overview -->
    <h2 class="section-title">📊 Quick Overview</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="kpi"><?= $moduleCount ?></div>
            <div class="label">📘 Modules Available</div>
        </div>
        <div class="stat-card">
            <div class="kpi"><?= $submissionCount ?></div>
            <div class="label">📨 Submissions</div>
        </div>
    </div>

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