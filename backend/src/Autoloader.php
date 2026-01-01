<?php
/**
 * DiabetaCare - PSR-4 Compatible Autoloader
 */

declare(strict_types=1);

namespace DiabetaCare;

class Autoloader
{
    private static string $baseDir;
    private static string $namespace = 'DiabetaCare\\';

    public static function register(): void
    {
        self::$baseDir = dirname(__DIR__) . '/src/';
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
        // Check if class uses our namespace
        if (!str_starts_with($class, self::$namespace)) {
            return;
        }

        // Remove namespace prefix and convert to path
        $relativeClass = substr($class, strlen(self::$namespace));
        $file = self::$baseDir . str_replace('\\', '/', $relativeClass) . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
}
