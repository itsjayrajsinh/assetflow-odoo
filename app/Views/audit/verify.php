<?php /** @var array $cycle */ /** @var array $items */ /** @var ?array $summary */ 
$summary = $summary ?? [
    'total' => count($items),
    'verified' => count(array_filter($items, fn($i) => $i['status'] === 'Verified')),
    'missing' => count(array_filter($items, fn($i) => $i['status'] === 'Missing')),
    'damaged' => count(array_filter($items, fn($i) => $i['status'] === 'Damaged')),
    'pending' => count(array_filter($items, fn($i) => $i['status'] === 'Pending')),
];
?>

<div class="page-header fade-in">
    <div><h1><i class="bi bi-check2-square"></i> <?= Helpers::e($cycle['name']) ?></h1>
    <p><?= Helpers::e($cycle['scope_type']) ?>: <?= Helpers::e($cycle['scope_value']) ?> | <?= Helpers::formatDate($cycle['start_date']) ?> → <?= Helpers::formatDate($cycle['end_date']) ?> <?= Helpers::statusBadge($cycle['status']) ?></p></div>
    <a href="/audit" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="kpi-card kpi-primary"><div class="kpi-icon"><i class="bi bi-box-seam"></i></div><div class="kpi-content"><div class="kpi-value"><?= $summary['total'] ?></div><div class="kpi-label">Total Assets</div></div></div></div>
    <div class="col-md-3"><div class="kpi-card kpi-success"><div class="kpi-icon"><i class="bi bi-check-circle"></i></div><div class="kpi-content"><div class="kpi-value"><?= $summary['verified'] ?></div><div class="kpi-label">Verified</div></div></div></div>
    <div class="col-md-3"><div class="kpi-card kpi-danger"><div class="kpi-icon"><i class="bi bi-x-circle"></i></div><div class="kpi-content"><div class="kpi-value"><?= $summary['missing'] ?></div><div class="kpi-label">Missing</div></div></div></div>
    <div class="col-md-3"><div class="kpi-card kpi-warning"><div class="kpi-icon"><i class="bi bi-exclamation-triangle"></i></div><div class="kpi-content"><div class="kpi-value"><?= $summary['damaged'] ?></div><div class="kpi-label">Damaged</div></div></div></div>
</div>

<!-- Items Table -->
<div class="card fade-in">
    <div class="card-header">Assets to Verify</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Asset Tag</th><th>Name</th><th>Location</th><th>Current Status</th><th>Condition</th><th>Audit Status</th><th>Auditor</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td style="font-weight:600;color:var(--primary);"><?= Helpers::e($item['asset_tag']) ?></td>
                        <td><?= Helpers::e($item['asset_name']) ?></td>
                        <td style="font-size:12px;"><?= Helpers::e($item['location'] ?? '—') ?></td>
                        <td><?= Helpers::statusBadge($item['asset_status']) ?></td>
                        <td><?= Helpers::statusBadge($item['condition']) ?></td>
                        <td><?= Helpers::statusBadge($item['status']) ?></td>
                        <td><?= $item['auditor_name'] ? Helpers::e($item['auditor_name']) : '<span class="text-muted">—</span>' ?></td>
                        <td>
                            <?php if ($item['status'] === 'Pending' && $cycle['status'] !== 'Closed'): ?>
                            <div class="d-flex gap-1">
                                <form method="POST" action="/audit/verify" class="d-inline"><?= Helpers::csrfField() ?><input type="hidden" name="item_id" value="<?= $item['id'] ?>"><input type="hidden" name="status" value="Verified"><button class="btn btn-sm btn-success" title="Verified"><i class="bi bi-check-lg"></i></button></form>
                                <form method="POST" action="/audit/verify" class="d-inline"><?= Helpers::csrfField() ?><input type="hidden" name="item_id" value="<?= $item['id'] ?>"><input type="hidden" name="status" value="Missing"><button class="btn btn-sm btn-danger" title="Missing"><i class="bi bi-x-lg"></i></button></form>
                                <form method="POST" action="/audit/verify" class="d-inline"><?= Helpers::csrfField() ?><input type="hidden" name="item_id" value="<?= $item['id'] ?>"><input type="hidden" name="status" value="Damaged"><button class="btn btn-sm btn-warning" title="Damaged"><i class="bi bi-exclamation-lg"></i></button></form>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
