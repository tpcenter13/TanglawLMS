<?php
// Teacher login posts to login.php (server logic lives there)
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Teacher Login - Tanglaw LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root{--card-bg:#fff;--accent:#2563eb;--muted:#6b7280}
        body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,Arial;margin:0;background:linear-gradient(180deg,#f7fbff,#fff);display:flex;align-items:center;justify-content:center;height:100vh;padding:24px}
        .login-wrap{width:100%;max-width:420px}
        .card{background:var(--card-bg);padding:26px;border-radius:12px;border:1px solid #eef3fb;box-shadow:0 10px 30px rgba(16,24,40,0.05)}
        .brand{display:flex;align-items:center;gap:10px;margin-bottom:14px}
        .brand .logo{width:40px;height:40px;border-radius:8px;background:linear-gradient(135deg,var(--accent),#1e40af);color:#fff;display:flex;align-items:center;justify-content:center}
        h2{margin:0 0 6px 0}
        p.lead{margin:0;color:var(--muted);font-size:13px;margin-bottom:12px}
        label{display:block;font-size:13px;color:#374151;margin-bottom:6px;font-weight:600}
        input[type=text]{width:100%;padding:11px;border:1px solid #e6eef8;border-radius:8px;background:#fbfeff}
        .login-btn{width:100%;padding:11px;background:var(--accent);color:#fff;border:none;border-radius:10px;font-weight:700}
        .back{display:block;margin-top:12px;text-align:center;color:var(--muted);text-decoration:none}
        @media(max-width:480px){.login-wrap{padding:0 12px}.card{padding:18px}}
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="card">
        <div class="brand">
            <div class="logo">T</div>
            <div>
                <h2>Teacher Sign in</h2>
                <p class="lead">Enter your ID Number to access your dashboard</p>
            </div>
        </div>
        <form method="POST" action="login.php">
            <input type="hidden" name="role" value="teacher">
            <div class="form-group">
                <label for="username_teacher">ID Number</label>
                <input type="text" id="username_teacher" name="username" placeholder="e.g., T001" required autofocus>
            </div>
            <button class="login-btn" type="submit">Continue</button>
        </form>
        <a class="back" href="login.php">← Back to role selection</a>
    </div>
</div>
</body>
</html>
