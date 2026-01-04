<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../src/Autoloader.php';
\DiabetaCare\Autoloader::register();

// Load environment
require_once __DIR__ . '/../config/env.php';

echo "Testing backend...\n";
echo "DB_DRIVER: " . getenv('DB_DRIVER') . "\n";
echo "DB_HOST: " . getenv('DB_HOST') . "\n";
echo "DB_NAME: " . getenv('DB_NAME') . "\n";

try {
    \DiabetaCare\Core\Database::initialize();
    echo "Database initialized!\n";
    
    // Try to get users
    $users = \DiabetaCare\Core\Database::query("SELECT TOP 1 * FROM users");
    print_r($users);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
