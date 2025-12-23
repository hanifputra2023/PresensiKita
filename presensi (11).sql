-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 22 Des 2025 pada 16.40
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
  `catatan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `absen_asisten`
--

INSERT INTO `absen_asisten` (`id`, `jadwal_id`, `kode_asisten`, `status`, `jam_masuk`, `jam_keluar`, `pengganti`, `catatan`) VALUES
(66, 902, '231064013', 'hadir', '21:25:13', NULL, NULL, NULL);

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
(6, '231064013', 57, 'AVOREY BIAS AGUNG V.D', '-', NULL, 'STP2503', 'aktif'),
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
(902, 1, '2025-12-22', '21:17:00', '22:00:00', 'LAB001', 'B', 'MK002', 'Pertemuan 1 - Pengenalan', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(903, 2, '2025-12-29', '21:17:00', '22:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 2 - Dasar', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(904, 3, '2026-01-05', '21:17:00', '22:00:00', 'LAB001', 'B', 'MK002', 'Pertemuan 3 - Lanjutan I', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(905, 4, '2026-01-12', '21:17:00', '22:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 4 - Lanjutan II', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(906, 5, '2026-01-19', '21:17:00', '22:00:00', 'LAB001', 'B', 'MK002', 'Pertemuan 5 - Praktik I', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(907, 6, '2026-01-26', '21:17:00', '22:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 6 - Praktik II', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(908, 7, '2026-02-02', '21:17:00', '22:00:00', 'LAB001', 'B', 'MK002', 'Pertemuan 7 - Praktik III', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(909, 8, '2026-02-09', '21:17:00', '22:00:00', 'LAB002', 'B', 'MK002', 'Pertemuan 8 - Review', '231064013', NULL, 'materi', NULL, '2025-12-22 14:18:02'),
(910, 9, '2026-02-16', '21:17:00', '22:00:00', 'LAB001', 'B', 'MK002', 'Praresponsi', '231064013', NULL, 'praresponsi', NULL, '2025-12-22 14:18:02'),
(911, 9, '2026-02-16', '22:00:00', '22:43:00', 'LAB001', 'B', 'MK002', 'Inhall', '231064013', NULL, 'inhall', NULL, '2025-12-22 14:18:02'),
(912, 10, '2026-02-23', '21:17:00', '22:00:00', 'LAB002', 'B', 'MK002', 'Responsi', '231064013', NULL, 'responsi', NULL, '2025-12-22 14:18:02');

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
  `status` enum('active','maintenance') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `lab`
--

INSERT INTO `lab` (`id`, `kode_lab`, `nama_lab`, `kapasitas`, `lokasi`, `status`) VALUES
(1, 'LAB001', 'Laboratorium Basis Data', 30, 'Gedung A Lantai 1', 'active'),
(2, 'LAB002', 'Laboratorium Pemrograman', 30, 'Gedung A Lantai 3', 'active'),
(3, 'LAB003', 'Laboratorium Jaringan', 25, 'Gedung B Lantai 1', 'active'),
(4, 'LAB004', 'Laboratorium Statistika', 20, 'Gedung B Lantai 2', 'active');

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
(1, 'MK002'),
(1, 'STP2503'),
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
(533, 57, 'LOGIN', 'users', 57, 'User login berhasil sebagai asisten', '2025-12-22 14:19:03'),
(534, 66, 'LOGIN', 'users', 66, 'User login berhasil sebagai mahasiswa', '2025-12-22 14:19:20'),
(535, 57, 'GENERATE_QR', 'qr_code_session', 117, 'QR Code untuk jadwal #902, expired: 2025-12-22 22:00:00', '2025-12-22 14:19:56'),
(536, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 902, 'Mahasiswa 070771 presensi via QR di Laboratorium Basis Data', '2025-12-22 14:20:06'),
(537, 57, 'GENERATE_QR', 'qr_code_session', 118, 'QR Code untuk jadwal #902, expired: 2025-12-22 22:00:00', '2025-12-22 14:25:13'),
(538, 0, 'PRESENSI_QR', 'presensi_mahasiswa', 902, 'Mahasiswa 070771 presensi via QR di Laboratorium Basis Data', '2025-12-22 14:25:20');

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
(52, '070771', 66, 'Muhammad Iniesta Wildan Bromo Putra', 'B', 'Teknik Informatika', '083841426422', NULL, '2025-12-19 05:38:00');

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
(624, 902, '070771', 'izin', '2025-12-22 15:39:43', 'qr', NULL, 'Laboratorium Basis Data', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Sa', '::1', 1);

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
(118, 902, '712bcdd1403ea1ed28572aeff5c80f86_1766413513', '2025-12-22 22:00:00', '2025-12-22 14:25:13');

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
(38, 'admin', 'admin', 'admin', '2025-12-12 03:52:26', NULL, NULL),
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
(57, '231064013', 'asisten123', 'asisten', '2025-12-12 04:24:41', NULL, NULL),
(58, '231064018', 'asisten123', 'asisten', '2025-12-12 04:25:17', NULL, NULL),
(59, '23108012', 'asisten123', 'asisten', '2025-12-12 04:25:41', NULL, NULL),
(60, '230607', 'admin123', 'mahasiswa', '2025-12-15 03:32:47', NULL, NULL),
(61, '24346554', '123456', 'mahasiswa', '2025-12-15 07:01:20', NULL, NULL),
(62, '765434567', '123456', 'mahasiswa', '2025-12-15 07:15:18', NULL, NULL),
(63, '9532753', '123456', 'mahasiswa', '2025-12-15 07:15:30', NULL, NULL),
(64, '12345678', '123456', 'mahasiswa', '2025-12-16 16:01:14', NULL, NULL),
(65, '12072010', '123456', 'mahasiswa', '2025-12-17 02:10:31', NULL, NULL),
(66, '070771', '1234567', 'mahasiswa', '2025-12-19 05:39:22', NULL, NULL),
(68, '123456789', 'asisten123', 'asisten', '2025-12-19 06:09:07', NULL, NULL);

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
  ADD KEY `pengganti` (`pengganti`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT untuk tabel `asisten`
--
ALTER TABLE `asisten`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=913;

--
-- AUTO_INCREMENT untuk tabel `lab`
--
ALTER TABLE `lab`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `log_presensi`
--
ALTER TABLE `log_presensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=539;

--
-- AUTO_INCREMENT untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT untuk tabel `materi_perkuliahan`
--
ALTER TABLE `materi_perkuliahan`
  MODIFY `id_materi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `penggantian_inhall`
--
ALTER TABLE `penggantian_inhall`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT untuk tabel `presensi_mahasiswa`
--
ALTER TABLE `presensi_mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=625;

--
-- AUTO_INCREMENT untuk tabel `qr_code_session`
--
ALTER TABLE `qr_code_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=119;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absen_asisten`
--
ALTER TABLE `absen_asisten`
  ADD CONSTRAINT `absen_asisten_ibfk_1` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`),
  ADD CONSTRAINT `absen_asisten_ibfk_2` FOREIGN KEY (`kode_asisten`) REFERENCES `asisten` (`kode_asisten`),
  ADD CONSTRAINT `absen_asisten_ibfk_3` FOREIGN KEY (`pengganti`) REFERENCES `asisten` (`kode_asisten`);

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
