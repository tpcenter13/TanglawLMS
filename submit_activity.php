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

echo '<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>';
include 'sidebar.php';

$studentId = $loggedUser['id'];

// Accept module_id as GET parameter
$moduleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;

// Get module info for display
$moduleTitle = '';
$moduleAlreadyGraded = false;

if ($moduleId > 0) {
    $stmt = $conn->prepare("SELECT id, title FROM modules WHERE id = ?");
    $stmt->bind_param("i", $moduleId);
    $stmt->execute();
    $modRes = $stmt->get_result();
    if ($modRes->num_rows > 0) {
        $row = $modRes->fetch_assoc();
        $moduleTitle = $row['title'];
    }
    
    // Check if student already has a GRADED submission for this module
    $gradeCheckStmt = $conn->prepare("SELECT id FROM activity_submissions WHERE student_id = ? AND module_id = ? AND status = 'graded' LIMIT 1");
    $gradeCheckStmt->bind_param("ii", $studentId, $moduleId);
    $gradeCheckStmt->execute();
    $gradeCheckRes = $gradeCheckStmt->get_result();
    if ($gradeCheckRes->num_rows > 0) {
        $moduleAlreadyGraded = true;
    }
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    $mId = intval($_POST['module_id']);
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';

    // CHECK: Prevent submission if module already has graded submission
    $gradeCheckStmt = $conn->prepare("SELECT id FROM activity_submissions WHERE student_id = ? AND module_id = ? AND status = 'graded' LIMIT 1");
    $gradeCheckStmt->bind_param("ii", $studentId, $mId);
    $gradeCheckStmt->execute();
    $gradeCheckRes = $gradeCheckStmt->get_result();
    
    if ($gradeCheckRes->num_rows > 0) {
        $errors[] = 'You cannot submit another activity for this module because it has already been graded.';
    } else {
        if (!isset($_FILES['activity_sheet'])) {
            $errors[] = 'No file uploaded.';
        } else {
            $file = $_FILES['activity_sheet'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'File upload error.';
            } else {
                // Validate size (max 10MB)
                if ($file['size'] > 10 * 1024 * 1024) {
                    $errors[] = 'File too large (max 10 MB).';
                }

                // Validate type
                $allowed = ['application/pdf', 'image/png', 'image/jpeg', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mime, $allowed)) {
                    $errors[] = 'File type not allowed. Use PDF, DOC, DOCX, JPG, PNG.';
                }

                if (empty($errors)) {
                    $uploadDir = __DIR__ . '/uploads/activity_sheets/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
                    $newName = $studentId . '_' . $mId . '_' . time() . '_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $baseName) . '.' . $ext;

                    $dest = $uploadDir . $newName;

                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        // Save metadata in DB (create table if not exists)
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
                        $insStmt->bind_param("iiss", $studentId, $mId, $relPath, $comments);
                        $ok = $insStmt->execute();

                        if ($ok) {
                            $success = 'Activity sheet submitted successfully and sent to Tanglaw Facilitator.';
                        } else {
                            $errors[] = 'Failed to record submission: ' . $conn->error;
                        }
                    } else {
                        $errors[] = 'Failed to move uploaded file.';
                    }
                }
            }
        }
    }
}

// Fetch modules for dropdown: resolve student's grade text to id
$modules = [];
$gradeId = null;
$gstmt = $conn->prepare("SELECT id FROM grade_levels WHERE level = ? LIMIT 1");
if ($gstmt) {
    $gstmt->bind_param("s", $loggedUser['grade_level']);
    $gstmt->execute();
    $gres = $gstmt->get_result();
    if ($gres && $gr = $gres->fetch_assoc()) { $gradeId = (int)$gr['id']; }
    $gstmt->close();
}

// Get student's school
$studentSchool = $loggedUser['school'] ?? null;

if ($gradeId !== null && $studentSchool !== null) {
    $stmt = $conn->prepare("SELECT id, title FROM modules WHERE grade_level_id = ? AND school = ? ORDER BY title");
    if ($stmt) {
        $stmt->bind_param("is", $gradeId, $studentSchool);
        $stmt->execute();
        $modResult = $stmt->get_result();
        while ($m = $modResult->fetch_assoc()) { $modules[] = $m; }
    }
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

/* ===== ALERT CARDS ===== */
.alert-success {
    background: #ecfdf5;
    border: 1px solid #bbf7d0;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 24px;
    color: #166534;
    font-weight: 600;
}

.alert-error {
    background: #fff1f2;
    border: 1px solid #fecaca;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 24px;
    color: #991b1b;
}

.alert-error div {
    margin-bottom: 8px;
}

.alert-error div:last-child {
    margin-bottom: 0;
}

.alert-warning {
    background: #fef3c7;
    border: 1px solid #fde047;
    border-radius: 14px;
    padding: 20px 24px;
    margin-bottom: 24px;
    color: #92400e;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.alert-warning.hidden {
    display: none;
}

.btn-ok {
    padding: 8px 20px;
    background: #f59e0b;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
    white-space: nowrap;
}

.btn-ok:hover {
    background: #d97706;
}

/* ===== FORM CARD ===== */
.form-card {
    background: #fff;
    border-radius: 14px;
    padding: 28px;
    border: 1px solid #e5e7eb;
    box-shadow: 0 2px 8px rgba(0,0,0,.06);
    margin-bottom: 24px;
}

.form-card label {
    display: block;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-card select,
.form-card input[type="file"],
.form-card textarea {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    font-family: inherit;
    margin-bottom: 20px;
    transition: border-color 0.2s;
}

.form-card select:focus,
.form-card textarea:focus {
    outline: none;
    border-color: #f59e0b;
}

.form-card input[type="file"] {
    padding: 8px;
}

.form-card textarea {
    resize: vertical;
    min-height: 120px;
}

.btn-submit {
    display: inline-block;
    padding: 12px 24px;
    background: #f59e0b;
    color: white;
    border: none;
    border-radius: 6px;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-submit:hover {
    background: #d97706;
}

.btn-submit:disabled {
    background: #d1d5db;
    cursor: not-allowed;
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
}
</style>

<!-- ===== HEADER ===== -->
<div class="student-header">
    <div class="student-header-content">
        <h1>📄 Submit Activity Sheet</h1>
    </div>
</div>

<div class="main-container">
    
    <h2 class="section-title">📝 Upload Your Work</h2>

    <?php if ($success): ?>
        <div class="alert-success">
            ✅ <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <?php foreach ($errors as $e): ?>
                <div>❌ <?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($moduleAlreadyGraded): ?>
        <div class="alert-warning" id="gradedWarning">
            <span>⚠️ This module has already been graded. You cannot submit another activity for this module.</span>
            <button type="button" class="btn-ok" onclick="dismissWarning()">OK</button>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="form-card">
        
        <label for="module_id">Select Module</label>
        <select name="module_id" id="module_id" required <?php echo $moduleAlreadyGraded ? 'disabled' : ''; ?>>
            <option value="">-- Choose a module --</option>
            <?php foreach ($modules as $m): ?>
                <option value="<?php echo $m['id']; ?>" <?php echo ($m['id'] == $moduleId) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($m['title']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="activity_sheet">Attach Activity Sheet (PDF, DOC, DOCX, JPG, PNG)</label>
        <input type="file" name="activity_sheet" id="activity_sheet" accept=".pdf, .doc, .docx, .jpg, .jpeg, .png" required <?php echo $moduleAlreadyGraded ? 'disabled' : ''; ?>>

        <label for="comments">Comments (optional)</label>
        <textarea name="comments" id="comments" placeholder="Add any notes or comments about your submission..." <?php echo $moduleAlreadyGraded ? 'disabled' : ''; ?>></textarea>

        <button type="submit" class="btn-submit" <?php echo $moduleAlreadyGraded ? 'disabled' : ''; ?>>📤 Submit to Tanglaw Facilitator</button>
    </form>

    <a href="student_modules.php" class="back-link">← Back to Modules</a>

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

function dismissWarning() {
    const warning = document.getElementById('gradedWarning');
    if (warning) {
        warning.classList.add('hidden');
        // Re-enable the form
        enableForm();
    }
}

function enableForm() {
    const moduleSelect = document.getElementById('module_id');
    const fileInput = document.getElementById('activity_sheet');
    const commentsTextarea = document.getElementById('comments');
    const submitButton = document.querySelector('.btn-submit');
    
    if (moduleSelect) moduleSelect.disabled = false;
    if (fileInput) fileInput.disabled = false;
    if (commentsTextarea) commentsTextarea.disabled = false;
    if (submitButton) submitButton.disabled = false;
    
    // Reset the module selection
    if (moduleSelect) moduleSelect.value = '';
}

document.addEventListener('DOMContentLoaded', function() {
    document.body.classList.remove('sidebar-open');
    document.body.classList.remove('sidebar-collapsed');
    
    // Check if module is selected and already graded
    const moduleSelect = document.getElementById('module_id');
    if (moduleSelect) {
        moduleSelect.addEventListener('change', function() {
            if (this.value) {
                // Redirect to same page with module_id parameter to check grade status
                window.location.href = 'submit_activity.php?module_id=' + this.value;
            }
        });
    }
});
</script>

<?php include 'footer.php'; ?>