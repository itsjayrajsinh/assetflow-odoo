<?php
/**
 * AssetFlow — Dashboard Controller
 */

class DashboardController extends Controller
{
    public function index(): void
    {
        Middleware::auth();
        $user = Auth::user();
        $role = $user['role'];

        // KPI Stats
        $stats = [];
        $stats['total_assets'] = (int) Database::fetchColumn("SELECT COUNT(*) FROM assets");
        $stats['available'] = (int) Database::fetchColumn("SELECT COUNT(*) FROM assets WHERE status = 'Available'");
        $stats['allocated'] = (int) Database::fetchColumn("SELECT COUNT(*) FROM assets WHERE status = 'Allocated'");
        $stats['under_maintenance'] = (int) Database::fetchColumn("SELECT COUNT(*) FROM assets WHERE status = 'Under Maintenance'");
        $stats['active_bookings'] = (int) Database::fetchColumn("SELECT COUNT(*) FROM bookings WHERE status IN ('Upcoming','Ongoing')");
        $stats['pending_transfers'] = (int) Database::fetchColumn("SELECT COUNT(*) FROM transfer_requests WHERE status = 'Requested'");
        $stats['pending_maintenance'] = (int) Database::fetchColumn("SELECT COUNT(*) FROM maintenance_requests WHERE status = 'Pending'");

        // Overdue allocations
        $overdueAllocations = Database::fetchAll(
            "SELECT al.*, a.asset_tag, a.name as asset_name, u.name as holder_name, d.name as dept_name
             FROM allocations al
             JOIN assets a ON al.asset_id = a.id
             JOIN users u ON al.allocated_to = u.id
             LEFT JOIN departments d ON al.department_id = d.id
             WHERE al.status = 'Active' AND al.expected_return_date < CURDATE()
             ORDER BY al.expected_return_date ASC
             LIMIT 10"
        );
        $stats['overdue_count'] = count($overdueAllocations);

        // Upcoming returns (within 7 days)
        $upcomingReturns = Database::fetchAll(
            "SELECT al.*, a.asset_tag, a.name as asset_name, u.name as holder_name
             FROM allocations al
             JOIN assets a ON al.asset_id = a.id
             JOIN users u ON al.allocated_to = u.id
             WHERE al.status = 'Active' 
               AND al.expected_return_date >= CURDATE()
               AND al.expected_return_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             ORDER BY al.expected_return_date ASC
             LIMIT 10"
        );

        // Recent activity
        $recentActivity = Database::fetchAll(
            "SELECT al.*, u.name as user_name
             FROM activity_logs al
             LEFT JOIN users u ON al.user_id = u.id
             ORDER BY al.created_at DESC
             LIMIT 8"
        );

        // Today's bookings
        $todayBookings = Database::fetchAll(
            "SELECT b.*, a.name as asset_name, a.asset_tag, u.name as booked_by_name
             FROM bookings b
             JOIN assets a ON b.asset_id = a.id
             JOIN users u ON b.booked_by = u.id
             WHERE DATE(b.start_time) = CURDATE() AND b.status IN ('Upcoming','Ongoing')
             ORDER BY b.start_time"
        );

        // Asset status distribution for chart
        $statusDistribution = Database::fetchAll(
            "SELECT status, COUNT(*) as count FROM assets GROUP BY status ORDER BY count DESC"
        );

        // Category distribution
        $categoryDistribution = Database::fetchAll(
            "SELECT c.name, COUNT(a.id) as count 
             FROM asset_categories c 
             LEFT JOIN assets a ON a.category_id = c.id
             GROUP BY c.id, c.name
             ORDER BY count DESC
             LIMIT 6"
        );

        $this->view('dashboard/index', [
            'stats'                 => $stats,
            'overdueAllocations'    => $overdueAllocations,
            'upcomingReturns'       => $upcomingReturns,
            'recentActivity'        => $recentActivity,
            'todayBookings'         => $todayBookings,
            'statusDistribution'    => $statusDistribution,
            'categoryDistribution'  => $categoryDistribution,
        ], 'Dashboard — AssetFlow');
    }

    public function stats(): void
    {
        Middleware::auth();
        $stats = [
            'total_assets' => (int) Database::fetchColumn("SELECT COUNT(*) FROM assets"),
            'available'    => (int) Database::fetchColumn("SELECT COUNT(*) FROM assets WHERE status = 'Available'"),
            'allocated'    => (int) Database::fetchColumn("SELECT COUNT(*) FROM assets WHERE status = 'Allocated'"),
            'bookings'     => (int) Database::fetchColumn("SELECT COUNT(*) FROM bookings WHERE status IN ('Upcoming','Ongoing')"),
        ];
        $this->json($stats);
    }
}
