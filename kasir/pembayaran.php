<?php
require_once '../includes/init.php';
requireKasir();

require_once '../models/Pesanan.php';
require_once '../models/Pembayaran.php';

$pesananModel = new Pesanan();
$pembayaranModel = new Pembayaran();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }

        if ($_POST['action'] === 'proses_pembayaran') {
            $data = [
                'id_pesanan' => (int)$_POST['id_pesanan'],
                'metode' => clean($_POST['metode']),
                'jumlah_tagihan' => (float)$_POST['jumlah_tagihan'],
                'jumlah_dibayar' => (float)$_POST['jumlah_dibayar'],
                'bukti_pembayaran' => null,
                'kasir_id' => $_SESSION['user_id']
            ];

            // Upload bukti pembayaran jika QRIS
            if ($data['metode'] === 'qris' && isset($_FILES['bukti_pembayaran'])) {
                if ($_FILES['bukti_pembayaran']['error'] === UPLOAD_ERR_OK) {
                    $data['bukti_pembayaran'] = uploadImage($_FILES['bukti_pembayaran']);
                }
            }

            $idPembayaran = $pembayaranModel->proses($data);

            setFlash('success', 'Pembayaran berhasil diproses!');
            redirect(APP_URL . '/kasir/struk.php?id=' . $idPembayaran);
        }
    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
    }
}

// Get pesanan menunggu pembayaran
$idPesanan = isset($_GET['id']) ? (int)$_GET['id'] : null;

if ($idPesanan) {
    $pesanan = $pesananModel->getById($idPesanan);

    if (!$pesanan) {
        setFlash('error', 'Pesanan tidak ditemukan');
        redirect(APP_URL . '/kasir/pesanan_aktif.php');
    }

    // Check apakah sudah dibayar
    $cekPembayaran = $pembayaranModel->getByPesanan($idPesanan);
    if ($cekPembayaran && $cekPembayaran['status'] === 'sudah_bayar') {
        setFlash('error', 'Pesanan ini sudah dibayar');
        redirect(APP_URL . '/kasir/pesanan_aktif.php');
    }
} else {
    // Get all pesanan menunggu pembayaran
    $pesananMenunggu = $pesananModel->getMenungguPembayaran();
}

$csrf_token = generateCSRFToken();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - <?= APP_NAME ?></title>
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
            max-width: 1200px;
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
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
            color: #1e293b;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .pesanan-info {
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-label {
            color: #64748b;
            font-weight: 500;
        }

        .info-value {
            font-weight: 600;
            color: #1e293b;
        }

        .items-list {
            margin: 20px 0;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: #f8fafc;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        .total-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .total-row.main {
            font-size: 24px;
            font-weight: 700;
            padding-top: 15px;
            border-top: 2px solid rgba(255, 255, 255, 0.3);
        }

        .payment-methods {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .method-card {
            border: 3px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .method-card:hover {
            border-color: #3b82f6;
        }

        .method-card.active {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .method-card i {
            font-size: 40px;
            margin-bottom: 10px;
            color: #3b82f6;
        }

        .method-card h4 {
            color: #1e293b;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #475569;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
        }

        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .form-control-lg {
            font-size: 24px;
            font-weight: 700;
            text-align: center;
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:disabled {
            background: #94a3b8;
            cursor: not-allowed;
            transform: none;
        }

        .kembalian-display {
            background: #d1fae5;
            color: #065f46;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-top: 15px;
        }

        .kembalian-display h3 {
            margin-bottom: 10px;
        }

        .kembalian-value {
            font-size: 32px;
            font-weight: 700;
        }

        .upload-area {
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .upload-area:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .upload-area i {
            font-size: 40px;
            color: #3b82f6;
            margin-bottom: 10px;
        }

        .preview-image {
            max-width: 100%;
            border-radius: 8px;
            margin-top: 15px;
        }

        .pesanan-list {
            display: grid;
            gap: 15px;
        }

        .pesanan-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pesanan-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        @media (max-width: 1024px) {
            .container {
                margin-left: 0;
            }

            .grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include '../sidebar/sidebar_kasir.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-cash-register"></i> Pembayaran</h1>
            <p>Proses pembayaran pesanan</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= $flash['message'] ?>
            </div>
        <?php endif; ?>

        <?php if (isset($pesanan)): ?>
            <!-- Form Pembayaran -->
            <div class="grid">
                <!-- Detail Pesanan -->
                <div class="section">
                    <h3 class="section-title">Detail Pesanan</h3>

                    <div class="pesanan-info">
                        <div class="info-row">
                            <span class="info-label">No. Pesanan</span>
                            <span class="info-value">#<?= $pesanan['id_pesanan'] ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Meja</span>
                            <span class="info-value">Meja <?= $pesanan['nomor_meja'] ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Waktu Pesan</span>
                            <span class="info-value"><?= date('d/m/Y H:i', strtotime($pesanan['waktu_pesan'])) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Jenis Pesanan</span>
                            <span class="info-value"><?= ucfirst($pesanan['jenis_pesanan']) ?></span>
                        </div>
                    </div>

                    <h4 style="margin: 20px 0 10px; color: #475569;">Item Pesanan</h4>
                    <div class="items-list">
                        <?php foreach ($pesanan['items'] as $item): ?>
                            <div class="item-row">
                                <div>
                                    <strong><?= htmlspecialchars($item['nama_menu']) ?></strong>
                                    <small style="color: #64748b;"> × <?= $item['jumlah'] ?></small>
                                </div>
                                <div style="font-weight: 600;">
                                    <?= rupiah($item['subtotal']) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="total-section">
                        <div class="total-row">
                            <span>Subtotal</span>
                            <span><?= rupiah($pesanan['total_harga']) ?></span>
                        </div>
                        <div class="total-row main">
                            <span>TOTAL TAGIHAN</span>
                            <span id="totalTagihan"><?= rupiah($pesanan['total_harga']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Form Pembayaran -->
                <div class="section">
                    <h3 class="section-title">Metode Pembayaran</h3>

                    <form method="POST" action="" enctype="multipart/form-data" id="paymentForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" value="proses_pembayaran">
                        <input type="hidden" name="id_pesanan" value="<?= $pesanan['id_pesanan'] ?>">
                        <input type="hidden" name="jumlah_tagihan" value="<?= $pesanan['total_harga'] ?>">
                        <input type="hidden" name="metode" id="metodeInput">

                        <div class="payment-methods">
                            <div class="method-card" data-method="qris" onclick="selectMethod('qris')">
                                <i class="fas fa-qrcode"></i>
                                <h4>QRIS</h4>
                                <p style="font-size: 12px; color: #64748b; margin-top: 5px;">Scan QR Code</p>
                            </div>
                            <div class="method-card" data-method="cash" onclick="selectMethod('cash')">
                                <i class="fas fa-money-bill-wave"></i>
                                <h4>Cash</h4>
                                <p style="font-size: 12px; color: #64748b; margin-top: 5px;">Tunai</p>
                            </div>
                        </div>

                        <!-- QRIS Section -->
                        <div id="qrisSection" style="display: none;">
                            <div class="form-group">
                                <label>Upload Bukti Pembayaran QRIS</label>
                                <div class="upload-area" onclick="document.getElementById('buktiFile').click()">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Klik untuk upload screenshot pembayaran</p>
                                    <small style="color: #64748b;">JPG, PNG, max 5MB</small>
                                </div>
                                <input type="file"
                                    id="buktiFile"
                                    name="bukti_pembayaran"
                                    accept="image/*"
                                    style="display: none;"
                                    onchange="previewImage(this)">
                                <img id="preview" class="preview-image" style="display: none;">
                            </div>
                            <input type="hidden" name="jumlah_dibayar" value="<?= $pesanan['total_harga'] ?>">
                        </div>

                        <!-- Cash Section -->
                        <div id="cashSection" style="display: none;">
                            <div class="form-group">
                                <label>Jumlah Uang Diterima</label>
                                <input type="number"
                                    name="jumlah_dibayar"
                                    id="jumlahDibayar"
                                    class="form-control form-control-lg"
                                    placeholder="0"
                                    min="<?= $pesanan['total_harga'] ?>"
                                    step="1000"
                                    oninput="hitungKembalian()">
                            </div>

                            <!-- Quick buttons -->
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px;">
                                <?php
                                $suggestions = [50000, 100000, 200000];
                                foreach ($suggestions as $amount):
                                ?>
                                    <button type="button"
                                        class="btn"
                                        style="background: #f1f5f9; color: #1e293b; padding: 10px;"
                                        onclick="setAmount(<?= $amount ?>)">
                                        <?= rupiah($amount) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <div id="kembalianDisplay" style="display: none;" class="kembalian-display">
                                <h3>Kembalian</h3>
                                <div class="kembalian-value" id="kembalianValue">Rp 0</div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                            <i class="fas fa-check-circle"></i> Proses Pembayaran
                        </button>
                    </form>
                </div>
            </div>

        <?php else: ?>
            <!-- List Pesanan Menunggu Pembayaran -->
            <div class="section">
                <h3 class="section-title">Pesanan Menunggu Pembayaran</h3>

                <div class="pesanan-list">
                    <?php
                    if ($pesananMenunggu && $pesananMenunggu->num_rows > 0):
                        while ($p = $pesananMenunggu->fetch_assoc()):
                    ?>
                            <div class="pesanan-card" onclick="location.href='?id=<?= $p['id_pesanan'] ?>'">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div>
                                        <h3 style="margin-bottom: 5px;">Pesanan #<?= $p['id_pesanan'] ?></h3>
                                        <p style="color: #64748b; margin: 0;">
                                            Meja <?= $p['nomor_meja'] ?> •
                                            <?= date('H:i', strtotime($p['waktu_pesan'])) ?>
                                        </p>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 24px; font-weight: 700; color: #f59e0b;">
                                            <?= rupiah($p['total_harga']) ?>
                                        </div>
                                        <?php if ($p['metode_pembayaran']): ?>
                                            <small style="color: #64748b;">
                                                <?= strtoupper($p['metode_pembayaran']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php
                        endwhile;
                    else:
                        ?>
                        <div style="text-align: center; padding: 40px; color: #94a3b8;">
                            <i class="fas fa-receipt" style="font-size: 48px; margin-bottom: 15px;"></i>
                            <p>Tidak ada pesanan yang menunggu pembayaran</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const totalTagihan = <?= isset($pesanan) ? $pesanan['total_harga'] : 0 ?>;

        function selectMethod(method) {
            // Remove active class from all
            document.querySelectorAll('.method-card').forEach(card => {
                card.classList.remove('active');
            });

            // Add active class
            document.querySelector(`[data-method="${method}"]`).classList.add('active');

            // Set hidden input
            document.getElementById('metodeInput').value = method;

            // Show/hide sections
            if (method === 'qris') {
                document.getElementById('qrisSection').style.display = 'block';
                document.getElementById('cashSection').style.display = 'none';
                document.getElementById('submitBtn').disabled = false;
            } else {
                document.getElementById('qrisSection').style.display = 'none';
                document.getElementById('cashSection').style.display = 'block';
                document.getElementById('submitBtn').disabled = true;
            }
        }

        function setAmount(amount) {
            document.getElementById('jumlahDibayar').value = amount;
            hitungKembalian();
        }

        function hitungKembalian() {
            const dibayar = parseFloat(document.getElementById('jumlahDibayar').value) || 0;
            const kembalian = dibayar - totalTagihan;

            if (dibayar >= totalTagihan) {
                document.getElementById('kembalianDisplay').style.display = 'block';
                document.getElementById('kembalianValue').textContent = formatRupiah(kembalian);
                document.getElementById('submitBtn').disabled = false;
            } else {
                document.getElementById('kembalianDisplay').style.display = 'none';
                document.getElementById('submitBtn').disabled = true;
            }
        }

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('preview');
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function formatRupiah(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    </script>
</body>

</html>