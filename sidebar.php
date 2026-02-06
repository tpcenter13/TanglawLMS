<?php
/**
 * Modern sidebar component — role-aware menu with improved design
 */

$currentSection = $_GET['section'] ?? '';
$currentPage = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['loggedUser']['role'] ?? '';

$menuItems = [];
if ($role === 'admin') {
$menuItems = [
    'dashboard' => ['label' => 'Dashboard', 'url' => 'admin_dashboard.php?section=dashboard'],
    'teachers' => ['label' => 'Teachers', 'url' => 'admin_dashboard.php?section=teachers'],
    'facilitators' => ['label' => 'Facilitators', 'url' => 'admin_dashboard.php?section=facilitators'],
    'detainees' => ['label' => 'Students', 'url' => 'admin_dashboard.php?section=detainees'],
    'subjects' => ['label' => 'Subjects', 'url' => 'admin_dashboard.php?section=subjects'],
    'grades' => ['label' => 'Grade Levels', 'url' => 'admin_dashboard.php?section=grades'],
    'providers' => ['label' => 'Providers', 'url' => 'admin_dashboard.php?section=providers'],
    'reset_requests' => ['label' => 'Password Resets', 'url' => 'password_reset_requests.php'],
];

} elseif ($role === 'teacher') {
    $menuItems = [
        'dashboard' => ['label' => 'Dashboard', 'url' => '?section=dashboard'],
        'modules' => ['label' => 'Upload Modules', 'url' => '?section=modules'],
        'upload_activity' => ['label' => 'Upload Activity Sheets', 'url' => '?section=activities'],
        'submissions' => ['label' => 'Received Submissions', 'url' => '?section=submissions'],
        'compute_grades' => ['label' => 'Compute Grades', 'url' => '?section=grades'],
        'report_cards' => ['label' => 'Report Cards', 'url' => '?section=report'],
    ];
} elseif ($role === 'facilitator') {
    $menuItems = [
        'dashboard' => ['label' => 'Dashboard', 'url' => '?section=dashboard'],
        'approve' => ['label' => 'Approve Modules', 'url' => '?section=approve'],
        'print' => ['label' => 'Print Activities', 'url' => '?section=print'],
        'distribute' => ['label' => 'Distribute', 'url' => '?section=distribute'],
        'collect' => ['label' => 'Collect', 'url' => '?section=collect'],
        'submit' => ['label' => 'Submit to Teacher', 'url' => '?section=submit'],
    ];
} elseif ($role === 'student' || $role === 'detainee') {
    $menuItems = [
        'dashboard' => ['label' => 'Dashboard', 'url' => 'student_dashboard.php'],
        'modules' => ['label' => 'Modules', 'url' => 'student_modules.php'],
        'submit' => ['label' => 'Submit', 'url' => 'submit_activity.php'],
        'submissions' => ['label' => 'My Submissions', 'url' => 'my_submissions.php'],
    ];
}

function getSidebarIcon($key) {
    switch ($key) {
        case 'dashboard':
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 12l9-8 9 8v8a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        case 'teachers':
        case 'facilitators':
        case 'detainees':
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11a4 4 0 1 1-8 0 4 4 0 0 1 8 0zm-13 9a7 7 0 0 1 14 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        case 'subjects':
        case 'modules':
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5a2 2 0 0 1 2-2h9a3 3 0 0 1 3 3v13a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        case 'grades':
        case 'compute_grades':
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19h16M4 15h10M4 11h7M4 7h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>';
        case 'providers':
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 20h18M5 20V9l7-5 7 5v11M9 20v-6h6v6" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        case 'reset_requests':
        case 'change_password':
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 11V8a5 5 0 0 1 10 0v3M6 11h12v9H6z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        case 'upload_activity':
        case 'submit':
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0-12l-4 4m4-4l4 4M5 21h14" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        case 'submissions':
        case 'collect':
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v10H4zM8 7V4h8v3" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        case 'print':
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V4h12v5M6 17h12v3H6zM4 13h16v4H4z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        case 'distribute':
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10h18M3 10l4-5M3 10l4 5M21 14H3m18 0l-4-5m4 5l-4 5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        case 'report_cards':
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6zM9 13h6M9 9h6M9 17h4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        case 'approve':
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12l4 4 12-12" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        case 'logout':
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 17l5-5-5-5M21 12H9M9 19H4V5h5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        default:
            return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>';
    }
}
?>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        overflow-x: hidden;
    }
    :root {
        --sidebar-width: 280px;
        --sidebar-width-collapsed: 88px;
        --sidebar-width-tablet: 260px;
    }

    /* Sidebar Styles */
    .sidebar { 
        width: var(--sidebar-width); 
        position: fixed; 
        left: 0; 
        top: 0; 
        height: 100vh; 
        background: #023047;
        color: #fff; 
        overflow-y: auto; 
        overflow-x: hidden;
        z-index: 200; 
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 12px rgba(0, 0, 0, 0.1);
    }

    /* Scrollbar styling */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }

    /* Sidebar Header */
    .sidebar-header {
        padding: 16px 16px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        flex-direction: column;
        gap: 12px;
        position: relative;
    }

    .sidebar-header-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }

    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 16px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 10px;
        letter-spacing: 0.2px;
    }

    .sidebar-logo img {
        width: 36px;
        height: 36px;
        object-fit: contain;
    }

    .role-badge {
        display: inline-block;
        padding: 4px 12px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #fff;
    }

    /* User Box */
    .user-box { 
        margin: 20px;
        padding: 16px;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .user-box-label {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 6px;
    }

    .user-box-name {
        font-size: 15px;
        font-weight: 600;
        color: #fff;
    }

    /* Navigation */
    .sidebar-nav {
        flex: 1;
        padding: 8px 16px 16px;
    }

    .nav-section {
        margin-bottom: 24px;
    }

    .nav-section-title {
        padding: 8px 12px;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255, 255, 255, 0.5);
        font-weight: 600;
    }

    .sidebar-nav a { 
        display: flex;
        align-items: center;
        gap: 12px;
        color: rgba(255, 255, 255, 0.85); 
        padding: 12px 16px; 
        text-decoration: none; 
        border-radius: 10px; 
        transition: all 0.2s ease;
        margin-bottom: 4px;
        font-size: 14.5px;
        font-weight: 500;
        position: relative;
    }

    .sidebar-nav a .icon {
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .sidebar-nav a .icon svg {
        width: 20px;
        height: 20px;
        display: block;
    }
    .nav-logout .icon {
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .nav-logout .icon svg {
        width: 18px;
        height: 18px;
        display: block;
    }

    .sidebar-nav a .label {
        flex: 1;
    }

    .sidebar-nav a:hover { 
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        transform: translateX(2px);
    }

    .sidebar-nav a.active { 
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        font-weight: 600;
    }

    .sidebar-nav a.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 70%;
        background: #fff;
        border-radius: 0 4px 4px 0;
    }

    /* Divider */
    .nav-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
        margin: 16px 12px;
    }

    /* Logout section */
    .nav-logout {
        padding: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .nav-logout a {
        margin-bottom: 6px;
        color: rgba(255, 255, 255, 0.85);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        border-radius: 10px;
    }

    .nav-logout a:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        text-decoration: none;
    }

    .nav-logout a.logout-btn {
        color: #ff6b6b;
        font-weight: 600;
    }

    .nav-logout a.logout-btn:hover {
        background: rgba(255, 107, 107, 0.15);
        color: #ff8787;
    }

    /* Main Content */
    .main-content { 
        margin-left: 280px; 
        min-height: 100vh;
        padding: 32px;
        transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        background: #f8f9fa;
    }

    .container { 
        max-width: 1400px; 
        margin: 0 auto; 
    }

    /* Mobile Toggle Button */
    .sidebar-toggle {
        display: none;
        position: fixed;
        top: 50%;
        left: 0;
        transform: translateY(-50%);
        z-index: 300;
        width: 24px;
        height: 24px;
        background: #023047;
        border: none;
        border-radius: 12px;
        color: #fff;
        font-size: 14px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        transition: all 0.2s ease;
    }

    .sidebar-toggle:hover {
        background: #034563;
        transform: translateY(-50%) scale(1.05);
    }

    .sidebar-toggle:active {
        transform: translateY(-50%) scale(0.95);
    }

    .sidebar-toggle::before {
        content: ">";
        font-weight: 700;
        line-height: 1;
    }

    body.sidebar-open .sidebar-toggle::before {
        content: "<";
    }

    /* Backdrop */
    .sidebar-backdrop { 
        display: none; 
        position: fixed; 
        inset: 0; 
        background: rgba(0, 0, 0, 0.5); 
        z-index: 150;
        backdrop-filter: blur(2px);
    }

    body.sidebar-open .sidebar-backdrop { 
        display: block;
    }

    /* Role-specific colors */
    body.role-admin .sidebar { 
        background: #023047;
    }

    body.role-admin .collapse-toggle {
        border-color: #023047;
    }

    body.role-teacher .sidebar { 
        background: #f59e0b;
    }

    body.role-teacher .collapse-toggle {
        border-color: #f59e0b;
    }

    body.role-teacher .sidebar-toggle {
        background: #f59e0b;
    }

    body.role-teacher .sidebar-toggle:hover {
        background: #d97706;
    }

    body.role-facilitator .sidebar { 
        background: #7c3aed;
    }

    body.role-facilitator .collapse-toggle {
        border-color: #7c3aed;
    }

    body.role-facilitator .sidebar-toggle {
        background: #7c3aed;
    }

    body.role-facilitator .sidebar-toggle:hover {
        background: #6d28d9;
    }

    body.role-student .sidebar { 
        background: #0b1c33;
    }

    body.role-student .collapse-toggle {
        border-color: #0b1c33;
    }

    body.role-student .sidebar-toggle {
        background: #0b1c33;
    }

    body.role-student .sidebar-toggle:hover {
        background: #173a6b;
    }

    /* Tablet and Mobile Responsive */
    @media (max-width: 1024px) {
        :root {
            --sidebar-width: var(--sidebar-width-tablet);
        }
        .sidebar {
            width: var(--sidebar-width);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 24px;
        }
    }

    @media (max-width: 768px) {
        .collapse-toggle {
            display: none;
        }
        
        .sidebar-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sidebar { 
            transform: translateX(-100%);
            z-index: 250;
            box-shadow: none;
        }

        body.sidebar-open .sidebar { 
            transform: translateX(0);
            box-shadow: 8px 0 24px rgba(0, 0, 0, 0.2);
        }

        .main-content { 
            margin-left: 0; 
            padding: 80px 16px 24px;
        }

        .container {
            padding: 0;
        }
    }

    @media (max-width: 480px) {
        .sidebar {
            width: 100%;
            max-width: 300px;
        }

        .main-content {
            padding: 72px 12px 20px;
        }
    }
</style>

<!-- Mobile Toggle Button -->
<button class="sidebar-toggle" onclick="document.body.classList.toggle('sidebar-open')" aria-label="Toggle Sidebar"></button>

<!-- Sidebar Backdrop -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="document.body.classList.remove('sidebar-open')"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-header-top">
            <div class="sidebar-logo">
                <img src="tanglaw_logo.png" alt="Tanglaw LMS">
                <span>Tanglaw LMS</span>
            </div>
        </div>
        <span class="role-badge"><?= htmlspecialchars($role === 'student' || $role === 'detainee' ? 'Student' : $role) ?></span>
    </div>

    <div class="user-box">
        <div class="user-box-label">Logged in as</div>
        <div class="user-box-name"><?= htmlspecialchars($_SESSION['loggedUser']['name'] ?? ucfirst($role)) ?></div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <?php foreach($menuItems as $k => $it): ?>
                <?php
                    // Determine if link is active
                    $isActive = false;
                    
                    if ($role === 'student' || $role === 'detainee') {
                        if (($k === 'dashboard' && $currentPage === 'student_dashboard.php') ||
                            ($k === 'modules' && $currentPage === 'student_modules.php') ||
                            ($k === 'submit' && $currentPage === 'submit_activity.php') ||
                            ($k === 'submissions' && $currentPage === 'my_submissions.php')) {
                            $isActive = true;
                        }
                    } else {
                        if ($currentSection === $k) {
                            $isActive = true;
                        }
                    }
                ?>
                <a href="<?= $it['url'] ?>" class="<?= $isActive ? 'active' : '' ?>" onclick="if(window.innerWidth <= 768) document.body.classList.remove('sidebar-open')">
                    <span class="icon"><?= getSidebarIcon($k) ?></span>
                    <span class="label"><?= $it['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>


    <div class="nav-logout">
        <a href="change_password.php" onclick="if(window.innerWidth <= 768) document.body.classList.remove('sidebar-open')">
            <span class="icon"><?= getSidebarIcon('change_password') ?></span>
            <span class="label">Change Password</span>
        </a>
        <a href="logout.php" class="logout-btn">
            <span class="icon"><?= getSidebarIcon('logout') ?></span>
            <span class="label">Logout</span>
        </a>
    </div>
</aside>

<script>
// Close sidebar when clicking outside on mobile
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
            document.body.classList.remove('sidebar-open');
        }
    });
});
</script>
