<?php
require_once '../includes/init.php';
requireKasir();

require_once '../models/Meja.php';

$mejaModel = new Meja();

// Handle reset action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }

        if ($_POST['action'] === 'reset_single') {
            $idMeja = (int)$_POST['id_meja'];
            $mejaModel->updateStatus($idMeja, 'kosong');

            logActivity($_SESSION['user_id'], 'reset_meja', "Reset meja ID: {$idMeja}");

            echo json_encode(['success' => true, 'message' => 'Meja berhasil direset']);
        } elseif ($_POST['action'] === 'reset_all') {
            $mejaModel->resetStatus();

            logActivity($_SESSION['user_id'], 'reset_all_meja', 'Reset semua meja selesai');

            echo json_encode(['success' => true, 'message' => 'Semua meja berhasil direset']);
        }

        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Get meja yang perlu direset
$db = Database::getInstance();
$mejaSelesai = $db->query("SELECT * FROM meja WHERE status_meja = 'selesai' ORDER BY nomor_meja ASC");

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Meja - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f8f9fa;
            padding-left: 260px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .meja-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }

        .meja-card {
            padding: 20px;
            border: 3px solid #93c5fd;
            border-radius: 10px;
            text-align: center;
            background: #eff6ff;
        }

        .meja-number {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .meja-status {
            font-size: 13px;
            font-weight: 600;
            color: #6366f1;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #10b981;
        }

        @media (max-width: 1024px) {
            body {
                padding-left: 0;
            }
        }
    </style>
</head>

<body>
    <?php include '../sidebar/sidebar_kasir.php'; ?>

    <div class="container">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-broom"></i> Reset Meja Selesai</h1>
                <p style="color: #64748b; margin-top: 5px;">
                    Reset meja yang sudah selesai menjadi kosong/tersedia
                </p>
            </div>

            <?php if ($mejaSelesai && $mejaSelesai->num_rows > 0): ?>
                <button class="btn btn-success" onclick="resetAll()">
                    <i class="fas fa-check-double"></i> Reset Semua
                </button>
            <?php endif; ?>
        </div>

        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <strong>Info:</strong> Meja dengan status "Selesai" akan direset menjadi "Kosong" agar bisa digunakan lagi.
        </div>

        <div class="section">
            <?php if ($mejaSelesai && $mejaSelesai->num_rows > 0): ?>
                <h3 style="margin-bottom: 15px;">
                    Meja yang Perlu Direset (<?= $mejaSelesai->num_rows ?>)
                </h3>

                <div class="meja-grid">
                    <?php while ($meja = $mejaSelesai->fetch_assoc()): ?>
                        <div class="meja-card">
                            <div class="meja-number"><?= $meja['nomor_meja'] ?></div>
                            <div class="meja-status">Selesai</div>
                            <button class="btn btn-primary"
                                onclick="resetMeja(<?= $meja['id_meja'] ?>, '<?= $meja['nomor_meja'] ?>')">
                                <i class="fas fa-redo"></i> Reset
                            </button>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h3 style="color: #475569; margin-bottom: 10px;">Semua Meja Sudah Bersih!</h3>
                    <p>Tidak ada meja yang perlu direset</p>
                    <br>
                    <a href="meja.php" class="btn btn-primary">
                        <i class="fas fa-table-cells"></i> Lihat Status Meja
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        const csrfToken = '<?= $csrf_token ?>';

        async function resetMeja(idMeja, nomorMeja) {
            if (!confirm(`Reset Meja ${nomorMeja}?`)) return;

            const formData = new FormData();
            formData.append('action', 'reset_single');
            formData.append('id_meja', idMeja);
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            }
        }

        async function resetAll() {
            if (!confirm('Reset SEMUA meja yang sudah selesai?')) return;

            const formData = new FormData();
            formData.append('action', 'reset_all');
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            }
        }
    </script>
</body>

</html>