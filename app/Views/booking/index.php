<?php /** @var array $bookableAssets */ /** @var array $bookings */ ?>

<div class="page-header fade-in">
    <div><h1><i class="bi bi-calendar-event"></i> Resource Booking</h1><p>Book shared resources by time slot with overlap prevention</p></div>
    <a href="/booking/new" class="btn btn-primary"><i class="bi bi-calendar-plus me-1"></i> New Booking</a>
</div>

<!-- Calendar View -->
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card fade-in">
            <div class="card-header"><i class="bi bi-display me-2" style="color:var(--primary)"></i>Select Resource</div>
            <div class="card-body">
                <select id="calendarResource" class="form-select mb-3" onchange="loadCalendar()">
                    <option value="">— Select a bookable resource —</option>
                    <?php foreach ($bookableAssets as $a): ?>
                    <option value="<?= $a['id'] ?>"><?= Helpers::e($a['name']) ?> (<?= Helpers::e($a['asset_tag']) ?>)</option>
                    <?php endforeach; ?>
                </select>
                <div id="calendarInfo" class="text-muted" style="font-size:13px;">Select a resource to see its bookings on the calendar.</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card fade-in">
            <div class="card-body">
                <div id="bookingCalendar" style="min-height:400px;"></div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="card fade-in">
    <div class="card-header d-flex justify-content-between"><span>All Bookings</span><span class="badge bg-primary-subtle text-primary"><?= count($bookings) ?></span></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Resource</th><th>Booked By</th><th>Start</th><th>End</th><th>Purpose</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No bookings yet</td></tr>
                    <?php else: foreach ($bookings as $b): ?>
                    <tr>
                        <td style="font-weight:600;"><?= Helpers::e($b['asset_name']) ?><div style="font-size:11px;color:var(--text-muted)"><?= Helpers::e($b['asset_tag']) ?></div></td>
                        <td><?= Helpers::e($b['booked_by_name']) ?></td>
                        <td><?= Helpers::formatDateTime($b['start_time']) ?></td>
                        <td><?= Helpers::formatDateTime($b['end_time']) ?></td>
                        <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= Helpers::e($b['purpose'] ?? '—') ?></td>
                        <td><?= Helpers::statusBadge($b['status']) ?></td>
                        <td>
                            <?php if (in_array($b['status'], ['Upcoming']) && ($b['booked_by'] == Auth::id() || Auth::hasRole('Admin','Asset Manager'))): ?>
                            <form method="POST" action="/booking/cancel" class="d-inline" onsubmit="return confirm('Cancel this booking?')">
                                <?= Helpers::csrfField() ?><input type="hidden" name="id" value="<?= $b['id'] ?>">
                                <button class="btn btn-sm btn-ghost text-danger"><i class="bi bi-x-circle"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
let calendar;
document.addEventListener('DOMContentLoaded', function() {
    const calEl = document.getElementById('bookingCalendar');
    calendar = new FullCalendar.Calendar(calEl, {
        initialView: 'timeGridWeek',
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'timeGridWeek,timeGridDay' },
        slotMinTime: '07:00:00', slotMaxTime: '22:00:00',
        allDaySlot: false, nowIndicator: true, height: 'auto',
        eventClick: function(info) {
            alert('Booked by: ' + (info.event.extendedProps.booked_by || 'Unknown') + '\nStatus: ' + (info.event.extendedProps.status || ''));
        }
    });
    calendar.render();
});

function loadCalendar() {
    const assetId = document.getElementById('calendarResource').value;
    if (!assetId) { calendar.removeAllEvents(); return; }
    
    fetch('/api/booking/events/' + assetId)
        .then(r => r.json())
        .then(events => {
            calendar.removeAllEvents();
            events.forEach(e => calendar.addEvent(e));
        });
}
</script>
