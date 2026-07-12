<?php
$flash = $_SESSION['flash'] ?? null;
if (isset($_SESSION['flash'])) unset($_SESSION['flash']);
$resetToken = $resetToken ?? null;
?>

<?php if ($resetToken): ?>
<!-- Reset Password Form -->
<h2>Set New Password 🔑</h2>
<p class="auth-subtitle">Enter your new password below</p>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> mb-3" style="font-size:13px;">
    <?= Helpers::e($flash['message']) ?>
</div>
<?php endif; ?>

<form method="POST" action="/reset-password">
    <?= Helpers::csrfField() ?>
    <input type="hidden" name="token" value="<?= Helpers::e($resetToken) ?>">

    <div class="form-group">
        <label class="form-label" for="password">New Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" class="form-control" id="password" name="password" placeholder="Min 6 characters" required minlength="6">
            <button type="button" class="input-group-text toggle-password" style="cursor:pointer;">
                <i class="bi bi-eye"></i>
            </button>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="confirm_password">Confirm Password</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat password" required>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-check-circle"></i> Reset Password
    </button>
</form>

<?php else: ?>
<!-- Forgot Password Form -->
<h2>Forgot Password? 🤔</h2>
<p class="auth-subtitle">Enter your email and we'll help you reset it</p>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> mb-3" style="font-size:13px;">
    <?= Helpers::e($flash['message']) ?>
</div>
<?php endif; ?>

<form method="POST" action="/forgot-password">
    <?= Helpers::csrfField() ?>
    
    <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" placeholder="you@company.com" required autofocus>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-envelope-arrow-up"></i> Send Reset Link
    </button>
</form>
<?php endif; ?>

<div class="auth-footer">
    Remember your password? <a href="/login">Sign in</a>
</div>
