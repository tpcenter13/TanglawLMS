<?php
include 'conn.php';
include 'config_portal.php';

if (!isset($_SESSION['loggedUser'])) {
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

$passingGrade = $PORTAL_PASSING_GRADE;
$maxFileSize = 10 * 1024 * 1024;
$allowedMime = [
    'application/pdf',
    'image/png',
    'image/jpeg',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

$gradeId = null;
if ($studentGrade) {
    $gstmt = $conn->prepare("SELECT id FROM grade_levels WHERE level = ? LIMIT 1");
    if ($gstmt) {
        $gstmt->bind_param("s", $studentGrade);
        $gstmt->execute();
        $gres = $gstmt->get_result();
        if ($gres && $gr = $gres->fetch_assoc()) { $gradeId = (int)$gr['id']; }
        $gstmt->close();
    }
}

$hasSubmissionsTable = tableExists($conn, 'activity_submissions');
$hasActivitiesTable = tableExists($conn, 'activity_sheets');
$hasModuleApproval = columnExists($conn, 'modules', 'approval_status');
$hasActivityApproval = columnExists($conn, 'activity_sheets', 'approval_status');
$orderBy = $PORTAL_MODULE_ORDER_BY ?? 'm.uploaded_at ASC';
$modulesAll = fetchStudentModules($conn, $studentId, $gradeId, $studentSchool, $hasSubmissionsTable, $hasActivitiesTable, $passingGrade, $orderBy, $hasModuleApproval, $hasActivityApproval);

$modulesMap = [];
foreach ($modulesAll as $m) {
    $modulesMap[$m['id']] = $m;
}

$moduleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$commentsValue = '';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moduleId = intval($_POST['module_id'] ?? 0);
    $commentsValue = isset($_POST['comments']) ? trim($_POST['comments']) : '';

    $target = $modulesMap[$moduleId] ?? null;
    if (!$target) {
        $errors[] = 'Please select a valid module.';
    } else {
        if ($target['is_locked']) {
            $errors[] = 'This module is locked. Complete the previous module first.';
        }
        if (!$target['has_activity']) {
            $errors[] = 'No activity sheet has been uploaded yet for this module.';
        }
        if ($target['is_passed']) {
            $errors[] = 'This module is already completed with a passing grade.';
        }
        if ($target['submission_status'] === 'submitted') {
            $errors[] = 'Your previous submission is still pending review.';
        }
    }

    if (!isset($_FILES['activity_sheet'])) {
        $errors[] = 'No file uploaded.';
    } else {
        $file = $_FILES['activity_sheet'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error.';
        } else {
            if ($file['size'] > $maxFileSize) {
                $errors[] = 'File too large (max 10 MB).';
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, $allowedMime)) {
                $errors[] = 'File type not allowed. Use PDF, DOC, DOCX, JPG, or PNG.';
            }
        }
    }

    if (empty($errors)) {
        $uploadDir = __DIR__ . '/uploads/activity_sheets/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
        $newName = $studentId . '_' . $moduleId . '_' . time() . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $baseName) . '.' . $ext;
        $dest = $uploadDir . $newName;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $createSql = "CREATE TABLE IF NOT EXISTS activity_submissions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                module_id INT NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                comments TEXT,
                status VARCHAR(50) DEFAULT 'submitted',
                grade DECIMAL(5,2) DEFAULT NULL,
                submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
            $conn->query($createSql);

            $relPath = 'uploads/activity_sheets/' . $newName;
            $insStmt = $conn->prepare("INSERT INTO activity_submissions (student_id, module_id, file_path, comments) VALUES (?, ?, ?, ?)");
            $insStmt->bind_param("iiss", $studentId, $moduleId, $relPath, $commentsValue);
            $ok = $insStmt->execute();

            if ($ok) {
                $success = 'Activity sheet submitted successfully and sent to the Tanglaw Teacher.';
                $commentsValue = '';
            } else {
                $errors[] = 'Failed to record submission: ' . $conn->error;
            }
        } else {
            $errors[] = 'Failed to move uploaded file.';
        }
    }
}

$selectedModule = $modulesMap[$moduleId] ?? null;
$moduleLocked = $selectedModule ? $selectedModule['is_locked'] : false;
$moduleHasActivity = $selectedModule ? $selectedModule['has_activity'] : false;
$modulePassed = $selectedModule ? $selectedModule['is_passed'] : false;
$moduleFailed = $selectedModule ? $selectedModule['is_failed'] : false;
$modulePending = $selectedModule ? ($selectedModule['submission_status'] === 'submitted') : false;

$moduleAlreadyGraded = $modulePassed;
$formDisabled = $moduleLocked || !$moduleHasActivity || $moduleAlreadyGraded || $modulePending;

include 'header.php';
include 'sidebar.php';
?>

<div class="student-header">
    <div class="student-header-content">
        <div>
            <h1>Tanglaw Learn</h1>
            <p class="header-meta">Submit Activity Sheet</p>
        </div>
        <p>Welcome, <?= htmlspecialchars($loggedUser['name']) ?> | <a href="logout.php">Logout</a></p>
    </div>
</div>

<div class="main-container">

    <h2 class="section-title">Upload Your Work</h2>

    <?php if ($success): ?>
        <div class="alert-success">
            <?= htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <?php foreach ($errors as $e): ?>
                <div><?= htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($moduleAlreadyGraded): ?>
        <div class="alert-warning" id="gradedWarning">
            <span>This module is already completed with a passing grade. You cannot submit another activity for it.</span>
            <button type="button" class="btn-ok" onclick="dismissWarning()">OK</button>
        </div>
    <?php elseif ($moduleFailed && $selectedModule): ?>
        <div class="alert-warning" id="failedWarning">
            <span>Your previous submission did not pass. Please resubmit after reviewing the feedback.</span>
            <button type="button" class="btn-ok" onclick="dismissWarning('failedWarning')">OK</button>
        </div>
    <?php endif; ?>

    <div class="alert-warning" id="moduleStatus" style="display: none;"></div>

    <form method="POST" enctype="multipart/form-data" class="form-card" id="activityForm">
        <label for="module_id">Select Module <span class="required">*</span></label>
        <select name="module_id" id="module_id" required>
            <option value="">-- Choose a module --</option>
            <?php foreach ($modulesAll as $idx => $m): ?>
                <?php
                    $statusNote = '';
                    if ($m['is_locked']) {
                        $statusNote = ' (Locked)';
                    } elseif (!$m['has_activity']) {
                        $statusNote = ' (Activity pending)';
                    } elseif ($m['is_passed']) {
                        $statusNote = ' (Passed)';
                    } elseif ($m['submission_status'] === 'submitted') {
                        $statusNote = ' (Awaiting teacher)';
                    } elseif ($m['is_failed']) {
                        $statusNote = ' (Needs revision)';
                    }
                    $moduleNumber = !empty($m['module_order']) ? (int)$m['module_order'] : ($idx + 1);
                    $subjectLabel = !empty($m['subject_title']) ? $m['subject_title'] . ' - ' : '';
                ?>
                <option value="<?= $m['id']; ?>" <?= ($m['id'] == $moduleId) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($subjectLabel) ?>Module <?= $moduleNumber ?>: <?= htmlspecialchars($m['title']); ?><?= $statusNote; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="form-helper" id="activityLinkWrap" style="display: none;">
            Activity Sheet: <a href="#" id="activityLink" target="_blank">Open activity</a>
        </div>

        <label for="activity_sheet">Attach Activity Sheet (PDF, DOC, DOCX, JPG, PNG) <span class="required">*</span></label>
        <input type="file" name="activity_sheet" id="activity_sheet" accept=".pdf, .doc, .docx, .jpg, .jpeg, .png" required <?= $formDisabled ? 'disabled' : ''; ?>>
        <div class="form-helper">Maximum file size: 10 MB.</div>
        <div class="field-error" id="fileError" style="display: none;"></div>

        <label for="comments">Comments (optional)</label>
        <textarea name="comments" id="comments" placeholder="Add any notes or comments about your submission..." <?= $formDisabled ? 'disabled' : ''; ?>><?= htmlspecialchars($commentsValue); ?></textarea>

        <button type="submit" class="btn-submit" id="submitButton" <?= $formDisabled ? 'disabled' : ''; ?>>Submit to Tanglaw Teacher</button>
    </form>

    <a href="student_modules.php" class="back-link">Back to Modules</a>

</div>

<script>
const moduleData = <?= json_encode(array_reduce($modulesAll, function($acc, $m) {
    $acc[$m['id']] = [
        'title' => $m['title'],
        'is_locked' => (bool)$m['is_locked'],
        'has_activity' => (bool)$m['has_activity'],
        'activity_title' => $m['activity_title'],
        'activity_path' => $m['activity_path'],
        'progress_status' => $m['progress_status'],
        'is_passed' => (bool)$m['is_passed'],
        'is_failed' => (bool)$m['is_failed'],
        'submission_status' => $m['submission_status'],
    ];
    return $acc;
}, [])); ?>;

const moduleSelect = document.getElementById('module_id');
const activityLinkWrap = document.getElementById('activityLinkWrap');
const activityLink = document.getElementById('activityLink');
const moduleStatus = document.getElementById('moduleStatus');
const fileInput = document.getElementById('activity_sheet');
const submitButton = document.getElementById('submitButton');
const fileError = document.getElementById('fileError');
const maxFileSize = <?= $maxFileSize ?>;

const allowedExtensions = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];

function showStatus(message, type) {
    if (!moduleStatus) return;
    moduleStatus.style.display = 'block';
    moduleStatus.className = type;
    moduleStatus.textContent = message;
}

function clearStatus() {
    if (!moduleStatus) return;
    moduleStatus.style.display = 'none';
    moduleStatus.textContent = '';
}

function updateModuleUI() {
    const selectedId = parseInt(moduleSelect.value || '0', 10);
    if (!selectedId || !moduleData[selectedId]) {
        clearStatus();
        if (activityLinkWrap) activityLinkWrap.style.display = 'none';
        if (fileInput) fileInput.disabled = true;
        if (submitButton) submitButton.disabled = true;
        return;
    }

    const data = moduleData[selectedId];

    if (data.has_activity && data.activity_path) {
        activityLinkWrap.style.display = 'block';
        activityLink.href = data.activity_path;
        activityLink.textContent = data.activity_title ? data.activity_title : 'Open activity';
    } else {
        activityLinkWrap.style.display = 'none';
    }

    if (data.is_locked) {
        showStatus('This module is locked. Complete the previous module first.', 'alert-warning');
        fileInput.disabled = true;
        submitButton.disabled = true;
        return;
    }

    if (!data.has_activity) {
        showStatus('No activity sheet has been uploaded yet for this module.', 'alert-warning');
        fileInput.disabled = true;
        submitButton.disabled = true;
        return;
    }

    if (data.is_passed) {
        showStatus('This module is already completed with a passing grade.', 'alert-warning');
        fileInput.disabled = true;
        submitButton.disabled = true;
        return;
    }

    if (data.submission_status === 'submitted') {
        showStatus('Submission received. Awaiting teacher review.', 'alert-warning');
        fileInput.disabled = true;
        submitButton.disabled = true;
        return;
    }

    if (data.is_failed) {
        showStatus('Previous submission did not pass. You may resubmit.', 'alert-warning');
    } else {
        showStatus('Ready to submit your activity.', 'alert-success');
    }

    fileInput.disabled = false;
    submitButton.disabled = false;
}

function validateFileInput() {
    fileError.style.display = 'none';
    fileError.textContent = '';

    const file = fileInput.files[0];
    if (!file) {
        return;
    }

    if (file.size > maxFileSize) {
        fileError.textContent = 'File too large (max 10 MB).';
        fileError.style.display = 'block';
        submitButton.disabled = true;
        return;
    }

    const extension = file.name.split('.').pop().toLowerCase();
    if (!allowedExtensions.includes(extension)) {
        fileError.textContent = 'File type not allowed. Use PDF, DOC, DOCX, JPG, or PNG.';
        fileError.style.display = 'block';
        submitButton.disabled = true;
        return;
    }

    updateModuleUI();
}

function dismissWarning(id = 'gradedWarning') {
    const warning = document.getElementById(id);
    if (warning) {
        warning.style.display = 'none';
    }
}

moduleSelect.addEventListener('change', updateModuleUI);
if (fileInput) {
    fileInput.addEventListener('change', validateFileInput);
}

document.addEventListener('DOMContentLoaded', function() {
    updateModuleUI();
});
</script>

<?php include 'footer.php'; ?>
