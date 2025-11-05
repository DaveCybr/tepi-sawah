<?php

/**
 * Konfigurasi Utama Aplikasi Resto
 * File ini berisi semua konfigurasi sistem
 * Version: 2.0.0
 */

// ======================
// DATABASE CONFIGURATION
// ======================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tepi_sawah');

// ======================
// APPLICATION SETTINGS
// ======================
define('APP_NAME', 'RestoCashier');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'http://localhost/tepi-sawah');
define('TIMEZONE', 'Asia/Jakarta');

// ======================
// PATH CONSTANTS
// ======================
define('ROOT_PATH', dirname(__DIR__));
define('ASSETS_PATH', ROOT_PATH . '/assets');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('MODELS_PATH', ROOT_PATH . '/models');
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads/');

// ======================
// SECURITY SETTINGS
// ======================
define('HASH_ALGO', PASSWORD_BCRYPT);
define('HASH_COST', 12);
define('SESSION_LIFETIME', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

// ======================
// FILE UPLOAD SETTINGS
// ======================
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp']);
define('ALLOWED_MIME_TYPES', [
    'image/jpeg',
    'image/png',
    'image/webp'
]);

// ======================
// VALIDATION LIMITS
// ======================
define('MAX_MENU_NAME_LENGTH', 100);
define('MAX_BAHAN_NAME_LENGTH', 150);
define('MAX_CATATAN_LENGTH', 500);
define('MIN_PASSWORD_LENGTH', 6);
define('MAX_PASSWORD_LENGTH', 72); // bcrypt limit

// ======================
// PAGINATION SETTINGS
// ======================
define('ITEMS_PER_PAGE', 20);

// ======================
// SESSION KEYS (Standardized)
// ======================
define('SESSION_USER_ID', 'user_id');
define('SESSION_USER_NAME', 'nama');
define('SESSION_USER_EMAIL', 'email');
define('SESSION_USER_ROLE', 'role');
define('SESSION_LAST_ACTIVITY', 'last_activity');

// ======================
// ERROR REPORTING
// ======================
$appEnv = getenv('APP_ENV') ?: 'development';
define('APP_ENV', $appEnv);

if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', ROOT_PATH . '/logs/error.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// ======================
// TIMEZONE
// ======================
date_default_timezone_set(TIMEZONE);

// ======================
// SESSION CONFIGURATION
// ======================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set 1 jika pakai HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_start();
}

// ======================
// AUTO CREATE DIRECTORIES
// ======================
$requiredDirs = [
    UPLOAD_PATH,
    ROOT_PATH . '/assets/qrcode',
    ROOT_PATH . '/logs'
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}
