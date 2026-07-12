<?php
/** @var array $departments */
$flash = $_SESSION['flash'] ?? null;
if (isset($_SESSION['flash'])) unset($_SESSION['flash']);
?>

<h2>Create Account ✨</h2>
<p class="auth-subtitle">Join AssetFlow as an employee</p>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> mb-3" style="font-size:13px;">
    <i class="bi bi-<?= $flash['type'] === 'error' ? 'exclamation-circle' : 'check-circle' ?>-fill"></i>
    <?= Helpers::e($flash['message']) ?>
</div>
<?php endif; ?>

<form method="POST" action="/signup">
    <?= Helpers::csrfField() ?>

    <div class="form-group">
        <label class="form-label" for="name">Full Name</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" class="form-control" id="name" name="name" placeholder="Your full name" required>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="email">Email Address</label>
        <div class="input-group">
            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
            <input type="email" class="form-control" id="email" name="email" placeholder="you@company.com" required>
        </div>
    </div>

    <div class="form-group">
        <label class="form-label" for="department_id">Department</label>
        <select class="form-select" id="department_id" name="department_id">
            <option value="">— Select Department —</option>
            <?php foreach ($departments as $dept): ?>
            <option value="<?= $dept['id'] ?>"><?= Helpers::e($dept['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label class="form-label" for="password">Password</label>
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
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat your password" required minlength="6">
        </div>
    </div>

    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="terms" required style="border-color:var(--border);">
        <label class="form-check-label" for="terms" style="font-size:13px;color:var(--text-secondary);">
            I agree to the <a href="#">Terms of Service</a>
        </label>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bi bi-person-plus"></i> Create Employee Account
    </button>
</form>

<div class="auth-footer">
    Already have an account? <a href="/login">Sign in</a>
</div>

<div style="margin-top:16px;text-align:center;">
    <small style="color:var(--text-muted);font-size:11px;">
        <i class="bi bi-info-circle"></i> Accounts are created with Employee role only. Admin can promote you later.
    </small>
</div>
