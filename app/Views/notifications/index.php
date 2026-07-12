<?php 
$showLogs = $showLogs ?? false;
$notifications = $notifications ?? [];
$logs = $logs ?? [];
$pagination = $pagination ?? null;

$typeIcons = [
    'asset_assigned' => ['bi-box-seam-fill', 'bg-primary-subtle', 'color:var(--primary)'],
    'maintenance_pending' => ['bi-tools', 'bg-warning-subtle', 'color:#cc9a00'],
    'maintenance_approved' => ['bi-check-circle-fill', 'bg-success-subtle', 'color:var(--success)'],
    'maintenance_rejected' => ['bi-x-circle-fill', 'bg-danger-subtle', 'color:var(--danger)'],
    'maintenance_resolved' => ['bi-wrench', 'bg-success-subtle', 'color:var(--success)'],
    'maintenance_assigned' => ['bi-person-gear', 'bg-info-subtle', 'color:var(--info)'],
    'booking_confirmed' => ['bi-calendar-check-fill', 'bg-info-subtle', 'color:var(--info)'],
    'transfer_approved' => ['bi-arrow-left-right', 'bg-success-subtle', 'color:var(--success)'],
    'role_changed' => ['bi-shield-check', 'bg-primary-subtle', 'color:var(--primary)'],
    'audit_assigned' => ['bi-clipboard-check', 'bg-info-subtle', 'color:var(--info)'],
];
?>

<div class="page-header fade-in">
    <div>
        <h1><i class="bi bi-<?= $showLogs ? 'journal-text' : 'bell' ?>"></i> <?= $showLogs ? 'Activity Logs' : 'Notifications' ?></h1>
        <p><?= $showLogs ? 'Full audit log of all system actions' : 'Stay informed about your assets, bookings, and requests' ?></p>
    </div>
    <div class="d-flex gap-2">
        <?php if (!$showLogs): ?>
        <form method="POST" action="/notifications/read-all"><?= Helpers::csrfField() ?><button class="btn btn-outline-primary btn-sm"><i class="bi bi-check2-all me-1"></i> Mark All Read</button></form>
        <?php endif; ?>
        <a href="<?= $showLogs ? '/notifications' : '/notifications/logs' ?>" class="btn btn-ghost btn-sm">
            <i class="bi bi-<?= $showLogs ? 'bell' : 'journal-text' ?> me-1"></i> <?= $showLogs ? 'Notifications' : 'Activity Logs' ?>
        </a>
    </div>
</div>

<?php if ($showLogs): ?>
<!-- Activity Logs -->
<div class="card fade-in">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>User</th><th>Action</th><th>Entity</th><th>Details</th><th>IP</th><th>Time</th></tr></thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="font-weight:500;"><?= Helpers::e($log['user_name'] ?? 'System') ?></td>
                        <td><span class="badge bg-primary-subtle text-primary"><?= Helpers::e($log['action']) ?></span></td>
                        <td><?= Helpers::e($log['entity_type']) ?> #<?= $log['entity_id'] ?? '—' ?></td>
                        <td style="font-size:12px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                            <?= Helpers::e(json_encode(json_decode($log['details'] ?? '{}'), JSON_UNESCAPED_UNICODE)) ?>
                        </td>
                        <td style="font-size:11px;color:var(--text-muted);"><?= Helpers::e($log['ip_address'] ?? '') ?></td>
                        <td style="font-size:12px;"><?= Helpers::timeAgo($log['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($pagination && $pagination['total_pages'] > 1): ?>
    <div class="card-body pt-2"><?= Helpers::paginationHtml($pagination, '/notifications/logs') ?></div>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- Notifications -->
<div class="card fade-in">
    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
        <div class="empty-state">
            <i class="bi bi-bell-slash"></i>
            <h3>All caught up!</h3>
            <p>No notifications to show. We'll alert you when something needs your attention.</p>
        </div>
        <?php else: ?>
        <?php foreach ($notifications as $n):
            $icon = $typeIcons[$n['type']] ?? ['bi-bell', 'bg-secondary-subtle', 'color:var(--text-muted)'];
        ?>
        <a href="<?= Helpers::e($n['link'] ?? '/notifications') ?>" class="notification-item <?= $n['is_read'] ? '' : 'unread' ?>" style="text-decoration:none;">
            <div class="notification-icon <?= $icon[1] ?>" style="<?= $icon[2] ?>">
                <i class="bi <?= $icon[0] ?>"></i>
            </div>
            <div class="notification-body">
                <div class="notification-title"><?= Helpers::e($n['title']) ?></div>
                <div class="notification-text"><?= Helpers::e($n['message']) ?></div>
                <div class="notification-time"><?= Helpers::timeAgo($n['created_at']) ?></div>
            </div>
        </a>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>
