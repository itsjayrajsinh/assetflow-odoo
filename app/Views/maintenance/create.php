<?php /** @var array $assets */ /** @var ?string $preselectedAsset */ ?>

<div class="page-header fade-in">
    <div><h1><i class="bi bi-tools"></i> Raise Maintenance Request</h1><p>Report an issue for asset repair</p></div>
    <a href="/maintenance" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<div class="row justify-content-center"><div class="col-lg-6">
<div class="card fade-in"><div class="card-body p-4">
    <form method="POST" action="/maintenance/create" enctype="multipart/form-data">
        <?= Helpers::csrfField() ?>
        <div class="mb-3">
            <label class="form-label">Asset *</label>
            <select name="asset_id" class="form-select" required>
                <option value="">— Select Asset —</option>
                <?php foreach ($assets as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $preselectedAsset == $a['id'] ? 'selected' : '' ?>><?= Helpers::e($a['asset_tag']) ?> — <?= Helpers::e($a['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Issue Description *</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Describe the issue in detail..." required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Priority</label>
            <select name="priority" class="form-select">
                <option value="Low">Low</option>
                <option value="Medium" selected>Medium</option>
                <option value="High">High</option>
                <option value="Critical">Critical</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Photo (optional)</label>
            <input type="file" name="photo" class="form-control" accept="image/*">
        </div>
        <hr class="my-4">
        <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="bi bi-send me-1"></i> Submit Request</button>
    </form>
</div></div>
</div></div>
