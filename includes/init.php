<?php

/**
 * Bootstrap / Initialization File
 * File ini harus di-include di setiap halaman
 * Version: 2.0.0
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

// Load helper classes
require_once __DIR__ . '/../helpers/FileUploader.php';
require_once __DIR__ . '/../helpers/Validator.php';

// Set error handler untuk production
if (APP_ENV === 'production') {
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
    error_log("Uncaught Exception: " . $exception->getMessage() . "\n" . $exception->getTraceAsString());

    if (APP_ENV === 'production') {
        http_response_code(500);
        die("Terjadi kesalahan sistem. Silakan hubungi administrator.");
    } else {
        http_response_code(500);
        echo "<pre style='background:#f8d7da;color:#721c24;padding:20px;border:2px solid #f5c6cb;border-radius:8px;'>";
        echo "<strong>⚠️ Exception:</strong> " . htmlspecialchars($exception->getMessage()) . "\n\n";
        echo "<strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "\n";
        echo "<strong>Line:</strong> " . $exception->getLine() . "\n\n";
        echo "<strong>Stack Trace:</strong>\n" . htmlspecialchars($exception->getTraceAsString());
        echo "</pre>";
        die();
    }
});

// Autoload models
spl_autoload_register(function ($class) {
    $modelFile = MODELS_PATH . '/' . $class . '.php';
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

// Middleware untuk rate limiting (simple implementation)
function checkRateLimit($identifier, $maxAttempts = 60, $period = 60)
{
    $key = 'rate_limit_' . $identifier;

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 0,
            'start_time' => time()
        ];
    }

    $rateData = $_SESSION[$key];
    $currentTime = time();

    // Reset jika sudah lewat periode
    if ($currentTime - $rateData['start_time'] > $period) {
        $_SESSION[$key] = [
            'count' => 1,
            'start_time' => $currentTime
        ];
        return true;
    }

    // Check limit
    if ($rateData['count'] >= $maxAttempts) {
        $timeLeft = $period - ($currentTime - $rateData['start_time']);
        throw new Exception("Terlalu banyak request. Coba lagi dalam {$timeLeft} detik.");
    }

    // Increment counter
    $_SESSION[$key]['count']++;
    return true;
}

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

if (APP_ENV === 'production') {
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Check session health
function checkSessionHealth()
{
    // Validasi IP address consistency (optional, bisa dinonaktifkan jika ada proxy)
    if (isset($_SESSION['ip_address'])) {
        $currentIP = $_SERVER['REMOTE_ADDR'] ?? '';
        if ($_SESSION['ip_address'] !== $currentIP) {
            // IP berubah - possible session hijacking
            // Untuk production, bisa log dan logout user
            if (APP_ENV === 'production') {
                error_log("Session IP mismatch for user " . ($_SESSION[SESSION_USER_ID] ?? 'unknown'));
            }
        }
    }

    // Validasi user agent consistency
    if (isset($_SESSION['user_agent'])) {
        $currentUA = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if ($_SESSION['user_agent'] !== $currentUA) {
            // User agent berubah - possible session hijacking
            if (APP_ENV === 'production') {
                error_log("Session UA mismatch for user " . ($_SESSION[SESSION_USER_ID] ?? 'unknown'));
            }
        }
    }
}

// Auto-check session health jika user sudah login
if (isset($_SESSION[SESSION_USER_ID])) {
    checkSessionHealth();
}
