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

    if ($action == 'print_activity') {
        $activity_id = $_POST['activity_id'];
        $message = '✅ Activity sheet ready to print. Use browser print function (Ctrl+P).';
    }

    if ($action == 'distribute_module') {
        $module_id = $_POST['module_id'];
        $detainee_ids = $_POST['detainee_ids'] ?? [];
        if (count($detainee_ids) > 0) {
            $success_count = 0;
            foreach ($detainee_ids as $detainee_id) {
                $stmt = $conn->prepare("INSERT INTO distributions (module_id, detainee_id, facilitator_id, distributed_at) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iii", $module_id, $detainee_id, $facilitator_id);
                if ($stmt->execute()) $success_count++;
                $stmt->close();
            }
            $message = "✅ Distributed to $success_count detainee(s)";
        } else {
            $message = "❌ Please select at least one detainee";
        }
    }

    if ($action == 'collect_submission') {
        $detainee_id = $_POST['detainee_id'];
        $activity_id = $_POST['activity_id'];
        if (isset($_FILES['submission_file'])) {
            $filename = $_FILES['submission_file']['name'];
            $tmpname = $_FILES['submission_file']['tmp_name'];
            $upload_dir = 'uploads/submissions/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            $filepath = $upload_dir . time() . '_' . basename($filename);
            if (move_uploaded_file($tmpname, $filepath)) {
                $stmt = $conn->prepare("INSERT INTO submissions (detainee_id, activity_sheet_id, file_path, facilitator_id, status) VALUES (?, ?, ?, ?, 'submitted')");
                $stmt->bind_param("iisi", $detainee_id, $activity_id, $filepath, $facilitator_id);
                $message = $stmt->execute() ? '✅ Submission collected successfully' : '❌ Error saving submission';
                $stmt->close();
            } else {
                $message = '❌ Error uploading file';
            }
        }
    }

    if ($action == 'submit_to_teacher') {
        $submission_ids = $_POST['submission_ids'] ?? [];
        if (count($submission_ids) > 0) {
            $success_count = 0;
            foreach ($submission_ids as $sub_id) {
                $stmt = $conn->prepare("UPDATE submissions SET status = 'submitted' WHERE id = ?");
                $stmt->bind_param("i", $sub_id);
                if ($stmt->execute()) $success_count++;
                $stmt->close();
            }
            $message = "✅ Submitted $success_count submission(s) to teacher";
        } else {
            $message = "❌ Please select at least one submission";
        }
    }

    if ($action == 'approve_module') {
        $module_id = intval($_POST['module_id'] ?? 0);
        if ($module_id > 0) {
            $stmt = $conn->prepare("UPDATE modules SET approval_status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $facilitator_id, $module_id);
                $message = $stmt->execute() ? '✅ Module approved successfully' : '❌ Error approving module';
                $stmt->close();
            }
        }
    }

    if ($action == 'approve_activity') {
        $activity_id = intval($_POST['activity_id'] ?? 0);
        if ($activity_id > 0) {
            $stmt = $conn->prepare("UPDATE activity_sheets SET approval_status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("ii", $facilitator_id, $activity_id);
                $message = $stmt->execute() ? '✅ Activity approved successfully' : '❌ Error approving activity';
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

$recentPendingModules    = array_slice($pending_modules, 0, 5);
$recentPendingActivities = array_slice($pending_activities, 0, 5);
$recentSubmissions       = array_slice($collected_submissions, 0, 5);
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
        /* ── Header (matches student-header) ── */
        .facilitator-header {
            background: linear-gradient(120deg, #023047, #034563);
            color: white;
            padding: 18px 32px;
            width: calc(100% - 280px);
            position: fixed;
            top: 0;
            left: 280px;
            z-index: 300;
        }
        .facilitator-header .facilitator-header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .facilitator-header h1 { margin: 0 0 4px 0; font-size: 1.6rem; }
        .facilitator-header .header-meta { margin: 0; opacity: 0.85; font-size: 0.9rem; }
        .facilitator-header a { color: white; text-decoration: none; }
        .facilitator-header a:hover { text-decoration: underline; }

        /* ── Main container (matches student .main-container) ── */
        .main-container {
            margin-left: 280px;
            padding-top: 110px;
            padding-bottom: 60px;
            padding-left: 32px;
            padding-right: 32px;
            max-width: 1400px;
        }

        /* ── Section title (matches student .section-title) ── */
        .section-title {
            font-size: 1.15rem;
            font-weight: 700;
            margin: 28px 0 14px;
            color: #1e293b;
            letter-spacing: 0.01em;
        }

        /* ── Stats grid (matches student .stats-grid) ── */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 16px;
            margin-bottom: 8px;
        }
        .stat-card {
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 28px rgba(15,23,42,0.08);
            padding: 22px 20px;
            text-align: center;
        }
        .stat-card .kpi {
            font-size: 2.2rem;
            font-weight: 800;
            color: #023047;
            line-height: 1;
            margin-bottom: 6px;
        }
        .stat-card .label {
            font-size: 0.82rem;
            color: #64748b;
            font-weight: 500;
        }

        /* ── Content grid + cards (matches student .content-grid / .card) ── */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }
        @media (max-width: 900px) {
            .content-grid { grid-template-columns: 1fr; }
        }
        .card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 28px rgba(15,23,42,0.08);
        }
        .card h3 {
            margin: 0 0 16px 0;
            font-size: 1.05rem;
            color: #1e293b;
        }
        .card ul {
            list-style: none;
            margin: 0 0 16px 0;
            padding: 0;
        }
        .card ul li {
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .card ul li:last-child { border-bottom: none; }

        /* ── Status pills (reuse student style) ── */
        .status-pill {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 6px;
        }
        .status-pill--pending   { background: #fef9c3; color: #854d0e; }
        .status-pill--approved  { background: #dcfce7; color: #166534; }
        .status-pill--submitted { background: #dbeafe; color: #1d4ed8; }

        /* ── Module / submission meta ── */
        .item-meta {
            font-size: 0.82rem;
            color: #64748b;
            margin-top: 4px;
        }
        .item-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        /* ── Buttons (match student .button-secondary / .button-action) ── */
        .button-secondary {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: white;
            color: #334155;
            font-size: 0.82rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s;
        }
        .button-secondary:hover { background: #f1f5f9; }
        .button-action {
            display: inline-block;
            padding: 9px 20px;
            border-radius: 10px;
            background: #023047;
            color: white;
            font-size: 0.88rem;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: background 0.15s;
        }
        .button-action:hover { background: #034563; }

        /* ── Inline approve form inside list ── */
        .inline-form { display: inline; }
        .inline-form button {
            padding: 5px 12px;
            border-radius: 8px;
            border: none;
            background: #023047;
            color: white;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
        }
        .inline-form button:hover { background: #034563; }

        /* ── Alert ── */
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 18px;
            font-weight: 600;
        }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error   { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* ── Full-width sections (approve / distribute / collect / submit) ── */
        .full-card {
            background: white;
            padding: 28px;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 12px 28px rgba(15,23,42,0.08);
            margin-bottom: 24px;
        }
        .full-card h2 { margin-top: 0; font-size: 1.25rem; color: #1e293b; margin-bottom: 20px; }
        .full-card h3 { font-size: 1rem; margin: 0 0 14px 0; }
        table { width: 100%; border-collapse: collapse; }
        table thead { background: #f8fafc; }
        table th, table td { padding: 12px 14px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 0.88rem; }
        table th { font-weight: 700; color: #475569; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.04em; }

        /* ── Forms inside full-card ── */
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
        .form-row.full { grid-template-columns: 1fr; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-weight: 600; font-size: 0.88rem; color: #374151; }
        .form-group input, .form-group select {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 10px;
            font-size: 0.9rem;
        }
        .checkbox-group { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin: 12px 0; }
        .checkbox-item { display: flex; align-items: center; gap: 8px; font-size: 0.88rem; }
        .tip-box { margin-top: 16px; padding: 14px; background: #fef9c3; border-radius: 10px; font-size: 0.88rem; }

        /* ── Section visibility ── */
        .section { display: none; }
        .section.active { display: block; }

        /* ── Responsive ── */
        @media (max-width: 1024px) {
            .facilitator-header { left: 260px; width: calc(100% - 260px); }
            .main-container { margin-left: 260px; }
        }
        @media (max-width: 900px) {
            .facilitator-header { left: 0; width: 100%; }
            .main-container { margin-left: 0; padding: 120px 16px 48px; }
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body class="role-facilitator">
<?php include 'sidebar.php'; ?>
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()" style="display:none"></div>

<!-- ── Header ── -->
<div class="facilitator-header">
    <div class="facilitator-header-content">
        <div>
            <h1>Tanglaw Learn</h1>
            <p class="header-meta">Facilitator Dashboard</p>
        </div>
        <p>Welcome, <?= htmlspecialchars($_SESSION['loggedUser']['name']) ?> | <a href="logout.php">Logout</a></p>
    </div>
</div>

<!-- ── Main ── -->
<div class="main-container">

    <?php if ($message): ?>
        <div class="alert <?= strpos($message, '✅') !== false ? 'alert-success' : 'alert-error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- ════════════════════ DASHBOARD ════════════════════ -->
    <div class="section <?= $section == 'dashboard' ? 'active' : '' ?>">

        <h2 class="section-title">Quick Overview</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="kpi">📚 <?= count($modules) ?></div>
                <div class="label">Available Modules</div>
            </div>
            <div class="stat-card">
                <div class="kpi">📄 <?= count($activity_sheets) ?></div>
                <div class="label">Activity Sheets</div>
            </div>
            <div class="stat-card">
                <div class="kpi">👨‍🎓 <?= count($detainees) ?></div>
                <div class="label">Detainees</div>
            </div>
            <div class="stat-card">
                <div class="kpi">📥 <?= count($collected_submissions) ?></div>
                <div class="label">Collected Submissions</div>
            </div>
        </div>


    </div><!-- /dashboard -->


    <!-- ════════════════════ APPROVE MODULES ════════════════════ -->
    <div class="section <?= $section == 'approve' ? 'active' : '' ?>">
        <div class="full-card">
            <h2>✅ Approve Modules</h2>
            <?php if (empty($pending_modules)): ?>
                <p style="color:#64748b;">No modules awaiting approval.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Module</th><th>Subject</th><th>Grade</th><th>Teacher</th><th>Uploaded</th><th>Action</th>
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
                                <a href="view_module.php?id=<?= $mod['id'] ?>" target="_blank" class="button-secondary" style="margin-right:6px;">Preview</a>
                                <form method="POST" class="inline-form">
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


    <!-- ════════════════════ APPROVE ACTIVITIES ════════════════════ -->
    <div class="section <?= $section == 'approve_activities' ? 'active' : '' ?>">
        <div class="full-card">
            <h2>✅ Approve Activities</h2>
            <?php if (empty($pending_activities)): ?>
                <p style="color:#64748b;">No activities awaiting approval.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr><th>Activity</th><th>Module</th><th>Teacher</th><th>Created</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_activities as $act): ?>
                        <tr>
                            <td><?= htmlspecialchars($act['title']) ?></td>
                            <td><?= htmlspecialchars($act['module_title'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($act['teacher_name'] ?? 'N/A') ?></td>
                            <td><?= date('M d, Y', strtotime($act['created_at'])) ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($act['file_path']) ?>" target="_blank" class="button-secondary" style="margin-right:6px;">Preview</a>
                                <form method="POST" class="inline-form">
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


    <!-- ════════════════════ PRINT ════════════════════ -->
    <div class="section <?= $section == 'print' ? 'active' : '' ?>">
        <div class="full-card">
            <h2>🖨️ Print Activity Sheets</h2>
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
                <button type="submit" class="button-action">Prepare for Print</button>
            </form>
            <div class="tip-box">
                <strong>📝 Tip:</strong> After selecting an activity, use your browser's print function (Ctrl+P) to print.
            </div>
        </div>
    </div>


    <!-- ════════════════════ DISTRIBUTE ════════════════════ -->
    <div class="section <?= $section == 'distribute' ? 'active' : '' ?>">
        <div class="full-card">
            <h2>📦 Distribute Modules / Activity Sheets</h2>
            <form method="POST">
                <input type="hidden" name="action" value="distribute_module">
                <div class="form-row full">
                    <div class="form-group">
                        <label>Select Module / Activity</label>
                        <select name="module_id" required>
                            <option value="">Select Module</option>
                            <?php foreach($modules as $mod): ?>
                            <option value="<?= $mod['id'] ?>"><?= htmlspecialchars($mod['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <h3>Select Detainees:</h3>
                <div class="checkbox-group">
                    <?php foreach($detainees as $det): ?>
                    <div class="checkbox-item">
                        <input type="checkbox" name="detainee_ids[]" value="<?= $det['id'] ?>" id="det_<?= $det['id'] ?>">
                        <label for="det_<?= $det['id'] ?>"><?= htmlspecialchars($det['name']) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="button-action" style="margin-top:16px;">Distribute</button>
            </form>
        </div>
    </div>


    <!-- ════════════════════ COLLECT ════════════════════ -->
    <div class="section <?= $section == 'collect' ? 'active' : '' ?>">
        <div class="full-card">
            <h2>📥 Collect Activity Sheets from Detainees</h2>
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
                <button type="submit" class="button-action">Collect Submission</button>
            </form>
        </div>
    </div>


    <!-- ════════════════════ SUBMIT TO TEACHER ════════════════════ -->
    <div class="section <?= $section == 'submit' ? 'active' : '' ?>">
        <div class="full-card">
            <h2>📤 Submit Activity Sheets to Teacher</h2>
            <form method="POST">
                <input type="hidden" name="action" value="submit_to_teacher">
                <table>
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="select-all" onclick="document.querySelectorAll('input[name=\'submission_ids[]\']').forEach(el => el.checked = this.checked);"></th>
                            <th>Detainee</th><th>Activity</th><th>Submitted</th>
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
                <button type="submit" class="button-action" style="margin-top:16px;">Submit Selected to Teacher</button>
            </form>
        </div>
    </div>

</div><!-- /main-container -->

<script>
function toggleSidebar() {
    const body = document.body;
    const backdrop = document.getElementById('sidebarBackdrop');
    if (window.innerWidth <= 900) {
        body.classList.toggle('sidebar-open');
    } else {
        body.classList.toggle('sidebar-collapsed');
    }
    if (backdrop) backdrop.style.display = body.classList.contains('sidebar-open') ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', function() {
    document.body.classList.remove('sidebar-open');
    document.body.classList.remove('sidebar-collapsed');
});
</script>
</body>
</html>