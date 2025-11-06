<?php
require_once '../../includes/init.php';
requireOwner();

$db = Database::getInstance();

// Get statistics
$stats = [
    'total_menu' => 0,
    'menu_aktif' => 0,
    'total_meja' => 0,
    'meja_terisi' => 0,
    'total_users' => 0,
    'pendapatan_hari_ini' => 0,
    'pesanan_hari_ini' => 0,
    'pengeluaran_hari_ini' => 0
];

// Total Menu
$result = $db->query("SELECT COUNT(*) as total FROM menu");
if ($result) {
    $stats['total_menu'] = $result->fetch_assoc()['total'];
}

// Menu Aktif
$result = $db->query("SELECT COUNT(*) as total FROM menu WHERE status_menu = 'aktif'");
if ($result) {
    $stats['menu_aktif'] = $result->fetch_assoc()['total'];
}

// Total Meja
$result = $db->query("SELECT COUNT(*) as total FROM meja");
if ($result) {
    $stats['total_meja'] = $result->fetch_assoc()['total'];
}

// Meja Terisi
$result = $db->query("SELECT COUNT(*) as total FROM meja WHERE status_meja = 'terisi'");
if ($result) {
    $stats['meja_terisi'] = $result->fetch_assoc()['total'];
}

// Total Users
$result = $db->query("SELECT COUNT(*) as total FROM pengguna");
if ($result) {
    $stats['total_users'] = $result->fetch_assoc()['total'];
}

// Pendapatan Hari Ini
$today = date('Y-m-d');
$result = $db->query("SELECT COALESCE(SUM(dp.subtotal), 0) as total 
                      FROM detail_pesanan dp 
                      JOIN pesanan p ON dp.id_pesanan = p.id_pesanan 
                      WHERE DATE(p.waktu_pesan) = ?", 's', [$today]);
if ($result) {
    $stats['pendapatan_hari_ini'] = $result->fetch_assoc()['total'];
}

// Pesanan Hari Ini
$result = $db->query("SELECT COUNT(DISTINCT id_pesanan) as total 
                      FROM pesanan 
                      WHERE DATE(waktu_pesan) = ?", 's', [$today]);
if ($result) {
    $stats['pesanan_hari_ini'] = $result->fetch_assoc()['total'];
}

// Pengeluaran Hari Ini
$result = $db->query("SELECT COALESCE(SUM(harga), 0) as total 
                      FROM pembelian_bahan 
                      WHERE DATE(tanggal_beli) = ?", 's', [$today]);
if ($result) {
    $stats['pengeluaran_hari_ini'] = $result->fetch_assoc()['total'];
}

// Recent Orders (5 terakhir)
$recentOrders = [];
$result = $db->query("SELECT p.id_pesanan, p.waktu_pesan, m.nomor_meja, 
                             COALESCE(SUM(dp.subtotal), 0) as total
                      FROM pesanan p
                      JOIN meja m ON p.id_meja = m.id_meja
                      LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
                      WHERE DATE(p.waktu_pesan) = ?
                      GROUP BY p.id_pesanan
                      ORDER BY p.waktu_pesan DESC
                      LIMIT 5", 's', [$today]);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recentOrders[] = $row;
    }
}

// Menu Terlaris Hari Ini
$topMenus = [];
$result = $db->query("SELECT m.nama_menu, 
                             SUM(dp.jumlah) as total_terjual,
                             SUM(dp.subtotal) as total_pendapatan
                      FROM detail_pesanan dp
                      JOIN menu m ON dp.id_menu = m.id_menu
                      JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
                      WHERE DATE(p.waktu_pesan) = ?
                      GROUP BY m.id_menu
                      ORDER BY total_terjual DESC
                      LIMIT 5", 's', [$today]);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $topMenus[] = $row;
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
    <title>Dashboard Owner - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/owner/dashboard.css">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/owner/base.css">
</head>

<body>
    <?php include ROOT_PATH . '/sidebar/sidebar.php'; ?>

    <div class="dashboard-container">
        <?php if ($flash): ?>
            <div class="alert-message alert-<?= $flash['type'] ?>">
                <?= $flash['message'] ?>
                <button class="alert-close" onclick="this.parentElement.remove()">×</button>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="dashboard-header">
            <div>
                <h1>Dashboard Owner</h1>
                <p class="header-subtitle">Selamat datang, <?= htmlspecialchars($_SESSION[SESSION_USER_NAME]) ?></p>
            </div>
            <div class="header-date">
                <i class="far fa-calendar-alt"></i>
                <?= tanggalIndo(date('Y-m-d')) ?>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card stat-blue">
                <div class="stat-icon">
                    <i class="fas fa-utensils"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Menu</div>
                    <div class="stat-value"><?= $stats['total_menu'] ?></div>
                    <div class="stat-detail"><?= $stats['menu_aktif'] ?> aktif</div>
                </div>
            </div>

            <div class="stat-card stat-green">
                <div class="stat-icon">
                    <i class="fas fa-chair"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Meja</div>
                    <div class="stat-value"><?= $stats['total_meja'] ?></div>
                    <div class="stat-detail"><?= $stats['meja_terisi'] ?> terisi</div>
                </div>
            </div>

            <div class="stat-card stat-orange">
                <div class="stat-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Pesanan Hari Ini</div>
                    <div class="stat-value"><?= $stats['pesanan_hari_ini'] ?></div>
                    <div class="stat-detail">Total pesanan</div>
                </div>
            </div>

            <div class="stat-card stat-purple">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">Total Pengguna</div>
                    <div class="stat-value"><?= $stats['total_users'] ?></div>
                    <div class="stat-detail">Kasir & Owner</div>
                </div>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="financial-grid">
            <div class="financial-card income">
                <div class="financial-header">
                    <div class="financial-icon">
                        <i class="fas fa-arrow-trend-up"></i>
                    </div>
                    <div>
                        <div class="financial-label">Pendapatan Hari Ini</div>
                        <div class="financial-value"><?= rupiah($stats['pendapatan_hari_ini']) ?></div>
                    </div>
                </div>
            </div>

            <div class="financial-card expense">
                <div class="financial-header">
                    <div class="financial-icon">
                        <i class="fas fa-arrow-trend-down"></i>
                    </div>
                    <div>
                        <div class="financial-label">Pengeluaran Hari Ini</div>
                        <div class="financial-value"><?= rupiah($stats['pengeluaran_hari_ini']) ?></div>
                    </div>
                </div>
            </div>

            <div class="financial-card profit">
                <div class="financial-header">
                    <div class="financial-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div>
                        <div class="financial-label">Keuntungan Hari Ini</div>
                        <div class="financial-value">
                            <?= rupiah($stats['pendapatan_hari_ini'] - $stats['pengeluaran_hari_ini']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Orders -->
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-clock-rotate-left"></i> Pesanan Terbaru</h3>
                    <a href="<?= APP_URL ?>/owner/inside/laporan_penjualan.php" class="card-link">
                        Lihat Semua <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="table-responsive">
                    <?php if (count($recentOrders) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID Pesanan</th>
                                    <th>Meja</th>
                                    <th>Waktu</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                    <tr>
                                        <td><span class="order-id">#<?= $order['id_pesanan'] ?></span></td>
                                        <td><?= htmlspecialchars($order['nomor_meja']) ?></td>
                                        <td><?= date('H:i', strtotime($order['waktu_pesan'])) ?></td>
                                        <td><strong><?= rupiah($order['total']) ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Belum ada pesanan hari ini</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Menu -->
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="fas fa-fire"></i> Menu Terlaris</h3>
                    <a href="<?= APP_URL ?>/owner/inside/tambah_menu.php" class="card-link">
                        Kelola Menu <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
                <div class="top-menu-list">
                    <?php if (count($topMenus) > 0): ?>
                        <?php $rank = 1;
                        foreach ($topMenus as $menu): ?>
                            <div class="top-menu-item">
                                <div class="menu-rank">#<?= $rank++ ?></div>
                                <div class="menu-info">
                                    <div class="menu-name"><?= htmlspecialchars($menu['nama_menu']) ?></div>
                                    <div class="menu-stats">
                                        <span><i class="fas fa-shopping-bag"></i> <?= $menu['total_terjual'] ?> terjual</span>
                                        <span class="menu-revenue"><?= rupiah($menu['total_pendapatan']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-utensils"></i>
                            <p>Belum ada penjualan hari ini</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <h3><i class="fas fa-bolt"></i> Aksi Cepat</h3>
            <div class="actions-grid">
                <a href="<?= APP_URL ?>/owner/inside/tambah_menu.php" class="action-btn">
                    <i class="fas fa-utensils"></i>
                    <span>Kelola Menu</span>
                </a>
                <a href="<?= APP_URL ?>/owner/inside/tambah_meja.php" class="action-btn">
                    <i class="fas fa-chair"></i>
                    <span>Kelola Meja</span>
                </a>
                <a href="<?= APP_URL ?>/owner/inside/pembelian_bahan.php" class="action-btn">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Input Pembelian</span>
                </a>
                <a href="<?= APP_URL ?>/owner/inside/laporan_penjualan.php" class="action-btn">
                    <i class="fas fa-chart-pie"></i>
                    <span>Laporan Keuangan</span>
                </a>
                <a href="<?= APP_URL ?>/owner/inside/manage_users.php" class="action-btn">
                    <i class="fas fa-users"></i>
                    <span>Kelola Pengguna</span>
                </a>
                <a href="<?= APP_URL ?>/owner/inside/manajemen_meja.php" class="action-btn">
                    <i class="fas fa-table-cells"></i>
                    <span>Manajemen Meja</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        // Auto hide alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert-message');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    </script>
</body>

</html>