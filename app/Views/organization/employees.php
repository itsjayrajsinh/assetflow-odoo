<?php /** @var array $employees */ /** @var array $departments */ ?>

<div class="page-header fade-in">
    <div>
        <h1><i class="bi bi-building-gear"></i> Organization Setup</h1>
        <p>Manage departments, categories, and employee directory</p>
    </div>
</div>

<ul class="nav nav-tabs mb-4 fade-in">
    <li class="nav-item"><a class="nav-link" href="/organization/departments"><i class="bi bi-building me-1"></i> Departments</a></li>
    <li class="nav-item"><a class="nav-link" href="/organization/categories"><i class="bi bi-tags me-1"></i> Asset Categories</a></li>
    <li class="nav-item"><a class="nav-link active" href="/organization/employees"><i class="bi bi-people me-1"></i> Employee Directory</a></li>
</ul>

<div class="card fade-in">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>All Employees</span>
        <span class="badge bg-primary-subtle text-primary"><?= count($employees) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Role</th>
                        <th>Active Assets</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar" style="width:32px;height:32px;font-size:11px;background:<?= Helpers::stringToColor($emp['name']) ?>">
                                    <?= Helpers::initials($emp['name']) ?>
                                </div>
                                <strong><?= Helpers::e($emp['name']) ?></strong>
                            </div>
                        </td>
                        <td style="font-size:13px;"><?= Helpers::e($emp['email']) ?></td>
                        <td><?= Helpers::e($emp['department_name'] ?? '—') ?></td>
                        <td>
                            <?php
                            $roleColors = ['Admin'=>'bg-danger-subtle text-danger','Asset Manager'=>'bg-primary-subtle text-primary','Department Head'=>'bg-warning-subtle text-warning','Employee'=>'bg-secondary-subtle text-secondary'];
                            $rc = $roleColors[$emp['role']] ?? 'bg-secondary-subtle';
                            ?>
                            <span class="badge rounded-pill <?= $rc ?>"><?= Helpers::e($emp['role']) ?></span>
                        </td>
                        <td><span class="badge bg-info-subtle text-info"><?= $emp['active_assets'] ?></span></td>
                        <td><?= Helpers::statusBadge($emp['status']) ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-ghost" data-bs-toggle="modal" data-bs-target="#editEmpModal"
                                    onclick="fillEditEmp(<?= htmlspecialchars(json_encode($emp)) ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-soft" data-bs-toggle="modal" data-bs-target="#roleModal"
                                    onclick="fillRole(<?= $emp['id'] ?>, '<?= Helpers::e($emp['name']) ?>', '<?= $emp['role'] ?>')">
                                    <i class="bi bi-shield-check"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmpModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/organization/employees/update">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="id" id="editEmpId">
                <div class="modal-header"><h5 class="modal-title">Edit Employee</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" id="editEmpName" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" id="editEmpEmail" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" id="editEmpDept" class="form-select">
                            <option value="">— None —</option>
                            <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= Helpers::e($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="editEmpStatus" class="form-select"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
</div>

<!-- Role Change Modal -->
<div class="modal fade" id="roleModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="/organization/employees/role">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="id" id="roleEmpId">
                <div class="modal-header"><h5 class="modal-title">Change Role</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p style="font-size:13px;">Promote <strong id="roleEmpName"></strong> to:</p>
                    <select name="role" id="roleSelect" class="form-select">
                        <option value="Employee">Employee</option>
                        <option value="Department Head">Department Head</option>
                        <option value="Asset Manager">Asset Manager</option>
                        <option value="Admin">Admin</option>
                    </select>
                    <div class="form-text mt-2"><i class="bi bi-info-circle"></i> This is the only place roles are assigned.</div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update Role</button></div>
            </form>
        </div>
    </div>
</div>

<script>
function fillEditEmp(emp) {
    document.getElementById('editEmpId').value = emp.id;
    document.getElementById('editEmpName').value = emp.name;
    document.getElementById('editEmpEmail').value = emp.email;
    document.getElementById('editEmpDept').value = emp.department_id || '';
    document.getElementById('editEmpStatus').value = emp.status;
}
function fillRole(id, name, currentRole) {
    document.getElementById('roleEmpId').value = id;
    document.getElementById('roleEmpName').textContent = name;
    document.getElementById('roleSelect').value = currentRole;
}
</script>
