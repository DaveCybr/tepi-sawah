<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../database/connect.php';
include '../sidebar/sidebar_kasir.php';

// === 1. Meja aktif (jumlah meja terdaftar) ===
$q1 = $conn->query("SELECT COUNT(*) AS total FROM meja");
$meja_aktif = $q1->fetch_assoc()['total'];

// === 2. Pesanan aktif (detail pesanan yang belum selesai) ===
$q2 = $conn->query("
    SELECT COUNT(*) AS total 
    FROM detail_pesanan dp
    JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
    WHERE p.status_pesanan NOT IN ('selesai','dibayar','dibatalkan')
");
$pesanan_aktif = $q2->fetch_assoc()['total'];

// === 3. Pendapatan hari ini (laporan transaksi penjualan hari ini) ===
$q3 = $conn->query("
    SELECT COALESCE(SUM(nominal), 0) AS total
    FROM laporan_transaksi
    WHERE jenis = 'penjualan'
      AND DATE(waktu_transaksi) = CURDATE()
");
$pendapatan_hari_ini = number_format($q3->fetch_assoc()['total'], 0, ',', '.');

// === 4. Pesanan selesai ===
$q4 = $conn->query("
    SELECT COUNT(*) AS total
    FROM pesanan
    WHERE status_pesanan IN ('selesai','dibayar')
");
$pesanan_selesai = $q4->fetch_assoc()['total'];

// === Data semua meja untuk tampilan grid ===
$meja = $conn->query("SELECT * FROM meja ORDER BY nomor_meja ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Meja</title>
<style>
    body {
        font-family: 'Segoe UI', sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f9fafb;
        display: flex;
    }

    main {
        flex: 1;
        padding: 25px;
        background: #f9fafb;
        transition: margin-left 0.3s ease;
        margin-left: 250px; /* mengikuti sidebar */
    }

    body.sidebar-collapsed main {
        margin-left: 80px;
    }

    /* ======== Statistik ======== */
    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: #fff;
        border-radius: 16px;
        padding: 22px;
        position: relative;
        box-shadow: 0 1px 6px rgba(0,0,0,0.1);
    }
    .stat-card h3 {
        color: #374151;
        font-size: 0.95rem;
        margin: 0;
    }
    .stat-card p {
        margin: 10px 0 0;
        font-size: 1.8rem;
        font-weight: bold;
        color: #111827;
    }

    .icon {
        position: absolute;
        top: 15px;
        right: 15px;
        width: 35px;
        height: 35px;
        border-radius: 10px;
        background-size: 60%;
        background-repeat: no-repeat;
        background-position: center;
        opacity: 0.9;
    }
    .icon.user { background: #e0f2fe url('https://cdn-icons-png.flaticon.com/512/847/847969.png'); }
    .icon.cart { background: #fef3c7 url('https://cdn-icons-png.flaticon.com/512/1170/1170678.png'); }
    .icon.money { background: #dcfce7 url('https://cdn-icons-png.flaticon.com/512/483/483947.png'); }
    .icon.done { background: #ede9fe url('https://cdn-icons-png.flaticon.com/512/1828/1828640.png'); }

    /* ======== Grid Meja ======== */
    .meja-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
    }

    .meja-card {
        background: #fff;
        border-radius: 15px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }

    .meja-card:hover {
        transform: translateY(-5px);
    }

    .circle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        border: 2px solid currentColor;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin: 0 auto 10px;
        font-size: 1.2rem;
    }

    .kosong { border-color: #22c55e; color: #16a34a; }
    .terisi { border-color: #facc15; color: #ca8a04; }
    .menunggu_pembayaran { border-color: #f97316; color: #c2410c; }
    .selesai { border-color: #9ca3af; color: #6b7280; }

    .meja-card h4 { margin: 8px 0 2px; color: inherit; font-size: 1.1rem; }
    .status { font-weight: bold; color: inherit; margin-bottom: 8px; }
    .info { color: #6b7280; font-size: 0.9rem; margin: 4px 0; }
    .harga { color: #111827; font-weight: bold; margin-bottom: 8px; }

    .qris {
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 6px 12px;
        cursor: pointer;
        font-size: 0.85rem;
    }
    .bayar {
        background: #16a34a;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 6px 12px;
        cursor: pointer;
        font-size: 0.85rem;
        margin-top: 6px;
    }

    @media (max-width: 768px) {
        main { margin-left: 0; padding: 15px; }
    }
</style>
</head>
<body>
<main>
    <div class="stats-container">
        <div class="stat-card">
            <div class="icon user"></div>
            <h3>Meja Aktif</h3>
            <p><?= $meja_aktif ?></p>
        </div>
        <div class="stat-card">
            <div class="icon cart"></div>
            <h3>Pesanan Aktif</h3>
            <p><?= $pesanan_aktif ?></p>
        </div>
        <div class="stat-card">
            <div class="icon money"></div>
            <h3>Pendapatan Hari Ini</h3>
            <p>Rp <?= $pendapatan_hari_ini ?></p>
        </div>
        <div class="stat-card">
            <div class="icon done"></div>
            <h3>Pesanan Selesai</h3>
            <p><?= $pesanan_selesai ?></p>
        </div>
    </div>

    <div class="meja-grid">
        <?php for ($i = 1; $i <= 12; $i++): 
            $m = $meja[$i-1] ?? ['nomor_meja'=>"M$i",'status_meja'=>'kosong'];
            $status = $m['status_meja'];
            $status_text = ucwords(str_replace('_',' ', $status));
        ?>
        <div class="meja-card <?= $status ?>">
            <div class="circle"><?= $i ?></div>
            <h4>Meja <?= htmlspecialchars($m['nomor_meja']) ?></h4>
            <p class="status"><?= $status_text ?></p>
            <p class="info">👥 <?= rand(1,4) ?> orang</p>
            <p class="harga">Rp <?= number_format(rand(100000,300000),0,',','.') ?></p>

            <?php if($status == 'menunggu_pembayaran'): ?>
                <button class="qris">QRIS</button>
                <button class="bayar">Sudah Bayar</button>
            <?php endif; ?>
        </div>
        <?php endfor; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('#sidebarToggle');
    const sidebar = document.querySelector('.sidebar'); // pastikan sidebar kamu punya class "sidebar"
    const main = document.querySelector('main');

    if (toggle && sidebar && main) {
        toggle.addEventListener('click', () => {
            // toggle kelas collapse
            sidebar.classList.toggle('collapsed');

            if (sidebar.classList.contains('collapsed')) {
                // sidebar disembunyikan, konten jadi full
                main.style.marginLeft = '0';
            } else {
                // sidebar dibuka lagi, konten geser ke kanan
                main.style.marginLeft = '250px';
            }
        });
    }
});
</script>

</body>
</html>
