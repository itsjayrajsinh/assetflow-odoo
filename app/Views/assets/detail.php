<?php /** @var array $asset */ /** @var array $allocHistory */ /** @var array $maintHistory */ ?>

<div class="page-header fade-in">
    <div>
        <h1><i class="bi bi-box-seam"></i> <?= Helpers::e($asset['asset_tag']) ?></h1>
        <p><?= Helpers::e($asset['name']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="/assets" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i> Back</a>
        <?php if (Auth::hasRole('Admin','Asset Manager')): ?>
        <a href="/allocation/allocate?asset_id=<?= $asset['id'] ?>" class="btn btn-outline-primary"><i class="bi bi-person-plus me-1"></i> Allocate</a>
        <a href="/maintenance/create?asset_id=<?= $asset['id'] ?>" class="btn btn-soft"><i class="bi bi-tools me-1"></i> Maintenance</a>
        <?php endif; ?>
    </div>
</div>

<!-- Asset Header -->
<div class="card mb-4 fade-in">
    <div class="card-body">
        <div class="asset-header">
            <div class="d-flex flex-column gap-3" style="width:200px; flex-shrink:0;">
                <div class="asset-image">
                    <?php if ($asset['photo']): ?>
                    <img src="<?= Helpers::e($asset['photo']) ?>" alt="<?= Helpers::e($asset['name']) ?>">
                    <?php else: ?>
                    <i class="bi bi-box-seam no-image"></i>
                    <?php endif; ?>
                </div>
                
                <!-- QR Code Tag Addon -->
                <div class="card p-3 text-center" style="border: 1px dashed var(--border); border-radius: var(--radius); width: 200px; background: var(--primary-subtle); margin:0;">
                    <div style="font-size: 11px; font-weight: 700; color: var(--primary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.05em;">Asset QR Tag</div>
                    <?php 
                    $qrUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost:8000') . "/assets/detail/" . $asset['id'];
                    $qrImg = "https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=" . urlencode($qrUrl);
                    ?>
                    <div class="mb-2" style="background: white; padding: 10px; border-radius: var(--radius-sm); display: inline-block;">
                        <img src="<?= $qrImg ?>" alt="QR Code Tag" style="width: 120px; height: 120px; display: block; margin: 0 auto;">
                    </div>
                    <div style="font-family: monospace; font-weight: 700; font-size: 13px; color: var(--text-primary); margin-bottom: 8px;">
                        <?= Helpers::e($asset['asset_tag']) ?>
                    </div>
                    <button class="btn btn-sm btn-primary w-100" onclick="window.print()" style="font-size: 11px; padding: 4px 10px;">
                        <i class="bi bi-printer me-1"></i> Print Tag
                    </button>
                </div>
            </div>
            <div style="flex:1;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <h2 class="mb-0"><?= Helpers::e($asset['name']) ?></h2>
                    <?= Helpers::statusBadge($asset['status']) ?>
                    <?php if ($asset['is_bookable']): ?>
                    <span class="badge bg-info-subtle text-info"><i class="bi bi-calendar-check me-1"></i>Bookable</span>
                    <?php endif; ?>
                </div>
                <div class="asset-info-grid">
                    <div class="info-item"><span class="info-label">Asset Tag</span><span class="info-value" style="color:var(--primary);font-weight:700;"><?= Helpers::e($asset['asset_tag']) ?></span></div>
                    <div class="info-item"><span class="info-label">Category</span><span class="info-value"><?= Helpers::e($asset['category_name'] ?? '—') ?></span></div>
                    <div class="info-item"><span class="info-label">Serial Number</span><span class="info-value"><?= Helpers::e($asset['serial_number'] ?? '—') ?></span></div>
                    <div class="info-item"><span class="info-label">Condition</span><span class="info-value"><?= Helpers::statusBadge($asset['condition']) ?></span></div>
                    <div class="info-item"><span class="info-label">Location</span><span class="info-value"><?= Helpers::e($asset['location'] ?? '—') ?></span></div>
                    <div class="info-item"><span class="info-label">Department</span><span class="info-value"><?= Helpers::e($asset['dept_name'] ?? '—') ?></span></div>
                    <div class="info-item"><span class="info-label">Assigned To</span><span class="info-value"><?= Helpers::e($asset['assigned_name'] ?? 'Unassigned') ?></span></div>
                    <div class="info-item"><span class="info-label">Acquisition Date</span><span class="info-value"><?= Helpers::formatDate($asset['acquisition_date']) ?></span></div>
                    <div class="info-item"><span class="info-label">Acquisition Cost</span><span class="info-value"><?= Helpers::formatCurrency((float)$asset['acquisition_cost']) ?></span></div>
                </div>
                <?php if ($asset['notes']): ?>
                <div class="mt-3" style="font-size:13px;color:var(--text-secondary);">
                    <strong>Notes:</strong> <?= Helpers::e($asset['notes']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (Auth::hasRole('Admin','Asset Manager')): ?>
<!-- Status Change -->
<div class="card mb-4 fade-in">
    <div class="card-header"><i class="bi bi-arrow-repeat me-2" style="color:var(--primary)"></i>Change Status</div>
    <div class="card-body">
        <form method="POST" action="/assets/status" class="d-flex gap-2 align-items-center flex-wrap">
            <?= Helpers::csrfField() ?>
            <input type="hidden" name="id" value="<?= $asset['id'] ?>">
            <select name="status" class="form-select" style="width:auto;">
                <?php foreach (['Available','Allocated','Reserved','Under Maintenance','Lost','Retired','Disposed'] as $s): ?>
                <option value="<?= $s ?>" <?= $asset['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Change asset status?')">Update Status</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- History Tabs -->
<div class="card fade-in">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#allocHistory">Allocation History</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#maintHistory">Maintenance History</a></li>
        </ul>
    </div>
    <div class="card-body p-0">
        <div class="tab-content">
            <div class="tab-pane fade show active" id="allocHistory">
                <?php if (empty($allocHistory)): ?>
                <div class="text-center py-4 text-muted">No allocation history</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Allocated To</th><th>By</th><th>Date</th><th>Expected Return</th><th>Returned</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($allocHistory as $h): ?>
                            <tr>
                                <td style="font-weight:500;"><?= Helpers::e($h['holder_name']) ?></td>
                                <td><?= Helpers::e($h['allocator_name']) ?></td>
                                <td><?= Helpers::formatDate($h['allocation_date']) ?></td>
                                <td><?= Helpers::formatDate($h['expected_return_date']) ?></td>
                                <td><?= Helpers::formatDate($h['actual_return_date']) ?></td>
                                <td><?= Helpers::statusBadge($h['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <div class="tab-pane fade" id="maintHistory">
                <?php if (empty($maintHistory)): ?>
                <div class="text-center py-4 text-muted">No maintenance history</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Requested By</th><th>Description</th><th>Priority</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($maintHistory as $m): ?>
                            <tr>
                                <td><?= Helpers::e($m['requester_name']) ?></td>
                                <td style="max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= Helpers::e($m['description']) ?></td>
                                <td><?= Helpers::statusBadge($m['priority']) ?></td>
                                <td><?= Helpers::statusBadge($m['status']) ?></td>
                                <td><?= Helpers::formatDate($m['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
