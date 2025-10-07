-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2025 at 05:08 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `upj_ahass_blpt_diy`
--

-- --------------------------------------------------------

--
-- Table structure for table `laporan_bulanan`
--

CREATE TABLE `laporan_bulanan` (
  `id_laporan` int(11) NOT NULL,
  `tahun` int(11) NOT NULL,
  `bulan` int(11) NOT NULL,
  `jumlah_unit` int(11) NOT NULL DEFAULT 0,
  `jumlah_sperpart` int(11) NOT NULL DEFAULT 0,
  `jumlah_oli` int(11) NOT NULL DEFAULT 0,
  `total_jasa` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_penjualan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `laporan_harian`
--

CREATE TABLE `laporan_harian` (
  `id_laporan` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jumlah_unit` int(11) NOT NULL DEFAULT 0,
  `jumlah_sperpart` int(11) NOT NULL DEFAULT 0,
  `jumlah_oli` int(11) NOT NULL DEFAULT 0,
  `total_jasa` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_penjualan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status_verifikasi` enum('Belum','Sudah') DEFAULT 'Belum',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `aktivitas` varchar(255) DEFAULT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id`, `user_id`, `aktivitas`, `waktu`) VALUES
(40, NULL, 'Login', '2025-10-06 01:54:33'),
(41, NULL, 'Login', '2025-10-06 01:56:45'),
(42, NULL, 'Login', '2025-10-06 01:58:46'),
(43, NULL, 'Login', '2025-10-06 01:59:40'),
(44, 6, 'Login', '2025-10-06 02:01:40'),
(45, NULL, 'Login', '2025-10-06 02:03:07'),
(46, 6, 'Login', '2025-10-06 02:03:33'),
(47, NULL, 'Login', '2025-10-06 02:07:33'),
(48, 6, 'Login', '2025-10-06 02:10:13'),
(49, NULL, 'Login', '2025-10-06 02:24:27'),
(50, NULL, 'Login', '2025-10-06 02:41:03'),
(51, 6, 'Login', '2025-10-06 02:41:22'),
(52, NULL, 'Login', '2025-10-06 02:55:10'),
(53, 6, 'Login', '2025-10-06 03:13:23'),
(54, 6, 'Login', '2025-10-06 03:15:22'),
(55, NULL, 'Login', '2025-10-06 03:18:14'),
(56, NULL, 'Login', '2025-10-06 03:21:48'),
(57, NULL, 'Login', '2025-10-06 03:35:27'),
(58, NULL, 'Login', '2025-10-06 03:37:09'),
(59, NULL, 'Login', '2025-10-06 03:39:32'),
(60, NULL, 'Login', '2025-10-06 03:50:43'),
(61, NULL, 'Login', '2025-10-06 03:58:59'),
(62, NULL, 'Login', '2025-10-06 03:59:34'),
(63, NULL, 'Login', '2025-10-06 04:00:06'),
(64, NULL, 'Login', '2025-10-06 04:01:51'),
(65, NULL, 'Login', '2025-10-06 04:02:38'),
(66, 6, 'Login', '2025-10-06 04:03:05'),
(67, NULL, 'Login', '2025-10-06 04:05:57'),
(68, NULL, 'Login', '2025-10-06 04:06:14'),
(69, NULL, 'Login', '2025-10-06 05:42:32'),
(70, 6, 'Login', '2025-10-06 05:42:50'),
(71, NULL, 'Login', '2025-10-06 05:45:38'),
(72, 6, 'Login', '2025-10-06 05:51:28'),
(73, NULL, 'Login', '2025-10-06 06:00:27'),
(74, NULL, 'Login', '2025-10-06 06:02:38'),
(75, NULL, 'Login', '2025-10-06 06:07:02'),
(76, NULL, 'Login', '2025-10-06 06:08:58'),
(77, NULL, 'Login', '2025-10-06 06:17:44'),
(78, 6, 'Login', '2025-10-06 06:18:19'),
(79, 6, 'Login', '2025-10-07 02:46:20');

-- --------------------------------------------------------

--
-- Table structure for table `mekanik`
--

CREATE TABLE `mekanik` (
  `id_mekanik` int(11) NOT NULL,
  `nama_mekanik` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mekanik`
--

INSERT INTO `mekanik` (`id_mekanik`, `nama_mekanik`) VALUES
(11, 'Agus Nugroho'),
(12, 'Eko Marwanto'),
(17, 'Mukhlisut Tohari'),
(16, 'Surana'),
(13, 'Widarmadi'),
(14, 'Widarmanto');

-- --------------------------------------------------------

--
-- Table structure for table `penandatangan`
--

CREATE TABLE `penandatangan` (
  `id` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `jabatan` varchar(100) NOT NULL,
  `urutan` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penandatangan`
--

INSERT INTO `penandatangan` (`id`, `nama_lengkap`, `jabatan`, `urutan`) VALUES
(2, 'Rini Nurtyastuti, M.M', 'Kepala Seksi Layanan ', 2),
(3, 'Sagung Gede Rahmawati', 'Bendahara', 1),
(4, 'Langgeng Arie Wirayudha', 'Koperasi', 3);

-- --------------------------------------------------------

--
-- Table structure for table `pengguna`
--

CREATE TABLE `pengguna` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` varchar(100) NOT NULL,
  `status` enum('Aktif','Nonaktif') DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengguna`
--

INSERT INTO `pengguna` (`id_user`, `username`, `password`, `nama_lengkap`, `role`, `status`, `created_at`) VALUES
(6, 'admin', '$2y$10$T5p/8oWtsJEu5PgCn8L0GucpLXo.rAavMZrUw1C.dkBrwfzObzbrO', 'Iman Mukhlisin', 'Super Admin', 'Aktif', '2025-10-03 11:45:49'),
(11, 'role_superadmin', '', 'Role Dummy', 'Super Admin', 'Nonaktif', '2025-10-07 03:05:43'),
(12, 'role_frontoffice', '', 'Role Dummy', 'Front Office', 'Nonaktif', '2025-10-07 03:05:43'),
(13, 'role_bendahara', '', 'Role Dummy', 'Bendahara', 'Nonaktif', '2025-10-07 03:05:43');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `no_nota` varchar(20) NOT NULL,
  `tanggal` date NOT NULL,
  `jasa_rp` decimal(15,2) NOT NULL DEFAULT 0.00,
  `jasa_lain_rp` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sperpart_rp` decimal(15,2) NOT NULL DEFAULT 0.00,
  `oli_rp` decimal(15,2) NOT NULL DEFAULT 0.00,
  `metode_pembayaran` enum('Tunai','Non Tunai') NOT NULL,
  `status_verifikasi` enum('Belum','Sudah') DEFAULT 'Belum',
  `status_verifikasi_bendahara` enum('Belum','Sudah') DEFAULT 'Belum',
  `nama_pengirim` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `mekanik` varchar(100) DEFAULT NULL,
  `id_mekanik` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`no_nota`, `tanggal`, `jasa_rp`, `jasa_lain_rp`, `sperpart_rp`, `oli_rp`, `metode_pembayaran`, `status_verifikasi`, `status_verifikasi_bendahara`, `nama_pengirim`, `created_by`, `created_at`, `verified_by`, `verified_at`, `mekanik`, `id_mekanik`) VALUES
('001', '2025-10-03', 100000.00, 10000.00, 10000.00, 56000.00, 'Non Tunai', 'Belum', 'Sudah', 'AHMAD', NULL, '2025-10-03 11:07:32', NULL, NULL, 'Madi', NULL),
('0010', '2025-10-04', 40000.00, 20000.00, 80000.00, 0.00, 'Non Tunai', 'Belum', 'Sudah', 'ANWAR', NULL, '2025-10-04 08:29:52', NULL, NULL, 'muchlisud', NULL),
('0011', '2025-10-04', 20000.00, 0.00, 0.00, 56000.00, 'Non Tunai', 'Belum', 'Sudah', 'RAIHAN', NULL, '2025-10-04 08:30:25', NULL, NULL, 'madi', NULL),
('0012', '2025-10-04', 100000.00, 0.00, 200000.00, 56000.00, 'Non Tunai', 'Belum', 'Sudah', 'GUNTUR', NULL, '2025-10-04 08:31:02', NULL, NULL, 'surono', NULL),
('002', '2025-10-03', 100000.00, 0.00, 20000.00, 0.00, 'Non Tunai', 'Belum', 'Sudah', 'BAMBANG', NULL, '2025-10-03 11:08:07', NULL, NULL, 'Eko', NULL),
('0020', '2025-10-05', 200000.00, 0.00, 50000.00, 56000.00, 'Non Tunai', 'Belum', 'Sudah', 'WANDA', NULL, '2025-10-05 05:15:45', NULL, NULL, 'eko', NULL),
('0021', '2025-10-05', 20000.00, 0.00, 60000.00, 56000.00, 'Non Tunai', 'Belum', 'Sudah', 'WAHID', NULL, '2025-10-05 05:16:13', NULL, NULL, 'manto', NULL),
('0022', '2025-10-05', 30000.00, 20000.00, 60000.00, 56000.00, 'Non Tunai', 'Belum', 'Sudah', 'NADIA', NULL, '2025-10-05 05:16:50', NULL, NULL, 'agus', NULL),
('0023', '2025-10-05', 40000.00, 0.00, 20000.00, 56000.00, 'Tunai', 'Belum', 'Belum', NULL, NULL, '2025-10-05 05:17:21', NULL, NULL, 'manto', NULL),
('0024', '2025-10-05', 20000.00, 0.00, 20000.00, 56000.00, 'Non Tunai', 'Belum', 'Sudah', 'RIDHO', NULL, '2025-10-05 05:17:58', NULL, NULL, 'surono', NULL),
('0025', '2025-10-05', 45000.00, 11000.00, 8000.00, 56000.00, 'Tunai', 'Belum', 'Belum', NULL, NULL, '2025-10-05 05:18:26', NULL, NULL, 'muchlisud', NULL),
('0026', '2025-10-05', 100000.00, 0.00, 0.00, 60000.00, 'Tunai', 'Belum', 'Belum', NULL, NULL, '2025-10-05 06:00:08', NULL, NULL, 'eko', NULL),
('003', '2025-10-03', 0.00, 100000.00, 20000.00, 30000.00, 'Non Tunai', 'Belum', 'Sudah', 'ALDO', NULL, '2025-10-03 11:16:21', NULL, NULL, 'Surono', NULL),
('0032', '2025-10-06', 20000.00, 200000.00, 0.00, 30000.00, 'Non Tunai', 'Belum', 'Sudah', NULL, NULL, '2025-10-06 01:55:51', NULL, NULL, NULL, NULL),
('0033', '2025-10-06', 200000.00, 0.00, 0.00, 0.00, 'Tunai', 'Belum', 'Belum', NULL, NULL, '2025-10-06 03:59:58', NULL, NULL, NULL, NULL),
('004', '2025-10-04', 100000.00, 0.00, 50000.00, 50000.00, 'Tunai', 'Belum', 'Belum', 'WARDANI', NULL, '2025-10-03 12:48:42', NULL, NULL, 'Muchlisud', NULL),
('005', '2025-10-03', 200000.00, 20000.00, 0.00, 0.00, 'Tunai', 'Belum', 'Belum', NULL, NULL, '2025-10-03 14:29:36', NULL, NULL, 'Manto', NULL),
('006', '2025-10-03', 30000.00, 20000.00, 50000.00, 20000.00, 'Tunai', 'Belum', 'Belum', NULL, NULL, '2025-10-03 14:30:14', NULL, NULL, 'Agus', NULL),
('007', '2025-10-03', 90000.00, 10000.00, 90000.00, 0.00, 'Tunai', 'Belum', 'Belum', 'anwar', NULL, '2025-10-03 16:03:23', NULL, NULL, 'alpridel', NULL),
('008', '2025-10-04', 60000.00, 30000.00, 70000.00, 56000.00, 'Non Tunai', 'Belum', 'Sudah', 'AJI', NULL, '2025-10-04 08:28:59', NULL, NULL, 'eko', NULL),
('009', '2025-10-04', 20000.00, 0.00, 50000.00, 0.00, 'Tunai', 'Belum', 'Belum', NULL, NULL, '2025-10-04 08:29:25', NULL, NULL, 'madi', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `laporan_bulanan`
--
ALTER TABLE `laporan_bulanan`
  ADD PRIMARY KEY (`id_laporan`),
  ADD UNIQUE KEY `tahun` (`tahun`,`bulan`);

--
-- Indexes for table `laporan_harian`
--
ALTER TABLE `laporan_harian`
  ADD PRIMARY KEY (`id_laporan`),
  ADD UNIQUE KEY `tanggal` (`tanggal`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `mekanik`
--
ALTER TABLE `mekanik`
  ADD PRIMARY KEY (`id_mekanik`),
  ADD UNIQUE KEY `nama_mekanik` (`nama_mekanik`);

--
-- Indexes for table `penandatangan`
--
ALTER TABLE `penandatangan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengguna`
--
ALTER TABLE `pengguna`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`no_nota`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `id_mekanik` (`id_mekanik`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `laporan_bulanan`
--
ALTER TABLE `laporan_bulanan`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `laporan_harian`
--
ALTER TABLE `laporan_harian`
  MODIFY `id_laporan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `mekanik`
--
ALTER TABLE `mekanik`
  MODIFY `id_mekanik` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `penandatangan`
--
ALTER TABLE `penandatangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pengguna`
--
ALTER TABLE `pengguna`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `laporan_harian`
--
ALTER TABLE `laporan_harian`
  ADD CONSTRAINT `laporan_harian_ibfk_1` FOREIGN KEY (`verified_by`) REFERENCES `pengguna` (`id_user`) ON DELETE SET NULL;

--
-- Constraints for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD CONSTRAINT `log_aktivitas_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `pengguna` (`id_user`) ON DELETE SET NULL;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `pengguna` (`id_user`) ON DELETE SET NULL,
  ADD CONSTRAINT `transaksi_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `pengguna` (`id_user`) ON DELETE SET NULL,
  ADD CONSTRAINT `transaksi_ibfk_3` FOREIGN KEY (`id_mekanik`) REFERENCES `mekanik` (`id_mekanik`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
