<?php
require_once '../includes/init.php';
requireKasir();

require_once '../models/Pembayaran.php';

$pembayaranModel = new Pembayaran();

$idPembayaran = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$idPembayaran) {
    setFlash('error', 'ID pembayaran tidak valid');
    redirect(APP_URL . '/kasir/pembayaran.php');
}

$strukData = $pembayaranModel->generateStruk($idPembayaran);

if (!$strukData) {
    setFlash('error', 'Data pembayaran tidak ditemukan');
    redirect(APP_URL . '/kasir/pembayaran.php');
}

$pembayaran = $strukData['pembayaran'];
$pesanan = $strukData['pesanan'];
$items = $strukData['items'];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pembayaran #<?= $pembayaran['id_pembayaran'] ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 20px;
        }

        .struk-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            border-bottom: 2px dashed #333;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 12px;
            color: #666;
        }

        .info-section {
            margin-bottom: 20px;
            font-size: 13px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .items-section {
            margin: 20px 0;
            border-top: 2px dashed #333;
            border-bottom: 2px dashed #333;
            padding: 15px 0;
        }

        .item {
            margin-bottom: 10px;
        }

        .item-header {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
        }

        .item-detail {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: #666;
            margin-top: 3px;
        }

        .total-section {
            margin-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .total-row.grand {
            font-size: 18px;
            font-weight: bold;
            padding-top: 10px;
            border-top: 2px solid #333;
            margin-top: 10px;
        }

        .payment-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #333;
            font-size: 12px;
            color: #666;
        }

        .buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-print {
            background: #3b82f6;
            color: white;
        }

        .btn-print:hover {
            background: #2563eb;
        }

        .btn-done {
            background: #10b981;
            color: white;
        }

        .btn-done:hover {
            background: #059669;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .struk-container {
                box-shadow: none;
                max-width: 100%;
            }

            .buttons {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="struk-container">
        <div class="header">
            <h1><?= APP_NAME ?></h1>
            <p>Jl. Raya Wringin, Buduan</p>
            <p>Telp: +62 812 3675 2899</p>
        </div>

        <div class="info-section">
            <div class="info-row">
                <span>No. Struk:</span>
                <strong>#<?= $pembayaran['id_pembayaran'] ?></strong>
            </div>
            <div class="info-row">
                <span>No. Pesanan:</span>
                <strong>#<?= $pesanan['id_pesanan'] ?></strong>
            </div>
            <div class="info-row">
                <span>Meja:</span>
                <strong>Meja <?= $pesanan['nomor_meja'] ?></strong>
            </div>
            <div class="info-row">
                <span>Tanggal:</span>
                <strong><?= date('d/m/Y H:i', strtotime($pembayaran['waktu_pembayaran'])) ?></strong>
            </div>
            <div class="info-row">
                <span>Kasir:</span>
                <strong><?= htmlspecialchars($pesanan['nama_kasir'] ?? 'Kasir') ?></strong>
            </div>
        </div>

        <div class="items-section">
            <?php foreach ($items as $item): ?>
                <div class="item">
                    <div class="item-header">
                        <span><?= htmlspecialchars($item['nama_menu']) ?></span>
                        <span><?= rupiah($item['subtotal']) ?></span>
                    </div>
                    <div class="item-detail">
                        <span><?= $item['jumlah'] ?> × <?= rupiah($item['harga_satuan']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="total-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span><?= rupiah($pembayaran['jumlah_tagihan']) ?></span>
            </div>
            <div class="total-row grand">
                <span>TOTAL:</span>
                <span><?= rupiah($pembayaran['jumlah_tagihan']) ?></span>
            </div>
        </div>

        <div class="payment-info">
            <div class="info-row" style="margin-bottom: 10px;">
                <span>Metode Pembayaran:</span>
                <strong><?= strtoupper($pembayaran['metode']) ?></strong>
            </div>

            <?php if ($pembayaran['metode'] === 'cash'): ?>
                <div class="info-row">
                    <span>Tunai:</span>
                    <strong><?= rupiah($pembayaran['jumlah_dibayar']) ?></strong>
                </div>
                <div class="info-row">
                    <span>Kembalian:</span>
                    <strong><?= rupiah($pembayaran['kembalian']) ?></strong>
                </div>
            <?php else: ?>
                <div class="info-row">
                    <span>Status:</span>
                    <strong style="color: #10b981;">LUNAS</strong>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p><strong>TERIMA KASIH</strong></p>
            <p>Telah berbelanja di <?= APP_NAME ?></p>
            <p style="margin-top: 10px;">Simpan struk ini sebagai bukti pembayaran</p>
        </div>

        <div class="buttons">
            <button class="btn btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Cetak Struk
            </button>
            <button class="btn btn-done" onclick="selesai()">
                <i class="fas fa-check"></i> Selesai
            </button>
        </div>
    </div>

    <script>
        function selesai() {
            if (confirm('Kembali ke dashboard?')) {
                window.location.href = '<?= APP_URL ?>/kasir/dashboard_kasir.php';
            }
        }

        // Auto print on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>

</html>