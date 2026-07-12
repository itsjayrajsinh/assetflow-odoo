<?php /** @var array $allocations */ ?>

<div class="page-header fade-in">
    <div>
        <h1><i class="bi bi-arrow-left-right"></i> Allocation & Transfer</h1>
        <p>Manage asset allocations, returns, and transfers</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/allocation/allocate" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Allocate Asset</a>
        <a href="/allocation/transfers" class="btn btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i> Transfers</a>
    </div>
</div>

<div class="card fade-in">
    <div class="card-header d-flex justify-content-between">
        <span>All Allocations</span>
        <span class="badge bg-primary-subtle text-primary"><?= count($allocations) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Asset</th><th>Allocated To</th><th>Department</th><th>Date</th><th>Expected Return</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($allocations)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No allocations yet</td></tr>
                    <?php else: foreach ($allocations as $a): ?>
                    <tr>
                        <td><a href="/assets/detail/<?= $a['asset_id'] ?>" style="font-weight:600;"><?= Helpers::e($a['asset_tag']) ?></a><div style="font-size:12px;color:var(--text-muted)"><?= Helpers::e($a['asset_name']) ?></div></td>
                        <td style="font-weight:500;"><?= Helpers::e($a['holder_name']) ?></td>
                        <td><?= Helpers::e($a['dept_name'] ?? '—') ?></td>
                        <td><?= Helpers::formatDate($a['allocation_date']) ?></td>
                        <td><?= Helpers::formatDate($a['expected_return_date']) ?></td>
                        <td><?= Helpers::statusBadge($a['status']) ?></td>
                        <td>
                            <?php if (in_array($a['status'], ['Active', 'Overdue'])): ?>
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#returnModal" onclick="fillReturn(<?= $a['id'] ?>, '<?= Helpers::e($a['asset_tag']) ?>')">
                                <i class="bi bi-arrow-return-left"></i> Return
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Return Modal -->
<div class="modal fade" id="returnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/allocation/return">
                <?= Helpers::csrfField() ?>
                <input type="hidden" name="allocation_id" id="returnAllocId">
                <div class="modal-header"><h5 class="modal-title">Return Asset <span id="returnAssetTag"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">Return Condition</label>
                        <select name="return_condition" class="form-select"><option value="New">New</option><option value="Good" selected>Good</option><option value="Fair">Fair</option><option value="Poor">Poor</option></select>
                    </div>
                    <div class="mb-3"><label class="form-label">Check-in Notes</label><textarea name="return_notes" class="form-control" rows="3" placeholder="Condition notes..."></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Confirm Return</button></div>
            </form>
        </div>
    </div>
</div>

<script>
function fillReturn(id, tag) {
    document.getElementById('returnAllocId').value = id;
    document.getElementById('returnAssetTag').textContent = tag;
}
</script>
