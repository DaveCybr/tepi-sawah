<?php

/**
 * Meja Model
 * Handle semua operasi terkait meja
 */

class Meja
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all meja
     */
    public function getAll()
    {
        $sql = "SELECT m.*,
                       CASE 
                           WHEN m.status_meja = 'terisi' THEN p.id_pesanan
                           ELSE NULL
                       END as pesanan_aktif,
                       CASE 
                           WHEN m.status_meja = 'terisi' THEN p.total_harga
                           ELSE 0
                       END as total_tagihan
                FROM meja m
                LEFT JOIN pesanan p ON m.id_meja = p.id_meja 
                    AND p.status_pesanan NOT IN ('selesai', 'dibayar', 'dibatalkan')
                ORDER BY m.nomor_meja ASC";

        return $this->db->query($sql);
    }

    /**
     * Get meja by ID
     */
    public function getById($idMeja)
    {
        $sql = "SELECT * FROM meja WHERE id_meja = ?";
        $result = $this->db->query($sql, 'i', [$idMeja]);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Get meja by kode unik (untuk customer scan QR)
     */
    public function getByKode($kodeUnik)
    {
        $sql = "SELECT * FROM meja WHERE kode_unik = ?";
        $result = $this->db->query($sql, 's', [$kodeUnik]);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Create meja baru
     */
    public function create($nomorMeja)
    {
        // Generate kode unik
        $kodeUnik = 'MEJA-' . strtoupper(uniqid());

        $sql = "INSERT INTO meja (nomor_meja, kode_unik, status_meja) 
                VALUES (?, ?, 'kosong')";

        return $this->db->insert($sql, 'ss', [$nomorMeja, $kodeUnik]);
    }

    /**
     * Update meja
     */
    public function update($idMeja, $data)
    {
        $sql = "UPDATE meja 
                SET nomor_meja = ?, status_meja = ?, last_update = NOW()
                WHERE id_meja = ?";

        return $this->db->execute($sql, 'ssi', [
            $data['nomor_meja'],
            $data['status_meja'],
            $idMeja
        ]);
    }

    /**
     * Update status meja
     */
    public function updateStatus($idMeja, $status)
    {
        $sql = "UPDATE meja SET status_meja = ?, last_update = NOW() WHERE id_meja = ?";
        return $this->db->execute($sql, 'si', [$status, $idMeja]);
    }

    /**
     * Delete meja
     */
    public function delete($idMeja)
    {
        // Check apakah meja sedang digunakan
        $meja = $this->getById($idMeja);

        if ($meja && $meja['status_meja'] !== 'kosong') {
            throw new Exception('Tidak dapat menghapus meja yang sedang digunakan');
        }

        $sql = "DELETE FROM meja WHERE id_meja = ?";
        return $this->db->execute($sql, 'i', [$idMeja]);
    }

    /**
     * Get statistik meja
     */
    public function getStatistik()
    {
        $sql = "SELECT 
                    COUNT(*) as total_meja,
                    COUNT(CASE WHEN status_meja = 'kosong' THEN 1 END) as kosong,
                    COUNT(CASE WHEN status_meja = 'terisi' THEN 1 END) as terisi,
                    COUNT(CASE WHEN status_meja = 'menunggu_pembayaran' THEN 1 END) as menunggu_pembayaran,
                    COUNT(CASE WHEN status_meja = 'selesai' THEN 1 END) as selesai
                FROM meja";

        $result = $this->db->query($sql);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Reset status meja (untuk end of day)
     */
    public function resetStatus()
    {
        $sql = "UPDATE meja 
                SET status_meja = 'kosong', last_update = NOW() 
                WHERE status_meja = 'selesai'";

        return $this->db->execute($sql);
    }

    /**
     * Get meja kosong
     */
    public function getKosong()
    {
        $sql = "SELECT * FROM meja 
                WHERE status_meja = 'kosong' 
                ORDER BY nomor_meja ASC";

        return $this->db->query($sql);
    }

    /**
     * Get meja terisi dengan detail pesanan
     */
    public function getTerisiWithPesanan()
    {
        $sql = "SELECT m.*, p.id_pesanan, p.total_harga, p.waktu_pesan,
                       p.status_pesanan,
                       COUNT(dp.id_detail) as jumlah_item
                FROM meja m
                INNER JOIN pesanan p ON m.id_meja = p.id_meja
                LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
                WHERE m.status_meja = 'terisi'
                  AND p.status_pesanan NOT IN ('selesai', 'dibayar', 'dibatalkan')
                GROUP BY m.id_meja, p.id_pesanan
                ORDER BY m.nomor_meja ASC";

        return $this->db->query($sql);
    }
}
