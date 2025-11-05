<?php
require_once '../includes/init.php';
requireKasir();

require_once '../models/Meja.php';
require_once '../models/Pesanan.php';

$mejaModel = new Meja();
$pesananModel = new Pesanan();

// Get all meja
$allMeja = $mejaModel->getAll();
$statMeja = $mejaModel->getStatistik();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Meja - <?= APP_NAME ?></title>
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
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            margin-left: 260px;
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

        .refresh-btn {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .stat-card h4 {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
        }

        .stat-card.total .value {
            color: #3b82f6;
        }

        .stat-card.kosong .value {
            color: #10b981;
        }

        .stat-card.terisi .value {
            color: #f59e0b;
        }

        .stat-card.menunggu .value {
            color: #ef4444;
        }

        .legend {
            display: flex;
            gap: 20px;
            padding: 20px;
            background: white;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        .legend-color.kosong {
            background: #d1fae5;
            border: 2px solid #10b981;
        }

        .legend-color.terisi {
            background: #fef3c7;
            border: 2px solid #f59e0b;
        }

        .legend-color.menunggu {
            background: #fee2e2;
            border: 2px solid #ef4444;
        }

        .legend-color.selesai {
            background: #e0e7ff;
            border: 2px solid #6366f1;
        }

        .meja-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .meja-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border: 3px solid #e2e8f0;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .meja-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .meja-card.kosong {
            background: #f0fdf4;
            border-color: #86efac;
        }

        .meja-card.terisi {
            background: #fefce8;
            border-color: #fde047;
        }

        .meja-card.menunggu_pembayaran {
            background: #fef2f2;
            border-color: #fca5a5;
        }

        .meja-card.selesai {
            background: #eff6ff;
            border-color: #93c5fd;
        }

        .meja-number {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 10px;
            color: #1e293b;
        }

        .meja-status {
            font-size: 13px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }

        .meja-card.kosong .meja-status {
            color: #10b981;
        }

        .meja-card.terisi .meja-status {
            color: #f59e0b;
        }

        .meja-card.menunggu_pembayaran .meja-status {
            color: #ef4444;
        }

        .meja-card.selesai .meja-status {
            color: #6366f1;
        }

        .meja-detail {
            padding-top: 15px;
            border-top: 2px solid rgba(0, 0, 0, 0.1);
            margin-top: 10px;
        }

        .meja-info {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 5px;
        }

        .meja-total {
            font-size: 18px;
            font-weight: 700;
            color: #f59e0b;
            margin-top: 8px;
        }

        .qr-code {
            width: 80px;
            height: 80px;
            margin: 10px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
        }

        .qr-code i {
            font-size: 40px;
            color: #94a3b8;
        }

        @media (max-width: 1024px) {
            .container {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <?php include '../sidebar/sidebar_kasir.php'; ?>

    <div class="container">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-table-cells"></i> Status Meja</h1>
                <p style="color: #64748b; margin-top: 5px;">
                    Real-time status semua meja
                </p>
            </div>
            <button class="refresh-btn" onclick="location.reload()">
                <i class="fas fa-refresh"></i> Refresh
            </button>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card total">
                <h4>Total Meja</h4>
                <div class="value"><?= $statMeja['total_meja'] ?? 0 ?></div>
            </div>
            <div class="stat-card kosong">
                <h4>Kosong</h4>
                <div class="value"><?= $statMeja['kosong'] ?? 0 ?></div>
            </div>
            <div class="stat-card terisi">
                <h4>Terisi</h4>
                <div class="value"><?= $statMeja['terisi'] ?? 0 ?></div>
            </div>
            <div class="stat-card menunggu">
                <h4>Menunggu Bayar</h4>
                <div class="value"><?= $statMeja['menunggu_pembayaran'] ?? 0 ?></div>
            </div>
        </div>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color kosong"></div>
                <span>Kosong (Tersedia)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color terisi"></div>
                <span>Terisi (Sedang Digunakan)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color menunggu"></div>
                <span>Menunggu Pembayaran</span>
            </div>
            <div class="legend-item">
                <div class="legend-color selesai"></div>
                <span>Selesai</span>
            </div>
        </div>

        <!-- Meja Grid -->
        <div class="meja-grid">
            <?php
            if ($allMeja && $allMeja->num_rows > 0):
                while ($meja = $allMeja->fetch_assoc()):
            ?>
                    <div class="meja-card <?= $meja['status_meja'] ?>"
                        onclick="<?= $meja['pesanan_aktif'] ? "location.href='pesanan_aktif.php'" : "location.href='input_pesanan.php'" ?>">

                        <div class="meja-number"><?= $meja['nomor_meja'] ?></div>

                        <div class="meja-status">
                            <?= str_replace('_', ' ', ucfirst($meja['status_meja'])) ?>
                        </div>

                        <?php if ($meja['status_meja'] === 'terisi' && $meja['total_tagihan'] > 0): ?>
                            <div class="meja-detail">
                                <div class="meja-info">
                                    <i class="fas fa-clock"></i>
                                    <?= date('H:i', strtotime($meja['last_update'])) ?>
                                </div>
                                <div class="meja-total">
                                    <?= rupiah($meja['total_tagihan']) ?>
                                </div>
                            </div>
                        <?php elseif ($meja['status_meja'] === 'kosong'): ?>
                            <div class="qr-code">
                                <i class="fas fa-qrcode"></i>
                            </div>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 5px;">
                                Scan QR untuk order
                            </div>
                        <?php endif; ?>
                    </div>
            <?php
                endwhile;
            endif;
            ?>
        </div>
    </div>

    <script>
        // Auto refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>

</html>