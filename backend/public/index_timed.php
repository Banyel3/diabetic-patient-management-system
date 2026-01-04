<?php
// Add timing to the actual index.php to see where slowdown occurs
$_SERVER['SCRIPT_START'] = microtime(true);

chdir(__DIR__);
require_once __DIR__ . '/../src/Autoloader.php';
DiabetaCare\Autoloader::register();

// Load environment variables
require_once __DIR__ . '/../config/env.php';

use DiabetaCare\Core\Router;
use DiabetaCare\Core\Database;

$timings = [];
$timings['start'] = microtime(true);

// Initialize database
Database::initialize();
$timings['db_init'] = microtime(true);

// Build Router and routes
$router = new Router();
require_once __DIR__ . '/../routes/api.php';
$timings['routes'] = microtime(true);

// Handle the request
$router->dispatch();
$timings['dispatch'] = microtime(true);

// Log timings
$log = fopen(__DIR__ . '/timing.log', 'a');
fwrite($log, date('Y-m-d H:i:s') . " - ");
fwrite($log, "DB init: " . round(($timings['db_init'] - $timings['start']) * 1000) . "ms, ");
fwrite($log, "Routes: " . round(($timings['routes'] - $timings['db_init']) * 1000) . "ms, ");
fwrite($log, "Dispatch: " . round(($timings['dispatch'] - $timings['routes']) * 1000) . "ms, ");
fwrite($log, "Total: " . round(($timings['dispatch'] - $timings['start']) * 1000) . "ms\n");
fclose($log);
