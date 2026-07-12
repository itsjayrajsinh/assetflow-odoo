<?php /** @var array $assets */ /** @var array $pagination */ /** @var array $categories */ /** @var array $statuses */ ?>

<div class="page-header fade-in">
    <div>
        <h1><i class="bi bi-box-seam"></i> Asset Directory</h1>
        <p>Search and track all organizational assets</p>
    </div>
    <?php if (Auth::hasRole('Admin', 'Asset Manager')): ?>
    <a href="/assets/register" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Register Asset</a>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4 fade-in">
    <div class="card-body py-3">
        <form method="GET" action="/assets" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Asset tag, name, serial, location..." value="<?= Helpers::e($search) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $s): ?>
                    <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filterCategory == $c['id'] ? 'selected' : '' ?>><?= Helpers::e($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
            </div>
            <div class="col-md-2">
                <a href="/assets" class="btn btn-ghost w-100">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Asset Table -->
<div class="card fade-in">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Asset Tag</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Condition</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Bookable</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($assets)): ?>
                    <tr><td colspan="8">
                        <div class="empty-state">
                            <i class="bi bi-box-seam"></i>
                            <h3>No assets found</h3>
                            <p>Try adjusting your filters or register a new asset.</p>
                        </div>
                    </td></tr>
                    <?php else: foreach ($assets as $a): ?>
                    <tr style="cursor:pointer;" onclick="window.location='/assets/detail/<?= $a['id'] ?>'">
                        <td>
                            <a href="/assets/detail/<?= $a['id'] ?>" style="font-weight:700;color:var(--primary);">
                                <?= Helpers::e($a['asset_tag']) ?>
                            </a>
                        </td>
                        <td style="font-weight:500;"><?= Helpers::e($a['name']) ?></td>
                        <td><?= Helpers::e($a['category_name'] ?? '—') ?></td>
                        <td style="font-size:12px;"><?= Helpers::e($a['location'] ?? '—') ?></td>
                        <td><?= Helpers::statusBadge($a['condition']) ?></td>
                        <td><?= Helpers::statusBadge($a['status']) ?></td>
                        <td><?= $a['assigned_name'] ? Helpers::e($a['assigned_name']) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= $a['is_bookable'] ? '<i class="bi bi-check-circle-fill text-success"></i>' : '<i class="bi bi-dash text-muted"></i>' ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($pagination['total_pages'] > 1): ?>
    <div class="card-body pt-2">
        <?= Helpers::paginationHtml($pagination, '/assets') ?>
    </div>
    <?php endif; ?>
</div>
