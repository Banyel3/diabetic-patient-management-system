<?php
/**
 * DiabetaCare - SQL Compatibility Helper
 * 
 * Provides SQL syntax helpers for cross-database compatibility
 * between MySQL and SQL Server.
 */

declare(strict_types=1);

namespace DiabetaCare\Core;

class SqlHelper
{
    /**
     * Get pagination clause for the current database
     * 
     * MySQL: LIMIT {pageSize} OFFSET {offset}
     * SQL Server: OFFSET {offset} ROWS FETCH NEXT {pageSize} ROWS ONLY
     * 
     * Note: SQL Server requires an ORDER BY clause when using OFFSET/FETCH
     */
    public static function paginate(int $pageSize, int $offset): string
    {
        if (Database::isSqlServer()) {
            return "OFFSET {$offset} ROWS FETCH NEXT {$pageSize} ROWS ONLY";
        }
        return "LIMIT {$pageSize} OFFSET {$offset}";
    }
    
    /**
     * Get current timestamp function
     * 
     * MySQL: NOW()
     * SQL Server: GETDATE()
     */
    public static function now(): string
    {
        return Database::isSqlServer() ? 'GETDATE()' : 'NOW()';
    }
    
    /**
     * Get current date function
     * 
     * MySQL: CURDATE()
     * SQL Server: CAST(GETDATE() AS DATE)
     */
    public static function currentDate(): string
    {
        return Database::isSqlServer() ? 'CAST(GETDATE() AS DATE)' : 'CURDATE()';
    }
    
    /**
     * Get date difference in days
     * 
     * MySQL: DATEDIFF(date1, date2)
     * SQL Server: DATEDIFF(DAY, date2, date1)
     * 
     * Note: Parameter order is reversed between MySQL and SQL Server
     */
    public static function dateDiffDays(string $date1, string $date2): string
    {
        if (Database::isSqlServer()) {
            return "DATEDIFF(DAY, {$date2}, {$date1})";
        }
        return "DATEDIFF({$date1}, {$date2})";
    }
    
    /**
     * Get date subtraction (date minus interval)
     * 
     * MySQL: DATE_SUB(date, INTERVAL n UNIT)
     * SQL Server: DATEADD(UNIT, -n, date)
     */
    public static function dateSub(string $date, int $amount, string $unit): string
    {
        $unit = strtoupper($unit);
        if (Database::isSqlServer()) {
            return "DATEADD({$unit}, -{$amount}, {$date})";
        }
        return "DATE_SUB({$date}, INTERVAL {$amount} {$unit})";
    }
    
    /**
     * Get date addition
     * 
     * MySQL: DATE_ADD(date, INTERVAL n UNIT)
     * SQL Server: DATEADD(UNIT, n, date)
     */
    public static function dateAdd(string $date, int $amount, string $unit): string
    {
        $unit = strtoupper($unit);
        if (Database::isSqlServer()) {
            return "DATEADD({$unit}, {$amount}, {$date})";
        }
        return "DATE_ADD({$date}, INTERVAL {$amount} {$unit})";
    }
    
    /**
     * Get date formatting
     * 
     * MySQL: DATE_FORMAT(date, format)
     * SQL Server: FORMAT(date, format)
     * 
     * Note: Format strings differ between databases
     */
    public static function dateFormat(string $date, string $mysqlFormat): string
    {
        if (Database::isSqlServer()) {
            // Convert MySQL format to SQL Server format
            $sqlServerFormat = str_replace(
                ['%Y', '%m', '%d', '%H', '%i', '%s', '%b'],
                ['yyyy', 'MM', 'dd', 'HH', 'mm', 'ss', 'MMM'],
                $mysqlFormat
            );
            return "FORMAT({$date}, '{$sqlServerFormat}')";
        }
        return "DATE_FORMAT({$date}, '{$mysqlFormat}')";
    }
    
    /**
     * Get IFNULL/ISNULL function
     * 
     * MySQL: IFNULL(expr, value)
     * SQL Server: ISNULL(expr, value)
     */
    public static function ifNull(string $expr, string $value): string
    {
        if (Database::isSqlServer()) {
            return "ISNULL({$expr}, {$value})";
        }
        return "IFNULL({$expr}, {$value})";
    }
    
    /**
     * Get string concatenation
     * 
     * MySQL: CONCAT(str1, str2, ...)
     * SQL Server: str1 + str2 + ... or CONCAT(str1, str2, ...)
     */
    public static function concat(array $parts): string
    {
        if (Database::isSqlServer()) {
            return implode(' + ', $parts);
        }
        return 'CONCAT(' . implode(', ', $parts) . ')';
    }
    
    /**
     * Get cast to date
     * 
     * MySQL: DATE(datetime)
     * SQL Server: CAST(datetime AS DATE)
     */
    public static function toDate(string $datetime): string
    {
        if (Database::isSqlServer()) {
            return "CAST({$datetime} AS DATE)";
        }
        return "DATE({$datetime})";
    }
    
    /**
     * Get LIMIT 1 for top record
     * 
     * MySQL: LIMIT 1
     * SQL Server: TOP 1 (goes after SELECT)
     */
    public static function limit1AfterSelect(): string
    {
        return Database::isSqlServer() ? 'TOP 1' : '';
    }
    
    public static function limit1AtEnd(): string
    {
        return Database::isSqlServer() ? '' : 'LIMIT 1';
    }
    
    /**
     * Build a SELECT query with optional TOP/LIMIT
     */
    public static function selectTop(string $columns, int $limit = 0): string
    {
        if ($limit > 0 && Database::isSqlServer()) {
            return "SELECT TOP {$limit} {$columns}";
        }
        return "SELECT {$columns}";
    }
    
    /**
     * Get day of week (0=Monday in MySQL, different in SQL Server)
     * 
     * MySQL: WEEKDAY(date) returns 0-6 (Mon-Sun)
     * SQL Server: DATEPART(WEEKDAY, date) returns 1-7 (based on SET DATEFIRST)
     */
    public static function weekday(string $date): string
    {
        if (Database::isSqlServer()) {
            // Adjust for Monday = 0
            return "(DATEPART(WEEKDAY, {$date}) + @@DATEFIRST - 2) % 7";
        }
        return "WEEKDAY({$date})";
    }
    
    /**
     * Get AUTO_INCREMENT / IDENTITY for last inserted ID
     * Both support PDO::lastInsertId(), but query syntax differs
     */
    public static function lastInsertIdQuery(): string
    {
        if (Database::isSqlServer()) {
            return "SELECT SCOPE_IDENTITY()";
        }
        return "SELECT LAST_INSERT_ID()";
    }
    
    /**
     * Boolean value representation
     * 
     * MySQL: TRUE/FALSE or 1/0
     * SQL Server: 1/0 (BIT)
     */
    public static function boolVal(bool $value): string
    {
        return $value ? '1' : '0';
    }
}
