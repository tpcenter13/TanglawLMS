<?php
ob_start();
include 'conn.php';
include 'config_portal.php';

if (!isset($_SESSION['loggedUser']) || !is_array($_SESSION['loggedUser'])) {
    header("Location: login.php");
    exit();
}

$loggedUser = $_SESSION['loggedUser'];
$studentId = (int)$loggedUser['id'];
$studentGrade = $loggedUser['grade_level'] ?? null;
$studentSchool = $loggedUser['school'] ?? null;

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


function fetchStudentModules($conn, $studentId, $gradeId, $studentSchool, $hasSubmissionsTable, $hasActivitiesTable, $passingGrade, $orderBy, $hasModuleApproval, $hasActivityApproval) {
    $modulesAll = [];
    if ($gradeId === null || $studentSchool === null) {
        return $modulesAll;
    }
    if (!$orderBy || !is_string($orderBy)) {
        $orderBy = 'm.uploaded_at ASC';
    }
    $approvalClause = $hasModuleApproval ? " AND m.approval_status = 'approved'" : "";
    $activityIndexSubquery = "SELECT module_id, COUNT(*) as activity_count, MAX(id) as latest_activity_id FROM activity_sheets";
    if ($hasActivityApproval) {
        $activityIndexSubquery .= " WHERE approval_status = 'approved'";
    }
    $activityIndexSubquery .= " GROUP BY module_id";
    $activityJoinClause = $hasActivityApproval
        ? "LEFT JOIN activity_sheets act ON act.id = act_idx.latest_activity_id AND act.approval_status = 'approved'"
        : "LEFT JOIN activity_sheets act ON act.id = act_idx.latest_activity_id";

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
                $activityIndexSubquery
            ) act_idx ON act_idx.module_id = m.id
            $activityJoinClause
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
                $activityIndexSubquery
            ) act_idx ON act_idx.module_id = m.id
            $activityJoinClause
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

    return $modulesAll;
}

$gradeId = null;
if ($studentGrade) {
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
}

$hasSubmissionsTable = tableExists($conn, 'activity_submissions');
$hasActivitiesTable = tableExists($conn, 'activity_sheets');
$hasModuleApproval = columnExists($conn, 'modules', 'approval_status');
$hasActivityApproval = columnExists($conn, 'activity_sheets', 'approval_status');
$passingGrade = $PORTAL_PASSING_GRADE;
$orderBy = $PORTAL_MODULE_ORDER_BY ?? 'm.uploaded_at ASC';
$modulesAll = fetchStudentModules($conn, $studentId, $gradeId, $studentSchool, $hasSubmissionsTable, $hasActivitiesTable, $passingGrade, $orderBy, $hasModuleApproval, $hasActivityApproval);

$moduleMap = [];
foreach ($modulesAll as $moduleRow) {
    $moduleMap[$moduleRow['id']] = $moduleRow;
}

$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $moduleId = (int)($_POST['module_id'] ?? 0);
    $target = $moduleMap[$moduleId] ?? null;

    if ($action === 'mark_done') {
        if (!$target) {
            $_SESSION['flash_error'] = 'Invalid module selection.';
        } elseif ($target['is_locked']) {
            $_SESSION['flash_error'] = 'Complete the previous module first to unlock this one.';
        } elseif ($target['has_activity']) {
            $_SESSION['flash_error'] = 'This module is completed after you pass the activity. Please wait for your grade.';
        } else {
            $stmt = $conn->prepare("INSERT INTO module_progress (student_id, module_id, status, marked_done_at) VALUES (?, ?, 'completed', NOW()) ON DUPLICATE KEY UPDATE status = 'completed', marked_done_at = NOW()");
            if ($stmt) {
                $stmt->bind_param('ii', $studentId, $moduleId);
                $stmt->execute();
                $stmt->close();
            }
        }
        ob_end_clean();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if ($action === 'mark_incomplete') {
        if (!$target) {
            $_SESSION['flash_error'] = 'Invalid module selection.';
        } else {
            $stmt = $conn->prepare("UPDATE module_progress SET status = 'not_started', marked_done_at = NULL WHERE student_id = ? AND module_id = ?");
            if ($stmt) {
                $stmt->bind_param('ii', $studentId, $moduleId);
                $stmt->execute();
                $stmt->close();
            }
        }
        ob_end_clean();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div class="student-header">
    <div class="student-header-content">
        <div>
            <h1>Tanglaw Learn</h1>
            <p class="header-meta">My Modules</p>
        </div>
        <div>
            Welcome, <?= htmlspecialchars($loggedUser['name']) ?> |
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>

<div class="main-container">

    <h2 class="section-title">My Modules</h2>

    <div class="action-buttons">
        <a class="btn secondary" href="student_dashboard.php">Go to Dashboard</a>
        <a class="btn secondary" href="my_submissions.php">View Activity Submissions</a>
    </div>

    <?php if ($flashError): ?>
        <div class="alert-error"><?= htmlspecialchars($flashError) ?></div>
    <?php endif; ?>

    <?php if (count($modulesAll) > 0): ?>
        <div class="modules-grid">
            <?php foreach($modulesAll as $index => $row):
                $isLocked = $row['is_locked'];
                $hasActivity = $row['has_activity'];
                $isPassed = $row['is_passed'];
                $isFailed = $row['is_failed'];
                $progressStatus = $row['progress_status'];
                $moduleNumber = !empty($row['module_order']) ? (int)$row['module_order'] : ($index + 1);
            ?>
                <div class="module-card <?= $isLocked ? 'locked' : '' ?>">
                    <div>
                        <h3 class="module-title">Module <?= $moduleNumber ?>: <?= htmlspecialchars($row['title']); ?></h3>
                        <div class="module-meta">
                            <?php if ($isLocked): ?>
                                <span class="status-pill status-pill--locked">Locked</span>
                            <?php elseif ($isPassed): ?>
                                <span class="status-pill status-pill--completed">Passed</span>
                            <?php elseif ($hasActivity && $isFailed): ?>
                                <span class="status-pill status-pill--failed">Needs Revision</span>
                            <?php elseif ($row['submission_status'] === 'submitted'): ?>
                                <span class="status-pill status-pill--pending">Awaiting Teacher</span>
                            <?php elseif ($progressStatus === 'completed'): ?>
                                <span class="status-pill status-pill--completed">Completed</span>
                            <?php elseif ($progressStatus === 'reading'): ?>
                                <span class="status-pill status-pill--reading">In Progress</span>
                            <?php elseif ($hasActivity): ?>
                                <span class="status-pill status-pill--pending">Activity Available</span>
                            <?php else: ?>
                                <span class="status-pill status-pill--pending">Not Started</span>
                            <?php endif; ?>

                            <?php if (!empty($row['subject_title'])): ?>
                                <span class="module-info">Subject: <?= htmlspecialchars($row['subject_title']) ?></span>
                            <?php endif; ?>

                            <?php if ($hasActivity): ?>
                                <span class="module-info">Activity: <?= htmlspecialchars($row['activity_title'] ?? 'Available') ?></span>
                            <?php else: ?>
                                <span class="module-info">Activity not yet available</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($isLocked): ?>
                        <div class="module-lock">Complete the previous module to unlock this one.</div>
                    <?php endif; ?>

                    <div class="module-actions">
                        <?php if (!$isLocked): ?>
                            <a href="view_module.php?id=<?= $row['id'] ?>" class="btn secondary">Preview Module</a>
                        <?php else: ?>
                            <span class="btn disabled">Locked</span>
                        <?php endif; ?>

                        <?php if ($hasActivity && !$isLocked): ?>
                            <a href="<?= htmlspecialchars($row['activity_path']); ?>" target="_blank" class="btn secondary">Activity Sheet</a>
                        <?php endif; ?>

                        <?php if ($row['can_submit']): ?>
                            <a class="btn" href="submit_activity.php?module_id=<?= $row['id'] ?>">Submit Activity</a>
                        <?php endif; ?>

                        <?php if ($row['can_mark_done']): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="mark_done">
                                <input type="hidden" name="module_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn success">Mark as Done</button>
                            </form>
                        <?php endif; ?>

                        <?php if (!$isLocked && $progressStatus === 'completed' && !$isPassed): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="mark_incomplete">
                                <input type="hidden" name="module_id" value="<?= $row['id'] ?>">
                                <button type="submit" class="btn warning">Mark as Incomplete</button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <?php if ($row['marked_done_at'] && $progressStatus === 'completed'): ?>
                        <div class="completion-date">
                            Completed on <?= date('M j, Y \a\t g:i A', strtotime($row['marked_done_at'])) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($isPassed || $isFailed): ?>
                        <div class="graded-info">
                            Grade: <?= htmlspecialchars($row['submission_grade'] ?? 'N/A') ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
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
