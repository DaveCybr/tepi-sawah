<?php
// Pastikan init sudah loaded
if (!defined('INIT_LOADED')) {
  die('Direct access not allowed');
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
  .sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 260px;
    height: 100vh;
    background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
    color: white;
    padding: 0;
    z-index: 1000;
    box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
    overflow-y: auto;
    display: flex;
    flex-direction: column;
  }

  /* Custom Scrollbar */
  .sidebar::-webkit-scrollbar {
    width: 6px;
  }

  .sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
  }

  .sidebar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 10px;
  }

  .sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.3);
  }

  .sidebar-header {
    padding: 25px 20px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  }

  .sidebar-header h2 {
    font-size: 22px;
    margin: 0 0 5px 0;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
  }

  .sidebar-header h2 i {
    font-size: 24px;
  }

  .sidebar-header p {
    font-size: 12px;
    opacity: 0.7;
    margin: 0;
  }

  .sidebar-user {
    padding: 15px 20px;
    background: rgba(255, 255, 255, 0.05);
    margin: 15px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
  }

  .sidebar-user .user-name {
    font-weight: 600;
    margin-bottom: 5px;
    font-size: 14px;
  }

  .sidebar-user .user-role {
    font-size: 11px;
    opacity: 0.8;
    text-transform: uppercase;
    color: #fbbf24;
    letter-spacing: 0.5px;
    font-weight: 600;
  }

  .sidebar-content {
    flex: 1;
    overflow-y: auto;
    padding-bottom: 100px;
  }

  .sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 10px 0 0 0;
  }

  .menu-item {
    margin-bottom: 2px;
  }

  .menu-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    transition: all 0.3s;
    position: relative;
    font-size: 14px;
  }

  .menu-link:hover {
    background: rgba(255, 255, 255, 0.08);
    color: white;
  }

  .menu-link.active {
    background: rgba(59, 130, 246, 0.15) !important;
    color: #60a5fa !important;
    border-left: 3px solid #3b82f6 !important;
    font-weight: 600 !important;
  }

  .menu-link i {
    width: 20px;
    text-align: center;
    font-size: 16px;
  }

  .menu-section {
    padding: 20px 20px 8px;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    opacity: 0.5;
    font-weight: 700;
    margin-top: 10px;
  }

  .sidebar-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    width: 260px;
    padding: 15px 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(10px);
    z-index: 1001;
  }

  .logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 12px;
    background: rgba(239, 68, 68, 0.15);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
    font-size: 14px;
  }

  .logout-btn:hover {
    background: rgba(239, 68, 68, 0.25);
    color: white;
    border-color: rgba(239, 68, 68, 0.5);
    transform: translateY(-1px);
  }

  .logout-btn:active {
    transform: translateY(0);
  }

  /* Mobile Toggle */
  .sidebar-toggle {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1002;
    background: #3b82f6;
    color: white;
    border: none;
    padding: 12px 16px;
    border-radius: 8px;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    transition: all 0.3s;
  }

  .sidebar-toggle:hover {
    background: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(59, 130, 246, 0.5);
  }

  .sidebar-toggle i {
    font-size: 18px;
  }

  @media (max-width: 1024px) {
    .sidebar {
      transform: translateX(-100%);
      transition: transform 0.3s ease;
    }

    .sidebar.mobile-active {
      transform: translateX(0);
    }

    .sidebar-toggle {
      display: block;
    }

    .sidebar-footer {
      width: 260px;
    }
  }

  /* Overlay untuk mobile */
  .sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
  }

  .sidebar-overlay.active {
    display: block;
  }

  @media (max-width: 1024px) {
    .sidebar-overlay.active {
      display: block;
    }
  }
</style>

<button class="sidebar-toggle" onclick="toggleSidebar()">
  <i class="fas fa-bars"></i>
</button>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <h2>
      <i class="fas fa-utensils"></i>
      <?= APP_NAME ?>
    </h2>
    <p>Owner Dashboard</p>
  </div>

  <div class="sidebar-user">
    <div class="user-name"><?= htmlspecialchars($_SESSION['nama']) ?></div>
    <div class="user-role">👑 OWNER</div>
  </div>

  <div class="sidebar-content">
    <ul class="sidebar-menu">
      <li class="menu-item">
        <a href="<?= APP_URL ?>/owner/inside/dashboard.php"
          class="menu-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
          <i class="fas fa-home"></i>
          <span>Dashboard</span>
        </a>
      </li>

      <div class="menu-section">Management</div>

      <li class="menu-item">
        <a href="<?= APP_URL ?>/owner/inside/manage_users.php"
          class="menu-link <?= $current_page === 'manage_users.php' ? 'active' : '' ?>">
          <i class="fas fa-users"></i>
          <span>Manage Users</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="<?= APP_URL ?>/owner/inside/tambah_meja.php"
          class="menu-link <?= $current_page === 'tambah_meja.php' ? 'active' : '' ?>">
          <i class="fas fa-table-cells"></i>
          <span>Meja</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="<?= APP_URL ?>/owner/inside/tambah_menu.php"
          class="menu-link <?= $current_page === 'tambah_menu.php' ? 'active' : '' ?>">
          <i class="fas fa-utensils"></i>
          <span>Menu</span>
        </a>
      </li>

      <div class="menu-section">Pesanan</div>

      <li class="menu-item">
        <a href="<?= APP_URL ?>/owner/inside/pesanan_aktif.php"
          class="menu-link <?= $current_page === 'pesanan_aktif.php' ? 'active' : '' ?>">
          <i class="fas fa-list-check"></i>
          <span>Pesanan Aktif</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="<?= APP_URL ?>/owner/inside/pemesanan.php"
          class="menu-link <?= $current_page === 'pemesanan.php' ? 'active' : '' ?>">
          <i class="fas fa-clipboard-list"></i>
          <span>Riwayat Pesanan</span>
        </a>
      </li>

      <div class="menu-section">Keuangan</div>

      <li class="menu-item">
        <a href="<?= APP_URL ?>/owner/inside/pembelian_bahan.php"
          class="menu-link <?= $current_page === 'pembelian_bahan.php' ? 'active' : '' ?>">
          <i class="fas fa-shopping-cart"></i>
          <span>Pembelian Bahan</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="<?= APP_URL ?>/owner/inside/pembayaran.php"
          class="menu-link <?= $current_page === 'pembayaran.php' ? 'active' : '' ?>">
          <i class="fas fa-receipt"></i>
          <span>Pembayaran</span>
        </a>
      </li>

      <div class="menu-section">Laporan</div>

      <li class="menu-item">
        <a href="<?= APP_URL ?>/owner/inside/transaksi_harian.php"
          class="menu-link <?= $current_page === 'transaksi_harian.php' ? 'active' : '' ?>">
          <i class="fas fa-chart-line"></i>
          <span>Transaksi Harian</span>
        </a>
      </li>

      <li class="menu-item">
        <a href="<?= APP_URL ?>/owner/inside/laporan_penjualan.php"
          class="menu-link <?= $current_page === 'laporan_penjualan.php' ? 'active' : '' ?>">
          <i class="fas fa-file-alt"></i>
          <span>Laporan Penjualan</span>
        </a>
      </li>
    </ul>
  </div>

  <div class="sidebar-footer">
    <form method="POST" action="<?= APP_URL ?>/logout.php">
      <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
      <button type="submit" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        <span>Logout</span>
      </button>
    </form>
  </div>
</div>

<script>
  function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    sidebar.classList.toggle('mobile-active');
    overlay.classList.toggle('active');
  }

  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('.sidebar-toggle');
    const overlay = document.getElementById('sidebarOverlay');

    if (window.innerWidth <= 1024) {
      if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
        sidebar.classList.remove('mobile-active');
        overlay.classList.remove('active');
      }
    }
  });

  // Highlight active menu on page load
  document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const menuLinks = document.querySelectorAll('.menu-link');

    menuLinks.forEach(link => {
      if (link.href === window.location.href) {
        link.classList.add('active');
      }
    });
  });
</script>