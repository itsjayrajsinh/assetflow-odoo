<?php /** @var array $categories */ /** @var array $departments */ /** @var string $nextTag */ ?>

<div class="page-header fade-in">
    <div>
        <h1><i class="bi bi-plus-circle"></i> Register New Asset</h1>
        <p>Add a new asset to the organization's inventory</p>
    </div>
    <a href="/assets" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i> Back to Directory</a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card fade-in">
            <div class="card-body p-4">
                <form method="POST" action="/assets/register" enctype="multipart/form-data">
                    <?= Helpers::csrfField() ?>
                    
                    <div class="row g-3">
                        <!-- Asset Tag (auto-generated) -->
                        <div class="col-md-4">
                            <label class="form-label">Asset Tag</label>
                            <input type="text" class="form-control" value="<?= Helpers::e($nextTag) ?>" disabled
                                   style="font-weight:700;color:var(--primary);background:var(--primary-subtle);">
                            <div class="form-text">Auto-generated</div>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label">Asset Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Dell Latitude 5540" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">— Select Category —</option>
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= Helpers::e($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Serial Number</label>
                            <input type="text" name="serial_number" class="form-control" placeholder="Manufacturer serial number">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Acquisition Date</label>
                            <input type="date" name="acquisition_date" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Acquisition Cost (₹)</label>
                            <input type="number" name="acquisition_cost" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Condition</label>
                            <select name="condition" class="form-select">
                                <option value="New">New</option>
                                <option value="Good">Good</option>
                                <option value="Fair">Fair</option>
                                <option value="Poor">Poor</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" placeholder="e.g. IT Office - Floor 2">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select">
                                <option value="">— None —</option>
                                <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= Helpers::e($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes..."></textarea>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_bookable" id="isBookable" value="1">
                                <label class="form-check-label" for="isBookable">
                                    <strong>Shared / Bookable Resource</strong>
                                    <span class="d-block text-muted" style="font-size:12px;">Enable time-slot booking for this asset (rooms, vehicles, equipment)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/assets" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check-circle me-1"></i> Register Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
