<?php
require_once '../../includes/init.php';
requireOwner();

require_once ROOT_PATH . '/assets/phpqrcode/qrlib.php';


$db = Database::getInstance();

// Handle POST Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Verify CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
      throw new Exception('Invalid CSRF token');
    }

    if (isset($_POST['tambah_meja'])) {
      $nomor_meja = clean($_POST['nomor_meja']);

      // Validate
      $validation = Validator::validateMeja(['nomor_meja' => $nomor_meja]);
      if (!$validation['valid']) {
        throw new Exception(implode(', ', $validation['errors']));
      }

      // Check duplicate
      $check = $db->query("SELECT id_meja FROM meja WHERE nomor_meja = ?", 's', [$nomor_meja]);
      if ($check && $check->num_rows > 0) {
        throw new Exception('Nomor meja sudah ada!');
      }

      // Generate unique code
      $kode_unik = generateUniqueCode('MEJA_');
      $qrcode_dir = ROOT_PATH . '/assets/qrcode/';
      $qrcode_path = 'assets/qrcode/' . $kode_unik . '.png';

      // Create QR directory if not exists
      if (!is_dir($qrcode_dir)) {
        mkdir($qrcode_dir, 0755, true);
      }

      // Generate QR Code
      $qr_data = APP_URL . "/customer/order.php?meja=" . urlencode($kode_unik);
      QRcode::png($qr_data, ROOT_PATH . '/' . $qrcode_path, QR_ECLEVEL_L, 6, 2);

      // Insert to database
      $sql = "INSERT INTO meja (nomor_meja, kode_unik, status_meja, qrcode_url, last_update)
                    VALUES (?, ?, 'kosong', ?, NOW())";

      $db->insert($sql, 'sss', [$nomor_meja, $kode_unik, $qrcode_path]);

      logActivity($_SESSION[SESSION_USER_ID], 'tambah_meja', "Tambah meja: $nomor_meja");
      setFlash('success', 'Meja berhasil ditambahkan beserta QR Code!');
      redirect(APP_URL . '/owner/inside/tambah_meja.php');
    } elseif (isset($_POST['edit_meja'])) {
      $id_meja = (int)$_POST['id_meja'];
      $nomor_meja = clean($_POST['nomor_meja']);
      $status_meja = clean($_POST['status_meja']);

      // Validate
      $validation = Validator::validateMeja([
        'nomor_meja' => $nomor_meja,
        'status_meja' => $status_meja
      ]);
      if (!$validation['valid']) {
        throw new Exception(implode(', ', $validation['errors']));
      }

      // Update
      $sql = "UPDATE meja 
                    SET nomor_meja = ?, status_meja = ?, last_update = NOW() 
                    WHERE id_meja = ?";

      $db->execute($sql, 'ssi', [$nomor_meja, $status_meja, $id_meja]);

      logActivity($_SESSION[SESSION_USER_ID], 'edit_meja', "Edit meja ID: $id_meja");
      setFlash('success', 'Data meja berhasil diperbarui!');
      redirect(APP_URL . '/owner/inside/tambah_meja.php');
    }
  } catch (Exception $e) {
    setFlash('error', $e->getMessage());
    redirect(APP_URL . '/owner/inside/tambah_meja.php');
  }
}

// Handle GET Delete
if (isset($_GET['hapus'])) {
  try {
    $id_meja = (int)$_GET['hapus'];

    // Get meja data for QR deletion
    $mejaData = $db->query("SELECT qrcode_url FROM meja WHERE id_meja = ?", 'i', [$id_meja]);

    if ($mejaData && $mejaData->num_rows > 0) {
      $meja = $mejaData->fetch_assoc();

      // Delete meja
      $db->execute("DELETE FROM meja WHERE id_meja = ?", 'i', [$id_meja]);

      // Delete QR file
      if ($meja['qrcode_url'] && file_exists(ROOT_PATH . '/' . $meja['qrcode_url'])) {
        @unlink(ROOT_PATH . '/' . $meja['qrcode_url']);
      }

      logActivity($_SESSION[SESSION_USER_ID], 'hapus_meja', "Hapus meja ID: $id_meja");
      setFlash('success', 'Meja berhasil dihapus!');
    } else {
      setFlash('error', 'Meja tidak ditemukan');
    }
  } catch (Exception $e) {
    setFlash('error', 'Gagal menghapus meja: ' . $e->getMessage());
  }

  redirect(APP_URL . '/owner/inside/tambah_meja.php');
}

// Get all meja
$meja = $db->query("SELECT * FROM meja ORDER BY nomor_meja ASC");
$mejaArray = [];
if ($meja) {
  while ($row = $meja->fetch_assoc()) {
    $mejaArray[] = $row;
  }
}

// Statistics
$total = count($mejaArray);
$kosong = count(array_filter($mejaArray, fn($m) => $m['status_meja'] === 'kosong'));
$terisi = count(array_filter($mejaArray, fn($m) => $m['status_meja'] === 'terisi'));
$menunggu = count(array_filter($mejaArray, fn($m) => $m['status_meja'] === 'menunggu_pembayaran'));

$csrf_token = generateCSRFToken();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kelola Meja - <?= APP_NAME ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link href="<?= APP_URL ?>/css/owner/tambah_meja.css" rel="stylesheet">
</head>

<body>

  <?php include ROOT_PATH . '/sidebar/sidebar.php'; ?>

  <div class="main-content">
    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
        <?= $flash['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="header-page mb-4">
      <div>
        <h5 class="fw-semibold mb-1">Input & Kelola Meja</h5>
        <p class="text-muted small mb-0">Tambah dan atur meja restoran</p>
      </div>
      <button class="btn btn-tambah text-white" data-bs-toggle="modal" data-bs-target="#tambahMejaModal">
        <i class="bi bi-plus-lg me-1"></i> Tambah Meja
      </button>
    </div>

    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card-info">
          <p class="text-muted mb-1">Total Meja</p>
          <h4><?= $total ?></h4>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-info">
          <p class="text-muted mb-1">Kosong</p>
          <h4 class="text-success"><?= $kosong ?></h4>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-info">
          <p class="text-muted mb-1">Terisi</p>
          <h4 class="text-primary"><?= $terisi ?></h4>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card-info">
          <p class="text-muted mb-1">Menunggu Bayar</p>
          <h4 class="text-warning"><?= $menunggu ?></h4>
        </div>
      </div>
    </div>

    <div class="table-card">
      <h6 class="fw-semibold mb-3">Daftar Meja</h6>
      <div class="row g-3">
        <?php if (!empty($mejaArray)): ?>
          <?php foreach ($mejaArray as $m): ?>
            <div class="col-md-3">
              <div class="card shadow-sm border rounded p-3 bg-white">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="mb-0">
                    <i class="bi bi-grid-3x3-gap me-1"></i>
                    <?= htmlspecialchars($m['nomor_meja']) ?>
                  </h6>
                  <span class="status <?= htmlspecialchars($m['status_meja']) ?>">
                    <?= ucfirst(str_replace('_', ' ', $m['status_meja'])) ?>
                  </span>
                </div>
                <img src="<?= APP_URL . '/' . htmlspecialchars($m['qrcode_url']) ?>" class="img-fluid rounded mt-2" alt="QR Code">
                <div class="d-flex justify-content-between mt-3">
                  <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal" data-bs-target="#editMejaModal<?= $m['id_meja'] ?>">
                    <i class="bi bi-pencil-square me-1"></i>Edit
                  </button>
                  <a href="?hapus=<?= $m['id_meja'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Yakin ingin menghapus meja ini?')">
                    <i class="bi bi-trash"></i>
                  </a>
                </div>
              </div>
            </div>

            <!-- Modal Edit -->
            <div class="modal fade" id="editMejaModal<?= $m['id_meja'] ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Meja <?= htmlspecialchars($m['nomor_meja']) ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="id_meja" value="<?= $m['id_meja'] ?>">
                      <div class="mb-3">
                        <label class="form-label">Nomor Meja <span class="text-danger">*</span></label>
                        <input type="text" name="nomor_meja" class="form-control" value="<?= htmlspecialchars($m['nomor_meja']) ?>" required maxlength="20">
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Status Meja <span class="text-danger">*</span></label>
                        <select name="status_meja" class="form-select" required>
                          <option value="kosong" <?= $m['status_meja'] == 'kosong' ? 'selected' : '' ?>>Kosong</option>
                          <option value="terisi" <?= $m['status_meja'] == 'terisi' ? 'selected' : '' ?>>Terisi</option>
                          <option value="menunggu_pembayaran" <?= $m['status_meja'] == 'menunggu_pembayaran' ? 'selected' : '' ?>>Menunggu Pembayaran</option>
                          <option value="selesai" <?= $m['status_meja'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                        </select>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                      <button type="submit" name="edit_meja" class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12">
            <div class="alert alert-info">
              <i class="bi bi-info-circle me-2"></i>
              Belum ada meja. Tambahkan meja pertama Anda!
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Modal Tambah -->
  <div class="modal fade" id="tambahMejaModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="">
          <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
          <div class="modal-header">
            <h5 class="modal-title">Tambah Meja Baru</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Nomor Meja <span class="text-danger">*</span></label>
              <input type="text" name="nomor_meja" class="form-control" placeholder="Contoh: Meja 5" required maxlength="20" autofocus>
              <small class="text-muted">QR Code akan dibuat otomatis</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" name="tambah_meja" class="btn btn-primary">
              <i class="bi bi-plus-circle me-1"></i> Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>