<?php
include 'conn.php';

// Check if user is teacher
if (!isset($_SESSION['loggedUser']) || $_SESSION['loggedUser']['role'] !== 'teacher') {
    header("Location: login.php");
    exit();
}

$teacher_id = $_SESSION['loggedUser']['id'];
$section = $_GET['section'] ?? 'dashboard';
$message = '';

// Handle module upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action == 'upload_module') {
        if (isset($_FILES['module_file'])) {
            $filename = $_FILES['module_file']['name'];
            $tmpname = $_FILES['module_file']['tmp_name'];
            $upload_dir = 'uploads/modules/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filepath = $upload_dir . time() . '_' . basename($filename);
            
            if (move_uploaded_file($tmpname, $filepath)) {
                $title = $_POST['module_title'];
                $subject_id = $_POST['subject_id'];
                $grade_level_id = $_POST['grade_level_id'];
                
                $stmt = $conn->prepare("INSERT INTO modules (title, subject_id, grade_level_id, file_path, teacher_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("siisi", $title, $subject_id, $grade_level_id, $filepath, $teacher_id);
                
                if ($stmt->execute()) {
                    $message = '✅ Module uploaded successfully';
                } else {
                    $message = '❌ Error saving module';
                }
                $stmt->close();
            } else {
                $message = '❌ Error uploading file';
            }
        }
    }
    
    if ($action == 'upload_activity') {
        if (isset($_FILES['activity_file'])) {
            $filename = $_FILES['activity_file']['name'];
            $tmpname = $_FILES['activity_file']['tmp_name'];
            $upload_dir = 'uploads/activities/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filepath = $upload_dir . time() . '_' . basename($filename);
            
            if (move_uploaded_file($tmpname, $filepath)) {
                $title = $_POST['activity_title'];
                $module_id = $_POST['module_id'];
                
                $stmt = $conn->prepare("INSERT INTO activity_sheets (title, module_id, file_path, teacher_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sisi", $title, $module_id, $filepath, $teacher_id);
                
                if ($stmt->execute()) {
                    $message = '✅ Activity sheet uploaded successfully';
                } else {
                    $message = '❌ Error saving activity sheet';
                }
                $stmt->close();
            } else {
                $message = '❌ Error uploading file';
            }
        }
    }
    
    if ($action == 'grade_submission') {
        $submission_id = $_POST['submission_id'];
        $grade = $_POST['grade'];
        $comments = $_POST['comments'];
        
        $stmt = $conn->prepare("UPDATE submissions SET grade = ?, comments = ?, status = 'graded' WHERE id = ?");
        $stmt->bind_param("dsi", $grade, $comments, $submission_id);
        
        if ($stmt->execute()) {
            $message = '✅ Grade submitted successfully';
        } else {
            $message = '❌ Error grading submission';
        }
        $stmt->close();
    }
    
    if ($action == 'generate_report_card') {
        $detainee_id = $_POST['detainee_id'];
        $subject_id = $_POST['subject_id'];
        $quarter = $_POST['quarter'];
        $grade = $_POST['grade'];
        
        // Check if report card exists
        $checkStmt = $conn->prepare("SELECT id FROM report_cards WHERE detainee_id = ? AND subject_id = ? AND quarter = ?");
        $checkStmt->bind_param("iii", $detainee_id, $subject_id, $quarter);
        $checkStmt->execute();
        
        if ($checkStmt->get_result()->num_rows > 0) {
            $updateStmt = $conn->prepare("UPDATE report_cards SET grade = ? WHERE detainee_id = ? AND subject_id = ? AND quarter = ?");
            $updateStmt->bind_param("diii", $grade, $detainee_id, $subject_id, $quarter);
            $updateStmt->execute();
            $message = '✅ Report card updated';
            $updateStmt->close();
        } else {
            $insertStmt = $conn->prepare("INSERT INTO report_cards (detainee_id, subject_id, teacher_id, quarter, grade) VALUES (?, ?, ?, ?, ?)");
            $insertStmt->bind_param("iiiii", $detainee_id, $subject_id, $teacher_id, $quarter, $grade);
            
            if ($insertStmt->execute()) {
                $message = '✅ Report card created successfully';
            } else {
                $message = '❌ Error creating report card';
            }
            $insertStmt->close();
        }
        $checkStmt->close();
    }

    // ====== MODULE EDIT / DELETE ======
    if ($action == 'edit_module') {
        $module_id = intval($_POST['module_id'] ?? 0);
        $title = $_POST['module_title'] ?? '';
        $subject_id = !empty($_POST['subject_id']) ? intval($_POST['subject_id']) : null;
        $grade_level_id = !empty($_POST['grade_level_id']) ? intval($_POST['grade_level_id']) : null;

        // Verify ownership
        $stmt = $conn->prepare("SELECT file_path, teacher_id FROM modules WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $module_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($res && (int)$res['teacher_id'] === (int)$teacher_id) {
                $oldPath = $res['file_path'];
                $newPath = $oldPath;
                // Handle optional new file
                if (!empty($_FILES['edit_module_file']['tmp_name'])) {
                    $upload_dir = 'uploads/modules/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $fname = time() . '_' . basename($_FILES['edit_module_file']['name']);
                    $target = $upload_dir . $fname;
                    if (@move_uploaded_file($_FILES['edit_module_file']['tmp_name'], $target)) {
                        $newPath = $target;
                        if (!empty($oldPath) && file_exists($oldPath)) {@unlink($oldPath);} 
                    }
                }

                $updateStmt = $conn->prepare("UPDATE modules SET title = ?, subject_id = ?, grade_level_id = ?, file_path = ? WHERE id = ? AND teacher_id = ?");
                $updateStmt->bind_param('siisii', $title, $subject_id, $grade_level_id, $newPath, $module_id, $teacher_id);
                if ($updateStmt->execute()) {
                    $message = '✅ Module updated successfully';
                } else {
                    $message = '❌ Failed to update module';
                }
                $updateStmt->close();
            } else {
                $message = '❌ Module not found or permission denied';
            }
        }
    }

    if ($action == 'delete_module') {
        $module_id = intval($_POST['module_id'] ?? 0);
        // Verify ownership and delete
        $stmt = $conn->prepare("SELECT file_path, teacher_id FROM modules WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $module_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($res && (int)$res['teacher_id'] === (int)$teacher_id) {
                if (!empty($res['file_path']) && file_exists($res['file_path'])) {@unlink($res['file_path']);}
                $del = $conn->prepare("DELETE FROM modules WHERE id = ? AND teacher_id = ?");
                $del->bind_param('ii', $module_id, $teacher_id);
                if ($del->execute()) {
                    $message = '✅ Module deleted';
                } else {
                    $message = '❌ Failed to delete module';
                }
                $del->close();
            } else {
                $message = '❌ Module not found or permission denied';
            }
        }
    }

    // ====== ACTIVITY SHEET EDIT / DELETE ======
    if ($action == 'edit_activity') {
        $activity_id = intval($_POST['activity_id'] ?? 0);
        $title = $_POST['activity_title'] ?? '';
        $module_id = !empty($_POST['module_id']) ? intval($_POST['module_id']) : null;

        // Verify ownership
        $stmt = $conn->prepare("SELECT file_path, teacher_id FROM activity_sheets WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $activity_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($res && (int)$res['teacher_id'] === (int)$teacher_id) {
                $oldPath = $res['file_path'];
                $newPath = $oldPath;
                // Handle optional new file
                if (!empty($_FILES['edit_activity_file']['tmp_name'])) {
                    $upload_dir = 'uploads/activities/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $fname = time() . '_' . basename($_FILES['edit_activity_file']['name']);
                    $target = $upload_dir . $fname;
                    if (@move_uploaded_file($_FILES['edit_activity_file']['tmp_name'], $target)) {
                        $newPath = $target;
                        if (!empty($oldPath) && file_exists($oldPath)) {@unlink($oldPath);} 
                    }
                }

                $updateStmt = $conn->prepare("UPDATE activity_sheets SET title = ?, module_id = ?, file_path = ? WHERE id = ? AND teacher_id = ?");
                $updateStmt->bind_param('sisii', $title, $module_id, $newPath, $activity_id, $teacher_id);
                if ($updateStmt->execute()) {
                    $message = '✅ Activity sheet updated successfully';
                } else {
                    $message = '❌ Failed to update activity sheet';
                }
                $updateStmt->close();
            } else {
                $message = '❌ Activity sheet not found or permission denied';
            }
        }
    }

    if ($action == 'delete_activity') {
        $activity_id = intval($_POST['activity_id'] ?? 0);
        $stmt = $conn->prepare("SELECT file_path, teacher_id FROM activity_sheets WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $activity_id);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($res && (int)$res['teacher_id'] === (int)$teacher_id) {
                if (!empty($res['file_path']) && file_exists($res['file_path'])) {@unlink($res['file_path']);}
                $del = $conn->prepare("DELETE FROM activity_sheets WHERE id = ? AND teacher_id = ?");
                $del->bind_param('ii', $activity_id, $teacher_id);
                if ($del->execute()) {
                    $message = '✅ Activity sheet deleted';
                } else {
                    $message = '❌ Failed to delete activity sheet';
                }
                $del->close();
            } else {
                $message = '❌ Activity sheet not found or permission denied';
            }
        }
    }
}

// Get data
$modules = $conn->query("SELECT m.*, s.title as subject_title, gl.level FROM modules m 
    LEFT JOIN subjects s ON m.subject_id = s.id 
    LEFT JOIN grade_levels gl ON m.grade_level_id = gl.id 
    WHERE m.teacher_id = $teacher_id ORDER BY m.uploaded_at DESC")->fetch_all(MYSQLI_ASSOC);

$activity_sheets = $conn->query("SELECT a.*, m.title as module_title FROM activity_sheets a 
    LEFT JOIN modules m ON a.module_id = m.id 
    WHERE a.teacher_id = $teacher_id ORDER BY a.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$submissions = $conn->query("SELECT s.*, det.name, a.title as activity_title FROM submissions s 
    JOIN detainees det ON s.detainee_id = det.id 
    JOIN activity_sheets a ON s.activity_sheet_id = a.id 
    ORDER BY s.submitted_at DESC")->fetch_all(MYSQLI_ASSOC);

$subjects = $conn->query("SELECT * FROM subjects WHERE archived = 0 ORDER BY title")->fetch_all(MYSQLI_ASSOC);
$grade_levels = $conn->query("SELECT * FROM grade_levels WHERE archived = 0 ORDER BY level")->fetch_all(MYSQLI_ASSOC);
$detainees = $conn->query("SELECT * FROM detainees WHERE archived = 0 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Teacher Dashboard - Tanglaw LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .teacher-header {
            background: linear-gradient(90deg, #059669 0%, #10b981 100%);
            color: white;
            padding: 20px;
            border-radius: 0;
            margin: 0 0 0 260px;
            width: calc(100% - 260px);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 300;
           	height: 50px;
        }
        .teacher-header .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .teacher-header h1 { margin: 0 0 8px 0; }
        .teacher-header p { margin: 0; opacity: 0.95; }
        .teacher-header a { color: white; text-decoration: none; }
        .teacher-header a:hover { text-decoration: underline; }
        .main-content { 
            margin-left: 260px;
            margin-top: 0;
            /* provide consistent top offset for fixed header */
            padding-top: 100px;
        }
        .main-content .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .section-nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .section-nav button {
            padding: 10px 15px;
            border: none;
            background: #e5e7eb;
            cursor: pointer;
            border-radius: 6px;
            font-weight: 600;
        }
        .section-nav button.active {
            background: #10b981;
            color: white;
        }
        .section { 
            display: none; 
        }
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
            background: white;
            padding: 16px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .section h2 {
            margin-top: 0;
            margin-bottom: 24px;
        }
        .section .card {
            margin-top: 0;
            margin-bottom: 20px;
        }
        .section .grid {
            margin-top: 0;
            margin-bottom: 20px;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; }
        .form-row.full { grid-template-columns: 1fr; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea {
            padding: 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
        }
        button[type="submit"] {
            background: var(--success);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        button[type="submit"]:hover { background: #15803d; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 15px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table thead { background: #f3f4f6; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        /* Buttons */
        .btn { padding:6px 10px; border-radius:6px; border: none; cursor: pointer; font-weight:600; font-size:0.9rem; }
        .btn:focus { outline:2px solid rgba(16,185,129,0.25); }
        .btn-edit { background:#2563eb; color:white; }
        .btn-edit:hover { background:#1e40af; }
        .btn-delete { background:#ef4444; color:white; }
        .btn-delete:hover { background:#dc2626; }
        .btn-secondary { background:#6b7280; color:white; }
        /* Modal styles for edit module */
        #editModuleModal { position: fixed; left:0; top:0; width:100%; height:100%; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.45); z-index:400; }
        #editModuleModal .modal-content { background:white; padding:20px; border-radius:8px; width:95%; max-width:720px; box-shadow:0 6px 18px rgba(0,0,0,0.12); }
        #editModuleModal h3 { margin-top:0; }
        /* Activity modal shares styles but has its own id */
        #editActivityModal { position: fixed; left:0; top:0; width:100%; height:100%; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,0.45); z-index:400; }
        #editActivityModal .modal-content { background:white; padding:20px; border-radius:8px; width:95%; max-width:720px; box-shadow:0 6px 18px rgba(0,0,0,0.12); }
    </style>
</head>
<body class="role-teacher">
<?php include 'sidebar.php'; ?>

<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()" style="display:none"></div>

<div class="teacher-header">
    <div class="container">
        <h1>Tanglaw Learn</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['loggedUser']['name']) ?> | <a href="logout.php" style="color:white;">Logout</a></p>
    </div>
</div>

<div class="main-content">
<div class="container">
    <?php if ($message): ?>
        <div class="alert <?= strpos($message, '✅') !== false ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- DASHBOARD SECTION -->
   <br> <br> <br> <br> <br>
    <div class="section <?= $section == 'dashboard' ? 'active' : '' ?>">
        <div class="grid">
            <div class="card">
                <div class="kpi"><?= count($modules) ?></div>
                <p class="small">📚 Modules Uploaded</p>
            </div>
            <div class="card">
                <div class="kpi"><?= count($activity_sheets) ?></div>
                <p class="small">📄 Activity Sheets</p>
            </div>
            <div class="card">
                <div class="kpi"><?= count(array_filter($submissions, fn($s) => $s['status'] == 'pending')) ?></div>
                <p class="small">⏳ Pending Submissions</p>
            </div>
            <div class="card">
                <div class="kpi"><?= count(array_filter($submissions, fn($s) => $s['status'] == 'graded')) ?></div>
                <p class="small">✅ Graded</p>
            </div>
        </div>
    </div>

    <!-- UPLOAD MODULES SECTION -->
    <div class="section <?= $section == 'modules' ? 'active' : '' ?>">
      <br> 
        <h2>📚 Upload Modules</h2>
        
        <div class="card">
            <h3>Add New Module</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_module">
                <div class="form-row">
                    <div class="form-group">
                        <label>Module Title</label>
                        <input type="text" name="module_title" required>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php foreach($subjects as $subj): ?>
                            <option value="<?= $subj['id'] ?>"><?= htmlspecialchars($subj['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Grade Level</label>
                        <select name="grade_level_id" required>
                            <option value="">Select Grade Level</option>
                            <?php foreach($grade_levels as $gl): ?>
                            <option value="<?= $gl['id'] ?>"><?= htmlspecialchars($gl['level']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Upload File (PDF, DOC, etc.)</label>
                        <input type="file" name="module_file" required accept=".pdf,.doc,.docx,.ppt,.pptx">
                    </div>
                </div>
                <button type="submit">Upload Module</button>
            </form>
        </div>

        <h3 style="margin-top: 30px;">📚 Modules</h3>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Subject</th>
                    <th>Grade Level</th>
                    <th>Uploaded</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($modules as $mod): ?>
                <tr>
                    <td><?= htmlspecialchars($mod['title']) ?></td>
                    <td><?= htmlspecialchars($mod['subject_title'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($mod['level'] ?? 'N/A') ?></td>
                    <td><?= date('M d, Y', strtotime($mod['uploaded_at'])) ?></td>
                    <td style="white-space:nowrap">
                        <button type="button" class="btn btn-edit" onclick='openEditModule(<?= json_encode($mod, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' aria-label="Edit module <?= htmlspecialchars($mod['title']) ?>" title="Edit">✏️ Edit</button>
                        <form method="POST" style="display:inline;margin-left:8px;" onsubmit="return confirm('Are you sure you want to delete this module? This will remove the file as well.')">
                            <input type="hidden" name="action" value="delete_module">
                            <input type="hidden" name="module_id" value="<?= $mod['id'] ?>">
                            <button type="submit" class="btn btn-delete" aria-label="Delete module <?= htmlspecialchars($mod['title']) ?>" title="Delete">🗑️ Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Edit Activity Modal -->
    <div id="editActivityModal" style="display:none;">
        <div class="modal-content">
            <h3>Edit Activity Sheet</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_activity">
                <input type="hidden" name="activity_id" id="edit_activity_id">
                <div class="form-row">
                    <div class="form-group">
                        <label>Activity Title</label>
                        <input type="text" name="activity_title" id="edit_activity_title" required>
                    </div>
                    <div class="form-group">
                        <label>Module</label>
                        <select name="module_id" id="edit_activity_module_id" required>
                            <option value="">Select Module</option>
                            <?php foreach($modules as $mod): ?>
                            <option value="<?= $mod['id'] ?>"><?= htmlspecialchars($mod['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Replace File (optional)</label>
                        <input type="file" name="edit_activity_file" id="edit_activity_file" accept=".pdf,.doc,.docx">
                    </div>
                </div>
                <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px;">
                    <button type="button" onclick="closeEditActivity()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-edit">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
        <!-- Edit Module Modal -->
        <div id="editModuleModal" style="display:none;">
            <div class="modal-content">
                <h3>Edit Module</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit_module">
                    <input type="hidden" name="module_id" id="edit_module_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Module Title</label>
                            <input type="text" name="module_title" id="edit_module_title" required>
                        </div>
                        <div class="form-group">
                            <label>Subject</label>
                            <select name="subject_id" id="edit_module_subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach($subjects as $subj): ?>
                                <option value="<?= $subj['id'] ?>"><?= htmlspecialchars($subj['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row full">
                        <div class="form-group">
                            <label>Grade Level</label>
                            <select name="grade_level_id" id="edit_module_grade_level_id" required>
                                <option value="">Select Grade Level</option>
                                <?php foreach($grade_levels as $gl): ?>
                                <option value="<?= $gl['id'] ?>"><?= htmlspecialchars($gl['level']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row full">
                        <div class="form-group">
                            <label>Replace File (optional)</label>
                            <input type="file" name="edit_module_file" id="edit_module_file" accept=".pdf,.doc,.docx,.ppt,.pptx">
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:10px;">
                        <button type="button" onclick="closeEditModal()" style="background:#6b7280;color:white;padding:8px 12px;border:none;border-radius:6px;">Cancel</button>
                        <button type="submit" style="background:#10b981;color:white;padding:8px 12px;border:none;border-radius:6px;">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

    <!-- UPLOAD ACTIVITIES SECTION -->
    <div class="section <?= $section == 'activities' ? 'active' : '' ?>">
        <h2>📄 Upload Activity Sheets</h2>
        
        <div class="card">
            <h3>Add New Activity Sheet</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_activity">
                <div class="form-row">
                    <div class="form-group">
                        <label>Activity Title</label>
                        <input type="text" name="activity_title" required>
                    </div>
                    <div class="form-group">
                        <label>Module</label>
                        <select name="module_id" required>
                            <option value="">Select Module</option>
                            <?php foreach($modules as $mod): ?>
                            <option value="<?= $mod['id'] ?>"><?= htmlspecialchars($mod['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Upload File</label>
                        <input type="file" name="activity_file" required accept=".pdf,.doc,.docx">
                    </div>
                </div>
                <button type="submit">Upload Activity Sheet</button>
            </form>
        </div>

        <h3 style="margin-top: 30px;">📄 My Activity Sheets</h3>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Module</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($activity_sheets as $act): ?>
                <tr>
                    <td><?= htmlspecialchars($act['title']) ?></td>
                    <td><?= htmlspecialchars($act['module_title'] ?? 'N/A') ?></td>
                    <td><?= date('M d, Y', strtotime($act['created_at'])) ?></td>
                        <td style="white-space:nowrap">
                            <button type="button" class="btn btn-edit" onclick='openEditActivity(<?= json_encode($act, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>)' aria-label="Edit activity <?= htmlspecialchars($act['title']) ?>">✏️ Edit</button>
                            <form method="POST" style="display:inline;margin-left:8px;" onsubmit="return confirm('Delete this activity sheet? This removes the file.')">
                                <input type="hidden" name="action" value="delete_activity">
                                <input type="hidden" name="activity_id" value="<?= $act['id'] ?>">
                                <button type="submit" class="btn btn-delete" aria-label="Delete activity <?= htmlspecialchars($act['title']) ?>">🗑️ Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- RECEIVED SUBMISSIONS SECTION -->
    <div class="section <?= $section == 'submissions' ? 'active' : '' ?>">
        <h2>📥 Received Submissions</h2>
        
        <table>
            <thead>
                <tr>
                    <th>Detainee</th>
                    <th>Activity</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($submissions as $sub): ?>
                <tr>
                    <td><?= htmlspecialchars($sub['name']) ?></td>
                    <td><?= htmlspecialchars($sub['activity_title']) ?></td>
                    <td><?= date('M d, Y H:i', strtotime($sub['submitted_at'])) ?></td>
                    <td><strong><?= ucfirst($sub['status']) ?></strong></td>
                    <td><?= $sub['grade'] ?? '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- COMPUTE GRADES SECTION -->
    <div class="section <?= $section == 'grades' ? 'active' : '' ?>">
        <h2>📊 Compute Grades</h2>
        
        <div class="card">
            <h3>Grade Submission</h3>
            <form method="POST">
                <input type="hidden" name="action" value="grade_submission">
                <div class="form-row">
                    <div class="form-group">
                        <label>Submission</label>
                        <select name="submission_id" required>
                            <option value="">Select Submission</option>
                            <?php foreach($submissions as $sub): ?>
                            <?php if ($sub['status'] != 'graded'): ?>
                            <option value="<?= $sub['id'] ?>"><?= htmlspecialchars($sub['name'] . ' - ' . $sub['activity_title']) ?></option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Grade (0-100)</label>
                        <input type="number" name="grade" min="0" max="100" step="0.5" required>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Comments</label>
                        <textarea name="comments" rows="4" placeholder="Optional feedback..."></textarea>
                    </div>
                </div>
                <button type="submit">Submit Grade</button>
            </form>
        </div>
    </div>

    <!-- REPORT CARDS SECTION -->
    <div class="section <?= $section == 'report' ? 'active' : '' ?>">
        <h2>📋 Report Cards</h2>
        
        <div class="card">
            <h3>Generate Report Card</h3>
            <form method="POST">
                <input type="hidden" name="action" value="generate_report_card">
                <div class="form-row">
                    <div class="form-group">
                        <label>Detainee</label>
                        <select name="detainee_id" required>
                            <option value="">Select Detainee</option>
                            <?php foreach($detainees as $det): ?>
                            <option value="<?= $det['id'] ?>"><?= htmlspecialchars($det['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php foreach($subjects as $subj): ?>
                            <option value="<?= $subj['id'] ?>"><?= htmlspecialchars($subj['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Quarter</label>
                        <select name="quarter" required>
                            <option value="">Select Quarter</option>
                            <option value="1">1st Quarter</option>
                            <option value="2">2nd Quarter</option>
                            <option value="3">3rd Quarter</option>
                            <option value="4">4th Quarter</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Grade (0-100)</label>
                        <input type="number" name="grade" min="0" max="100" step="0.5" required>
                    </div>
                </div>
                <button type="submit">Generate Report Card</button>
            </form>
        </div>
    </div>

</div>

</body>
</html>
<script>
    function toggleSidebar() {
        var body = document.body;
        var backdrop = document.getElementById('sidebarBackdrop');
        if (window.innerWidth <= 900) {
            // mobile: toggle overlay
            if (body.classList.contains('sidebar-open')) {
                body.classList.remove('sidebar-open');
            } else {
                body.classList.add('sidebar-open');
            }
        } else {
            // desktop: collapse/expand
            body.classList.toggle('sidebar-collapsed');
        }
        // update backdrop visibility (for noscript fallback)
        if (backdrop) backdrop.style.display = body.classList.contains('sidebar-open') ? 'block' : 'none';
    }
    // Ensure initial state: not collapsed
    document.addEventListener('DOMContentLoaded', function(){
        document.body.classList.remove('sidebar-open');
        document.body.classList.remove('sidebar-collapsed');
    });
    
    // Edit Module modal helpers
    function openEditModule(mod) {
        try {
            document.getElementById('edit_module_id').value = mod.id || '';
            document.getElementById('edit_module_title').value = mod.title || '';
            var subj = document.getElementById('edit_module_subject_id');
            if (subj) subj.value = mod.subject_id || '';
            var gl = document.getElementById('edit_module_grade_level_id');
            if (gl) gl.value = mod.grade_level_id || '';
            var modal = document.getElementById('editModuleModal');
            if (modal) modal.style.display = 'flex';
            // focus title for quick edit
            var titleInput = document.getElementById('edit_module_title');
            if (titleInput) { titleInput.focus(); titleInput.select(); }
            window.scrollTo(0,0);
        } catch (e) {
            console.error('Error opening edit modal', e);
        }
    }
    function closeEditModal() {
        var modal = document.getElementById('editModuleModal');
        if (modal) modal.style.display = 'none';
    }
    // close when clicking backdrop (supports both modals)
    document.addEventListener('click', function(ev){
        var modalModule = document.getElementById('editModuleModal');
        var modalActivity = document.getElementById('editActivityModal');
        if (modalModule && ev.target === modalModule) closeEditModal();
        if (modalActivity && ev.target === modalActivity) closeEditActivity();
    });

    // Activity sheet modal helpers
    function openEditActivity(act) {
        try {
            document.getElementById('edit_activity_id').value = act.id || '';
            document.getElementById('edit_activity_title').value = act.title || '';
            var modsel = document.getElementById('edit_activity_module_id');
            if (modsel) modsel.value = act.module_id || '';
            var modal = document.getElementById('editActivityModal');
            if (modal) modal.style.display = 'flex';
            var titleInput = document.getElementById('edit_activity_title');
            if (titleInput) { titleInput.focus(); titleInput.select(); }
            window.scrollTo(0,0);
        } catch (e) { console.error('Error opening edit activity modal', e); }
    }
    function closeEditActivity() {
        var modal = document.getElementById('editActivityModal');
        if (modal) modal.style.display = 'none';
    }
</script></body>
</html>
