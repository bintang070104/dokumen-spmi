-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 20, 2026 at 06:19 PM
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
-- Database: `spmi_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `dokumen`
--

CREATE TABLE `dokumen` (
  `id` int(11) NOT NULL,
  `judul_dokumen` varchar(200) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `nama_file` varchar(255) NOT NULL,
  `ukuran_file` int(10) UNSIGNED DEFAULT NULL COMMENT 'Ukuran file dalam bytes',
  `tipe_file` varchar(100) DEFAULT NULL COMMENT 'MIME type file',
  `versi` varchar(10) DEFAULT '1.0',
  `tanggal_upload` date NOT NULL,
  `tanggal_kadaluarsa` date NOT NULL,
  `status` enum('aktif','kadaluarsa') DEFAULT 'aktif',
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `folder_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dokumen`
--

INSERT INTO `dokumen` (`id`, `judul_dokumen`, `kategori_id`, `nama_file`, `ukuran_file`, `tipe_file`, `versi`, `tanggal_upload`, `tanggal_kadaluarsa`, `status`, `uploaded_by`, `created_at`, `folder_id`) VALUES
(22, 'standar visi', 16, '2026/07/20260702_174955_download_SDLC_1938b371.jpeg', 9665, 'image/jpeg', '1.0', '2026-07-02', '2026-07-02', 'aktif', 8, '2026-07-02 15:49:55', 1),
(23, 'izin penelitian', 21, '2026/07/20260716_100042_izin_penelitian_sttis_jpg_ecdca1b0.jpeg', 3947176, 'image/jpeg', '1.0', '2026-07-16', '2026-07-25', 'aktif', 8, '2026-07-16 08:00:42', 7),
(24, 'daftar lulusan 2026', 21, '2026/07/20260716_100149_Muhammad_Bintang_Raitama_a79a3707.pdf', 4134259, 'application/pdf', '1.0', '2026-07-16', '2027-01-16', 'aktif', 8, '2026-07-16 08:01:49', 7);

-- --------------------------------------------------------

--
-- Table structure for table `folders`
--

CREATE TABLE `folders` (
  `id` int(11) NOT NULL,
  `nama_folder` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `folders`
--

INSERT INTO `folders` (`id`, `nama_folder`, `parent_id`, `created_by`, `created_at`) VALUES
(1, '2.  ASPEK PENELITIAN', NULL, 8, '2026-07-02 15:22:19'),
(6, '1.  ASPEK PENDIDIKAN', NULL, 8, '2026-07-11 05:15:36'),
(7, '1.1. Standar Kompetensi Lulusan (1-2)', 6, 8, '2026-07-11 09:03:44'),
(8, '1.2. Standar Proses Pembelajaran (3-12)', 6, 8, '2026-07-11 09:04:28'),
(9, '2.1. Standar Hasil Penelitian (21)', 1, 8, '2026-07-11 09:06:20'),
(10, '3. ASPEK PENGABDIAN KEPADA MASYARAKAT (PkM)', NULL, 8, '2026-07-16 07:28:19'),
(11, '4. ASPEK STANDAR INOVASI PRODI (38)', NULL, 8, '2026-07-16 07:51:08');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`, `created_at`) VALUES
(16, '10. ASPEK SISTEM INFORMASI (SI)', '2026-07-02 15:40:10'),
(17, '3. ASPEK PENGABDIAN KEPADA MASYARAKAT (PkM)', '2026-07-02 15:58:46'),
(18, '2. ASPEK PENELITIAN', '2026-07-02 15:59:13'),
(19, '8. ASPEK PENGELOLA ORGANISASI', '2026-07-02 15:59:30'),
(20, '7. ASPEK SARANA PRASARANA', '2026-07-02 15:59:50'),
(21, '1. ASPEK PENDIDIKAN', '2026-07-02 16:00:04'),
(22, '9. ASPEK KERJASAMA', '2026-07-02 16:00:21'),
(23, '5. ASPEK SUMBER DAYA MANUSIA', '2026-07-02 16:00:47'),
(24, '4. ASPEK STANDAR INOVASI PRODI', '2026-07-02 16:01:11'),
(25, '11. ASPEK KEMAHASISWAAN, ALUMNI, DAN KEWIRAUSAHAAN (KAK)', '2026-07-11 08:49:02'),
(26, '12. ASPEK VISI, MISI, TUJUAN dan STRATEGI', '2026-07-11 08:49:45'),
(27, '13. ASPEK SPMI', '2026-07-11 08:50:18');

-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `aksi` varchar(50) NOT NULL,
  `detail` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id`, `user_id`, `aksi`, `detail`, `created_at`) VALUES
(1, 8, 'upload_dokumen', 'Upload dokumen: sempro3', '2026-04-02 08:03:40'),
(2, 4, 'login', 'Login berhasil', '2026-04-02 08:09:33'),
(3, 4, 'hapus_dokumen', 'Menghapus dokumen: sempro', '2026-04-02 08:23:13'),
(4, 4, 'download_dokumen', 'Download: sempro3 (20260402_150340_Wireframe_Toko-bintang_d5861a26.pdf)', '2026-04-02 08:24:36'),
(5, 8, 'login', 'Login berhasil', '2026-04-02 08:27:47'),
(6, 8, 'download_dokumen', 'Download: sempro3 (20260402_150340_Wireframe_Toko-bintang_d5861a26.pdf)', '2026-04-02 08:28:39'),
(7, 8, 'edit_dokumen', 'Mengedit dokumen: sempro3', '2026-04-02 08:31:17'),
(8, 9, 'login', 'Login berhasil', '2026-04-02 08:32:37'),
(9, 4, 'login', 'Login berhasil', '2026-04-03 07:29:30'),
(10, 8, 'login', 'Login berhasil', '2026-04-03 07:30:03'),
(11, 9, 'login', 'Login berhasil', '2026-04-03 07:30:24'),
(12, 4, 'login', 'Login berhasil', '2026-04-11 22:10:27'),
(13, 8, 'login', 'Login berhasil', '2026-04-11 23:18:59'),
(14, 8, 'edit_dokumen', 'Mengedit dokumen: sempro3', '2026-04-11 23:22:54'),
(15, 4, 'login', 'Login berhasil', '2026-04-11 23:26:19'),
(16, 8, 'login', 'Login berhasil', '2026-04-17 22:55:28'),
(17, 8, 'download_dokumen', 'Download: sempro3 (20260402_150340_Wireframe_Toko-bintang_d5861a26.pdf)', '2026-04-17 23:51:18'),
(18, 9, 'login', 'Login berhasil', '2026-04-17 23:52:39'),
(19, 4, 'login', 'Login berhasil', '2026-05-16 01:07:11'),
(20, 4, 'login', 'Login berhasil', '2026-05-16 01:29:09'),
(21, 8, 'login', 'Login berhasil', '2026-05-16 01:29:52'),
(22, 4, 'login', 'Login berhasil', '2026-05-28 07:01:47'),
(23, 4, 'login', 'Login berhasil', '2026-05-28 07:14:04'),
(24, 8, 'login', 'Login berhasil', '2026-05-28 08:11:26'),
(25, 8, 'download_dokumen', 'Download: sempro3 (20260402_150340_Wireframe_Toko-bintang_d5861a26.pdf)', '2026-05-28 08:26:14'),
(26, 8, 'edit_dokumen', 'Mengedit dokumen: sempro2', '2026-05-28 10:30:31'),
(27, 8, 'upload_dokumen', 'Upload dokumen: bab 3 skripsi', '2026-05-28 10:31:45'),
(28, 8, 'download_dokumen', 'Download: bab 3 skripsi (20260528_173145_skripsi_BAB_III_part_2_1bd7f358.docx)', '2026-05-28 10:32:07'),
(29, 4, 'login', 'Login berhasil', '2026-05-29 11:42:48'),
(30, 4, 'login', 'Login berhasil', '2026-05-29 23:18:16'),
(31, 8, 'login', 'Login berhasil', '2026-05-29 23:19:19'),
(32, 8, 'upload_dokumen', 'Upload dokumen: sempro4', '2026-05-29 23:20:29'),
(33, 8, 'download_dokumen', 'Download: sempro4 (20260530_062029_PANDUAN_PENULISAN_SKRIPSI_PROGRAM_STUDI_TEKNIK_INFORMATIKA_STTI_SONY_SUGEMA_TAHUN_2025-2026_ok_e40baa49.docx)', '2026-05-29 23:21:36'),
(34, 8, 'edit_dokumen', 'Mengedit dokumen: sempro2', '2026-05-29 23:22:18'),
(35, 8, 'edit_dokumen', 'Mengedit dokumen: sempro3', '2026-05-29 23:23:56'),
(36, 8, 'edit_dokumen', 'Mengedit dokumen: sempro2', '2026-05-29 23:24:08'),
(37, 4, 'login', 'Login berhasil', '2026-05-30 06:54:55'),
(38, 4, 'hapus_dokumen', 'Menghapus dokumen: sempro2', '2026-05-30 06:55:26'),
(39, 8, 'login', 'Login berhasil', '2026-06-01 02:17:10'),
(40, 8, 'upload_dokumen', 'Upload dokumen: folmulir pendaftaran skripsi', '2026-06-01 02:19:52'),
(41, 4, 'login', 'Login berhasil', '2026-06-01 06:37:21'),
(42, 10, 'login', 'Login berhasil', '2026-06-01 09:22:24'),
(43, 8, 'login', 'Login berhasil', '2026-06-01 09:23:06'),
(44, 10, 'login', 'Login berhasil', '2026-06-01 09:25:06'),
(45, 10, 'upload_dokumen', 'Upload dokumen: dokumen pribadi', '2026-06-01 09:28:39'),
(46, 4, 'login', 'Login berhasil', '2026-06-01 09:32:35'),
(47, 10, 'login', 'Login berhasil', '2026-06-01 09:35:16'),
(48, 4, 'login', 'Login berhasil', '2026-06-01 09:48:11'),
(49, 11, 'login', 'Login berhasil', '2026-06-01 09:48:58'),
(50, 4, 'login', 'Login berhasil', '2026-06-01 10:00:48'),
(51, 12, 'login', 'Login berhasil', '2026-06-01 10:04:51'),
(52, 10, 'login', 'Login berhasil', '2026-06-01 10:08:01'),
(53, 10, 'upload_dokumen', 'Upload dokumen: hasil rapat kelulusan', '2026-06-01 10:10:39'),
(54, 11, 'login', 'Login berhasil', '2026-06-01 10:16:37'),
(55, 10, 'login', 'Login berhasil', '2026-06-05 09:46:57'),
(56, 10, 'login', 'Login berhasil', '2026-06-05 09:52:24'),
(57, 10, 'login', 'Login berhasil', '2026-06-05 09:55:54'),
(58, 10, 'login', 'Login berhasil', '2026-06-05 10:03:11'),
(59, 4, 'login', 'Login berhasil', '2026-06-05 10:03:40'),
(60, 10, 'login', 'Login berhasil', '2026-06-05 10:08:51'),
(61, 9, 'login', 'Login berhasil', '2026-06-05 10:09:54'),
(62, 4, 'login', 'Login berhasil', '2026-06-05 10:38:52'),
(63, 10, 'login', 'Login berhasil', '2026-06-05 11:13:33'),
(64, 10, 'upload_dokumen', 'Upload dokumen: DRAFT SKRIPSI DWI JAYANT', '2026-06-05 11:15:17'),
(65, 10, 'upload_dokumen', 'Upload dokumen: nama dosen untuk surat pernyataan', '2026-06-05 11:16:35'),
(66, 10, 'upload_dokumen', 'Upload dokumen: PPT_SIDANG SKRIPSI_DWI JAYANTI_11210377 - dwi jayanti', '2026-06-05 11:17:36'),
(67, 10, 'upload_dokumen', 'Upload dokumen: FORMAT Pengajuan Judul Skripsi Tugas akhir tahun akademik 2025-2026', '2026-06-05 11:22:22'),
(68, 10, 'upload_dokumen', 'Upload dokumen: FORMULIR SEMINAR PROPOSAL SKRIPSI TA 2025-2026', '2026-06-05 11:23:25'),
(69, 10, 'upload_dokumen', 'Upload dokumen: Kartu Bimbingan Skripsi_2025-2026', '2026-06-05 11:24:29'),
(70, 10, 'upload_dokumen', 'Upload dokumen: Permohonan Ijin Penelitian-riset Skripsi', '2026-06-05 11:25:11'),
(71, 10, 'upload_dokumen', 'Upload dokumen: Judul Skripsi Mahasiswa angkatan 2022', '2026-06-05 11:26:40'),
(72, 12, 'login', 'Login berhasil', '2026-06-05 11:28:35'),
(73, 10, 'login', 'Login berhasil', '2026-06-05 11:55:51'),
(74, 10, 'edit_dokumen', 'Mengedit dokumen: Judul Skripsi Mahasiswa angkatan 2022', '2026-06-05 12:02:51'),
(75, 9, 'login', 'Login berhasil', '2026-06-05 12:03:25'),
(76, 4, 'login', 'Login berhasil', '2026-06-05 12:04:40'),
(77, 12, 'login', 'Login berhasil', '2026-06-05 12:08:20'),
(78, 10, 'login', 'Login berhasil', '2026-06-05 12:14:55'),
(79, 11, 'login', 'Login berhasil', '2026-06-05 12:17:41'),
(80, 10, 'login', 'Login berhasil', '2026-06-05 12:21:43'),
(81, 10, 'upload_dokumen', 'Upload dokumen: SURAT PERMOHONAN DAN PERNYATAAN 2', '2026-06-05 12:24:08'),
(82, 12, 'login', 'Login berhasil', '2026-06-05 12:25:00'),
(83, 10, 'login', 'Login berhasil', '2026-06-17 05:48:50'),
(84, 4, 'login', 'Login berhasil', '2026-06-17 05:49:26'),
(85, 4, 'login', 'Login berhasil', '2026-06-22 04:45:34'),
(86, 8, 'login', 'Login berhasil', '2026-06-22 04:47:08'),
(87, 8, 'download_dokumen', 'Download: sempro3 (20260402_150340_Wireframe_Toko-bintang_d5861a26.pdf)', '2026-06-22 04:48:06'),
(88, 8, 'upload_dokumen', 'Upload dokumen: M DAEROBI_SEMPRO-3', '2026-06-22 04:57:47'),
(89, 10, 'login', 'Login berhasil', '2026-06-22 04:58:18'),
(90, 9, 'login', 'Login berhasil', '2026-06-22 04:59:21'),
(91, 4, 'login', 'Login berhasil', '2026-06-22 05:00:27'),
(92, 4, 'login', 'Login berhasil', '2026-07-02 10:00:53'),
(93, 8, 'login', 'Login berhasil', '2026-07-02 10:14:12'),
(94, 8, 'upload_dokumen', 'Upload dokumen: tes', '2026-07-02 10:23:30'),
(95, 8, 'edit_dokumen', 'Mengedit dokumen: tes1', '2026-07-02 10:25:53'),
(96, 8, 'upload_dokumen', 'Upload dokumen: tes2', '2026-07-02 10:26:47'),
(97, 4, 'login', 'Login berhasil', '2026-07-02 10:27:39'),
(98, 4, 'hapus_dokumen', 'Menghapus dokumen: tes2', '2026-07-02 10:27:57'),
(99, 4, 'hapus_dokumen', 'Menghapus dokumen: tes1', '2026-07-02 10:28:01'),
(100, 4, 'hapus_dokumen', 'Menghapus dokumen: M DAEROBI_SEMPRO-3', '2026-07-02 10:28:06'),
(101, 4, 'hapus_dokumen', 'Menghapus dokumen: SURAT PERMOHONAN DAN PERNYATAAN 2', '2026-07-02 10:28:10'),
(102, 4, 'hapus_dokumen', 'Menghapus dokumen: sempro3', '2026-07-02 10:28:15'),
(103, 4, 'hapus_dokumen', 'Menghapus dokumen: bab 3 skripsi', '2026-07-02 10:28:19'),
(104, 4, 'hapus_dokumen', 'Menghapus dokumen: sempro4', '2026-07-02 10:28:23'),
(105, 4, 'hapus_dokumen', 'Menghapus dokumen: Judul Skripsi Mahasiswa angkatan 2022', '2026-07-02 10:28:27'),
(106, 4, 'hapus_dokumen', 'Menghapus dokumen: folmulir pendaftaran skripsi', '2026-07-02 10:28:33'),
(107, 4, 'hapus_dokumen', 'Menghapus dokumen: Permohonan Ijin Penelitian-riset Skripsi', '2026-07-02 10:28:36'),
(108, 4, 'hapus_dokumen', 'Menghapus dokumen: Kartu Bimbingan Skripsi_2025-2026', '2026-07-02 10:28:40'),
(109, 4, 'hapus_dokumen', 'Menghapus dokumen: FORMULIR SEMINAR PROPOSAL SKRIPSI TA 2025-2026', '2026-07-02 10:28:44'),
(110, 4, 'hapus_dokumen', 'Menghapus dokumen: FORMAT Pengajuan Judul Skripsi Tugas akhir tahun akademik 2025-2026', '2026-07-02 10:28:48'),
(111, 4, 'hapus_dokumen', 'Menghapus dokumen: PPT_SIDANG SKRIPSI_DWI JAYANTI_11210377 - dwi jayanti', '2026-07-02 10:28:51'),
(112, 4, 'hapus_dokumen', 'Menghapus dokumen: nama dosen untuk surat pernyataan', '2026-07-02 10:28:55'),
(113, 4, 'hapus_dokumen', 'Menghapus dokumen: DRAFT SKRIPSI DWI JAYANT', '2026-07-02 10:28:58'),
(114, 4, 'hapus_dokumen', 'Menghapus dokumen: hasil rapat kelulusan', '2026-07-02 10:29:02'),
(115, 4, 'hapus_dokumen', 'Menghapus dokumen: dokumen pribadi', '2026-07-02 10:29:06'),
(116, 8, 'login', 'Login berhasil', '2026-07-02 10:38:09'),
(117, 4, 'login', 'Login berhasil', '2026-07-02 10:39:27'),
(118, 4, 'upload_dokumen', 'Upload dokumen: standar visi (Folder ID: 1)', '2026-07-02 10:40:57'),
(119, 8, 'login', 'Login berhasil', '2026-07-02 10:48:55'),
(120, 8, 'upload_dokumen', 'Upload dokumen: standar visi (Folder ID: 1)', '2026-07-02 10:49:55'),
(121, 10, 'login', 'Login berhasil', '2026-07-02 10:53:21'),
(122, 4, 'login', 'Login berhasil', '2026-07-02 10:54:48'),
(123, 4, 'hapus_dokumen', 'Menghapus dokumen: standar visi', '2026-07-02 10:55:11'),
(124, 4, 'login', 'Login berhasil', '2026-07-06 11:09:13'),
(125, 4, 'login', 'Login berhasil', '2026-07-10 23:11:52'),
(126, 8, 'login', 'Login berhasil', '2026-07-10 23:15:30'),
(127, 8, 'login', 'Login berhasil', '2026-07-11 01:48:02'),
(128, 4, 'login', 'Login berhasil', '2026-07-11 02:47:10'),
(129, 8, 'login', 'Login berhasil', '2026-07-11 04:02:25'),
(130, 10, 'login', 'Login berhasil', '2026-07-16 01:09:52'),
(131, 8, 'login', 'Login berhasil', '2026-07-16 01:10:17'),
(132, 4, 'login', 'Login berhasil', '2026-07-16 02:51:33'),
(133, 8, 'login', 'Login berhasil', '2026-07-16 02:54:31'),
(134, 9, 'login', 'Login berhasil', '2026-07-16 02:56:13'),
(135, 4, 'login', 'Login berhasil', '2026-07-16 02:58:17'),
(136, 8, 'login', 'Login berhasil', '2026-07-16 02:59:22'),
(137, 8, 'upload_dokumen', 'Upload dokumen: izin penelitian (Folder ID: 7)', '2026-07-16 03:00:42'),
(138, 8, 'upload_dokumen', 'Upload dokumen: daftar lulusan 2026 (Folder ID: 7)', '2026-07-16 03:01:49');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','operator','pimpinan') NOT NULL DEFAULT 'operator',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `password`, `role`, `created_at`) VALUES
(4, 'Administrator', 'admin@spmi.com', '$2y$10$c0pPFavlxfeaxwpnu38gq.pwc8d962Jh3qgFEC57o0MXe/d8AdpdK', 'admin', '2026-03-12 04:20:29'),
(8, 'operator', 'operator@spmi.com', '$2y$10$5V4815O1zEaEaosUzJs/je2EohEkY.9yDt9n..IATkA6E.JnEF/oC', 'operator', '2026-04-02 12:32:34'),
(9, 'pimpinan', 'pimpinan@spmi.com', '$2y$10$BrOEExUw9Askuv9fhhyTaucMvCol3WnCqIUTDlsEVPKqdr2xCP07y', 'pimpinan', '2026-04-02 12:33:13'),
(10, 'Muhammad Bintang Raitama', 'muhamadbintang864@gmail.com', '$2y$10$JWLDv6.j0VwIGfG07rqFdeSWt9j1NtzP/QaDgHYVXVmH1kFWp7xZy', 'operator', '2026-06-01 11:42:37'),
(11, 'wahab', 'wahab@gmail.com', '$2y$10$Rdh1YnC9FSAQOwKpAciO7uObKdgwzbEIAJBcshljEGSujZhbGB9gG', 'pimpinan', '2026-06-01 14:48:47'),
(12, 'muhamad daerobi', 'daerobi@gmail.com', '$2y$10$SolB/7ISrnxgcfcaxMbDO.4eLWreMSnY4oD0RHI6sxLufjSsZkGOy', 'admin', '2026-06-01 15:04:14'),
(13, 'kemahasiswaan', 'kemahasiswaan@gmail.com', '$2y$10$cXYU3JS7qBHBfMq8A.pblO0xc4/9x3qsmfAXlPexh6o.dxoPAwgGW', 'operator', '2026-06-05 17:29:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_dokumen_folder` (`folder_id`);

--
-- Indexes for table `folders`
--
ALTER TABLE `folders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_folders_parent` (`parent_id`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_time` (`user_id`,`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dokumen`
--
ALTER TABLE `dokumen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `folders`
--
ALTER TABLE `folders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD CONSTRAINT `dokumen_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dokumen_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dokumen_ibfk_3` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `folders`
--
ALTER TABLE `folders`
  ADD CONSTRAINT `folders_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `folders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
