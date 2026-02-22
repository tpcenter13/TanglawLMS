<?php
include 'conn.php';

// Check if user is facilitator
if (!isset($_SESSION['loggedUser']) || $_SESSION['loggedUser']['role'] !== 'facilitator') {
    header("Location: login.php");
    exit();
}

$facilitator_id = $_SESSION['loggedUser']['id'];
$section = $_GET['section'] ?? 'dashboard';
$message = '';

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


function ensureModuleApprovalSchema($conn) {
    if (!columnExists($conn, 'modules', 'approval_status')) {
        $conn->query("ALTER TABLE modules ADD COLUMN approval_status VARCHAR(20) NOT NULL DEFAULT 'approved'");
    }
    if (!columnExists($conn, 'modules', 'approved_by')) {
        $conn->query("ALTER TABLE modules ADD COLUMN approved_by INT NULL");
    }
    if (!columnExists($conn, 'modules', 'approved_at')) {
        $conn->query("ALTER TABLE modules ADD COLUMN approved_at TIMESTAMP NULL DEFAULT NULL");
    }
}

ensureModuleApprovalSchema($conn);

function ensureActivityApprovalSchema($conn) {
    if (!columnExists($conn, 'activity_sheets', 'approval_status')) {
        $conn->query("ALTER TABLE activity_sheets ADD COLUMN approval_status VARCHAR(20) NOT NULL DEFAULT 'approved'");
    }
    if (!columnExists($conn, 'activity_sheets', 'approved_by')) {
        $conn->query("ALTER TABLE activity_sheets ADD COLUMN approved_by INT NULL");
    }
    if (!columnExists($conn, 'activity_sheets', 'approved_at')) {
        $conn->query("ALTER TABLE activity_sheets ADD COLUMN approved_at TIMESTAMP NULL DEFAULT NULL");
    }
}

ensureActivityApprovalSchema($conn);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Print Activity Sheet
    if ($action == 'print_activity') {
        $activity_id = $_POST['activity_id'];
        // In a real system, this would generate a PDF or prompt to print
        $message = '✅ Activity sheet ready to print. Use browser print function (Ctrl+P).';
    }
    
    // Distribute Module
    if ($action == 'distribute_module') {
        $module_id = $_POST['module_id'];
        $detainee_ids = $_POST['detainee_ids'] ?? [];
        
        if (count($detainee_ids) > 0) {
            $success_count = 0;
            foreach ($detainee_ids as $detainee_id) {
                // Create a distribution record or log
                $stmt = $conn->prepare("INSERT INTO distributions (module_id, detainee_id, facilitator_id, distributed_at) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iii", $module_id, $detainee_id, $facilitator_id);
                if ($stmt->execute()) {
                    $success_count++;
                }
                $stmt->close();
            }
            $message = "✅ Distributed to $success_count detainee(s)";
        } else {
            $message = "❌ Please select at least one detainee";
        }
    }
    
    // Collect Submissions
    if ($action == 'collect_submission') {
        $detainee_id = $_POST['detainee_id'];
        $activity_id = $_POST['activity_id'];
        
        if (isset($_FILES['submission_file'])) {
            $filename = $_FILES['submission_file']['name'];
            $tmpname = $_FILES['submission_file']['tmp_name'];
            $upload_dir = 'uploads/submissions/';
            
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filepath = $upload_dir . time() . '_' . basename($filename);
            
            if (move_uploaded_file($tmpname, $filepath)) {
                $stmt = $conn->prepare("INSERT INTO submissions (detainee_id, activity_sheet_id, file_path, facilitator_id, status) VALUES (?, ?, ?, ?, 'submitted')");
                $stmt->bind_param("iisi", $detainee_id, $activity_id, $filepath, $facilitator_id);
                
                if ($stmt->execute()) {
                    $message = '✅ Submission collected successfully';
                } else {
                    $message = '❌ Error saving submission';
                }
                $stmt->close();
            } else {
                $message = '❌ Error uploading file';
            }
        }
    }
    
    // Submit to Teacher
    if ($action == 'submit_to_teacher') {
        $submission_ids = $_POST['submission_ids'] ?? [];
        
        if (count($submission_ids) > 0) {
            $success_count = 0;
            foreach ($submission_ids as $sub_id) {
                $stmt = $conn->prepare("UPDATE submissions SET status = 'submitted' WHERE id = ?");
                $stmt->bind_param("i", $sub_id);
                if ($stmt->execute()) {
                    $success_count++;
                }
                $stmt->close();
            }
            $message = "Submitted $success_count submission(s) to teacher";
        } else {
            $message = "Please select at least one submission";
        }
    }

    // Approve Module
    if ($action == 'approve_module') {
        $module_id = intval($_POST['module_id'] ?? 0);
        if ($module_id > 0) {
            $stmt = $conn->prepare("UPDATE modules SET approval_status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $facilitator_id, $module_id);
                if ($stmt->execute()) {
                    $message = 'Module approved successfully';
                } else {
                    $message = 'Error approving module';
                }
                $stmt->close();
            }
        }
    }

    // Approve Activity
    if ($action == 'approve_activity') {
        $activity_id = intval($_POST['activity_id'] ?? 0);
        if ($activity_id > 0) {
            $stmt = $conn->prepare("UPDATE activity_sheets SET approval_status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $facilitator_id, $activity_id);
                if ($stmt->execute()) {
                    $message = 'Activity approved successfully';
                } else {
                    $message = 'Error approving activity';
                }
                $stmt->close();
            }
        }
    }
}

// Get data
$modules = $conn->query("SELECT m.*, s.title as subject_title, gl.level FROM modules m 
    LEFT JOIN subjects s ON m.subject_id = s.id 
    LEFT JOIN grade_levels gl ON m.grade_level_id = gl.id 
    ORDER BY m.uploaded_at DESC")->fetch_all(MYSQLI_ASSOC);

$pending_modules = $conn->query("SELECT m.*, s.title as subject_title, gl.level, t.name as teacher_name FROM modules m 
    LEFT JOIN subjects s ON m.subject_id = s.id 
    LEFT JOIN grade_levels gl ON m.grade_level_id = gl.id 
    LEFT JOIN teachers t ON m.teacher_id = t.id
    WHERE m.approval_status = 'pending'
    ORDER BY m.uploaded_at DESC")->fetch_all(MYSQLI_ASSOC);

$pending_activities = $conn->query("SELECT a.*, m.title as module_title, t.name as teacher_name FROM activity_sheets a 
    LEFT JOIN modules m ON a.module_id = m.id 
    LEFT JOIN teachers t ON a.teacher_id = t.id
    WHERE a.approval_status = 'pending'
    ORDER BY a.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$activity_sheets = $conn->query("SELECT a.*, m.title as module_title FROM activity_sheets a 
    LEFT JOIN modules m ON a.module_id = m.id 
    WHERE a.approval_status = 'approved'
    ORDER BY a.created_at DESC")->fetch_all(MYSQLI_ASSOC);

$detainees = $conn->query("SELECT * FROM detainees WHERE archived = 0 ORDER BY name")->fetch_all(MYSQLI_ASSOC);

$collected_submissions = $conn->query("SELECT s.*, det.name, a.title as activity_title FROM submissions s 
    JOIN detainees det ON s.detainee_id = det.id 
    JOIN activity_sheets a ON s.activity_sheet_id = a.id 
    WHERE s.facilitator_id = $facilitator_id AND s.status IN ('collected', 'submitted')
    ORDER BY s.submitted_at DESC")->fetch_all(MYSQLI_ASSOC);

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Facilitator Dashboard - Tanglaw LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/portal-shared.css">
    <style>
        .facilitator-header {
            background: linear-gradient(120deg, #023047, #034563);
            color: white;
            padding: 18px 32px;
            border-radius: 0;
            width: calc(100% - 280px);
            position: fixed;
            top: 0;
            left: 280px;
            z-index: 300;
        }
        .facilitator-header .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .facilitator-header h1 { margin: 0 0 8px 0; }
        .facilitator-header p { margin: 0; opacity: 0.95; }
        .facilitator-header a { color: white; text-decoration: none; }
        .facilitator-header a:hover { text-decoration: underline; }
        .main-content { 
            margin-left: 280px;
            margin-top: 0;
            padding-top: 110px;
            padding-bottom: 60px;
        }
        .main-content .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
        }
        .main-content .card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 28px rgba(15,23,42,0.08);
            margin-bottom: 20px;
        }
        .main-content .card h3 {
            margin-top: 0;
            margin-bottom: 16px;
            font-size: 18px;
        }
        .main-content .card h4 {
            margin-top: 16px;
            margin-bottom: 12px;
            font-weight: 600;
        }
        .section-nav {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .section-nav button {
            padding: 10px 16px;
            border: none;
            background: #e2e8f0;
            cursor: pointer;
            border-radius: 10px;
            font-weight: 600;
        }
        .section-nav button.active {
            background: var(--portal-primary);
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
            padding: 20px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 28px rgba(15,23,42,0.08);
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
        .form-row { 
            display: center; 
            grid-template-columns: 1fr 1fr; 
            gap: 15px; 
            margin-bottom: 15px; 
            max-width: 100%;
        }
        .form-row.full { grid-template-columns: 1fr; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; margin-bottom: 5px; }
        .form-group input, .form-group select {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
        }
        .card h2 {
            margin-top: 0;
        }
        button[type="submit"] {
            background: var(--portal-primary);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
        }
        button[type="submit"]:hover { background: var(--portal-primary-2); }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 15px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table thead { background: #f3f4f6; }
        table th, table td { padding: 12px; text-align: left; border-bottom: 1px solid #e5e7eb; }
        .checkbox-group { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin: 10px 0; }
        .checkbox-item { display: flex; align-items: center; gap: 8px; }
        .checkbox-item input { cursor: pointer; }

        @media (max-width: 1024px) {
            .facilitator-header {
                left: 260px;
                width: calc(100% - 260px);
            }
            .main-content {
                margin-left: 260px;
                padding-top: 110px;
            }
        }

        @media (max-width: 900px) {
            .facilitator-header {
                left: 0;
                width: 100%;
            }
            .main-content {
                margin-left: 0;
                padding: 120px 16px 48px;
            }
        }
    </style>
</head>
<body class="role-facilitator">
<?php include 'sidebar.php'; ?>

<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()" style="display:none"></div>

<div class="facilitator-header portal-header">
    <div class="container">
        <h1>Tanglaw Learn</h1>
        <p>Welcome, <?= htmlspecialchars($_SESSION['loggedUser']['name']) ?> | <a href="logout.php" style="color:white;">Logout</a></p>
    </div>
</div>

<div class="main-content portal-main">
<div class="container">
    <?php if ($message): ?>
        <div class="alert <?= strpos($message, '✅') !== false ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <!-- navigation moved to sidebar -->

    <!-- DASHBOARD SECTION -->
  	<br> <br> <br> <br> <br>
    <div class="section <?= $section == 'dashboard' ? 'active' : '' ?>">
        <div class="grid">
            <div class="card">
                <div class="kpi"><?= count($modules) ?></div>
                <p class="small">📚 Available Modules</p>
            </div>
            <div class="card">
                <div class="kpi"><?= count($activity_sheets) ?></div>
                <p class="small">📄 Activity Sheets</p>
            </div>
            <div class="card">
                <div class="kpi"><?= count($detainees) ?></div>
                <p class="small">👨‍🎓 Detainees</p>
            </div>
            <div class="card">
                <div class="kpi"><?= count($collected_submissions) ?></div>
                <p class="small">📥 Collected Submissions</p>
            </div>
        </div>
    </div>

    <!-- APPROVE MODULES SECTION -->
    <div class="section <?= $section == 'approve' ? 'active' : '' ?>">
        <h2>✅ Approve Modules</h2>

        <div class="card">
            <?php if (empty($pending_modules)): ?>
                <p>No modules awaiting approval.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th>Subject</th>
                            <th>Grade Level</th>
                            <th>Teacher</th>
                            <th>Uploaded</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_modules as $mod): ?>
                        <tr>
                            <td><?= htmlspecialchars($mod['title']) ?></td>
                            <td><?= htmlspecialchars($mod['subject_title'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($mod['level'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($mod['teacher_name'] ?? 'N/A') ?></td>
                            <td><?= date('M d, Y', strtotime($mod['uploaded_at'])) ?></td>
                            <td>
                                <a href="view_module.php?id=<?= $mod['id'] ?>" target="_blank" style="margin-right:8px;">Preview</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="approve_module">
                                    <input type="hidden" name="module_id" value="<?= $mod['id'] ?>">
                                    <button type="submit">Approve</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- APPROVE ACTIVITIES SECTION -->
    <div class="section <?= $section == 'approve_activities' ? 'active' : '' ?>">
        <h2>✅ Approve Activities</h2>

        <div class="card">
            <?php if (empty($pending_activities)): ?>
                <p>No activities awaiting approval.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Activity</th>
                            <th>Module</th>
                            <th>Teacher</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_activities as $act): ?>
                        <tr>
                            <td><?= htmlspecialchars($act['title']) ?></td>
                            <td><?= htmlspecialchars($act['module_title'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($act['teacher_name'] ?? 'N/A') ?></td>
                            <td><?= date('M d, Y', strtotime($act['created_at'])) ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($act['file_path']) ?>" target="_blank" style="margin-right:8px;">Preview</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="approve_activity">
                                    <input type="hidden" name="activity_id" value="<?= $act['id'] ?>">
                                    <button type="submit">Approve</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- PRINT ACTIVITY SHEETS SECTION -->
    <div class="section <?= $section == 'print' ? 'active' : '' ?>">
        <h2>🖨️ Print Activity Sheets</h2>
        
        <div class="card">
            <h3>Select Activity to Print</h3>
            <form method="POST">
                <input type="hidden" name="action" value="print_activity">
                <div class="form-row full">
                    <div class="form-group">
                        <label>Activity Sheet</label>
                        <select name="activity_id" required>
                            <option value="">Select Activity</option>
                            <?php foreach($activity_sheets as $act): ?>
                            <option value="<?= $act['id'] ?>"><?= htmlspecialchars($act['title'] . ' (' . $act['module_title'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit">Prepare for Print</button>
            </form>
        </div>

        <div style="margin-top: 20px; padding: 15px; background: #fef08a; border-radius: 6px;">
            <strong>📝 Tip:</strong> After selecting an activity, use your browser's print function (Ctrl+P) to print the selected document.
        </div>
    </div>

    <!-- DISTRIBUTE MODULES/ACTIVITIES SECTION -->
    <div class="section <?= $section == 'distribute' ? 'active' : '' ?>">
        <h2>📦 Distribute Modules / Activity Sheets</h2>
        
        <div class="card">
            <h3>Distribute to Detainees</h3>
            <form method="POST">
                <input type="hidden" name="action" value="distribute_module">
                <div class="form-row full">
                    <div class="form-group">
                        <label>Select Module/Activity</label>
                        <select name="module_id" required>
                            <option value="">Select Module</option>
                            <?php foreach($modules as $mod): ?>
                            <option value="<?= $mod['id'] ?>"><?= htmlspecialchars($mod['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <h4>Select Detainees to Distribute To:</h4>
                <div class="checkbox-group">
                    <?php foreach($detainees as $det): ?>
                    <div class="checkbox-item">
                        <input type="checkbox" name="detainee_ids[]" value="<?= $det['id'] ?>" id="det_<?= $det['id'] ?>">
                        <label for="det_<?= $det['id'] ?>"><?= htmlspecialchars($det['name']) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <button type="submit" style="margin-top: 15px;">Distribute</button>
            </form>
        </div>
    </div>

    <!-- COLLECT SUBMISSIONS SECTION -->
    <div class="section <?= $section == 'collect' ? 'active' : '' ?>">
        <h2>📥 Collect Activity Sheets from Detainees</h2>
        
        <div class="card">
            <h3>Collect Submission</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="collect_submission">
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
                        <label>Activity Sheet</label>
                        <select name="activity_id" required>
                            <option value="">Select Activity</option>
                            <?php foreach($activity_sheets as $act): ?>
                            <option value="<?= $act['id'] ?>"><?= htmlspecialchars($act['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row full">
                    <div class="form-group">
                        <label>Upload Submitted File</label>
                        <input type="file" name="submission_file" required accept=".pdf,.doc,.docx,.jpg,.png">
                    </div>
                </div>
                <button type="submit">Collect Submission</button>
            </form>
        </div>
    </div>

    <!-- SUBMIT TO TEACHER SECTION -->
    <div class="section <?= $section == 'submit' ? 'active' : '' ?>">
        <h2>📤 Submit Activity Sheets to Teacher</h2>
        
        <div class="card">
            <h3>Submit Collected Activities</h3>
            <form method="POST">
                <input type="hidden" name="action" value="submit_to_teacher">
                
                <h4>Select Submissions to Submit:</h4>
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all" onclick="document.querySelectorAll('input[name=\"submission_ids[]\"]').forEach(el => el.checked = this.checked);"></th>
                            <th>Detainee</th>
                            <th>Activity</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($collected_submissions as $sub): ?>
                        <tr>
                            <td><input type="checkbox" name="submission_ids[]" value="<?= $sub['id'] ?>"></td>
                            <td><?= htmlspecialchars($sub['name']) ?></td>
                            <td><?= htmlspecialchars($sub['activity_title']) ?></td>
                            <td><?= date('M d, Y H:i', strtotime($sub['submitted_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <button type="submit" style="margin-top: 15px;">Submit Selected to Teacher</button>
            </form>
        </div>
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
