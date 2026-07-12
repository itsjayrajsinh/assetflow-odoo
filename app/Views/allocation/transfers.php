<?php /** @var array $transfers */ ?>

<div class="page-header fade-in">
    <div><h1><i class="bi bi-arrow-repeat"></i> Transfer Requests</h1><p>Approve or reject asset transfer requests</p></div>
    <a href="/allocation" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i> Back to Allocations</a>
</div>

<div class="card fade-in">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Asset</th><th>From</th><th>To</th><th>Requested By</th><th>Reason</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($transfers)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No transfer requests</td></tr>
                    <?php else: foreach ($transfers as $t): ?>
                    <tr>
                        <td><a href="/assets/detail/<?= $t['asset_id'] ?>" style="font-weight:600;"><?= Helpers::e($t['asset_tag']) ?></a></td>
                        <td><?= Helpers::e($t['from_name']) ?></td>
                        <td><?= Helpers::e($t['to_name']) ?></td>
                        <td><?= Helpers::e($t['requester_name']) ?></td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= Helpers::e($t['reason'] ?? '—') ?></td>
                        <td><?= Helpers::statusBadge($t['status']) ?></td>
                        <td>
                            <?php if ($t['status'] === 'Requested'): ?>
                            <form method="POST" action="/allocation/transfer/approve" class="d-inline"><?= Helpers::csrfField() ?><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i></button></form>
                            <form method="POST" action="/allocation/transfer/reject" class="d-inline"><?= Helpers::csrfField() ?><input type="hidden" name="id" value="<?= $t['id'] ?>"><button class="btn btn-sm btn-danger"><i class="bi bi-x-lg"></i></button></form>
                            <?php else: ?>
                            <span class="text-muted" style="font-size:12px;"><?= Helpers::e($t['approver_name'] ?? '—') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
