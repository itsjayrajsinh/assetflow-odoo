<?php
/**
 * AssetFlow — Booking Controller
 */


class BookingController extends Controller
{
    public function index(): void  {
        Middleware::auth();
        $bookableAssets = Database::fetchAll("SELECT id, asset_tag, name, location FROM assets WHERE is_bookable = 1 AND status IN ('Available','Reserved') ORDER BY name");
        $bookings = Database::fetchAll(
            "SELECT b.*, a.asset_tag, a.name as asset_name, u.name as booked_by_name
             FROM bookings b JOIN assets a ON b.asset_id = a.id JOIN users u ON b.booked_by = u.id
             ORDER BY b.start_time DESC LIMIT 50" );

        // Auto-update statuses
        Database::execute("UPDATE bookings SET status='Ongoing' WHERE status='Upcoming' AND start_time <= NOW() AND end_time > NOW()");
        Database::execute("UPDATE bookings SET status='Completed' WHERE status IN ('Upcoming','Ongoing') AND end_time <= NOW()");

        $this->view('booking/index', ['bookableAssets' => $bookableAssets, 'bookings' => $bookings], 'Resource Booking — AssetFlow');  }

    public function showBook(): void {
        Middleware::auth();
        $bookableAssets = Database::fetchAll("SELECT id, asset_tag, name, location FROM assets WHERE is_bookable = 1 AND status IN ('Available','Reserved') ORDER BY name");
        $this->view('booking/book', ['bookableAssets' => $bookableAssets], 'Book Resource — AssetFlow');
    }

    public function book(): void
    {
        Middleware::auth();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/booking/new'); return; }
        $assetId = (int) $this->input('asset_id');
        $startTime = $this->input('start_time');
        $endTime = $this->input('end_time');
        $purpose = $this->input('purpose');

        if (!$assetId || !$startTime || !$endTime) { $this->flash('error', 'All fields are required.'); $this->redirect('/booking/new'); return; }
        if (strtotime($endTime) <= strtotime($startTime)) { $this->flash('error', 'End time must be after start time.'); $this->redirect('/booking/new'); return; }
        // Overlap check
        $overlap = Database::fetch(
            "SELECT id FROM bookings WHERE asset_id = :aid AND status IN ('Upcoming','Ongoing')
             AND start_time < :end AND end_time > :start LIMIT 1",
            ['aid' => $assetId, 'end' => $endTime, 'start' => $startTime]
        );

        if ($overlap) {
            $this->flash('error', 'Booking rejected: this time slot overlaps with an existing booking. Please choose a different time.');
            $this->redirect('/booking/new');
            return;
        }

        $bookingId = Database::insert(
            "INSERT INTO bookings (asset_id, booked_by, start_time, end_time, purpose, status) VALUES (:asset, :by, :start, :end, :purpose, 'Upcoming')",
            ['asset' => $assetId, 'by' => Auth::id(), 'start' => $startTime, 'end' => $endTime, 'purpose' => $purpose]
        );
        $asset = Database::fetch("SELECT asset_tag, name FROM assets WHERE id=:id", ['id' => $assetId]);
        Helpers::notify(Auth::id(), 'booking_confirmed', 'Booking Confirmed', "{$asset['name']} booked for " . date('M d, h:i A', strtotime($startTime)), '/booking');
        Helpers::logActivity(Auth::id(), 'resource_booked', 'booking', $bookingId, ['asset' => $asset['asset_tag']]);

        $this->flash('success', 'Resource booked successfully!');
        $this->redirect('/booking');
    }
    public function cancel(): void
    {
        Middleware::auth();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/booking'); return; }

        $id = (int) $this->input('id');
        $booking = Database::fetch("SELECT * FROM bookings WHERE id=:id", ['id' => $id]);
        if (!$booking || ($booking['booked_by'] != Auth::id() && !Auth::hasRole('Admin', 'Asset Manager'))) {
            $this->flash('error', 'Cannot cancel this booking.');
            $this->redirect('/booking');
            return;
        }

        Database::execute("UPDATE bookings SET status='Cancelled' WHERE id=:id", ['id' => $id]);
        Helpers::logActivity(Auth::id(), 'booking_cancelled', 'booking', $id);
        $this->flash('success', 'Booking cancelled.');
        $this->redirect('/booking');
    }

    public function apiEvents(string $assetId): void
    {
        Middleware::auth();
        $bookings = Database::fetchAll(
            "SELECT b.id, b.start_time as start, b.end_time as end, b.purpose as title, b.status, u.name as booked_by
             FROM bookings b JOIN users u ON b.booked_by = u.id
             WHERE b.asset_id = :id AND b.status IN ('Upcoming','Ongoing')
             ORDER BY b.start_time",
            ['id' => (int) $assetId]
        );
        $colors = ['Upcoming' => '#7C83FD', 'Ongoing' => '#96E6A1', 'Completed' => '#B0B0B8', 'Cancelled' => '#FF6B6B'];
        $events = array_map(function($b) use ($colors) {
            return [
                'id' => $b['id'], 'title' => $b['title'] ?: $b['booked_by'], 'start' => $b['start'], 'end' => $b['end'],
                'color' => $colors[$b['status']] ?? '#ccc', 'extendedProps' => ['booked_by' => $b['booked_by'], 'status' => $b['status']]
            ];
        }, $bookings);

        $this->json($events);
    }

    public function apiCheckOverlap(): void
    {
        Middleware::auth();
        $assetId = (int) $this->query('asset_id');
        $start = $this->query('start');
        $end = $this->query('end');
        $overlap = Database::fetch(
            "SELECT id FROM bookings WHERE asset_id=:aid AND status IN ('Upcoming','Ongoing') AND start_time < :end AND end_time > :start LIMIT 1",
            ['aid' => $assetId, 'end' => $end, 'start' => $start]
        );
        $this->json(['overlap' => (bool) $overlap]);
    }
}
