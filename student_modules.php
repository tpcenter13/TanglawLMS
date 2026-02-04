<?php
ob_start();
session_start();
include 'conn.php';
include 'header.php';

// ✅ Ensure user is logged in
if (!isset($_SESSION['loggedUser']) || !is_array($_SESSION['loggedUser'])) {
    header("Location: login.php");
    exit();
}

$loggedUser = $_SESSION['loggedUser'];
$studentId = $loggedUser['id'];
$studentGrade = $loggedUser['grade_level'];

include 'sidebar.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_done') {
        $moduleId = intval($_POST['module_id'] ?? 0);
        
        // Insert or update progress record
        $stmt = $conn->prepare("INSERT INTO module_progress (student_id, module_id, status, marked_done_at) VALUES (?, ?, 'completed', NOW()) ON DUPLICATE KEY UPDATE status = 'completed', marked_done_at = NOW()");
        if ($stmt) {
            $stmt->bind_param('ii', $studentId, $moduleId);
            $stmt->execute();
            $stmt->close();
        }
        
        ob_end_clean(); // ✅ Clear buffer before redirect
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
    
    if ($action === 'mark_incomplete') {
        $moduleId = intval($_POST['module_id'] ?? 0);
        
        // Update progress to not_started
        $stmt = $conn->prepare("UPDATE module_progress SET status = 'not_started', marked_done_at = NULL WHERE student_id = ? AND module_id = ?");
        if ($stmt) {
            $stmt->bind_param('ii', $studentId, $moduleId);
            $stmt->execute();
            $stmt->close();
        }
        
        ob_end_clean();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Resolve grade text → grade_levels.id
$gradeId = null;
$gstmt = $conn->prepare("SELECT id FROM grade_levels WHERE level = ? LIMIT 1");
if ($gstmt) {
    $gstmt->bind_param("s", $studentGrade);
    $gstmt->execute();
    $gres = $gstmt->get_result();
    if ($gres && $gr = $gres->fetch_assoc()) {
        $gradeId = (int)$gr['id'];
    }
    $gstmt->close();
}

// Get student's school from session
$studentSchool = $loggedUser['school'] ?? null;

// Get modules with progress status AND submission status - filtered by grade AND school
$result = null;
if ($gradeId !== null && $studentSchool !== null) {
    $stmt = $conn->prepare("
        SELECT m.id, m.title, m.file_path, 
               COALESCE(mp.status, 'not_started') as progress_status,
               mp.marked_done_at,
               asub.status as submission_status,
               asub.grade as submission_grade
        FROM modules m 
        LEFT JOIN module_progress mp ON m.id = mp.module_id AND mp.student_id = ?
        LEFT JOIN activity_submissions asub ON m.id = asub.module_id AND asub.student_id = ?
        WHERE m.grade_level_id = ? AND m.school = ?
        GROUP BY m.id
        ORDER BY m.uploaded_at DESC
    ");
    if ($stmt) {
        $stmt->bind_param("iiis", $studentId, $studentId, $gradeId, $studentSchool);
        $stmt->execute();
        $result = $stmt->get_result();
    }
}

if ($result === null) {
    $result = new class {
        public $num_rows = 0;
        public function fetch_assoc(){ return false; }
    };
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
    left: 280px;
    width: calc(100% - 280px);
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
    margin-left: 280px;
    padding: 100px 48px 64px 48px;
    max-width: calc(100% - 280px);
    min-height: 100vh;
}

/* ===== SECTION TITLES ===== */
.section-title {
    font-size: 22px;
    font-weight: 700;
    margin: 0 0 20px;
    color: #1f2937;
    border-bottom: 3px solid #f59e0b;
    padding-bottom: 8px;
}

/* ===== ACTION BUTTONS ===== */
.action-buttons {
    display: flex;
    gap: 12px;
    margin-bottom: 32px;
    flex-wrap: wrap;
}

.btn {
    display: inline-block;
    padding: 10px 18px;
    background: #f59e0b;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: background 0.2s;
    border: none;
    cursor: pointer;
}

.btn:hover {
    background: #d97706;
}

.btn.secondary {
    background: #dbeafe;
    color: #1e40af;
}

.btn.secondary:hover {
    background: #bfdbfe;
}

.btn.success {
    background: #10b981;
    color: white;
}

.btn.success:hover {
    background: #059669;
}

.btn.warning {
    background: #f59e0b;
    color: white;
}

.btn.warning:hover {
    background: #d97706;
}

.btn.disabled {
    background: #9ca3af;
    color: white;
    cursor: not-allowed;
    opacity: 0.6;
}

.btn.disabled:hover {
    background: #9ca3af;
}

/* ===== MODULE GRID ===== */
.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.module-card {
    background: #fff;
    border-radius: 14px;
    padding: 24px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
}

.module-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,.12);
}

.module-card.completed {
    border-left: 4px solid #10b981;
    background: linear-gradient(135deg, #ffffff 0%, #f0fdf4 100%);
}

.module-card.graded {
    border-left: 4px solid #8b5cf6;
    background: linear-gradient(135deg, #ffffff 0%, #faf5ff 100%);
}

.module-card strong {
    display: block;
    color: #1f2937;
    font-size: 16px;
    margin-bottom: 12px;
    line-height: 1.4;
}

.progress-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 16px;
    text-transform: uppercase;
}

.progress-status.not-started {
    background: #f3f4f6;
    color: #6b7280;
}

.progress-status.reading {
    background: #dbeafe;
    color: #1e40af;
}

.progress-status.completed {
    background: #d1fae5;
    color: #065f46;
}

.progress-status.graded {
    background: #ede9fe;
    color: #6b21a8;
}

.module-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    margin-top: 16px;
}

.module-actions form {
    margin: 0;
}

.module-card a {
    display: inline-block;
    margin: 0;
    padding: 8px 14px;
    background: #dbeafe;
    color: #1e40af;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: background 0.2s;
    text-align: center;
}

.module-card a:hover {
    background: #bfdbfe;
}

.completion-date {
    font-size: 11px;
    color: #059669;
    margin-top: 8px;
    font-style: italic;
}

.graded-info {
    font-size: 12px;
    color: #6b21a8;
    margin-top: 8px;
    font-weight: 600;
    background: #ede9fe;
    padding: 8px 12px;
    border-radius: 6px;
}

.empty-state {
    background: #fff;
    border-radius: 14px;
    padding: 48px 24px;
    border: 1px solid #e5e7eb;
    text-align: center;
    color: #6b7280;
}

/* ===== RESPONSIVE FIX ===== */
@media (max-width: 1024px) {
    .student-header {
        left: 260px;
        width: calc(100% - 260px);
    }
    
    .main-container {
        margin-left: 260px;
        padding: 100px 32px 48px 32px;
        max-width: calc(100% - 260px);
    }
}

@media (max-width: 900px) {
    .student-header {
        left: 0;
        width: 100%;
    }
    
    .main-container {
        margin-left: 0;
        padding: 110px 16px 48px;
        max-width: 100%;
    }
    
    .modules-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- ===== HEADER ===== -->
<div class="student-header">
    <div class="student-header-content">
        <h1>🎓 Tanglaw Learn</h1>
        <div>
            Welcome, <?= htmlspecialchars($loggedUser['name']) ?> |
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-container">

    <h2 class="section-title">📘 My Modules</h2>

    <div class="action-buttons">
        <a class="btn secondary" href="student_dashboard.php">🧭 Go to Dashboard</a>
        <a class="btn secondary" href="my_submissions.php">📥 View my Activity Submissions</a>
    </div>

    <?php if ($result->num_rows > 0): ?>
        <div class="modules-grid">
            <?php while($row = $result->fetch_assoc()): 
                $isGraded = ($row['submission_status'] === 'graded');
                $hasSubmission = !empty($row['submission_status']);
            ?>
                <div class="module-card <?= $isGraded ? 'graded' : ($row['progress_status'] === 'completed' ? 'completed' : '') ?>">
                    <strong><?= htmlspecialchars($row['title']); ?></strong>
                    
                    <?php if ($isGraded): ?>
                        <div class="progress-status graded">
                            🎓 Graded
                        </div>
                    <?php else: ?>
                        <div class="progress-status <?= $row['progress_status'] ?>">
                            <?php if ($row['progress_status'] === 'not_started'): ?>
                                📋 Not Started
                            <?php elseif ($row['progress_status'] === 'reading'): ?>
                                📖 Reading
                            <?php else: ?>
                                ✅ Completed
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="module-actions">
                        <a href="<?= htmlspecialchars($row['file_path']); ?>" target="_blank">📖 Read Module</a>
                        
                        <?php if ($isGraded): ?>
                            <!-- Module is graded - show grade info, hide all action buttons -->
                            <div class="graded-info">
                                ✅ Grade: <?= htmlspecialchars($row['submission_grade'] ?? 'N/A') ?>
                            </div>
                        <?php else: ?>
                            <!-- Module is NOT graded yet -->
                            <?php if ($row['progress_status'] !== 'completed'): ?>
                                <!-- Not marked as complete - show Mark as Done button -->
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="mark_done">
                                    <input type="hidden" name="module_id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn success">✓ Mark as Done</button>
                                </form>
                            <?php else: ?>
                                <!-- Marked as complete - show Submit Activity and toggle button -->
                                <a class="btn" href="submit_activity.php?module_id=<?= $row['id'] ?>">📨 Submit Activity</a>
                                
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="mark_incomplete">
                                    <input type="hidden" name="module_id" value="<?= $row['id'] ?>">
                                    <button type="submit" class="btn warning">↩️ Mark as Incomplete</button>
                                </form>
                                
                                <?php if ($row['marked_done_at']): ?>
                                    <div class="completion-date">
                                        Completed on <?= date('M j, Y \a\t g:i A', strtotime($row['marked_done_at'])) ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <p>No modules available for your grade level yet.</p>
        </div>
    <?php endif; ?>

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