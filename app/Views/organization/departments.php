<?php /** @var array $departments */ /** @var array $allDepts */ /** @var array $employees */ ?>

<div class="page-header fade-in">
    <div>
        <h1><i class="bi bi-building-gear"></i> Organization Setup</h1>
        <p>Manage departments, categories, and employee directory</p>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-4 fade-in">
    <li class="nav-item">
        <a class="nav-link active" href="/organization/departments"><i class="bi bi-building me-1"></i> Departments</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="/organization/categories"><i class="bi bi-tags me-1"></i> Asset Categories</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="/organization/employees"><i class="bi bi-people me-1"></i> Employee Directory</a>
    </li>
</ul>

<!-- Department List + Create -->
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>All Departments</span>
                <span class="badge bg-primary-subtle text-primary"><?= count($departments) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Head</th>
                                <th>Parent</th>
                                <th>Employees</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($departments)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No departments found</td></tr>
                            <?php else: foreach ($departments as $dept): ?>
                            <tr>
                                <td style="font-weight:600;"><?= Helpers::e($dept['name']) ?></td>
                                <td><?= $dept['head_name'] ? Helpers::e($dept['head_name']) : '<span class="text-muted">—</span>' ?></td>
                                <td><?= $dept['parent_name'] ? Helpers::e($dept['parent_name']) : '<span class="text-muted">—</span>' ?></td>
                                <td><span class="badge bg-primary-subtle text-primary"><?= $dept['employee_count'] ?></span></td>
                                <td><?= Helpers::statusBadge($dept['status']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-ghost" data-bs-toggle="modal" data-bs-target="#editDeptModal"
                                        onclick="fillEditDept(<?= htmlspecialchars(json_encode($dept)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="/organization/departments/delete" class="d-inline" onsubmit="return confirm('Delete this department?')">
                                        <?= Helpers::csrfField() ?>
                                        <input type="hidden" name="id" value="<?= $dept['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-ghost text-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Form -->
    <div class="col-lg-4">
        <div class="card fade-in">
            <div class="card-header"><i class="bi bi-plus-circle me-2" style="color:var(--primary)"></i>Add Department</div>
            <div class="card-body">
                <form method="POST" action="/organization/departments">
                    <?= Helpers::csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">Department Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Human Resources" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department Head</label>
                        <select name="head_id" class="form-select">
                            <option value="">— Select Head —</option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= Helpers::e($emp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent Department</label>
                        <select name="parent_id" class="form-select">
                            <option value="">— None (Top-level) —</option>
                            <?php foreach ($allDepts as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= Helpers::e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i> Create Department</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editDeptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/organization/departments/update">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="id" id="editDeptId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Department</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="editDeptName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Head</label>
                        <select name="head_id" id="editDeptHead" class="form-select">
                            <option value="">— None —</option>
                            <?php foreach ($employees as $emp): ?>
                            <option value="<?= $emp['id'] ?>"><?= Helpers::e($emp['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Parent</label>
                        <select name="parent_id" id="editDeptParent" class="form-select">
                            <option value="">— None —</option>
                            <?php foreach ($allDepts as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= Helpers::e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="editDeptStatus" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function fillEditDept(dept) {
    document.getElementById('editDeptId').value = dept.id;
    document.getElementById('editDeptName').value = dept.name;
    document.getElementById('editDeptHead').value = dept.head_id || '';
    document.getElementById('editDeptParent').value = dept.parent_id || '';
    document.getElementById('editDeptStatus').value = dept.status;
}
</script>
