<?php
/**
 * AssetFlow — Employee Controller (Admin Only for role mgmt)
 */

class EmployeeController extends Controller
{
    public function index(): void
    {
        Middleware::admin();
        $employees = Database::fetchAll(
            "SELECT u.*, d.name as department_name,
                    (SELECT COUNT(*) FROM allocations WHERE allocated_to = u.id AND status='Active') as active_assets
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.id
             ORDER BY u.name"
        );
        $departments = Database::fetchAll("SELECT id, name FROM departments WHERE status='Active' ORDER BY name");

        $this->view('organization/employees', [
            'employees'   => $employees,
            'departments' => $departments,
            'activeTab'   => 'employees',
        ], 'Employee Directory — AssetFlow');
    }

    public function update(): void
    {
        Middleware::admin();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/organization/employees'); return; }

        $id = (int) $this->input('id');
        $name = $this->input('name');
        $email = $this->input('email');
        $departmentId = $this->input('department_id') ?: null;
        $status = $this->input('status', 'Active');

        Database::execute(
            "UPDATE users SET name=:name, email=:email, department_id=:dept, status=:status WHERE id=:id",
            ['name' => $name, 'email' => $email, 'dept' => $departmentId, 'status' => $status, 'id' => $id]
        );

        Helpers::logActivity(Auth::id(), 'employee_updated', 'user', $id, ['name' => $name]);
        $this->flash('success', 'Employee updated!');
        $this->redirect('/organization/employees');
    }

    /**
     * Promote/change employee role — this is the ONLY place roles are assigned
     */
    public function updateRole(): void
    {
        Middleware::admin();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/organization/employees'); return; }

        $id = (int) $this->input('id');
        $newRole = $this->input('role');

        $allowedRoles = ['Employee', 'Department Head', 'Asset Manager', 'Admin'];
        if (!in_array($newRole, $allowedRoles)) {
            $this->flash('error', 'Invalid role.');
            $this->redirect('/organization/employees');
            return;
        }

        // Prevent self-demotion
        if ($id === Auth::id() && $newRole !== 'Admin') {
            $this->flash('error', 'You cannot change your own role.');
            $this->redirect('/organization/employees');
            return;
        }

        $user = Database::fetch("SELECT name, role FROM users WHERE id = :id", ['id' => $id]);
        if (!$user) {
            $this->flash('error', 'User not found.');
            $this->redirect('/organization/employees');
            return;
        }

        Database::execute("UPDATE users SET role = :role WHERE id = :id", ['role' => $newRole, 'id' => $id]);

        Helpers::logActivity(Auth::id(), 'role_changed', 'user', $id, [
            'name' => $user['name'], 'old_role' => $user['role'], 'new_role' => $newRole
        ]);
        Helpers::notify($id, 'role_changed', 'Role Updated', "Your role has been changed to {$newRole}.", '/dashboard');

        $this->flash('success', "{$user['name']} promoted to {$newRole}!");
        $this->redirect('/organization/employees');
    }

    public function apiList(): void
    {
        Middleware::auth();
        $dept = $this->query('department_id');
        $search = $this->query('search');

        $sql = "SELECT u.id, u.name, u.email, u.role, d.name as department_name 
                FROM users u LEFT JOIN departments d ON u.department_id = d.id 
                WHERE u.status = 'Active'";
        $params = [];

        if ($dept) { $sql .= " AND u.department_id = :dept"; $params['dept'] = $dept; }
        if ($search) { $sql .= " AND (u.name LIKE :s OR u.email LIKE :s)"; $params['s'] = "%{$search}%"; }

        $sql .= " ORDER BY u.name LIMIT 50";
        $this->json(['employees' => Database::fetchAll($sql, $params)]);
    }
}
