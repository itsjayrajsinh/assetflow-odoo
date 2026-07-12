<?php
/** @var array $stats */
/** @var array $overdueAllocations */
/** @var array $upcomingReturns */
/** @var array $recentActivity */
/** @var array $todayBookings */
/** @var array $statusDistribution */
/** @var array $categoryDistribution */
$role = $currentUser['role'];
?>

<!-- Page Header -->
<div class="page-header fade-in">
    <div>
        <h1><i class="bi bi-speedometer2"></i> Dashboard</h1>
        <p>Welcome back, <?= Helpers::e($currentUser['name']) ?>! Here's your operational snapshot.</p>
    </div>
    <div class="d-flex gap-2">
        <?php if (in_array($role, ['Admin', 'Asset Manager'])): ?>
        <a href="/assets/register" class="btn btn-primary btn-sm"><i class="bi bi-plus-circle"></i> Register Asset</a>
        <?php endif; ?>
        <a href="/booking/new" class="btn btn-outline-primary btn-sm"><i class="bi bi-calendar-plus"></i> Book Resource</a>
        <a href="/maintenance/create" class="btn btn-soft btn-sm"><i class="bi bi-tools"></i> Maintenance</a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-xl-2 col-lg-4 col-md-4 col-6">
        <div class="kpi-card kpi-primary slide-up">
            <div class="kpi-icon"><i class="bi bi-box-seam-fill"></i></div>
            <div class="kpi-content">
                <div class="kpi-value" data-count="<?= $stats['total_assets'] ?>">0</div>
                <div class="kpi-label">Total Assets</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-4 col-6">
        <div class="kpi-card kpi-success slide-up" style="animation-delay:0.05s">
            <div class="kpi-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="kpi-content">
                <div class="kpi-value" data-count="<?= $stats['available'] ?>">0</div>
                <div class="kpi-label">Available</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-4 col-6">
        <div class="kpi-card kpi-info slide-up" style="animation-delay:0.1s">
            <div class="kpi-icon"><i class="bi bi-person-check-fill"></i></div>
            <div class="kpi-content">
                <div class="kpi-value" data-count="<?= $stats['allocated'] ?>">0</div>
                <div class="kpi-label">Allocated</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-4 col-6">
        <div class="kpi-card kpi-warning slide-up" style="animation-delay:0.15s">
            <div class="kpi-icon"><i class="bi bi-wrench-adjustable-circle-fill"></i></div>
            <div class="kpi-content">
                <div class="kpi-value" data-count="<?= $stats['under_maintenance'] ?>">0</div>
                <div class="kpi-label">Maintenance</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-4 col-6">
        <div class="kpi-card kpi-accent slide-up" style="animation-delay:0.2s">
            <div class="kpi-icon"><i class="bi bi-calendar-event-fill"></i></div>
            <div class="kpi-content">
                <div class="kpi-value" data-count="<?= $stats['active_bookings'] ?>">0</div>
                <div class="kpi-label">Active Bookings</div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-lg-4 col-md-4 col-6">
        <div class="kpi-card kpi-danger slide-up" style="animation-delay:0.25s">
            <div class="kpi-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="kpi-content">
                <div class="kpi-value" data-count="<?= $stats['overdue_count'] ?>">0</div>
                <div class="kpi-label">Overdue Returns</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-pie-chart me-2" style="color:var(--primary)"></i>Asset Status Distribution</span>
            </div>
            <div class="card-body" style="height:280px;">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bar-chart me-2" style="color:var(--primary)"></i>Assets by Category</span>
            </div>
            <div class="card-body" style="height:280px;">
                <canvas id="categoryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Row -->
<div class="row g-3 mb-4">
    <!-- Overdue Returns -->
    <div class="col-lg-6">
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-exclamation-triangle me-2" style="color:var(--danger)"></i>Overdue Returns</span>
                <?php if (count($overdueAllocations) > 0): ?>
                <span class="badge bg-danger-subtle text-danger"><?= count($overdueAllocations) ?></span>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (empty($overdueAllocations)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-check-circle" style="font-size:32px;color:var(--success);"></i>
                    <p class="text-muted mt-2 mb-0" style="font-size:13px;">No overdue returns! 🎉</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Asset</th>
                                <th>Held By</th>
                                <th>Due Date</th>
                                <th>Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overdueAllocations as $alloc): 
                                $days = (int)((time() - strtotime($alloc['expected_return_date'])) / 86400);
                            ?>
                            <tr>
                                <td>
                                    <a href="/assets/detail/<?= $alloc['asset_id'] ?>" style="font-weight:600;">
                                        <?= Helpers::e($alloc['asset_tag']) ?>
                                    </a>
                                    <div style="font-size:12px;color:var(--text-muted);"><?= Helpers::e($alloc['asset_name']) ?></div>
                                </td>
                                <td><?= Helpers::e($alloc['holder_name']) ?></td>
                                <td><?= Helpers::formatDate($alloc['expected_return_date']) ?></td>
                                <td><span class="badge bg-danger-subtle text-danger"><?= $days ?> days</span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Today's Bookings -->
    <div class="col-lg-6">
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar-day me-2" style="color:var(--info)"></i>Today's Bookings</span>
                <a href="/booking" class="btn btn-sm btn-ghost">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($todayBookings)): ?>
                <div class="text-center py-4">
                    <i class="bi bi-calendar-x" style="font-size:32px;color:var(--text-muted);"></i>
                    <p class="text-muted mt-2 mb-0" style="font-size:13px;">No bookings scheduled today</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>Resource</th><th>Time</th><th>Booked By</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($todayBookings as $booking): ?>
                            <tr>
                                <td style="font-weight:600;"><?= Helpers::e($booking['asset_name']) ?></td>
                                <td style="font-size:12px;">
                                    <?= date('h:i A', strtotime($booking['start_time'])) ?> — <?= date('h:i A', strtotime($booking['end_time'])) ?>
                                </td>
                                <td><?= Helpers::e($booking['booked_by_name']) ?></td>
                                <td><?= Helpers::statusBadge($booking['status']) ?></td>
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

<!-- Upcoming Returns + Recent Activity -->
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card fade-in">
            <div class="card-header">
                <i class="bi bi-arrow-return-left me-2" style="color:var(--warning)"></i>Upcoming Returns (7 days)
            </div>
            <div class="card-body p-0">
                <?php if (empty($upcomingReturns)): ?>
                <div class="text-center py-4">
                    <p class="text-muted mb-0" style="font-size:13px;">No upcoming returns</p>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Asset</th><th>Held By</th><th>Due Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($upcomingReturns as $ret): ?>
                            <tr>
                                <td>
                                    <a href="/assets/detail/<?= $ret['asset_id'] ?>" style="font-weight:600;"><?= Helpers::e($ret['asset_tag']) ?></a>
                                    <div style="font-size:12px;color:var(--text-muted);"><?= Helpers::e($ret['asset_name']) ?></div>
                                </td>
                                <td><?= Helpers::e($ret['holder_name']) ?></td>
                                <td><?= Helpers::statusBadge(Helpers::formatDate($ret['expected_return_date'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card fade-in">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-activity me-2" style="color:var(--secondary)"></i>Recent Activity</span>
                <a href="/notifications/logs" class="btn btn-sm btn-ghost">View All</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recentActivity)): ?>
                <div class="text-center py-4">
                    <p class="text-muted mb-0" style="font-size:13px;">No recent activity</p>
                </div>
                <?php else: ?>
                <div style="max-height:300px;overflow-y:auto;">
                    <?php foreach ($recentActivity as $log): ?>
                    <div style="padding:10px 16px;border-bottom:1px solid var(--border);font-size:13px;">
                        <div style="display:flex;justify-content:space-between;">
                            <span><strong><?= Helpers::e($log['user_name'] ?? 'System') ?></strong> <?= Helpers::e($log['action']) ?></span>
                            <span style="font-size:11px;color:var(--text-muted);"><?= Helpers::timeAgo($log['created_at']) ?></span>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:2px;">
                            <?= Helpers::e($log['entity_type']) ?> #<?= $log['entity_id'] ?? '-' ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Charts Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate counters
    document.querySelectorAll('.kpi-value[data-count]').forEach(el => {
        animateCounter(el, parseInt(el.dataset.count), 800);
    });

    // Status Distribution Doughnut
    const statusData = <?= json_encode($statusDistribution) ?>;
    const statusColors = {
        'Available': '#96E6A1', 'Allocated': '#7C83FD', 'Reserved': '#6FC8CE',
        'Under Maintenance': '#FFD93D', 'Lost': '#FF6B6B', 'Retired': '#B0B0B8', 'Disposed': '#636E72'
    };

    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: statusData.map(s => s.status),
            datasets: [{
                data: statusData.map(s => s.count),
                backgroundColor: statusData.map(s => statusColors[s.status] || '#ccc'),
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: { position: 'right', labels: { padding: 12, font: { size: 12, family: 'Inter' }, usePointStyle: true, pointStyle: 'circle' } }
            }
        }
    });

    // Category Bar Chart
    const catData = <?= json_encode($categoryDistribution) ?>;
    const gradientColors = ['#7C83FD', '#96E6A1', '#FFB5B5', '#6FC8CE', '#FFD93D', '#B8BBFF'];

    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: catData.map(c => c.name),
            datasets: [{
                label: 'Assets',
                data: catData.map(c => c.count),
                backgroundColor: gradientColors.slice(0, catData.length),
                borderRadius: 8,
                borderSkipped: false,
                barThickness: 32
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Inter', size: 11 } }, grid: { color: '#f0f0f4' } },
                x: { ticks: { font: { family: 'Inter', size: 11 } }, grid: { display: false } }
            }
        }
    });
});
</script>
