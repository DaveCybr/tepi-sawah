<?php

/**
 * Pembayaran Model
 * Handle semua operasi pembayaran
 */

class Pembayaran
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Proses pembayaran
     */
    public function proses($data)
    {
        $this->db->beginTransaction();

        try {
            // Hitung kembalian
            $kembalian = 0;
            if ($data['metode'] === 'cash') {
                $kembalian = $data['jumlah_dibayar'] - $data['jumlah_tagihan'];
                if ($kembalian < 0) {
                    throw new Exception('Jumlah bayar kurang dari tagihan');
                }
            } else {
                // QRIS - jumlah dibayar = jumlah tagihan
                $data['jumlah_dibayar'] = $data['jumlah_tagihan'];
            }

            // Insert pembayaran
            $sql = "INSERT INTO pembayaran 
                    (id_pesanan, metode, status, jumlah_tagihan, jumlah_dibayar, kembalian, bukti_pembayaran) 
                    VALUES (?, ?, 'sudah_bayar', ?, ?, ?, ?)";

            $idPembayaran = $this->db->insert($sql, 'isddds', [
                $data['id_pesanan'],
                $data['metode'],
                $data['jumlah_tagihan'],
                $data['jumlah_dibayar'],
                $kembalian,
                $data['bukti_pembayaran'] ?? null
            ]);

            if (!$idPembayaran) {
                throw new Exception('Gagal memproses pembayaran');
            }

            // Update status pesanan
            $sqlPesanan = "UPDATE pesanan SET status_pesanan = 'dibayar', metode_bayar = ? WHERE id_pesanan = ?";
            $this->db->execute($sqlPesanan, 'si', [$data['metode'], $data['id_pesanan']]);

            // Update status meja - langsung ke KOSONG setelah dibayar
            $sqlMeja = "UPDATE meja m
                        INNER JOIN pesanan p ON m.id_meja = p.id_meja
                        SET m.status_meja = 'kosong', m.last_update = NOW()
                        WHERE p.id_pesanan = ?";
            $this->db->execute($sqlMeja, 'i', [$data['id_pesanan']]);

            // Log activity
            if (isset($data['kasir_id'])) {
                logActivity($data['kasir_id'], 'pembayaran', "Pembayaran pesanan #{$data['id_pesanan']} - {$data['metode']}");
            }

            $this->db->commit();
            return $idPembayaran;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get pembayaran by pesanan
     */
    public function getByPesanan($idPesanan)
    {
        $sql = "SELECT * FROM pembayaran WHERE id_pesanan = ? LIMIT 1";
        $result = $this->db->query($sql, 'i', [$idPesanan]);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Get pembayaran by ID
     */
    public function getById($idPembayaran)
    {
        $sql = "SELECT pay.*, p.id_meja, m.nomor_meja, p.total_harga
                FROM pembayaran pay
                LEFT JOIN pesanan p ON pay.id_pesanan = p.id_pesanan
                LEFT JOIN meja m ON p.id_meja = m.id_meja
                WHERE pay.id_pembayaran = ?";

        $result = $this->db->query($sql, 'i', [$idPembayaran]);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Get transaksi harian
     */
    public function getTransaksiHarian($tanggal = null)
    {
        if ($tanggal === null) {
            $tanggal = date('Y-m-d');
        }

        $sql = "SELECT pay.*, p.id_meja, m.nomor_meja, p.waktu_pesan,
                       p.jenis_pesanan
                FROM pembayaran pay
                LEFT JOIN pesanan p ON pay.id_pesanan = p.id_pesanan
                LEFT JOIN meja m ON p.id_meja = m.id_meja
                WHERE DATE(pay.waktu_pembayaran) = ?
                  AND pay.status = 'sudah_bayar'
                ORDER BY pay.waktu_pembayaran DESC";

        return $this->db->query($sql, 's', [$tanggal]);
    }

    /**
     * Get statistik pembayaran
     */
    public function getStatistik($tanggal = null)
    {
        if ($tanggal === null) {
            $tanggal = date('Y-m-d');
        }

        $sql = "SELECT 
                    COUNT(*) as total_transaksi,
                    COALESCE(SUM(jumlah_tagihan), 0) as total_pendapatan,
                    COALESCE(SUM(CASE WHEN metode = 'cash' THEN jumlah_tagihan END), 0) as total_cash,
                    COALESCE(SUM(CASE WHEN metode = 'qris' THEN jumlah_tagihan END), 0) as total_qris,
                    COUNT(CASE WHEN metode = 'cash' THEN 1 END) as jumlah_cash,
                    COUNT(CASE WHEN metode = 'qris' THEN 1 END) as jumlah_qris
                FROM pembayaran
                WHERE DATE(waktu_pembayaran) = ?
                  AND status = 'sudah_bayar'";

        $result = $this->db->query($sql, 's', [$tanggal]);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Generate struk pembayaran
     */
    public function generateStruk($idPembayaran)
    {
        $pembayaran = $this->getById($idPembayaran);

        if (!$pembayaran) {
            return null;
        }

        // Get detail pesanan
        $pesananModel = new Pesanan();
        $pesanan = $pesananModel->getById($pembayaran['id_pesanan']);

        return [
            'pembayaran' => $pembayaran,
            'pesanan' => $pesanan,
            'items' => $pesanan['items'] ?? []
        ];
    }

    /**
     * Update bukti pembayaran (untuk QRIS)
     */
    public function updateBukti($idPembayaran, $buktiPembayaran)
    {
        $sql = "UPDATE pembayaran SET bukti_pembayaran = ? WHERE id_pembayaran = ?";
        return $this->db->execute($sql, 'si', [$buktiPembayaran, $idPembayaran]);
    }

    /**
     * Get pendapatan per periode
     */
    public function getPendapatanPeriode($startDate, $endDate)
    {
        $sql = "SELECT DATE(waktu_pembayaran) as tanggal,
                       COUNT(*) as total_transaksi,
                       COALESCE(SUM(jumlah_dibayar), 0) as total_pendapatan,
                       COALESCE(SUM(CASE WHEN metode = 'cash' THEN jumlah_dibayar END), 0) as cash,
                       COALESCE(SUM(CASE WHEN metode = 'qris' THEN jumlah_dibayar END), 0) as qris
                FROM pembayaran
                WHERE DATE(waktu_pembayaran) BETWEEN ? AND ?
                  AND status = 'sudah_bayar'
                GROUP BY DATE(waktu_pembayaran)
                ORDER BY tanggal DESC";

        return $this->db->query($sql, 'ss', [$startDate, $endDate]);
    }
}
