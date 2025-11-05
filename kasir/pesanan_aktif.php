<?php
require_once '../includes/init.php';
requireKasir();

require_once '../models/Pesanan.php';
require_once '../models/Meja.php';

$pesananModel = new Pesanan();
$mejaModel = new Meja();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }

        $response = ['success' => false, 'message' => ''];

        switch ($_POST['action']) {
            case 'update_status':
                $idPesanan = (int)$_POST['id_pesanan'];
                $status = clean($_POST['status']);

                $pesananModel->updateStatus($idPesanan, $status);
                logActivity($_SESSION['user_id'], 'update_status_pesanan', "Pesanan #{$idPesanan} → {$status}");

                $response['success'] = true;
                $response['message'] = 'Status pesanan berhasil diupdate';
                break;

            case 'update_status_item':
                $idDetail = (int)$_POST['id_detail'];
                $status = clean($_POST['status']);

                $pesananModel->updateStatusItem($idDetail, $status);

                $response['success'] = true;
                $response['message'] = 'Status item berhasil diupdate';
                break;

            case 'batalkan_pesanan':
                $idPesanan = (int)$_POST['id_pesanan'];
                $alasan = clean($_POST['alasan']);

                $pesananModel->batalkan($idPesanan, $alasan, 'kasir');
                logActivity($_SESSION['user_id'], 'batalkan_pesanan', "Pesanan #{$idPesanan} dibatalkan: {$alasan}");

                $response['success'] = true;
                $response['message'] = 'Pesanan berhasil dibatalkan';
                break;

            case 'siap_bayar':
                $idPesanan = (int)$_POST['id_pesanan'];

                // Update status pesanan ke selesai
                $pesananModel->updateStatus($idPesanan, 'selesai');

                // Update status meja ke menunggu pembayaran
                $pesanan = $pesananModel->getById($idPesanan);
                if ($pesanan) {
                    $mejaModel->updateStatus($pesanan['id_meja'], 'menunggu_pembayaran');
                }

                $response['success'] = true;
                $response['message'] = 'Pesanan siap untuk pembayaran';
                break;
        }

        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Get pesanan aktif
$pesananAktif = $pesananModel->getAktif();
$statistik = $pesananModel->getStatistik();

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Aktif - <?= APP_NAME ?></title>
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

        .refresh-btn {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .refresh-btn:hover {
            background: #2563eb;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .stat-card h4 {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
        }

        .stat-card.menunggu .value {
            color: #f59e0b;
        }

        .stat-card.dimasak .value {
            color: #3b82f6;
        }

        .stat-card.siap .value {
            color: #10b981;
        }

        .stat-card.selesai .value {
            color: #8b5cf6;
        }

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            background: white;
            padding: 15px;
            border-radius: 10px;
        }

        .tab {
            padding: 10px 20px;
            background: #f1f5f9;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: #475569;
            transition: all 0.3s;
        }

        .tab.active {
            background: #3b82f6;
            color: white;
        }

        .pesanan-grid {
            display: grid;
            gap: 20px;
        }

        .pesanan-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #e2e8f0;
        }

        .pesanan-card.menunggu {
            border-left-color: #f59e0b;
        }

        .pesanan-card.diterima {
            border-left-color: #3b82f6;
        }

        .pesanan-card.dimasak {
            border-left-color: #8b5cf6;
        }

        .pesanan-card.siap_disajikan {
            border-left-color: #10b981;
        }

        .pesanan-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .pesanan-id {
            font-weight: 700;
            font-size: 18px;
            color: #1e293b;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.menunggu {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.diterima,
        .status-badge.dimasak {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge.siap_disajikan {
            background: #d1fae5;
            color: #065f46;
        }

        .pesanan-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: 14px;
        }

        .info-item i {
            color: #3b82f6;
        }

        .items-list {
            margin: 15px 0;
        }

        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            background: #f8fafc;
            border-radius: 6px;
            margin-bottom: 8px;
        }

        .item-name {
            font-weight: 500;
        }

        .item-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
        }

        .item-status.menunggu {
            background: #fef3c7;
            color: #92400e;
        }

        .item-status.dimasak {
            background: #dbeafe;
            color: #1e40af;
        }

        .item-status.selesai {
            background: #d1fae5;
            color: #065f46;
        }

        .total-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 2px solid #f1f5f9;
            margin-top: 15px;
        }

        .total-label {
            font-weight: 600;
            color: #64748b;
        }

        .total-value {
            font-size: 24px;
            font-weight: 700;
            color: #f59e0b;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-warning {
            background: #f59e0b;
            color: white;
        }

        .btn-warning:hover {
            background: #d97706;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-secondary {
            background: #64748b;
            color: white;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }

        .empty-state i {
            font-size: 64px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #475569;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #94a3b8;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h3 {
            font-size: 20px;
            color: #1e293b;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
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
            <h1><i class="fas fa-list-check"></i> Pesanan Aktif</h1>
            <button class="refresh-btn" onclick="location.reload()">
                <i class="fas fa-refresh"></i> Refresh
            </button>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card menunggu">
                <h4>Menunggu</h4>
                <div class="value"><?= $statistik['menunggu'] ?? 0 ?></div>
            </div>
            <div class="stat-card dimasak">
                <h4>Dimasak</h4>
                <div class="value"><?= $statistik['dimasak'] ?? 0 ?></div>
            </div>
            <div class="stat-card siap">
                <h4>Siap Disajikan</h4>
                <div class="value"><?= $statistik['siap_disajikan'] ?? 0 ?></div>
            </div>
            <div class="stat-card selesai">
                <h4>Selesai</h4>
                <div class="value"><?= $statistik['selesai'] ?? 0 ?></div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="tabs">
            <button class="tab active" data-status="semua">Semua Pesanan</button>
            <button class="tab" data-status="menunggu">Menunggu</button>
            <button class="tab" data-status="dimasak">Dimasak</button>
            <button class="tab" data-status="siap_disajikan">Siap Disajikan</button>
        </div>

        <!-- Pesanan List -->
        <div class="pesanan-grid" id="pesananGrid">
            <?php
            if ($pesananAktif && $pesananAktif->num_rows > 0):
                while ($pesanan = $pesananAktif->fetch_assoc()):
                    $details = $pesananModel->getDetailPesanan($pesanan['id_pesanan']);
            ?>
                    <div class="pesanan-card <?= $pesanan['status_pesanan'] ?>" data-status="<?= $pesanan['status_pesanan'] ?>">
                        <div class="pesanan-header">
                            <div>
                                <div class="pesanan-id">#<?= $pesanan['id_pesanan'] ?></div>
                                <small style="color: #64748b;">Meja <?= $pesanan['nomor_meja'] ?></small>
                            </div>
                            <span class="status-badge <?= $pesanan['status_pesanan'] ?>">
                                <?= str_replace('_', ' ', $pesanan['status_pesanan']) ?>
                            </span>
                        </div>

                        <div class="pesanan-info">
                            <div class="info-item">
                                <i class="fas fa-clock"></i>
                                <span><?= date('H:i', strtotime($pesanan['waktu_pesan'])) ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-utensils"></i>
                                <span><?= ucfirst($pesanan['jenis_pesanan']) ?></span>
                            </div>
                            <div class="info-item">
                                <i class="fas fa-shopping-bag"></i>
                                <span><?= $pesanan['jumlah_item'] ?> Item</span>
                            </div>
                        </div>

                        <div class="items-list">
                            <?php foreach ($details as $item): ?>
                                <div class="item-row">
                                    <div>
                                        <span class="item-name"><?= $item['nama_menu'] ?></span>
                                        <small style="color: #64748b;"> × <?= $item['jumlah'] ?></small>
                                    </div>
                                    <span class="item-status <?= $item['status_item'] ?>">
                                        <?= $item['status_item'] ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!empty($pesanan['catatan'])): ?>
                            <div style="padding: 10px; background: #fef3c7; border-radius: 6px; margin: 10px 0;">
                                <small style="color: #92400e;">
                                    <i class="fas fa-note-sticky"></i> <?= htmlspecialchars($pesanan['catatan']) ?>
                                </small>
                            </div>
                        <?php endif; ?>

                        <div class="total-section">
                            <span class="total-label">Total</span>
                            <span class="total-value"><?= rupiah($pesanan['total_harga']) ?></span>
                        </div>

                        <div class="action-buttons">
                            <?php if ($pesanan['status_pesanan'] === 'menunggu'): ?>
                                <button class="btn btn-success" onclick="updateStatus(<?= $pesanan['id_pesanan'] ?>, 'diterima')">
                                    <i class="fas fa-check"></i> Terima
                                </button>
                                <button class="btn btn-danger" onclick="openBatalkanModal(<?= $pesanan['id_pesanan'] ?>)">
                                    <i class="fas fa-times"></i> Batalkan
                                </button>
                            <?php elseif ($pesanan['status_pesanan'] === 'diterima'): ?>
                                <button class="btn btn-warning" onclick="updateStatus(<?= $pesanan['id_pesanan'] ?>, 'dimasak')">
                                    <i class="fas fa-fire"></i> Mulai Masak
                                </button>
                            <?php elseif ($pesanan['status_pesanan'] === 'dimasak'): ?>
                                <button class="btn btn-success" onclick="updateStatus(<?= $pesanan['id_pesanan'] ?>, 'siap_disajikan')">
                                    <i class="fas fa-check-double"></i> Siap Disajikan
                                </button>
                            <?php elseif ($pesanan['status_pesanan'] === 'siap_disajikan'): ?>
                                <button class="btn btn-success" onclick="siapBayar(<?= $pesanan['id_pesanan'] ?>)">
                                    <i class="fas fa-cash-register"></i> Siap Bayar
                                </button>
                            <?php endif; ?>

                            <button class="btn btn-secondary" onclick="lihatDetail(<?= $pesanan['id_pesanan'] ?>)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                <?php
                endwhile;
            else:
                ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Tidak Ada Pesanan Aktif</h3>
                    <p>Semua pesanan sudah selesai atau belum ada pesanan baru</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Batalkan -->
    <div class="modal" id="batalkanModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Batalkan Pesanan</h3>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Alasan Pembatalan</label>
                    <textarea id="alasanBatal" class="form-control" rows="3" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button class="btn btn-danger" onclick="confirmBatalkan()">Ya, Batalkan</button>
            </div>
        </div>
    </div>

    <script>
        let currentIdPesanan = null;
        const csrfToken = '<?= $csrf_token ?>';

        // Tab filtering
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const status = this.dataset.status;
                const cards = document.querySelectorAll('.pesanan-card');

                cards.forEach(card => {
                    if (status === 'semua' || card.dataset.status === status) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Update status
        async function updateStatus(idPesanan, status) {
            if (!confirm('Update status pesanan ini?')) return;

            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('id_pesanan', idPesanan);
            formData.append('status', status);
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            }
        }

        // Siap bayar
        async function siapBayar(idPesanan) {
            if (!confirm('Tandai pesanan ini siap untuk pembayaran?')) return;

            const formData = new FormData();
            formData.append('action', 'siap_bayar');
            formData.append('id_pesanan', idPesanan);
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    window.location.href = 'pembayaran.php?id=' + idPesanan;
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            }
        }

        // Open batalkan modal
        function openBatalkanModal(idPesanan) {
            currentIdPesanan = idPesanan;
            document.getElementById('batalkanModal').classList.add('active');
        }

        // Close modal
        function closeModal() {
            document.getElementById('batalkanModal').classList.remove('active');
            document.getElementById('alasanBatal').value = '';
        }

        // Confirm batalkan
        async function confirmBatalkan() {
            const alasan = document.getElementById('alasanBatal').value.trim();

            if (!alasan) {
                alert('Alasan pembatalan wajib diisi');
                return;
            }

            const formData = new FormData();
            formData.append('action', 'batalkan_pesanan');
            formData.append('id_pesanan', currentIdPesanan);
            formData.append('alasan', alasan);
            formData.append('csrf_token', csrfToken);

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    alert(result.message);
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            }

            closeModal();
        }

        // Lihat detail
        function lihatDetail(idPesanan) {
            window.location.href = 'detail_pesanan.php?id=' + idPesanan;
        }

        // Auto refresh every 30 seconds
        setInterval(() => {
            location.reload();
        }, 30000);
    </script>
</body>

</html>