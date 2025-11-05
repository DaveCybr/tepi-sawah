<?php

/**
 * Authentication Middleware
 * Proteksi halaman dari akses unauthorized
 * Version: 2.0.0 - Updated with standardized session keys
 */

/**
 * Require login - redirect ke login jika belum login
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        setFlash('error', 'Silakan login terlebih dahulu');
        redirect(APP_URL . '/login.php');
    }

    // Check session timeout
    if (!checkSessionTimeout()) {
        setFlash('error', 'Sesi Anda telah berakhir. Silakan login kembali');
        logout();
    }

    // Check account status
    checkAccountStatus();
}

/**
 * Require specific role
 */
function requireRole($requiredRole)
{
    requireLogin();

    if (!hasRole($requiredRole)) {
        setFlash('error', 'Anda tidak memiliki akses ke halaman ini');
        logActivity($_SESSION[SESSION_USER_ID], 'unauthorized_access', "Attempted to access $requiredRole page");
        redirect(APP_URL . '/unauthorized.php');
    }
}

/**
 * Require owner role
 */
function requireOwner()
{
    requireRole('owner');
}

/**
 * Require kasir role
 */
function requireKasir()
{
    requireRole('kasir');
}

/**
 * Check if already logged in (untuk halaman login/register)
 */
function redirectIfLoggedIn()
{
    if (isLoggedIn()) {
        $role = $_SESSION[SESSION_USER_ROLE];

        if ($role === 'owner') {
            redirect(APP_URL . '/owner/inside/dashboard.php');
        } elseif ($role === 'kasir') {
            redirect(APP_URL . '/kasir/dashboard_kasir.php');
        } else {
            // Unknown role - logout for security
            logout();
        }
    }
}

/**
 * Login user dengan standardized session keys
 * 
 * @param int $userId
 * @param string $nama
 * @param string $email
 * @param string $role
 */
function login($userId, $nama, $email, $role)
{
    // Regenerate session ID untuk keamanan (prevent session fixation)
    session_regenerate_id(true);

    // Set session dengan standardized keys
    $_SESSION[SESSION_USER_ID] = $userId;
    $_SESSION[SESSION_USER_NAME] = $nama;
    $_SESSION[SESSION_USER_EMAIL] = $email;
    $_SESSION[SESSION_USER_ROLE] = $role;
    $_SESSION[SESSION_LAST_ACTIVITY] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Log activity
    logActivity($userId, 'login', 'User logged in from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

    // Update last login di database
    try {
        $db = Database::getInstance();
        $sql = "UPDATE pengguna SET terakhir_login = NOW() WHERE id_pengguna = ?";
        $db->execute($sql, 'i', [$userId]);
    } catch (Exception $e) {
        error_log("Failed to update last login: " . $e->getMessage());
    }
}

/**
 * Logout user
 */
function logout()
{
    if (isLoggedIn()) {
        logActivity($_SESSION[SESSION_USER_ID], 'logout', 'User logged out');
    }

    // Hapus semua session
    $_SESSION = [];

    // Hapus session cookie
    if (isset($_COOKIE[session_name()])) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 3600,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Destroy session
    session_destroy();

    redirect(APP_URL . '/login.php');
}

/**
 * Verify password
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Hash password dengan konstanta dari config
 */
function hashPassword($password)
{
    // Validate password length
    if (strlen($password) < MIN_PASSWORD_LENGTH) {
        throw new Exception('Password minimal ' . MIN_PASSWORD_LENGTH . ' karakter');
    }

    if (strlen($password) > MAX_PASSWORD_LENGTH) {
        throw new Exception('Password maksimal ' . MAX_PASSWORD_LENGTH . ' karakter');
    }

    return password_hash($password, HASH_ALGO, ['cost' => HASH_COST]);
}

/**
 * Get current user data
 */
function getCurrentUser()
{
    if (!isLoggedIn()) {
        return null;
    }

    try {
        $db = Database::getInstance();
        $sql = "SELECT id_pengguna, nama, email, role, status_akun, terakhir_login 
                FROM pengguna 
                WHERE id_pengguna = ? 
                LIMIT 1";

        $result = $db->query($sql, 'i', [$_SESSION[SESSION_USER_ID]]);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    } catch (Exception $e) {
        error_log("Get current user error: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if user account is active
 */
function checkAccountStatus()
{
    $user = getCurrentUser();

    if (!$user) {
        logout();
        setFlash('error', 'Akun tidak ditemukan');
        redirect(APP_URL . '/login.php');
    }

    if ($user['status_akun'] !== 'aktif') {
        logout();
        setFlash('error', 'Akun Anda telah dinonaktifkan');
        redirect(APP_URL . '/login.php');
    }
}

/**
 * Verify CSRF and check if user is logged in (untuk API endpoints)
 */
function requireAuthAPI()
{
    if (!isLoggedIn()) {
        jsonResponse(false, 'Unauthorized', null, 401);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!verifyCSRFToken($token)) {
            jsonResponse(false, 'Invalid CSRF token', null, 403);
        }
    }
}

/**
 * Check if password needs rehashing (untuk security upgrade)
 */
function needsRehash($hash)
{
    return password_needs_rehash($hash, HASH_ALGO, ['cost' => HASH_COST]);
}

/**
 * Rehash password jika algoritma berubah
 */
function rehashPasswordIfNeeded($userId, $password, $currentHash)
{
    if (needsRehash($currentHash)) {
        try {
            $newHash = hashPassword($password);
            $db = Database::getInstance();
            $sql = "UPDATE pengguna SET password = ? WHERE id_pengguna = ?";
            $db->execute($sql, 'si', [$newHash, $userId]);

            logActivity($userId, 'password_rehash', 'Password rehashed with new algorithm');
            return true;
        } catch (Exception $e) {
            error_log("Password rehash failed: " . $e->getMessage());
            return false;
        }
    }
    return false;
}
