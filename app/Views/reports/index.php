<?php /** @var array $utilization */ /** @var array $maintByCategory */ /** @var array $deptSummary */ /** @var array $bookingTrends */ /** @var array $nearRetirement */ ?>

<div class="page-header fade-in">
    <div><h1><i class="bi bi-graph-up-arrow"></i> Reports & Analytics</h1><p>Actionable operational insights for managers</p></div>
    <div class="dropdown">
        <button class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-download me-1"></i> Export</button>
        <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="/reports/export?type=assets"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Assets (CSV)</a></li>
            <li><a class="dropdown-item" href="/reports/export?type=allocations"><i class="bi bi-file-earmark-spreadsheet me-2"></i>Allocations (CSV)</a></li>
        </ul>
    </div>
</div>

<!-- Charts Row 1 -->
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card fade-in">
            <div class="card-header"><i class="bi bi-bar-chart me-2" style="color:var(--primary)"></i>Maintenance Frequency by Category</div>
            <div class="card-body" style="height:300px;"><canvas id="maintChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card fade-in">
            <div class="card-header"><i class="bi bi-calendar-heat me-2" style="color:var(--info)"></i>Booking Heatmap (Peak Hours)</div>
            <div class="card-body" style="height:300px;"><canvas id="heatmapChart"></canvas></div>
        </div>
    </div>
</div>

<!-- Department Summary -->
<div class="card mb-4 fade-in">
    <div class="card-header"><i class="bi bi-building me-2" style="color:var(--primary)"></i>Department-wise Allocation Summary</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Department</th><th>Total Assets</th><th>Available</th><th>Allocated</th><th>Total Value</th></tr></thead>
                <tbody>
                    <?php foreach ($deptSummary as $d): ?>
                    <tr>
                        <td style="font-weight:600;"><?= Helpers::e($d['name']) ?></td>
                        <td><span class="badge bg-primary-subtle text-primary"><?= $d['total'] ?></span></td>
                        <td><span class="badge bg-success-subtle text-success"><?= $d['available'] ?></span></td>
                        <td><span class="badge bg-info-subtle text-info"><?= $d['allocated'] ?></span></td>
                        <td style="font-weight:500;"><?= Helpers::formatCurrency((float)$d['total_value']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Asset Utilization + Near Retirement -->
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card fade-in">
            <div class="card-header"><i class="bi bi-graph-up me-2" style="color:var(--secondary)"></i>Top Assets by Usage</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Asset</th><th>Status</th><th>Allocations</th><th>Bookings</th><th>Maintenance</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($utilization, 0, 10) as $u): ?>
                            <tr>
                                <td><a href="/assets/detail/<?= $u['asset_tag'] ?>" style="font-weight:600;"><?= Helpers::e($u['asset_tag']) ?></a> <?= Helpers::e($u['name']) ?></td>
                                <td><?= Helpers::statusBadge($u['status']) ?></td>
                                <td><?= $u['alloc_count'] ?></td>
                                <td><?= $u['booking_count'] ?></td>
                                <td><?= $u['maint_count'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card fade-in">
            <div class="card-header"><i class="bi bi-exclamation-diamond me-2" style="color:var(--warning)"></i>Nearing Retirement</div>
            <div class="card-body p-0">
                <?php if (empty($nearRetirement)): ?>
                <div class="text-center py-4 text-muted">No assets in poor condition</div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Asset</th><th>Condition</th><th>Since</th></tr></thead>
                        <tbody>
                            <?php foreach ($nearRetirement as $nr): ?>
                            <tr>
                                <td style="font-weight:600;"><?= Helpers::e($nr['asset_tag']) ?> <?= Helpers::e($nr['name']) ?></td>
                                <td><?= Helpers::statusBadge($nr['condition']) ?></td>
                                <td><?= Helpers::formatDate($nr['acquisition_date']) ?></td>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Maintenance by Category
    const maintData = <?= json_encode($maintByCategory) ?>;
    const colors = ['#7C83FD','#96E6A1','#FFB5B5','#6FC8CE','#FFD93D','#B8BBFF'];
    new Chart(document.getElementById('maintChart'), {
        type: 'bar', data: { labels: maintData.map(m=>m.name), datasets: [{ label: 'Requests', data: maintData.map(m=>m.count), backgroundColor: colors, borderRadius: 8, barThickness: 28 }] },
        options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1}},x:{grid:{display:false}}} }
    });

    // Booking Heatmap (Peak Hours)
    fetch('/api/reports/bookings').then(r=>r.json()).then(data => {
        new Chart(document.getElementById('heatmapChart'), {
            type: 'bar', data: { labels: data.map(d=> d.hour+':00'), datasets: [{ label: 'Bookings', data: data.map(d=>d.count), backgroundColor: data.map(d => d.count > 3 ? '#FF6B6B' : d.count > 1 ? '#FFD93D' : '#96E6A1'), borderRadius: 6, barThickness: 20 }] },
            options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true,ticks:{stepSize:1},title:{display:true,text:'Bookings'}},x:{title:{display:true,text:'Hour of Day'},grid:{display:false}}} }
        });
    });
});
</script>
