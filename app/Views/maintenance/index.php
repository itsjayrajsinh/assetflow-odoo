<?php /** @var array $requests */ /** @var array $employees */ ?>

<div class="page-header fade-in">
    <div><h1><i class="bi bi-tools"></i> Maintenance Management</h1><p>Route repairs through approval before work starts</p></div>
    <a href="/maintenance/create" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Raise Request</a>
</div>

<div class="card fade-in">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Asset</th><th>Requested By</th><th>Description</th><th>Priority</th><th>Status</th><th>Technician</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No maintenance requests</td></tr>
                    <?php else: foreach ($requests as $r): ?>
                    <tr>
                        <td><a href="/assets/detail/<?= $r['asset_id'] ?>" style="font-weight:600;"><?= Helpers::e($r['asset_tag']) ?></a><div style="font-size:12px;color:var(--text-muted)"><?= Helpers::e($r['asset_name']) ?></div></td>
                        <td><?= Helpers::e($r['requester_name']) ?></td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= Helpers::e($r['description']) ?></td>
                        <td><?= Helpers::statusBadge($r['priority']) ?></td>
                        <td><?= Helpers::statusBadge($r['status']) ?></td>
                        <td><?= $r['tech_name'] ? Helpers::e($r['tech_name']) : '<span class="text-muted">—</span>' ?></td>
                        <td>
                            <div class="d-flex gap-1 flex-wrap">
                            <?php if ($r['status'] === 'Pending' && Auth::hasRole('Admin','Asset Manager')): ?>
                                <form method="POST" action="/maintenance/approve" class="d-inline"><?= Helpers::csrfField() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-success" title="Approve"><i class="bi bi-check-lg"></i></button></form>
                                <form method="POST" action="/maintenance/reject" class="d-inline"><?= Helpers::csrfField() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-danger" title="Reject"><i class="bi bi-x-lg"></i></button></form>
                            <?php endif; ?>
                            <?php if ($r['status'] === 'Approved' && Auth::hasRole('Admin','Asset Manager')): ?>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal" onclick="document.getElementById('assignReqId').value=<?= $r['id'] ?>"><i class="bi bi-person-plus"></i> Assign</button>
                            <?php endif; ?>
                            <?php if ($r['status'] === 'Assigned'): ?>
                                <form method="POST" action="/maintenance/progress" class="d-inline"><?= Helpers::csrfField() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn btn-sm btn-info"><i class="bi bi-play-fill"></i> Start</button></form>
                            <?php endif; ?>
                            <?php if (in_array($r['status'], ['In Progress','Assigned']) && Auth::hasRole('Admin','Asset Manager')): ?>
                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#resolveModal" onclick="document.getElementById('resolveReqId').value=<?= $r['id'] ?>"><i class="bi bi-check-circle"></i> Resolve</button>
                            <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Assign Technician Modal -->
<div class="modal fade" id="assignModal" tabindex="-1"><div class="modal-dialog modal-sm"><div class="modal-content">
    <form method="POST" action="/maintenance/assign"><?= Helpers::csrfField() ?><input type="hidden" name="id" id="assignReqId">
    <div class="modal-header"><h5 class="modal-title">Assign Technician</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <select name="technician_id" class="form-select" required>
            <option value="">— Select —</option>
            <?php foreach ($employees as $e): ?><option value="<?= $e['id'] ?>"><?= Helpers::e($e['name']) ?></option><?php endforeach; ?>
        </select>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-primary">Assign</button></div>
    </form>
</div></div></div>

<!-- Resolve Modal -->
<div class="modal fade" id="resolveModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="POST" action="/maintenance/resolve"><?= Helpers::csrfField() ?><input type="hidden" name="id" id="resolveReqId">
    <div class="modal-header"><h5 class="modal-title">Resolve Maintenance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <label class="form-label">Resolution Notes</label>
        <textarea name="resolution_notes" class="form-control" rows="3" placeholder="What was done to resolve the issue..."></textarea>
    </div>
    <div class="modal-footer"><button type="submit" class="btn btn-success">Mark Resolved</button></div>
    </form>
</div></div></div>
