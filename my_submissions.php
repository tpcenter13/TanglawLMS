<?php
include 'conn.php';
include 'header.php';

session_start();

// FIX: ensure user is logged in
if (!isset($_SESSION['loggedUser'])) {
    header("Location: login.php");
    exit();
}

$loggedUser = $_SESSION['loggedUser']; // IMPORTANT FIX

echo '<div class="sidebar-backdrop" id="sidebarBackdrop"></div>';
include 'sidebar.php';

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
    margin: 0 0 20px;
    color: #1f2937;
    border-bottom: 3px solid #f59e0b;
    padding-bottom: 8px;
}

/* ===== SUBMISSIONS TABLE CARD ===== */
.submissions-card {
    background: #fff;
    border-radius: 14px;
    padding: 28px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    margin-bottom: 24px;
}

.submissions-table {
    width: 100%;
    border-collapse: collapse;
}

.submissions-table tr {
    border-bottom: 1px solid #f3f4f6;
}

.submissions-table tr:last-child {
    border-bottom: none;
}

.submissions-table th {
    background: #f9fafb;
    padding: 12px;
    text-align: left;
    font-weight: 700;
    color: #1f2937;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.submissions-table td {
    padding: 16px 12px;
    color: #4b5563;
    font-size: 14px;
}

.submissions-table a {
    color: #f59e0b;
    text-decoration: none;
    font-weight: 600;
}

.submissions-table a:hover {
    color: #d97706;
    text-decoration: underline;
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

.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: #6b7280;
}

.back-link {
    display: inline-block;
    margin-top: 16px;
    padding: 10px 18px;
    background: #dbeafe;
    color: #1e40af;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: background 0.2s;
}

.back-link:hover {
    background: #bfdbfe;
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
    .submissions-table {
        font-size: 12px;
    }
    .submissions-table th,
    .submissions-table td {
        padding: 10px 8px;
    }
}
</style>

<!-- ===== HEADER ===== -->
<div class="student-header">
    <div class="student-header-content">
        <h1>📥 My Activity Submissions</h1>
    </div>
</div>

<div class="main-container">
    <h2 class="section-title">📋 All Submissions</h2>
    
    <div class="submissions-card">
        <?php if ($res->num_rows > 0): ?>
            <table class="submissions-table">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>File</th>
                        <th>Comments</th>
                        <th>Status</th>
                        <th>Submitted At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($r = $res->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($r['module_title']); ?></td>
                            <td><a href="<?php echo htmlspecialchars($r['file_path']); ?>" target="_blank">📄 View File</a></td>
                            <td><?php echo nl2br(htmlspecialchars($r['comments'])); ?></td>
                            <td><span class="status-<?php echo htmlspecialchars($r['status']); ?>"><?php echo htmlspecialchars(ucfirst($r['status'])); ?></span></td>
                            <td><?php echo htmlspecialchars($r['submitted_at']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>No submissions found. Start submitting your activities!</p>
            </div>
        <?php endif; ?>
    </div>

    <a href="student_modules.php" class="back-link">← Back to Modules</a>
</div>

<?php include 'footer.php'; ?>

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