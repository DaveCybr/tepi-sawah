<?php
require_once 'includes/init.php';

// Verify CSRF token untuk prevent CSRF logout attack
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid logout request');
        redirect(APP_URL . '/login.php');
    }

    // Logout akan handle semua session cleanup
    logout();
} else {
    // GET request - show confirmation page
    if (!isLoggedIn()) {
        redirect(APP_URL . '/login.php');
    }

    $csrf_token = generateCSRFToken();
?>
    <!DOCTYPE html>
    <html lang="id">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Logout - <?= APP_NAME ?></title>
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

            .logout-container {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                padding: 40px 30px;
                max-width: 400px;
                width: 100%;
                text-align: center;
            }

            .logout-icon {
                font-size: 60px;
                color: #f59e0b;
                margin-bottom: 20px;
            }

            h1 {
                font-size: 24px;
                color: #1f2937;
                margin-bottom: 10px;
            }

            p {
                color: #6b7280;
                line-height: 1.6;
                margin-bottom: 30px;
            }

            .user-info {
                background: #f3f4f6;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 30px;
            }

            .user-info strong {
                color: #1f2937;
            }

            .btn-group {
                display: flex;
                gap: 10px;
            }

            .btn {
                flex: 1;
                padding: 12px;
                border-radius: 10px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s;
                border: none;
            }

            .btn-cancel {
                background: #f3f4f6;
                color: #374151;
            }

            .btn-cancel:hover {
                background: #e5e7eb;
            }

            .btn-logout {
                background: #ef4444;
                color: white;
            }

            .btn-logout:hover {
                background: #dc2626;
                transform: translateY(-2px);
            }
        </style>
    </head>

    <body>
        <div class="logout-container">
            <div class="logout-icon">
                <i class="fas fa-sign-out-alt"></i>
            </div>

            <h1>Konfirmasi Logout</h1>
            <p>Apakah Anda yakin ingin keluar dari sistem?</p>

            <div class="user-info">
                <strong><?= htmlspecialchars($_SESSION[SESSION_USER_NAME]) ?></strong><br>
                <small><?= htmlspecialchars($_SESSION[SESSION_USER_EMAIL]) ?></small>
            </div>

            <form method="POST" action="" id="logoutForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div class="btn-group">
                    <button type="button" class="btn btn-cancel" onclick="window.history.back()">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-logout">
                        Logout
                    </button>
                </div>
            </form>
        </div>

        <script>
            document.getElementById('logoutForm').addEventListener('submit', function() {
                const btn = this.querySelector('.btn-logout');
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logout...';
            });
        </script>
    </body>

    </html>
<?php
}
