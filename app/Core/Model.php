<?php
/**
 * AssetFlow — Base Model with CRUD helpers
 */

class Model
{
    protected string $table;
    protected string $primaryKey = 'id';

    /**
     * Find a record by primary key
     */
    public function find(int $id): ?array
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1",
            ['id' => $id]
        );
    }

    /**
     * Get all records
     */
    public function all(string $orderBy = 'id DESC'): array
    {
        return Database::fetchAll("SELECT * FROM {$this->table} ORDER BY {$orderBy}");
    }

    /**
     * Get records with a WHERE condition
     */
    public function where(array $conditions, string $orderBy = 'id DESC'): array
    {
        $clauses = [];
        $params = [];
        foreach ($conditions as $col => $val) {
            $clauses[] = "{$col} = :{$col}";
            $params[$col] = $val;
        }
        $where = implode(' AND ', $clauses);
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE {$where} ORDER BY {$orderBy}",
            $params
        );
    }

    /**
     * Get a single record matching conditions
     */
    public function findWhere(array $conditions): ?array
    {
        $clauses = [];
        $params = [];
        foreach ($conditions as $col => $val) {
            $clauses[] = "{$col} = :{$col}";
            $params[$col] = $val;
        }
        $where = implode(' AND ', $clauses);
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE {$where} LIMIT 1",
            $params
        );
    }

    /**
     * Insert a new record
     */
    public function create(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_map(fn($k) => ":{$k}", array_keys($data)));
        return Database::insert(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})",
            $data
        );
    }

    /**
     * Update a record by primary key
     */
    public function update(int $id, array $data): int
    {
        $setClauses = [];
        $params = ['pk_id' => $id];
        foreach ($data as $col => $val) {
            $setClauses[] = "{$col} = :set_{$col}";
            $params["set_{$col}"] = $val;
        }
        $setStr = implode(', ', $setClauses);
        return Database::execute(
            "UPDATE {$this->table} SET {$setStr} WHERE {$this->primaryKey} = :pk_id",
            $params
        );
    }

    /**
     * Delete a record by primary key
     */
    public function delete(int $id): int
    {
        return Database::execute(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id",
            ['id' => $id]
        );
    }

    /**
     * Count records with optional conditions
     */
    public function count(array $conditions = []): int
    {
        if (empty($conditions)) {
            return (int) Database::fetchColumn("SELECT COUNT(*) FROM {$this->table}");
        }
        $clauses = [];
        $params = [];
        foreach ($conditions as $col => $val) {
            $clauses[] = "{$col} = :{$col}";
            $params[$col] = $val;
        }
        $where = implode(' AND ', $clauses);
        return (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE {$where}",
            $params
        );
    }

    /**
     * Run a raw query
     */
    public function raw(string $sql, array $params = []): array
    {
        return Database::fetchAll($sql, $params);
    }

    /**
     * Run a raw query and fetch single
     */
    public function rawOne(string $sql, array $params = []): ?array
    {
        return Database::fetch($sql, $params);
    }
}
