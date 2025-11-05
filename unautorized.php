<?php
require_once 'includes/init.php';

// Log unauthorized access attempt
if (isset($_SESSION[SESSION_USER_ID])) {
    logActivity(
        $_SESSION[SESSION_USER_ID],
        'unauthorized_access',
        'Attempted to access restricted page: ' . ($_SERVER['HTTP_REFERER'] ?? 'unknown')
    );
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - <?= APP_NAME ?></title>
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

        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 60px 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
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

        .error-icon {
            font-size: 80px;
            color: #ef4444;
            margin-bottom: 20px;
            animation: shake 0.5s ease;
        }

        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-10px);
            }

            75% {
                transform: translateX(10px);
            }
        }

        h1 {
            font-size: 32px;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .error-code {
            font-size: 18px;
            color: #6b7280;
            margin-bottom: 20px;
            font-weight: 600;
        }

        p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .alert {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc2626;
            text-align: left;
        }

        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 2px solid #e5e7eb;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .info-box {
            background: #f3f4f6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            text-align: left;
        }

        .info-box strong {
            color: #1f2937;
            display: block;
            margin-bottom: 5px;
        }

        .info-box span {
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-ban"></i>
        </div>

        <h1>Akses Ditolak</h1>
        <p class="error-code">Error 403 - Forbidden</p>

        <?php if ($flash): ?>
            <div class="alert">
                <?= $flash['message'] ?>
            </div>
        <?php endif; ?>

        <p>
            Maaf, Anda tidak memiliki izin untuk mengakses halaman ini.
            Silakan hubungi administrator jika Anda merasa ini adalah kesalahan.
        </p>

        <?php if (isLoggedIn()): ?>
            <div class="info-box">
                <strong>Informasi Akun Anda:</strong>
                <span>
                    <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION[SESSION_USER_NAME]) ?><br>
                    <i class="fas fa-id-badge"></i> Role: <?= ucfirst($_SESSION[SESSION_USER_ROLE]) ?>
                </span>
            </div>
        <?php endif; ?>

        <div class="btn-group">
            <button class="btn btn-secondary" onclick="window.history.back()">
                <i class="fas fa-arrow-left"></i> Kembali
            </button>

            <?php if (isLoggedIn()): ?>
                <?php if (hasRole('owner')): ?>
                    <a href="<?= APP_URL ?>/owner/inside/dashboard.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                <?php elseif (hasRole('kasir')): ?>
                    <a href="<?= APP_URL ?>/kasir/dashboard_kasir.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="<?= APP_URL ?>/login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>