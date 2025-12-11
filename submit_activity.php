<?php
include 'conn.php';
include 'header.php';
echo '<div class="sidebar-backdrop" id="sidebarBackdrop"></div>';
include 'sidebar.php';
echo '<div class="main-content">';

if (!isset($_SESSION)) {
    session_start();
}

$studentId = $loggedUser['id'];

// Accept module_id as GET parameter
$moduleId = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;

// Get module info for display
$moduleTitle = '';
if ($moduleId > 0) {
    $stmt = $conn->prepare("SELECT id, title FROM modules WHERE id = ?");
    $stmt->bind_param("i", $moduleId);
    $stmt->execute();
    $modRes = $stmt->get_result();
    if ($modRes->num_rows > 0) {
        $row = $modRes->fetch_assoc();
        $moduleTitle = $row['title'];
    }
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic validation
    $mId = intval($_POST['module_id']);
    $comments = isset($_POST['comments']) ? trim($_POST['comments']) : '';

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
if ($gradeId !== null) {
    $stmt = $conn->prepare("SELECT id, title FROM modules WHERE grade_level_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $gradeId);
        $stmt->execute();
        $modResult = $stmt->get_result();
        while ($m = $modResult->fetch_assoc()) { $modules[] = $m; }
    }
}
?>

<style>
    .page-header {
        background: linear-gradient(90deg, #f59e0b 0%, #fbbf24 100%);
        color: white;
        padding: 20px;
        border-radius: 0;
        margin: 0 0 0 260px;
        width: calc(100% - 260px);
        position: fixed;
        top: 0;
        left: 0;
        z-index: 300;
    }
    .page-header h1 { margin: 0; font-size: 28px; font-weight: 700; }
    .page-header + .container {
        margin-top: 120px;
        margin-left: 260px;
        max-width: 1200px;
        margin-right: auto;
        padding: 0 20px;
    }
    /* Sections / cards spacing (match other dashboards) */
    .section { display: none; }
    .section.active { display: block; max-width: 100%; margin: 0; }
    .section.active .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap:20px; margin-bottom:24px; }
    .section.active .grid .card { background:white; padding:24px; border-radius:8px; border:1px solid #e5e7eb; box-shadow:0 1px 3px rgba(0,0,0,0.05);} 
    .section h2 { margin-top:0; margin-bottom:24px }
    .section .card { margin-top:120px; margin-bottom:20px }
    .section .grid { margin-top:120px; margin-bottom:20px }
</style>

<div class="page-header">
    <h1>📄 Submit Activity Sheet</h1>
</div>

<div class="container" style="margin-top: 100px;">
    <?php if ($success): ?>
        <div class="card" style="background:#ecfdf5; border-color:#bbf7d0;">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="card" style="background:#fff1f2; border-color:#fecaca;">
            <?php foreach ($errors as $e): ?>
                <div>- <?php echo htmlspecialchars($e); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" style="margin-top:15px;" class="card">
        <label>Module:</label><br>
        <select name="module_id" required style="width:90%; padding:6px;">
            <option value="">-- choose a module --</option>
            <?php foreach ($modules as $m): ?>
                <option value="<?php echo $m['id']; ?>" <?php echo ($m['id'] == $moduleId) ? 'selected' : ''; ?>><?php echo htmlspecialchars($m['title']); ?></option>
            <?php endforeach; ?>
        </select>
        <br><br>

        <label>Attach Activity Sheet (PDF, DOC, DOCX, JPG, PNG):</label><br>
        <input type="file" name="activity_sheet" accept=".pdf, .doc, .docx, .jpg, .jpeg, .png" required>
        <br><br>

        <label>Comments (optional):</label><br>
        <textarea name="comments" rows="5" style="width:90%;"></textarea>
        <br><br>

        <button type="submit" class="btn">Submit to Tanglaw Facilitator</button>
    </form>

    <hr>

<?php include 'footer.php'; ?>
</div>
<script>
function toggleSidebar() {
    document.body.classList.toggle('sidebar-open');
}
document.getElementById('sidebarBackdrop')?.addEventListener('click', function() {
    document.body.classList.remove('sidebar-open');
});
</script><p style="margin-top:12px;"><a href="student_modules.php">← Back to Modules</a></p>

<?php include 'footer.php'; ?>