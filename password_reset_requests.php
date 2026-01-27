<?php
session_start();
include 'conn.php';

// Check if user is admin
if (!isset($_SESSION['loggedUser']) || $_SESSION['loggedUser']['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = intval($_POST['request_id'] ?? 0);
    
    if ($action == 'mark_done') {
        $stmt = $conn->prepare("UPDATE password_reset_requests SET status = 'done', resolved_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $request_id);
            if ($stmt->execute()) {
                $message = '✅ Request marked as done.';
            } else {
                $message = '❌ Failed to update request.';
            }
            $stmt->close();
        }
    } elseif ($action == 'mark_pending') {
        $stmt = $conn->prepare("UPDATE password_reset_requests SET status = 'pending', resolved_at = NULL WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $request_id);
            if ($stmt->execute()) {
                $message = '✅ Request marked as pending.';
            } else {
                $message = '❌ Failed to update request.';
            }
            $stmt->close();
        }
    } elseif ($action == 'delete') {
        $stmt = $conn->prepare("DELETE FROM password_reset_requests WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $request_id);
            if ($stmt->execute()) {
                $message = '✅ Request deleted successfully.';
            } else {
                $message = '❌ Failed to delete request.';
            }
            $stmt->close();
        }
    } elseif ($action == 'add_note') {
        $note = trim($_POST['note'] ?? '');
        $stmt = $conn->prepare("UPDATE password_reset_requests SET notes = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $note, $request_id);
            if ($stmt->execute()) {
                $message = '✅ Note added successfully.';
            } else {
                $message = '❌ Failed to add note.';
            }
            $stmt->close();
        }
    }
}

// Get all requests with user information
$requests_query = "
    SELECT 
        prr.*,
        CASE 
            WHEN prr.role = 'teacher' THEN t.name
            WHEN prr.role = 'facilitator' THEN f.name
            WHEN prr.role = 'detainee' THEN d.name
        END as user_name,
        CASE 
            WHEN prr.role = 'teacher' THEN t.id_number
            WHEN prr.role = 'facilitator' THEN f.id_number
            WHEN prr.role = 'detainee' THEN d.id_number
        END as user_id_number
    FROM password_reset_requests prr
    LEFT JOIN teachers t ON prr.user_id = t.id AND prr.role = 'teacher'
    LEFT JOIN facilitators f ON prr.user_id = f.id AND prr.role = 'facilitator'
    LEFT JOIN detainees d ON prr.user_id = d.id AND prr.role = 'detainee'
    ORDER BY 
        CASE WHEN prr.status = 'pending' THEN 0 ELSE 1 END,
        prr.requested_at DESC
";

$requests = [];
$result = $conn->query($requests_query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

// Count stats
$pending_count = 0;
$done_count = 0;
foreach ($requests as $req) {
    if ($req['status'] == 'pending') $pending_count++;
    else $done_count++;
}

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Password Reset Requests - Tanglaw LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .admin-header {
            background: linear-gradient(90deg, #003049 0%, #003049 100%);
            color: white;
            padding: 20px;
            border-radius: 0;
            margin: 0 0 0 260px;
            position: fixed;
            top: 0;
            left: 0;
            width: calc(100% - 260px);
            z-index: 300;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            height: 60px;
        }
        
        body {
            overflow: hidden;
        }
        
        html {
            height: 100%;
        }
        
        .main-content { 
            margin-left: 260px;
            padding-top: 80px;
            padding-bottom: 60px;
            height: 100vh;
            overflow-y: auto;
        }
        
        .main-content .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .stat-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
        }
        
        .stat-card.pending .value {
            color: #f59e0b;
        }
        
        .stat-card.done .value {
            color: #10b981;
        }
        
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .requests-table {
            background: white;
            border-radius: 10px;
            border: 1px solid #e5e7eb;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .table-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }
        
        .table-header h2 {
            margin: 0;
            font-size: 18px;
            color: #1f2937;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table thead {
            background: #f9fafb;
        }
        
        table th {
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        table td {
            padding: 16px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #1f2937;
        }
        
        table tbody tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-badge.done {
            background: #d1fae5;
            color: #065f46;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .role-badge.teacher {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .role-badge.facilitator {
            background: #e0e7ff;
            color: #4338ca;
        }
        
        .role-badge.detainee {
            background: #fef3c7;
            color: #92400e;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-done {
            background: #10b981;
            color: white;
        }
        
        .btn-done:hover {
            background: #059669;
        }
        
        .btn-pending {
            background: #f59e0b;
            color: white;
        }
        
        .btn-pending:hover {
            background: #d97706;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-delete:hover {
            background: #dc2626;
        }
        
        .btn-note {
            background: #6366f1;
            color: white;
        }
        
        .btn-note:hover {
            background: #4f46e5;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .empty-state svg {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }
        
        .notes-text {
            font-size: 13px;
            color: #6b7280;
            font-style: italic;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 9999;
            backdrop-filter: blur(4px);
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 28px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .modal-content h3 {
            margin-top: 0;
            margin-bottom: 20px;
            color: #1f2937;
            font-size: 20px;
        }
        
        .modal-content textarea {
            width: 100%;
            min-height: 120px;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            resize: vertical;
            box-sizing: border-box;
        }
        
        .modal-content textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .modal-buttons button {
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .btn-modal-cancel {
            background: #f3f4f6;
            color: #374151;
        }
        
        .btn-modal-cancel:hover {
            background: #e5e7eb;
        }
        
        .btn-modal-submit {
            background: #2563eb;
            color: white;
        }
        
        .btn-modal-submit:hover {
            background: #1d4ed8;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
            
            .admin-header {
                margin-left: 0;
                width: 100%;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            table {
                font-size: 12px;
            }
            
            table th,
            table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body class="role-admin">
<?php include 'sidebar.php'; ?>

<header class="admin-header">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="#" style="color:#fff;font-weight:700;text-decoration:none;font-size:18px">TANGLAW LEARN</a>
    </div>
    <div></div>
</header>

<div class="main-content">
    <div class="container">
        <?php if ($message): ?>
            <div class="alert <?= strpos($message, '✅') !== false ? 'alert-success' : 'alert-error' ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card pending">
                <h3>⏳ Pending Requests</h3>
                <div class="value"><?= $pending_count ?></div>
            </div>
            <div class="stat-card done">
                <h3>✅ Resolved Requests</h3>
                <div class="value"><?= $done_count ?></div>
            </div>
            <div class="stat-card">
                <h3>📊 Total Requests</h3>
                <div class="value"><?= count($requests) ?></div>
            </div>
        </div>
        
        <div class="requests-table">
            <div class="table-header">
                <h2>🔐 Password Reset Requests</h2>
            </div>
            
            <?php if (empty($requests)): ?>
                <div class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3>No password reset requests</h3>
                    <p>When users forget their passwords, their requests will appear here.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>User</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Requested</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td>
                                <span class="status-badge <?= $req['status'] ?>">
                                    <?= $req['status'] == 'pending' ? '⏳ Pending' : '✅ Done' ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($req['user_name'] ?? 'Unknown') ?></strong><br>
                                <span style="font-size: 12px; color: #6b7280;">
                                    ID: <?= htmlspecialchars($req['user_id_number'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td>
                                <span class="role-badge <?= $req['role'] ?>">
                                    <?= ucfirst($req['role']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($req['email']) ?></td>
                            <td>
                                <?= date('M d, Y', strtotime($req['requested_at'])) ?><br>
                                <span style="font-size: 12px; color: #6b7280;">
                                    <?= date('h:i A', strtotime($req['requested_at'])) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($req['notes']): ?>
                                    <span class="notes-text" title="<?= htmlspecialchars($req['notes']) ?>">
                                        <?= htmlspecialchars($req['notes']) ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #d1d5db;">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($req['status'] == 'pending'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_done">
                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                            <button type="submit" class="btn btn-done">✅ Done</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_pending">
                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                            <button type="submit" class="btn btn-pending">↩️ Undo</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-note" onclick="openNoteModal(<?= $req['id'] ?>, '<?= htmlspecialchars($req['notes'] ?? '', ENT_QUOTES) ?>')">
                                        📝 Note
                                    </button>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this request?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <button type="submit" class="btn btn-delete">🗑️ Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Note Modal -->
<div id="noteModal" class="modal">
    <div class="modal-content">
        <h3>📝 Add/Edit Note</h3>
        <form method="POST" id="noteForm">
            <input type="hidden" name="action" value="add_note">
            <input type="hidden" name="request_id" id="note_request_id">
            
            <textarea name="note" id="note_textarea" placeholder="Add notes about this request (e.g., 'Contacted user via email', 'Password reset completed')"></textarea>
            
            <div class="modal-buttons">
                <button type="button" class="btn-modal-cancel" onclick="closeNoteModal()">Cancel</button>
                <button type="submit" class="btn-modal-submit">Save Note</button>
            </div>
        </form>
    </div>
</div>

<script>
function openNoteModal(requestId, currentNote) {
    document.getElementById('note_request_id').value = requestId;
    document.getElementById('note_textarea').value = currentNote;
    document.getElementById('noteModal').classList.add('show');
}

function closeNoteModal() {
    document.getElementById('noteModal').classList.remove('show');
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('noteModal');
    if (event.target === modal) {
        closeNoteModal();
    }
}

// Close modal on Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeNoteModal();
    }
});
</script>

</body>
</html>