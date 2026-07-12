<?php
/**
 * AssetFlow — Reports & Analytics Controller
 */

class ReportController extends Controller
{
    public function index(): void
    {
        Middleware::leadership();

        // Asset Utilization
        $utilization = Database::fetchAll(
            "SELECT a.asset_tag, a.name, a.status, 
                    (SELECT COUNT(*) FROM allocations WHERE asset_id = a.id) as alloc_count,
                    (SELECT COUNT(*) FROM bookings WHERE asset_id = a.id) as booking_count,
                    (SELECT COUNT(*) FROM maintenance_requests WHERE asset_id = a.id) as maint_count
             FROM assets a ORDER BY alloc_count DESC LIMIT 20"
        );

        // Maintenance frequency by category
        $maintByCategory = Database::fetchAll(
            "SELECT c.name, COUNT(m.id) as count
             FROM maintenance_requests m
             JOIN assets a ON m.asset_id = a.id
             JOIN asset_categories c ON a.category_id = c.id
             GROUP BY c.id, c.name ORDER BY count DESC"
        );

        // Department allocation summary
        $deptSummary = Database::fetchAll(
            "SELECT d.name, COUNT(a.id) as total,
                    SUM(CASE WHEN a.status='Available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN a.status='Allocated' THEN 1 ELSE 0 END) as allocated,
                    SUM(a.acquisition_cost) as total_value
             FROM departments d
             LEFT JOIN assets a ON a.department_id = d.id
             GROUP BY d.id, d.name
             HAVING total > 0
             ORDER BY total DESC"
        );

        // Booking trends (last 7 days)
        $bookingTrends = Database::fetchAll(
            "SELECT DATE(start_time) as date, COUNT(*) as count
             FROM bookings WHERE start_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY DATE(start_time) ORDER BY date"
        );

        // Assets nearing retirement (old condition Poor)
        $nearRetirement = Database::fetchAll(
            "SELECT asset_tag, name, `condition`, acquisition_date, status
             FROM assets WHERE `condition` = 'Poor' AND status NOT IN ('Retired','Disposed')
             ORDER BY acquisition_date LIMIT 10"
        );

        $this->view('reports/index', [
            'utilization'     => $utilization,
            'maintByCategory' => $maintByCategory,
            'deptSummary'     => $deptSummary,
            'bookingTrends'   => $bookingTrends,
            'nearRetirement'  => $nearRetirement,
        ], 'Reports & Analytics — AssetFlow');
    }

    public function export(): void
    {
        Middleware::leadership();
        $type = $this->query('type', 'assets');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="assetflow_' . $type . '_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        if ($type === 'assets') {
            fputcsv($output, ['Asset Tag', 'Name', 'Category', 'Serial Number', 'Status', 'Condition', 'Location', 'Assigned To', 'Acquisition Date', 'Cost']);
            $assets = Database::fetchAll(
                "SELECT a.asset_tag, a.name, c.name as category, a.serial_number, a.status, a.`condition`, a.location, u.name as assigned_to, a.acquisition_date, a.acquisition_cost
                 FROM assets a LEFT JOIN asset_categories c ON a.category_id=c.id LEFT JOIN users u ON a.assigned_to=u.id ORDER BY a.asset_tag"
            );
            foreach ($assets as $a) { fputcsv($output, array_values($a)); }
        } elseif ($type === 'allocations') {
            fputcsv($output, ['Asset Tag', 'Asset Name', 'Allocated To', 'Date', 'Expected Return', 'Status']);
            $allocs = Database::fetchAll(
                "SELECT a.asset_tag, a.name, u.name, al.allocation_date, al.expected_return_date, al.status
                 FROM allocations al JOIN assets a ON al.asset_id=a.id JOIN users u ON al.allocated_to=u.id ORDER BY al.created_at DESC"
            );
            foreach ($allocs as $al) { fputcsv($output, array_values($al)); }
        }

        fclose($output);
        exit;
    }

    // API endpoints for dynamic chart data
    public function apiUtilization(): void { Middleware::leadership(); $this->json(Database::fetchAll("SELECT status, COUNT(*) as count FROM assets GROUP BY status")); }
    public function apiMaintenance(): void { Middleware::leadership(); $this->json(Database::fetchAll("SELECT priority, COUNT(*) as count FROM maintenance_requests GROUP BY priority")); }
    public function apiBookings(): void { Middleware::leadership(); $this->json(Database::fetchAll("SELECT HOUR(start_time) as hour, COUNT(*) as count FROM bookings WHERE status!='Cancelled' GROUP BY HOUR(start_time) ORDER BY hour")); }
    public function apiDepartments(): void { Middleware::leadership(); $this->json(Database::fetchAll("SELECT d.name, COUNT(a.id) as count FROM departments d LEFT JOIN assets a ON a.department_id=d.id GROUP BY d.id,d.name HAVING count > 0")); }
}
