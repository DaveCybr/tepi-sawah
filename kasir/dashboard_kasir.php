<?php
require_once '../includes/init.php';
requireKasir();

require_once '../models/Meja.php';
require_once '../models/Pesanan.php';
require_once '../models/Pembayaran.php';

$mejaModel = new Meja();
$pesananModel = new Pesanan();
$pembayaranModel = new Pembayaran();

// Get statistics
$statMeja = $mejaModel->getStatistik();
$statPesanan = $pesananModel->getStatistik();
$statPembayaran = $pembayaranModel->getStatistik();

// Get meja dengan detail
$mejaTerisi = $mejaModel->getTerisiWithPesanan();
$mejaKosong = $mejaModel->getKosong();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Kasir - <?= APP_NAME ?></title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }

        .page-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .page-header p {
            opacity: 0.9;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .quick-btn {
            background: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: #1e293b;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .quick-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            border-color: #3b82f6;
        }

        .quick-btn i {
            font-size: 32px;
            margin-bottom: 10px;
            color: #3b82f6;
        }

        .quick-btn.success i {
            color: #10b981;
        }

        .quick-btn.warning i {
            color: #f59e0b;
        }

        .quick-btn.danger i {
            color: #ef4444;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #e2e8f0;
        }

        .stat-card.blue {
            border-left-color: #3b82f6;
        }

        .stat-card.green {
            border-left-color: #10b981;
        }

        .stat-card.orange {
            border-left-color: #f59e0b;
        }

        .stat-card.purple {
            border-left-color: #8b5cf6;
        }

        .stat-card h4 {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card .value {
            font-size: 36px;
            font-weight: 700;
            color: #1e293b;
        }

        .stat-card .subtitle {
            font-size: 13px;
            color: #94a3b8;
            margin-top: 8px;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }

        .meja-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }

        .meja-card {
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
        }

        .meja-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .meja-card.kosong {
            background: #f0fdf4;
            border-color: #86efac;
        }

        .meja-card.terisi {
            background: #fef3c7;
            border-color: #fcd34d;
        }

        .meja-card.menunggu_pembayaran {
            background: #fee2e2;
            border-color: #fca5a5;
        }

        .meja-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .meja-status {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .meja-info {
            margin-top: 10px;
            font-size: 13px;
            color: #64748b;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            font-size: 13px;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
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
            <h1><i class="fas fa-chart-line"></i> Dashboard Kasir</h1>
            <p>Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>! • <?= date('d F Y, H:i') ?></p>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="input_pesanan.php" class="quick-btn success">
                <i class="fas fa-plus-circle"></i>
                <h3>Input Pesanan</h3>
            </a>
            <a href="pesanan_aktif.php" class="quick-btn">
                <i class="fas fa-list-check"></i>
                <h3>Pesanan Aktif</h3>
            </a>
            <a href="pembayaran.php" class="quick-btn warning">
                <i class="fas fa-cash-register"></i>
                <h3>Pembayaran</h3>
            </a>
            <a href="transaksi_harian.php" class="quick-btn danger">
                <i class="fas fa-receipt"></i>
                <h3>Transaksi</h3>
            </a>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <h4>Total Meja</h4>
                <div class="value"><?= $statMeja['total_meja'] ?? 0 ?></div>
                <div class="subtitle">
                    <?= $statMeja['kosong'] ?? 0 ?> kosong •
                    <?= $statMeja['terisi'] ?? 0 ?> terisi
                </div>
            </div>

            <div class="stat-card orange">
                <h4>Pesanan Aktif</h4>
                <div class="value"><?= $statPesanan['menunggu'] + $statPesanan['dimasak'] + $statPesanan['siap_disajikan'] ?></div>
                <div class="subtitle">
                    <?= $statPesanan['menunggu'] ?? 0 ?> menunggu •
                    <?= $statPesanan['dimasak'] ?? 0 ?> dimasak
                </div>
            </div>

            <div class="stat-card green">
                <h4>Pendapatan Hari Ini</h4>
                <div class="value"><?= rupiah($statPembayaran['total_pendapatan'] ?? 0) ?></div>
                <div class="subtitle">
                    <?= $statPembayaran['total_transaksi'] ?? 0 ?> transaksi
                </div>
            </div>

            <div class="stat-card purple">
                <h4>Pesanan Selesai</h4>
                <div class="value"><?= $statPesanan['selesai'] ?? 0 ?></div>
                <div class="subtitle">
                    Hari ini
                </div>
            </div>
        </div>

        <!-- Meja Terisi -->
        <?php if ($mejaTerisi && $mejaTerisi->num_rows > 0): ?>
            <div class="section">
                <div class="section-header">
                    <h3 class="section-title">
                        <i class="fas fa-table-cells"></i> Meja Terisi
                    </h3>
                    <span style="color: #64748b;">
                        <?= $mejaTerisi->num_rows ?> meja aktif
                    </span>
                </div>

                <div class="meja-grid">
                    <?php while ($meja = $mejaTerisi->fetch_assoc()): ?>
                        <div class="meja-card terisi" onclick="location.href='pesanan_aktif.php'">
                            <div class="meja-number"><?= $meja['nomor_meja'] ?></div>
                            <div class="meja-status">Terisi</div>
                            <div class="meja-info">
                                <div style="margin-top: 8px;">
                                    <i class="fas fa-shopping-bag"></i>
                                    <?= $meja['jumlah_item'] ?> item
                                </div>
                                <div style="margin-top: 5px; font-weight: 600; color: #f59e0b;">
                                    <?= rupiah($meja['total_harga']) ?>
                                </div>
                                <div style="margin-top: 8px;">
                                    <span style="padding: 4px 8px; background: #fef3c7; border-radius: 12px; font-size: 11px;">
                                        <?= ucfirst(str_replace('_', ' ', $meja['status_pesanan'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Meja Kosong -->
        <div class="section">
            <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-table-cells"></i> Meja Kosong
                </h3>
                <span style="color: #64748b;">
                    <?= $mejaKosong ? $mejaKosong->num_rows : 0 ?> meja tersedia
                </span>
            </div>

            <div class="meja-grid">
                <?php
                if ($mejaKosong && $mejaKosong->num_rows > 0):
                    while ($meja = $mejaKosong->fetch_assoc()):
                ?>
                        <div class="meja-card kosong">
                            <div class="meja-number"><?= $meja['nomor_meja'] ?></div>
                            <div class="meja-status" style="color: #10b981;">Tersedia</div>
                        </div>
                    <?php
                    endwhile;
                else:
                    ?>
                    <p style="color: #94a3b8;">Semua meja sedang terisi</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>

</html>