<?php
// Simple admin login form that posts to login.php for processing
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Login - Tanglaw LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root{--card-bg:#ffffff;--page-bg:#f7fbff;--accent:#2563eb;--muted:#6b7280}
        html,body{height:100%;margin:0;font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,'Helvetica Neue',Arial}
        body{background:linear-gradient(180deg,#f0f7ff 0%,#ffffff 100%);display:flex;align-items:center;justify-content:center;padding:28px}
        .login-wrap{width:100%;max-width:420px}
        .card{background:var(--card-bg);padding:28px;border-radius:12px;border:1px solid #eef3fb;box-shadow:0 10px 30px rgba(16,24,40,0.06)}
        .brand{display:flex;align-items:center;gap:10px;margin-bottom:18px}
        .brand .logo{width:44px;height:44px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:linear-gradient(135deg,var(--accent),#1e40af);color:#fff;font-weight:700}
        h2{margin:0 0 6px 0;font-size:20px}
        p.lead{margin:0;color:var(--muted);font-size:13px;margin-bottom:16px}
        .form-group{margin-bottom:14px}
        label{display:block;font-size:13px;color:#374151;margin-bottom:6px;font-weight:600}
        input[type=text],input[type=password]{width:100%;padding:11px 12px;border:1px solid #e6eef8;border-radius:8px;font-size:14px;background:#fbfeff}
        .login-btn{width:100%;padding:11px;background:var(--accent);color:#fff;border:none;border-radius:10px;font-weight:700;font-size:15px}
        .login-btn:hover{filter:brightness(0.98)}
        .back{display:block;margin-top:12px;text-align:center;font-size:13px;color:var(--muted);text-decoration:none}
        .input-row{position:relative}
        .password-toggle{position:absolute;right:8px;top:38px;background:transparent;border:none;cursor:pointer;padding:6px}
        .muted{color:var(--muted);font-size:13px;margin-top:6px}
        @media(max-width:480px){.login-wrap{padding:0 12px}.card{padding:20px}}
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="card">
        <div class="brand">
            <div class="logo">TL</div>
            <div>
                <h2>Admin Sign in</h2>
                <p class="lead">Manage users, subjects and system settings</p>
            </div>
        </div>
        <form method="POST" action="login.php" autocomplete="on">
            <input type="hidden" name="role" value="admin">
            <div class="form-group">
                <label for="username_admin">Username</label>
                <input type="text" id="username_admin" name="username" value="admin" required>
            </div>
            <div class="form-group input-row">
                <label for="password_admin">Password</label>
                <input type="password" id="password_admin" name="password" value="admin123" required>
                <button type="button" class="password-toggle" aria-label="Toggle password" onclick="togglePassword()">
                    <svg id="eyeIcon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke="#374151" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="#374151" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
            <button class="login-btn" type="submit">Sign in</button>
        </form>
        <script>
            function togglePassword(){
                const pw = document.getElementById('password_admin');
                const eye = document.getElementById('eyeIcon');
                if(pw.type === 'password'){
                    pw.type = 'text';
                    eye.innerHTML = '<path d="M3 3l18 18" stroke="#374151" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke="#374151" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>';
                } else {
                    pw.type = 'password';
                    eye.innerHTML = '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke="#374151" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" stroke="#374151" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>';
                }
            }
        </script>
        <a class="back" href="login.php">← Back to role selection</a>
    </div>
</div>
</body>
</html>
