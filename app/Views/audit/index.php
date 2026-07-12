<?php /** @var array $cycles */ ?>

<div class="page-header fade-in">
    <div><h1><i class="bi bi-clipboard-check"></i> Asset Audit</h1><p>Run structured verification cycles and catch discrepancies</p></div>
    <a href="/audit/create" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Create Audit Cycle</a>
</div>

<div class="card fade-in">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Cycle Name</th><th>Scope</th><th>Date Range</th><th>Progress</th><th>Discrepancies</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($cycles)): ?>
                    <tr><td colspan="7"><div class="empty-state"><i class="bi bi-clipboard-check"></i><h3>No audit cycles yet</h3><p>Create your first audit cycle to start verifying assets.</p></div></td></tr>
                    <?php else: foreach ($cycles as $c):
                        $progress = $c['total_items'] > 0 ? round(($c['verified_items'] / $c['total_items']) * 100) : 0;
                    ?>
                    <tr>
                        <td style="font-weight:600;"><?= Helpers::e($c['name']) ?><div style="font-size:11px;color:var(--text-muted)">by <?= Helpers::e($c['creator_name']) ?></div></td>
                        <td><span class="badge bg-info-subtle text-info"><?= Helpers::e($c['scope_type']) ?></span> <?= Helpers::e($c['scope_value']) ?></td>
                        <td style="font-size:12px;"><?= Helpers::formatDate($c['start_date']) ?> → <?= Helpers::formatDate($c['end_date']) ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress" style="width:80px;height:6px;"><div class="progress-bar" style="width:<?= $progress ?>%;background:var(--primary);"></div></div>
                                <span style="font-size:12px;"><?= $c['verified_items'] ?>/<?= $c['total_items'] ?></span>
                            </div>
                        </td>
                        <td>
                            <?php if ($c['discrepancies'] > 0): ?>
                            <span class="badge bg-danger-subtle text-danger"><?= $c['discrepancies'] ?> found</span>
                            <?php else: ?>
                            <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td><?= Helpers::statusBadge($c['status']) ?></td>
                        <td>
                            <?php if ($c['status'] !== 'Closed'): ?>
                            <a href="/audit/verify/<?= $c['id'] ?>" class="btn btn-sm btn-primary"><i class="bi bi-check2-square"></i> Verify</a>
                            <?php if (Auth::hasRole('Admin','Asset Manager')): ?>
                            <form method="POST" action="/audit/close" class="d-inline" onsubmit="return confirm('Close this audit cycle? Missing assets will be marked as Lost.')">
                                <?= Helpers::csrfField() ?><input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button class="btn btn-sm btn-ghost text-danger"><i class="bi bi-lock"></i> Close</button>
                            </form>
                            <?php endif; ?>
                            <?php else: ?>
                            <a href="/audit/verify/<?= $c['id'] ?>" class="btn btn-sm btn-ghost"><i class="bi bi-file-text"></i> Report</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
