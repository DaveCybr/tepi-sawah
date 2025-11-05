<?php
require_once '../includes/init.php';
requireOwner();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid CSRF token');
        }

        $db = Database::getInstance();

        switch ($_POST['action']) {
            case 'tambah':
                $nama = clean($_POST['nama']);
                $email = clean($_POST['email']);
                $password = $_POST['password'];
                $role = clean($_POST['role']);

                // Validasi
                if (empty($nama) || empty($email) || empty($password)) {
                    throw new Exception('Semua field wajib diisi');
                }

                if (!validateEmail($email)) {
                    throw new Exception('Format email tidak valid');
                }

                // Check email duplicate
                $check = $db->query("SELECT id_pengguna FROM pengguna WHERE email = ?", 's', [$email]);
                if ($check && $check->num_rows > 0) {
                    throw new Exception('Email sudah terdaftar');
                }

                // Hash password
                $hashedPassword = hashPassword($password);

                // Insert
                $sql = "INSERT INTO pengguna (nama, email, password, role, status_akun) 
                        VALUES (?, ?, ?, ?, 'aktif')";

                $db->insert($sql, 'ssss', [$nama, $email, $hashedPassword, $role]);

                logActivity($_SESSION['user_id'], 'tambah_user', "User baru: {$nama} ({$role})");
                setFlash('success', 'User berhasil ditambahkan');
                break;

            case 'edit':
                $idPengguna = (int)$_POST['id_pengguna'];
                $nama = clean($_POST['nama']);
                $email = clean($_POST['email']);
                $role = clean($_POST['role']);
                $status = clean($_POST['status_akun']);

                // Update without password
                if (empty($_POST['password'])) {
                    $sql = "UPDATE pengguna 
                            SET nama = ?, email = ?, role = ?, status_akun = ?
                            WHERE id_pengguna = ?";

                    $db->execute($sql, 'ssssi', [$nama, $email, $role, $status, $idPengguna]);
                } else {
                    // Update with new password
                    $hashedPassword = hashPassword($_POST['password']);

                    $sql = "UPDATE pengguna 
                            SET nama = ?, email = ?, password = ?, role = ?, status_akun = ?
                            WHERE id_pengguna = ?";

                    $db->execute($sql, 'sssssi', [$nama, $email, $hashedPassword, $role, $status, $idPengguna]);
                }

                logActivity($_SESSION['user_id'], 'edit_user', "Edit user ID: {$idPengguna}");
                setFlash('success', 'User berhasil diupdate');
                break;

            case 'hapus':
                $idPengguna = (int)$_POST['id_pengguna'];

                // Jangan bisa hapus diri sendiri
                if ($idPengguna === $_SESSION['user_id']) {
                    throw new Exception('Tidak dapat menghapus akun sendiri');
                }

                $sql = "DELETE FROM pengguna WHERE id_pengguna = ?";
                $db->execute($sql, 'i', [$idPengguna]);

                logActivity($_SESSION['user_id'], 'hapus_user', "Hapus user ID: {$idPengguna}");
                setFlash('success', 'User berhasil dihapus');
                break;
        }
    } catch (Exception $e) {
        setFlash('error', $e->getMessage());
    }
}

// Get all users
$db = Database::getInstance();
$users = $db->query("SELECT * FROM pengguna ORDER BY dibuat_pada DESC");

$csrf_token = generateCSRFToken();
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - <?= APP_NAME ?></title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            padding: 12px 24px;
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

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8fafc;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            font-weight: 600;
            color: #475569;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.owner {
            background: #fef3c7;
            color: #92400e;
        }

        .badge.kasir {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge.aktif {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.nonaktif {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-btns {
            display: flex;
            gap: 8px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-delete {
            background: #ef4444;
            color: white;
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
            align-items: center;
            justify-content: center;
            z-index: 9999;
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
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            margin-bottom: 20px;
        }

        .modal-header h3 {
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control,
        .form-select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
        }

        .form-control:focus,
        .form-select:focus {
            outline: none;
            border-color: #3b82f6;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .btn-secondary {
            background: #64748b;
            color: white;
        }

        @media (max-width: 1024px) {
            .container {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <?php include '../sidebar/sidebar.php'; ?>

    <div class="container">
        <div class="page-header">
            <div>
                <h1><i class="fas fa-users"></i> Manage Users</h1>
                <p style="color: #64748b; margin-top: 5px;">
                    Kelola akun kasir dan owner
                </p>
            </div>
            <button class="btn btn-primary" onclick="openModal('tambah')">
                <i class="fas fa-plus"></i> Tambah User
            </button>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <?= $flash['message'] ?>
            </div>
        <?php endif; ?>

        <div class="section">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Terakhir Login</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($users && $users->num_rows > 0):
                        while ($user = $users->fetch_assoc()):
                    ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($user['nama']) ?></strong></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="badge <?= $user['role'] ?>">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $user['status_akun'] ?>">
                                        <?= ucfirst($user['status_akun']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?= $user['terakhir_login'] ? date('d/m/Y H:i', strtotime($user['terakhir_login'])) : '-' ?>
                                </td>
                                <td style="text-align: center;">
                                    <div class="action-btns">
                                        <button class="btn-sm btn-edit"
                                            onclick='editUser(<?= json_encode($user) ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <?php if ($user['id_pengguna'] !== $_SESSION['user_id']): ?>
                                            <button class="btn-sm btn-delete"
                                                onclick="hapusUser(<?= $user['id_pengguna'] ?>, '<?= htmlspecialchars($user['nama']) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                    <?php
                        endwhile;
                    endif;
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah -->
    <div class="modal" id="modalTambah">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah User Baru</h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="tambah">

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" class="form-select" required>
                        <option value="kasir">Kasir</option>
                        <option value="owner">Owner</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalTambah')">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit -->
    <div class="modal" id="modalEdit">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit User</h3>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id_pengguna" id="edit_id">

                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" id="edit_nama" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Password Baru (Kosongkan jika tidak ingin ubah)</label>
                    <input type="password" name="password" class="form-control">
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="edit_role" class="form-select" required>
                        <option value="kasir">Kasir</option>
                        <option value="owner">Owner</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status_akun" id="edit_status" class="form-select" required>
                        <option value="aktif">Aktif</option>
                        <option value="nonaktif">Nonaktif</option>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('modalEdit')">
                        Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Update
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Form Hapus -->
    <form method="POST" action="" id="formHapus" style="display: none;">
        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
        <input type="hidden" name="action" value="hapus">
        <input type="hidden" name="id_pengguna" id="hapus_id">
    </form>

    <script>
        function openModal(type) {
            if (type === 'tambah') {
                document.getElementById('modalTambah').classList.add('active');
            }
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function editUser(user) {
            document.getElementById('edit_id').value = user.id_pengguna;
            document.getElementById('edit_nama').value = user.nama;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_status').value = user.status_akun;

            document.getElementById('modalEdit').classList.add('active');
        }

        function hapusUser(id, nama) {
            if (confirm(`Yakin ingin menghapus user "${nama}"?`)) {
                document.getElementById('hapus_id').value = id;
                document.getElementById('formHapus').submit();
            }
        }

        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>

</html>