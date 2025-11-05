<?php

/**
 * Helper Functions
 * Fungsi-fungsi pembantu untuk keamanan dan utility
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
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check user role
 */
function hasRole($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Redirect function
 */
function redirect($url)
{
    header("Location: " . $url);
    exit;
}

/**
 * Flash message
 */
function setFlash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
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
function tanggalIndo($tanggal)
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

    $pecahkan = explode('-', date('Y-m-d', strtotime($tanggal)));
    return $pecahkan[2] . ' ' . $bulan[(int)$pecahkan[1]] . ' ' . $pecahkan[0];
}

/**
 * Upload image dengan validasi
 */
function uploadImage($file, $allowedTypes = null)
{
    if ($allowedTypes === null) {
        $allowedTypes = ALLOWED_IMAGE_TYPES;
    }

    // Validasi error
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error: ' . $file['error']);
    }

    // Validasi ukuran
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File terlalu besar. Maksimal ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB');
    }

    // Validasi tipe file
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedTypes)) {
        throw new Exception('Tipe file tidak diizinkan. Hanya: ' . implode(', ', $allowedTypes));
    }

    // Validasi MIME type (lebih secure)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp'
    ];

    if (!isset($allowedMimes[$fileExt]) || $allowedMimes[$fileExt] !== $mimeType) {
        throw new Exception('File tidak valid');
    }

    // Generate unique filename
    $newFilename = uniqid() . '_' . time() . '.' . $fileExt;
    $destination = UPLOAD_PATH . $newFilename;

    // Pindahkan file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Gagal mengupload file');
    }

    return $newFilename;
}

/**
 * Delete file
 */
function deleteFile($filename)
{
    $filepath = UPLOAD_PATH . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

/**
 * Log activity
 */
function logActivity($userId, $action, $description = '')
{
    try {
        $db = Database::getInstance();
        $sql = "INSERT INTO activity_log (user_id, action, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)";

        $db->execute($sql, 'issss', [
            $userId,
            $action,
            $description,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
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
    if (isset($_SESSION['last_activity'])) {
        $elapsed = time() - $_SESSION['last_activity'];
        if ($elapsed > SESSION_LIFETIME) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Rate limiting untuk login
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

    // Check attempts
    if (isset($attempts[$identifier])) {
        if ($attempts[$identifier]['count'] >= MAX_LOGIN_ATTEMPTS) {
            $timeLeft = LOCKOUT_TIME - ($currentTime - $attempts[$identifier]['time']);
            if ($timeLeft > 0) {
                throw new Exception('Terlalu banyak percobaan login. Coba lagi dalam ' . ceil($timeLeft / 60) . ' menit.');
            } else {
                unset($attempts[$identifier]);
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

    if ($success) {
        // Reset attempts jika berhasil
        unset($_SESSION['login_attempts'][$identifier]);
    } else {
        // Tambah attempt jika gagal
        if (!isset($_SESSION['login_attempts'][$identifier])) {
            $_SESSION['login_attempts'][$identifier] = [
                'count' => 0,
                'time' => time()
            ];
        }
        $_SESSION['login_attempts'][$identifier]['count']++;
        $_SESSION['login_attempts'][$identifier]['time'] = time();
    }
}

/**
 * JSON response helper
 */
function jsonResponse($success, $message, $data = null)
{
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Pagination helper
 */
function paginate($totalRows, $currentPage = 1, $perPage = ITEMS_PER_PAGE)
{
    $totalPages = ceil($totalRows / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total_rows' => $totalRows,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}
