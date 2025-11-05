<?php

/**
 * Menu Model
 * Handle semua operasi terkait menu
 */

class Menu
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all menu aktif
     */
    public function getAll($kategori = null, $search = null)
    {
        $sql = "SELECT * FROM menu WHERE status_menu = 'aktif'";
        $params = [];
        $types = '';

        if ($kategori) {
            $sql .= " AND kategori = ?";
            $params[] = $kategori;
            $types .= 's';
        }

        if ($search) {
            $sql .= " AND nama_menu LIKE ?";
            $params[] = "%$search%";
            $types .= 's';
        }

        $sql .= " ORDER BY kategori, nama_menu ASC";

        if (!empty($params)) {
            return $this->db->query($sql, $types, $params);
        }

        return $this->db->query($sql);
    }

    /**
     * Get menu by ID
     */
    public function getById($idMenu)
    {
        $sql = "SELECT * FROM menu WHERE id_menu = ?";
        $result = $this->db->query($sql, 'i', [$idMenu]);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Get menu by kategori
     */
    public function getByKategori($kategori)
    {
        $sql = "SELECT * FROM menu 
                WHERE kategori = ? AND status_menu = 'aktif'
                ORDER BY nama_menu ASC";

        return $this->db->query($sql, 's', [$kategori]);
    }

    /**
     * Create menu baru
     */
    public function create($data)
    {
        $sql = "INSERT INTO menu (nama_menu, kategori, harga, status_menu, gambar) 
                VALUES (?, ?, ?, ?, ?)";

        return $this->db->insert($sql, 'ssdss', [
            $data['nama_menu'],
            $data['kategori'],
            $data['harga'],
            $data['status_menu'],
            $data['gambar'] ?? null
        ]);
    }

    /**
     * Update menu
     */
    public function update($idMenu, $data)
    {
        if (isset($data['gambar']) && !empty($data['gambar'])) {
            $sql = "UPDATE menu 
                    SET nama_menu = ?, kategori = ?, harga = ?, status_menu = ?, gambar = ?
                    WHERE id_menu = ?";

            return $this->db->execute($sql, 'ssdssi', [
                $data['nama_menu'],
                $data['kategori'],
                $data['harga'],
                $data['status_menu'],
                $data['gambar'],
                $idMenu
            ]);
        } else {
            $sql = "UPDATE menu 
                    SET nama_menu = ?, kategori = ?, harga = ?, status_menu = ?
                    WHERE id_menu = ?";

            return $this->db->execute($sql, 'ssdsi', [
                $data['nama_menu'],
                $data['kategori'],
                $data['harga'],
                $data['status_menu'],
                $idMenu
            ]);
        }
    }

    /**
     * Toggle status menu
     */
    public function toggleStatus($idMenu)
    {
        $sql = "UPDATE menu 
                SET status_menu = IF(status_menu = 'aktif', 'nonaktif', 'aktif')
                WHERE id_menu = ?";

        return $this->db->execute($sql, 'i', [$idMenu]);
    }

    /**
     * Delete menu
     */
    public function delete($idMenu)
    {
        // Get gambar untuk dihapus
        $menu = $this->getById($idMenu);

        $sql = "DELETE FROM menu WHERE id_menu = ?";
        $result = $this->db->execute($sql, 'i', [$idMenu]);

        // Hapus file gambar jika ada
        if ($result && $menu && $menu['gambar']) {
            deleteFile($menu['gambar']);
        }

        return $result;
    }

    /**
     * Get menu terlaris
     */
    public function getTerlaris($limit = 10)
    {
        $sql = "SELECT m.*, COUNT(dp.id_detail) as total_terjual,
                       SUM(dp.jumlah) as jumlah_terjual
                FROM menu m
                INNER JOIN detail_pesanan dp ON m.id_menu = dp.id_menu
                INNER JOIN pesanan p ON dp.id_pesanan = p.id_pesanan
                WHERE p.status_pesanan IN ('selesai', 'dibayar')
                  AND m.status_menu = 'aktif'
                GROUP BY m.id_menu
                ORDER BY total_terjual DESC
                LIMIT ?";

        return $this->db->query($sql, 'i', [$limit]);
    }

    /**
     * Get kategori menu
     */
    public function getKategori()
    {
        $sql = "SELECT DISTINCT kategori, COUNT(*) as jumlah
                FROM menu 
                WHERE status_menu = 'aktif'
                GROUP BY kategori
                ORDER BY kategori ASC";

        return $this->db->query($sql);
    }

    /**
     * Search menu
     */
    public function search($keyword)
    {
        $sql = "SELECT * FROM menu 
                WHERE status_menu = 'aktif'
                  AND (nama_menu LIKE ? OR kategori LIKE ?)
                ORDER BY nama_menu ASC";

        $keyword = "%$keyword%";
        return $this->db->query($sql, 'ss', [$keyword, $keyword]);
    }
}
