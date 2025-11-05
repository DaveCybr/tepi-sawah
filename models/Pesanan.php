<?php

/**
 * Pesanan Model
 * Handle semua operasi terkait pesanan
 */

class Pesanan
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Buat pesanan baru
     */
    public function create($data)
    {
        $this->db->beginTransaction();

        try {
            // Insert pesanan
            $sql = "INSERT INTO pesanan (id_meja, dibuat_oleh, jenis_pesanan, catatan) 
                    VALUES (?, ?, ?, ?)";

            $idPesanan = $this->db->insert($sql, 'iiss', [
                $data['id_meja'],
                $data['dibuat_oleh'],
                $data['jenis_pesanan'],
                $data['catatan'] ?? ''
            ]);

            if (!$idPesanan) {
                throw new Exception('Gagal membuat pesanan');
            }

            // Insert detail pesanan
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $sqlDetail = "INSERT INTO detail_pesanan (id_pesanan, id_menu, jumlah, harga_satuan, catatan_item) 
                                  VALUES (?, ?, ?, ?, ?)";

                    $this->db->insert($sqlDetail, 'iiids', [
                        $idPesanan,
                        $item['id_menu'],
                        $item['jumlah'],
                        $item['harga_satuan'],
                        $item['catatan_item'] ?? ''
                    ]);
                }
            }

            // Update total harga
            $this->updateTotalHarga($idPesanan);

            // Update status meja
            $this->updateStatusMeja($data['id_meja'], 'terisi');

            $this->db->commit();
            return $idPesanan;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update total harga pesanan
     */
    private function updateTotalHarga($idPesanan)
    {
        $sql = "UPDATE pesanan p 
                SET total_harga = (
                    SELECT COALESCE(SUM(subtotal), 0) 
                    FROM detail_pesanan 
                    WHERE id_pesanan = ?
                )
                WHERE id_pesanan = ?";

        return $this->db->execute($sql, 'ii', [$idPesanan, $idPesanan]);
    }

    /**
     * Update status meja
     */
    private function updateStatusMeja($idMeja, $status)
    {
        $sql = "UPDATE meja SET status_meja = ?, last_update = NOW() WHERE id_meja = ?";
        return $this->db->execute($sql, 'si', [$status, $idMeja]);
    }

    /**
     * Get pesanan by ID
     */
    public function getById($idPesanan)
    {
        $sql = "SELECT p.*, m.nomor_meja, m.kode_unik,
                       u.nama as nama_kasir
                FROM pesanan p
                LEFT JOIN meja m ON p.id_meja = m.id_meja
                LEFT JOIN pengguna u ON p.dibuat_oleh = u.id_pengguna
                WHERE p.id_pesanan = ?";

        $result = $this->db->query($sql, 'i', [$idPesanan]);

        if ($result && $result->num_rows > 0) {
            $pesanan = $result->fetch_assoc();
            $pesanan['items'] = $this->getDetailPesanan($idPesanan);
            return $pesanan;
        }

        return null;
    }

    /**
     * Get detail pesanan (items)
     */
    public function getDetailPesanan($idPesanan)
    {
        $sql = "SELECT dp.*, m.nama_menu, m.kategori, m.gambar
                FROM detail_pesanan dp
                LEFT JOIN menu m ON dp.id_menu = m.id_menu
                WHERE dp.id_pesanan = ?
                ORDER BY dp.id_detail ASC";

        $result = $this->db->query($sql, 'i', [$idPesanan]);

        $items = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }

        return $items;
    }

    /**
     * Get pesanan aktif (belum selesai/dibayar)
     */
    public function getAktif()
    {
        $sql = "SELECT p.*, m.nomor_meja, 
                       COUNT(dp.id_detail) as jumlah_item
                FROM pesanan p
                LEFT JOIN meja m ON p.id_meja = m.id_meja
                LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
                WHERE p.status_pesanan NOT IN ('selesai', 'dibayar', 'dibatalkan')
                GROUP BY p.id_pesanan
                ORDER BY p.waktu_pesan DESC";

        return $this->db->query($sql);
    }

    /**
     * Get pesanan by meja
     */
    public function getByMeja($idMeja)
    {
        $sql = "SELECT p.*, COUNT(dp.id_detail) as jumlah_item
                FROM pesanan p
                LEFT JOIN detail_pesanan dp ON p.id_pesanan = dp.id_pesanan
                WHERE p.id_meja = ? 
                  AND p.status_pesanan NOT IN ('selesai', 'dibayar', 'dibatalkan')
                GROUP BY p.id_pesanan
                ORDER BY p.waktu_pesan DESC
                LIMIT 1";

        $result = $this->db->query($sql, 'i', [$idMeja]);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Update status pesanan
     */
    public function updateStatus($idPesanan, $status)
    {
        $sql = "UPDATE pesanan SET status_pesanan = ? WHERE id_pesanan = ?";
        return $this->db->execute($sql, 'si', [$status, $idPesanan]);
    }

    /**
     * Update status item
     */
    public function updateStatusItem($idDetail, $status)
    {
        $sql = "UPDATE detail_pesanan SET status_item = ? WHERE id_detail = ?";
        return $this->db->execute($sql, 'si', [$status, $idDetail]);
    }

    /**
     * Batalkan pesanan
     */
    public function batalkan($idPesanan, $alasan, $dibatalkanOleh)
    {
        $this->db->beginTransaction();

        try {
            // Update status pesanan
            $this->updateStatus($idPesanan, 'dibatalkan');

            // Insert ke tabel pembatalan
            $sql = "INSERT INTO pembatalan_pesanan (id_pesanan, alasan, dibatalkan_oleh) 
                    VALUES (?, ?, ?)";

            $this->db->insert($sql, 'iss', [$idPesanan, $alasan, $dibatalkanOleh]);

            // Get id_meja untuk update status
            $pesanan = $this->getById($idPesanan);
            if ($pesanan) {
                $this->updateStatusMeja($pesanan['id_meja'], 'kosong');
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get statistik pesanan
     */
    public function getStatistik($tanggal = null)
    {
        if ($tanggal === null) {
            $tanggal = date('Y-m-d');
        }

        $sql = "SELECT 
                    COUNT(CASE WHEN status_pesanan = 'menunggu' THEN 1 END) as menunggu,
                    COUNT(CASE WHEN status_pesanan = 'dimasak' THEN 1 END) as dimasak,
                    COUNT(CASE WHEN status_pesanan = 'siap_disajikan' THEN 1 END) as siap_disajikan,
                    COUNT(CASE WHEN status_pesanan IN ('selesai', 'dibayar') THEN 1 END) as selesai,
                    COUNT(CASE WHEN status_pesanan = 'dibatalkan' THEN 1 END) as dibatalkan,
                    COALESCE(SUM(CASE WHEN status_pesanan IN ('selesai', 'dibayar') THEN total_harga END), 0) as total_pendapatan
                FROM pesanan
                WHERE DATE(waktu_pesan) = ?";

        $result = $this->db->query($sql, 's', [$tanggal]);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Get pesanan menunggu pembayaran
     */
    public function getMenungguPembayaran()
    {
        $sql = "SELECT p.*, m.nomor_meja,
                       pay.status as status_pembayaran,
                       pay.metode as metode_pembayaran
                FROM pesanan p
                LEFT JOIN meja m ON p.id_meja = m.id_meja
                LEFT JOIN pembayaran pay ON p.id_pesanan = pay.id_pesanan
                WHERE m.status_meja = 'menunggu_pembayaran'
                  AND p.status_pesanan = 'selesai'
                ORDER BY p.waktu_pesan ASC";

        return $this->db->query($sql);
    }
}
