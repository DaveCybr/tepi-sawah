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
    padding: 20px 0;
    z-index: 1000;
    box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
    overflow-y: auto;
  }

  .sidebar-header {
    padding: 0 20px 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    margin-bottom: 20px;
  }

  .sidebar-header h2 {
    font-size: 20px;
    margin-bottom: 5px;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .sidebar-header p {
    font-size: 12px;
    opacity: 0.7;
  }

  .sidebar-user {
    padding: 15px 20px;
    background: rgba(255, 255, 255, 0.05);
    margin: 0 15px 20px;
    border-radius: 10px;
  }

  .sidebar-user .user-name {
    font-weight: 600;
    margin-bottom: 3px;
  }

  .sidebar-user .user-role {
    font-size: 12px;
    opacity: 0.7;
    text-transform: uppercase;
  }

  .sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .menu-item {
    margin-bottom: 5px;
  }

  .menu-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s;
    position: relative;
  }

  .menu-link:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
  }

  .menu-link.active {
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
    border-left: 3px solid #3b82f6;
  }

  .menu-link i {
    width: 20px;
    text-align: center;
  }

  .menu-section {
    padding: 15px 20px 5px;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.5;
    font-weight: 600;
  }

  .sidebar-footer {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.2);
  }

  .logout-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    width: 100%;
    padding: 12px;
    background: rgba(239, 68, 68, 0.2);
    color: #fca5a5;
    border: 1px solid rgba(239, 68, 68, 0.3);
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s;
  }

  .logout-btn:hover {
    background: rgba(239, 68, 68, 0.3);
    color: white;
  }

  /* Mobile Toggle */
  .sidebar-toggle {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1001;
    background: #3b82f6;
    color: white;
    border: none;
    padding: 10px 15px;
    border-radius: 8px;
    cursor: pointer;
  }

  @media (max-width: 1024px) {
    .sidebar {
      transform: translateX(-100%);
      transition: transform 0.3s;
    }

    .sidebar.mobile-active {
      transform: translateX(0);
    }

    .sidebar-toggle {
      display: block;
    }
  }
</style>

<button class="sidebar-toggle" onclick="toggleSidebar()">
  <i class="fas fa-bars"></i>
</button>

<div class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <h2>
      <i class="fas fa-utensils"></i>
      <?= APP_NAME ?>
    </h2>
    <p>Kasir Dashboard</p>
  </div>

  <div class="sidebar-user">
    <div class="user-name"><?= htmlspecialchars($_SESSION['nama']) ?></div>
    <div class="user-role">Kasir</div>
  </div>

  <ul class="sidebar-menu">
    <li class="menu-item">
      <a href="<?= APP_URL ?>/kasir/dashboard_kasir.php"
        class="menu-link <?= $current_page === 'dashboard_kasir.php' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <span>Dashboard</span>
      </a>
    </li>

    <div class="menu-section">Pesanan</div>

    <li class="menu-item">
      <a href="<?= APP_URL ?>/kasir/input_pesanan.php"
        class="menu-link <?= $current_page === 'input_pesanan.php' ? 'active' : '' ?>">
        <i class="fas fa-plus-circle"></i>
        <span>Input Pesanan</span>
      </a>
    </li>

    <li class="menu-item">
      <a href="<?= APP_URL ?>/kasir/pesanan_aktif.php"
        class="menu-link <?= $current_page === 'pesanan_aktif.php' ? 'active' : '' ?>">
        <i class="fas fa-list-check"></i>
        <span>Pesanan Aktif</span>
      </a>
    </li>

    <div class="menu-section">Pembayaran</div>

    <li class="menu-item">
      <a href="<?= APP_URL ?>/kasir/pembayaran.php"
        class="menu-link <?= $current_page === 'pembayaran.php' ? 'active' : '' ?>">
        <i class="fas fa-cash-register"></i>
        <span>Pembayaran</span>
      </a>
    </li>

    <li class="menu-item">
      <a href="<?= APP_URL ?>/kasir/transaksi_harian.php"
        class="menu-link <?= $current_page === 'transaksi_harian.php' ? 'active' : '' ?>">
        <i class="fas fa-receipt"></i>
        <span>Transaksi Harian</span>
      </a>
    </li>

    <div class="menu-section">Lainnya</div>

    <li class="menu-item">
      <a href="<?= APP_URL ?>/kasir/meja.php"
        class="menu-link <?= $current_page === 'meja.php' ? 'active' : '' ?>">
        <i class="fas fa-table-cells"></i>
        <span>Status Meja</span>
      </a>
    </li>

    <li class="menu-item">
      <a href="<?= APP_URL ?>/kasir/reset_meja.php"
        class="menu-link <?= $current_page === 'reset_meja.php' ? 'active' : '' ?>">
        <i class="fas fa-broom"></i>
        <span>Reset Meja</span>
      </a>
    </li>
  </ul>

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
    document.getElementById('sidebar').classList.toggle('mobile-active');
  }

  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.querySelector('.sidebar-toggle');

    if (window.innerWidth <= 1024) {
      if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
        sidebar.classList.remove('mobile-active');
      }
    }
  });
</script>