<?php
/**
 * AssetFlow — Category Controller (Admin Only)
 */

class CategoryController extends Controller
{
    public function index(): void
    {
        Middleware::admin();
        $categories = Database::fetchAll(
            "SELECT c.*, (SELECT COUNT(*) FROM assets WHERE category_id = c.id) as asset_count
             FROM asset_categories c ORDER BY c.name"
        );

        $this->view('organization/categories', [
            'categories' => $categories,
            'activeTab'  => 'categories',
        ], 'Asset Categories — AssetFlow');
    }

    public function store(): void
    {
        Middleware::admin();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/organization/categories'); return; }

        $name = $this->input('name');
        $description = $this->input('description');
        $customFields = $this->input('custom_fields');

        if (!$name) { $this->flash('error', 'Category name is required.'); $this->redirect('/organization/categories'); return; }

        Database::insert(
            "INSERT INTO asset_categories (name, description, custom_fields) VALUES (:name, :desc, :fields)",
            ['name' => $name, 'desc' => $description, 'fields' => $customFields ?: null]
        );

        Helpers::logActivity(Auth::id(), 'category_created', 'category', null, ['name' => $name]);
        $this->flash('success', "Category '{$name}' created!");
        $this->redirect('/organization/categories');
    }

    public function update(): void
    {
        Middleware::admin();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/organization/categories'); return; }

        $id = (int) $this->input('id');
        $name = $this->input('name');
        $description = $this->input('description');
        $customFields = $this->input('custom_fields');
        $status = $this->input('status', 'Active');

        Database::execute(
            "UPDATE asset_categories SET name=:name, description=:desc, custom_fields=:fields, status=:status WHERE id=:id",
            ['name' => $name, 'desc' => $description, 'fields' => $customFields ?: null, 'status' => $status, 'id' => $id]
        );

        Helpers::logActivity(Auth::id(), 'category_updated', 'category', $id);
        $this->flash('success', 'Category updated!');
        $this->redirect('/organization/categories');
    }

    public function delete(): void
    {
        Middleware::admin();
        if (!$this->validateCsrf()) { $this->flash('error', 'Invalid request.'); $this->redirect('/organization/categories'); return; }

        $id = (int) $this->input('id');
        $assetCount = Database::fetchColumn("SELECT COUNT(*) FROM assets WHERE category_id = :id", ['id' => $id]);
        if ($assetCount > 0) {
            $this->flash('error', 'Cannot delete: category has assets assigned.');
            $this->redirect('/organization/categories');
            return;
        }

        Database::execute("DELETE FROM asset_categories WHERE id = :id", ['id' => $id]);
        Helpers::logActivity(Auth::id(), 'category_deleted', 'category', $id);
        $this->flash('success', 'Category deleted.');
        $this->redirect('/organization/categories');
    }
}
