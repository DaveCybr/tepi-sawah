<?php
ob_start();
require_once '../../includes/init.php';
requireOwner();

$db = Database::getInstance();

// === TAMBAH MENU ===
if (isset($_POST['action']) && $_POST['action'] === 'tambah') {
  try {
    // Verify CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
      throw new Exception('Invalid CSRF token');
    }

    $nama = clean($_POST['nama_menu']);
    $kategori = strtolower(clean($_POST['kategori']));
    $harga = floatval($_POST['harga']);
    $status = clean($_POST['status_menu']);

    // Validate data
    $validation = Validator::validateMenu([
      'nama_menu' => $nama,
      'kategori' => $kategori,
      'harga' => $harga,
      'status_menu' => $status
    ]);

    if (!$validation['valid']) {
      throw new Exception(implode(', ', $validation['errors']));
    }

    // Validate file upload
    if (!isset($_FILES['gambar']) || $_FILES['gambar']['error'] === UPLOAD_ERR_NO_FILE) {
      throw new Exception('Gambar wajib diupload');
    }

    // Upload image dengan validasi keamanan
    $filename = FileUploader::uploadImage($_FILES['gambar']);

    // Insert to database
    $sql = "INSERT INTO menu (nama_menu, kategori, harga, status_menu, gambar) 
                VALUES (?, ?, ?, ?, ?)";

    $idMenu = $db->insert($sql, 'ssdss', [$nama, $kategori, $harga, $status, $filename]);

    logActivity($_SESSION[SESSION_USER_ID], 'tambah_menu', "Tambah menu: $nama");

    ob_end_clean();
    setFlash('success', 'Menu berhasil ditambahkan!');
    redirect(APP_URL . '/owner/inside/tambah_menu.php');
  } catch (Exception $e) {
    ob_end_clean();
    setFlash('error', $e->getMessage());
    redirect(APP_URL . '/owner/inside/tambah_menu.php');
  }
}

// === EDIT MENU ===
if (isset($_POST['action']) && $_POST['action'] === 'edit') {
  try {
    // Verify CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
      throw new Exception('Invalid CSRF token');
    }

    $id = (int)$_POST['id_menu'];
    $nama = clean($_POST['nama_menu']);
    $kategori = strtolower(clean($_POST['kategori']));
    $harga = floatval($_POST['harga']);
    $status = clean($_POST['status_menu']);

    // Validate
    $validation = Validator::validateMenu([
      'nama_menu' => $nama,
      'kategori' => $kategori,
      'harga' => $harga,
      'status_menu' => $status
    ]);

    if (!$validation['valid']) {
      throw new Exception(implode(', ', $validation['errors']));
    }

    // Handle image upload (optional for edit)
    $newFilename = null;
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] !== UPLOAD_ERR_NO_FILE) {
      // Upload new image
      $newFilename = FileUploader::uploadImage($_FILES['gambar']);

      // Delete old image
      $oldData = $db->query("SELECT gambar FROM menu WHERE id_menu = ?", 'i', [$id]);
      if ($oldData && $oldData->num_rows > 0) {
        $oldMenu = $oldData->fetch_assoc();
        if ($oldMenu['gambar']) {
          FileUploader::deleteFile($oldMenu['gambar']);
        }
      }
    }

    // Update database
    if ($newFilename) {
      $sql = "UPDATE menu 
                    SET nama_menu = ?, kategori = ?, harga = ?, status_menu = ?, gambar = ? 
                    WHERE id_menu = ?";
      $db->execute($sql, 'ssdssi', [$nama, $kategori, $harga, $status, $newFilename, $id]);
    } else {
      $sql = "UPDATE menu 
                    SET nama_menu = ?, kategori = ?, harga = ?, status_menu = ? 
                    WHERE id_menu = ?";
      $db->execute($sql, 'ssdsi', [$nama, $kategori, $harga, $status, $id]);
    }

    logActivity($_SESSION[SESSION_USER_ID], 'edit_menu', "Edit menu ID: $id");

    ob_end_clean();
    setFlash('success', 'Menu berhasil diperbarui!');
    redirect(APP_URL . '/owner/inside/tambah_menu.php');
  } catch (Exception $e) {
    ob_end_clean();
    setFlash('error', $e->getMessage());
    redirect(APP_URL . '/owner/inside/tambah_menu.php');
  }
}

// === DELETE MENU ===
if (isset($_GET['hapus'])) {
  try {
    $id = (int)$_GET['hapus'];

    // Get menu data
    $menuData = $db->query("SELECT gambar FROM menu WHERE id_menu = ?", 'i', [$id]);

    if ($menuData && $menuData->num_rows > 0) {
      $menu = $menuData->fetch_assoc();

      // Delete from database
      $db->execute("DELETE FROM menu WHERE id_menu = ?", 'i', [$id]);

      // Delete image file
      if ($menu['gambar']) {
        FileUploader::deleteFile($menu['gambar']);
      }

      logActivity($_SESSION[SESSION_USER_ID], 'hapus_menu', "Hapus menu ID: $id");
      setFlash('success', 'Menu berhasil dihapus!');
    } else {
      setFlash('error', 'Menu tidak ditemukan');
    }
  } catch (Exception $e) {
    setFlash('error', 'Gagal menghapus menu: ' . $e->getMessage());
  }

  redirect(APP_URL . '/owner/inside/tambah_menu.php');
}

// Get all menu
$menus = $db->query("SELECT * FROM menu ORDER BY id_menu DESC");
$csrf_token = generateCSRFToken();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Daftar Menu - <?= APP_NAME ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="<?= APP_URL ?>/css/owner/tambah_menu.css">
</head>

<body>

  <?php include ROOT_PATH . '/sidebar/sidebar.php'; ?>

  <main>
    <div class="content-wrapper">
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>">
          <?= $flash['message'] ?>
          <button class="alert-close" onclick="this.parentElement.remove()">×</button>
        </div>
      <?php endif; ?>

      <div class="topbar">
        <div>
          <h1>Daftar Menu</h1>
          <p>Kelola menu makanan & minuman dengan mudah</p>
        </div>
        <button id="openModal" class="btn-tambah">
          <i class="fa fa-plus"></i> Tambah Menu
        </button>
      </div>

      <div class="tabs">
        <button class="tab active" data-filter="semua">Semua</button>
        <button class="tab" data-filter="makanan">Makanan</button>
        <button class="tab" data-filter="minuman">Minuman</button>
        <button class="tab" data-filter="cemilan">Cemilan</button>
      </div>

      <input type="text" id="searchMenu" class="search-box" placeholder="Cari menu...">

      <div class="grid" id="menuGrid">
        <?php if ($menus && $menus->num_rows > 0): ?>
          <?php while ($row = $menus->fetch_assoc()): ?>
            <div class="card"
              data-id="<?= $row['id_menu'] ?>"
              data-nama="<?= htmlspecialchars($row['nama_menu']) ?>"
              data-kategori="<?= htmlspecialchars($row['kategori']) ?>"
              data-harga="<?= htmlspecialchars($row['harga']) ?>"
              data-status="<?= htmlspecialchars($row['status_menu']) ?>">

              <?php if ($row['status_menu'] === 'nonaktif'): ?>
                <div class="status-badge">Nonaktif</div>
              <?php endif; ?>

              <img src="<?= FileUploader::getFileUrl($row['gambar']) ?>"
                alt="<?= htmlspecialchars($row['nama_menu']) ?>"
                onerror="this.src='<?= APP_URL ?>/assets/images/no-image.png'">

              <h3><?= htmlspecialchars($row['nama_menu']) ?></h3>
              <span class="kategori"><?= ucfirst($row['kategori']) ?></span>
              <div class="harga"><?= rupiah($row['harga']) ?></div>

              <div class="card-actions">
                <button class="editBtn"><i class="fa fa-edit"></i> Edit</button>
                <a href="?hapus=<?= $row['id_menu'] ?>"
                  class="deleteBtn"
                  onclick="return confirm('Yakin hapus menu ini?')">
                  <i class="fa fa-trash"></i> Hapus
                </a>
              </div>
            </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p class="empty-state">Tidak ada menu. Tambahkan menu pertama!</p>
        <?php endif; ?>
      </div>
    </div>
  </main>

  <!-- Modal Tambah -->
  <div class="modal" id="addModal">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h2>Tambah Menu</h2>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="action" value="tambah">

        <label>Nama Menu <span class="required">*</span></label>
        <input name="nama_menu" required maxlength="<?= MAX_MENU_NAME_LENGTH ?>" placeholder="Contoh: Nasi Goreng Special">

        <label>Kategori <span class="required">*</span></label>
        <select name="kategori" required>
          <option value="">Pilih Kategori</option>
          <option value="makanan">Makanan</option>
          <option value="minuman">Minuman</option>
          <option value="cemilan">Cemilan</option>
        </select>

        <label>Harga <span class="required">*</span></label>
        <input type="number" name="harga" min="0" step="0.01" required placeholder="15000">

        <label>Status Aktif</label>
        <div class="toggle-container">
          <label class="switch">
            <input type="checkbox" id="add_status_toggle" checked>
            <span class="slider round"></span>
          </label>
          <span id="add_status_text">Aktif</span>
        </div>
        <input type="hidden" name="status_menu" id="add_status" value="aktif">

        <label>Gambar <span class="required">*</span></label>
        <input type="file" name="gambar" accept="image/*" required>
        <small>Maksimal <?= formatFileSize(MAX_FILE_SIZE) ?>. Format: JPG, PNG, WEBP</small>

        <div class="actions">
          <button type="submit" class="save">
            <i class="fa fa-save"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal Edit -->
  <div class="modal" id="editModal">
    <div class="modal-content">
      <span class="close-btn">&times;</span>
      <h2>Edit Menu</h2>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="id_menu" id="edit_id">

        <label>Nama Menu <span class="required">*</span></label>
        <input name="nama_menu" id="edit_nama" required maxlength="<?= MAX_MENU_NAME_LENGTH ?>">

        <label>Kategori <span class="required">*</span></label>
        <select name="kategori" id="edit_kategori" required>
          <option value="makanan">Makanan</option>
          <option value="minuman">Minuman</option>
          <option value="cemilan">Cemilan</option>
        </select>

        <label>Harga <span class="required">*</span></label>
        <input type="number" name="harga" id="edit_harga" min="0" step="0.01" required>

        <label>Status Aktif</label>
        <div class="toggle-container">
          <label class="switch">
            <input type="checkbox" id="edit_status_toggle">
            <span class="slider round"></span>
          </label>
          <span id="edit_status_text">Nonaktif</span>
        </div>
        <input type="hidden" name="status_menu" id="edit_status" value="nonaktif">

        <label>Ganti Gambar (Opsional)</label>
        <input type="file" name="gambar" accept="image/*">
        <small>Kosongkan jika tidak ingin mengganti gambar</small>

        <div class="actions">
          <button type="submit" class="save">
            <i class="fa fa-save"></i> Perbarui Menu
          </button>
        </div>
      </form>
    </div>
  </div>

  <script src="<?= APP_URL ?>/js/owner/tambah_menu.js"></script>
</body>

</html>
<?php ob_end_flush(); ?>