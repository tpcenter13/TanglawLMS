<?php
include 'conn.php';
include 'config_portal.php';

if (!isset($_SESSION['loggedUser'])) {
    header("Location: login.php");
    exit();
}

$loggedUser = $_SESSION['loggedUser'];
$role = $loggedUser['role'] ?? '';
$moduleId = intval($_GET['id'] ?? 0);

$error = '';
$module = null;

function columnExists($conn, $table, $column) {
    $tableSafe = str_replace('`', '', $table);
    $colSafe = $conn->real_escape_string($column);
    $sql = "SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$colSafe}'";
    $res = $conn->query($sql);
    return $res && $res->num_rows > 0;
}


if ($moduleId <= 0) {
    $error = 'Invalid module selection.';
} else {
    if ($role === 'student' || $role === 'detainee') {
        $studentGrade = $loggedUser['grade_level'] ?? null;
        $studentSchool = $loggedUser['school'] ?? null;

        if (!$studentGrade || !$studentSchool) {
            $error = 'Student profile is missing grade or school.';
        } else {
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

            if ($gradeId === null) {
                $error = 'Grade level not found.';
            } else {
                $hasApproval = columnExists($conn, 'modules', 'approval_status');
                $approvalClause = $hasApproval ? " AND approval_status = 'approved'" : "";
                $stmt = $conn->prepare("SELECT id, title, file_path, module_order FROM modules WHERE id = ? AND grade_level_id = ? AND school = ?$approvalClause LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("iis", $moduleId, $gradeId, $studentSchool);
                    $stmt->execute();
                    $module = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                }
            }
        }
    } else {
        $stmt = $conn->prepare("SELECT id, title, file_path, module_order FROM modules WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $moduleId);
            $stmt->execute();
            $module = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }

    if (!$module && !$error) {
        $error = 'Module not found or access denied.';
    }
}

$filePath = $module['file_path'] ?? '';
$isPdf = strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf';

$backUrl = 'index.php';
if ($role === 'teacher') {
    $backUrl = 'teacher_dashboard.php?section=modules';
} elseif ($role === 'facilitator') {
    $backUrl = 'facilitator_dashboard.php?section=distribute';
} elseif ($role === 'student' || $role === 'detainee') {
    $backUrl = 'student_modules.php';
} elseif ($role === 'admin') {
    $backUrl = 'admin_dashboard.php?section=dashboard';
}

include 'header.php';
include 'sidebar.php';
?>

<?php if ($role === 'student' || $role === 'detainee'): ?>
<div class="student-header">
    <div class="student-header-content">
        <div>
            <h1>Tanglaw Learn</h1>
            <p class="header-meta">Module Preview</p>
        </div>
        <div>
            Welcome, <?= htmlspecialchars($loggedUser['name'] ?? 'Student') ?> |
            <a href="logout.php">Logout</a>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="main-container">
    <h2 class="section-title">Module Preview</h2>

    <?php if ($error): ?>
        <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php elseif (!$isPdf): ?>
        <div class="alert-error">This module file is not a PDF. Please contact your teacher.</div>
    <?php else: ?>
        <div class="form-card" style="max-width: 100%;">
            <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
                <strong><?= htmlspecialchars($module['title'] ?? 'Module') ?></strong>
                <a class="btn secondary" href="<?= htmlspecialchars($filePath) ?>" target="_blank" rel="noopener">Download PDF</a>
            </div>
            <iframe src="<?= htmlspecialchars($filePath) ?>" title="Module Preview" style="width:100%;height:75vh;border:1px solid #e2e8f0;border-radius:12px;"></iframe>
        </div>
    <?php endif; ?>

    <a href="<?= htmlspecialchars($backUrl) ?>" class="back-link">Back</a>
</div>

<?php include 'footer.php'; ?>
