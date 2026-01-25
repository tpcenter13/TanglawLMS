<?php
include 'conn.php';
include 'header.php';
session_start();

// FIX: Ensure logged user exists
if (!isset($_SESSION['loggedUser'])) {
    header("Location: login.php");
    exit();
}

$loggedUser = $_SESSION['loggedUser']; // retrieve the logged-in user

// include sidebar and wrap content so layout matches other dashboards
include 'sidebar.php';
echo "\n<div class=\"sidebar-backdrop\" id=\"sidebarBackdrop\" onclick=\"toggleSidebar()\" style=\"display:none\"></div>\n";
echo "<div class=\"main-content\">\n";

// Student header with gradient (at the top)
$student = $loggedUser;
$studentId = $student['id'];
$grade = $student['grade_level'];


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
// If we have a grade id, fetch modules for that grade; otherwise return none
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
    // no grade mapping found — no modules
    $modules = new class { public $num_rows = 0; public function fetch_assoc(){return false;} };
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

?>

<style>
    .student-header {
        background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
        color: white;
        padding: 12px 20px;
        border-radius: 0;
        margin: 0 0 0 260px;
        width: calc(100% - 260px);
        margin-bottom: 0;
        position: fixed;
        top: 0;
        left: 0;
        z-index: 300;
        height: 60px;
    }
    .student-header .container {
        max-width: 1200px;
        margin: 0 auto;
    }
    .student-header h1 { margin: 0 0 4px 0; font-size: 28px; font-weight: 700; }
    .student-header p { margin: 0; opacity: 0.95; font-size: 13px; }
    .student-header a { color: white; text-decoration: none; }
    .student-header a:hover { text-decoration: underline; }
    .main-content {
        padding-top: 100px;
    }
    /* Sections / cards spacing (match other dashboards) */
    .section { display: none; }
    .section.active { 
        display: block;
        max-width: 100%;
        margin: 0;
    }
    .section.active .grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }
    .section.active .grid .card {
        background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        padding: 24px;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .section.active .grid .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    .section h2 { margin-top: 0; margin-bottom: 24px; font-size: 22px; font-weight: 700; color: #1f2937; }
    .section .card { 
        background: white; 
        margin-top: 0; 
        margin-bottom: 20px; 
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    .section .grid { margin-top: 0; margin-bottom: 20px; }
    .kpi { font-size: 36px; font-weight: 800; margin-bottom: 8px; background: linear-gradient(135deg, #f59e0b 0%, #f97316 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
    .small { margin: 0; font-size: 13px; color: #6b7280; font-weight: 600; }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 24px; }
    .card { 
        background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
        padding: 24px; 
        border-radius: 12px; 
        border: 1px solid #e5e7eb; 
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    .card h3 { 
        margin: 0 0 16px 0; 
        font-size: 18px; 
        font-weight: 700;
        color: #1f2937;
        padding-bottom: 12px;
        border-bottom: 2px solid #f59e0b;
    }
    .card h4 { 
        margin: 0 0 14px 0; 
        font-size: 15px; 
        font-weight: 700;
        color: #374151;
    }
    .card ul { 
        margin: 0; 
        padding: 0; 
        list-style: none;
    }
    .card li { 
        margin-bottom: 14px; 
        line-height: 1.6; 
        font-size: 14px;
        color: #4b5563;
    }
    .card li strong {
        color: #1f2937;
        display: block;
        margin-bottom: 6px;
    }
    .card li small {
        display: block;
        color: #9ca3af;
        margin-top: 4px;
    }
    .card a { 
        color: #f59e0b; 
        text-decoration: none; 
        font-weight: 600; 
        font-size: 13px;
        transition: color 0.2s;
    }
    .card a:hover { 
        color: #d97706;
        text-decoration: underline;
    }
    .card p { 
        margin: 8px 0; 
        line-height: 1.6; 
        color: #6b7280; 
        font-size: 14px;
    }
    .button-action {
        display: inline-block; 
        margin-top: 12px; 
        padding: 10px 16px; 
        background: #f59e0b; 
        color: white; 
        border-radius: 6px; 
        text-decoration: none;
        font-weight: 600;
        font-size: 13px;
        transition: background 0.2s;
    }
    .button-action:hover {
        background: #d97706;
    }
    .button-secondary {
        display: inline-block; 
        margin: 6px 8px 6px 0; 
        padding: 6px 12px; 
        background: #dbeafe; 
        color: #1e40af; 
        border-radius: 4px; 
        font-size: 12px; 
        font-weight: 600; 
        text-decoration: none;
        transition: background 0.2s;
    }
    .button-secondary:hover {
        background: #bfdbfe;
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
    .submission-item {
        padding: 12px;
        background: #f9fafb;
        border-radius: 8px;
        border-left: 4px solid #f59e0b;
        margin-bottom: 12px;
    }
    .submission-item strong {
        color: #1f2937;
        display: block;
        margin-bottom: 6px;
    }
    .submission-item small {
        color: #9ca3af;
        display: block;
        margin-top: 6px;
    }
    .container {
        padding: 0 20px;
    }
</style>

<?php
// Output student header right at the top
echo "<div class=\"student-header\">\n";
echo "    <div class=\"container\">\n";
echo "        <h1>Tanglaw Learn</h1>\n";
echo "        <p>Welcome, " . htmlspecialchars($student['name']) . " | <a href=\"logout.php\">Logout</a></p>\n";
echo "    </div>\n";
echo "</div>\n";
?>

<div class="container" style="margin-top: 0; margin-left: 260px; max-width: 1200px; margin-right: auto; padding: 20px;">
    <h2 style="margin-top: 0; margin-bottom: 24px; font-size: 24px; font-weight: 700; color: #1f2937;">📚 Learning Materials</h2>
    <div class="grid">
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
                <ul>
                    <?php while ($s = $recentSubmissions->fetch_assoc()): ?>
                        <li class="submission-item">
                            <strong><?= htmlspecialchars($s['module_title'] ?? 'Unknown') ?></strong>
                            <div>Status: <span class="status-<?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars($s['status']) ?></span></div>
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

    <h2 style="margin-top: 40px; margin-bottom: 24px; font-size: 24px; font-weight: 700; color: #1f2937;">📊 Quick Overview</h2>
    <div class="grid">
        <div class="card">
            <div class="kpi"><?= $moduleCount ?></div>
            <p class="small">📘 Modules Available</p>
        </div>
        <div class="card">
            <div class="kpi"><?= $submissionCount ?></div>
            <p class="small">📨 Submissions</p>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
    function toggleSidebar() {
        var body = document.body;
        var backdrop = document.getElementById('sidebarBackdrop');
        if (window.innerWidth <= 900) {
            if (body.classList.contains('sidebar-open')) {
                body.classList.remove('sidebar-open');
            } else {
                body.classList.add('sidebar-open');
            }
        } else {
            body.classList.toggle('sidebar-collapsed');
        }
        if (backdrop) backdrop.style.display = body.classList.contains('sidebar-open') ? 'block' : 'none';
    }
    document.addEventListener('DOMContentLoaded', function(){
        document.body.classList.remove('sidebar-open');
        document.body.classList.remove('sidebar-collapsed');
    });
</script>

