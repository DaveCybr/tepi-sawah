<?php
require_once 'includes/init.php';

// Redirect jika sudah login
redirectIfLoggedIn();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }

        $email = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validasi input
        if (empty($email) || empty($password)) {
            throw new Exception('Email dan password wajib diisi');
        }

        if (!validateEmail($email)) {
            throw new Exception('Format email tidak valid');
        }

        // Check login attempts (rate limiting)
        checkLoginAttempts($email);

        // Query user dari database dengan prepared statement
        $db = Database::getInstance();
        $sql = "SELECT id_pengguna, nama, email, password, role, status_akun 
                FROM pengguna 
                WHERE email = ? 
                LIMIT 1";

        $result = $db->query($sql, 's', [$email]);

        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();

            // Check status akun
            if ($user['status_akun'] !== 'aktif') {
                recordLoginAttempt($email, false);
                throw new Exception('Akun Anda telah dinonaktifkan');
            }

            // Verify password
            if (verifyPassword($password, $user['password'])) {
                // Login berhasil
                recordLoginAttempt($email, true);

                // Login user
                login($user['id_pengguna'], $user['nama'], $user['email'], $user['role']);

                // Redirect berdasarkan role
                if ($user['role'] === 'owner') {
                    redirect(APP_URL . '/owner/inside/dashboard.php');
                } elseif ($user['role'] === 'kasir') {
                    redirect(APP_URL . '/kasir/dashboard_kasir.php');
                } else {
                    // Unknown role
                    logout();
                    throw new Exception('Role tidak dikenali');
                }
            } else {
                // Password salah
                recordLoginAttempt($email, false);
                throw new Exception('Email atau password salah');
            }
        } else {
            // User tidak ditemukan
            recordLoginAttempt($email, false);
            throw new Exception('Email atau password salah');
        }
    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
    }
}

$csrf_token = generateCSRFToken();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .login-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .login-body {
            padding: 40px 30px;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            animation: shake 0.5s ease;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            75% {
                transform: translateX(5px);
            }
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 500;
            font-size: 14px;
        }

        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            z-index: 1;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 13px 50px 13px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
            background: white;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #9ca3af;
            transition: all 0.3s;
            z-index: 2;
            padding: 8px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }

        .password-toggle:hover {
            color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }

        .password-toggle:active {
            transform: translateY(-50%) scale(0.9);
        }

        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .login-footer p {
            color: #6b7280;
            font-size: 13px;
        }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-header {
                padding: 30px 20px;
            }

            .login-header h1 {
                font-size: 24px;
            }

            .login-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-utensils"></i> <?= APP_NAME ?></h1>
            <p>Silakan login untuk melanjutkan</p>
        </div>

        <div class="login-body">
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <i class="fas fa-<?= $flash['type'] === 'error' ? 'exclamation-circle' : 'check-circle' ?>"></i>
                    <?= $flash['message'] ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div class="form-group">
                    <label for="email">
                        Email
                    </label>
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email"
                            id="email"
                            name="email"
                            class="form-control"
                            placeholder="nama@email.com"
                            value="<?= isset($_POST['email']) ? clean($_POST['email']) : '' ?>"
                            required
                            autocomplete="email"
                            autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">
                        Password
                    </label>
                    <div class="input-group">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password"
                            id="password"
                            name="password"
                            class="form-control"
                            placeholder="Masukkan password"
                            required
                            autocomplete="current-password">
                        <i class="fas fa-eye password-toggle"
                            id="togglePassword"
                            title="Tampilkan/Sembunyikan Password"></i>
                    </div>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="login-footer">
                <p>
                    <i class="fas fa-info-circle"></i>
                    Hubungi admin untuk mendapatkan akses
                </p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            // Toggle type
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;

            // Toggle icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');

            // Change title
            this.title = type === 'password' ?
                'Tampilkan Password' :
                'Sembunyikan Password';
        });

        // Clear flash message after 5 seconds
        setTimeout(function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            }
        }, 5000);

        // Prevent double submit with loading state
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');

        loginForm.addEventListener('submit', function() {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<div class="spinner"></div> Memproses...';
        });

        // Auto-enable button jika ada error (halaman reload)
        window.addEventListener('load', function() {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login';
        });

        // Keyboard shortcut: Enter to submit
        passwordInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loginForm.submit();
            }
        });
    </script>
</body>

</html>