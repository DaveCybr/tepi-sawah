<?php

/**
 * Input Validation Helper Class
 * Centralized validation untuk semua input
 */

class Validator
{
    /**
     * Validate menu data
     * 
     * @param array $data
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateMenu($data)
    {
        $errors = [];

        // Nama menu
        if (empty($data['nama_menu'])) {
            $errors[] = 'Nama menu wajib diisi';
        } elseif (strlen($data['nama_menu']) > MAX_MENU_NAME_LENGTH) {
            $errors[] = 'Nama menu maksimal ' . MAX_MENU_NAME_LENGTH . ' karakter';
        }

        // Kategori
        $validKategori = ['makanan', 'minuman', 'cemilan'];
        if (empty($data['kategori'])) {
            $errors[] = 'Kategori wajib dipilih';
        } elseif (!in_array(strtolower($data['kategori']), $validKategori)) {
            $errors[] = 'Kategori tidak valid';
        }

        // Harga
        if (!isset($data['harga'])) {
            $errors[] = 'Harga wajib diisi';
        } elseif (!is_numeric($data['harga']) || $data['harga'] < 0) {
            $errors[] = 'Harga harus berupa angka positif';
        } elseif ($data['harga'] > 99999999) {
            $errors[] = 'Harga terlalu besar';
        }

        // Status
        $validStatus = ['aktif', 'nonaktif'];
        if (empty($data['status_menu'])) {
            $errors[] = 'Status menu wajib dipilih';
        } elseif (!in_array($data['status_menu'], $validStatus)) {
            $errors[] = 'Status tidak valid';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate user data
     * 
     * @param array $data
     * @param bool $isUpdate
     * @return array
     */
    public static function validateUser($data, $isUpdate = false)
    {
        $errors = [];

        // Nama
        if (empty($data['nama'])) {
            $errors[] = 'Nama wajib diisi';
        } elseif (strlen($data['nama']) < 3) {
            $errors[] = 'Nama minimal 3 karakter';
        } elseif (strlen($data['nama']) > 100) {
            $errors[] = 'Nama maksimal 100 karakter';
        }

        // Email
        if (empty($data['email'])) {
            $errors[] = 'Email wajib diisi';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Format email tidak valid';
        }

        // Password (hanya required untuk create)
        if (!$isUpdate || !empty($data['password'])) {
            if (empty($data['password'])) {
                $errors[] = 'Password wajib diisi';
            } elseif (strlen($data['password']) < MIN_PASSWORD_LENGTH) {
                $errors[] = 'Password minimal ' . MIN_PASSWORD_LENGTH . ' karakter';
            } elseif (strlen($data['password']) > MAX_PASSWORD_LENGTH) {
                $errors[] = 'Password maksimal ' . MAX_PASSWORD_LENGTH . ' karakter';
            }
        }

        // Role
        $validRoles = ['owner', 'kasir'];
        if (empty($data['role'])) {
            $errors[] = 'Role wajib dipilih';
        } elseif (!in_array($data['role'], $validRoles)) {
            $errors[] = 'Role tidak valid';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate meja data
     * 
     * @param array $data
     * @return array
     */
    public static function validateMeja($data)
    {
        $errors = [];

        if (empty($data['nomor_meja'])) {
            $errors[] = 'Nomor meja wajib diisi';
        } elseif (strlen($data['nomor_meja']) > 20) {
            $errors[] = 'Nomor meja maksimal 20 karakter';
        }

        // Status meja (untuk update)
        if (isset($data['status_meja'])) {
            $validStatus = ['kosong', 'terisi', 'menunggu_pembayaran', 'selesai'];
            if (!in_array($data['status_meja'], $validStatus)) {
                $errors[] = 'Status meja tidak valid';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate pesanan data
     * 
     * @param array $data
     * @return array
     */
    public static function validatePesanan($data)
    {
        $errors = [];

        // ID Meja
        if (empty($data['id_meja'])) {
            $errors[] = 'Meja wajib dipilih';
        } elseif (!is_numeric($data['id_meja']) || $data['id_meja'] <= 0) {
            $errors[] = 'ID meja tidak valid';
        }

        // Jenis pesanan
        $validJenis = ['dine_in', 'take_away'];
        if (empty($data['jenis_pesanan'])) {
            $errors[] = 'Jenis pesanan wajib dipilih';
        } elseif (!in_array($data['jenis_pesanan'], $validJenis)) {
            $errors[] = 'Jenis pesanan tidak valid';
        }

        // Items
        if (empty($data['items']) || !is_array($data['items'])) {
            $errors[] = 'Items pesanan wajib diisi';
        } else {
            foreach ($data['items'] as $idx => $item) {
                if (empty($item['id_menu']) || !is_numeric($item['id_menu'])) {
                    $errors[] = "Item #{$idx}: ID menu tidak valid";
                }
                if (empty($item['jumlah']) || !is_numeric($item['jumlah']) || $item['jumlah'] <= 0) {
                    $errors[] = "Item #{$idx}: Jumlah harus lebih dari 0";
                }
                if (empty($item['harga_satuan']) || !is_numeric($item['harga_satuan'])) {
                    $errors[] = "Item #{$idx}: Harga tidak valid";
                }
            }
        }

        // Catatan (optional)
        if (!empty($data['catatan']) && strlen($data['catatan']) > MAX_CATATAN_LENGTH) {
            $errors[] = 'Catatan maksimal ' . MAX_CATATAN_LENGTH . ' karakter';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate pembayaran data
     * 
     * @param array $data
     * @return array
     */
    public static function validatePembayaran($data)
    {
        $errors = [];

        // ID Pesanan
        if (empty($data['id_pesanan']) || !is_numeric($data['id_pesanan'])) {
            $errors[] = 'ID pesanan tidak valid';
        }

        // Metode
        $validMetode = ['cash', 'qris'];
        if (empty($data['metode'])) {
            $errors[] = 'Metode pembayaran wajib dipilih';
        } elseif (!in_array($data['metode'], $validMetode)) {
            $errors[] = 'Metode pembayaran tidak valid';
        }

        // Jumlah tagihan
        if (!isset($data['jumlah_tagihan']) || !is_numeric($data['jumlah_tagihan'])) {
            $errors[] = 'Jumlah tagihan tidak valid';
        } elseif ($data['jumlah_tagihan'] <= 0) {
            $errors[] = 'Jumlah tagihan harus lebih dari 0';
        }

        // Jumlah dibayar (khusus cash)
        if ($data['metode'] === 'cash') {
            if (!isset($data['jumlah_dibayar']) || !is_numeric($data['jumlah_dibayar'])) {
                $errors[] = 'Jumlah dibayar tidak valid';
            } elseif ($data['jumlah_dibayar'] < $data['jumlah_tagihan']) {
                $errors[] = 'Jumlah bayar kurang dari tagihan';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate pembelian bahan data
     * 
     * @param array $data
     * @return array
     */
    public static function validatePembelianBahan($data)
    {
        $errors = [];

        // Nama bahan
        if (empty($data['nama_bahan'])) {
            $errors[] = 'Nama bahan wajib diisi';
        } elseif (strlen($data['nama_bahan']) > MAX_BAHAN_NAME_LENGTH) {
            $errors[] = 'Nama bahan maksimal ' . MAX_BAHAN_NAME_LENGTH . ' karakter';
        }

        // Harga
        if (!isset($data['harga']) || !is_numeric($data['harga'])) {
            $errors[] = 'Harga tidak valid';
        } elseif ($data['harga'] <= 0) {
            $errors[] = 'Harga harus lebih dari 0';
        }

        // Tanggal beli
        if (empty($data['tanggal_beli'])) {
            $errors[] = 'Tanggal beli wajib diisi';
        } elseif (!self::isValidDate($data['tanggal_beli'])) {
            $errors[] = 'Format tanggal tidak valid';
        }

        // Keterangan (optional)
        if (!empty($data['keterangan']) && strlen($data['keterangan']) > MAX_CATATAN_LENGTH) {
            $errors[] = 'Keterangan maksimal ' . MAX_CATATAN_LENGTH . ' karakter';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Check if date is valid
     * 
     * @param string $date
     * @param string $format
     * @return bool
     */
    private static function isValidDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Sanitize string untuk SQL LIKE
     * 
     * @param string $string
     * @return string
     */
    public static function sanitizeLike($string)
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $string);
    }
}
