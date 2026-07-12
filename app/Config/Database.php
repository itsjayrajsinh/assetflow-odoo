<?php
/**
 * AssetFlow — Database Connection (PDO Singleton)
 */

class Database
{
    private static ?PDO $instance = null;
    private static string  $host= 'localhost';
    private static string $dbname = 'assetflow';
    private static string $username = 'root';
    private static string $password = '';
    private static string $charset = 'utf8mb4';

    /**
     * Get the singleton PDO instance
     */
    public static function getInstance(): PDO
    {
    if (self::$instance === null) {
            $dsn = "mysql:host=" . self::$host . ";dbname=" . self::$dbname . ";charset=" . self::$charset;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            try {
                self::$instance = new PDO($dsn, self::$username, self::$password, $options);
            } catch (PDOException $e) {
                // In production, log this error instead of displaying
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    /**
     * Execute a query with parameters and return the statement
     */
    public static function query(string $sql, array $params = []): PDOStatement {
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        return $stmt; }

    /**
     * Fetch all results
     */
    public static function fetchAll(string $sql, array $params = []): array  {
        return self::query($sql, $params)->fetchAll();  }

    /**
     * Fetch a single row
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }
    public static function fetchColumn(string $sql, array $params = [])
    {
        return self::query($sql, $params)->fetchColumn();
    }
    public static function insert(string $sql, array $params = []): int
    {
        self::query($sql, $params);
        return (int) self::getInstance()->lastInsertId();
    }
   
    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();}

    public static function beginTransaction(): void
    {
        self::getInstance()->beginTransaction(); }

    /**
     * Commit a transaction
     */
    public static function commit(): void
    {
        self::getInstance()->commit();  }

    /**
     * Rollback a transaction
     */
    public static function rollback(): void  {
        self::getInstance()->rollBack();    }}
