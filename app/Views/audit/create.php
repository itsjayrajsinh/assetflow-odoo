<?php /** @var array $departments */ /** @var array $employees */ ?>

<div class="page-header fade-in">
    <div><h1><i class="bi bi-plus-circle"></i> Create Audit Cycle</h1><p>Define scope, date range, and assign auditors</p></div>
    <a href="/audit" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<div class="row justify-content-center"><div class="col-lg-7">
<div class="card fade-in"><div class="card-body p-4">
    <form method="POST" action="/audit/create">
        <?= Helpers::csrfField() ?>
        <div class="mb-3"><label class="form-label">Cycle Name *</label><input type="text" name="name" class="form-control" placeholder="e.g. Q3 2026 IT Department Audit" required></div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Scope Type *</label>
                <select name="scope_type" id="scopeType" class="form-select" required onchange="toggleScope()">
                    <option value="Department">Department</option>
                    <option value="Location">Location</option>
                </select>
            </div>
            <div class="col-md-6" id="scopeDeptDiv">
                <label class="form-label">Department *</label>
                <select name="scope_value" id="scopeDept" class="form-select">
                    <?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>"><?= Helpers::e($d['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6" id="scopeLocDiv" style="display:none;">
                <label class="form-label">Location *</label>
                <input type="text" name="scope_value_loc" id="scopeLoc" class="form-control" placeholder="e.g. Floor 2">
            </div>
        </div>
        <div class="row g-3 mt-1">
            <div class="col-md-6"><label class="form-label">Start Date *</label><input type="date" name="start_date" class="form-control" required></div>
            <div class="col-md-6"><label class="form-label">End Date *</label><input type="date" name="end_date" class="form-control" required></div>
        </div>
        <div class="mb-3 mt-3">
            <label class="form-label">Assign Auditors</label>
            <select name="auditors[]" class="form-select" multiple size="5">
                <?php foreach ($employees as $e): ?><option value="<?= $e['id'] ?>"><?= Helpers::e($e['name']) ?></option><?php endforeach; ?>
            </select>
            <div class="form-text">Hold Ctrl/Cmd to select multiple auditors</div>
        </div>
        <hr class="my-4">
        <button type="submit" class="btn btn-primary w-100 btn-lg"><i class="bi bi-clipboard-check me-1"></i> Create Audit Cycle</button>
    </form>
</div></div>
</div></div>

<script>
function toggleScope() {
    const type = document.getElementById('scopeType').value;
    document.getElementById('scopeDeptDiv').style.display = type === 'Department' ? 'block' : 'none';
    document.getElementById('scopeLocDiv').style.display = type === 'Location' ? 'block' : 'none';
    if (type === 'Location') {
        document.getElementById('scopeDept').removeAttribute('name');
        document.getElementById('scopeLoc').setAttribute('name', 'scope_value');
    } else {
        document.getElementById('scopeDept').setAttribute('name', 'scope_value');
        document.getElementById('scopeLoc').removeAttribute('name');
    }
}
</script>
