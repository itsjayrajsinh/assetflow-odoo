<?php
/** @var array $flash */
$flash = $_SESSION['flash'] ?? null;
if (isset($_SESSION['flash'])) unset($_SESSION['flash']);
?>

<h2>Welcome back! 👋</h2>
<p class="auth-subtitle">Sign in to your AssetFlow account</p>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> mb-3" style="font-size:13px;">
    <i class="bi bi-<?= $flash['type'] === 'error' ? 'exclamation-circle' : 'check-circle' ?>-fill"></i>
    <?= Helpers::e($flash['message']) ?>
</div>
<?php endif; ?>

<form method="POST" action="/login" id="loginForm">
    <?= Helpers::csrfField() ?>
    
    <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" placeholder="you@company.com" required autofocus>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            <button type="button" class="input-group-text toggle-password" style="cursor:pointer;">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="remember" style="border-color:var(--border);">
            <label class="form-check-label" for="remember" style="font-size:13px;color:var(--text-secondary);">Remember me</label>
        </div>
        <a href="/forgot-password" style="font-size:13px;">Forgot password?</a>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-box-arrow-in-right"></i> Sign In
    </button>
</form>

<div class="auth-footer">
    Don't have an account? <a href="/signup">Create one</a>
</div>

<div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--border);">
    <p style="font-size:12px;color:var(--text-muted);text-align:center;margin-bottom:12px;">Demo Accounts</p>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;">
        <button type="button" class="btn btn-sm btn-soft" onclick="fillDemo('admin@assetflow.com')" style="font-size:11px;">
            <i class="bi bi-shield-check"></i> Admin
        </button>
        <button type="button" class="btn btn-sm btn-soft" onclick="fillDemo('rajesh@assetflow.com')" style="font-size:11px;">
            <i class="bi bi-person-gear"></i> Asset Mgr
        </button>
        <button type="button" class="btn btn-sm btn-soft" onclick="fillDemo('priya@assetflow.com')" style="font-size:11px;">
            <i class="bi bi-people"></i> Dept Head
        </button>
        <button type="button" class="btn btn-sm btn-soft" onclick="fillDemo('amit@assetflow.com')" style="font-size:11px;">
            <i class="bi bi-person"></i> Employee
        </button>
    </div>
</div>

<script>
function fillDemo(email) {
    document.getElementById('email').value = email;
    document.getElementById('password').value = 'password123';
}
</script>
