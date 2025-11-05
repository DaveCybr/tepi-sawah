<?php
require_once '../../includes/init.php';
requireOwner();

$db = Database::getInstance();

// Proses tambah pembelian
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_pembelian'])) {
    try {
        // Verify CSRF
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }

        $nama_bahan = clean($_POST['nama_bahan']);
        $harga = floatval($_POST['harga']);
        $tanggal_beli = clean($_POST['tanggal_beli']);
        $keterangan = clean($_POST['keterangan'] ?? '');
        $bukti_pembayaran = clean($_POST['bukti_pembayaran'] ?? '');

        // Validate
        // Simple inline validation to avoid undefined Validator class
        $errors = [];

        if ($nama_bahan === '' || !is_string($nama_bahan)) {
            $errors[] = 'Nama bahan wajib diisi';
        } elseif (defined('MAX_BAHAN_NAME_LENGTH') && mb_strlen($nama_bahan) > MAX_BAHAN_NAME_LENGTH) {
            $errors[] = 'Nama bahan terlalu panjang';
        }

        if (!is_numeric($harga) || $harga < 0) {
            $errors[] = 'Harga tidak valid';
        }

        if ($tanggal_beli === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_beli)) {
            $errors[] = 'Tanggal beli tidak valid';
        }

        if ($keterangan !== '' && defined('MAX_CATATAN_LENGTH') && mb_strlen($keterangan) > MAX_CATATAN_LENGTH) {
            $errors[] = 'Keterangan terlalu panjang';
        }

        if (count($errors) > 0) {
            throw new Exception(implode(', ', $errors));
        }

        if (!isset($_SESSION[SESSION_USER_ID])) {
            throw new Exception('User tidak terautentikasi');
        }

        $dibuat_oleh = $_SESSION[SESSION_USER_ID];

        $sql = "INSERT INTO pembelian_bahan (nama_bahan, harga, tanggal_beli, keterangan, bukti_pembayaran, dibuat_oleh) 
                VALUES (?, ?, ?, ?, ?, ?)";

        $db->insert($sql, 'sdsssi', [
            $nama_bahan,
            $harga,
            $tanggal_beli,
            $keterangan,
            $bukti_pembayaran,
            $dibuat_oleh
        ]);

        logActivity($dibuat_oleh, 'tambah_pembelian_bahan', "Tambah pembelian: $nama_bahan - " . rupiah($harga));
        setFlash('success', 'Pembelian berhasil ditambahkan!');
        redirect(APP_URL . '/owner/inside/pembelian_bahan.php');
    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
        redirect(APP_URL . '/owner/inside/pembelian_bahan.php');
    }
}

// Proses hapus pembelian
if (isset($_GET['hapus'])) {
    try {
        $id_beli = (int)$_GET['hapus'];

        $sql = "DELETE FROM pembelian_bahan WHERE id_beli = ?";
        $db->execute($sql, 'i', [$id_beli]);

        logActivity($_SESSION[SESSION_USER_ID], 'hapus_pembelian_bahan', "Hapus pembelian ID: $id_beli");
        setFlash('success', 'Pembelian berhasil dihapus!');
    } catch (Exception $e) {
        setFlash('error', 'Gagal menghapus pembelian: ' . $e->getMessage());
    }

    redirect(APP_URL . '/owner/inside/pembelian_bahan.php');
}

// Ambil data pembelian hari ini
$today = date('Y-m-d');
$sql = "SELECT * FROM pembelian_bahan WHERE DATE(tanggal_beli) = ? ORDER BY tanggal_beli DESC";
$result = $db->query($sql, 's', [$today]);

$pembelian_list = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pembelian_list[] = $row;
    }
}

// Hitung summary
$total_pembelian = count($pembelian_list);
$total_pengeluaran = array_sum(array_column($pembelian_list, 'harga'));

$csrf_token = generateCSRFToken();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pembelian Bahan - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= APP_URL ?>/css/owner/pembelian_bahan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>
    <?php include ROOT_PATH . '/sidebar/sidebar.php'; ?>

    <main class="main-content">
        <div class="container">
            <!-- Alert Messages -->
            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?>">
                    <?= $flash['message'] ?>
                    <button class="alert-close" onclick="this.parentElement.remove()">×</button>
                </div>
            <?php endif; ?>

            <div class="page-header">
                <div>
                    <h1>Input Pembelian Bahan</h1>
                    <p>Catat pembelian bahan baku restoran</p>
                </div>
                <button class="btn-tambah" onclick="openModal()">
                    <span class="plus-icon">+</span> Tambah Pembelian
                </button>
            </div>

            <div class="summary-cards">
                <div class="card">
                    <p class="card-label">Total Pembelian Hari Ini</p>
                    <h2 class="card-value"><?= $total_pembelian ?></h2>
                </div>
                <div class="card">
                    <p class="card-label">Total Pengeluaran</p>
                    <h2 class="card-value-amount"><?= rupiah($total_pengeluaran) ?></h2>
                </div>
            </div>

            <div class="table-container">
                <h3>Daftar Pembelian</h3>
                <table class="purchase-table">
                    <thead>
                        <tr>
                            <th>Nama Bahan</th>
                            <th>Harga</th>
                            <th>Tanggal Beli</th>
                            <th>Keterangan</th>
                            <th>Bukti Pembelian</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($pembelian_list) > 0): ?>
                            <?php foreach ($pembelian_list as $pembelian): ?>
                                <tr>
                                    <td><?= htmlspecialchars($pembelian['nama_bahan']) ?></td>
                                    <td><?= rupiah($pembelian['harga']) ?></td>
                                    <td><?= tanggalIndo($pembelian['tanggal_beli']) ?></td>
                                    <td><?= $pembelian['keterangan'] ? htmlspecialchars($pembelian['keterangan']) : '-' ?></td>
                                    <td><?= $pembelian['bukti_pembayaran'] ? htmlspecialchars($pembelian['bukti_pembayaran']) : '-' ?></td>
                                    <td>
                                        <a href="?hapus=<?= $pembelian['id_beli'] ?>"
                                            class="btn-delete"
                                            onclick="return confirm('Apakah Anda yakin ingin menghapus pembelian ini?')">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                                                <path d="M2 4h12M5.333 4V2.667a1.333 1.333 0 0 1 1.334-1.334h2.666a1.333 1.333 0 0 1 1.334 1.334V4m2 0v9.333a1.333 1.333 0 0 1-1.334 1.334H4.667a1.333 1.333 0 0 1-1.334-1.334V4h9.334Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                    Belum ada data pembelian hari ini
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Tambah Pembelian -->
    <div id="modalTambah" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Tambah Pembelian Bahan</h2>
                <button class="btn-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                <div class="form-group">
                    <label for="nama_bahan">Nama Bahan <span class="required">*</span></label>
                    <input type="text"
                        id="nama_bahan"
                        name="nama_bahan"
                        maxlength="<?= MAX_BAHAN_NAME_LENGTH ?>"
                        placeholder="Contoh: Beras 10kg"
                        required>
                </div>

                <div class="form-group">
                    <label for="harga">Harga <span class="required">*</span></label>
                    <input type="number"
                        id="harga"
                        name="harga"
                        placeholder="285000"
                        step="0.01"
                        min="0"
                        required>
                </div>

                <div class="form-group">
                    <label for="tanggal_beli">Tanggal Beli <span class="required">*</span></label>
                    <input type="date"
                        id="tanggal_beli"
                        name="tanggal_beli"
                        value="<?= date('Y-m-d') ?>"
                        max="<?= date('Y-m-d') ?>"
                        required>
                </div>

                <div class="form-group">
                    <label for="keterangan">Keterangan</label>
                    <textarea id="keterangan"
                        name="keterangan"
                        rows="3"
                        maxlength="<?= MAX_CATATAN_LENGTH ?>"
                        placeholder="Masukkan keterangan pembelian..."></textarea>
                </div>

                <div class="form-group">
                    <label for="bukti_pembayaran">Bukti Pembelian</label>
                    <input type="text"
                        id="bukti_pembayaran"
                        name="bukti_pembayaran"
                        maxlength="255"
                        placeholder="Nomor invoice atau kode bukti">
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                    <button type="submit" name="tambah_pembelian" class="btn-submit">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modalTambah').style.display = 'flex';
        }

        function closeModal() {
            const modal = document.getElementById('modalTambah');
            modal.style.display = 'none';
            modal.querySelector('form').reset();
        }

        window.onclick = function(event) {
            const modal = document.getElementById('modalTambah');
            if (event.target === modal) {
                closeModal();
            }
        }

        // Auto hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Prevent double submit
        document.querySelector('form').addEventListener('submit', function() {
            const btn = this.querySelector('.btn-submit');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';
        });
    </script>
</body>

</html>