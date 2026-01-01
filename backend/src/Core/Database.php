<?php
/**
 * DiabetaCare - Database Connection Manager
 * 
 * Singleton PDO wrapper with prepared statement caching.
 * Uses MySQL with utf8mb4 encoding for full Unicode support.
 */

declare(strict_types=1);

namespace DiabetaCare\Core;

use PDO;
use PDOException;
use PDOStatement;

class Database
{
    private static ?PDO $connection = null;
    private static array $statementCache = [];

    /**
     * Initialize database connection
     */
    public static function initialize(): void
    {
        if (self::$connection !== null) {
            return;
        }

        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $dbName = getenv('DB_NAME') ?: 'diabetacare';
        $user = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            self::$connection = new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get PDO connection instance
     */
    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            self::initialize();
        }
        return self::$connection;
    }

    /**
     * Prepare and cache a statement
     */
    public static function prepare(string $sql): PDOStatement
    {
        $hash = md5($sql);
        
        if (!isset(self::$statementCache[$hash])) {
            self::$statementCache[$hash] = self::getConnection()->prepare($sql);
        }
        
        return self::$statementCache[$hash];
    }

    /**
     * Execute a query with parameters and return results
     */
    public static function query(string $sql, array $params = []): array
    {
        $stmt = self::prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a query and return a single row
     */
    public static function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = self::prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute a query and return single column value
     */
    public static function queryValue(string $sql, array $params = []): mixed
    {
        $stmt = self::prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    /**
     * Execute an insert/update/delete and return affected rows
     */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Get last inserted ID
     */
    public static function lastInsertId(): string
    {
        return self::getConnection()->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public static function beginTransaction(): bool
    {
        return self::getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public static function commit(): bool
    {
        return self::getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public static function rollback(): bool
    {
        return self::getConnection()->rollBack();
    }

    /**
     * Execute callback within transaction
     */
    public static function transaction(callable $callback): mixed
    {
        self::beginTransaction();
        try {
            $result = $callback();
            self::commit();
            return $result;
        } catch (\Throwable $e) {
            self::rollback();
            throw $e;
        }
    }
}
