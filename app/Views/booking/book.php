<?php /** @var array $bookableAssets */ ?>

<div class="page-header fade-in">
    <div><h1><i class="bi bi-calendar-plus"></i> New Booking</h1><p>Book a shared resource by time slot</p></div>
    <a href="/booking" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i> Back</a>
</div>

<div class="row justify-content-center"><div class="col-lg-6">
<div class="card fade-in"><div class="card-body p-4">
    <form method="POST" action="/booking/new" id="bookingForm">
        <?= Helpers::csrfField() ?>
        <div class="mb-3">
            <label class="form-label">Resource *</label>
            <select name="asset_id" id="bookAsset" class="form-select" required>
                <option value="">— Select a bookable resource —</option>
                <?php foreach ($bookableAssets as $a): ?>
                <option value="<?= $a['id'] ?>"><?= Helpers::e($a['name']) ?> (<?= Helpers::e($a['asset_tag']) ?>) — <?= Helpers::e($a['location'] ?? '') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="row g-3">
            <div class="col-6">
                <label class="form-label">Start Time *</label>
                <input type="datetime-local" name="start_time" id="bookStart" class="form-control" required>
            </div>
            <div class="col-6">
                <label class="form-label">End Time *</label>
                <input type="datetime-local" name="end_time" id="bookEnd" class="form-control" required>
            </div>
        </div>
        <div id="overlapWarning" class="alert alert-danger mt-3" style="display:none;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> This time slot overlaps with an existing booking!
        </div>
        <div class="mb-3 mt-3">
            <label class="form-label">Purpose</label>
            <input type="text" name="purpose" class="form-control" placeholder="e.g. Sprint Planning Meeting">
        </div>
        <hr class="my-4">
        <button type="submit" class="btn btn-primary w-100 btn-lg" id="bookBtn"><i class="bi bi-calendar-check me-1"></i> Book Now</button>
    </form>
</div></div>
</div></div>

<script>
['bookAsset','bookStart','bookEnd'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', checkOverlap);
});

function checkOverlap() {
    const assetId = document.getElementById('bookAsset').value;
    const start = document.getElementById('bookStart').value;
    const end = document.getElementById('bookEnd').value;
    if (!assetId || !start || !end) return;

    fetch(`/api/booking/check?asset_id=${assetId}&start=${start}&end=${end}`)
        .then(r => r.json())
        .then(data => {
            const warn = document.getElementById('overlapWarning');
            const btn = document.getElementById('bookBtn');
            if (data.overlap) {
                warn.style.display = 'flex';
                btn.disabled = true;
            } else {
                warn.style.display = 'none';
                btn.disabled = false;
            }
        });
}
</script>
