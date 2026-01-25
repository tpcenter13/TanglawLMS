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
        'dashboard' => ['label' => 'Dashboard', 'icon' => '📊', 'url' => '?section=dashboard'],
        'teachers' => ['label' => 'Teachers', 'icon' => '👨‍🏫', 'url' => '?section=teachers'],
        'facilitators' => ['label' => 'Facilitators', 'icon' => '👥', 'url' => '?section=facilitators'],
        'detainees' => ['label' => 'Students', 'icon' => '👨‍🎓', 'url' => '?section=detainees'],
        'subjects' => ['label' => 'Subjects', 'icon' => '📚', 'url' => '?section=subjects'],
        'grades' => ['label' => 'Grade Levels', 'icon' => '📊', 'url' => '?section=grades'],
        'providers' => ['label' => 'Providers', 'icon' => '🏢', 'url' => '?section=providers'],
    ];
} elseif ($role === 'teacher') {
    $menuItems = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => '📊', 'url' => '?section=dashboard'],
        'modules' => ['label' => 'Upload Modules', 'icon' => '📚', 'url' => '?section=modules'],
        'upload_activity' => ['label' => 'Upload Activity Sheets', 'icon' => '📄', 'url' => '?section=activities'],
        'submissions' => ['label' => 'Received Submissions', 'icon' => '📥', 'url' => '?section=submissions'],
        'compute_grades' => ['label' => 'Compute Grades', 'icon' => '📊', 'url' => '?section=grades'],
        'report_cards' => ['label' => 'Report Cards', 'icon' => '📋', 'url' => '?section=report'],
    ];
} elseif ($role === 'facilitator') {
    $menuItems = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => '📊', 'url' => '?section=dashboard'],
        'print' => ['label' => 'Print Activities', 'icon' => '🖨️', 'url' => '?section=print'],
        'distribute' => ['label' => 'Distribute', 'icon' => '📦', 'url' => '?section=distribute'],
        'collect' => ['label' => 'Collect', 'icon' => '📥', 'url' => '?section=collect'],
        'submit' => ['label' => 'Submit to Teacher', 'icon' => '📤', 'url' => '?section=submit'],
    ];
} elseif ($role === 'student' || $role === 'detainee') {
    $menuItems = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => '📊', 'url' => 'student_dashboard.php'],
        'modules' => ['label' => 'Modules', 'icon' => '📚', 'url' => 'student_modules.php'],
        'submit' => ['label' => 'Submit', 'icon' => '📄', 'url' => 'submit_activity.php'],
        'submissions' => ['label' => 'My Submissions', 'icon' => '📥', 'url' => 'my_submissions.php'],
    ];
}
?>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    /* Sidebar Styles */
    .sidebar { 
        width: 280px; 
        position: fixed; 
        left: 0; 
        top: 0; 
        height: 100vh; 
        background: #023047;
        color: #fff; 
        overflow-y: auto; 
        overflow-x: hidden;
        z-index: 200; 
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
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
        padding: 24px 20px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-logo {
        font-size: 24px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 8px;
        letter-spacing: -0.5px;
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
        font-size: 20px;
        width: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
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
    }

    .nav-logout a:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
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
        top: 16px;
        left: 16px;
        z-index: 300;
        width: 44px;
        height: 44px;
        background: #023047;
        border: none;
        border-radius: 10px;
        color: #fff;
        font-size: 20px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        transition: all 0.2s ease;
    }

    .sidebar-toggle:hover {
        background: #034563;
        transform: scale(1.05);
    }

    .sidebar-toggle:active {
        transform: scale(0.95);
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

    body.role-teacher .sidebar { 
        background: #059669;
    }

    body.role-teacher .sidebar-toggle {
        background: #059669;
    }

    body.role-teacher .sidebar-toggle:hover {
        background: #047857;
    }

    body.role-facilitator .sidebar { 
        background: #7c3aed;
    }

    body.role-facilitator .sidebar-toggle {
        background: #7c3aed;
    }

    body.role-facilitator .sidebar-toggle:hover {
        background: #6d28d9;
    }

    body.role-student .sidebar { 
        background: #f59e0b;
    }

    body.role-student .sidebar-toggle {
        background: #f59e0b;
    }

    body.role-student .sidebar-toggle:hover {
        background: #d97706;
    }

    /* Tablet and Mobile Responsive */
    @media (max-width: 1024px) {
        .sidebar {
            width: 260px;
        }

        .main-content {
            margin-left: 260px;
            padding: 24px;
        }
    }

    @media (max-width: 768px) {
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
<button class="sidebar-toggle" onclick="document.body.classList.toggle('sidebar-open')" aria-label="Toggle Sidebar">
    ☰
</button>

<!-- Sidebar Backdrop -->
<div class="sidebar-backdrop" onclick="document.body.classList.remove('sidebar-open')"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        
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
                    <span class="icon"><?= $it['icon'] ?></span>
                    <span class="label"><?= $it['label'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </nav>

    <div class="nav-logout">
        <a href="change_password.php" onclick="if(window.innerWidth <= 768) document.body.classList.remove('sidebar-open')">
            <span class="icon">🔐</span>
            <span class="label">Change Password</span>
        </a>
        <a href="logout.php" class="logout-btn">
            <span class="icon">🚪</span>
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