<?php
require_once '../includes/init.php';
requireKasir();

// Load models
require_once '../models/Meja.php';
require_once '../models/Menu.php';
require_once '../models/Pesanan.php';

$mejaModel = new Meja();
$menuModel = new Menu();
$pesananModel = new Pesanan();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }

        if ($_POST['action'] === 'create_pesanan') {
            $items = json_decode($_POST['items'], true);

            if (empty($items)) {
                throw new Exception('Keranjang masih kosong');
            }

            $data = [
                'id_meja' => (int)$_POST['id_meja'],
                'dibuat_oleh' => $_SESSION['user_id'],
                'jenis_pesanan' => clean($_POST['jenis_pesanan']),
                'catatan' => clean($_POST['catatan'] ?? ''),
                'items' => $items
            ];

            $idPesanan = $pesananModel->create($data);

            setFlash('success', 'Pesanan berhasil dibuat!');
            redirect(APP_URL . '/kasir/pesanan_aktif.php');
        }
    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
    }
}

// Get data
$mejaTersedia = $mejaModel->getKosong();
$kategoriMenu = $menuModel->getKategori();

$csrf_token = generateCSRFToken();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Pesanan - <?= APP_NAME ?></title>
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
            padding-left: 260px;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .page-header h1 {
            color: #1e293b;
            margin-bottom: 5px;
        }

        .page-header p {
            color: #64748b;
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
            grid-template-columns: 1fr 450px;
            gap: 20px;
            align-items: start;
        }

        @media (max-width: 1400px) {
            .grid {
                grid-template-columns: 1fr 400px;
            }
        }

        @media (max-width: 1024px) {
            body {
                padding-left: 0;
            }

            .grid {
                grid-template-columns: 1fr;
            }
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .grid>div:last-child .section {
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
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

        .form-control,
        .form-select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            overflow-x: auto;
            padding-bottom: 0;
            flex-wrap: wrap;
        }

        .tab {
            padding: 12px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 600;
            color: #64748b;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            white-space: nowrap;
            font-size: 14px;
        }

        .tab:hover {
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }

        .tab.active {
            color: #3b82f6;
            border-bottom-color: #3b82f6;
            background: rgba(59, 130, 246, 0.05);
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
            max-height: 65vh;
            overflow-y: auto;
            padding-right: 10px;
        }

        /* Scrollbar styling */
        .menu-grid::-webkit-scrollbar {
            width: 8px;
        }

        .menu-grid::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 10px;
        }

        .menu-grid::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }

        .menu-grid::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .menu-card {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .menu-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
            transform: translateY(-2px);
        }

        .menu-card img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 8px;
            background: #f1f5f9;
        }

        .menu-card h4 {
            font-size: 13px;
            margin-bottom: 5px;
            color: #1e293b;
            min-height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .menu-card .price {
            color: #f59e0b;
            font-weight: 700;
            font-size: 14px;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 8px;
            background: white;
            gap: 10px;
        }

        .cart-item-info {
            flex: 1;
            min-width: 0;
        }

        .cart-item-info h4 {
            font-size: 13px;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cart-item-info .price {
            color: #64748b;
            font-size: 12px;
        }

        .cart-item-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .qty-control {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .qty-btn {
            width: 28px;
            height: 28px;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transition: all 0.2s;
        }

        .qty-btn:hover {
            background: #f1f5f9;
            border-color: #3b82f6;
            color: #3b82f6;
        }

        .qty-value {
            min-width: 25px;
            text-align: center;
            font-weight: 600;
            font-size: 13px;
        }

        .remove-btn {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 6px 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
        }

        .remove-btn:hover {
            background: #fecaca;
        }

        .cart-summary {
            border-top: 2px solid #e2e8f0;
            padding-top: 15px;
            margin-top: 15px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .summary-row.total {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-primary:disabled {
            background: #94a3b8;
            cursor: not-allowed;
        }

        .empty-cart {
            text-align: center;
            padding: 30px 20px;
            color: #94a3b8;
        }

        .empty-cart i {
            font-size: 40px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .empty-cart p {
            font-size: 13px;
        }

        .search-box {
            margin-bottom: 15px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }

        .search-box input:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
    </style>
</head>

<body>
    <?php include '../sidebar/sidebar_kasir.php'; ?>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-plus-circle"></i> Input Pesanan Baru</h1>
            <p>Buat pesanan untuk pelanggan</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= $flash['message'] ?>
            </div>
        <?php endif; ?>

        <div class="grid">
            <!-- Menu Selection -->
            <div class="section">
                <h3 class="section-title">Pilih Menu</h3>

                <div class="tabs">
                    <button class="tab active" data-kategori="semua">Semua</button>
                    <?php
                    $kategoriResult = $kategoriMenu;
                    if ($kategoriResult):
                        while ($kat = $kategoriResult->fetch_assoc()):
                    ?>
                            <button class="tab" data-kategori="<?= $kat['kategori'] ?>">
                                <?= ucfirst($kat['kategori']) ?> (<?= $kat['jumlah'] ?>)
                            </button>
                    <?php
                        endwhile;
                    endif;
                    ?>
                </div>

                <div class="search-box">
                    <input type="text"
                        id="searchMenu"
                        placeholder="Cari menu..."
                        onkeyup="searchMenu()">
                    <i class="fas fa-search"></i>
                </div>

                <div class="menu-grid" id="menuGrid">
                    <?php
                    $menuResult = $menuModel->getAll();
                    if ($menuResult && $menuResult->num_rows > 0):
                        while ($menu = $menuResult->fetch_assoc()):
                    ?>
                            <div class="menu-card"
                                data-kategori="<?= $menu['kategori'] ?>"
                                data-id="<?= $menu['id_menu'] ?>"
                                data-nama="<?= htmlspecialchars($menu['nama_menu']) ?>"
                                data-harga="<?= $menu['harga'] ?>"
                                onclick="addToCart(this)">

                                <?php if ($menu['gambar']): ?>
                                    <img src="../assets/uploads/<?= $menu['gambar'] ?>" alt="<?= $menu['nama_menu'] ?>">
                                <?php else: ?>
                                    <img src="../assets/placeholder.png" alt="No Image">
                                <?php endif; ?>

                                <h4><?= htmlspecialchars($menu['nama_menu']) ?></h4>
                                <div class="price"><?= rupiah($menu['harga']) ?></div>
                            </div>
                        <?php
                        endwhile;
                    else:
                        ?>
                        <p>Tidak ada menu tersedia</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cart & Form -->
            <div>
                <div class="section">
                    <h3 class="section-title">Keranjang</h3>

                    <form method="POST" action="" id="pesananForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="action" value="create_pesanan">
                        <input type="hidden" name="items" id="itemsInput">

                        <div class="form-group">
                            <label>Tipe Pesanan</label>
                            <select name="jenis_pesanan" class="form-select" required>
                                <option value="dine_in">Dine In</option>
                                <option value="take_away">Take Away</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Pilih Meja</label>
                            <select name="id_meja" class="form-select" required>
                                <option value="">-- Pilih Meja --</option>
                                <?php
                                $mejaResult = $mejaTersedia;
                                if ($mejaResult && $mejaResult->num_rows > 0):
                                    while ($meja = $mejaResult->fetch_assoc()):
                                ?>
                                        <option value="<?= $meja['id_meja'] ?>">
                                            Meja <?= $meja['nomor_meja'] ?>
                                        </option>
                                    <?php
                                    endwhile;
                                else:
                                    ?>
                                    <option value="" disabled>Tidak ada meja kosong</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Catatan (Opsional)</label>
                            <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan pesanan..."></textarea>
                        </div>

                        <div id="cartItems">
                            <div class="empty-cart">
                                <i class="fas fa-shopping-cart"></i>
                                <p>Keranjang masih kosong</p>
                            </div>
                        </div>

                        <div class="cart-summary" id="cartSummary" style="display: none;">
                            <div class="summary-row">
                                <span>Subtotal</span>
                                <span id="subtotal">Rp 0</span>
                            </div>
                            <div class="summary-row total">
                                <span>Total</span>
                                <span id="total">Rp 0</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                            <i class="fas fa-paper-plane"></i> Buat Pesanan
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cart = [];

        // Search menu
        function searchMenu() {
            const searchValue = document.getElementById('searchMenu').value.toLowerCase();
            const cards = document.querySelectorAll('.menu-card');

            cards.forEach(card => {
                const menuName = card.dataset.nama.toLowerCase();
                if (menuName.includes(searchValue)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Tab filtering
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Reset search
                document.getElementById('searchMenu').value = '';

                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                const kategori = this.dataset.kategori;
                const cards = document.querySelectorAll('.menu-card');

                cards.forEach(card => {
                    if (kategori === 'semua' || card.dataset.kategori === kategori) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Add to cart
        function addToCart(element) {
            const id = element.dataset.id;
            const nama = element.dataset.nama;
            const harga = parseFloat(element.dataset.harga);

            const existing = cart.find(item => item.id === id);

            if (existing) {
                existing.qty++;
            } else {
                cart.push({
                    id: id,
                    nama: nama,
                    harga: harga,
                    qty: 1
                });
            }

            updateCart();
        }

        // Update cart display
        function updateCart() {
            const cartItems = document.getElementById('cartItems');
            const cartSummary = document.getElementById('cartSummary');
            const submitBtn = document.getElementById('submitBtn');

            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <p>Keranjang masih kosong</p>
                    </div>
                `;
                cartSummary.style.display = 'none';
                submitBtn.disabled = true;
                return;
            }

            let html = '';
            let total = 0;

            cart.forEach((item, index) => {
                const subtotal = item.harga * item.qty;
                total += subtotal;

                html += `
                    <div class="cart-item">
                        <div class="cart-item-info">
                            <h4>${item.nama}</h4>
                            <div class="price">${formatRupiah(item.harga)} × ${item.qty}</div>
                        </div>
                        <div class="cart-item-actions">
                            <div class="qty-control">
                                <button type="button" class="qty-btn" onclick="updateQty(${index}, -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="qty-value">${item.qty}</span>
                                <button type="button" class="qty-btn" onclick="updateQty(${index}, 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <button type="button" class="remove-btn" onclick="removeItem(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });

            cartItems.innerHTML = html;
            cartSummary.style.display = 'block';
            document.getElementById('subtotal').textContent = formatRupiah(total);
            document.getElementById('total').textContent = formatRupiah(total);
            submitBtn.disabled = false;

            // Update hidden input
            const itemsData = cart.map(item => ({
                id_menu: item.id,
                jumlah: item.qty,
                harga_satuan: item.harga
            }));
            document.getElementById('itemsInput').value = JSON.stringify(itemsData);
        }

        // Update quantity
        function updateQty(index, change) {
            cart[index].qty += change;

            if (cart[index].qty <= 0) {
                cart.splice(index, 1);
            }

            updateCart();
        }

        // Remove item
        function removeItem(index) {
            cart.splice(index, 1);
            updateCart();
        }

        // Format rupiah
        function formatRupiah(angka) {
            return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
    </script>
</body>

</html>