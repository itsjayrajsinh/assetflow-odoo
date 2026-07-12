<?php
/**
 * AssetFlow — Department Controller (Admin Only)
 */

class DepartmentController extends Controller
{
    public function index(): void
    {
        Middleware::admin();
        $departments = Database::fetchAll(
            "SELECT d.*, h.name as head_name, p.name as parent_name,
                    (SELECT COUNT(*) FROM users WHERE department_id = d.id AND status='Active') as employee_count
             FROM departments d
             LEFT JOIN users h ON d.head_id = h.id
             LEFT JOIN departments p ON d.parent_id = p.id
             ORDER BY d.name"
        );
        $allDepts = Database::fetchAll("SELECT id, name FROM departments WHERE status='Active' ORDER BY name");
        $employees = Database::fetchAll("SELECT id, name FROM users WHERE status='Active' ORDER BY name");
        
        $this->view('organization/departments', [
            'departments' => $departments,
            'allDepts'    => $allDepts,
            'employees'   => $employees,
            'activeTab'   => 'departments',
        ], 'Organization Setup — AssetFlow');
    }

    public function store(): void
    {
        Middleware::admin();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/organization/departments'); return; }

        $name = $this->input('name');
        $headId = $this->input('head_id') ?: null;
        $parentId = $this->input('parent_id') ?: null;
        $status = $this->input('status', 'Active');

        if (!$name) { $this->flash('error', 'Department name is required.'); $this->redirect('/organization/departments'); return; }

        Database::insert(
            "INSERT INTO departments (name, head_id, parent_id, status) VALUES (:name, :head_id, :parent_id, :status)",
            ['name' => $name, 'head_id' => $headId, 'parent_id' => $parentId, 'status' => $status]
        );

        Helpers::logActivity(Auth::id(), 'department_created', 'department', null, ['name' => $name]);
        $this->flash('success', "Department '{$name}' created successfully!");
        $this->redirect('/organization/departments');
    }

    public function update(): void
    {
        Middleware::admin();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/organization/departments'); return; }

        $id = (int) $this->input('id');
        $name = $this->input('name');
        $headId = $this->input('head_id') ?: null;
        $parentId = $this->input('parent_id') ?: null;
        $status = $this->input('status', 'Active');

        Database::execute(
            "UPDATE departments SET name=:name, head_id=:head_id, parent_id=:parent_id, status=:status WHERE id=:id",
            ['name' => $name, 'head_id' => $headId, 'parent_id' => $parentId, 'status' => $status, 'id' => $id]
        );

        Helpers::logActivity(Auth::id(), 'department_updated', 'department', $id, ['name' => $name]);
        $this->flash('success', "Department updated successfully!");
        $this->redirect('/organization/departments');
    }

    public function delete(): void
    {
        Middleware::admin();
        if (!$this->validateCsrf()) { $this->json(['error' => 'Invalid request'], 403); return; }

        $id = (int) $this->input('id');
        
        // Check for employees in the department
        $empCount = Database::fetchColumn("SELECT COUNT(*) FROM users WHERE department_id = :id", ['id' => $id]);
        if ($empCount > 0) {
            $this->flash('error', 'Cannot delete: department has employees assigned.');
            $this->redirect('/organization/departments');
            return;
        }

        Database::execute("DELETE FROM departments WHERE id = :id", ['id' => $id]);
        Helpers::logActivity(Auth::id(), 'department_deleted', 'department', $id);
        $this->flash('success', 'Department deleted.');
        $this->redirect('/organization/departments');
    }
}
