<?php
require_once '../includes/init.php';
requireKasir();

require_once '../models/Pembayaran.php';

$pembayaranModel = new Pembayaran();

// Get tanggal dari parameter atau hari ini
$tanggal = isset($_GET['tanggal']) ? clean($_GET['tanggal']) : date('Y-m-d');

// Get transaksi
$transaksi = $pembayaranModel->getTransaksiHarian($tanggal);
$statistik = $pembayaranModel->getStatistik($tanggal);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Harian - <?= APP_NAME ?></title>
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

        .page-header h1 {
            color: #1e293b;
        }

        .date-filter {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .date-filter input {
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }

        .date-filter button {
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .stat-card h4 {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
        }

        .stat-card.blue .value {
            color: #3b82f6;
        }

        .stat-card.green .value {
            color: #10b981;
        }

        .stat-card.orange .value {
            color: #f59e0b;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8fafc;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            font-weight: 600;
            color: #475569;
            font-size: 14px;
        }

        td {
            color: #1e293b;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.qris {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge.cash {
            background: #d1fae5;
            color: #065f46;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
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
                <h1><i class="fas fa-receipt"></i> Transaksi Harian</h1>
                <p style="color: #64748b; margin-top: 5px;">
                    <?= tanggalIndo($tanggal) ?>
                </p>
            </div>

            <div class="date-filter">
                <input type="date"
                    id="tanggalInput"
                    value="<?= $tanggal ?>"
                    max="<?= date('Y-m-d') ?>">
                <button onclick="filterTanggal()">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <h4>Total Transaksi</h4>
                <div class="value"><?= $statistik['total_transaksi'] ?? 0 ?></div>
            </div>

            <div class="stat-card green">
                <h4>Total Pendapatan</h4>
                <div class="value"><?= rupiah($statistik['total_pendapatan'] ?? 0) ?></div>
            </div>

            <div class="stat-card orange">
                <h4>Pembayaran Cash</h4>
                <div class="value"><?= $statistik['jumlah_cash'] ?? 0 ?></div>
                <small style="color: #64748b;">
                    <?= rupiah($statistik['total_cash'] ?? 0) ?>
                </small>
            </div>

            <div class="stat-card blue">
                <h4>Pembayaran QRIS</h4>
                <div class="value"><?= $statistik['jumlah_qris'] ?? 0 ?></div>
                <small style="color: #64748b;">
                    <?= rupiah($statistik['total_qris'] ?? 0) ?>
                </small>
            </div>
        </div>

        <!-- Transaction List -->
        <div class="section">
            <h3 class="section-title">Daftar Transaksi</h3>

            <div class="table-container">
                <?php if ($transaksi && $transaksi->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>No. Struk</th>
                                <th>No. Pesanan</th>
                                <th>Meja</th>
                                <th>Waktu</th>
                                <th>Metode</th>
                                <th>Jenis</th>
                                <th style="text-align: right;">Total</th>
                                <th style="text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($trx = $transaksi->fetch_assoc()): ?>
                                <tr>
                                    <td><strong>#<?= $trx['id_pembayaran'] ?></strong></td>
                                    <td>#<?= $trx['id_pesanan'] ?></td>
                                    <td>Meja <?= $trx['nomor_meja'] ?></td>
                                    <td><?= date('H:i', strtotime($trx['waktu_pembayaran'])) ?></td>
                                    <td>
                                        <span class="badge <?= $trx['metode'] ?>">
                                            <?= strtoupper($trx['metode']) ?>
                                        </span>
                                    </td>
                                    <td><?= ucfirst($trx['jenis_pesanan']) ?></td>
                                    <td style="text-align: right; font-weight: 600; color: #10b981;">
                                        <?= rupiah($trx['jumlah_dibayar']) ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="struk.php?id=<?= $trx['id_pembayaran'] ?>"
                                            style="color: #3b82f6; text-decoration: none;"
                                            title="Lihat Struk">
                                            <i class="fas fa-receipt"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                        <tfoot>
                            <tr style="background: #f8fafc; font-weight: 700;">
                                <td colspan="6" style="text-align: right;">TOTAL PENDAPATAN:</td>
                                <td style="text-align: right; color: #10b981; font-size: 18px;">
                                    <?= rupiah($statistik['total_pendapatan'] ?? 0) ?>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-receipt"></i>
                        <h3 style="color: #475569; margin-bottom: 10px;">Tidak Ada Transaksi</h3>
                        <p>Belum ada transaksi pada tanggal ini</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function filterTanggal() {
            const tanggal = document.getElementById('tanggalInput').value;
            window.location.href = '?tanggal=' + tanggal;
        }
    </script>
</body>

</html>