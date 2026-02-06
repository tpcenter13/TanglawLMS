<?php
include 'conn.php';
include 'config_portal.php';

if (!isset($_SESSION['loggedUser'])) {
    header("Location: login.php");
    exit();
}

$loggedUser = $_SESSION['loggedUser'];
$student = $loggedUser;
$studentId = (int)$student['id'];
$grade = $student['grade_level'] ?? null;
$studentSchool = $student['school'] ?? null;

include 'header.php';
include 'sidebar.php';

function tableExists($conn, $table) {
    $resCheck = $conn->query("SHOW TABLES LIKE '$table'");
    return $resCheck && $resCheck->num_rows > 0;
}

function columnExists($conn, $table, $column) {
    $tableSafe = str_replace('`', '', $table);
    $colSafe = $conn->real_escape_string($column);
    $sql = "SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$colSafe}'";
    $res = $conn->query($sql);
    return $res && $res->num_rows > 0;
}


// Resolve grade_level text to grade_levels.id (modules use grade_level_id FK)
$gradeId = null;
if ($grade) {
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
}

$hasSubmissionsTable = tableExists($conn, 'activity_submissions');
$hasActivitiesTable = tableExists($conn, 'activity_sheets');
$hasModuleApproval = columnExists($conn, 'modules', 'approval_status');
$orderBy = $PORTAL_MODULE_ORDER_BY ?? 'm.uploaded_at ASC';
$approvalClause = $hasModuleApproval ? " AND m.approval_status = 'approved'" : "";

// Build module list with latest activity and latest submission
$modulesAll = [];
if ($gradeId !== null && $studentSchool !== null) {
    if ($hasSubmissionsTable && $hasActivitiesTable) {
        $query = "
            SELECT m.id, m.title, m.subject_id, s.title as subject_title, m.file_path, m.uploaded_at, m.module_order,
                   COALESCE(mp.status, 'not_started') as progress_status,
                   mp.marked_done_at,
                   asub.status as submission_status,
                   asub.grade as submission_grade,
                   asub.submitted_at as submission_date,
                   act_idx.activity_count,
                   act.title as activity_title,
                   act.file_path as activity_path
            FROM modules m
            LEFT JOIN subjects s ON m.subject_id = s.id
            LEFT JOIN module_progress mp ON m.id = mp.module_id AND mp.student_id = ?
            LEFT JOIN (
                SELECT s1.*
                FROM activity_submissions s1
                INNER JOIN (
                    SELECT module_id, student_id, MAX(submitted_at) as max_submitted_at
                    FROM activity_submissions
                    WHERE student_id = ?
                    GROUP BY module_id, student_id
                ) s2 ON s2.module_id = s1.module_id AND s2.student_id = s1.student_id AND s2.max_submitted_at = s1.submitted_at
            ) asub ON asub.module_id = m.id AND asub.student_id = ?
            LEFT JOIN (
                SELECT module_id, COUNT(*) as activity_count, MAX(id) as latest_activity_id
                FROM activity_sheets
                GROUP BY module_id
            ) act_idx ON act_idx.module_id = m.id
            LEFT JOIN activity_sheets act ON act.id = act_idx.latest_activity_id
            WHERE m.grade_level_id = ? AND m.school = ?$approvalClause
            ORDER BY $orderBy
        ";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("iiiis", $studentId, $studentId, $studentId, $gradeId, $studentSchool);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $modulesAll[] = $row;
            }
            $stmt->close();
        }
    } elseif ($hasSubmissionsTable && !$hasActivitiesTable) {
        $query = "
            SELECT m.id, m.title, m.subject_id, s.title as subject_title, m.file_path, m.uploaded_at, m.module_order,
                   COALESCE(mp.status, 'not_started') as progress_status,
                   mp.marked_done_at,
                   asub.status as submission_status,
                   asub.grade as submission_grade,
                   asub.submitted_at as submission_date,
                   0 as activity_count,
                   NULL as activity_title,
                   NULL as activity_path
            FROM modules m
            LEFT JOIN subjects s ON m.subject_id = s.id
            LEFT JOIN module_progress mp ON m.id = mp.module_id AND mp.student_id = ?
            LEFT JOIN (
                SELECT s1.*
                FROM activity_submissions s1
                INNER JOIN (
                    SELECT module_id, student_id, MAX(submitted_at) as max_submitted_at
                    FROM activity_submissions
                    WHERE student_id = ?
                    GROUP BY module_id, student_id
                ) s2 ON s2.module_id = s1.module_id AND s2.student_id = s1.student_id AND s2.max_submitted_at = s1.submitted_at
            ) asub ON asub.module_id = m.id AND asub.student_id = ?
            WHERE m.grade_level_id = ? AND m.school = ?$approvalClause
            ORDER BY $orderBy
        ";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("iiiis", $studentId, $studentId, $studentId, $gradeId, $studentSchool);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $modulesAll[] = $row;
            }
            $stmt->close();
        }
    } elseif (!$hasSubmissionsTable && $hasActivitiesTable) {
        $query = "
            SELECT m.id, m.title, m.subject_id, s.title as subject_title, m.file_path, m.uploaded_at, m.module_order,
                   COALESCE(mp.status, 'not_started') as progress_status,
                   mp.marked_done_at,
                   NULL as submission_status,
                   NULL as submission_grade,
                   NULL as submission_date,
                   act_idx.activity_count,
                   act.title as activity_title,
                   act.file_path as activity_path
            FROM modules m
            LEFT JOIN subjects s ON m.subject_id = s.id
            LEFT JOIN module_progress mp ON m.id = mp.module_id AND mp.student_id = ?
            LEFT JOIN (
                SELECT module_id, COUNT(*) as activity_count, MAX(id) as latest_activity_id
                FROM activity_sheets
                GROUP BY module_id
            ) act_idx ON act_idx.module_id = m.id
            LEFT JOIN activity_sheets act ON act.id = act_idx.latest_activity_id
            WHERE m.grade_level_id = ? AND m.school = ?$approvalClause
            ORDER BY $orderBy
        ";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("iis", $studentId, $gradeId, $studentSchool);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $modulesAll[] = $row;
            }
            $stmt->close();
        }
    } else {
        $query = "
            SELECT m.id, m.title, m.subject_id, s.title as subject_title, m.file_path, m.uploaded_at, m.module_order,
                   COALESCE(mp.status, 'not_started') as progress_status,
                   mp.marked_done_at,
                   NULL as submission_status,
                   NULL as submission_grade,
                   NULL as submission_date,
                   0 as activity_count,
                   NULL as activity_title,
                   NULL as activity_path
            FROM modules m
            LEFT JOIN subjects s ON m.subject_id = s.id
            LEFT JOIN module_progress mp ON m.id = mp.module_id AND mp.student_id = ?
            WHERE m.grade_level_id = ? AND m.school = ?$approvalClause
            ORDER BY $orderBy
        ";
        $stmt = $conn->prepare($query);
        if ($stmt) {
            $stmt->bind_param("iis", $studentId, $gradeId, $studentSchool);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $modulesAll[] = $row;
            }
            $stmt->close();
        }
    }
}

$moduleCount = count($modulesAll);

$passingGrade = $PORTAL_PASSING_GRADE;
    $priorCompletedBySubject = [];
    foreach ($modulesAll as &$module) {
        $module['activity_count'] = (int)($module['activity_count'] ?? 0);
        $module['has_activity'] = $module['activity_count'] > 0;
        $gradeVal = $module['submission_grade'];
        $isGraded = ($module['submission_status'] === 'graded' && $gradeVal !== null);
        $isPassed = $module['has_activity'] ? ($isGraded && (float)$gradeVal >= $passingGrade) : false;
        $isFailed = $module['has_activity'] && $isGraded && (float)$gradeVal < $passingGrade;
        $hasSubmission = !empty($module['submission_status']);
        $isPending = ($module['submission_status'] === 'submitted');

        $module['is_passed'] = $isPassed;
        $module['is_failed'] = $isFailed;
        $module['has_submission'] = $hasSubmission;
        $module['is_pending'] = $isPending;
        $module['is_completed'] = $module['has_activity'] ? $module['is_passed'] : ($module['progress_status'] === 'completed');

        $subjectKey = (string)($module['subject_id'] ?? 0);
        if (!array_key_exists($subjectKey, $priorCompletedBySubject)) {
            $priorCompletedBySubject[$subjectKey] = true;
        }
        $module['is_locked'] = !$priorCompletedBySubject[$subjectKey];
        $module['can_submit'] = !$module['is_locked'] && $module['has_activity'] && !$module['is_passed'] && !$isPending;
        $module['can_mark_done'] = !$module['is_locked'] && !$module['has_activity'] && $module['progress_status'] !== 'completed';
        $priorCompletedBySubject[$subjectKey] = $priorCompletedBySubject[$subjectKey] && $module['is_completed'];
    }
unset($module);

$recentModules = array_slice(array_reverse($modulesAll), 0, 5);

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

// Submissions count and recent (with grades)
$submissionCount = 0;
$gradedCount = 0;
$recentSubmissions = [];
if ($hasSubmissionsTable) {
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM activity_submissions WHERE student_id = ?");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $sc = $stmt->get_result()->fetch_assoc();
    $submissionCount = $sc['cnt'] ?? 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM activity_submissions WHERE student_id = ? AND status = 'graded'");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $gc = $stmt->get_result()->fetch_assoc();
    $gradedCount = $gc['cnt'] ?? 0;

    $stmt = $conn->prepare("SELECT a.id, a.module_id, a.file_path, a.comments, a.status, a.grade, a.submitted_at, m.title AS module_title
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

<div class="student-header">
    <div class="student-header-content">
        <div>
            <h1>Tanglaw Learn</h1>
            <p class="header-meta">Student Dashboard</p>
        </div>
        <p>Welcome, <?= htmlspecialchars($student['name']) ?> | <a href="logout.php">Logout</a></p>
    </div>
</div>

<div class="main-container">

    <h2 class="section-title">Quick Overview</h2>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="kpi"><?= $moduleCount ?></div>
            <div class="label">Modules Available</div>
        </div>
        <div class="stat-card">
            <div class="kpi"><?= $completedCount ?></div>
            <div class="label">Completed Modules</div>
        </div>
        <div class="stat-card">
            <div class="kpi"><?= $submissionCount ?></div>
            <div class="label">Total Submissions</div>
        </div>
        <div class="stat-card">
            <div class="kpi"><?= $gradedCount ?></div>
            <div class="label">Graded Activities</div>
        </div>
        <div class="stat-card">
            <div class="kpi"><?= round($moduleCount > 0 ? ($completedCount / $moduleCount) * 100 : 0) ?>%</div>
            <div class="label">Progress</div>
        </div>
    </div>

    <h2 class="section-title">Learning Materials</h2>
    <div class="content-grid">
        <div class="card">
            <h3>Recent Modules</h3>
            <?php if (!empty($recentModules)): ?>
                <ul>
                    <?php foreach ($recentModules as $idx => $m): ?>
                        <li>
                            <?php $moduleNumber = !empty($m['module_order']) ? (int)$m['module_order'] : ($idx + 1); ?>
                            <strong>Module <?= $moduleNumber ?>: <?= htmlspecialchars($m['title']) ?></strong>
                            <div class="module-meta">
                                <?php if (!empty($m['subject_title'])): ?>
                                    <span class="module-info">Subject: <?= htmlspecialchars($m['subject_title']) ?></span>
                                <?php endif; ?>
                                <?php if ($m['is_locked']): ?>
                                    <span class="status-pill status-pill--locked">Locked</span>
                                    <span class="module-info">Complete previous module to unlock</span>
                                <?php elseif ($m['is_passed']): ?>
                                    <span class="status-pill status-pill--completed">Passed</span>
                                <?php elseif ($m['has_activity'] && $m['is_failed']): ?>
                                    <span class="status-pill status-pill--failed">Needs Revision</span>
                                <?php elseif ($m['submission_status'] === 'submitted'): ?>
                                    <span class="status-pill status-pill--pending">Awaiting Teacher</span>
                                <?php elseif ($m['progress_status'] === 'completed'): ?>
                                    <span class="status-pill status-pill--completed">Completed</span>
                                <?php elseif ($m['progress_status'] === 'reading'): ?>
                                    <span class="status-pill status-pill--reading">In Progress</span>
                                <?php elseif ($m['has_activity']): ?>
                                    <span class="status-pill status-pill--pending">Activity Available</span>
                                <?php else: ?>
                                    <span class="status-pill status-pill--pending">Not Started</span>
                                <?php endif; ?>
                            </div>
                            <div class="module-actions">
                                <?php if (!$m['is_locked']): ?>
                                    <a href="view_module.php?id=<?= $m['id'] ?>" class="button-secondary">Preview Module</a>
                                <?php else: ?>
                                    <span class="button-secondary btn disabled">Locked</span>
                                <?php endif; ?>

                                <?php if ($m['has_activity'] && !$m['is_locked']): ?>
                                    <a href="<?= htmlspecialchars($m['activity_path']) ?>" target="_blank" class="button-secondary">Activity Sheet</a>
                                <?php else: ?>
                                    <span class="module-info"><?= $m['has_activity'] ? 'Activity locked' : 'Activity not yet available' ?></span>
                                <?php endif; ?>

                                <?php if ($m['can_submit']): ?>
                                    <a href="submit_activity.php?module_id=<?= $m['id'] ?>" class="button-secondary">Submit Activity</a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <a href="student_modules.php" class="button-action">View All Modules</a>
            <?php else: ?>
                <p>No modules available for your grade level yet.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Recent Submissions</h3>
            <?php if (!$hasSubmissionsTable): ?>
                <p>No submissions yet. Get started now!</p>
                <a href="submit_activity.php" class="button-action">Submit Your First Activity</a>
            <?php elseif ($recentSubmissions && $recentSubmissions->num_rows > 0): ?>
                <ul>
                    <?php while ($s = $recentSubmissions->fetch_assoc()): ?>
                        <li class="submission-item <?= $s['status'] === 'graded' ? 'graded' : '' ?>">
                            <strong><?= htmlspecialchars($s['module_title'] ?? 'Unknown Module') ?></strong>
                            <div class="status-line">
                                <?php
                                    $statusLabel = ucfirst($s['status']);
                                    if ($s['status'] === 'submitted') $statusLabel = 'Awaiting Teacher';
                                ?>
                                <span>Status: <span class="status-<?= htmlspecialchars($s['status']) ?>"><?= htmlspecialchars($statusLabel) ?></span></span>
                                <?php if ($s['status'] === 'graded' && $s['grade'] !== null && $s['grade'] !== ''): ?>
                                    <span class="grade-badge">Grade: <?= htmlspecialchars($s['grade']) ?></span>
                                <?php endif; ?>
                            </div>
                            <small><?= htmlspecialchars($s['submitted_at']) ?></small>
                        </li>
                    <?php endwhile; ?>
                </ul>
                <a href="my_submissions.php" class="button-action">View All Submissions</a>
            <?php else: ?>
                <p>No submissions yet.</p>
                <a href="submit_activity.php" class="button-action">Get Started</a>
            <?php endif; ?>
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
