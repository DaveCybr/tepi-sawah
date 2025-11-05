<?php

/**
 * Bootstrap / Initialization File
 * File ini harus di-include di setiap halaman
 */

// Pastikan file ini hanya diakses dari file PHP lain
if (!defined('INIT_LOADED')) {
    define('INIT_LOADED', true);
}

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Load database class
require_once __DIR__ . '/../database/database.php';

// Load helper functions
require_once __DIR__ . '/functions.php';

// Load authentication functions
require_once __DIR__ . '/auth.php';

// Set error handler untuk production
if (getenv('APP_ENV') === 'production') {
    set_error_handler(function ($errno, $errstr, $errfile, $errline) {
        error_log("Error [$errno]: $errstr in $errfile on line $errline");

        // Jangan tampilkan error ke user
        if (!(error_reporting() & $errno)) {
            return false;
        }

        // Tampilkan pesan generic ke user
        if ($errno === E_ERROR || $errno === E_USER_ERROR) {
            die("Terjadi kesalahan sistem. Silakan hubungi administrator.");
        }

        return true;
    });
}

// Exception handler
set_exception_handler(function ($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage());

    if (getenv('APP_ENV') === 'production') {
        die("Terjadi kesalahan sistem. Silakan hubungi administrator.");
    } else {
        die("Exception: " . $exception->getMessage() . "\n" . $exception->getTraceAsString());
    }
});

spl_autoload_register(function ($class) {
    $modelFile = ROOT_PATH . '/models/' . $class . '.php';
    if (file_exists($modelFile)) {
        require_once $modelFile;
    }
});

// Check untuk AJAX request
function isAjax()
{
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Define constants untuk path
define('ROOT_PATH', dirname(__DIR__));
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
