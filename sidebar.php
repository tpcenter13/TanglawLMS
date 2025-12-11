<?php
/**
 * Clean sidebar component — role-aware menu
 */

$currentSection = $_GET['section'] ?? 'dashboard';
$currentPage = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['loggedUser']['role'] ?? '';

$menuItems = [];
if ($role === 'admin') {
    $menuItems = [
        'dashboard' => ['label' => '📊 Dashboard', 'url' => '?section=dashboard'],
        'teachers' => ['label' => '👨‍🏫 Teachers', 'url' => '?section=teachers'],
        'facilitators' => ['label' => '👥 Facilitators', 'url' => '?section=facilitators'],
        'detainees' => ['label' => '👨‍🎓 Students', 'url' => '?section=detainees'],
        'subjects' => ['label' => '📚 Subjects', 'url' => '?section=subjects'],
        'grades' => ['label' => '📊 Grade Levels', 'url' => '?section=grades'],
        'providers' => ['label' => '🏢 Providers', 'url' => '?section=providers'],
    ];
} elseif ($role === 'teacher') {
    $menuItems = [
        'dashboard' => ['label' => '📊 Dashboard', 'url' => '?section=dashboard'],
        'modules' => ['label' => '📚 Upload Modules', 'url' => '?section=modules'],
        'upload_activity' => ['label' => '📄 Upload Activity Sheets', 'url' => '?section=activities'],
        'submissions' => ['label' => '📥 Received Submissions', 'url' => '?section=submissions'],
        'compute_grades' => ['label' => '📊 Compute Grades', 'url' => '?section=grades'],
        'report_cards' => ['label' => '📋 Report Cards', 'url' => '?section=report'],
    ];
} elseif ($role === 'facilitator') {
    $menuItems = [
        'dashboard' => ['label' => '📊 Dashboard', 'url' => '?section=dashboard'],
        'print' => ['label' => '🖨️ Print Activities', 'url' => '?section=print'],
        'distribute' => ['label' => '📦 Distribute', 'url' => '?section=distribute'],
        'collect' => ['label' => '📥 Collect', 'url' => '?section=collect'],
        'submit' => ['label' => '📤 Submit to Teacher', 'url' => '?section=submit'],
    ];
} elseif ($role === 'student' || $role === 'detainee') {
    $menuItems = [
        'dashboard' => ['label' => '📊 Dashboard', 'url' => 'student_dashboard.php'],
        'modules' => ['label' => '📚 Modules', 'url' => 'student_modules.php'],
        'submit' => ['label' => '📄 Submit', 'url' => 'submit_activity.php'],
        'submissions' => ['label' => '📥 My Submissions', 'url' => 'my_submissions.php'],
    ];
}
?>
<style>
    /* Fixed sidebar with collapse/overlay support */
    .sidebar { 
        width: 260px; 
        position: fixed; 
        left: 0; 
        top: 0; 
        height: 100vh; 
        padding: 20px; 
        background: linear-gradient(180deg, #1e40af, #1e3a8a); 
        color: #fff; 
        overflow-y: auto; 
        z-index: 200; 
        transition: transform 0.25s ease; 
        box-shadow: 2px 0 6px rgba(0,0,0,0.06);
        box-sizing: border-box;
    }
    .main-content { 
        margin-left: 260px; 
        margin-top: 0; 
        padding: 24px; 
        min-height: 100vh;
        transition: margin-left 0.25s ease; 
        box-sizing: border-box; 
    }
    .container { 
        max-width: 1200px; 
        margin: 0 auto; 
        padding: 0 20px; 
        box-sizing: border-box; 
    }
    .sidebar .role-label { 
        margin: 8px 0 12px 0; 
        font-size: 13px; 
        color: rgba(255, 255, 255, 0.9);
    }
    .sidebar a { 
        display: block; 
        color: rgba(255, 255, 255, 0.95); 
        padding: 10px 12px; 
        text-decoration: none; 
        border-radius: 4px; 
        transition: all 0.2s;
    }
    .sidebar a:hover { 
        background: rgba(255, 255, 255, 0.08);
    }
    .sidebar a.active { 
        background: rgba(255, 255, 255, 0.15); 
        border-left: 4px solid #fbbf24; 
        color: #fff;
    }
    .sidebar .user-box { 
        background: rgba(255, 255, 255, 0.06); 
        padding: 10px; 
        border-radius: 6px; 
        margin-bottom: 12px; 
        font-size: 12px;
    }
    .sidebar hr {
        border: none; 
        border-top: 1px solid rgba(255, 255, 255, 0.12); 
        margin: 12px 0;
    }
    /* Role-specific colors */
    body.role-teacher .sidebar { 
        background: linear-gradient(180deg, #059669, #10b981);
    }
    body.role-facilitator .sidebar { 
        background: linear-gradient(180deg, #7c3aed, #a855f7);
    }
    body.role-student .sidebar { 
        background: linear-gradient(180deg, #f59e0b, #fbbf24);
    }
    /* Collapsed state (desktop) */
    body.sidebar-collapsed .sidebar { 
        transform: translateX(-260px);
    }
    body.sidebar-collapsed .main-content { 
        margin-left: 0;
    }
    /* Mobile behaviour: sidebar hidden by default, shown when body.sidebar-open */
    @media (max-width: 900px) {
        .sidebar { 
            transform: translateX(-260px);
            z-index: 250;
        }
        body.sidebar-open .sidebar { 
            transform: translateX(0);
        }
        .main-content { 
            margin-left: 0; 
            padding: 16px;
        }
        .sidebar-backdrop { 
            display: none; 
            position: fixed; 
            inset: 0; 
            background: rgba(0, 0, 0, 0.4); 
            z-index: 150;
        }
        body.sidebar-open .sidebar-backdrop { 
            display: block;
        }
    }
</style>
<aside class="sidebar" id="sidebar">
    <div class="role-label"><?= htmlspecialchars($role === 'student' || $role === 'detainee' ? 'Student' : ucfirst($role)) ?></div>
    
    <div class="user-box">
        <p style="margin:0 0 4px 0; color:rgba(255,255,255,.7)">Logged in as:</p>
        <p style="margin:0; color:#fff; font-weight:600"><?= htmlspecialchars($_SESSION['loggedUser']['name'] ?? ucfirst($role)) ?></p>
    </div>
    
    <nav>
        <?php foreach($menuItems as $k => $it): ?>
            <?php
                // Determine if link is active: check both section-based and page-based (for student pages)
                $isActive = false;
                if ($currentSection === $k) { $isActive = true; } // section match
                elseif ($role === 'student' || $role === 'detainee') {
                    // For students, also check if URL matches current page
                    if (($k === 'dashboard' && $currentPage === 'student_dashboard.php') ||
                        ($k === 'modules' && $currentPage === 'student_modules.php') ||
                        ($k === 'submit' && $currentPage === 'submit_activity.php') ||
                        ($k === 'submissions' && $currentPage === 'my_submissions.php')) {
                        $isActive = true;
                    }
                }
            ?>
            <a href="<?= $it['url'] ?>" class="<?= $isActive ? 'active' : '' ?>"><?= $it['label'] ?></a>
        <?php endforeach; ?>
        <hr style="border:none; border-top:1px solid rgba(255,255,255,.12); margin:12px 0">
        <a href="change_password.php">🔐 Change Password</a>
        <a href="logout.php" style="color:#ff6b6b">🚪 Logout</a>
    </nav>
</aside>
