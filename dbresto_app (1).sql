-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 24, 2025 at 03:31 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `dbresto_app`
--

-- --------------------------------------------------------

--
-- Table structure for table `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail` int NOT NULL,
  `id_pesanan` int NOT NULL,
  `id_menu` int NOT NULL,
  `jumlah` int NOT NULL DEFAULT '1',
  `harga_satuan` decimal(12,2) NOT NULL,
  `subtotal` decimal(14,2) GENERATED ALWAYS AS ((`jumlah` * `harga_satuan`)) STORED,
  `status_item` enum('menunggu','dimasak','selesai') DEFAULT 'menunggu',
  `catatan_item` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laporan_transaksi`
--

CREATE TABLE `laporan_transaksi` (
  `id_laporan` int NOT NULL,
  `jenis` enum('penjualan','pembelian') NOT NULL,
  `id_referensi` int NOT NULL,
  `nominal` decimal(14,2) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `waktu_transaksi` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meja`
--

CREATE TABLE `meja` (
  `id_meja` int NOT NULL,
  `nomor_meja` varchar(20) NOT NULL,
  `kode_unik` varchar(64) NOT NULL,
  `status_meja` enum('kosong','terisi','menunggu_pembayaran','selesai') DEFAULT 'kosong',
  `qrcode_url` varchar(255) DEFAULT NULL,
  `last_update` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `meja`
--

INSERT INTO `meja` (`id_meja`, `nomor_meja`, `kode_unik`, `status_meja`, `qrcode_url`, `last_update`) VALUES
(1, 'M01', 'KODE-M01-ABC123', 'kosong', 'https://resto.test/meja.php?kode=KODE-M01-ABC123', '2025-10-23 23:32:44'),
(2, 'M02', 'KODE-M02-DEF456', 'kosong', 'https://resto.test/meja.php?kode=KODE-M02-DEF456', '2025-10-23 23:32:44');

-- --------------------------------------------------------

--
-- Table structure for table `menu`
--

CREATE TABLE `menu` (
  `id_menu` int NOT NULL,
  `nama_menu` varchar(150) NOT NULL,
  `kategori` enum('makanan','minuman','lainnya') NOT NULL DEFAULT 'makanan',
  `harga` decimal(12,2) NOT NULL,
  `status_menu` enum('aktif','nonaktif') DEFAULT 'aktif',
  `gambar` varchar(255) DEFAULT NULL,
  `dibuat_pada` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `menu`
--

INSERT INTO `menu` (`id_menu`, `nama_menu`, `kategori`, `harga`, `status_menu`, `gambar`, `dibuat_pada`) VALUES
(1, 'Nasi Goreng Spesial', 'makanan', 25000.00, 'aktif', NULL, '2025-10-23 23:32:44'),
(2, 'Es Teh Manis', 'minuman', 8000.00, 'aktif', NULL, '2025-10-23 23:32:44'),
(3, 'Mie Goreng Pedas', 'makanan', 23000.00, 'aktif', NULL, '2025-10-23 23:32:44');

-- --------------------------------------------------------

--
-- Table structure for table `pembatalan_pesanan`
--

CREATE TABLE `pembatalan_pesanan` (
  `id_batal` int NOT NULL,
  `id_pesanan` int NOT NULL,
  `alasan` text,
  `dibatalkan_oleh` enum('customer','kasir') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci DEFAULT 'customer',
  `waktu_batal` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id_pembayaran` int NOT NULL,
  `id_pesanan` int NOT NULL,
  `metode` enum('qris','cash') NOT NULL,
  `status` enum('belum_bayar','sudah_bayar','gagal') DEFAULT 'belum_bayar',
  `jumlah_tagihan` decimal(12,2) NOT NULL,
  `jumlah_dibayar` decimal(12,2) NOT NULL,
  `kembalian` decimal(12,2) DEFAULT '0.00',
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `waktu_pembayaran` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Triggers `pembayaran`
--
DELIMITER $$
CREATE TRIGGER `trg_after_pembayaran_insert` AFTER INSERT ON `pembayaran` FOR EACH ROW BEGIN
  IF NEW.status = 'sudah_bayar' THEN
    INSERT INTO laporan_transaksi (jenis, id_referensi, nominal, keterangan, waktu_transaksi)
    VALUES ('penjualan', NEW.id_pesanan, NEW.jumlah_dibayar, CONCAT('Pembayaran pesanan #', NEW.id_pesanan), NEW.waktu_pembayaran);
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `pembelian_bahan`
--

CREATE TABLE `pembelian_bahan` (
  `id_beli` int NOT NULL,
  `nama_bahan` varchar(150) NOT NULL,
  `harga` decimal(12,2) NOT NULL,
  `tanggal_beli` date NOT NULL,
  `keterangan` text,
  `bukti_pembelian` varchar(255) DEFAULT NULL,
  `dibuat_oleh` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Triggers `pembelian_bahan`
--
DELIMITER $$
CREATE TRIGGER `trg_after_pembelian_insert` AFTER INSERT ON `pembelian_bahan` FOR EACH ROW BEGIN
  INSERT INTO laporan_transaksi (jenis, id_referensi, nominal, keterangan, waktu_transaksi)
  VALUES ('pembelian', NEW.id_beli, NEW.harga, CONCAT('Pembelian bahan: ', NEW.nama_bahan), NOW());
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id_pengguna` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('owner','kasir') NOT NULL,
  `status_akun` enum('aktif','nonaktif') DEFAULT 'aktif',
  `terakhir_login` datetime DEFAULT NULL,
  `dibuat_pada` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`id_pengguna`, `nama`, `email`, `password`, `role`, `status_akun`, `terakhir_login`, `dibuat_pada`) VALUES
(1, 'Owner Demo', 'owner@resto.test', '$2y$10$examplehash', 'owner', 'aktif', NULL, '2025-10-23 23:32:44'),
(2, 'Kasir Demo', 'kasir@resto.test', '$2y$10$examplehash', 'kasir', 'aktif', NULL, '2025-10-23 23:32:44'),
(3, 'fira', 'fira@gmail.com', '$2y$10$bDI4ubxL/NzwqE6Fbv43OOxXVVcuUNWo590whbwhEU2HN8pNAWpd.', 'owner', 'aktif', NULL, '2025-10-24 08:42:21');

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int NOT NULL,
  `id_meja` int NOT NULL,
  `dibuat_oleh` int DEFAULT NULL,
  `waktu_pesan` datetime DEFAULT CURRENT_TIMESTAMP,
  `jenis_pesanan` enum('dine_in','take_away') DEFAULT 'dine_in',
  `status_pesanan` enum('menunggu','diterima','dimasak','siap_disajikan','selesai','dibayar','dibatalkan') DEFAULT 'menunggu',
  `metode_bayar` enum('qris','cash') DEFAULT NULL,
  `total_harga` decimal(12,2) DEFAULT '0.00',
  `catatan` text,
  `diterima_oleh` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_laporan_penjualan`
-- (See below for the actual view)
--
CREATE TABLE `view_laporan_penjualan` (
`diterima_oleh` int
,`id_meja` int
,`id_pesanan` int
,`jumlah_dibayar` decimal(12,2)
,`kembalian` decimal(12,2)
,`metode_bayar` enum('qris','cash')
,`status_pembayaran` enum('belum_bayar','sudah_bayar','gagal')
,`status_pesanan` enum('menunggu','diterima','dimasak','siap_disajikan','selesai','dibayar','dibatalkan')
,`total_harga` decimal(12,2)
,`waktu_pesan` datetime
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_menu` (`id_menu`);

--
-- Indexes for table `laporan_transaksi`
--
ALTER TABLE `laporan_transaksi`
  ADD PRIMARY KEY (`id_laporan`),
  ADD KEY `idx_laporan_jenis_time` (`jenis`,`waktu_transaksi`);

--
-- Indexes for table `meja`
--
ALTER TABLE `meja`
  ADD PRIMARY KEY (`id_meja`),
  ADD UNIQUE KEY `nomor_meja` (`nomor_meja`),
  ADD UNIQUE KEY `kode_unik` (`kode_unik`);

--
-- Indexes for table `menu`
--
ALTER TABLE `menu`
  ADD PRIMARY KEY (`id_menu`);

--
-- Indexes for table `pembatalan_pesanan`
--
ALTER TABLE `pembatalan_pesanan`
  ADD PRIMARY KEY (`id_batal`),
  ADD KEY `id_pesanan` (`id_pesanan`);

--
-- Indexes for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id_pembayaran`),
  ADD KEY `idx_pembayaran_pesanan` (`id_pesanan`);

--
-- Indexes for table `pembelian_bahan`
--
ALTER TABLE `pembelian_bahan`
  ADD PRIMARY KEY (`id_beli`),
  ADD KEY `dibuat_oleh` (`dibuat_oleh`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_pengguna`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `dibuat_oleh` (`dibuat_oleh`),
  ADD KEY `diterima_oleh` (`diterima_oleh`),
  ADD KEY `idx_pesanan_meja` (`id_meja`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laporan_transaksi`
--
ALTER TABLE `laporan_transaksi`
  MODIFY `id_laporan` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meja`
--
ALTER TABLE `meja`
  MODIFY `id_meja` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `menu`
--
ALTER TABLE `menu`
  MODIFY `id_menu` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pembatalan_pesanan`
--
ALTER TABLE `pembatalan_pesanan`
  MODIFY `id_batal` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id_pembayaran` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pembelian_bahan`
--
ALTER TABLE `pembelian_bahan`
  MODIFY `id_beli` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_pengguna` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `view_laporan_penjualan`
--
DROP TABLE IF EXISTS `view_laporan_penjualan`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_laporan_penjualan`  AS SELECT `p`.`id_pesanan` AS `id_pesanan`, `p`.`id_meja` AS `id_meja`, `p`.`waktu_pesan` AS `waktu_pesan`, `p`.`status_pesanan` AS `status_pesanan`, `p`.`metode_bayar` AS `metode_bayar`, `p`.`total_harga` AS `total_harga`, `pay`.`status` AS `status_pembayaran`, `pay`.`jumlah_dibayar` AS `jumlah_dibayar`, `pay`.`kembalian` AS `kembalian`, `p`.`diterima_oleh` AS `diterima_oleh` FROM (`pesanan` `p` left join `pembayaran` `pay` on((`p`.`id_pesanan` = `pay`.`id_pesanan`))) WHERE (`p`.`status_pesanan` in ('selesai','dibayar')) ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`id_menu`) REFERENCES `menu` (`id_menu`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pembatalan_pesanan`
--
ALTER TABLE `pembatalan_pesanan`
  ADD CONSTRAINT `pembatalan_pesanan_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `pembelian_bahan`
--
ALTER TABLE `pembelian_bahan`
  ADD CONSTRAINT `pembelian_bahan_ibfk_1` FOREIGN KEY (`dibuat_oleh`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`id_meja`) REFERENCES `meja` (`id_meja`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `pesanan_ibfk_2` FOREIGN KEY (`dibuat_oleh`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `pesanan_ibfk_3` FOREIGN KEY (`diterima_oleh`) REFERENCES `pengguna` (`id_pengguna`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
