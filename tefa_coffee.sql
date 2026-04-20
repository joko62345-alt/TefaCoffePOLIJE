-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 31 Mar 2026 pada 09.36
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tefa_coffee`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `coffee_beans`
--

CREATE TABLE `coffee_beans` (
  `id` int(11) NOT NULL,
  `nama_biji_kopi` varchar(100) NOT NULL,
  `varietas` varchar(50) DEFAULT NULL,
  `asal` varchar(100) DEFAULT NULL,
  `stok` decimal(10,1) DEFAULT 0.0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `coffee_beans`
--

INSERT INTO `coffee_beans` (`id`, `nama_biji_kopi`, `varietas`, `asal`, `stok`, `created_at`) VALUES
(1, 'arabica', NULL, NULL, 45.5, '2026-03-06 11:51:55'),
(3, 'Excelsa', NULL, NULL, 35.0, '2026-03-06 11:59:17'),
(4, 'robusta', NULL, NULL, 20.0, '2026-03-18 06:44:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `nama_barang` varchar(100) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `kondisi` enum('Baik','Cukup Baik','Dalam Perbaikan','Rusak Ringan','Rusak Berat') DEFAULT 'Baik',
  `tanggal_pembelian` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `inventory`
--

INSERT INTO `inventory` (`id`, `nama_barang`, `kategori`, `jumlah`, `kondisi`, `tanggal_pembelian`, `keterangan`, `created_at`, `updated_at`) VALUES
(1, 'Mesin Roaster Kopi', 'Mesin Roasting', 1, 'Rusak Ringan', '2024-01-10', 'Kapasitas 1kg/batch, gas LPG', '2026-02-27 14:03:08', '2026-03-28 13:59:55'),
(2, 'Grinder Burr Elektrik', 'Mesin Grinding', 2, 'Baik', '2024-01-12', '15 setting kehalusan, motor 300W', '2026-02-27 14:03:08', '2026-02-27 14:03:08'),
(3, 'Timbangan Digital 5kg', 'Alat Ukur', 3, 'Baik', '2024-01-08', 'Akurasi 0.1g, platform stainless', '2026-02-27 14:03:08', '2026-02-27 14:03:08'),
(4, 'Wadah Kedap Udara 1kg', 'Penyimpanan', 90, 'Baik', '2024-01-15', 'Food grade plastic, seal karet', '2026-02-27 14:03:08', '2026-03-28 14:03:05'),
(5, 'Kantong Kopi Valve 250gr', 'Packaging', 100, 'Baik', '2024-02-01', 'Aluminium foil + katup degassing', '2026-02-27 14:03:08', '2026-02-27 14:03:08');

-- --------------------------------------------------------

--
-- Struktur dari tabel `inventory_usage`
--

CREATE TABLE `inventory_usage` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `jenis_penggunaan` varchar(50) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `kondisi_setelah` varchar(50) NOT NULL,
  `pengguna` varchar(100) NOT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `inventory_usage`
--

INSERT INTO `inventory_usage` (`id`, `inventory_id`, `jenis_penggunaan`, `keterangan`, `kondisi_setelah`, `pengguna`, `tanggal`) VALUES
(1, 1, 'Pemakaian Harian', 'digunakan praktikum ', 'Baik', 'mahasiswa', '2026-03-18 06:52:06'),
(2, 4, 'Pemakaian Harian', 'digunakan 10 pcs\r\n', 'Baik', 'mahasiswa', '2026-03-28 14:02:54');

-- --------------------------------------------------------

--
-- Struktur dari tabel `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `login_time` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('success','failed') DEFAULT 'success'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) DEFAULT 0,
  `kategori` enum('robusta','arabica','lainnya') DEFAULT 'lainnya',
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `nama_produk`, `deskripsi`, `harga`, `stok`, `kategori`, `gambar`, `created_at`) VALUES
(1, 'Kopi Bubuk Robusta', 'Kopi robusta pilihan dengan cita rasa kuat dan bold', 65000.00, 4, 'robusta', 'prod_69a2ae4999cf0.jpeg', '2026-02-27 13:15:37'),
(2, 'Kopi Bubuk Arabica', 'Kopi arabica premium dengan aroma harum dan rasa smooth', 95000.00, 98, 'arabica', 'prod_69a2d976dc26f.jpeg', '2026-02-27 13:15:37');

-- --------------------------------------------------------

--
-- Struktur dari tabel `stock_movements`
--

CREATE TABLE `stock_movements` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `jenis` enum('masuk','keluar') NOT NULL,
  `jumlah` int(11) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `stock_movements`
--

INSERT INTO `stock_movements` (`id`, `product_id`, `jenis`, `jumlah`, `keterangan`, `tanggal`) VALUES
(1, 1, 'masuk', 2, 'tambah', '2026-02-27 13:41:57'),
(2, 1, 'keluar', 1, 'Penjualan TRX-20260227-0B5A70', '2026-02-27 14:21:52'),
(3, 2, 'keluar', 1, 'Penjualan TRX-20260227-E8DC46', '2026-02-27 14:24:46'),
(4, 1, 'keluar', 1, 'Penjualan TRX-20260228-CBCE7D', '2026-02-28 03:36:44'),
(5, 1, 'keluar', 1, 'Penjualan TRX-20260228-698EF3', '2026-02-28 03:37:42'),
(6, 2, 'keluar', 1, 'Penjualan TRX-20260228-DA7AD0', '2026-02-28 03:46:37'),
(7, 2, 'keluar', 1, 'Penjualan TRX-20260228-9905FC', '2026-02-28 03:50:33'),
(8, 2, 'keluar', 1, 'Penjualan TRX-20260228-5491BF', '2026-02-28 03:55:17'),
(9, 2, 'masuk', 23, 'yes', '2026-03-02 04:36:46'),
(10, 1, 'masuk', 2, 'yyy', '2026-03-03 20:58:45'),
(11, 2, 'masuk', 2, 'ji', '2026-03-06 11:54:24'),
(12, 1, 'masuk', 2, 'restok', '2026-03-18 02:49:22'),
(14, 1, 'keluar', 1, 'Penjualan TRX-20260318-272103', '2026-03-18 02:59:30'),
(15, 1, 'masuk', 8, 'Restok manual', '2026-03-18 06:41:30'),
(16, 2, 'keluar', 1, 'Order WhatsApp #WA-20260318-8ABFE5 - Kopi Bubuk Arabica', '2026-03-18 07:57:57'),
(17, 1, 'keluar', 2, 'Order WhatsApp #WA-20260318-5C8172 - Kopi Bubuk Robusta', '2026-03-18 08:02:27'),
(18, 2, 'keluar', 2, 'Penjualan TRX-20260319-69BB75', '2026-03-19 04:01:14'),
(19, 2, 'masuk', 18, 'Restok manual', '2026-03-19 04:24:20'),
(20, 2, 'masuk', 7, 'Restok: restok', '2026-03-28 15:02:17'),
(21, 2, 'masuk', 7, 'Restok: restok', '2026-03-28 15:32:31'),
(22, 2, 'keluar', 1, 'Penjualan TRX-20260328-C398DE', '2026-03-29 03:02:00'),
(23, 2, 'keluar', 1, 'Penjualan TRX-20260328-664EEC', '2026-03-29 03:09:51'),
(24, 2, 'keluar', 1, 'Penjualan TRX-20260329-68E39F', '2026-03-29 03:14:20'),
(25, 1, 'keluar', 90, 'Pengurangan manual', '2026-03-29 12:45:03'),
(26, 2, 'keluar', 1, 'Penjualan TRX-20260331-1210BE', '2026-03-31 07:23:12');

-- --------------------------------------------------------

--
-- Struktur dari tabel `stock_movements_beans`
--

CREATE TABLE `stock_movements_beans` (
  `id` int(11) NOT NULL,
  `bean_id` int(11) NOT NULL,
  `jenis` enum('masuk','keluar') NOT NULL,
  `jumlah` decimal(10,1) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `tanggal` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `stock_movements_beans`
--

INSERT INTO `stock_movements_beans` (`id`, `bean_id`, `jenis`, `jumlah`, `keterangan`, `tanggal`) VALUES
(1, 1, 'masuk', 2.0, 'ji', '2026-03-06 11:53:30'),
(2, 3, 'keluar', 5.0, 'iji', '2026-03-06 11:59:17'),
(3, 1, 'masuk', 20.0, 'dari kebun\r\n', '2026-03-18 06:43:12'),
(4, 4, 'masuk', 20.0, 'dari kebun', '2026-03-18 06:44:41'),
(5, 3, 'masuk', 20.0, 'panen', '2026-03-19 07:19:38'),
(6, 3, 'masuk', 20.0, 'kiriman suplier', '2026-03-19 07:20:04'),
(7, 1, 'keluar', 2.0, 'kadaluwarsa', '2026-03-31 06:45:14');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `kode_transaksi` varchar(50) NOT NULL,
  `total_harga` decimal(10,2) NOT NULL,
  `metode_pembayaran` enum('cod','qris') NOT NULL,
  `status_pembayaran` enum('pending','lunas','dikonfirmasi') DEFAULT 'pending',
  `status_pengambilan` enum('belum_diambil','sudah_diambil') DEFAULT 'belum_diambil',
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `tanggal_transaksi` timestamp NOT NULL DEFAULT current_timestamp(),
  `diambil_oleh` varchar(100) DEFAULT NULL,
  `tanggal_diambil` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `kode_transaksi`, `total_harga`, `metode_pembayaran`, `status_pembayaran`, `status_pengambilan`, `bukti_pembayaran`, `tanggal_transaksi`, `diambil_oleh`, `tanggal_diambil`) VALUES
(1, 3, 'TRX-20260227-0B5A70', 75000.00, 'qris', 'lunas', 'sudah_diambil', NULL, '2026-02-27 14:21:52', 'Administrator', '2026-02-28 10:30:19'),
(2, 3, 'TRX-20260227-E8DC46', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-02-27 14:24:46', 'Administrator', '2026-02-28 10:24:33'),
(3, 3, 'TRX-20260228-CBCE7D', 75000.00, 'qris', 'lunas', 'belum_diambil', NULL, '2026-02-28 03:36:44', NULL, NULL),
(4, 3, 'TRX-20260228-698EF3', 75000.00, 'qris', 'lunas', 'belum_diambil', NULL, '2026-02-28 03:37:42', NULL, NULL),
(5, 6, 'TRX-20260228-DA7AD0', 95000.00, 'qris', 'lunas', 'belum_diambil', NULL, '2026-02-28 03:46:37', NULL, NULL),
(6, 6, 'TRX-20260228-9905FC', 95000.00, 'qris', 'lunas', 'belum_diambil', NULL, '2026-02-28 03:50:33', NULL, NULL),
(7, 6, 'TRX-20260228-5491BF', 95000.00, 'qris', 'lunas', 'belum_diambil', NULL, '2026-02-28 03:55:17', NULL, NULL),
(8, 6, 'TRX-20260228-1BDB96', 95000.00, 'qris', 'lunas', 'belum_diambil', NULL, '2026-02-28 04:00:17', NULL, NULL),
(9, 3, 'TRX-20260228-C2CD50', 65000.00, 'qris', 'lunas', 'belum_diambil', NULL, '2026-02-28 09:13:16', NULL, NULL),
(10, 3, 'TRX-20260228-22F7C2', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-02-28 09:13:38', NULL, NULL),
(11, 3, 'TRX-20260228-24D9D2', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-02-28 09:14:58', NULL, NULL),
(12, 3, 'TRX-20260228-DDCF28', 65000.00, 'qris', 'lunas', 'belum_diambil', NULL, '2026-02-28 09:59:25', NULL, NULL),
(13, 3, 'TRX-20260228-D847DD', 65000.00, 'qris', 'lunas', 'belum_diambil', NULL, '2026-02-28 10:03:25', NULL, NULL),
(14, 3, 'TRX-20260228-C84931', 95000.00, 'qris', 'lunas', 'belum_diambil', NULL, '2026-02-28 10:07:08', NULL, NULL),
(15, 3, 'TRX-20260228-B00EA4', 95000.00, 'qris', 'lunas', 'belum_diambil', NULL, '2026-02-28 10:07:23', NULL, NULL),
(16, 3, 'TRX-20260228-32277B', 95000.00, 'qris', 'lunas', 'sudah_diambil', NULL, '2026-02-28 10:09:39', 'Administrator', '2026-02-28 20:23:03'),
(17, 6, 'TRX-20260228-324C36', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-02-28 10:21:23', 'Administrator', '2026-02-28 20:57:09'),
(18, 3, 'TRX-20260301-44334A', 95000.00, 'qris', 'lunas', 'belum_diambil', NULL, '2026-03-01 00:49:08', NULL, NULL),
(19, 3, 'TRX-20260301-8729F7', 95000.00, 'qris', 'lunas', 'belum_diambil', NULL, '2026-03-01 01:06:00', NULL, NULL),
(20, 3, 'TRX-20260301-B11215', 65000.00, 'qris', 'lunas', 'sudah_diambil', NULL, '2026-03-01 11:49:15', 'Administrator', '2026-03-01 18:51:45'),
(21, 3, 'TRX-20260303-A93C30', 65000.00, 'qris', 'lunas', 'belum_diambil', NULL, '2026-03-03 03:46:50', NULL, NULL),
(22, 3, 'TRX-20260303-805557', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-03 04:02:48', NULL, NULL),
(23, 3, 'TRX-20260303-5CE216', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-03 04:06:13', NULL, NULL),
(24, 3, 'TRX-20260303-D96DAF', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-03 04:06:21', NULL, NULL),
(25, 3, 'TRX-20260303-51918F', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-03 04:19:17', NULL, NULL),
(26, 3, 'TRX-20260303-10AF55', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-03 04:21:53', 'Administrator', '2026-03-03 11:30:14'),
(27, 3, 'TRX-20260303-6062C7', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-03 04:39:18', NULL, NULL),
(28, 3, 'TRX-20260303-9A8530', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-03 04:40:09', NULL, NULL),
(29, 3, 'TRX-20260303-EEEE61', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-03 04:43:42', NULL, NULL),
(30, 3, 'TRX-20260303-DAF05D', 65000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-03 05:40:29', NULL, NULL),
(31, 3, 'TRX-20260303-BCEDD3', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-03 06:40:43', NULL, NULL),
(32, 3, 'TRX-20260303-DEF5FA', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-03 08:39:25', NULL, NULL),
(33, 3, 'TRX-20260303-7B9EE8', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-03 08:51:35', NULL, NULL),
(34, 3, 'TRX-20260303-B9DE1F', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-03 09:05:15', 'Administrator', '2026-03-03 16:41:22'),
(35, 3, 'TRX-20260303-C8C46E', 95000.00, 'cod', 'lunas', 'sudah_diambil', NULL, '2026-03-03 09:23:40', 'Administrator', '2026-03-03 16:36:15'),
(36, 3, 'TRX-20260303-C8347E', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-03 09:37:48', 'Administrator', '2026-03-03 16:38:11'),
(37, 3, 'TRX-20260303-78D5F0', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-03 09:41:11', NULL, NULL),
(38, 3, 'TRX-20260303-5C5DF0', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-03 20:52:53', 'Administrator', '2026-03-04 03:56:14'),
(39, 3, 'TRX-20260303-C49909', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-03 20:55:56', 'Administrator', '2026-03-04 03:56:51'),
(40, 3, 'TRX-20260303-8CBDAB', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-03 20:56:40', 'Administrator', '2026-03-04 03:56:59'),
(41, 3, 'TRX-20260303-B9FA59', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-03 21:00:27', 'Administrator', '2026-03-04 04:00:56'),
(42, 3, 'TRX-20260303-519EE4', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-03 21:03:49', 'Administrator', '2026-03-04 04:05:51'),
(43, 3, 'TRX-20260303-EDD1C0', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-03 21:05:34', 'Administrator', '2026-03-04 04:07:20'),
(44, 3, 'TRX-20260316-9326BC', 390000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-16 05:07:05', 'Administrator', '2026-03-16 12:26:33'),
(45, 3, 'TRX-20260318-272103', 65000.00, '', 'pending', 'belum_diambil', NULL, '2026-03-18 02:59:30', NULL, NULL),
(46, 3, 'TRX-20260318-B49188', 65000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-18 03:00:59', 'Administrator', '2026-03-18 10:01:13'),
(47, 3, 'TRX-20260318-DDE157', 65000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-18 03:09:33', 'Administrator', '2026-03-18 10:10:29'),
(48, 3, 'TRX-20260318-C779AC', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-18 06:57:00', 'Administrator', '2026-03-18 13:57:19'),
(57, 7, 'WA-20260318-8ABFE5', 0.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-18 07:57:57', 'Administrator', '2026-03-18 14:58:24'),
(58, 7, 'WA-20260318-5C8172', 130000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-18 08:02:27', 'Administrator', '2026-03-18 15:02:53'),
(59, 7, 'TRX-20260319-69BB75', 190000.00, '', 'lunas', 'sudah_diambil', NULL, '2026-03-19 04:01:14', 'Administrator', '2026-03-19 11:10:42'),
(61, 15, 'TRX-20260319-E8C471', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-19 04:36:46', 'Administrator', '2026-03-19 11:37:08'),
(62, 15, 'TRX-20260323-DDCEA6', 285000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-22 23:53:33', 'Administrator', '2026-03-23 06:54:11'),
(63, 3, 'TRX-20260323-252D34', 285000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-22 23:54:58', 'Administrator', '2026-03-23 06:55:20'),
(64, 3, 'TRX-20260324-EA0D3F', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-24 07:15:58', 'Administrator', '2026-03-24 14:16:19'),
(65, 3, 'TRX-20260324-ECD23C', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-24 07:27:58', NULL, NULL),
(66, 15, 'TRX-20260324-E089AF', 380000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-24 07:42:54', NULL, NULL),
(67, 15, 'TRX-20260327-57EEE8', 95000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-27 04:11:49', NULL, NULL),
(68, 15, 'TRX-20260327-805B4A', 190000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-27 08:49:44', 'Administrator', '2026-03-28 21:16:00'),
(69, 3, 'TRX-20260328-A9F9AB', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-28 12:39:54', 'Administrator', '2026-03-28 19:40:14'),
(70, 3, 'TRX-20260328-664EEC', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-28 15:18:46', 'Administrator', '2026-03-29 10:09:51'),
(71, 16, 'TRX-20260328-C398DE', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-28 15:28:12', 'Administrator', '2026-03-29 10:02:00'),
(72, 15, 'TRX-20260329-68E39F', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-29 03:13:58', 'Administrator', '2026-03-29 10:14:20'),
(73, 16, 'TRX-20260329-9DF36F', 65000.00, 'cod', 'pending', 'belum_diambil', NULL, '2026-03-29 11:19:53', NULL, NULL),
(74, 3, 'TRX-20260331-1210BE', 95000.00, 'cod', 'dikonfirmasi', 'sudah_diambil', NULL, '2026-03-31 07:22:41', 'Administrator', '2026-03-31 14:23:12');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transaction_details`
--

CREATE TABLE `transaction_details` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `harga_satuan` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `transaction_details`
--

INSERT INTO `transaction_details` (`id`, `transaction_id`, `product_id`, `quantity`, `harga_satuan`, `subtotal`) VALUES
(1, 1, 1, 1, 75000.00, 75000.00),
(2, 2, 2, 1, 95000.00, 95000.00),
(3, 3, 1, 1, 75000.00, 75000.00),
(4, 4, 1, 1, 75000.00, 75000.00),
(5, 5, 2, 1, 95000.00, 95000.00),
(6, 6, 2, 1, 95000.00, 95000.00),
(7, 7, 2, 1, 95000.00, 95000.00),
(8, 8, 2, 1, 95000.00, 95000.00),
(9, 9, 1, 1, 65000.00, 65000.00),
(10, 10, 2, 1, 95000.00, 95000.00),
(11, 11, 2, 1, 95000.00, 95000.00),
(12, 12, 1, 1, 65000.00, 65000.00),
(13, 13, 1, 1, 65000.00, 65000.00),
(14, 14, 2, 1, 95000.00, 95000.00),
(15, 15, 2, 1, 95000.00, 95000.00),
(16, 16, 2, 1, 95000.00, 95000.00),
(17, 17, 2, 1, 95000.00, 95000.00),
(18, 18, 2, 1, 95000.00, 95000.00),
(19, 19, 2, 1, 95000.00, 95000.00),
(20, 20, 1, 1, 65000.00, 65000.00),
(21, 21, 1, 1, 65000.00, 65000.00),
(22, 22, 2, 1, 95000.00, 95000.00),
(23, 23, 2, 1, 95000.00, 95000.00),
(24, 24, 2, 1, 95000.00, 95000.00),
(25, 25, 2, 1, 95000.00, 95000.00),
(26, 26, 2, 1, 95000.00, 95000.00),
(27, 27, 2, 1, 95000.00, 95000.00),
(28, 28, 2, 1, 95000.00, 95000.00),
(29, 29, 2, 1, 95000.00, 95000.00),
(30, 30, 1, 1, 65000.00, 65000.00),
(31, 31, 2, 1, 95000.00, 95000.00),
(32, 32, 2, 1, 95000.00, 95000.00),
(33, 33, 2, 1, 95000.00, 95000.00),
(34, 34, 2, 1, 95000.00, 95000.00),
(35, 35, 2, 1, 95000.00, 95000.00),
(36, 36, 2, 1, 95000.00, 95000.00),
(37, 37, 2, 1, 95000.00, 95000.00),
(38, 38, 2, 1, 95000.00, 95000.00),
(39, 39, 2, 1, 95000.00, 95000.00),
(40, 40, 2, 1, 95000.00, 95000.00),
(41, 41, 2, 1, 95000.00, 95000.00),
(42, 42, 2, 1, 95000.00, 95000.00),
(43, 43, 2, 1, 95000.00, 95000.00),
(44, 44, 1, 6, 65000.00, 390000.00),
(45, 45, 1, 1, 65000.00, 65000.00),
(46, 46, 1, 1, 65000.00, 65000.00),
(47, 47, 1, 1, 65000.00, 65000.00),
(48, 48, 2, 1, 95000.00, 95000.00),
(49, 57, 2, 1, 0.00, 0.00),
(50, 58, 1, 2, 65000.00, 130000.00),
(51, 59, 2, 2, 95000.00, 190000.00),
(52, 61, 2, 1, 95000.00, 95000.00),
(53, 62, 2, 3, 95000.00, 285000.00),
(54, 63, 2, 3, 95000.00, 285000.00),
(55, 64, 2, 1, 95000.00, 95000.00),
(56, 65, 2, 1, 95000.00, 95000.00),
(57, 66, 2, 4, 95000.00, 380000.00),
(58, 67, 2, 1, 95000.00, 95000.00),
(59, 68, 2, 2, 95000.00, 190000.00),
(60, 69, 2, 1, 95000.00, 95000.00),
(61, 70, 2, 1, 95000.00, 95000.00),
(62, 71, 2, 1, 95000.00, 95000.00),
(63, 72, 2, 1, 95000.00, 95000.00),
(64, 73, 1, 1, 65000.00, 65000.00),
(65, 74, 2, 1, 95000.00, 95000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','customer') NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telepon` varchar(15) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `nama_lengkap`, `email`, `telepon`, `alamat`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administrator', 'admin@tefacoffee.com', '08123456789', NULL, '2026-02-27 13:15:37'),
(2, 'manager', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'manager', 'Manager TEFA', 'manager@tefacoffee.com', '08123456788', NULL, '2026-02-27 13:15:37'),
(3, 'joko', '$2y$10$s/bDT1IUNXRH/IvbQIFvs.F2SDvn0VHyLyu/FPkV6kwsNDAwHOz3i', 'customer', 'Joko sutrisno', 'kpcjoko@gmail.com', '0836636365', 'kalibendo', '2026-02-27 14:21:20'),
(6, 'carin', '$2y$10$znDwBjuOYo2tKQL8R2CLsuM/T025eEgE6UqbNfrRB.6fNsFjbymS6', 'customer', 'carirena putri', 'achmad.kevin988@smk.belajar.id', '928746', 'glagah', '2026-02-28 03:39:51'),
(7, '', 'e840deb4', 'customer', 'kepin darto', '', '087234561234', 'situbondo ', '2026-03-18 07:12:37'),
(15, 'Darto', '$2y$10$2Y209faTqV3i1KYV89BtIeVgQvTl5fNTeoM/2F.3d.oqiT2B3r5PK', 'customer', 'jancokkk', 'darto1@gmail.com', '087654345012', 'situbondo jawa timur', '2026-03-19 04:36:18'),
(16, 'maden', '$2y$10$8YdSAzj.uuDeBKKhBWuPy.iocDSWWyG2pImwpzxRK.AYNlClPM6AS', 'customer', 'dartooo', 'madentra@gmail.com', '087654876012', 'jombang jawa timur', '2026-03-28 15:27:33');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `coffee_beans`
--
ALTER TABLE `coffee_beans`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `inventory_usage`
--
ALTER TABLE `inventory_usage`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indeks untuk tabel `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_login_time` (`login_time`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `stock_movements_beans`
--
ALTER TABLE `stock_movements_beans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bean_id` (`bean_id`);

--
-- Indeks untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `coffee_beans`
--
ALTER TABLE `coffee_beans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `inventory_usage`
--
ALTER TABLE `inventory_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `stock_movements`
--
ALTER TABLE `stock_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT untuk tabel `stock_movements_beans`
--
ALTER TABLE `stock_movements_beans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT untuk tabel `transaction_details`
--
ALTER TABLE `transaction_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `inventory_usage`
--
ALTER TABLE `inventory_usage`
  ADD CONSTRAINT `inventory_usage_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `stock_movements`
--
ALTER TABLE `stock_movements`
  ADD CONSTRAINT `stock_movements_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Ketidakleluasaan untuk tabel `stock_movements_beans`
--
ALTER TABLE `stock_movements_beans`
  ADD CONSTRAINT `stock_movements_beans_ibfk_1` FOREIGN KEY (`bean_id`) REFERENCES `coffee_beans` (`id`);

--
-- Ketidakleluasaan untuk tabel `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `transaction_details`
--
ALTER TABLE `transaction_details`
  ADD CONSTRAINT `transaction_details_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`),
  ADD CONSTRAINT `transaction_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
