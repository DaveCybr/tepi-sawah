<?php

/**
 * Helper Functions
 * Fungsi-fungsi pembantu untuk keamanan dan utility
 * Version: 2.0.0
 */

/**
 * Sanitasi input untuk mencegah XSS
 */
function clean($data)
{
    if (is_array($data)) {
        return array_map('clean', $data);
    }

    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validasi email
 */
function validateEmail($email)
{
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    } else {
        // Regenerate token setiap 1 jam
        if (time() - $_SESSION['csrf_token_time'] > 3600) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_time'] = time();
        }
    }

    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token dengan timing-safe comparison
 */
function verifyCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    // Check token expiry (optional - 1 hour)
    if (isset($_SESSION['csrf_token_time'])) {
        if (time() - $_SESSION['csrf_token_time'] > 3600) {
            return false;
        }
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is logged in dengan standardized session key
 */
function isLoggedIn()
{
    return isset($_SESSION[SESSION_USER_ID]) && !empty($_SESSION[SESSION_USER_ID]);
}

/**
 * Check user role
 */
function hasRole($role)
{
    return isset($_SESSION[SESSION_USER_ROLE]) && $_SESSION[SESSION_USER_ROLE] === $role;
}

/**
 * Redirect function dengan exit
 */
function redirect($url)
{
    // Prevent header injection
    $url = str_replace(["\r", "\n"], '', $url);

    header("Location: " . $url);
    exit;
}

/**
 * Flash message dengan auto-expire
 */
function setFlash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => clean($message),
        'time' => time()
    ];
}

/**
 * Get and clear flash message
 */
function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];

        // Auto-expire flash message setelah 5 menit
        if (time() - $flash['time'] > 300) {
            unset($_SESSION['flash']);
            return null;
        }

        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format rupiah
 */
function rupiah($angka)
{
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

/**
 * Format tanggal Indonesia
 */
function tanggalIndo($tanggal, $includeTime = false)
{
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $timestamp = strtotime($tanggal);
    if ($timestamp === false) {
        return $tanggal; // Return original jika parsing gagal
    }

    $pecahkan = explode('-', date('Y-m-d', $timestamp));
    $result = $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];

    if ($includeTime) {
        $result .= ' ' . date('H:i', $timestamp);
    }

    return $result;
}

/**
 * Upload image - DEPRECATED, gunakan FileUploader::uploadImage()
 * Kept for backward compatibility
 */
function uploadImage($file, $allowedTypes = null)
{
    try {
        return FileUploader::uploadImage($file);
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Delete file - DEPRECATED, gunakan FileUploader::deleteFile()
 */
function deleteFile($filename)
{
    return FileUploader::deleteFile($filename);
}

/**
 * Log activity ke database
 */
function logActivity($userId, $action, $description = '')
{
    try {
        $db = Database::getInstance();

        // Pastikan tabel activity_log ada
        $sql = "INSERT INTO activity_log (user_id, action, description, ip_address, user_agent, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";

        $db->execute($sql, 'issss', [
            $userId,
            substr($action, 0, 50),
            substr($description, 0, 255),
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255)
        ]);
    } catch (Exception $e) {
        // Silent fail - jangan break aplikasi jika logging gagal
        error_log("Log Activity Error: " . $e->getMessage());
    }
}

/**
 * Generate random string
 */
function generateRandomString($length = 10)
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Check session timeout
 */
function checkSessionTimeout()
{
    if (isset($_SESSION[SESSION_LAST_ACTIVITY])) {
        $elapsed = time() - $_SESSION[SESSION_LAST_ACTIVITY];
        if ($elapsed > SESSION_LIFETIME) {
            return false;
        }
    }
    $_SESSION[SESSION_LAST_ACTIVITY] = time();
    return true;
}

/**
 * Rate limiting untuk login dengan improved algorithm
 */
function checkLoginAttempts($identifier)
{
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    $attempts = &$_SESSION['login_attempts'];

    // Bersihkan attempts yang sudah expired
    $currentTime = time();
    foreach ($attempts as $key => $data) {
        if ($currentTime - $data['time'] > LOCKOUT_TIME) {
            unset($attempts[$key]);
        }
    }

    // Hash identifier untuk privacy
    $key = hash('sha256', $identifier);

    // Check attempts
    if (isset($attempts[$key])) {
        if ($attempts[$key]['count'] >= MAX_LOGIN_ATTEMPTS) {
            $timeLeft = LOCKOUT_TIME - ($currentTime - $attempts[$key]['time']);
            if ($timeLeft > 0) {
                throw new Exception('Terlalu banyak percobaan login. Coba lagi dalam ' . ceil($timeLeft / 60) . ' menit.');
            } else {
                unset($attempts[$key]);
            }
        }
    }

    return true;
}

/**
 * Record login attempt
 */
function recordLoginAttempt($identifier, $success = false)
{
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    $key = hash('sha256', $identifier);

    if ($success) {
        // Reset attempts jika berhasil
        unset($_SESSION['login_attempts'][$key]);
    } else {
        // Tambah attempt jika gagal
        if (!isset($_SESSION['login_attempts'][$key])) {
            $_SESSION['login_attempts'][$key] = [
                'count' => 0,
                'time' => time()
            ];
        }
        $_SESSION['login_attempts'][$key]['count']++;
        $_SESSION['login_attempts'][$key]['time'] = time();
    }
}

/**
 * JSON response helper dengan proper HTTP status codes
 */
function jsonResponse($success, $message, $data = null, $httpCode = null)
{
    // Set HTTP status code
    if ($httpCode !== null) {
        http_response_code($httpCode);
    } elseif (!$success) {
        http_response_code(400);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Pagination helper dengan improved calculation
 */
function paginate($totalRows, $currentPage = 1, $perPage = ITEMS_PER_PAGE)
{
    $totalRows = max(0, (int)$totalRows);
    $perPage = max(1, (int)$perPage);
    $totalPages = ($totalRows > 0) ? ceil($totalRows / $perPage) : 1;
    $currentPage = max(1, min((int)$currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total_rows' => $totalRows,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'prev_page' => ($currentPage > 1) ? $currentPage - 1 : null,
        'next_page' => ($currentPage < $totalPages) ? $currentPage + 1 : null
    ];
}

/**
 * Format file size
 */
function formatFileSize($bytes)
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));

    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Generate unique code (untuk meja, pesanan, dll)
 */
function generateUniqueCode($prefix = '', $length = 8)
{
    $code = $prefix . strtoupper(substr(uniqid(), -$length));
    return $code;
}

/**
 * Calculate time ago (untuk display)
 */
function timeAgo($datetime)
{
    $timestamp = strtotime($datetime);
    if ($timestamp === false) {
        return $datetime;
    }

    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Baru saja';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' menit lalu';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' jam lalu';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' hari lalu';
    } else {
        return date('d/m/Y', $timestamp);
    }
}

/**
 * Truncate text with ellipsis
 */
function truncate($text, $length = 100, $suffix = '...')
{
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . $suffix;
}

/**
 * Debug helper (hanya untuk development)
 */
function dd(...$vars)
{
    if (APP_ENV !== 'production') {
        echo '<pre style="background:#1a1a1a;color:#00ff00;padding:20px;border-radius:8px;">';
        foreach ($vars as $var) {
            var_dump($var);
        }
        echo '</pre>';
        die();
    }
}
