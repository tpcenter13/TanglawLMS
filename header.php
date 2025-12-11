<?php
// Minimal header include: requires connection session handled by conn.php included before this.
if (!isset($loggedUser)) {
    // if header is included without conn.php, try to include
    include 'conn.php';
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Tanglaw LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<?php
// Add role-based body class when available
$bodyClass = '';
if (isset($loggedUser['role'])) {
    $role = $loggedUser['role'];
    if ($role === 'teacher') $bodyClass = 'role-teacher';
    elseif ($role === 'facilitator') $bodyClass = 'role-facilitator';
    elseif ($role === 'detainee' || $role === 'student') $bodyClass = 'role-student';
    elseif ($role === 'admin') $bodyClass = 'role-admin';
}
?>
<body<?= $bodyClass ? ' class="' . htmlspecialchars($bodyClass) . '"' : '' ?>>
<script>
// Provide a fallback toggleSidebar implementation if a page doesn't define it
if (typeof toggleSidebar !== 'function') {
    function toggleSidebar() {
        var body = document.body;
        var backdrop = document.getElementById('sidebarBackdrop');
        if (window.innerWidth <= 900) {
            body.classList.toggle('sidebar-open');
        } else {
            body.classList.toggle('sidebar-collapsed');
        }
        if (backdrop) backdrop.style.display = body.classList.contains('sidebar-open') ? 'block' : 'none';
    }
}
</script>
<div class="container">
