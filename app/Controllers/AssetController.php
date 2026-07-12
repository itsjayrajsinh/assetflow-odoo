<?php
/**
 * AssetFlow — Asset Controller
 */

class AssetController extends Controller
{
    public function index(): void
    {
        Middleware::auth();
        $search = $this->query('search', '');
        $status = $this->query('status', '');
        $category = $this->query('category', '');
        $page = max(1, (int) $this->query('page', 1));

        $sql = "SELECT a.*, c.name as category_name, d.name as dept_name, u.name as assigned_name
                FROM assets a
                LEFT JOIN asset_categories c ON a.category_id = c.id
                LEFT JOIN departments d ON a.department_id = d.id
                LEFT JOIN users u ON a.assigned_to = u.id WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (a.asset_tag LIKE :s OR a.name LIKE :s OR a.serial_number LIKE :s OR a.location LIKE :s)";
            $params['s'] = "%{$search}%";
        }
        if ($status) { $sql .= " AND a.status = :st"; $params['st'] = $status; }
        if ($category) { $sql .= " AND a.category_id = :cat"; $params['cat'] = $category; }

        $sql .= " ORDER BY a.id DESC";
        $pagination = Helpers::paginate($sql, $params, $page, 15);

        $categories = Database::fetchAll("SELECT id, name FROM asset_categories WHERE status='Active' ORDER BY name");
        $statuses = ['Available','Allocated','Reserved','Under Maintenance','Lost','Retired','Disposed'];

        $this->view('assets/index', [
            'assets'     => $pagination['data'],
            'pagination' => $pagination,
            'categories' => $categories,
            'statuses'   => $statuses,
            'search'     => $search,
            'filterStatus'   => $status,
            'filterCategory' => $category,
        ], 'Asset Directory — AssetFlow');
    }

    public function showRegister(): void
    {
        Middleware::manager();
        $categories = Database::fetchAll("SELECT id, name FROM asset_categories WHERE status='Active' ORDER BY name");
        $departments = Database::fetchAll("SELECT id, name FROM departments WHERE status='Active' ORDER BY name");
        $nextTag = Helpers::generateAssetTag();

        $this->view('assets/register', [
            'categories'  => $categories,
            'departments' => $departments,
            'nextTag'     => $nextTag,
        ], 'Register Asset — AssetFlow');
    }

    public function register(): void
    {
        Middleware::manager();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/assets/register'); return; }

        $assetTag = Helpers::generateAssetTag();
        $data = [
            'asset_tag'        => $assetTag,
            'name'             => $this->input('name'),
            'category_id'      => $this->input('category_id') ?: null,
            'serial_number'    => $this->input('serial_number'),
            'acquisition_date' => $this->input('acquisition_date') ?: null,
            'acquisition_cost' => (float) $this->input('acquisition_cost', 0),
            'condition'        => $this->input('condition', 'New'),
            'location'         => $this->input('location'),
            'status'           => 'Available',
            'is_bookable'      => $this->input('is_bookable') ? 1 : 0,
            'department_id'    => $this->input('department_id') ?: null,
            'notes'            => $this->input('notes'),
        ];

        if (!$data['name']) { $this->flash('error', 'Asset name is required.'); $this->redirect('/assets/register'); return; }

        // Handle photo upload
        if (!empty($_FILES['photo']['name'])) {
            $data['photo'] = Helpers::uploadFile($_FILES['photo'], 'assets');
        }

        $assetId = Database::insert(
            "INSERT INTO assets (asset_tag, name, category_id, serial_number, acquisition_date, acquisition_cost, `condition`, location, status, is_bookable, department_id, notes, photo)
             VALUES (:asset_tag, :name, :category_id, :serial_number, :acquisition_date, :acquisition_cost, :condition, :location, :status, :is_bookable, :department_id, :notes, " . (isset($data['photo']) ? ":photo" : "NULL") . ")",
            $data
        );

        Helpers::logActivity(Auth::id(), 'asset_registered', 'asset', $assetId, ['tag' => $assetTag, 'name' => $data['name']]);
        $this->flash('success', "Asset {$assetTag} registered successfully!");
        $this->redirect('/assets/detail/' . $assetId);
    }

    public function detail(string $id): void
    {
        Middleware::auth();
        $asset = Database::fetch(
            "SELECT a.*, c.name as category_name, d.name as dept_name, u.name as assigned_name
             FROM assets a
             LEFT JOIN asset_categories c ON a.category_id = c.id
             LEFT JOIN departments d ON a.department_id = d.id
             LEFT JOIN users u ON a.assigned_to = u.id
             WHERE a.id = :id",
            ['id' => (int) $id]
        );

        if (!$asset) { Router::error(404, 'Asset not found.'); return; }

        // Allocation history
        $allocHistory = Database::fetchAll(
            "SELECT al.*, u.name as holder_name, ab.name as allocator_name
             FROM allocations al
             JOIN users u ON al.allocated_to = u.id
             JOIN users ab ON al.allocated_by = ab.id
             WHERE al.asset_id = :id
             ORDER BY al.allocation_date DESC",
            ['id' => (int) $id]
        );

        // Maintenance history
        $maintHistory = Database::fetchAll(
            "SELECT m.*, u.name as requester_name
             FROM maintenance_requests m
             JOIN users u ON m.requested_by = u.id
             WHERE m.asset_id = :id
             ORDER BY m.created_at DESC",
            ['id' => (int) $id]
        );

        $this->view('assets/detail', [
            'asset'        => $asset,
            'allocHistory' => $allocHistory,
            'maintHistory' => $maintHistory,
        ], $asset['asset_tag'] . ' — AssetFlow');
    }

    public function update(): void
    {
        Middleware::manager();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/assets'); return; }

        $id = (int) $this->input('id');
        Database::execute(
            "UPDATE assets SET name=:name, category_id=:cat, serial_number=:sn, acquisition_date=:ad, 
             acquisition_cost=:ac, `condition`=:cond, location=:loc, is_bookable=:book, department_id=:dept, notes=:notes
             WHERE id=:id",
            [
                'name' => $this->input('name'), 'cat' => $this->input('category_id') ?: null,
                'sn' => $this->input('serial_number'), 'ad' => $this->input('acquisition_date') ?: null,
                'ac' => (float) $this->input('acquisition_cost', 0), 'cond' => $this->input('condition', 'Good'),
                'loc' => $this->input('location'), 'book' => $this->input('is_bookable') ? 1 : 0,
                'dept' => $this->input('department_id') ?: null, 'notes' => $this->input('notes'), 'id' => $id
            ]
        );

        Helpers::logActivity(Auth::id(), 'asset_updated', 'asset', $id);
        $this->flash('success', 'Asset updated!');
        $this->redirect('/assets/detail/' . $id);
    }

    public function updateStatus(): void
    {
        Middleware::manager();
        if (!$this->validateCsrf()) { $this->json(['error' => 'Invalid'], 403); return; }

        $id = (int) $this->input('id');
        $status = $this->input('status');
        $allowed = ['Available','Allocated','Reserved','Under Maintenance','Lost','Retired','Disposed'];
        if (!in_array($status, $allowed)) { $this->flash('error', 'Invalid status.'); $this->redirect('/assets/detail/'.$id); return; }

        Database::execute("UPDATE assets SET status = :s WHERE id = :id", ['s' => $status, 'id' => $id]);
        Helpers::logActivity(Auth::id(), 'asset_status_changed', 'asset', $id, ['status' => $status]);
        $this->flash('success', "Asset status changed to {$status}.");
        $this->redirect('/assets/detail/' . $id);
    }

    public function apiSearch(): void
    {
        Middleware::auth();
        $q = $this->query('q', '');
        $assets = Database::fetchAll(
            "SELECT id, asset_tag, name, status, location FROM assets WHERE (asset_tag LIKE :q OR name LIKE :q OR serial_number LIKE :q) LIMIT 10",
            ['q' => "%{$q}%"]
        );
        $this->json(['assets' => $assets]);
    }

    public function apiAvailable(): void
    {
        Middleware::auth();
        $bookable = $this->query('bookable');
        $sql = "SELECT id, asset_tag, name, status, location FROM assets WHERE status = 'Available'";
        if ($bookable) $sql .= " AND is_bookable = 1";
        $sql .= " ORDER BY name";
        $this->json(['assets' => Database::fetchAll($sql)]);
    }
}
