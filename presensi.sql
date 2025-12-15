-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 10, 2025 at 07:56 AM
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
-- Database: `presensi`
--

-- --------------------------------------------------------

--
-- Table structure for table `absen_asisten`
--

CREATE TABLE `absen_asisten` (
  `id` int(11) NOT NULL,
  `jadwal_id` int(11) NOT NULL,
  `kode_asisten` varchar(10) NOT NULL,
  `status` enum('hadir','izin','sakit') DEFAULT 'hadir',
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `pengganti` varchar(10) DEFAULT NULL,
  `catatan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `absen_asisten`
--

INSERT INTO `absen_asisten` (`id`, `jadwal_id`, `kode_asisten`, `status`, `jam_masuk`, `jam_keluar`, `pengganti`, `catatan`) VALUES
(35, 413, 'AST003', 'hadir', '13:55:39', NULL, NULL, NULL),
(36, 423, 'AST003', 'hadir', '14:18:15', NULL, NULL, NULL),
(37, 433, 'AST001', 'hadir', '22:46:58', NULL, NULL, NULL),
(38, 443, 'AST001', 'sakit', NULL, NULL, 'AST005', 'tolong gantikan saya untuk jagain'),
(39, 453, 'AST001', 'hadir', '09:42:54', NULL, NULL, NULL),
(40, 463, 'AST001', 'hadir', '10:19:15', NULL, NULL, NULL),
(41, 473, 'AST003', 'hadir', '08:14:35', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `asisten`
--

CREATE TABLE `asisten` (
  `id` int(11) NOT NULL,
  `kode_asisten` varchar(10) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `kode_mk` varchar(10) DEFAULT NULL COMMENT 'Keahlian utama (opsional, referensi saja)',
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `asisten`
--

INSERT INTO `asisten` (`id`, `kode_asisten`, `user_id`, `nama`, `no_hp`, `foto`, `kode_mk`, `status`) VALUES
(1, 'AST001', 3, 'Muhammad Iniesta Wildan Bromo Putra', '083841426411', 'uploads/profil/ast_AST001_1765214164.png', 'MK002', 'aktif'),
(2, 'AST002', 5, 'budi santosa', '083841426413', NULL, 'MK001', 'aktif'),
(3, 'AST003', 6, 'Anik Yuliana', '083841426422', 'uploads/profil/ast_AST003_1764904445.png', 'MK003', 'aktif'),
(4, 'AST004', 8, 'Mulyono', '083841416422', NULL, 'MK004', 'aktif'),
(5, 'AST005', 15, 'Marco', '083822426411', NULL, 'MK002', 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id` int(11) NOT NULL,
  `pertemuan_ke` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `kode_lab` varchar(10) DEFAULT NULL,
  `kode_kelas` char(1) NOT NULL,
  `kode_mk` varchar(10) NOT NULL,
  `materi` varchar(100) NOT NULL,
  `kode_asisten_1` varchar(10) DEFAULT NULL,
  `kode_asisten_2` varchar(10) DEFAULT NULL,
  `jenis` enum('materi','inhall','praresponsi','responsi') DEFAULT 'materi',
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id`, `pertemuan_ke`, `tanggal`, `jam_mulai`, `jam_selesai`, `kode_lab`, `kode_kelas`, `kode_mk`, `materi`, `kode_asisten_1`, `kode_asisten_2`, `jenis`, `keterangan`, `created_at`) VALUES
(413, 1, '2025-12-05', '13:52:00', '14:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 1 - Pengenalan', 'AST003', NULL, 'materi', NULL, '2025-12-05 06:53:02'),
(414, 2, '2025-12-12', '13:52:00', '14:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 2 - Dasar', 'AST003', NULL, 'materi', NULL, '2025-12-05 06:53:02'),
(415, 3, '2025-12-19', '13:52:00', '14:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 3 - Lanjutan I', 'AST003', NULL, 'materi', NULL, '2025-12-05 06:53:02'),
(416, 4, '2025-12-26', '13:52:00', '14:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 4 - Lanjutan II', 'AST003', NULL, 'materi', NULL, '2025-12-05 06:53:02'),
(417, 5, '2026-01-02', '13:52:00', '14:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 5 - Praktik I', 'AST003', NULL, 'materi', NULL, '2025-12-05 06:53:02'),
(418, 6, '2026-01-09', '13:52:00', '14:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 6 - Praktik II', 'AST003', NULL, 'materi', NULL, '2025-12-05 06:53:02'),
(419, 7, '2026-01-16', '13:52:00', '14:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 7 - Praktik III', 'AST003', NULL, 'materi', NULL, '2025-12-05 06:53:02'),
(420, 8, '2026-01-23', '13:52:00', '14:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 8 - Review', 'AST003', NULL, 'materi', NULL, '2025-12-05 06:53:02'),
(422, 10, '2026-02-06', '13:52:00', '14:00:00', 'LAB003', 'A', 'MK003', 'Responsi', 'AST003', NULL, 'responsi', NULL, '2025-12-05 06:53:02'),
(423, 1, '2025-12-05', '14:17:00', '15:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 1 - Pengenalan', 'AST003', NULL, 'materi', NULL, '2025-12-05 07:15:55'),
(424, 2, '2025-12-12', '14:17:00', '15:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 2 - Dasar', 'AST003', NULL, 'materi', NULL, '2025-12-05 07:15:55'),
(425, 3, '2025-12-19', '14:17:00', '15:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 3 - Lanjutan I', 'AST003', NULL, 'materi', NULL, '2025-12-05 07:15:55'),
(426, 4, '2025-12-26', '14:17:00', '15:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 4 - Lanjutan II', 'AST003', NULL, 'materi', NULL, '2025-12-05 07:15:55'),
(427, 5, '2026-01-02', '14:17:00', '15:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 5 - Praktik I', 'AST003', NULL, 'materi', NULL, '2025-12-05 07:15:55'),
(428, 6, '2026-01-09', '14:17:00', '15:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 6 - Praktik II', 'AST003', NULL, 'materi', NULL, '2025-12-05 07:15:55'),
(429, 7, '2026-01-16', '14:17:00', '15:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 7 - Praktik III', 'AST003', NULL, 'materi', NULL, '2025-12-05 07:15:55'),
(430, 8, '2026-01-23', '14:17:00', '15:00:00', 'LAB003', 'A', 'MK003', 'Pertemuan 8 - Review', 'AST003', NULL, 'materi', NULL, '2025-12-05 07:15:55'),
(431, 9, '2026-01-30', '14:17:00', '15:00:00', 'LAB003', 'A', 'MK003', 'Praresponsi', 'AST003', NULL, 'praresponsi', NULL, '2025-12-05 07:15:55'),
(432, 10, '2026-02-06', '14:17:00', '15:00:00', 'LAB003', 'A', 'MK003', 'Responsi', 'AST003', NULL, 'responsi', NULL, '2025-12-05 07:15:55'),
(433, 1, '2025-12-05', '22:41:00', '23:58:00', 'LAB002', 'A', 'MK002', 'Pertemuan 1 - Pengenalan', 'AST001', NULL, 'materi', NULL, '2025-12-05 15:36:40'),
(434, 2, '2025-12-12', '22:41:00', '00:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 2 - Dasar', 'AST001', NULL, 'materi', NULL, '2025-12-05 15:36:40'),
(435, 3, '2025-12-19', '22:41:00', '00:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 3 - Lanjutan I', 'AST001', NULL, 'materi', NULL, '2025-12-05 15:36:40'),
(436, 4, '2025-12-26', '22:41:00', '00:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 4 - Lanjutan II', 'AST001', NULL, 'materi', NULL, '2025-12-05 15:36:40'),
(437, 5, '2026-01-02', '22:41:00', '00:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 5 - Praktik I', 'AST001', NULL, 'materi', NULL, '2025-12-05 15:36:40'),
(438, 6, '2026-01-09', '22:41:00', '00:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 6 - Praktik II', 'AST001', NULL, 'materi', NULL, '2025-12-05 15:36:41'),
(439, 7, '2026-01-16', '22:41:00', '00:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 7 - Praktik III', 'AST001', NULL, 'materi', NULL, '2025-12-05 15:36:42'),
(440, 8, '2026-01-23', '22:41:00', '00:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 8 - Review', 'AST001', NULL, 'materi', NULL, '2025-12-05 15:36:43'),
(441, 9, '2026-01-30', '22:41:00', '00:00:00', 'LAB002', 'A', 'MK002', 'Praresponsi', 'AST001', NULL, 'praresponsi', NULL, '2025-12-05 15:36:43'),
(442, 10, '2026-02-06', '22:41:00', '00:00:00', 'LAB002', 'A', 'MK002', 'Responsi', 'AST001', NULL, 'responsi', NULL, '2025-12-05 15:36:43'),
(443, 1, '2025-12-06', '07:00:00', '09:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 1 - Pengenalan', 'AST001', NULL, 'materi', NULL, '2025-12-05 16:58:27'),
(444, 2, '2025-12-13', '07:00:00', '09:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 2 - Dasar', 'AST001', NULL, 'materi', NULL, '2025-12-05 16:58:27'),
(445, 3, '2025-12-20', '07:00:00', '09:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 3 - Lanjutan I', 'AST001', NULL, 'materi', NULL, '2025-12-05 16:58:27'),
(446, 4, '2025-12-27', '07:00:00', '09:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 4 - Lanjutan II', 'AST001', NULL, 'materi', NULL, '2025-12-05 16:58:27'),
(447, 5, '2026-01-03', '07:00:00', '09:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 5 - Praktik I', 'AST001', NULL, 'materi', NULL, '2025-12-05 16:58:27'),
(448, 6, '2026-01-10', '07:00:00', '09:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 6 - Praktik II', 'AST001', NULL, 'materi', NULL, '2025-12-05 16:58:27'),
(449, 7, '2026-01-17', '07:00:00', '09:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 7 - Praktik III', 'AST001', NULL, 'materi', NULL, '2025-12-05 16:58:27'),
(450, 8, '2026-01-24', '07:00:00', '09:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 8 - Review', 'AST001', NULL, 'materi', NULL, '2025-12-05 16:58:27'),
(451, 9, '2026-01-31', '07:00:00', '09:00:00', 'LAB002', 'B', 'MK002', 'Praresponsi', 'AST001', NULL, 'praresponsi', NULL, '2025-12-05 16:58:27'),
(452, 10, '2026-02-07', '07:00:00', '09:00:00', 'LAB002', 'B', 'MK002', 'Responsi', 'AST001', NULL, 'responsi', NULL, '2025-12-05 16:58:27'),
(453, 1, '2025-12-08', '09:00:00', '10:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 1 - Pengenalan', 'AST001', NULL, 'materi', NULL, '2025-12-08 02:21:13'),
(454, 2, '2025-12-15', '09:00:00', '10:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 2 - Dasar', 'AST001', NULL, 'materi', NULL, '2025-12-08 02:21:14'),
(455, 3, '2025-12-22', '09:00:00', '10:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 3 - Lanjutan I', 'AST001', NULL, 'materi', NULL, '2025-12-08 02:21:15'),
(456, 4, '2025-12-29', '09:00:00', '10:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 4 - Lanjutan II', 'AST001', NULL, 'materi', NULL, '2025-12-08 02:21:15'),
(457, 5, '2026-01-05', '09:00:00', '10:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 5 - Praktik I', 'AST001', NULL, 'materi', NULL, '2025-12-08 02:21:16'),
(458, 6, '2026-01-12', '09:00:00', '10:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 6 - Praktik II', 'AST001', NULL, 'materi', NULL, '2025-12-08 02:21:17'),
(459, 7, '2026-01-19', '09:00:00', '10:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 7 - Praktik III', 'AST001', NULL, 'materi', NULL, '2025-12-08 02:21:18'),
(460, 8, '2026-01-26', '09:00:00', '10:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 8 - Review', 'AST001', NULL, 'materi', NULL, '2025-12-08 02:21:18'),
(461, 9, '2026-02-02', '09:00:00', '10:00:00', 'LAB002', 'A', 'MK002', 'Praresponsi', 'AST001', NULL, 'praresponsi', NULL, '2025-12-08 02:21:20'),
(462, 10, '2026-02-09', '09:00:00', '10:00:00', 'LAB002', 'A', 'MK002', 'Responsi', 'AST001', NULL, 'responsi', NULL, '2025-12-08 02:21:21'),
(463, 1, '2025-12-08', '10:12:00', '11:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 1 - Pengenalan', 'AST001', NULL, 'materi', NULL, '2025-12-08 03:13:08'),
(464, 2, '2025-12-15', '10:12:00', '11:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 2 - Dasar', 'AST001', NULL, 'materi', NULL, '2025-12-08 03:13:08'),
(465, 3, '2025-12-22', '10:12:00', '11:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 3 - Lanjutan I', 'AST001', NULL, 'materi', NULL, '2025-12-08 03:13:08'),
(466, 4, '2025-12-29', '10:12:00', '11:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 4 - Lanjutan II', 'AST001', NULL, 'materi', NULL, '2025-12-08 03:13:08'),
(467, 5, '2026-01-05', '10:12:00', '11:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 5 - Praktik I', 'AST001', NULL, 'materi', NULL, '2025-12-08 03:13:09'),
(468, 6, '2026-01-12', '10:12:00', '11:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 6 - Praktik II', 'AST001', NULL, 'materi', NULL, '2025-12-08 03:13:09'),
(469, 7, '2026-01-19', '10:12:00', '11:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 7 - Praktik III', 'AST001', NULL, 'materi', NULL, '2025-12-08 03:13:09'),
(470, 8, '2026-01-26', '10:12:00', '11:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 8 - Review', 'AST001', NULL, 'materi', NULL, '2025-12-08 03:13:09'),
(471, 9, '2026-02-02', '10:12:00', '11:00:00', 'LAB002', 'B', 'MK002', 'Praresponsi', 'AST001', NULL, 'praresponsi', NULL, '2025-12-08 03:13:09'),
(472, 10, '2026-02-09', '10:12:00', '11:00:00', 'LAB002', 'B', 'MK002', 'Responsi', 'AST001', NULL, 'responsi', NULL, '2025-12-08 03:13:09'),
(473, 1, '2025-12-09', '08:21:00', '10:00:00', 'LAB003', 'D', 'MK003', 'Pertemuan 1 - Pengenalan', 'AST003', NULL, 'materi', NULL, '2025-12-09 01:11:22'),
(474, 2, '2025-12-16', '08:21:00', '10:00:00', 'LAB003', 'D', 'MK003', 'Pertemuan 2 - Dasar', 'AST003', NULL, 'materi', NULL, '2025-12-09 01:11:22'),
(475, 3, '2025-12-23', '08:21:00', '10:00:00', 'LAB003', 'D', 'MK003', 'Pertemuan 3 - Lanjutan I', 'AST003', NULL, 'materi', NULL, '2025-12-09 01:11:23'),
(476, 4, '2025-12-30', '08:21:00', '10:00:00', 'LAB003', 'D', 'MK003', 'Pertemuan 4 - Lanjutan II', 'AST003', NULL, 'materi', NULL, '2025-12-09 01:11:23'),
(477, 5, '2026-01-06', '08:21:00', '10:00:00', 'LAB003', 'D', 'MK003', 'Pertemuan 5 - Praktik I', 'AST003', NULL, 'materi', NULL, '2025-12-09 01:11:23'),
(478, 6, '2026-01-13', '08:21:00', '10:00:00', 'LAB003', 'D', 'MK003', 'Pertemuan 6 - Praktik II', 'AST003', NULL, 'materi', NULL, '2025-12-09 01:11:23'),
(479, 7, '2026-01-20', '08:21:00', '10:00:00', 'LAB003', 'D', 'MK003', 'Pertemuan 7 - Praktik III', 'AST003', NULL, 'materi', NULL, '2025-12-09 01:11:23'),
(480, 8, '2026-01-27', '08:21:00', '10:00:00', 'LAB003', 'D', 'MK003', 'Pertemuan 8 - Review', 'AST003', NULL, 'materi', NULL, '2025-12-09 01:11:23'),
(481, 9, '2026-02-03', '08:21:00', '10:00:00', 'LAB003', 'D', 'MK003', 'Praresponsi', 'AST003', NULL, 'praresponsi', NULL, '2025-12-09 01:11:23'),
(482, 10, '2026-02-10', '08:21:00', '10:00:00', 'LAB003', 'D', 'MK003', 'Responsi', 'AST003', NULL, 'responsi', NULL, '2025-12-09 01:11:23');

-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `kode_kelas` char(1) NOT NULL,
  `nama_kelas` varchar(50) DEFAULT NULL,
  `program_studi` varchar(100) DEFAULT NULL,
  `tahun_ajaran` varchar(9) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kelas`
--

INSERT INTO `kelas` (`kode_kelas`, `nama_kelas`, `program_studi`, `tahun_ajaran`) VALUES
('A', 'Pemrograman', 'Teknik Informatika', '2024/2025'),
('B', 'Kelas B', 'Sistem Informasi', '2024/2025'),
('C', 'Kelas C', 'Teknik Komputer', '2024/2025'),
('D', 'Kelas D', 'Manajemen Informatika', '2024/2025');

-- --------------------------------------------------------

--
-- Table structure for table `lab`
--

CREATE TABLE `lab` (
  `id` int(11) NOT NULL,
  `kode_lab` varchar(10) NOT NULL,
  `nama_lab` varchar(50) DEFAULT NULL,
  `kapasitas` int(11) DEFAULT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `status` enum('active','maintenance') DEFAULT 'active',
  `kode_mk` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab`
--

INSERT INTO `lab` (`id`, `kode_lab`, `nama_lab`, `kapasitas`, `lokasi`, `status`, `kode_mk`) VALUES
(1, 'LAB001', 'Laboratorium BasisData', 30, 'Gedung A Lantai 1', 'active', 'MK001'),
(2, 'LAB002', 'Laboratorium Pemrograman', 30, 'Gedung A Lantai 3', 'active', 'MK002'),
(3, 'LAB003', 'Laboratorium Jaringan', 25, 'Gedung B Lantai 1', 'active', 'MK003'),
(4, 'LAB004', 'Laboratorium Statistika', 20, 'Gedung B Lantai 2', 'active', 'MK004');

-- --------------------------------------------------------

--
-- Table structure for table `log_presensi`
--

CREATE TABLE `log_presensi` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `aksi` varchar(50) DEFAULT NULL,
  `tabel` varchar(50) DEFAULT NULL,
  `id_record` int(11) DEFAULT NULL,
  `detail` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_presensi`
--

INSERT INTO `log_presensi` (`id`, `user_id`, `aksi`, `tabel`, `id_record`, `detail`, `created_at`) VALUES
(290, 22, 'LOGIN', 'users', 22, 'User login berhasil sebagai mahasiswa', '2025-12-05 06:55:08'),
(291, 22, 'PENGAJUAN_IZIN', 'penggantian_inhall', 13, 'Mahasiswa 120607 mengajukan sakit (pending approval): sakit', '2025-12-05 06:55:27'),
(292, 6, 'GENERATE_QR', 'qr_code_session', 83, 'QR Code untuk jadwal #413, expired: 2025-12-05 14:00:00', '2025-12-05 06:55:39'),
(293, 6, 'APPROVE_IZIN', 'penggantian_inhall', 13, 'Asisten AST003 menyetujui sakit mahasiswa 120607', '2025-12-05 06:55:48'),
(294, 21, 'LOGIN', 'users', 21, 'User login berhasil sebagai mahasiswa', '2025-12-05 06:58:33'),
(295, 6, 'GENERATE_QR', 'qr_code_session', 84, 'QR Code untuk jadwal #413, expired: 2025-12-05 14:00:00', '2025-12-05 06:59:27'),
(296, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 413, 'Mahasiswa 130607 presensi via QR di Laboratorium Jaringan', '2025-12-05 06:59:37'),
(297, 6, 'GENERATE_QR', 'qr_code_session', 85, 'QR Code untuk jadwal #413, expired: 2025-12-05 14:00:00', '2025-12-05 07:00:01'),
(298, 30, 'LOGIN', 'users', 30, 'User login berhasil sebagai mahasiswa', '2025-12-05 07:17:58'),
(299, 6, 'GENERATE_QR', 'qr_code_session', 86, 'QR Code untuk jadwal #423, expired: 2025-12-05 15:00:00', '2025-12-05 07:18:15'),
(300, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 423, 'Mahasiswa 00012345 presensi via QR di Laboratorium Jaringan', '2025-12-05 07:18:27'),
(301, 1, 'LOGIN', 'users', 1, 'User login berhasil sebagai admin', '2025-12-05 15:26:31'),
(302, 2, 'LOGIN', 'users', 2, 'User login berhasil sebagai mahasiswa', '2025-12-05 15:34:59'),
(303, 5, 'LOGIN', 'users', 5, 'User login berhasil sebagai asisten', '2025-12-05 15:46:07'),
(304, 3, 'LOGIN', 'users', 3, 'User login berhasil sebagai asisten', '2025-12-05 15:46:19'),
(305, 2, 'LOGIN', 'users', 2, 'User login berhasil sebagai mahasiswa', '2025-12-05 15:46:40'),
(306, 3, 'GENERATE_QR', 'qr_code_session', 87, 'QR Code untuk jadwal #433, expired: 2025-12-05 23:58:00', '2025-12-05 15:46:58'),
(307, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 433, 'Mahasiswa 230607 presensi via QR di Laboratorium Pemrograman', '2025-12-05 15:47:04'),
(308, 29, 'LOGIN', 'users', 29, 'User login berhasil sebagai mahasiswa', '2025-12-05 15:48:11'),
(309, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 433, 'Mahasiswa 0001234 presensi via QR di Laboratorium Pemrograman', '2025-12-05 15:48:20'),
(310, 30, 'LOGIN', 'users', 30, 'User login berhasil sebagai mahasiswa', '2025-12-05 15:48:44'),
(311, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 433, 'Mahasiswa 00012345 presensi via QR di Laboratorium Pemrograman', '2025-12-05 15:48:51'),
(312, 22, 'LOGIN', 'users', 22, 'User login berhasil sebagai mahasiswa', '2025-12-05 15:49:17'),
(313, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 433, 'Mahasiswa 120607 presensi via QR di Laboratorium Pemrograman', '2025-12-05 15:49:25'),
(314, 21, 'LOGIN', 'users', 21, 'User login berhasil sebagai mahasiswa', '2025-12-05 15:49:47'),
(315, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 433, 'Mahasiswa 130607 presensi via QR di Laboratorium Pemrograman', '2025-12-05 15:50:00'),
(316, 4, 'LOGIN', 'users', 4, 'User login berhasil sebagai mahasiswa', '2025-12-05 15:51:49'),
(317, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 433, 'Mahasiswa 2802087 presensi via QR di Laboratorium Pemrograman', '2025-12-05 15:52:00'),
(318, 14, 'LOGIN', 'users', 14, 'User login berhasil sebagai mahasiswa', '2025-12-05 15:54:13'),
(319, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 433, 'Mahasiswa 0314563 presensi via QR di Laboratorium Pemrograman', '2025-12-05 15:54:25'),
(320, 12, 'LOGIN', 'users', 12, 'User login berhasil sebagai mahasiswa', '2025-12-05 17:00:38'),
(321, 11, 'LOGIN', 'users', 11, 'User login berhasil sebagai mahasiswa', '2025-12-05 17:01:03'),
(322, 3, 'IZIN_ASISTEN', 'absen_asisten', 443, 'Asisten AST001 mengajukan sakit', '2025-12-05 17:05:06'),
(323, 15, 'LOGIN', 'users', 15, 'User login berhasil sebagai asisten', '2025-12-05 17:05:43'),
(324, 13, 'LOGIN', 'users', 13, 'User login berhasil sebagai mahasiswa', '2025-12-05 17:06:35'),
(325, 13, 'PENGAJUAN_IZIN', 'penggantian_inhall', 14, 'Mahasiswa 2157941 mengajukan sakit (pending approval): Sakit gak bisa ikut', '2025-12-05 17:07:43'),
(326, 3, 'APPROVE_IZIN', 'penggantian_inhall', 14, 'Asisten AST001 menyetujui sakit mahasiswa 2157941', '2025-12-05 17:08:27'),
(327, 1, 'LOGIN', 'users', 1, 'User login berhasil sebagai admin', '2025-12-08 01:02:44'),
(328, 2, 'LOGIN', 'users', 2, 'User login berhasil sebagai mahasiswa', '2025-12-08 02:18:35'),
(329, 3, 'LOGIN', 'users', 3, 'User login berhasil sebagai asisten', '2025-12-08 02:42:09'),
(330, 3, 'GENERATE_QR', 'qr_code_session', 88, 'QR Code untuk jadwal #453, expired: 2025-12-08 10:00:00', '2025-12-08 02:42:57'),
(331, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 453, 'Mahasiswa 230607 presensi via QR di Laboratorium Pemrograman', '2025-12-08 02:43:18'),
(332, 29, 'LOGIN', 'users', 29, 'User login berhasil sebagai mahasiswa', '2025-12-08 02:44:24'),
(333, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 453, 'Mahasiswa 0001234 presensi via QR di Laboratorium Pemrograman', '2025-12-08 02:45:09'),
(334, 30, 'LOGIN', 'users', 30, 'User login berhasil sebagai mahasiswa', '2025-12-08 02:45:46'),
(335, 3, 'GENERATE_QR', 'qr_code_session', 89, 'QR Code untuk jadwal #453, expired: 2025-12-08 10:00:00', '2025-12-08 02:46:00'),
(336, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 453, 'Mahasiswa 00012345 presensi via QR di Laboratorium Pemrograman', '2025-12-08 02:46:21'),
(337, 33, 'LOGIN', 'users', 33, 'User login berhasil sebagai mahasiswa', '2025-12-08 02:51:26'),
(338, 3, 'GENERATE_QR', 'qr_code_session', 90, 'QR Code untuk jadwal #453, expired: 2025-12-08 10:00:00', '2025-12-08 02:51:49'),
(339, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 453, 'Mahasiswa 22224444 presensi via QR di Laboratorium Pemrograman', '2025-12-08 02:51:58'),
(340, 36, 'LOGIN', 'users', 36, 'User login berhasil sebagai mahasiswa', '2025-12-08 03:19:02'),
(341, 3, 'GENERATE_QR', 'qr_code_session', 91, 'QR Code untuk jadwal #463, expired: 2025-12-08 11:00:00', '2025-12-08 03:19:15'),
(342, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 463, 'Mahasiswa 44445555 presensi via QR di Laboratorium Pemrograman', '2025-12-08 03:19:24'),
(343, 36, 'LOGIN', 'users', 36, 'User login berhasil sebagai mahasiswa', '2025-12-08 03:19:52'),
(344, 35, 'LOGIN', 'users', 35, 'User login berhasil sebagai mahasiswa', '2025-12-08 03:20:19'),
(345, 35, 'PENGAJUAN_IZIN', 'penggantian_inhall', 15, 'Mahasiswa 22225555 mengajukan sakit (pending approval): sakit keras kepala bocor', '2025-12-08 03:20:43'),
(346, 3, 'APPROVE_IZIN', 'penggantian_inhall', 15, 'Asisten AST001 menyetujui sakit mahasiswa 22225555', '2025-12-08 03:20:58'),
(347, 2, 'LOGIN', 'users', 2, 'User login berhasil sebagai mahasiswa', '2025-12-08 03:26:05'),
(348, 15, 'LOGIN', 'users', 15, 'User login berhasil sebagai asisten', '2025-12-08 03:59:18'),
(349, 13, 'LOGIN', 'users', 13, 'User login berhasil sebagai mahasiswa', '2025-12-08 04:07:00'),
(350, 3, 'LOGIN', 'users', 3, 'User login berhasil sebagai asisten', '2025-12-08 05:29:08'),
(351, 1, 'LOGIN', 'users', 1, 'User login berhasil sebagai admin', '2025-12-08 05:41:25'),
(352, 2, 'LOGIN', 'users', 2, 'User login berhasil sebagai mahasiswa', '2025-12-08 07:44:10'),
(353, 1, 'LOGIN', 'users', 1, 'User login berhasil sebagai admin', '2025-12-08 07:56:36'),
(354, 3, 'LOGIN', 'users', 3, 'User login berhasil sebagai asisten', '2025-12-08 07:56:50'),
(355, 2, 'LOGIN', 'users', 2, 'User login berhasil sebagai mahasiswa', '2025-12-08 16:05:39'),
(356, 6, 'LOGIN', 'users', 6, 'User login berhasil sebagai asisten', '2025-12-08 18:14:49'),
(357, 2, 'LOGIN', 'users', 2, 'User login berhasil sebagai mahasiswa', '2025-12-08 18:35:04'),
(358, 3, 'LOGIN', 'users', 3, 'User login berhasil sebagai asisten', '2025-12-08 18:55:33'),
(359, 1, 'LOGIN', 'users', 1, 'User login berhasil sebagai admin', '2025-12-08 19:37:00'),
(360, 1, 'LOGIN', 'users', 1, 'User login berhasil sebagai admin', '2025-12-08 19:47:29'),
(361, 2, 'LOGIN', 'users', 2, 'User login berhasil sebagai mahasiswa', '2025-12-08 19:54:50'),
(362, 1, 'LOGIN', 'users', 1, 'User login berhasil sebagai admin', '2025-12-08 19:56:15'),
(363, 1, 'LOGIN', 'users', 1, 'User login berhasil sebagai admin', '2025-12-09 01:02:09'),
(364, 1, 'LOGIN', 'users', 1, 'User login berhasil sebagai admin', '2025-12-09 01:10:28'),
(365, 6, 'LOGIN', 'users', 6, 'User login berhasil sebagai asisten', '2025-12-09 01:13:35'),
(366, 37, 'LOGIN', 'users', 37, 'User login berhasil sebagai mahasiswa', '2025-12-09 01:14:23'),
(367, 6, 'GENERATE_QR', 'qr_code_session', 92, 'QR Code untuk jadwal #473, expired: 2025-12-09 10:00:00', '2025-12-09 01:14:35'),
(368, 6, 'GENERATE_QR', 'qr_code_session', 93, 'QR Code untuk jadwal #473, expired: 2025-12-09 10:00:00', '2025-12-09 01:26:22'),
(369, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 473, 'Mahasiswa 11115555 presensi via QR di Laboratorium Jaringan', '2025-12-09 01:26:54'),
(370, 17, 'LOGIN', 'users', 17, 'User login berhasil sebagai mahasiswa', '2025-12-09 07:44:15'),
(371, 1, 'LOGIN', 'users', 1, 'User login berhasil sebagai admin', '2025-12-09 14:38:23'),
(372, 1, 'LOGIN', 'users', 1, 'User login berhasil sebagai admin', '2025-12-10 03:42:11'),
(373, 17, 'LOGIN', 'users', 17, 'User login berhasil sebagai mahasiswa', '2025-12-10 03:46:18'),
(374, 15, 'LOGIN', 'users', 15, 'User login berhasil sebagai asisten', '2025-12-10 05:37:51'),
(375, 3, 'LOGIN', 'users', 3, 'User login berhasil sebagai asisten', '2025-12-10 05:39:11'),
(376, 2, 'LOGIN', 'users', 2, 'User login berhasil sebagai mahasiswa', '2025-12-10 06:17:57');

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id` int(11) NOT NULL,
  `nim` varchar(15) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `kode_kelas` char(1) NOT NULL,
  `prodi` varchar(50) DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `tanggal_daftar` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `nim`, `user_id`, `nama`, `kode_kelas`, `prodi`, `no_hp`, `foto`, `tanggal_daftar`) VALUES
(1, '230607', 2, 'Nanda Hanif Abyan Bromo Putra', 'A', 'BasisData', '083841426400', 'uploads/profil/mhs_230607_1765348590.png', '2025-12-02 03:39:16'),
(2, '2802087', 4, 'Reifan Ahmad Muhyidin', 'A', 'Pemrograman', '083841426422', NULL, '2025-12-02 03:39:16'),
(3, '1924145', 7, 'Paenhi Putra', 'B', 'Jaringan', '083841426433', NULL, '2025-12-02 03:39:16'),
(6, '2802001', 11, 'Masayu', 'B', 'Pemrograman', '083841426444', 'uploads/profil/mhs_2802001_1764954230.webp', '2025-12-02 03:45:29'),
(7, '2802085', 12, 'Nicholas', 'C', 'Statistika', '083841426211', NULL, '2025-12-02 04:21:56'),
(8, '2157941', 13, 'Jefri', 'B', 'Pemrograman', '083841426112', NULL, '2025-12-02 04:48:44'),
(9, '0314563', 14, 'Sulistiyo', 'A', 'Basis Data', '083813426413', NULL, '2025-12-02 06:33:52'),
(10, '23456789', 16, 'Syou', 'C', 'Pemrograman', '089525801972', NULL, '2025-12-04 03:33:28'),
(11, '22233344', 17, 'Sulthan', 'D', 'Jaringan', '083821426221', 'uploads/profil/mhs_22233344_1765266294.jpg', '2025-12-04 03:59:09'),
(13, '130607', 21, 'Budi Santoso', 'A', 'Pemrograman', '081234567890', NULL, '2025-12-05 02:36:03'),
(14, '120607', 22, 'Ani Wijaya', 'A', 'Pemrograman', '081234567891', NULL, '2025-12-05 02:36:03'),
(19, '0001234', 29, 'aaaa', 'A', 'Jaringan', '234567', NULL, '2025-12-05 17:00:00'),
(20, '00012345', 30, 'AAAAA', 'A', 'Jaringan', '0838414261323', NULL, '2025-12-05 07:14:00'),
(23, '22224444', 33, 'BBBBBB', 'A', 'Teknik Informatika', '8123456000', NULL, '2025-12-08 02:47:00'),
(24, '44442222', 34, 'CCCCCC', 'A', 'Teknik Informatika', '81234561891', NULL, '2025-12-08 02:47:00'),
(25, '22225555', 35, 'bbbb', 'B', 'Teknik Informatika', '8123456000', NULL, '2025-12-08 03:17:00'),
(26, '44445555', 36, 'cccc', 'A', 'Teknik Informatika', '81234561891', NULL, '2025-12-08 03:17:00'),
(27, '11115555', 37, 'Kurniawan Saputra', 'D', 'Statistika', '082143546563', 'uploads/profil/mhs_11115555_1765243650.png', '2025-12-09 01:11:00');

-- --------------------------------------------------------

--
-- Table structure for table `mata_kuliah`
--

CREATE TABLE `mata_kuliah` (
  `kode_mk` varchar(10) NOT NULL,
  `nama_mk` varchar(100) NOT NULL,
  `sks` int(11) DEFAULT 3,
  `semester` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mata_kuliah`
--

INSERT INTO `mata_kuliah` (`kode_mk`, `nama_mk`, `sks`, `semester`) VALUES
('MK001', 'Basis Data', 3, 'Ganjil'),
('MK002', 'Pemrograman', 3, 'Ganjil'),
('MK003', 'Jaringan', 3, 'Ganjil'),
('MK004', 'Statistika', 3, 'Ganjil');

-- --------------------------------------------------------

--
-- Table structure for table `penggantian_inhall`
--

CREATE TABLE `penggantian_inhall` (
  `id` int(11) NOT NULL,
  `nim` varchar(15) NOT NULL,
  `jadwal_asli_id` int(11) DEFAULT NULL,
  `jadwal_inhall_id` int(11) DEFAULT NULL,
  `materi_diulang` varchar(100) DEFAULT NULL,
  `status` enum('terdaftar','hadir','tidak_hadir') DEFAULT 'terdaftar',
  `alasan_izin` text DEFAULT NULL,
  `bukti_file` varchar(255) DEFAULT NULL,
  `status_approval` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` varchar(10) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `alasan_reject` text DEFAULT NULL,
  `tanggal_daftar` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penggantian_inhall`
--

INSERT INTO `penggantian_inhall` (`id`, `nim`, `jadwal_asli_id`, `jadwal_inhall_id`, `materi_diulang`, `status`, `alasan_izin`, `bukti_file`, `status_approval`, `approved_by`, `approved_at`, `alasan_reject`, `tanggal_daftar`) VALUES
(13, '120607', 413, NULL, 'sakit', 'terdaftar', 'sakit', 'bukti_120607_1764917727.png', 'approved', 'AST003', '2025-12-05 13:55:48', NULL, '2025-12-05 06:55:27'),
(14, '2157941', 443, NULL, 'sakit', 'terdaftar', 'Sakit gak bisa ikut', 'bukti_2157941_1764954462.jpg', 'approved', 'AST001', '2025-12-06 00:08:27', NULL, '2025-12-05 17:07:43'),
(15, '22225555', 463, NULL, 'sakit', 'terdaftar', 'sakit keras kepala bocor', 'bukti_22225555_1765164043.png', 'approved', 'AST001', '2025-12-08 10:20:58', NULL, '2025-12-08 03:20:43');

-- --------------------------------------------------------

--
-- Table structure for table `presensi_mahasiswa`
--

CREATE TABLE `presensi_mahasiswa` (
  `id` int(11) NOT NULL,
  `jadwal_id` int(11) NOT NULL,
  `nim` varchar(15) NOT NULL,
  `status` enum('hadir','izin','sakit','alpha','belum') DEFAULT 'belum',
  `waktu_presensi` timestamp NOT NULL DEFAULT current_timestamp(),
  `metode` enum('qr','manual','fingerprint','auto') DEFAULT 'manual',
  `validated_by` varchar(10) DEFAULT NULL,
  `location_lab` varchar(50) DEFAULT NULL,
  `device_id` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `verified_by_system` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `presensi_mahasiswa`
--

INSERT INTO `presensi_mahasiswa` (`id`, `jadwal_id`, `nim`, `status`, `waktu_presensi`, `metode`, `validated_by`, `location_lab`, `device_id`, `ip_address`, `verified_by_system`) VALUES
(168, 413, '120607', 'sakit', '2025-12-05 06:55:48', 'manual', 'AST003', NULL, NULL, NULL, 0),
(169, 413, '230607', 'alpha', '2025-12-05 07:00:01', 'auto', NULL, NULL, NULL, NULL, 1),
(170, 413, '2802087', 'alpha', '2025-12-05 07:00:01', 'auto', NULL, NULL, NULL, NULL, 1),
(171, 413, '0314563', 'alpha', '2025-12-05 07:00:01', 'auto', NULL, NULL, NULL, NULL, 1),
(172, 413, '130607', 'hadir', '2025-12-05 06:59:37', 'qr', NULL, 'Laboratorium Jaringan', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Sa', '::1', 1),
(175, 423, '230607', 'alpha', '2025-12-05 08:10:06', 'auto', NULL, NULL, NULL, NULL, 1),
(176, 423, '2802087', 'alpha', '2025-12-05 08:10:06', 'auto', NULL, NULL, NULL, NULL, 1),
(177, 423, '0314563', 'alpha', '2025-12-05 08:10:06', 'auto', NULL, NULL, NULL, NULL, 1),
(178, 423, '130607', 'alpha', '2025-12-05 08:10:06', 'auto', NULL, NULL, NULL, NULL, 1),
(179, 423, '120607', 'alpha', '2025-12-05 08:10:06', 'auto', NULL, NULL, NULL, NULL, 1),
(180, 423, '0001234', 'alpha', '2025-12-05 08:10:06', 'auto', NULL, NULL, NULL, NULL, 1),
(181, 423, '00012345', 'hadir', '2025-12-05 07:18:27', 'qr', NULL, 'Laboratorium Jaringan', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Sa', '::1', 1),
(185, 433, '230607', 'hadir', '2025-12-05 15:47:04', 'qr', NULL, 'Laboratorium Pemrograman', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.3', '192.168.43.154', 1),
(186, 433, '2802087', 'hadir', '2025-12-05 15:52:00', 'qr', NULL, 'Laboratorium Pemrograman', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.3', '192.168.43.154', 1),
(187, 433, '0314563', 'hadir', '2025-12-05 15:54:25', 'qr', NULL, 'Laboratorium Pemrograman', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.3', '192.168.43.154', 1),
(188, 433, '130607', 'hadir', '2025-12-05 15:49:59', 'qr', NULL, 'Laboratorium Pemrograman', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.3', '192.168.43.154', 1),
(189, 433, '120607', 'hadir', '2025-12-05 15:49:25', 'qr', NULL, 'Laboratorium Pemrograman', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.3', '192.168.43.154', 1),
(190, 433, '0001234', 'hadir', '2025-12-05 15:48:20', 'qr', NULL, 'Laboratorium Pemrograman', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.3', '192.168.43.154', 1),
(191, 433, '00012345', 'hadir', '2025-12-05 15:48:51', 'qr', NULL, 'Laboratorium Pemrograman', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.3', '192.168.43.154', 1),
(192, 443, '2157941', 'sakit', '2025-12-05 17:08:27', 'manual', 'AST001', NULL, NULL, NULL, 0),
(193, 443, '1924145', 'alpha', '2025-12-08 01:02:46', 'auto', NULL, NULL, NULL, NULL, 1),
(194, 443, '2802001', 'alpha', '2025-12-08 01:02:46', 'auto', NULL, NULL, NULL, NULL, 1),
(195, 453, '230607', 'hadir', '2025-12-08 02:43:17', 'qr', NULL, 'Laboratorium Pemrograman', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Sa', '::1', 1),
(196, 453, '2802087', 'alpha', '2025-12-08 03:00:10', 'auto', NULL, NULL, NULL, NULL, 1),
(197, 453, '0314563', 'alpha', '2025-12-08 03:00:10', 'auto', NULL, NULL, NULL, NULL, 1),
(198, 453, '130607', 'alpha', '2025-12-08 03:00:10', 'auto', NULL, NULL, NULL, NULL, 1),
(199, 453, '120607', 'alpha', '2025-12-08 03:00:10', 'auto', NULL, NULL, NULL, NULL, 1),
(200, 453, '0001234', 'hadir', '2025-12-08 02:45:08', 'qr', NULL, 'Laboratorium Pemrograman', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Sa', '::1', 1),
(201, 453, '00012345', 'hadir', '2025-12-08 02:46:16', 'qr', NULL, 'Laboratorium Pemrograman', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Sa', '::1', 1),
(202, 453, '22224444', 'hadir', '2025-12-08 02:51:58', 'qr', NULL, 'Laboratorium Pemrograman', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Sa', '::1', 1),
(203, 453, '44442222', 'alpha', '2025-12-08 03:00:10', 'auto', NULL, NULL, NULL, NULL, 1),
(204, 463, '1924145', 'alpha', '2025-12-08 04:00:57', 'auto', NULL, NULL, NULL, NULL, 1),
(205, 463, '2802001', 'alpha', '2025-12-08 04:00:57', 'auto', NULL, NULL, NULL, NULL, 1),
(206, 463, '2157941', 'alpha', '2025-12-08 04:00:57', 'auto', NULL, NULL, NULL, NULL, 1),
(207, 463, '22225555', 'sakit', '2025-12-08 03:20:58', 'manual', 'AST001', NULL, NULL, NULL, 0),
(208, 463, '44445555', 'hadir', '2025-12-08 03:19:24', 'qr', NULL, 'Laboratorium Pemrograman', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Sa', '::1', 1),
(209, 473, '22233344', 'alpha', '2025-12-09 03:07:40', 'auto', NULL, NULL, NULL, NULL, 1),
(210, 473, '11115555', 'hadir', '2025-12-09 01:26:54', 'qr', NULL, 'Laboratorium Jaringan', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Sa', '::1', 1),
(211, 443, '130607', 'alpha', '2025-12-10 06:21:06', 'auto', NULL, NULL, NULL, NULL, 1),
(212, 463, '130607', 'alpha', '2025-12-10 06:21:06', 'auto', NULL, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `qr_code_session`
--

CREATE TABLE `qr_code_session` (
  `id` int(11) NOT NULL,
  `jadwal_id` int(11) NOT NULL,
  `qr_code` varchar(100) NOT NULL,
  `expired_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `qr_code_session`
--

INSERT INTO `qr_code_session` (`id`, `jadwal_id`, `qr_code`, `expired_at`, `created_at`) VALUES
(85, 413, 'aadc385a59c8714a860b027bcfa82fc1_1764918001', '2025-12-05 14:00:00', '2025-12-05 07:00:01'),
(86, 423, '23b8aea275ad65db42e24ffbb95bbe7c_1764919095', '2025-12-05 15:00:00', '2025-12-05 07:18:15'),
(87, 433, 'aee304f73ab4d14f2e996a3d8139f09f_1764949618', '2025-12-05 23:58:00', '2025-12-05 15:46:58'),
(90, 453, 'cb0a97a34e70811f71f681fe09e72a30_1765162308', '2025-12-08 10:00:00', '2025-12-08 02:51:49'),
(91, 463, '54533a605c8ef2306e2ce85dc04e9994_1765163955', '2025-12-08 11:00:00', '2025-12-08 03:19:15'),
(93, 473, '3d81f3f651dd9c49e77916aadf480c98_1765243582', '2025-12-09 10:00:00', '2025-12-09 01:26:22');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('mahasiswa','asisten','admin') DEFAULT 'mahasiswa',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `remember_token` varchar(64) DEFAULT NULL,
  `token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `remember_token`, `token_expires`) VALUES
(1, 'admin', 'admin', 'admin', '2025-12-01 12:30:14', NULL, NULL),
(2, '230607', 'admin123', 'mahasiswa', '2025-12-01 12:32:38', NULL, NULL),
(3, 'AST001', 'asisten123', 'asisten', '2025-12-01 12:35:46', NULL, NULL),
(4, '2802087', 'admin', 'mahasiswa', '2025-12-01 13:33:49', NULL, NULL),
(5, 'AST002', 'asisten123', 'asisten', '2025-12-01 14:30:14', NULL, NULL),
(6, 'AST003', 'asisten123', 'asisten', '2025-12-02 01:14:13', NULL, NULL),
(7, '1924145', 'admin', 'mahasiswa', '2025-12-02 01:15:49', NULL, NULL),
(8, 'AST004', 'asisten123', 'asisten', '2025-12-02 01:22:13', NULL, NULL),
(11, '2802001', 'admin', 'mahasiswa', '2025-12-02 03:45:29', NULL, NULL),
(12, '2802085', 'admin', 'mahasiswa', '2025-12-02 04:21:56', NULL, NULL),
(13, '2157941', 'admin', 'mahasiswa', '2025-12-02 04:48:44', NULL, NULL),
(14, '0314563', 'admin123', 'mahasiswa', '2025-12-02 06:33:52', NULL, NULL),
(15, 'AST005', 'asisten123', 'asisten', '2025-12-03 03:02:29', NULL, NULL),
(16, '23456789', 'admin', 'mahasiswa', '2025-12-04 03:33:28', NULL, NULL),
(17, '22233344', 'admin', 'mahasiswa', '2025-12-04 03:59:09', NULL, NULL),
(21, '130607', 'admin123', 'mahasiswa', '2025-12-05 02:36:03', NULL, NULL),
(22, '120607', 'admin123', 'mahasiswa', '2025-12-05 02:36:03', NULL, NULL),
(23, '12233445', '123456', 'mahasiswa', '2025-12-05 06:18:15', NULL, NULL),
(24, '12233446', '123456', 'mahasiswa', '2025-12-05 06:18:15', NULL, NULL),
(26, '0000012', '123456', 'mahasiswa', '2025-12-05 07:01:37', NULL, NULL),
(28, '0000123', '123456', 'mahasiswa', '2025-12-05 07:08:01', NULL, NULL),
(29, '0001234', '123456', 'mahasiswa', '2025-12-05 07:09:41', NULL, NULL),
(30, '00012345', '123456', 'mahasiswa', '2025-12-05 07:15:01', NULL, NULL),
(33, '22224444', '123456', 'mahasiswa', '2025-12-08 02:50:48', NULL, NULL),
(34, '44442222', '123456', 'mahasiswa', '2025-12-08 02:50:48', NULL, NULL),
(35, '22225555', '123456', 'mahasiswa', '2025-12-08 03:18:27', NULL, NULL),
(36, '44445555', '123456', 'mahasiswa', '2025-12-08 03:18:27', NULL, NULL),
(37, '11115555', '123456', 'mahasiswa', '2025-12-09 01:12:32', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absen_asisten`
--
ALTER TABLE `absen_asisten`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jadwal_id` (`jadwal_id`),
  ADD KEY `kode_asisten` (`kode_asisten`),
  ADD KEY `pengganti` (`pengganti`);

--
-- Indexes for table `asisten`
--
ALTER TABLE `asisten`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_asisten` (`kode_asisten`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `kode_mk` (`kode_mk`);

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kode_kelas` (`kode_kelas`),
  ADD KEY `kode_mk` (`kode_mk`),
  ADD KEY `kode_asisten_1` (`kode_asisten_1`),
  ADD KEY `kode_asisten_2` (`kode_asisten_2`),
  ADD KEY `idx_jadwal_tanggal_kelas` (`tanggal`,`kode_kelas`),
  ADD KEY `idx_jadwal_lab` (`kode_lab`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`kode_kelas`);

--
-- Indexes for table `lab`
--
ALTER TABLE `lab`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_lab` (`kode_lab`),
  ADD KEY `fk_lab_matakuliah` (`kode_mk`);

--
-- Indexes for table `log_presensi`
--
ALTER TABLE `log_presensi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_mahasiswa_kelas` (`kode_kelas`);

--
-- Indexes for table `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  ADD PRIMARY KEY (`kode_mk`);

--
-- Indexes for table `penggantian_inhall`
--
ALTER TABLE `penggantian_inhall`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nim` (`nim`),
  ADD KEY `jadwal_asli_id` (`jadwal_asli_id`),
  ADD KEY `jadwal_inhall_id` (`jadwal_inhall_id`),
  ADD KEY `fk_approved_by` (`approved_by`);

--
-- Indexes for table `presensi_mahasiswa`
--
ALTER TABLE `presensi_mahasiswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nim` (`nim`),
  ADD KEY `validated_by` (`validated_by`),
  ADD KEY `idx_presensi_jadwal_nim` (`jadwal_id`,`nim`),
  ADD KEY `idx_presensi_tanggal` (`waktu_presensi`);

--
-- Indexes for table `qr_code_session`
--
ALTER TABLE `qr_code_session`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD KEY `jadwal_id` (`jadwal_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absen_asisten`
--
ALTER TABLE `absen_asisten`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `asisten`
--
ALTER TABLE `asisten`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=483;

--
-- AUTO_INCREMENT for table `lab`
--
ALTER TABLE `lab`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `log_presensi`
--
ALTER TABLE `log_presensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=377;

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `penggantian_inhall`
--
ALTER TABLE `penggantian_inhall`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `presensi_mahasiswa`
--
ALTER TABLE `presensi_mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=213;

--
-- AUTO_INCREMENT for table `qr_code_session`
--
ALTER TABLE `qr_code_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `absen_asisten`
--
ALTER TABLE `absen_asisten`
  ADD CONSTRAINT `absen_asisten_ibfk_1` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`),
  ADD CONSTRAINT `absen_asisten_ibfk_2` FOREIGN KEY (`kode_asisten`) REFERENCES `asisten` (`kode_asisten`),
  ADD CONSTRAINT `absen_asisten_ibfk_3` FOREIGN KEY (`pengganti`) REFERENCES `asisten` (`kode_asisten`);

--
-- Constraints for table `asisten`
--
ALTER TABLE `asisten`
  ADD CONSTRAINT `asisten_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asisten_ibfk_2` FOREIGN KEY (`kode_mk`) REFERENCES `mata_kuliah` (`kode_mk`);

--
-- Constraints for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`kode_lab`) REFERENCES `lab` (`kode_lab`),
  ADD CONSTRAINT `jadwal_ibfk_2` FOREIGN KEY (`kode_kelas`) REFERENCES `kelas` (`kode_kelas`),
  ADD CONSTRAINT `jadwal_ibfk_3` FOREIGN KEY (`kode_mk`) REFERENCES `mata_kuliah` (`kode_mk`),
  ADD CONSTRAINT `jadwal_ibfk_4` FOREIGN KEY (`kode_asisten_1`) REFERENCES `asisten` (`kode_asisten`),
  ADD CONSTRAINT `jadwal_ibfk_5` FOREIGN KEY (`kode_asisten_2`) REFERENCES `asisten` (`kode_asisten`);

--
-- Constraints for table `lab`
--
ALTER TABLE `lab`
  ADD CONSTRAINT `fk_lab_matakuliah` FOREIGN KEY (`kode_mk`) REFERENCES `mata_kuliah` (`kode_mk`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `mahasiswa_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mahasiswa_ibfk_2` FOREIGN KEY (`kode_kelas`) REFERENCES `kelas` (`kode_kelas`);

--
-- Constraints for table `penggantian_inhall`
--
ALTER TABLE `penggantian_inhall`
  ADD CONSTRAINT `fk_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `asisten` (`kode_asisten`) ON DELETE SET NULL,
  ADD CONSTRAINT `penggantian_inhall_ibfk_1` FOREIGN KEY (`nim`) REFERENCES `mahasiswa` (`nim`),
  ADD CONSTRAINT `penggantian_inhall_ibfk_2` FOREIGN KEY (`jadwal_asli_id`) REFERENCES `jadwal` (`id`),
  ADD CONSTRAINT `penggantian_inhall_ibfk_3` FOREIGN KEY (`jadwal_inhall_id`) REFERENCES `jadwal` (`id`);

--
-- Constraints for table `presensi_mahasiswa`
--
ALTER TABLE `presensi_mahasiswa`
  ADD CONSTRAINT `presensi_mahasiswa_ibfk_1` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`),
  ADD CONSTRAINT `presensi_mahasiswa_ibfk_2` FOREIGN KEY (`nim`) REFERENCES `mahasiswa` (`nim`),
  ADD CONSTRAINT `presensi_mahasiswa_ibfk_3` FOREIGN KEY (`validated_by`) REFERENCES `asisten` (`kode_asisten`);

--
-- Constraints for table `qr_code_session`
--
ALTER TABLE `qr_code_session`
  ADD CONSTRAINT `qr_code_session_ibfk_1` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`);

-- --------------------------------------------------------

--
-- Table structure for table `materi_perkuliahan`
--

CREATE TABLE `materi_perkuliahan` (
  `id_materi` int(11) NOT NULL,
  `id_jadwal` int(11) NOT NULL,
  `judul_materi` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `nama_file` varchar(255) DEFAULT NULL,
  `path_file` varchar(255) DEFAULT NULL,
  `tgl_upload` timestamp NOT NULL DEFAULT current_timestamp(),
  `uploader_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for table `materi_perkuliahan`
--
ALTER TABLE `materi_perkuliahan`
  ADD PRIMARY KEY (`id_materi`),
  ADD KEY `id_jadwal` (`id_jadwal`),
  ADD KEY `uploader_id` (`uploader_id`);

--
-- AUTO_INCREMENT for table `materi_perkuliahan`
--
ALTER TABLE `materi_perkuliahan`
  MODIFY `id_materi` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for table `materi_perkuliahan`
--
ALTER TABLE `materi_perkuliahan`
  ADD CONSTRAINT `materi_perkuliahan_ibfk_1` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `materi_perkuliahan_ibfk_2` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
