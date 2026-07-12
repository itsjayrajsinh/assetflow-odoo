<?php /** @var ?array $asset */ /** @var array $availableAssets */ /** @var array $employees */ /** @var array $departments */ ?>

<div class="page-header fade-in">
    <div><h1><i class="bi bi-person-plus"></i> Allocate Asset</h1><p>Assign an asset to an employee or department</p></div>
    <a href="/allocation" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<div class="row justify-content-center"><div class="col-lg-7">
<div class="card fade-in"><div class="card-body p-4">
    <form method="POST" action="/allocation/allocate">
        <?= Helpers::csrfField() ?>
        <div class="mb-3">
            <label class="form-label">Select Asset *</label>
            <select name="asset_id" class="form-select" required>
                <option value="">— Choose an available asset —</option>
                <?php foreach ($availableAssets as $a): ?>
                <option value="<?= $a['id'] ?>" <?= ($asset && $asset['id'] == $a['id']) ? 'selected' : '' ?>>
                    <?= Helpers::e($a['asset_tag']) ?> — <?= Helpers::e($a['name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
            <div class="form-text">Only available assets are shown. If an asset is already allocated, request a transfer instead.</div>
        </div>
        <div class="mb-3">
            <label class="form-label">Allocate To (Employee) *</label>
            <select name="allocated_to" class="form-select" required>
                <option value="">— Select Employee —</option>
                <?php foreach ($employees as $e): ?>
                <option value="<?= $e['id'] ?>"><?= Helpers::e($e['name']) ?> (<?= Helpers::e($e['dept_name'] ?? 'No Dept') ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Department</label>
            <select name="department_id" class="form-select">
                <option value="">— Auto from employee —</option>
                <?php foreach ($departments as $d): ?>
                <option value="<?= $d['id'] ?>"><?= Helpers::e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Expected Return Date</label>
            <input type="date" name="expected_return_date" class="form-control">
            <div class="form-text">Leave blank if no return date needed</div>
        </div>
        <hr class="my-4">
        <div class="d-flex justify-content-end gap-2">
            <a href="/allocation" class="btn btn-ghost">Cancel</a>
            <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle me-1"></i> Allocate Asset</button>
        </div>
    </form>
</div></div>
</div></div>
