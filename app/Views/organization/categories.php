<?php /** @var array $categories */ ?>

<div class="page-header fade-in">
    <div>
        <h1><i class="bi bi-building-gear"></i> Organization Setup</h1>
        <p>Manage departments, categories, and employee directory</p>
    </div>
</div>

<ul class="nav nav-tabs mb-4 fade-in">
    <li class="nav-item"><a class="nav-link" href="/organization/departments"><i class="bi bi-building me-1"></i> Departments</a></li>
    <li class="nav-item"><a class="nav-link active" href="/organization/categories"><i class="bi bi-tags me-1"></i> Asset Categories</a></li>
    <li class="nav-item"><a class="nav-link" href="/organization/employees"><i class="bi bi-people me-1"></i> Employee Directory</a></li>
</ul>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>All Categories</span>
                <span class="badge bg-primary-subtle text-primary"><?= count($categories) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>Category</th><th>Description</th><th>Assets</th><th>Custom Fields</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No categories found</td></tr>
                            <?php else: foreach ($categories as $cat): ?>
                            <tr>
                                <td style="font-weight:600;"><?= Helpers::e($cat['name']) ?></td>
                                <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?= Helpers::e($cat['description'] ?? '—') ?>
                                </td>
                                <td><span class="badge bg-primary-subtle text-primary"><?= $cat['asset_count'] ?></span></td>
                                <td>
                                    <?php 
                                    $fields = json_decode($cat['custom_fields'] ?? '{}', true);
                                    if ($fields) {
                                        foreach ($fields as $k => $v) {
                                            echo '<span class="badge bg-secondary-subtle text-secondary me-1" style="font-size:10px;">' . Helpers::e($k) . '</span>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">—</span>';
                                    }
                                    ?>
                                </td>
                                <td><?= Helpers::statusBadge($cat['status']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-ghost" data-bs-toggle="modal" data-bs-target="#editCatModal"
                                        onclick="fillEditCat(<?= htmlspecialchars(json_encode($cat)) ?>)">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="/organization/categories/delete" class="d-inline" onsubmit="return confirm('Delete this category?')">
                                        <?= Helpers::csrfField() ?>
                                        <input type="hidden" name="id" value="<?= $cat['id'] ?>">
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

    <div class="col-lg-4">
        <div class="card fade-in">
            <div class="card-header"><i class="bi bi-plus-circle me-2" style="color:var(--primary)"></i>Add Category</div>
            <div class="card-body">
                <form method="POST" action="/organization/categories">
                    <?= Helpers::csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Electronics" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief description..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Custom Fields (JSON)</label>
                        <textarea name="custom_fields" class="form-control" rows="3" placeholder='{"warranty_period": "months", "brand": "text"}'></textarea>
                        <div class="form-text">Optional. Define category-specific fields as JSON.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i> Create Category</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editCatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/organization/categories/update">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="id" id="editCatId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Name</label><input type="text" name="name" id="editCatName" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" id="editCatDesc" class="form-control" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label">Custom Fields</label><textarea name="custom_fields" id="editCatFields" class="form-control" rows="3"></textarea></div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="editCatStatus" class="form-select"><option value="Active">Active</option><option value="Inactive">Inactive</option></select>
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
function fillEditCat(cat) {
    document.getElementById('editCatId').value = cat.id;
    document.getElementById('editCatName').value = cat.name;
    document.getElementById('editCatDesc').value = cat.description || '';
    document.getElementById('editCatFields').value = cat.custom_fields || '';
    document.getElementById('editCatStatus').value = cat.status;
}
</script>
