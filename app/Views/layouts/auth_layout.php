<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="AssetFlow — Enterprise Asset & Resource Management System">
    <title><?= htmlspecialchars($title ?? 'AssetFlow') ?></title>
    
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- AssetFlow Theme -->
    <link href="/css/theme.css" rel="stylesheet">
    <link href="/css/custom.css" rel="stylesheet">
</head>
<body>

<div class="auth-container">
    <!-- Left Panel - Branding -->
    <div class="auth-left">
        <div style="margin-bottom:24px;">
            <div style="width:64px;height:64px;background:rgba(255,255,255,0.2);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:800;color:white;margin:0 auto 16px;backdrop-filter:blur(10px);">AF</div>
        </div>
        <h1>AssetFlow</h1>
        <p>Enterprise Asset & Resource Management System — Track, allocate, and maintain your organization's assets with ease.</p>
        <div style="margin-top:48px;display:flex;gap:32px;position:relative;z-index:1;">
            <div style="text-align:center;">
                <div style="font-size:28px;font-weight:800;color:white;">500+</div>
                <div style="font-size:12px;color:rgba(255,255,255,0.7);">Assets Tracked</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:28px;font-weight:800;color:white;">50+</div>
                <div style="font-size:12px;color:rgba(255,255,255,0.7);">Departments</div>
            </div>
            <div style="text-align:center;">
                <div style="font-size:28px;font-weight:800;color:white;">99%</div>
                <div style="font-size:12px;color:rgba(255,255,255,0.7);">Uptime</div>
            </div>
        </div>
    </div>

    <!-- Right Panel - Form -->
    <div class="auth-right">
        <div class="auth-card fade-in">
            <?php
            $viewFile = APP_PATH . '/Views/' . $viewPath . '.php';
            if (file_exists($viewFile)) {
                require $viewFile;
            }
            ?>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Password visibility toggle
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function() {
        const input = this.closest('.input-group').querySelector('input');
        const icon = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });
});
</script>
</body>
</html>
