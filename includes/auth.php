<?php

/**
 * Authentication Middleware
 * Proteksi halaman dari akses unauthorized
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
        redirect(APP_URL . '/login.php');
    }
}

/**
 * Require specific role
 */
function requireRole($requiredRole)
{
    requireLogin();

    if (!hasRole($requiredRole)) {
        setFlash('error', 'Anda tidak memiliki akses ke halaman ini');
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
        $role = $_SESSION['role'];

        if ($role === 'owner') {
            redirect(APP_URL . '/owner/inside/dashboard.php');
        } elseif ($role === 'kasir') {
            redirect(APP_URL . '/kasir/dashboard_kasir.php');
        }
    }
}

/**
 * Login user
 */
function login($userId, $nama, $email, $role)
{
    // Regenerate session ID untuk keamanan
    session_regenerate_id(true);

    $_SESSION['user_id'] = $userId;
    $_SESSION['nama'] = $nama;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['last_activity'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];

    // Log activity
    logActivity($userId, 'login', 'User logged in');

    // Update last login di database
    $db = Database::getInstance();
    $sql = "UPDATE pengguna SET terakhir_login = NOW() WHERE id_pengguna = ?";
    $db->execute($sql, 'i', [$userId]);
}

/**
 * Logout user
 */
function logout()
{
    if (isLoggedIn()) {
        logActivity($_SESSION['user_id'], 'logout', 'User logged out');
    }

    // Hapus semua session
    $_SESSION = [];

    // Hapus session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
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
 * Hash password
 */
function hashPassword($password)
{
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

    $db = Database::getInstance();
    $sql = "SELECT id_pengguna, nama, email, role, status_akun FROM pengguna WHERE id_pengguna = ?";
    $result = $db->query($sql, 'i', [$_SESSION['user_id']]);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * Check if user account is active
 */
function checkAccountStatus()
{
    $user = getCurrentUser();

    if (!$user || $user['status_akun'] !== 'aktif') {
        logout();
        setFlash('error', 'Akun Anda telah dinonaktifkan');
        redirect(APP_URL . '/login.php');
    }
}
