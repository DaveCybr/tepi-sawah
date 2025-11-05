<?php

/**
 * Konfigurasi Utama Aplikasi Resto
 * File ini berisi semua konfigurasi sistem
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
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/tepi-sawah');
define('TIMEZONE', 'Asia/Jakarta');

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
define('UPLOAD_PATH', __DIR__ . '/../assets/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'webp']);

// ======================
// PAGINATION SETTINGS
// ======================
define('ITEMS_PER_PAGE', 20);

// ======================
// ERROR REPORTING
// ======================
if (getenv('APP_ENV') === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Set timezone
date_default_timezone_set(TIMEZONE);

// Start session dengan security settings
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set 1 jika pakai HTTPS
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}
