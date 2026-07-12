<?php
/**
 * AssetFlow — Allocation & Transfer Controller
 */

class AllocationController extends Controller
{
    public function index(): void
    {
        Middleware::leadership();
        $allocations = Database::fetchAll(
            "SELECT al.*, a.asset_tag, a.name as asset_name, u.name as holder_name, ab.name as allocator_name, d.name as dept_name
             FROM allocations al
             JOIN assets a ON al.asset_id = a.id
             JOIN users u ON al.allocated_to = u.id
             JOIN users ab ON al.allocated_by = ab.id
             LEFT JOIN departments d ON al.department_id = d.id
             ORDER BY al.created_at DESC LIMIT 100"
        );

        // Auto-flag overdue
        Database::execute("UPDATE allocations SET status='Overdue' WHERE status='Active' AND expected_return_date < CURDATE()");

        $this->view('allocation/index', ['allocations' => $allocations], 'Allocation & Transfer — AssetFlow');
    }

    public function showAllocate(): void
    {
        Middleware::leadership();
        $assetId = $this->query('asset_id');
        $asset = $assetId ? Database::fetch("SELECT * FROM assets WHERE id = :id", ['id' => $assetId]) : null;
        $availableAssets = Database::fetchAll("SELECT id, asset_tag, name FROM assets WHERE status='Available' ORDER BY asset_tag");
        $employees = Database::fetchAll("SELECT u.id, u.name, d.name as dept_name FROM users u LEFT JOIN departments d ON u.department_id=d.id WHERE u.status='Active' ORDER BY u.name");
        $departments = Database::fetchAll("SELECT id, name FROM departments WHERE status='Active' ORDER BY name");

        $this->view('allocation/allocate', [
            'asset' => $asset, 'availableAssets' => $availableAssets, 'employees' => $employees, 'departments' => $departments
        ], 'Allocate Asset — AssetFlow');
    }

    public function allocate(): void
    {
        Middleware::leadership();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/allocation/allocate'); return; }

        $assetId = (int) $this->input('asset_id');
        $userId = (int) $this->input('allocated_to');
        $deptId = $this->input('department_id') ?: null;
        $expectedReturn = $this->input('expected_return_date') ?: null;

        // Check asset status — prevent double allocation
        $asset = Database::fetch("SELECT * FROM assets WHERE id = :id", ['id' => $assetId]);
        if (!$asset) { $this->flash('error', 'Asset not found.'); $this->redirect('/allocation/allocate'); return; }

        if ($asset['status'] !== 'Available') {
            $holder = Database::fetch(
                "SELECT u.name FROM allocations al JOIN users u ON al.allocated_to = u.id WHERE al.asset_id = :id AND al.status='Active' LIMIT 1",
                ['id' => $assetId]
            );
            $holderName = $holder ? $holder['name'] : 'another user';
            $this->flash('error', "Asset {$asset['asset_tag']} is currently {$asset['status']} (held by {$holderName}). Use Transfer Request instead.");
            $this->redirect('/allocation/allocate');
            return;
        }

        // Create allocation
        Database::beginTransaction();
        try {
            $allocId = Database::insert(
                "INSERT INTO allocations (asset_id, allocated_to, allocated_by, department_id, allocation_date, expected_return_date, status)
                 VALUES (:asset, :to, :by, :dept, NOW(), :ret, 'Active')",
                ['asset' => $assetId, 'to' => $userId, 'by' => Auth::id(), 'dept' => $deptId, 'ret' => $expectedReturn]
            );

            Database::execute("UPDATE assets SET status='Allocated', assigned_to=:to, department_id=COALESCE(:dept, department_id) WHERE id=:id",
                ['to' => $userId, 'dept' => $deptId, 'id' => $assetId]);

            Database::commit();

            $user = Database::fetch("SELECT name FROM users WHERE id=:id", ['id' => $userId]);
            Helpers::notify($userId, 'asset_assigned', 'Asset Assigned', "{$asset['name']} ({$asset['asset_tag']}) has been allocated to you.", "/assets/detail/{$assetId}");
            Helpers::logActivity(Auth::id(), 'asset_allocated', 'allocation', $allocId, ['asset' => $asset['asset_tag'], 'to' => $user['name']]);

            $this->flash('success', "Asset {$asset['asset_tag']} allocated to {$user['name']}!");
            $this->redirect('/allocation');
        } catch (Exception $e) {
            Database::rollback();
            $this->flash('error', 'Allocation failed: ' . $e->getMessage());
            $this->redirect('/allocation/allocate');
        }
    }

    public function returnAsset(): void
    {
        Middleware::leadership();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/allocation'); return; }

        $allocId = (int) $this->input('allocation_id');
        $condition = $this->input('return_condition', 'Good');
        $notes = $this->input('return_notes');

        $alloc = Database::fetch("SELECT * FROM allocations WHERE id = :id AND status IN ('Active','Overdue')", ['id' => $allocId]);
        if (!$alloc) { $this->flash('error', 'Allocation not found.'); $this->redirect('/allocation'); return; }

        Database::beginTransaction();
        try {
            Database::execute("UPDATE allocations SET status='Returned', actual_return_date=CURDATE(), return_condition=:cond, return_notes=:notes WHERE id=:id",
                ['cond' => $condition, 'notes' => $notes, 'id' => $allocId]);
            Database::execute("UPDATE assets SET status='Available', assigned_to=NULL WHERE id=:id", ['id' => $alloc['asset_id']]);
            Database::commit();

            Helpers::logActivity(Auth::id(), 'asset_returned', 'allocation', $allocId);
            $this->flash('success', 'Asset returned successfully!');
        } catch (Exception $e) {
            Database::rollback();
            $this->flash('error', 'Return failed.');
        }
        $this->redirect('/allocation');
    }

    public function transfers(): void
    {
        Middleware::leadership();
        $transfers = Database::fetchAll(
            "SELECT t.*, a.asset_tag, a.name as asset_name, fu.name as from_name, tu.name as to_name, ru.name as requester_name, au.name as approver_name
             FROM transfer_requests t
             JOIN assets a ON t.asset_id = a.id
             JOIN users fu ON t.from_user_id = fu.id
             JOIN users tu ON t.to_user_id = tu.id
             JOIN users ru ON t.requested_by = ru.id
             LEFT JOIN users au ON t.approved_by = au.id
             ORDER BY t.created_at DESC LIMIT 50"
        );
        $this->view('allocation/transfers', ['transfers' => $transfers], 'Transfer Requests — AssetFlow');
    }

    public function requestTransfer(): void
    {
        Middleware::auth();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/allocation/transfers'); return; }

        $assetId = (int) $this->input('asset_id');
        $toUserId = (int) $this->input('to_user_id');
        $reason = $this->input('reason');

        $alloc = Database::fetch("SELECT allocated_to FROM allocations WHERE asset_id=:id AND status='Active' LIMIT 1", ['id' => $assetId]);
        $fromUserId = $alloc ? $alloc['allocated_to'] : Auth::id();

        Database::insert(
            "INSERT INTO transfer_requests (asset_id, from_user_id, to_user_id, requested_by, reason, status) VALUES (:asset, :from, :to, :by, :reason, 'Requested')",
            ['asset' => $assetId, 'from' => $fromUserId, 'to' => $toUserId, 'by' => Auth::id(), 'reason' => $reason]
        );

        Helpers::logActivity(Auth::id(), 'transfer_requested', 'transfer', null, ['asset_id' => $assetId]);
        $this->flash('success', 'Transfer request submitted!');
        $this->redirect('/allocation/transfers');
    }

    public function approveTransfer(): void
    {
        Middleware::leadership();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/allocation/transfers'); return; }

        $id = (int) $this->input('id');
        $transfer = Database::fetch("SELECT * FROM transfer_requests WHERE id=:id AND status='Requested'", ['id' => $id]);
        if (!$transfer) { $this->flash('error', 'Transfer not found.'); $this->redirect('/allocation/transfers'); return; }

        Database::beginTransaction();
        try {
            Database::execute("UPDATE transfer_requests SET status='Completed', approved_by=:by WHERE id=:id", ['by' => Auth::id(), 'id' => $id]);
            Database::execute("UPDATE allocations SET status='Transferred' WHERE asset_id=:aid AND status='Active'", ['aid' => $transfer['asset_id']]);
            
            $allocId = Database::insert(
                "INSERT INTO allocations (asset_id, allocated_to, allocated_by, allocation_date, status) VALUES (:asset, :to, :by, NOW(), 'Active')",
                ['asset' => $transfer['asset_id'], 'to' => $transfer['to_user_id'], 'by' => Auth::id()]
            );
            Database::execute("UPDATE assets SET assigned_to=:to WHERE id=:id", ['to' => $transfer['to_user_id'], 'id' => $transfer['asset_id']]);
            
            Database::commit();
            Helpers::notify($transfer['to_user_id'], 'transfer_approved', 'Transfer Approved', 'An asset has been transferred to you.', '/allocation');
            Helpers::logActivity(Auth::id(), 'transfer_approved', 'transfer', $id);
            $this->flash('success', 'Transfer approved and asset re-allocated!');
        } catch (Exception $e) {
            Database::rollback();
            $this->flash('error', 'Approval failed.');
        }
        $this->redirect('/allocation/transfers');
    }

    public function rejectTransfer(): void
    {
        Middleware::leadership();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/allocation/transfers'); return; }
        $id = (int) $this->input('id');
        Database::execute("UPDATE transfer_requests SET status='Rejected', approved_by=:by WHERE id=:id", ['by' => Auth::id(), 'id' => $id]);
        Helpers::logActivity(Auth::id(), 'transfer_rejected', 'transfer', $id);
        $this->flash('success', 'Transfer rejected.');
        $this->redirect('/allocation/transfers');
    }
}
