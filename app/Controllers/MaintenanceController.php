<?php
/**
 * AssetFlow — Maintenance Controller
 */

class MaintenanceController extends Controller
{
    public function index(): void
    {
        Middleware::auth();
        $role = Auth::role();
        $sql = "SELECT m.*, a.asset_tag, a.name as asset_name, u.name as requester_name, 
                       ap.name as approver_name, t.name as tech_name
                FROM maintenance_requests m
                JOIN assets a ON m.asset_id = a.id
                JOIN users u ON m.requested_by = u.id
                LEFT JOIN users ap ON m.approved_by = ap.id
                LEFT JOIN users t ON m.technician_id = t.id";
        
        if ($role === 'Employee') {
            $sql .= " WHERE m.requested_by = :uid";
            $params = ['uid' => Auth::id()];
        } else {
            $params = [];
        }
        $sql .= " ORDER BY m.created_at DESC LIMIT 100";

        $requests = Database::fetchAll($sql, $params);
        $employees = Database::fetchAll("SELECT id, name FROM users WHERE status='Active' ORDER BY name");

        $this->view('maintenance/index', ['requests' => $requests, 'employees' => $employees], 'Maintenance — AssetFlow');
    }

    public function showCreate(): void
    {
        Middleware::auth();
        $assetId = $_GET['asset_id'] ?? null;
        $assets = Database::fetchAll("SELECT id, asset_tag, name FROM assets WHERE status NOT IN ('Disposed','Retired') ORDER BY asset_tag");
        $this->view('maintenance/create', ['assets' => $assets, 'preselectedAsset' => $assetId], 'Raise Maintenance Request — AssetFlow');
    }

    public function create(): void
    {
        Middleware::auth();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/maintenance/create'); return; }

        $assetId = (int) $this->input('asset_id');
        $description = $this->input('description');
        $priority = $this->input('priority', 'Medium');

        if (!$assetId || !$description) { $this->flash('error', 'Asset and description are required.'); $this->redirect('/maintenance/create'); return; }

        $photo = null;
        if (!empty($_FILES['photo']['name'])) { $photo = Helpers::uploadFile($_FILES['photo'], 'maintenance'); }

        $reqId = Database::insert(
            "INSERT INTO maintenance_requests (asset_id, requested_by, description, priority, photo, status)
             VALUES (:asset, :by, :desc, :pri, :photo, 'Pending')",
            ['asset' => $assetId, 'by' => Auth::id(), 'desc' => $description, 'pri' => $priority, 'photo' => $photo]
        );

        // Notify Asset Managers
        $managers = Database::fetchAll("SELECT id FROM users WHERE role IN ('Admin','Asset Manager') AND status='Active'");
        $asset = Database::fetch("SELECT asset_tag, name FROM assets WHERE id=:id", ['id' => $assetId]);
        foreach ($managers as $m) {
            Helpers::notify($m['id'], 'maintenance_pending', 'New Maintenance Request',
                Auth::user()['name'] . " raised a {$priority} maintenance request for {$asset['asset_tag']}.", '/maintenance');
        }

        Helpers::logActivity(Auth::id(), 'maintenance_requested', 'maintenance', $reqId, ['asset' => $asset['asset_tag'], 'priority' => $priority]);
        $this->flash('success', 'Maintenance request submitted!');
        $this->redirect('/maintenance');
    }

    public function approve(): void
    {
        Middleware::manager();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid.'); $this->redirect('/maintenance'); return; }
        $id = (int) $this->input('id');
        $req = Database::fetch("SELECT * FROM maintenance_requests WHERE id=:id AND status='Pending'", ['id' => $id]);
        if (!$req) { $this->flash('error', 'Not found.'); $this->redirect('/maintenance'); return; }

        Database::execute("UPDATE maintenance_requests SET status='Approved', approved_by=:by WHERE id=:id", ['by' => Auth::id(), 'id' => $id]);
        Database::execute("UPDATE assets SET status='Under Maintenance' WHERE id=:id", ['id' => $req['asset_id']]);

        Helpers::notify($req['requested_by'], 'maintenance_approved', 'Maintenance Approved', 'Your maintenance request has been approved.', '/maintenance');
        Helpers::logActivity(Auth::id(), 'maintenance_approved', 'maintenance', $id);
        $this->flash('success', 'Request approved. Asset marked Under Maintenance.');
        $this->redirect('/maintenance');
    }

    public function reject(): void
    {
        Middleware::manager();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid.'); $this->redirect('/maintenance'); return; }
        $id = (int) $this->input('id');
        Database::execute("UPDATE maintenance_requests SET status='Rejected', approved_by=:by WHERE id=:id", ['by' => Auth::id(), 'id' => $id]);
        
        $req = Database::fetch("SELECT requested_by FROM maintenance_requests WHERE id=:id", ['id' => $id]);
        if ($req) Helpers::notify($req['requested_by'], 'maintenance_rejected', 'Maintenance Rejected', 'Your maintenance request has been rejected.', '/maintenance');
        Helpers::logActivity(Auth::id(), 'maintenance_rejected', 'maintenance', $id);
        $this->flash('success', 'Request rejected.');
        $this->redirect('/maintenance');
    }

    public function assign(): void
    {
        Middleware::manager();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid.'); $this->redirect('/maintenance'); return; }
        $id = (int) $this->input('id');
        $techId = (int) $this->input('technician_id');

        Database::execute("UPDATE maintenance_requests SET status='Assigned', technician_id=:tech WHERE id=:id", ['tech' => $techId, 'id' => $id]);
        Helpers::notify($techId, 'maintenance_assigned', 'Maintenance Assigned', 'You have been assigned a maintenance task.', '/maintenance');
        Helpers::logActivity(Auth::id(), 'technician_assigned', 'maintenance', $id);
        $this->flash('success', 'Technician assigned.');
        $this->redirect('/maintenance');
    }

    public function progress(): void
    {
        Middleware::auth();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid.'); $this->redirect('/maintenance'); return; }
        $id = (int) $this->input('id');
        Database::execute("UPDATE maintenance_requests SET status='In Progress' WHERE id=:id", ['id' => $id]);
        Helpers::logActivity(Auth::id(), 'maintenance_in_progress', 'maintenance', $id);
        $this->flash('success', 'Marked as In Progress.');
        $this->redirect('/maintenance');
    }

    public function resolve(): void
    {
        Middleware::manager();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid.'); $this->redirect('/maintenance'); return; }
        $id = (int) $this->input('id');
        $notes = $this->input('resolution_notes');
        
        $req = Database::fetch("SELECT * FROM maintenance_requests WHERE id=:id", ['id' => $id]);
        if (!$req) { $this->flash('error', 'Not found.'); $this->redirect('/maintenance'); return; }

        Database::execute("UPDATE maintenance_requests SET status='Resolved', resolution_notes=:notes, resolved_at=NOW() WHERE id=:id",
            ['notes' => $notes, 'id' => $id]);
        Database::execute("UPDATE assets SET status='Available' WHERE id=:id", ['id' => $req['asset_id']]);

        Helpers::notify($req['requested_by'], 'maintenance_resolved', 'Maintenance Resolved', 'Your maintenance request has been resolved.', '/maintenance');
        Helpers::logActivity(Auth::id(), 'maintenance_resolved', 'maintenance', $id);
        $this->flash('success', 'Request resolved. Asset back to Available.');
        $this->redirect('/maintenance');
    }
}
