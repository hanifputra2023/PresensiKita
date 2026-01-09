-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 06 Jan 2026 pada 05.36
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
-- Database: `presensi`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absen_asisten`
--

CREATE TABLE `absen_asisten` (
  `id` int(11) NOT NULL,
  `jadwal_id` int(11) NOT NULL,
  `kode_asisten` varchar(10) NOT NULL,
  `status` enum('hadir','izin','sakit') DEFAULT 'hadir',
  `jam_masuk` time DEFAULT NULL,
  `jam_keluar` time DEFAULT NULL,
  `pengganti` varchar(10) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `status_approval` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `alasan_reject` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `absen_asisten`
--

INSERT INTO `absen_asisten` (`id`, `jadwal_id`, `kode_asisten`, `status`, `jam_masuk`, `jam_keluar`, `pengganti`, `catatan`, `status_approval`, `approved_by`, `approved_at`, `alasan_reject`) VALUES
(74, 968, '231064013', 'hadir', '08:21:12', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(76, 959, '231064013', 'sakit', NULL, NULL, '123456789', 'saya sakit habis jatuh dari motor tolong ya gantiin saya mengajar', 'approved', 38, '2026-01-06 11:26:23', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `asisten`
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
-- Dumping data untuk tabel `asisten`
--

INSERT INTO `asisten` (`id`, `kode_asisten`, `user_id`, `nama`, `no_hp`, `foto`, `kode_mk`, `status`) VALUES
(6, '231064013', 57, 'AVOREY BIAS AGUNG V.D', '-', 'uploads/profil/ast_231064013_1767079909.png', 'STP2503', 'aktif'),
(7, '231064018', 58, 'DEFAULLO A.R BENGE', '-', NULL, 'STP2503', 'aktif'),
(8, '23108012', 59, 'AGUSTINUS KAROL SANI', '-', NULL, 'STP2503', 'aktif'),
(9, '123456789', 68, 'Mulyono', '081234567890', NULL, 'MK003', 'aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jadwal`
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
-- Dumping data untuk tabel `jadwal`
--

INSERT INTO `jadwal` (`id`, `pertemuan_ke`, `tanggal`, `jam_mulai`, `jam_selesai`, `kode_lab`, `kode_kelas`, `kode_mk`, `materi`, `kode_asisten_1`, `kode_asisten_2`, `jenis`, `keterangan`, `created_at`) VALUES
(902, 1, '2025-12-22', '21:17:00', '22:00:00', 'LAB001', 'B', 'MK002', 'Pertemuan 1 - Pengenalan Bahasa Pemrograman', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(903, 2, '2025-12-29', '13:33:00', '17:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 2 - Dasar', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(904, 3, '2026-01-05', '15:17:00', '22:00:00', 'LAB001', 'B', 'MK002', 'Pertemuan 3 - Lanjutan I', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(905, 4, '2026-01-12', '21:17:00', '22:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 4 - Lanjutan II', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(906, 5, '2026-01-19', '21:17:00', '22:00:00', 'LAB001', 'B', 'MK002', 'Pertemuan 5 - Praktik I', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(907, 6, '2026-01-26', '21:17:00', '22:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 6 - Praktik II', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(908, 7, '2026-02-02', '21:17:00', '22:00:00', 'LAB001', 'B', 'MK002', 'Pertemuan 7 - Praktik III', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(909, 8, '2026-02-09', '21:17:00', '22:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 8 - Review', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(910, 9, '2026-02-16', '21:17:00', '22:00:00', 'LAB001', 'B', 'MK002', 'Praresponsi', '231064013', NULL, 'praresponsi', NULL, '2025-12-22 14:18:02'),
(911, 9, '2026-02-16', '22:00:00', '22:43:00', 'LAB001', 'B', 'MK002', 'Inhall', '231064013', NULL, 'inhall', NULL, '2025-12-22 14:18:02'),
(912, 10, '2026-02-23', '21:17:00', '22:00:00', 'LAB002', 'B', 'MK002', 'Responsi', '231064013', NULL, 'responsi', NULL, '2025-12-22 14:18:02'),
(924, 1, '2025-12-23', '10:22:00', '12:00:00', 'LAB001', 'E', 'STP2503', 'Pertemuan 1 - Pengenalan', '23108012', '231064018', 'materi', NULL, '2025-12-23 03:22:16'),
(925, 2, '2025-12-30', '10:22:00', '12:00:00', 'LAB002', 'E', 'STP2503', 'Pertemuan 2 - Dasar', '23108012', '231064018', 'materi', NULL, '2025-12-23 03:22:16'),
(926, 3, '2026-01-06', '10:22:00', '12:00:00', 'LAB001', 'E', 'STP2503', 'Pertemuan 3 - Lanjutan I', '23108012', '231064018', 'materi', NULL, '2025-12-23 03:22:16'),
(927, 4, '2026-01-13', '10:22:00', '12:00:00', 'LAB002', 'E', 'STP2503', 'Pertemuan 4 - Lanjutan II', '23108012', '231064018', 'materi', NULL, '2025-12-23 03:22:16'),
(928, 5, '2026-01-20', '10:22:00', '12:00:00', 'LAB001', 'E', 'STP2503', 'Pertemuan 5 - Praktik I', '23108012', '231064018', 'materi', NULL, '2025-12-23 03:22:16'),
(929, 6, '2026-01-27', '10:22:00', '12:00:00', 'LAB002', 'E', 'STP2503', 'Pertemuan 6 - Praktik II', '23108012', '231064018', 'materi', NULL, '2025-12-23 03:22:16'),
(930, 7, '2026-02-03', '10:22:00', '12:00:00', 'LAB001', 'E', 'STP2503', 'Pertemuan 7 - Praktik III', '23108012', '231064018', 'materi', NULL, '2025-12-23 03:22:16'),
(931, 8, '2026-02-10', '10:22:00', '12:00:00', 'LAB002', 'E', 'STP2503', 'Pertemuan 8 - Review', '23108012', '231064018', 'materi', NULL, '2025-12-23 03:22:16'),
(932, 9, '2026-02-17', '10:22:00', '12:00:00', 'LAB001', 'E', 'STP2503', 'Praresponsi', '23108012', '231064018', 'praresponsi', NULL, '2025-12-23 03:22:16'),
(933, 9, '2026-02-17', '12:00:00', '13:38:00', 'LAB001', 'E', 'STP2503', 'Inhall', '23108012', '231064018', 'inhall', NULL, '2025-12-23 03:22:16'),
(934, 10, '2026-02-24', '10:22:00', '12:00:00', 'LAB002', 'E', 'STP2503', 'Responsi', '23108012', '231064018', 'responsi', NULL, '2025-12-23 03:22:16'),
(957, 1, '2025-12-23', '10:22:00', '15:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 1 - Pengenalan', '231064013', NULL, 'materi', NULL, '2025-12-23 06:16:20'),
(958, 2, '2025-12-30', '10:22:00', '12:00:00', 'LAB001', 'A', 'MK002', 'Pertemuan 2 - Dasar', '231064013', NULL, 'materi', NULL, '2025-12-23 06:16:20'),
(959, 3, '2026-01-06', '10:22:00', '12:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 3 - Lanjutan I', '231064013', NULL, 'materi', NULL, '2025-12-23 06:16:20'),
(960, 4, '2026-01-13', '10:22:00', '12:00:00', 'LAB001', 'A', 'MK002', 'Pertemuan 4 - Lanjutan II', '231064013', NULL, 'materi', NULL, '2025-12-23 06:16:20'),
(961, 5, '2026-01-20', '10:22:00', '12:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 5 - Praktik I', '231064013', NULL, 'materi', NULL, '2025-12-23 06:16:20'),
(962, 6, '2026-01-27', '10:22:00', '12:00:00', 'LAB001', 'A', 'MK002', 'Pertemuan 6 - Praktik II', '231064013', NULL, 'materi', NULL, '2025-12-23 06:16:20'),
(963, 7, '2026-02-03', '10:22:00', '12:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 7 - Praktik III', '231064013', NULL, 'materi', NULL, '2025-12-23 06:16:20'),
(964, 8, '2026-02-10', '10:22:00', '12:00:00', 'LAB001', 'A', 'MK002', 'Pertemuan 8 - Review', '231064013', NULL, 'materi', NULL, '2025-12-23 06:16:20'),
(965, 9, '2026-02-17', '10:22:00', '12:00:00', 'LAB002', 'A', 'MK002', 'Praresponsi', '231064013', NULL, 'praresponsi', NULL, '2025-12-23 06:16:20'),
(966, 9, '2026-02-17', '12:00:00', '13:38:00', 'LAB002', 'A', 'MK002', 'Inhall', '231064013', NULL, 'inhall', NULL, '2025-12-23 06:16:20'),
(967, 10, '2026-02-24', '10:22:00', '12:00:00', 'LAB001', 'A', 'MK002', 'Responsi', '231064013', NULL, 'responsi', NULL, '2025-12-23 06:16:20'),
(968, 2, '2025-12-31', '08:19:00', '12:19:00', 'LAB001', 'B', 'MK002', 'Pertemuan 2 - Dasar Pemrograman PHP', '231064013', NULL, 'materi', NULL, '2025-12-31 01:19:54');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kelas`
--

CREATE TABLE `kelas` (
  `kode_kelas` char(1) NOT NULL,
  `nama_kelas` varchar(50) DEFAULT NULL,
  `program_studi` varchar(100) DEFAULT NULL,
  `tahun_ajaran` varchar(9) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `kelas`
--

INSERT INTO `kelas` (`kode_kelas`, `nama_kelas`, `program_studi`, `tahun_ajaran`) VALUES
('A', 'Pemrograman', 'Teknik Informatika', '2024/2025'),
('B', 'Kelas B', 'Sistem Informasi', '2024/2025'),
('C', 'Kelas C', 'Teknik Komputer', '2024/2025'),
('D', 'Kelas D', 'Manajemen Informatika', '2024/2025'),
('E', 'STATISTIK-2024', 'Statistik S1', '2024/2025');

-- --------------------------------------------------------

--
-- Struktur dari tabel `lab`
--

CREATE TABLE `lab` (
  `id` int(11) NOT NULL,
  `kode_lab` varchar(10) NOT NULL,
  `nama_lab` varchar(50) DEFAULT NULL,
  `kapasitas` int(11) DEFAULT NULL,
  `lokasi` varchar(100) DEFAULT NULL,
  `status` enum('active','maintenance') DEFAULT 'active',
  `latitude` varchar(50) DEFAULT NULL,
  `longitude` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `lab`
--

INSERT INTO `lab` (`id`, `kode_lab`, `nama_lab`, `kapasitas`, `lokasi`, `status`, `latitude`, `longitude`) VALUES
(1, 'LAB001', 'Laboratorium Basis Data', 30, 'Gedung A Lantai 1', 'active', '-7.787231895737355', '110.3885152626932'),
(2, 'LAB002', 'Laboratorium Pemrograman', 30, 'Gedung A Lantai 3', 'active', '-7.787231895737355', '110.3885152626932'),
(3, 'LAB003', 'Laboratorium Jaringan', 25, 'Gedung B Lantai 1', 'active', '-7.787231895737355', '110.3885152626932'),
(4, 'LAB004', 'Laboratorium Statistika', 20, 'Gedung B Lantai 2', 'active', '-7.787231895737355', '110.3885152626932');

-- --------------------------------------------------------

--
-- Struktur dari tabel `lab_matakuliah`
--

CREATE TABLE `lab_matakuliah` (
  `id_lab` int(11) NOT NULL,
  `kode_mk` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `lab_matakuliah`
--

INSERT INTO `lab_matakuliah` (`id_lab`, `kode_mk`) VALUES
(1, 'MK001'),
(1, 'MK002'),
(2, 'MK002'),
(2, 'STP2503'),
(3, 'MK003'),
(3, 'MK004'),
(4, 'MK003'),
(4, 'MK004');

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_presensi`
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
-- Dumping data untuk tabel `log_presensi`
--

INSERT INTO `log_presensi` (`id`, `user_id`, `aksi`, `tabel`, `id_record`, `detail`, `created_at`) VALUES
(626, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2025-12-30 07:50:25'),
(627, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2025-12-31 01:10:43'),
(628, 57, 'GENERATE_QR', 'qr_code_session', 128, 'QR Code untuk jadwal #968, expired: 2025-12-31 12:19:00', '2025-12-31 01:21:12'),
(629, 69, 'LOGIN', 'users', 69, 'User login berhasil sebagai mahasiswa', '2025-12-31 01:21:53'),
(630, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 968, 'Mahasiswa 11112222 presensi via QR di Laboratorium Basis Data', '2025-12-31 01:22:08'),
(631, 66, 'LOGIN', 'users', 66, 'User login berhasil sebagai mahasiswa', '2025-12-31 01:23:58'),
(632, 57, 'GENERATE_QR', 'qr_code_session', 129, 'QR Code untuk jadwal #968, expired: 2025-12-31 12:19:00', '2025-12-31 01:26:03'),
(633, 57, 'GENERATE_QR', 'qr_code_session', 130, 'QR Code untuk jadwal #968, expired: 2025-12-31 12:19:00', '2025-12-31 01:26:04'),
(634, 57, 'GENERATE_QR', 'qr_code_session', 131, 'QR Code untuk jadwal #968, expired: 2025-12-31 12:19:00', '2025-12-31 01:26:06'),
(635, 57, 'GENERATE_QR', 'qr_code_session', 132, 'QR Code untuk jadwal #968, expired: 2025-12-31 12:19:00', '2025-12-31 01:26:08'),
(636, 57, 'GENERATE_QR', 'qr_code_session', 133, 'QR Code untuk jadwal #968, expired: 2025-12-31 12:19:00', '2025-12-31 01:26:15'),
(637, 57, 'GENERATE_QR', 'qr_code_session', 134, 'QR Code untuk jadwal #968, expired: 2025-12-31 12:19:00', '2025-12-31 01:28:35'),
(638, 57, 'GENERATE_QR', 'qr_code_session', 135, 'QR Code untuk jadwal #968, expired: 2025-12-31 12:19:00', '2025-12-31 01:28:37'),
(639, 57, 'GENERATE_QR', 'qr_code_session', 136, 'QR Code untuk jadwal #968, expired: 2025-12-31 12:19:00', '2025-12-31 01:28:39'),
(640, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 968, 'Mahasiswa 070771 presensi via QR di Laboratorium Basis Data', '2025-12-31 01:29:02'),
(641, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 968, 'Mahasiswa 11112222 presensi via QR di Laboratorium Basis Data', '2025-12-31 03:53:43'),
(642, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-05 04:05:53'),
(643, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-05 05:46:54'),
(644, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-01-05 05:50:08'),
(645, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-05 06:02:06'),
(646, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-01-05 06:05:30'),
(647, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-05 06:10:01'),
(648, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-05 06:20:46'),
(649, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-05 06:41:54'),
(650, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-06 01:17:04'),
(651, 59, 'LOGIN', 'users', 59, 'User login berhasil sebagai asisten', '2026-01-06 01:18:56'),
(652, 57, 'LOGIN', 'users', 57, 'User login berhasil sebagai asisten', '2026-01-06 01:19:45'),
(653, 57, 'IZIN_ASISTEN', 'absen_asisten', 959, 'Asisten 231064013 mengajukan sakit', '2026-01-06 01:21:08'),
(654, 58, 'LOGIN', 'users', 58, 'User login berhasil sebagai asisten', '2026-01-06 01:21:58'),
(655, 57, 'LOGIN', 'users', 57, 'User login berhasil sebagai asisten', '2026-01-06 01:24:25'),
(656, 57, 'IZIN_ASISTEN', 'absen_asisten', 959, 'Asisten 231064013 mengajukan sakit (menunggu approval admin)', '2026-01-06 02:02:02'),
(657, 38, 'APPROVE_IZIN_ASISTEN', 'absen_asisten', 76, 'Admin menyetujui izin asisten AVOREY BIAS AGUNG V.D', '2026-01-06 04:26:23');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mahasiswa`
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
-- Dumping data untuk tabel `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `nim`, `user_id`, `nama`, `kode_kelas`, `prodi`, `no_hp`, `foto`, `tanggal_daftar`) VALUES
(28, '251062022', 39, 'NUNUT SITUMORANG', 'E', 'Statistik S1', '0', 'uploads/profil/mhs_251062022_1765869042.png', '2025-12-12 03:55:00'),
(29, '251062025', 40, 'FLORENS SANTA AGUSTIN .S', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00'),
(30, '251062026', 41, 'ARIZA MUHAIMIN .Z', 'E', 'Statistik S1', '083841426400', 'uploads/profil/mhs_251062026_1765519581.png', '2025-12-12 03:55:00'),
(31, '241064001', 42, 'NATALIA ALBERGATI NIPU', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00'),
(32, '241064002', 43, 'MAGDALENA B. S. SOBANG', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00'),
(33, '241064004', 44, 'ERA AMALIA PUTRI', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00'),
(34, '241064007', 45, 'ROSWITA ASMELITA NESTI .P', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00'),
(35, '241064008', 46, 'SANRY FRIDOLING OKI NAAT', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00'),
(36, '241064009', 47, 'FREDERICK HARDIMAN', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00'),
(37, '241064013', 48, 'KEZIA GREDALYA SITANIA', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00'),
(38, '241064014', 49, 'SEPTI NURELISA', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00'),
(39, '241067010', 50, 'MIKAELA MAYANTRIS', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00'),
(40, '241067011', 51, 'MUHAMMAD KHOLIK KHOIRI', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00'),
(41, '241068005', 52, 'IKHSANUDDIN MUKHLISH', 'E', 'Statistik S1', '0', 'uploads/profil/mhs_241068005_1765870189.jpg', '2025-12-12 03:55:00'),
(42, '241068006', 53, 'CORAZON RATU MARA', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00'),
(43, '242062001', 54, 'KAMELIA', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00'),
(44, '242062004', 55, 'DINA SITTONGA', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00'),
(45, '211063024', 56, 'OLIN PUTRA PRATAMA', 'E', 'Statistik S1', '0', 'uploads/profil/mhs_211063024_1765769283.png', '2025-12-12 03:55:00'),
(46, '230607', 60, 'Nanda Hanif Abyan Bromo Putra', 'A', 'Pemrogaman', '083841426400', 'uploads/profil/mhs_230607_1765913509.jpg', '2025-12-15 03:32:00'),
(47, '24346554', 61, 'AAAA', 'E', 'Statistik S1', '', NULL, '2025-12-15 07:01:00'),
(48, '765434567', 62, 'ccccc', 'E', 'Statistik S1', '', NULL, '2025-12-15 07:15:00'),
(49, '9532753', 63, 'gggg', 'E', 'Statistik S1', '', NULL, '2025-12-15 07:15:00'),
(50, '12345678', 64, 'Muhammad Iniesta Wildan Bromo Putra', 'A', 'Pemrogaman', '24356786576', 'uploads/profil/mhs_12345678_1765910430.jpg', '2025-12-16 16:00:00'),
(51, '12072010', 65, 'Anik Yuliana', 'A', 'Pemrogaman', '-', NULL, '2025-12-17 02:10:00'),
(52, '070771', 66, 'Muhammad Iniesta Wildan Bromo Putra', 'B', 'Teknik Informatika', '083841426422', 'uploads/profil/mhs_070771_1766543709.jpg', '2025-12-19 05:38:00'),
(53, '11112222', 69, 'Massayu Sekar Anindita', 'B', 'Stastatika', '085727662393', NULL, '2025-12-29 06:33:00');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mata_kuliah`
--

CREATE TABLE `mata_kuliah` (
  `kode_mk` varchar(10) NOT NULL,
  `nama_mk` varchar(100) NOT NULL,
  `sks` int(11) DEFAULT 3,
  `semester` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mata_kuliah`
--

INSERT INTO `mata_kuliah` (`kode_mk`, `nama_mk`, `sks`, `semester`) VALUES
('MK001', 'Basis Data', 3, 'Ganjil'),
('MK002', 'Pemrograman', 3, 'Ganjil'),
('MK003', 'Jaringan', 3, 'Ganjil'),
('MK004', 'Statistika', 3, 'Ganjil'),
('STP2503', 'Basis Data S1', 4, 'Ganjil');

-- --------------------------------------------------------

--
-- Struktur dari tabel `materi_perkuliahan`
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

-- --------------------------------------------------------

--
-- Struktur dari tabel `penggantian_inhall`
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

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi` text NOT NULL,
  `target_role` enum('semua','mahasiswa','asisten') NOT NULL DEFAULT 'semua',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengumuman`
--

INSERT INTO `pengumuman` (`id`, `judul`, `isi`, `target_role`, `created_at`, `created_by`, `status`) VALUES
(4, 'Fix Bug and Optimalisation System ', 'The system will be under maintenance from January 1 to January 3, so the server cannot be accessed during this time', 'semua', '2025-12-30 06:32:03', 38, 'inactive');

-- --------------------------------------------------------

--
-- Struktur dari tabel `presensi_mahasiswa`
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
-- Dumping data untuk tabel `presensi_mahasiswa`
--

INSERT INTO `presensi_mahasiswa` (`id`, `jadwal_id`, `nim`, `status`, `waktu_presensi`, `metode`, `validated_by`, `location_lab`, `device_id`, `ip_address`, `verified_by_system`) VALUES
(720, 924, '251062022', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(721, 924, '251062025', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(722, 924, '251062026', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(723, 924, '241064001', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(724, 924, '241064002', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(725, 924, '241064004', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(726, 924, '241064007', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(727, 924, '241064008', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(728, 924, '241064009', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(729, 924, '241064013', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(730, 924, '241064014', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(731, 924, '241067010', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(732, 924, '241067011', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(733, 924, '241068005', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(734, 924, '241068006', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(735, 924, '242062001', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(736, 924, '242062004', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(737, 924, '211063024', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(738, 924, '24346554', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(739, 924, '765434567', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(740, 924, '9532753', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(746, 925, '251062022', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(747, 925, '251062025', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(748, 925, '251062026', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(749, 925, '241064001', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(750, 925, '241064002', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(751, 925, '241064004', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(752, 925, '241064007', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(753, 925, '241064008', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(754, 925, '241064009', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(755, 925, '241064013', 'alpha', '2025-12-30 05:43:41', 'auto', NULL, NULL, NULL, NULL, 1),
(756, 925, '241064014', 'alpha', '2025-12-30 05:43:42', 'auto', NULL, NULL, NULL, NULL, 1),
(757, 925, '241067010', 'alpha', '2025-12-30 05:43:42', 'auto', NULL, NULL, NULL, NULL, 1),
(758, 925, '241067011', 'alpha', '2025-12-30 05:43:42', 'auto', NULL, NULL, NULL, NULL, 1),
(759, 925, '241068005', 'alpha', '2025-12-30 05:43:42', 'auto', NULL, NULL, NULL, NULL, 1),
(760, 925, '241068006', 'alpha', '2025-12-30 05:43:42', 'auto', NULL, NULL, NULL, NULL, 1),
(761, 925, '242062001', 'alpha', '2025-12-30 05:43:42', 'auto', NULL, NULL, NULL, NULL, 1),
(762, 925, '242062004', 'alpha', '2025-12-30 05:43:42', 'auto', NULL, NULL, NULL, NULL, 1),
(763, 925, '211063024', 'alpha', '2025-12-30 05:43:42', 'auto', NULL, NULL, NULL, NULL, 1),
(764, 925, '24346554', 'alpha', '2025-12-30 05:43:42', 'auto', NULL, NULL, NULL, NULL, 1),
(765, 925, '765434567', 'alpha', '2025-12-30 05:43:42', 'auto', NULL, NULL, NULL, NULL, 1),
(766, 925, '9532753', 'alpha', '2025-12-30 05:43:42', 'auto', NULL, NULL, NULL, NULL, 1),
(767, 903, '11112222', 'hadir', '2025-12-30 06:00:35', 'manual', NULL, NULL, NULL, NULL, 0),
(768, 902, '070771', 'hadir', '2025-12-30 06:00:44', 'manual', NULL, NULL, NULL, NULL, 0),
(769, 903, '070771', 'hadir', '2025-12-30 06:00:47', 'manual', NULL, NULL, NULL, NULL, 0),
(770, 957, '12072010', 'hadir', '2025-12-30 06:00:52', 'manual', NULL, NULL, NULL, NULL, 0),
(771, 958, '12072010', 'hadir', '2025-12-30 06:00:54', 'manual', NULL, NULL, NULL, NULL, 0),
(772, 957, '12345678', 'hadir', '2025-12-30 06:01:01', 'manual', NULL, NULL, NULL, NULL, 0),
(773, 958, '12345678', 'hadir', '2025-12-30 06:01:06', 'manual', NULL, NULL, NULL, NULL, 0),
(774, 957, '230607', 'hadir', '2025-12-30 06:01:12', 'manual', NULL, NULL, NULL, NULL, 0),
(775, 958, '230607', 'hadir', '2025-12-30 06:01:14', 'manual', NULL, NULL, NULL, NULL, 0),
(778, 968, '11112222', 'hadir', '2025-12-31 03:53:43', 'qr', NULL, 'Laboratorium Basis Data', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Sa', '::1', 1),
(779, 968, '070771', 'alpha', '2025-12-31 05:19:42', 'auto', NULL, NULL, NULL, NULL, 1),
(780, 968, '070771', 'alpha', '2025-12-31 05:19:42', 'auto', NULL, NULL, NULL, NULL, 1),
(781, 904, '070771', 'alpha', '2026-01-06 01:17:04', 'auto', NULL, NULL, NULL, NULL, 1),
(782, 904, '11112222', 'alpha', '2026-01-06 01:17:04', 'auto', NULL, NULL, NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `qr_code_session`
--

CREATE TABLE `qr_code_session` (
  `id` int(11) NOT NULL,
  `jadwal_id` int(11) NOT NULL,
  `qr_code` varchar(100) NOT NULL,
  `expired_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `qr_code_session`
--

INSERT INTO `qr_code_session` (`id`, `jadwal_id`, `qr_code`, `expired_at`, `created_at`) VALUES
(136, 968, '54e24550d26af83110a1bcfcdac91515_1767144519', '2025-12-31 12:19:00', '2025-12-31 01:28:39');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
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
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`, `remember_token`, `token_expires`) VALUES
(38, 'admin', '$2y$10$Zgoeh1cedR/dfuM6mHF4ZOcCugxck/tJt5bltIVUpPWLJY5ZeyHwO', 'admin', '2025-12-12 03:52:26', NULL, NULL),
(39, '251062022', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(40, '251062025', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(41, '251062026', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(42, '241064001', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(43, '241064002', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(44, '241064004', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(45, '241064007', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(46, '241064008', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(47, '241064009', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(48, '241064013', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(49, '241064014', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(50, '241067010', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(51, '241067011', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(52, '241068005', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(53, '241068006', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(54, '242062001', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(55, '242062004', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(56, '211063024', '1234567', 'mahasiswa', '2025-12-12 03:55:19', NULL, NULL),
(57, '231064013', '$2y$10$6Viv7759evphGlbd/MUF/.Dg0FuUhtagsSMxPE9zg8x6ytOj3U/xW', 'asisten', '2025-12-12 04:24:41', NULL, NULL),
(58, '231064018', '$2y$10$jZtqUTrZZ8ChjVlOz1s39OABAEPEwPXzkuhyTpyUYoM5gCZI.HBwy', 'asisten', '2025-12-12 04:25:17', NULL, NULL),
(59, '23108012', '$2y$10$7.3eVX20PSp5grtZN4W2iORYW8yj4nYEI7OysKFIKSXH9yFI1DDqa', 'asisten', '2025-12-12 04:25:41', NULL, NULL),
(60, '230607', '$2y$10$2E1rNMxwXdMrz/mL7uOpWecU38O4Er7AntBVBGo/3O9xT6.aiy7P2', 'mahasiswa', '2025-12-15 03:32:47', NULL, NULL),
(61, '24346554', '$2y$10$F1.bV5KRPlRj7Dpx8v1e9u6N2v5e4K6N/9nhLdt5Zbu/k2OHM/8cm', 'mahasiswa', '2025-12-15 07:01:20', NULL, NULL),
(62, '765434567', '123456', 'mahasiswa', '2025-12-15 07:15:18', NULL, NULL),
(63, '9532753', '123456', 'mahasiswa', '2025-12-15 07:15:30', NULL, NULL),
(64, '12345678', '$2y$10$mBP4PK0drPux3ReoHLBqneG66RwuGAc07psKbFkj09CPRJojZ8Yt2', 'mahasiswa', '2025-12-16 16:01:14', NULL, NULL),
(65, '12072010', '$2y$10$CdPpmzBPKyZNCZtCHS2Wp.fFucesbMdcq9gC0IOIGAlADQtncYDmW', 'mahasiswa', '2025-12-17 02:10:31', NULL, NULL),
(66, '070771', '$2y$10$gkBEq7zNQj30iyVI/3ukUuRJVpXV9dTjBSzgV9ieVfWYDjTdCRLIC', 'mahasiswa', '2025-12-19 05:39:22', NULL, NULL),
(68, '123456789', 'asisten123', 'asisten', '2025-12-19 06:09:07', NULL, NULL),
(69, '11112222', '$2y$10$rVl2TpeIjgxWoz0xBHPDPeP9lM.n6.WPwgcsEEA1s39h1LrGO3rQa', 'mahasiswa', '2025-12-29 06:34:56', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absen_asisten`
--
ALTER TABLE `absen_asisten`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jadwal_id` (`jadwal_id`),
  ADD KEY `kode_asisten` (`kode_asisten`),
  ADD KEY `pengganti` (`pengganti`),
  ADD KEY `fk_absen_asisten_approved_by` (`approved_by`);

--
-- Indeks untuk tabel `asisten`
--
ALTER TABLE `asisten`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_asisten` (`kode_asisten`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `kode_mk` (`kode_mk`);

--
-- Indeks untuk tabel `jadwal`
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
-- Indeks untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`kode_kelas`);

--
-- Indeks untuk tabel `lab`
--
ALTER TABLE `lab`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_lab` (`kode_lab`);

--
-- Indeks untuk tabel `lab_matakuliah`
--
ALTER TABLE `lab_matakuliah`
  ADD PRIMARY KEY (`id_lab`,`kode_mk`),
  ADD KEY `idx_lab_matakuliah_lab` (`id_lab`),
  ADD KEY `idx_lab_matakuliah_mk` (`kode_mk`);

--
-- Indeks untuk tabel `log_presensi`
--
ALTER TABLE `log_presensi`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_mahasiswa_kelas` (`kode_kelas`);

--
-- Indeks untuk tabel `mata_kuliah`
--
ALTER TABLE `mata_kuliah`
  ADD PRIMARY KEY (`kode_mk`);

--
-- Indeks untuk tabel `materi_perkuliahan`
--
ALTER TABLE `materi_perkuliahan`
  ADD PRIMARY KEY (`id_materi`),
  ADD KEY `id_jadwal` (`id_jadwal`),
  ADD KEY `uploader_id` (`uploader_id`);

--
-- Indeks untuk tabel `penggantian_inhall`
--
ALTER TABLE `penggantian_inhall`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nim` (`nim`),
  ADD KEY `jadwal_asli_id` (`jadwal_asli_id`),
  ADD KEY `jadwal_inhall_id` (`jadwal_inhall_id`),
  ADD KEY `fk_approved_by` (`approved_by`);

--
-- Indeks untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `presensi_mahasiswa`
--
ALTER TABLE `presensi_mahasiswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nim` (`nim`),
  ADD KEY `validated_by` (`validated_by`),
  ADD KEY `idx_presensi_jadwal_nim` (`jadwal_id`,`nim`),
  ADD KEY `idx_presensi_tanggal` (`waktu_presensi`);

--
-- Indeks untuk tabel `qr_code_session`
--
ALTER TABLE `qr_code_session`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `qr_code` (`qr_code`),
  ADD KEY `jadwal_id` (`jadwal_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_users_username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absen_asisten`
--
ALTER TABLE `absen_asisten`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT untuk tabel `asisten`
--
ALTER TABLE `asisten`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=969;

--
-- AUTO_INCREMENT untuk tabel `lab`
--
ALTER TABLE `lab`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `log_presensi`
--
ALTER TABLE `log_presensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=658;

--
-- AUTO_INCREMENT untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT untuk tabel `materi_perkuliahan`
--
ALTER TABLE `materi_perkuliahan`
  MODIFY `id_materi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `penggantian_inhall`
--
ALTER TABLE `penggantian_inhall`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `presensi_mahasiswa`
--
ALTER TABLE `presensi_mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=783;

--
-- AUTO_INCREMENT untuk tabel `qr_code_session`
--
ALTER TABLE `qr_code_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absen_asisten`
--
ALTER TABLE `absen_asisten`
  ADD CONSTRAINT `absen_asisten_ibfk_1` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`),
  ADD CONSTRAINT `absen_asisten_ibfk_2` FOREIGN KEY (`kode_asisten`) REFERENCES `asisten` (`kode_asisten`),
  ADD CONSTRAINT `absen_asisten_ibfk_3` FOREIGN KEY (`pengganti`) REFERENCES `asisten` (`kode_asisten`),
  ADD CONSTRAINT `fk_absen_asisten_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `asisten`
--
ALTER TABLE `asisten`
  ADD CONSTRAINT `asisten_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asisten_ibfk_2` FOREIGN KEY (`kode_mk`) REFERENCES `mata_kuliah` (`kode_mk`);

--
-- Ketidakleluasaan untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  ADD CONSTRAINT `jadwal_ibfk_1` FOREIGN KEY (`kode_lab`) REFERENCES `lab` (`kode_lab`),
  ADD CONSTRAINT `jadwal_ibfk_2` FOREIGN KEY (`kode_kelas`) REFERENCES `kelas` (`kode_kelas`),
  ADD CONSTRAINT `jadwal_ibfk_3` FOREIGN KEY (`kode_mk`) REFERENCES `mata_kuliah` (`kode_mk`),
  ADD CONSTRAINT `jadwal_ibfk_4` FOREIGN KEY (`kode_asisten_1`) REFERENCES `asisten` (`kode_asisten`),
  ADD CONSTRAINT `jadwal_ibfk_5` FOREIGN KEY (`kode_asisten_2`) REFERENCES `asisten` (`kode_asisten`);

--
-- Ketidakleluasaan untuk tabel `lab_matakuliah`
--
ALTER TABLE `lab_matakuliah`
  ADD CONSTRAINT `fk_labmatakuliah_lab` FOREIGN KEY (`id_lab`) REFERENCES `lab` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_labmatakuliah_matakuliah` FOREIGN KEY (`kode_mk`) REFERENCES `mata_kuliah` (`kode_mk`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD CONSTRAINT `mahasiswa_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mahasiswa_ibfk_2` FOREIGN KEY (`kode_kelas`) REFERENCES `kelas` (`kode_kelas`);

--
-- Ketidakleluasaan untuk tabel `materi_perkuliahan`
--
ALTER TABLE `materi_perkuliahan`
  ADD CONSTRAINT `materi_perkuliahan_ibfk_1` FOREIGN KEY (`id_jadwal`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `materi_perkuliahan_ibfk_2` FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `penggantian_inhall`
--
ALTER TABLE `penggantian_inhall`
  ADD CONSTRAINT `fk_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `asisten` (`kode_asisten`) ON DELETE SET NULL,
  ADD CONSTRAINT `penggantian_inhall_ibfk_1` FOREIGN KEY (`nim`) REFERENCES `mahasiswa` (`nim`),
  ADD CONSTRAINT `penggantian_inhall_ibfk_2` FOREIGN KEY (`jadwal_asli_id`) REFERENCES `jadwal` (`id`),
  ADD CONSTRAINT `penggantian_inhall_ibfk_3` FOREIGN KEY (`jadwal_inhall_id`) REFERENCES `jadwal` (`id`);

--
-- Ketidakleluasaan untuk tabel `presensi_mahasiswa`
--
ALTER TABLE `presensi_mahasiswa`
  ADD CONSTRAINT `presensi_mahasiswa_ibfk_1` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`),
  ADD CONSTRAINT `presensi_mahasiswa_ibfk_2` FOREIGN KEY (`nim`) REFERENCES `mahasiswa` (`nim`),
  ADD CONSTRAINT `presensi_mahasiswa_ibfk_3` FOREIGN KEY (`validated_by`) REFERENCES `asisten` (`kode_asisten`);

--
-- Ketidakleluasaan untuk tabel `qr_code_session`
--
ALTER TABLE `qr_code_session`
  ADD CONSTRAINT `qr_code_session_ibfk_1` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
