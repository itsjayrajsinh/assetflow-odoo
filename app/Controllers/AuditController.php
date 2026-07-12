<?php
/**
 * AssetFlow — Audit Controller
 */

class AuditController extends Controller
{
    public function index(): void
    {
        Middleware::manager();
        $cycles = Database::fetchAll(
            "SELECT ac.*, u.name as creator_name,
                    (SELECT COUNT(*) FROM audit_items WHERE audit_cycle_id = ac.id) as total_items,
                    (SELECT COUNT(*) FROM audit_items WHERE audit_cycle_id = ac.id AND status != 'Pending') as verified_items,
                    (SELECT COUNT(*) FROM audit_items WHERE audit_cycle_id = ac.id AND status IN ('Missing','Damaged')) as discrepancies
             FROM audit_cycles ac
             JOIN users u ON ac.created_by = u.id
             ORDER BY ac.created_at DESC"
        );
        $this->view('audit/index', ['cycles' => $cycles], 'Asset Audit — AssetFlow');
    }

    public function showCreate(): void
    {
        Middleware::manager();
        $departments = Database::fetchAll("SELECT id, name FROM departments WHERE status='Active' ORDER BY name");
        $employees = Database::fetchAll("SELECT id, name FROM users WHERE status='Active' ORDER BY name");
        $this->view('audit/create', ['departments' => $departments, 'employees' => $employees], 'Create Audit Cycle — AssetFlow');
    }

    public function create(): void
    {
        Middleware::manager();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid.'); $this->redirect('/audit/create'); return; }

        $name = $this->input('name');
        $scopeType = $this->input('scope_type');
        $scopeValue = $this->input('scope_value');
        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');
        $auditors = $_POST['auditors'] ?? [];

        if (!$name || !$scopeType || !$scopeValue || !$startDate || !$endDate) {
            $this->flash('error', 'All fields are required.');
            $this->redirect('/audit/create');
            return;
        }

        Database::beginTransaction();
        try {
            $cycleId = Database::insert(
                "INSERT INTO audit_cycles (name, scope_type, scope_value, start_date, end_date, status, created_by)
                 VALUES (:name, :st, :sv, :sd, :ed, 'Open', :by)",
                ['name' => $name, 'st' => $scopeType, 'sv' => $scopeValue, 'sd' => $startDate, 'ed' => $endDate, 'by' => Auth::id()]
            );

            // Assign auditors
            foreach ($auditors as $auditorId) {
                Database::insert("INSERT INTO audit_assignments (audit_cycle_id, auditor_id) VALUES (:cid, :aid)",
                    ['cid' => $cycleId, 'aid' => (int) $auditorId]);
                Helpers::notify((int)$auditorId, 'audit_assigned', 'Audit Assignment', "You've been assigned to audit cycle: {$name}", "/audit/verify/{$cycleId}");
            }

            // Populate audit items based on scope
            if ($scopeType === 'Department') {
                $assets = Database::fetchAll("SELECT id FROM assets WHERE department_id = :did AND status NOT IN ('Disposed')", ['did' => $scopeValue]);
            } else {
                $assets = Database::fetchAll("SELECT id FROM assets WHERE location LIKE :loc AND status NOT IN ('Disposed')", ['loc' => "%{$scopeValue}%"]);
            }

            foreach ($assets as $asset) {
                Database::insert("INSERT INTO audit_items (audit_cycle_id, asset_id, status) VALUES (:cid, :aid, 'Pending')",
                    ['cid' => $cycleId, 'aid' => $asset['id']]);
            }

            Database::commit();
            Helpers::logActivity(Auth::id(), 'audit_created', 'audit', $cycleId, ['name' => $name, 'items' => count($assets)]);
            $this->flash('success', "Audit cycle created with " . count($assets) . " assets to verify!");
            $this->redirect('/audit');
        } catch (Exception $e) {
            Database::rollback();
            $this->flash('error', 'Failed to create audit cycle.');
            $this->redirect('/audit/create');
        }
    }

    public function showVerify(string $id): void
    {
        Middleware::auth();
        $cycle = Database::fetch("SELECT * FROM audit_cycles WHERE id = :id", ['id' => (int) $id]);
        if (!$cycle) { Router::error(404, 'Audit cycle not found.'); return; }

        $items = Database::fetchAll(
            "SELECT ai.*, a.asset_tag, a.name as asset_name, a.location, a.status as asset_status, a.`condition`,
                    u.name as auditor_name
             FROM audit_items ai
             JOIN assets a ON ai.asset_id = a.id
             LEFT JOIN users u ON ai.auditor_id = u.id
             WHERE ai.audit_cycle_id = :cid
             ORDER BY a.asset_tag",
            ['cid' => (int) $id]
        );

        $this->view('audit/verify', ['cycle' => $cycle, 'items' => $items], 'Verify Audit — AssetFlow');
    }

    public function verify(): void
    {
        Middleware::auth();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid.'); $this->redirect('/audit'); return; }

        $itemId = (int) $this->input('item_id');
        $status = $this->input('status');
        $notes = $this->input('notes');

        $allowed = ['Verified', 'Missing', 'Damaged'];
        if (!in_array($status, $allowed)) { $this->flash('error', 'Invalid status.'); $this->redirect('/audit'); return; }

        Database::execute(
            "UPDATE audit_items SET status=:st, notes=:notes, auditor_id=:uid, verified_at=NOW() WHERE id=:id",
            ['st' => $status, 'notes' => $notes, 'uid' => Auth::id(), 'id' => $itemId]
        );

        $item = Database::fetch("SELECT audit_cycle_id FROM audit_items WHERE id=:id", ['id' => $itemId]);
        Helpers::logActivity(Auth::id(), 'audit_item_verified', 'audit_item', $itemId, ['status' => $status]);

        $this->flash('success', "Item marked as {$status}.");
        $this->redirect('/audit/verify/' . $item['audit_cycle_id']);
    }

    public function close(): void
    {
        Middleware::manager();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid.'); $this->redirect('/audit'); return; }

        $cycleId = (int) $this->input('id');

        // Update missing assets to Lost
        Database::execute(
            "UPDATE assets a JOIN audit_items ai ON a.id = ai.asset_id
             SET a.status = 'Lost'
             WHERE ai.audit_cycle_id = :cid AND ai.status = 'Missing'",
            ['cid' => $cycleId]
        );

        Database::execute("UPDATE audit_cycles SET status='Closed' WHERE id=:id", ['id' => $cycleId]);
        Helpers::logActivity(Auth::id(), 'audit_closed', 'audit', $cycleId);
        $this->flash('success', 'Audit cycle closed. Missing assets marked as Lost.');
        $this->redirect('/audit');
    }

    public function report(string $id): void
    {
        Middleware::manager();
        $cycle = Database::fetch("SELECT * FROM audit_cycles WHERE id = :id", ['id' => (int) $id]);
        if (!$cycle) { Router::error(404); return; }

        $items = Database::fetchAll(
            "SELECT ai.*, a.asset_tag, a.name as asset_name, a.location, u.name as auditor_name
             FROM audit_items ai JOIN assets a ON ai.asset_id = a.id LEFT JOIN users u ON ai.auditor_id = u.id
             WHERE ai.audit_cycle_id = :cid ORDER BY ai.status, a.asset_tag",
            ['cid' => (int) $id]
        );

        $summary = [
            'total' => count($items),
            'verified' => count(array_filter($items, fn($i) => $i['status'] === 'Verified')),
            'missing' => count(array_filter($items, fn($i) => $i['status'] === 'Missing')),
            'damaged' => count(array_filter($items, fn($i) => $i['status'] === 'Damaged')),
            'pending' => count(array_filter($items, fn($i) => $i['status'] === 'Pending')),
        ];

        $this->view('audit/verify', ['cycle' => $cycle, 'items' => $items, 'summary' => $summary], 'Audit Report — AssetFlow');
    }
}
