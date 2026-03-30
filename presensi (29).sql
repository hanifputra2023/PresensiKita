-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 30 Mar 2026 pada 06.31
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
(102, 1144, '23108012', 'hadir', '10:03:27', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(103, 1180, '23108012', 'hadir', '09:40:15', NULL, NULL, NULL, 'pending', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `jabatan` varchar(50) DEFAULT 'Administrator',
  `status` enum('aktif','nonaktif') DEFAULT 'aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin`
--

INSERT INTO `admin` (`id`, `user_id`, `nama`, `email`, `no_hp`, `foto`, `jabatan`, `status`, `created_at`) VALUES
(1, 38, 'Nanda Hanif Abyan Bromo Putra', 'nandahanif2020@gmail.com', '083841426400', 'uploads/profil/admin_1_1770864843.jpg', 'Administrator', 'aktif', '2026-02-11 12:48:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `app_settings`
--

CREATE TABLE `app_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `app_settings`
--

INSERT INTO `app_settings` (`setting_key`, `setting_value`, `description`) VALUES
('app_name', 'Sistem Presensi', 'Nama Aplikasi'),
('contact_wa', '6285727662393', 'Nomor WhatsApp Admin'),
('instansi_name', 'Universitas AKPRIND', 'Nama Instansi'),
('maintenance_mode', '0', 'Mode Maintenance (1=Ya, 0=Tidak)'),
('semester_aktif', 'Genap', 'Semester Aktif'),
('tahun_ajaran', '2026/2027', 'Tahun Ajaran'),
('wa_token', 'xyoRVSL6be4h8XT6RdE1', NULL);

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
(6, '231064013', 57, 'AVOREY BIAS AGUNG V.D', '6285865895255', 'uploads/profil/ast_231064013_1767079909.png', 'STP2503', 'aktif'),
(7, '231064018', 58, 'DEFAULLO A.R BENGE', '6285727662393', 'uploads/profil/ast_231064018_1769052683.png', 'STP2503', 'aktif'),
(8, '23108012', 59, 'AGUSTINUS KAROL SANI', '6285180972214', NULL, 'STP2503', 'aktif'),
(9, '123456789', 68, 'Mulyono', '6283841426400', NULL, 'MK003', 'aktif'),
(10, '23108000', 186, 'Mulyono Raja Tipu Tipu', '', NULL, 'MK006', 'aktif'),
(11, '23108001', 187, 'Jokowi Jagonya Ngutang', '', NULL, 'MK006', 'aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `berita_acara`
--

CREATE TABLE `berita_acara` (
  `id` int(11) NOT NULL,
  `jadwal_id` int(11) NOT NULL,
  `kode_asisten` varchar(50) NOT NULL,
  `waktu_mulai_real` datetime DEFAULT NULL,
  `waktu_selesai_real` datetime DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `foto_bukti` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_jawaban_kuis`
--

CREATE TABLE `detail_jawaban_kuis` (
  `id` int(11) NOT NULL,
  `hasil_kuis_id` int(11) NOT NULL,
  `soal_id` int(11) NOT NULL,
  `jawaban_mahasiswa` char(1) DEFAULT NULL,
  `is_benar` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `feedback_praktikum`
--

CREATE TABLE `feedback_praktikum` (
  `id` int(11) NOT NULL,
  `jadwal_id` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `rating` int(1) NOT NULL,
  `komentar` text DEFAULT NULL,
  `is_anonim` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `hasil_kuis`
--

CREATE TABLE `hasil_kuis` (
  `id` int(11) NOT NULL,
  `kuis_id` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `nilai` float NOT NULL DEFAULT 0,
  `benar` int(11) NOT NULL DEFAULT 0,
  `salah` int(11) NOT NULL DEFAULT 0,
  `waktu_mulai` datetime NOT NULL,
  `waktu_selesai` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `sesi` int(11) DEFAULT 0 COMMENT '0=Semua, 1=Sesi 1, 2=Sesi 2, dst',
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

INSERT INTO `jadwal` (`id`, `pertemuan_ke`, `tanggal`, `jam_mulai`, `jam_selesai`, `kode_lab`, `kode_kelas`, `sesi`, `kode_mk`, `materi`, `kode_asisten_1`, `kode_asisten_2`, `jenis`, `keterangan`, `created_at`) VALUES
(1180, 1, '2026-03-30', '09:40:00', '11:00:00', 'LAB003', 'F', 1, 'MK006', 'Pertemuan 1 - Pengenalan', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1181, 2, '2026-04-06', '09:40:00', '11:00:00', 'LAB004', 'F', 1, 'MK006', 'Pertemuan 2 - Dasar', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1182, 3, '2026-04-13', '09:40:00', '11:00:00', 'LAB003', 'F', 1, 'MK006', 'Pertemuan 3 - Lanjutan I', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1183, 4, '2026-04-20', '09:40:00', '11:00:00', 'LAB004', 'F', 1, 'MK006', 'Pertemuan 4 - Lanjutan II', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1184, 5, '2026-04-27', '09:40:00', '11:00:00', 'LAB003', 'F', 1, 'MK006', 'Pertemuan 5 - Praktik I', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1185, 6, '2026-05-04', '09:40:00', '11:00:00', 'LAB004', 'F', 1, 'MK006', 'Pertemuan 6 - Praktik II', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1186, 7, '2026-05-11', '09:40:00', '11:00:00', 'LAB003', 'F', 1, 'MK006', 'Pertemuan 7 - Praktik III', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1187, 8, '2026-05-18', '09:40:00', '11:00:00', 'LAB004', 'F', 1, 'MK006', 'Pertemuan 8 - Review', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1188, 9, '2026-05-25', '09:40:00', '11:00:00', 'LAB003', 'F', 1, 'MK006', 'Praresponsi', '23108012', '231064013', 'praresponsi', NULL, '2026-03-30 02:34:04'),
(1189, 9, '2026-05-25', '11:00:00', '12:20:00', 'LAB003', 'F', 1, 'MK006', 'Inhall 1', '23108012', '231064013', 'inhall', NULL, '2026-03-30 02:34:04'),
(1190, 9, '2026-05-25', '12:20:00', '13:40:00', 'LAB003', 'F', 1, 'MK006', 'Inhall 2', '23108012', '231064013', 'inhall', NULL, '2026-03-30 02:34:04'),
(1191, 10, '2026-06-01', '09:40:00', '11:00:00', 'LAB004', 'F', 1, 'MK006', 'Responsi', '23108012', '231064013', 'responsi', NULL, '2026-03-30 02:34:04'),
(1192, 1, '2026-03-30', '11:00:00', '13:00:00', 'LAB003', 'F', 2, 'MK006', 'Pertemuan 1 - Pengenalan', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1193, 2, '2026-04-06', '11:00:00', '13:00:00', 'LAB004', 'F', 2, 'MK006', 'Pertemuan 2 - Dasar', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1194, 3, '2026-04-13', '11:00:00', '13:00:00', 'LAB003', 'F', 2, 'MK006', 'Pertemuan 3 - Lanjutan I', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1195, 4, '2026-04-20', '11:00:00', '13:00:00', 'LAB004', 'F', 2, 'MK006', 'Pertemuan 4 - Lanjutan II', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1196, 5, '2026-04-27', '11:00:00', '13:00:00', 'LAB003', 'F', 2, 'MK006', 'Pertemuan 5 - Praktik I', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1197, 6, '2026-05-04', '11:00:00', '13:00:00', 'LAB004', 'F', 2, 'MK006', 'Pertemuan 6 - Praktik II', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1198, 7, '2026-05-11', '11:00:00', '13:00:00', 'LAB003', 'F', 2, 'MK006', 'Pertemuan 7 - Praktik III', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1199, 8, '2026-05-18', '11:00:00', '13:00:00', 'LAB004', 'F', 2, 'MK006', 'Pertemuan 8 - Review', '23108012', '231064013', 'materi', NULL, '2026-03-30 02:34:04'),
(1200, 9, '2026-05-25', '11:00:00', '13:00:00', 'LAB003', 'F', 2, 'MK006', 'Praresponsi', '23108012', '231064013', 'praresponsi', NULL, '2026-03-30 02:34:04'),
(1201, 9, '2026-05-25', '13:00:00', '15:00:00', 'LAB003', 'F', 2, 'MK006', 'Inhall 1', '23108012', '231064013', 'inhall', NULL, '2026-03-30 02:34:04'),
(1202, 9, '2026-05-25', '15:00:00', '17:00:00', 'LAB003', 'F', 2, 'MK006', 'Inhall 2', '23108012', '231064013', 'inhall', NULL, '2026-03-30 02:34:04'),
(1203, 10, '2026-06-01', '11:00:00', '13:00:00', 'LAB004', 'F', 2, 'MK006', 'Responsi', '23108012', '231064013', 'responsi', NULL, '2026-03-30 02:34:04');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jurnal_praktikum`
--

CREATE TABLE `jurnal_praktikum` (
  `id` int(11) NOT NULL,
  `jadwal_id` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `kegiatan` text NOT NULL,
  `hasil` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
('E', 'STATISTIK-2024', 'Statistik S1', '2024/2025'),
('F', 'PPLG', 'Pemrograman Perangkat Lunak dan Gim', '2025/2026');

-- --------------------------------------------------------

--
-- Struktur dari tabel `kuis`
--

CREATE TABLE `kuis` (
  `id` int(11) NOT NULL,
  `jadwal_id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `durasi_menit` int(11) NOT NULL DEFAULT 15,
  `metode_penilaian` enum('skala_100','poin_murni','bobot_kustom') NOT NULL DEFAULT 'skala_100',
  `bobot_per_soal` int(11) NOT NULL DEFAULT 0,
  `status` enum('draft','aktif','selesai') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(3, 'LAB003', 'Laboratorium Jaringan', 51, 'Gedung B Lantai 1', 'active', '-7.787231895737355', '110.3885152626932'),
(4, 'LAB004', 'Laboratorium Statistika', 51, 'Gedung B Lantai 2', 'active', '-7.787231895737355', '110.3885152626932');

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
(1, 'STP2503'),
(2, 'MK001'),
(2, 'MK002'),
(2, 'STP2503'),
(3, 'MK001'),
(3, 'MK002'),
(3, 'MK003'),
(3, 'MK004'),
(3, 'MK006'),
(3, 'STP2503'),
(3, 'TI'),
(4, 'MK001'),
(4, 'MK002'),
(4, 'MK003'),
(4, 'MK004'),
(4, 'MK006'),
(4, 'STP2503'),
(4, 'TI');

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
(1354, 59, 'LOGIN', 'users', 59, 'User login berhasil sebagai asisten', '2026-03-30 02:35:22'),
(1355, 59, 'GENERATE_QR', 'qr_code_session', 172, 'QR Code untuk jadwal #1180, expired: 2026-03-30 10:10:00', '2026-03-30 02:40:16'),
(1356, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-03-30 02:43:51'),
(1357, 128, 'LOGIN', 'users', 128, 'User login berhasil sebagai mahasiswa', '2026-03-30 02:45:41'),
(1358, NULL, 'PRESENSI_QR', 'presensi_mahasiswa', 1180, 'Mahasiswa 2025043 presensi via QR di Laboratorium Jaringan', '2026-03-30 02:47:33'),
(1359, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025022 - hadir', '2026-03-30 02:50:49'),
(1360, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025067 - hadir', '2026-03-30 02:50:49'),
(1361, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025091 - hadir', '2026-03-30 02:50:50'),
(1362, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025087 - hadir', '2026-03-30 02:50:51'),
(1363, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025097 - hadir', '2026-03-30 02:50:52'),
(1364, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025033 - hadir', '2026-03-30 02:50:52'),
(1365, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025039 - hadir', '2026-03-30 02:50:53'),
(1366, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025027 - hadir', '2026-03-30 02:50:55'),
(1367, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025077 - hadir', '2026-03-30 02:50:56'),
(1368, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025015 - hadir', '2026-03-30 02:50:59'),
(1369, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025013 - hadir', '2026-03-30 02:51:00'),
(1370, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025061 - hadir', '2026-03-30 02:51:02'),
(1371, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025073 - hadir', '2026-03-30 02:51:04'),
(1372, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025085 - hadir', '2026-03-30 02:51:06'),
(1373, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025001 - hadir', '2026-03-30 02:51:08'),
(1374, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025045 - hadir', '2026-03-30 02:51:10'),
(1375, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025075 - hadir', '2026-03-30 02:51:13'),
(1376, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025095 - hadir', '2026-03-30 02:51:15'),
(1377, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025007 - hadir', '2026-03-30 02:51:16'),
(1378, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025002 - hadir', '2026-03-30 02:51:18'),
(1379, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025071 - hadir', '2026-03-30 02:51:21'),
(1380, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025093 - hadir', '2026-03-30 02:51:23'),
(1381, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025047 - hadir', '2026-03-30 02:51:26'),
(1382, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025081 - hadir', '2026-03-30 02:51:29'),
(1383, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025019 - hadir', '2026-03-30 02:51:32'),
(1384, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025031 - hadir', '2026-03-30 02:51:35'),
(1385, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025099 - hadir', '2026-03-30 02:51:38'),
(1386, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025059 - hadir', '2026-03-30 02:51:42'),
(1387, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025017 - hadir', '2026-03-30 02:51:44'),
(1388, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025035 - hadir', '2026-03-30 02:51:47'),
(1389, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025065 - hadir', '2026-03-30 02:51:51'),
(1390, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025057 - hadir', '2026-03-30 02:51:54'),
(1391, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025011 - hadir', '2026-03-30 02:51:56'),
(1392, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025053 - hadir', '2026-03-30 02:51:58'),
(1393, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025009 - hadir', '2026-03-30 02:52:02'),
(1394, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025025 - hadir', '2026-03-30 02:52:04'),
(1395, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025049 - hadir', '2026-03-30 02:52:07'),
(1396, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025069 - hadir', '2026-03-30 02:52:10'),
(1397, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025089 - hadir', '2026-03-30 02:52:14'),
(1398, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025079 - hadir', '2026-03-30 02:52:17'),
(1399, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025005 - hadir', '2026-03-30 02:52:19'),
(1400, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025037 - hadir', '2026-03-30 02:52:22'),
(1401, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025051 - hadir', '2026-03-30 02:52:24'),
(1402, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025003 - hadir', '2026-03-30 02:52:27'),
(1403, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025023 - hadir', '2026-03-30 02:52:29'),
(1404, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025029 - hadir', '2026-03-30 02:52:32'),
(1405, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025063 - hadir', '2026-03-30 02:52:34'),
(1406, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025083 - hadir', '2026-03-30 02:52:37'),
(1407, 59, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1180, 'Presensi manual: 2025021 - hadir', '2026-03-30 02:52:39'),
(1408, 128, 'LOGIN', 'users', 128, 'User login berhasil sebagai mahasiswa', '2026-03-30 04:01:38'),
(1409, 128, 'LOGIN', 'users', 128, 'User login berhasil sebagai mahasiswa', '2026-03-30 04:23:43');

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
  `sesi` int(11) DEFAULT 1,
  `prodi` varchar(50) DEFAULT NULL,
  `no_hp` varchar(15) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `tanggal_daftar` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `nim`, `user_id`, `nama`, `kode_kelas`, `sesi`, `prodi`, `no_hp`, `foto`, `tanggal_daftar`, `status`) VALUES
(28, '251062022', 39, 'NUNUT SITUMORANG', 'E', 1, 'Statistik S1', '0', 'uploads/profil/mhs_251062022_1765869042.png', '2025-12-12 03:55:00', 'aktif'),
(29, '251062025', 40, 'FLORENS SANTA AGUSTIN .S', 'E', 1, 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(30, '251062026', 41, 'ARIZA MUHAIMIN .Z', 'E', 1, 'Statistik S1', '083841426400', 'uploads/profil/mhs_251062026_1765519581.png', '2025-12-12 03:55:00', 'aktif'),
(31, '241064001', 42, 'NATALIA ALBERGATI NIPU', 'E', 1, 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(32, '241064002', 43, 'MAGDALENA B. S. SOBANG', 'E', 1, 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(33, '241064004', 44, 'ERA AMALIA PUTRI', 'E', 1, 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(34, '241064007', 45, 'ROSWITA ASMELITA NESTI .P', 'E', 1, 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(35, '241064008', 46, 'SANRY FRIDOLING OKI NAAT', 'E', 1, 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(36, '241064009', 47, 'FREDERICK HARDIMAN', 'E', 1, 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(37, '241064013', 48, 'KEZIA GREDALYA SITANIA', 'E', 1, 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(38, '241064014', 49, 'SEPTI NURELISA', 'E', 1, 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(39, '241067010', 50, 'MIKAELA MAYANTRIS', 'E', 1, 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(40, '241067011', 51, 'MUHAMMAD KHOLIK KHOIRI', 'E', 1, 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(41, '241068005', 52, 'IKHSANUDDIN MUKHLISH', 'E', 1, 'Statistik S1', '0', 'uploads/profil/mhs_241068005_1765870189.jpg', '2025-12-12 03:55:00', 'aktif'),
(42, '241068006', 53, 'CORAZON RATU MARA', 'E', 1, 'Statistik S1', '0', 'uploads/profil/mhs_241068006_1769487195.jpg', '2025-12-12 03:55:00', 'aktif'),
(43, '242062001', 54, 'KAMELIA', 'E', 1, 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(44, '242062004', 55, 'DINA SITTONGA', 'E', 1, 'Statistik S1', '0', 'uploads/profil/mhs_242062004_1769483954.jpg', '2025-12-12 03:55:00', 'aktif'),
(45, '211063024', 56, 'OLIN PUTRA PRATAMA', 'E', 1, 'Statistik S1', '0', 'uploads/profil/mhs_211063024_1765769283.png', '2025-12-12 03:55:00', 'aktif'),
(46, '230607', 60, 'Nanda Hanif Abyan Bromo Putra', 'A', 2, 'Pemrogaman', '083841426400', 'uploads/profil/mhs_230607_1767856437.png', '2025-12-15 03:32:00', 'aktif'),
(47, '24346554', 61, 'AAAA', 'E', 1, 'Statistik S1', '', NULL, '2025-12-15 07:01:00', 'aktif'),
(48, '765434567', 62, 'ccccc', 'E', 1, 'Statistik S1', '', NULL, '2025-12-15 07:15:00', 'aktif'),
(49, '9532753', 63, 'gggg', 'E', 1, 'Statistik S1', '', NULL, '2025-12-15 07:15:00', 'aktif'),
(50, '12345678', 64, 'Muhammad Iniesta Wildan Bromo Putra', 'A', 2, 'Pemrogaman', '083173784691', 'uploads/profil/mhs_12345678_1765910430.jpg', '2025-12-16 16:00:00', 'aktif'),
(51, '12072010', 65, 'Anik Yuliana', 'A', 1, 'Pemrogaman', '-', NULL, '2025-12-17 02:10:00', 'aktif'),
(52, '070771', 66, 'Muhammad Iniesta Wildan Bromo Putra', 'B', 1, 'Teknik Informatika', '083841426422', 'uploads/profil/mhs_070771_1766543709.jpg', '2025-12-19 05:38:00', 'aktif'),
(53, '11112222', 69, 'Massayu Sekar Anindita', 'B', 1, 'Stastatika', '085727662393', NULL, '2025-12-29 06:33:00', 'aktif'),
(55, '070772', 72, 'Simba', 'E', 1, 'Statistik S1', '', NULL, '2026-01-09 01:57:00', 'aktif'),
(56, '0000123', 74, 'Budi Purnama', 'E', 1, 'Statistik S1', '08126007900', 'uploads/profil/mhs_0000123_1769001059.webp', '2026-01-19 06:08:59', 'aktif'),
(58, '0000456', 76, 'Budi Purbaya', 'E', 1, 'Statistik S1', '', 'uploads/profil/mhs_0000456_1768885505.jpg', '2026-01-19 06:30:00', 'aktif'),
(63, '10167021', 82, 'Alexander Sucipto', 'E', 1, 'Teknik Informatika', '62212423341', 'uploads/profil/mhs_10167021_1769060212.png', '2026-01-19 07:51:00', 'aktif'),
(65, '12451731', 84, 'Alexander Kurdian', 'E', 1, 'Teknik Informatika', '62212436341', NULL, '2026-01-19 07:56:00', 'aktif'),
(66, '12455531', 85, 'Alexander Kurniawan', 'E', 1, 'Teknik Informatika', '62212423341', NULL, '2026-01-20 06:34:00', 'aktif'),
(67, '2025001', 86, 'Citra Permata Sari', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88419372260', 'uploads/profil/mhs_2025001_1770014175.jpg', '2026-02-02 02:33:00', 'aktif'),
(68, '2025002', 87, 'Fikri Alamsyah', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88305416705', NULL, '2026-02-02 02:33:00', 'aktif'),
(69, '2025003', 88, 'Siti Khadijah Nurhaliza', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88279369213', NULL, '2026-02-02 02:33:00', 'aktif'),
(70, '2025004', 89, 'Aldi Setiawan', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88506118244', NULL, '2026-02-02 02:33:00', 'aktif'),
(71, '2025005', 90, 'Rizky Ananda', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88490333181', NULL, '2026-02-02 02:33:00', 'aktif'),
(72, '2025006', 91, 'Indah Puspita Sari', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88782351000', NULL, '2026-02-02 02:33:00', 'aktif'),
(73, '2025007', 92, 'Dimas Arya Saputra', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88901767717', NULL, '2026-02-02 02:33:00', 'aktif'),
(74, '2025008', 93, 'Feby Lestari', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88128049688', NULL, '2026-02-02 02:33:00', 'aktif'),
(75, '2025009', 94, 'Nabila Zahra Aulia', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88744410203', NULL, '2026-02-02 02:33:00', 'aktif'),
(76, '2025010', 95, 'Bagas Dwi Kurniawan', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88696125873', NULL, '2026-02-02 02:33:00', 'aktif'),
(77, '2025011', 96, 'Muhammad Fajar Ramadhan', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88391406792', NULL, '2026-02-02 02:33:00', 'aktif'),
(78, '2025012', 97, 'Citra Permata Sari', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88234257703', NULL, '2026-02-02 02:33:00', 'aktif'),
(79, '2025013', 98, 'Bagas Dwi Kurniawan', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88567560833', NULL, '2026-02-02 02:33:00', 'aktif'),
(80, '2025014', 99, 'Siti Khadijah Nurhaliza', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88405391693', NULL, '2026-02-02 02:33:00', 'aktif'),
(81, '2025015', 100, 'Bayu Saputra', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88373783393', NULL, '2026-02-02 02:33:00', 'aktif'),
(82, '2025016', 101, 'Rizky Ananda', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88931400444', NULL, '2026-02-02 02:33:00', 'aktif'),
(83, '2025017', 102, 'Iqbal Maulana', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88765440179', NULL, '2026-02-02 02:33:00', 'aktif'),
(84, '2025018', 103, 'Muhammad Fajar Ramadhan', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88193422603', NULL, '2026-02-02 02:33:00', 'aktif'),
(85, '2025019', 104, 'Indah Puspita Sari', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88859664198', NULL, '2026-02-02 02:33:00', 'aktif'),
(86, '2025020', 105, 'Anisa Rahmawati', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88195027563', NULL, '2026-02-02 02:33:00', 'aktif'),
(87, '2025021', 106, 'Zahra Nurfadillah', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88772600392', NULL, '2026-02-02 02:33:00', 'aktif'),
(88, '2025022', 107, 'Aditya Putra Wibowo', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88952680929', NULL, '2026-02-02 02:33:00', 'aktif'),
(89, '2025023', 108, 'Tika Wulandari', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88859399057', NULL, '2026-02-02 02:33:00', 'aktif'),
(90, '2025024', 109, 'Aisyah Putri Ramadhani', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88870159783', NULL, '2026-02-02 02:33:00', 'aktif'),
(91, '2025025', 110, 'Raka Aditya Nugroho', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88735673916', NULL, '2026-02-02 02:33:00', 'aktif'),
(92, '2025026', 111, 'Ilham Maulana Hakim', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88394932558', NULL, '2026-02-02 02:33:00', 'aktif'),
(93, '2025027', 112, 'Anisa Rahmawati', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88478695042', NULL, '2026-02-02 02:33:00', 'aktif'),
(94, '2025028', 113, 'Bayu Saputra', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88614842480', NULL, '2026-02-02 02:33:00', 'aktif'),
(95, '2025029', 114, 'Yoga Prasetya', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88970292814', NULL, '2026-02-02 02:33:00', 'aktif'),
(96, '2025030', 115, 'Shelli Puspita', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88415347013', NULL, '2026-02-02 02:33:00', 'aktif'),
(97, '2025031', 116, 'Indah Puspita Sari', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88911806858', NULL, '2026-02-02 02:33:00', 'aktif'),
(98, '2025032', 117, 'Fitria Ananda', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88703165370', NULL, '2026-02-02 02:33:00', 'aktif'),
(99, '2025033', 118, 'Aldi Setiawan', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88460812335', NULL, '2026-02-02 02:33:00', 'aktif'),
(100, '2025034', 119, 'Agus Salim', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88691837660', NULL, '2026-02-02 02:33:00', 'aktif'),
(101, '2025035', 120, 'Iqbal Maulana', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88707073893', NULL, '2026-02-02 02:33:00', 'aktif'),
(102, '2025036', 121, 'Putri Ayu Lestari', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88568091863', NULL, '2026-02-02 02:33:00', 'aktif'),
(103, '2025037', 122, 'Shafila Azzarmah', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88653373755', NULL, '2026-02-02 02:33:00', 'aktif'),
(104, '2025038', 123, 'Putri Ayu Lestari', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88262843808', NULL, '2026-02-02 02:33:00', 'aktif'),
(105, '2025039', 124, 'Alya Safira Putri', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88494949608', NULL, '2026-02-02 02:33:00', 'aktif'),
(106, '2025040', 125, 'Bagas Dwi Kurniawan', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88792932490', NULL, '2026-02-02 02:33:00', 'aktif'),
(107, '2025041', 126, 'Fikri Alamsyah', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88785906988', NULL, '2026-02-02 02:33:00', 'aktif'),
(108, '2025042', 127, 'Arif Rahman Hakim', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88955086033', NULL, '2026-02-02 02:33:00', 'aktif'),
(109, '2025043', 128, 'Alya Safira Putri', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88148738842', NULL, '2026-02-02 02:33:00', 'aktif'),
(110, '2025044', 129, 'Muhammad Fajar Ramadhan', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88778558591', NULL, '2026-02-02 02:33:00', 'aktif'),
(111, '2025045', 130, 'Citra Permata Sari', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88745781616', NULL, '2026-02-02 02:33:00', 'aktif'),
(112, '2025046', 131, 'Ahmad Rizky Pratama', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88985722992', NULL, '2026-02-02 02:33:00', 'aktif'),
(113, '2025047', 132, 'Hanif Alvaro Putra', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88220274220', NULL, '2026-02-02 02:33:00', 'aktif'),
(114, '2025048', 133, 'Rani Oktavia', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88918521462', NULL, '2026-02-02 02:33:00', 'aktif'),
(115, '2025049', 134, 'Rani Oktavia', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88962472007', NULL, '2026-02-02 02:33:00', 'aktif'),
(116, '2025050', 135, 'Putri Ayu Lestari', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88439820801', NULL, '2026-02-02 02:33:00', 'aktif'),
(117, '2025051', 136, 'Shafirah Putri', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88168658424', NULL, '2026-02-02 02:33:00', 'aktif'),
(118, '2025052', 137, 'Raka Aditya Nugroho', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88866887728', NULL, '2026-02-02 02:33:00', 'aktif'),
(119, '2025053', 138, 'Muhammad Fajar Ramadhan', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88626400155', NULL, '2026-02-02 02:33:00', 'aktif'),
(120, '2025054', 139, 'Aldi Setiawan', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88208716996', NULL, '2026-02-02 02:33:00', 'aktif'),
(121, '2025055', 140, 'Aditya Putra Wibowo', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88234664417', NULL, '2026-02-02 02:33:00', 'aktif'),
(122, '2025056', 141, 'Yoga Prasetya', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88850270530', NULL, '2026-02-02 02:33:00', 'aktif'),
(123, '2025057', 142, 'Lia Oktaviani', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88587485190', NULL, '2026-02-02 02:33:00', 'aktif'),
(124, '2025058', 143, 'Fitria Ananda', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88658427635', NULL, '2026-02-02 02:33:00', 'aktif'),
(125, '2025059', 144, 'Intan Maharani', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88817352952', NULL, '2026-02-02 02:33:00', 'aktif'),
(126, '2025060', 145, 'Nabila Zahra Aulia', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88200383875', NULL, '2026-02-02 02:33:00', 'aktif'),
(127, '2025061', 146, 'Bagas Dwi Kurniawan', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88695217117', NULL, '2026-02-02 02:33:00', 'aktif'),
(128, '2025062', 147, 'Iqbal Maulana', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88394499338', NULL, '2026-02-02 02:33:00', 'aktif'),
(129, '2025063', 148, 'Yoga Prasetya', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88188603962', NULL, '2026-02-02 02:33:00', 'aktif'),
(130, '2025064', 149, 'Aditya Putra Wibowo', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88963947320', NULL, '2026-02-02 02:33:00', 'aktif'),
(131, '2025065', 150, 'Iqbal Maulana', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88766577166', NULL, '2026-02-02 02:33:00', 'aktif'),
(132, '2025066', 151, 'Intan Maharani', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88365986514', NULL, '2026-02-02 02:33:00', 'aktif'),
(133, '2025067', 152, 'Aditya Putra Wibowo', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88502330642', NULL, '2026-02-02 02:33:00', 'aktif'),
(134, '2025068', 153, 'Bagas Dwi Kurniawan', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88460045733', NULL, '2026-02-02 02:33:00', 'aktif'),
(135, '2025069', 154, 'Rani Oktavia', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88750112491', NULL, '2026-02-02 02:33:00', 'aktif'),
(136, '2025070', 155, 'Nabila Zahra Aulia', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88251488201', NULL, '2026-02-02 02:33:00', 'aktif'),
(137, '2025071', 156, 'Fitria Ananda', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88827400970', NULL, '2026-02-02 02:33:00', 'aktif'),
(138, '2025072', 157, 'Bayu Saputra', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88773770954', NULL, '2026-02-02 02:33:00', 'aktif'),
(139, '2025073', 158, 'Bagas Dwi Kurniawan', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88113077901', NULL, '2026-02-02 02:33:00', 'aktif'),
(140, '2025074', 159, 'Hanif Alvaro Putra', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88947441853', NULL, '2026-02-02 02:33:00', 'aktif'),
(141, '2025075', 160, 'Citra Permata Sari', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88655372101', NULL, '2026-02-02 02:33:00', 'aktif'),
(142, '2025076', 161, 'Dimas Arya Saputra', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88671320178', NULL, '2026-02-02 02:33:00', 'aktif'),
(143, '2025077', 162, 'Anisa Rahmawati', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88895987591', NULL, '2026-02-02 02:33:00', 'aktif'),
(144, '2025078', 163, 'Aditya Putra Wibowo', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88188389454', NULL, '2026-02-02 02:33:00', 'aktif'),
(145, '2025079', 164, 'Rendy Kurniawan', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88612239621', NULL, '2026-02-02 02:33:00', 'aktif'),
(146, '2025080', 165, 'Ahmad Rizky Pratama', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88349193391', NULL, '2026-02-02 02:33:00', 'aktif'),
(147, '2025081', 166, 'Ilham Maulana Hakim', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88318256131', NULL, '2026-02-02 02:33:00', 'aktif'),
(148, '2025082', 167, 'Tika Wulandari', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88474178878', NULL, '2026-02-02 02:33:00', 'aktif'),
(149, '2025083', 168, 'Yoga Prasetya', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88315083520', NULL, '2026-02-02 02:33:00', 'aktif'),
(150, '2025084', 169, 'Bayu Saputra', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88737948453', NULL, '2026-02-02 02:33:00', 'aktif'),
(151, '2025085', 170, 'Bagas Dwi Kurniawan', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88637661935', NULL, '2026-02-02 02:33:00', 'aktif'),
(152, '2025086', 171, 'Feby Lestari', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88260720680', NULL, '2026-02-02 02:33:00', 'aktif'),
(153, '2025087', 172, 'Aisyah Putri Ramadhani', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88887328577', NULL, '2026-02-02 02:33:00', 'aktif'),
(154, '2025088', 173, 'Nadya Amalia', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88416934057', NULL, '2026-02-02 02:33:00', 'aktif'),
(155, '2025089', 174, 'Rani Oktavia', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88138185129', NULL, '2026-02-02 02:33:00', 'aktif'),
(156, '2025090', 175, 'Rizal Akbar Maulana', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88260094035', NULL, '2026-02-02 02:33:00', 'aktif'),
(157, '2025091', 176, 'Ahmad Rizky Pratama', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88258043162', NULL, '2026-02-02 02:33:00', 'aktif'),
(158, '2025092', 177, 'Sakura Kinomoto', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88268572720', NULL, '2026-02-02 02:33:00', 'aktif'),
(159, '2025093', 178, 'Fitria Ananda', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88198063824', NULL, '2026-02-02 02:33:00', 'aktif'),
(160, '2025094', 179, 'Nabila Zahra Aulia', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88815533739', NULL, '2026-02-02 02:33:00', 'aktif'),
(161, '2025095', 180, 'Dewi Anggraini', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88217870755', NULL, '2026-02-02 02:33:00', 'aktif'),
(162, '2025096', 181, 'Arif Rahman Hakim', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88359847641', NULL, '2026-02-02 02:33:00', 'aktif'),
(163, '2025097', 182, 'Aisyah Putri Ramadhani', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88968802958', NULL, '2026-02-02 02:33:00', 'aktif'),
(164, '2025098', 183, 'Intan Maharani', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88126450143', NULL, '2026-02-02 02:33:00', 'aktif'),
(165, '2025099', 184, 'Indah Puspita Sari', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88770917754', NULL, '2026-02-02 02:33:00', 'aktif'),
(166, '2025100', 185, 'Salsa Billa Ramadhani', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88649620674', NULL, '2026-02-02 02:33:00', 'aktif'),
(167, '120341456', 188, 'Reifan Ahmad Muhyidin', 'A', 1, 'Pemrogaman', '085865895255', NULL, '2026-02-23 03:04:00', 'aktif');

-- --------------------------------------------------------

--
-- Struktur dari tabel `mata_kuliah`
--

CREATE TABLE `mata_kuliah` (
  `kode_mk` varchar(10) NOT NULL,
  `nama_mk` varchar(100) NOT NULL,
  `program_studi` varchar(100) DEFAULT NULL,
  `sks` int(11) DEFAULT 3,
  `semester` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mata_kuliah`
--

INSERT INTO `mata_kuliah` (`kode_mk`, `nama_mk`, `program_studi`, `sks`, `semester`) VALUES
('MK001', 'Basis Data', NULL, 3, 'Ganjil'),
('MK002', 'Pemrograman', NULL, 3, 'Ganjil'),
('MK003', 'Jaringan', 'Sistem Informasi', 3, 'Ganjil'),
('MK004', 'Statistika', NULL, 3, 'Ganjil'),
('MK006', 'Rekayasa Perangkat Lunak', NULL, 3, 'Ganjil'),
('STP2503', 'Basis Data S1', NULL, 4, 'Ganjil'),
('TI', 'Algoritma dan Pemrograman', 'Teknik Informatika', 3, 'Ganjil');

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

-- --------------------------------------------------------

--
-- Struktur dari tabel `presensi_mahasiswa`
--

CREATE TABLE `presensi_mahasiswa` (
  `id` int(11) NOT NULL,
  `jadwal_id` int(11) NOT NULL,
  `nim` varchar(15) NOT NULL,
  `status` enum('hadir','izin','sakit','alpha','belum') DEFAULT 'belum',
  `keterangan` text DEFAULT NULL,
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

INSERT INTO `presensi_mahasiswa` (`id`, `jadwal_id`, `nim`, `status`, `keterangan`, `waktu_presensi`, `metode`, `validated_by`, `location_lab`, `device_id`, `ip_address`, `verified_by_system`) VALUES
(3276, 1180, '2025001', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3277, 1180, '2025002', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3278, 1180, '2025003', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3279, 1180, '2025005', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3280, 1180, '2025007', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3281, 1180, '2025009', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3282, 1180, '2025011', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3283, 1180, '2025013', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3284, 1180, '2025015', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3285, 1180, '2025017', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3286, 1180, '2025019', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3287, 1180, '2025021', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3288, 1180, '2025022', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3289, 1180, '2025023', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3290, 1180, '2025025', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3291, 1180, '2025027', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3292, 1180, '2025029', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3293, 1180, '2025031', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3294, 1180, '2025033', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3295, 1180, '2025035', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3296, 1180, '2025037', 'hadir', NULL, '2026-03-30 02:40:15', 'manual', '23108012', NULL, NULL, NULL, 0),
(3297, 1180, '2025039', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3298, 1180, '2025043', 'hadir', NULL, '2026-03-30 02:47:33', 'qr', NULL, 'Laboratorium Jaringan', 'dev_mncl8aj0gg45p8fx80h', '192.168.2.102', 1),
(3299, 1180, '2025045', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3300, 1180, '2025047', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3301, 1180, '2025049', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3302, 1180, '2025051', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3303, 1180, '2025053', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3304, 1180, '2025057', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3305, 1180, '2025059', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3306, 1180, '2025061', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3307, 1180, '2025063', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3308, 1180, '2025065', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3309, 1180, '2025067', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3310, 1180, '2025069', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3311, 1180, '2025071', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3312, 1180, '2025073', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3313, 1180, '2025075', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3314, 1180, '2025077', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3315, 1180, '2025079', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3316, 1180, '2025081', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3317, 1180, '2025083', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3318, 1180, '2025085', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3319, 1180, '2025087', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3320, 1180, '2025089', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3321, 1180, '2025091', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3322, 1180, '2025093', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3323, 1180, '2025095', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3324, 1180, '2025097', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3325, 1180, '2025099', 'hadir', NULL, '2026-03-30 02:40:16', 'manual', '23108012', NULL, NULL, NULL, 0),
(3326, 1192, '2025004', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3327, 1192, '2025006', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3328, 1192, '2025008', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3329, 1192, '2025010', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3330, 1192, '2025012', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3331, 1192, '2025014', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3332, 1192, '2025016', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3333, 1192, '2025018', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3334, 1192, '2025020', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3335, 1192, '2025024', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3336, 1192, '2025026', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3337, 1192, '2025028', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3338, 1192, '2025030', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3339, 1192, '2025032', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3340, 1192, '2025034', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3341, 1192, '2025036', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3342, 1192, '2025038', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3343, 1192, '2025040', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3344, 1192, '2025041', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3345, 1192, '2025042', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3346, 1192, '2025044', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3347, 1192, '2025046', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3348, 1192, '2025048', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3349, 1192, '2025050', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3350, 1192, '2025052', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3351, 1192, '2025054', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3352, 1192, '2025055', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3353, 1192, '2025056', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3354, 1192, '2025058', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3355, 1192, '2025060', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3356, 1192, '2025062', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3357, 1192, '2025064', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3358, 1192, '2025066', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3359, 1192, '2025068', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3360, 1192, '2025070', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3361, 1192, '2025072', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3362, 1192, '2025074', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3363, 1192, '2025076', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3364, 1192, '2025078', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3365, 1192, '2025080', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3366, 1192, '2025082', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3367, 1192, '2025084', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3368, 1192, '2025086', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3369, 1192, '2025088', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3370, 1192, '2025090', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3371, 1192, '2025092', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3372, 1192, '2025094', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3373, 1192, '2025096', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3374, 1192, '2025098', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1),
(3375, 1192, '2025100', 'alpha', NULL, '2026-03-30 04:30:06', 'auto', NULL, NULL, NULL, NULL, 1);

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
(172, 1180, '9a5d61587d80ee6be420518a318c61e6_1774838415', '2026-03-30 10:10:00', '2026-03-30 02:40:16');

-- --------------------------------------------------------

--
-- Struktur dari tabel `soal_kuis`
--

CREATE TABLE `soal_kuis` (
  `id` int(11) NOT NULL,
  `kuis_id` int(11) NOT NULL,
  `pertanyaan` text NOT NULL,
  `opsi_a` text NOT NULL,
  `opsi_b` text NOT NULL,
  `opsi_c` text NOT NULL,
  `opsi_d` text NOT NULL,
  `kunci_jawaban` enum('A','B','C','D') NOT NULL,
  `gambar` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tiket_bantuan`
--

CREATE TABLE `tiket_bantuan` (
  `id` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `kategori` varchar(50) NOT NULL,
  `subjek` varchar(200) NOT NULL,
  `pesan` text NOT NULL,
  `lampiran` varchar(255) DEFAULT NULL,
  `status` enum('pending','proses','selesai','ditolak') DEFAULT 'pending',
  `tanggapan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tukar_jadwal_sementara`
--

CREATE TABLE `tukar_jadwal_sementara` (
  `id` int(11) NOT NULL,
  `nim_pengaju` varchar(15) NOT NULL,
  `jadwal_awal_id` int(11) NOT NULL,
  `nim_dituju` varchar(15) DEFAULT NULL,
  `jadwal_tujuan_id` int(11) NOT NULL,
  `alasan` text NOT NULL,
  `status` enum('menunggu_teman','menunggu_admin','disetujui','ditolak','dibatalkan') DEFAULT 'menunggu_teman',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(38, 'Hanif123', '$2y$10$Zgoeh1cedR/dfuM6mHF4ZOcCugxck/tJt5bltIVUpPWLJY5ZeyHwO', 'admin', '2025-12-12 03:52:26', NULL, NULL),
(39, '251062022', '$2y$10$lo/zzH98iq55E2owRH.Oru3haVmS7DgnrXcJBsw3V3Prlj9EFo2Ze', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(40, '251062025', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(41, '251062026', '$2y$10$V7JLm8y6NJ/n07Xqfz2BZ.E9RsrkGDFsKyzC4xnXaW3EpCVi3a/.O', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(42, '241064001', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(43, '241064002', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(44, '241064004', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(45, '241064007', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(46, '241064008', '$2y$10$wUb/eQLw0u0mnk/sesfwP.2H80n0LsI71zDjJB6EVT8fWTIr9seia', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(47, '241064009', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(48, '241064013', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(49, '241064014', '$2y$10$o9brA5LniAX/vRs3ZOUwLerWdT6cNQOZZicqOfEq3wK4seZxLLyhe', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(50, '241067010', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(51, '241067011', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(52, '241068005', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(53, '241068006', '$2y$10$B.rUobMRDlZoFpXXFrLCuuIEMpnvFJFGuFuwRSVHX3gM/JU8.NpyG', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(54, '242062001', '1234567', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(55, '242062004', '$2y$10$iEfnBQNCkg7C5/Z4kluzAemvPb8IvGEpdOO111W5cNkv2Fms1yNTi', 'mahasiswa', '2025-12-12 03:55:18', NULL, NULL),
(56, '211063024', '$2y$10$urY.IxB0T83vXltEqO4sju7KJBgXJEbTGudkwZAJsNA320visk2Ku', 'mahasiswa', '2025-12-12 03:55:19', NULL, NULL),
(57, '231064013', '$2y$10$6Viv7759evphGlbd/MUF/.Dg0FuUhtagsSMxPE9zg8x6ytOj3U/xW', 'asisten', '2025-12-12 04:24:41', NULL, NULL),
(58, '231064018', '$2y$10$jZtqUTrZZ8ChjVlOz1s39OABAEPEwPXzkuhyTpyUYoM5gCZI.HBwy', 'asisten', '2025-12-12 04:25:17', NULL, NULL),
(59, '23108012', '$2y$10$7.3eVX20PSp5grtZN4W2iORYW8yj4nYEI7OysKFIKSXH9yFI1DDqa', 'asisten', '2025-12-12 04:25:41', NULL, NULL),
(60, 'Hanif230607', '$2y$10$6DCUPA/w1RqGHnFHcAIhQe0F.StqZPHA7eEMuhaakHmXKaGO8EkOa', 'mahasiswa', '2025-12-15 03:32:47', NULL, NULL),
(61, '24346554', '$2y$10$F1.bV5KRPlRj7Dpx8v1e9u6N2v5e4K6N/9nhLdt5Zbu/k2OHM/8cm', 'mahasiswa', '2025-12-15 07:01:20', NULL, NULL),
(62, '765434567', '123456', 'mahasiswa', '2025-12-15 07:15:18', NULL, NULL),
(63, '9532753', '123456', 'mahasiswa', '2025-12-15 07:15:30', NULL, NULL),
(64, '12345678', '$2y$10$mBP4PK0drPux3ReoHLBqneG66RwuGAc07psKbFkj09CPRJojZ8Yt2', 'mahasiswa', '2025-12-16 16:01:14', NULL, NULL),
(65, '12072010', '$2y$10$CdPpmzBPKyZNCZtCHS2Wp.fFucesbMdcq9gC0IOIGAlADQtncYDmW', 'mahasiswa', '2025-12-17 02:10:31', NULL, NULL),
(66, '070771', '$2y$10$4GcBrdm7fm53oh2HYtf5Me5fIVY6S2kZu5JktUL5ED/6yTK9PWuJu', 'mahasiswa', '2025-12-19 05:39:22', NULL, NULL),
(68, '123456789', '$2y$10$fO9kcU3ZGpLo/eMGJ5gTZeYntRjHqeJ0u80zXYkVXFwPcZpIt2f9u', 'asisten', '2025-12-19 06:09:07', NULL, NULL),
(69, '11112222', '$2y$10$rVl2TpeIjgxWoz0xBHPDPeP9lM.n6.WPwgcsEEA1s39h1LrGO3rQa', 'mahasiswa', '2025-12-29 06:34:56', NULL, NULL),
(70, '12123434', '$2y$10$dTn6rA.k2S/bKf3iBIrQvujJY3NpQckY4YnSlHAo5ES0Cj65V0X1u', 'asisten', '2026-01-07 05:38:20', NULL, NULL),
(72, '070772', '$2y$10$5NL8MFw9hirl7EbzJo8V8Of/LWOz.4cA4oBYQM9i1kFSH8QuNOVwC', 'mahasiswa', '2026-01-09 01:57:26', NULL, NULL),
(74, 'Budi12', '$2y$10$PQAEjO8/UVdbIzpw/8f2Oe6FpBhsRzzBS/wFUjE0HXhierG258prG', 'mahasiswa', '2026-01-19 06:08:59', NULL, NULL),
(76, 'Purbaya123', '$2y$10$TkTRnI5/vlDfIb9IcEm96.CWENDAFYWgyyc0v5zUcntoTl963E1lu', 'mahasiswa', '2026-01-19 06:31:17', NULL, NULL),
(82, '10167021', '$2y$10$hB5.DECrOvy3y.sw1LLOBOHjiUwoTES0/in14xL4R8Oo3EgExzw6y', 'mahasiswa', '2026-01-19 07:51:28', NULL, NULL),
(84, '12451731', '$2y$10$PSNUMpXGf1A9tA2WSL5GBObifhBCMEoL4SxsazdYn729ZFKr3Natu', 'mahasiswa', '2026-01-19 07:57:05', NULL, NULL),
(85, 'Kurniawan123', '$2y$10$p8hMgb/ueIZTnYwO22xle.vgwuYm2Km7KFoIaH7jn//UL6kngzsRa', 'mahasiswa', '2026-01-20 06:34:24', NULL, NULL),
(86, 'user2025001', '$2y$10$u7YwTk8WBEQyyerYnwqgL./OlYQJNiN27naCRsXUYMTIWQ/OmnowK', 'mahasiswa', '2026-02-02 02:33:30', NULL, NULL),
(87, 'user2025002', '$2y$10$vLZv/Ordoy5zSs9yyNU4jOo8P5hsA/oaUgKfgo85/Kx7wLS2PiRPa', 'mahasiswa', '2026-02-02 02:33:31', NULL, NULL),
(88, 'user2025003', '$2y$10$qbmbL92EevjVFmpiLPGBvuf/c.sGHDnH6/dbCT9M8CZLg0dljpnDi', 'mahasiswa', '2026-02-02 02:33:31', NULL, NULL),
(89, 'user2025004', '$2y$10$FKFuCL8k9rtLxXdwJdhJbuCbTCt1i3p9CGj8Y5er1uYWDTT.otm.W', 'mahasiswa', '2026-02-02 02:33:31', NULL, NULL),
(90, 'user2025005', '$2y$10$/Eg/aWBY7FZc6pGEq7rHEO6lghdsi0cpRH..2ljnwe0.v4QkR7moK', 'mahasiswa', '2026-02-02 02:33:31', NULL, NULL),
(91, 'user2025006', '$2y$10$Lu9uL77N0z2tUa79SmGChepxON0PsXDd8bPm3iwmQv7eQT1bgG10m', 'mahasiswa', '2026-02-02 02:33:31', NULL, NULL),
(92, 'user2025007', '$2y$10$xD1kSUYG9hbePzxDTr/QAep8WJDdyyp0cjwewIXYlCq/0yHuzKz0O', 'mahasiswa', '2026-02-02 02:33:31', NULL, NULL),
(93, 'user2025008', '$2y$10$3OD3CDeOMECs3uH3.4ygKOFhU4nK73xFfNgASgHwpEJzN076AuOfK', 'mahasiswa', '2026-02-02 02:33:31', NULL, NULL),
(94, 'user2025009', '$2y$10$Sc18SjacanzR2mO1Y0KsC./ivv7I/TKPv6gjKKrgiNRdYNDOc3ZbG', 'mahasiswa', '2026-02-02 02:33:31', NULL, NULL),
(95, 'user2025010', '$2y$10$UGcuVZRSvH749C4TVc.u9eKAbOzz61PMEBgdE2aseiWRKdoSHy8ye', 'mahasiswa', '2026-02-02 02:33:31', NULL, NULL),
(96, 'user2025011', '$2y$10$WbMvgOo4XKXr1lUS4sJjGunQD48Frk3CGqwKd04VJ1xpGLrvOFNGW', 'mahasiswa', '2026-02-02 02:33:31', NULL, NULL),
(97, 'user2025012', '$2y$10$aKLtiG11qH3THDppezLtI.Xq1npBfJ/CG1L7gtRCcPyGtEZy76/ky', 'mahasiswa', '2026-02-02 02:33:32', NULL, NULL),
(98, 'user2025013', '$2y$10$57bt/dfhDS.SrNwbyzL70OQSLOYD.zuGEGbEv35tMPoBZuRHqGZge', 'mahasiswa', '2026-02-02 02:33:32', NULL, NULL),
(99, 'user2025014', '$2y$10$pvRmVGWv6CN1dL6J/PygpeleGkEJYi0kP/vivH/h8g7Bxy3.aBeEO', 'mahasiswa', '2026-02-02 02:33:32', NULL, NULL),
(100, 'user2025015', '$2y$10$WPFiagIgpy6AnAea8cC37erkTE7BG8QVra4AajZsUMDO19XkPZXMO', 'mahasiswa', '2026-02-02 02:33:32', NULL, NULL),
(101, 'user2025016', '$2y$10$00h3OCpCqJ0trLe1OPlO4eoeB0hwjYdihd5HMwXUXGZ8b1hJ.BhuC', 'mahasiswa', '2026-02-02 02:33:32', NULL, NULL),
(102, 'user2025017', '$2y$10$S5vGzasM62wGvlf0gNwnVOJWn.17mqaOuGSU358DqkzX1u94InWfe', 'mahasiswa', '2026-02-02 02:33:32', NULL, NULL),
(103, 'user2025018', '$2y$10$pSzlUg9THByC/MNJT7rMkOQUuhSY5X2fxyVYtKX/BZPt2F62pLos.', 'mahasiswa', '2026-02-02 02:33:32', NULL, NULL),
(104, 'user2025019', '$2y$10$eSBH7Na6jUwx8mWGfdeAoe0ynSCRwxOk5Cbb.Bp4NEAPW80RS9IFq', 'mahasiswa', '2026-02-02 02:33:32', NULL, NULL),
(105, 'user2025020', '$2y$10$WujmEeMvsMV4n/yLeXoqMuuQH/D5dDlpc4eSqbM6qheboVVzbxwQe', 'mahasiswa', '2026-02-02 02:33:32', NULL, NULL),
(106, 'user2025021', '$2y$10$33IJstJxhf.GPMHrbB2Iauwkn530Qgnk0T5B3mDnMEHnjV2yuomOK', 'mahasiswa', '2026-02-02 02:33:32', NULL, NULL),
(107, 'user2025022', '$2y$10$6Ua4NIIWt3P5KGtQIcXrUu/i4dMQDxDmklT51bqSp2I8dSbPQ85tC', 'mahasiswa', '2026-02-02 02:33:33', NULL, NULL),
(108, 'user2025023', '$2y$10$XcthWx/MuxAAxS3VXZkWyu3YXbYgSaGzkB4Bp1BJ3YmkVInVVaKb2', 'mahasiswa', '2026-02-02 02:33:33', NULL, NULL),
(109, 'user2025024', '$2y$10$DqgTPpo8h6eOt7lEJ51GGemtKE.8Fw3W7nyOpLky1wjK98i4xA6pC', 'mahasiswa', '2026-02-02 02:33:33', NULL, NULL),
(110, 'user2025025', '$2y$10$AezcaBA86xtDtzjVDOVFReArlAcg2mcmXvIXcSwWew62SpCmFe362', 'mahasiswa', '2026-02-02 02:33:33', NULL, NULL),
(111, 'user2025026', '$2y$10$gYA7m/WQJUUjX.nVme4wx.DkDxbFScUUPfty.qrTtxgWjHhnZgguS', 'mahasiswa', '2026-02-02 02:33:33', NULL, NULL),
(112, 'user2025027', '$2y$10$RZJvEMSLSw4MZ.6m./cxXue6O6BDIibVwnNymHk2Erbyz1z/LmlyO', 'mahasiswa', '2026-02-02 02:33:33', NULL, NULL),
(113, 'user2025028', '$2y$10$xYVM9YwSWlfUFv2eXHpBsebWg/45BjlZ86Jvu0LXrgefnT9X4mhJ2', 'mahasiswa', '2026-02-02 02:33:33', NULL, NULL),
(114, 'user2025029', '$2y$10$pgUuxEzZVGCqEoEBAvcMROQtlkS.ZGuAIdrrvLDFOGzevJAKoVLw6', 'mahasiswa', '2026-02-02 02:33:33', NULL, NULL),
(115, 'user2025030', '$2y$10$bMEUuqrHbV9JqIN/Xm33j.WaL4ybe6.kkzWEs8BICL5GSQE1owpdy', 'mahasiswa', '2026-02-02 02:33:33', NULL, NULL),
(116, 'user2025031', '$2y$10$PWtVCcxuc1OJsS0gT94JKuJpKa6fzahY3Kt.v3kxe09qzcsW2ua.G', 'mahasiswa', '2026-02-02 02:33:33', NULL, NULL),
(117, 'user2025032', '$2y$10$QvlZuqkJC/4buf918j.dLe/phaQ2Dxod794O7tzo3F8TPtNIZKZqK', 'mahasiswa', '2026-02-02 02:33:34', NULL, NULL),
(118, 'user2025033', '$2y$10$WGomPbATL6rzt7C4lEXksurpJ90LwAPDrnaQfAROj0QSPbF7HWgq6', 'mahasiswa', '2026-02-02 02:33:34', '79e0244c2023309ea1dda4ffbb5114839b21f2a6044fcd9036efee7dd4b2d417', '2026-03-04 13:50:58'),
(119, 'user2025034', '$2y$10$4K3ydfDIQzNv3cmq1.qCxOR8VdQgMgxD/QRnnaq4yGLZebxL8leeO', 'mahasiswa', '2026-02-02 02:33:34', NULL, NULL),
(120, 'user2025035', '$2y$10$Bp3C4eSuFCBbuDC5RsjoauSISPIUpstpyRXr17BG8OvPOktExez6C', 'mahasiswa', '2026-02-02 02:33:34', NULL, NULL),
(121, 'user2025036', '$2y$10$hXs9szS1vHZRbxKrM/xF4.gJhOX3bed1LTvMurSSmaIjVkWeSYREi', 'mahasiswa', '2026-02-02 02:33:34', NULL, NULL),
(122, 'user2025037', '$2y$10$ZE61qpQDdgoz3TmCuXksq.WCXEwTHRbfttqjQHcTE4r9SSKAI2lhm', 'mahasiswa', '2026-02-02 02:33:34', NULL, NULL),
(123, 'user2025038', '$2y$10$p2rwIELnFjs81Hk/JLA79O0kWh.t1Btw5bBiD3zpgNyOGk6eMNIFa', 'mahasiswa', '2026-02-02 02:33:34', NULL, NULL),
(124, 'user2025039', '$2y$10$WUERN3z/75RrG4yrZ1g12uO8ijSiDFguxt0lEwCvzJdnbfBjOy5Je', 'mahasiswa', '2026-02-02 02:33:34', NULL, NULL),
(125, 'user2025040', '$2y$10$qjQ5GQJsmBOZxINZhTAm1ehqJga1ZhVayNf9SvwB6bElBHg902US6', 'mahasiswa', '2026-02-02 02:33:34', NULL, NULL),
(126, 'user2025041', '$2y$10$ZK.KMG2VAhJ/jhQhELSds.MTSpJ8qnnz8MrZelmpFmRCNGy9qxJau', 'mahasiswa', '2026-02-02 02:33:34', NULL, NULL),
(127, 'user2025042', '$2y$10$9F0WZLNAaWEz/eP/vMufEeCUOoKEK6P8KnP9um3IdP0PbcdBrlfHW', 'mahasiswa', '2026-02-02 02:33:35', NULL, NULL),
(128, 'user2025043', '$2y$10$RzW91yoLY/ElXC7sP28M/OAHUIwDkfiPGrdI7Sszq9qloU8M8lBpW', 'mahasiswa', '2026-02-02 02:33:35', NULL, NULL),
(129, 'user2025044', '$2y$10$xgCnmC2jfQyWi1LDyUFs0eZG7fBh8akkah2PTAvpncBCYLsau3fba', 'mahasiswa', '2026-02-02 02:33:35', NULL, NULL),
(130, 'user2025045', '$2y$10$of.yaiCMn2JtoW5BNL58.eg/VR54AeJciiR5k7S8faJha9p5oDdSi', 'mahasiswa', '2026-02-02 02:33:35', NULL, NULL),
(131, 'user2025046', '$2y$10$2YS8loruIesE70wYBA3qk.INBP73nUOj5E00hXytMjx4N53L5qWgO', 'mahasiswa', '2026-02-02 02:33:35', NULL, NULL),
(132, 'user2025047', '$2y$10$0ohHhO//IFzMuSHTmKEAa.5C0Sa7Sw8lc1vutR.NhFKjMAK3rDrXy', 'mahasiswa', '2026-02-02 02:33:35', NULL, NULL),
(133, 'user2025048', '$2y$10$B.ccS9JwIWI66Y76vBGlhOHDhCM0F.GxzAUcb.jFPBno9yaQpRnEy', 'mahasiswa', '2026-02-02 02:33:35', NULL, NULL),
(134, 'user2025049', '$2y$10$quZhzwJjNz8EDN87Ky9coeU9qkbsm2Vu.J/p1uVxvAfx1UO96Udmy', 'mahasiswa', '2026-02-02 02:33:35', NULL, NULL),
(135, 'user2025050', '$2y$10$5hCvh7TL8CJK7yx33eWFc.bxKVORETeYzYeXWhjaxOPnrpR4wGSX.', 'mahasiswa', '2026-02-02 02:33:35', NULL, NULL),
(136, 'user2025051', '$2y$10$BDFLoV7tQVVXnsClUwj5zOH78Hk6bnigCfR3Bjk/JPPNa28kIqG/C', 'mahasiswa', '2026-02-02 02:33:35', NULL, NULL),
(137, 'user2025052', '$2y$10$lHVILTPGGK4scL9em2cYDe9/.ebc9QVWhS.u8xB84YTKMUAR/p4DG', 'mahasiswa', '2026-02-02 02:33:36', NULL, NULL),
(138, 'user2025053', '$2y$10$Xb21u2qSsVcVMvL0bYWCl.x0aTtBIgV4HWRUzHxdtvjpZxq.hl1kq', 'mahasiswa', '2026-02-02 02:33:36', NULL, NULL),
(139, 'user2025054', '$2y$10$eHNAMTS1OF.pKGRqa5383ue0SLYlFKwLD33i0ToekFrlkNeHdsutO', 'mahasiswa', '2026-02-02 02:33:36', NULL, NULL),
(140, 'user2025055', '$2y$10$A2gZrsWZzfNwy3RM6ffNzOq7oUmbl2IABe6DdaYbJOcWRJC.lpnfC', 'mahasiswa', '2026-02-02 02:33:36', NULL, NULL),
(141, 'user2025056', '$2y$10$AR/2cHTg0xVZlZuhWaLVA.LJx9u9bCazQWHexydB.BHXGTYSJY6Mq', 'mahasiswa', '2026-02-02 02:33:36', NULL, NULL),
(142, 'user2025057', '$2y$10$fBaM/AsrUomXcFHj/11bsOSxo3lVJSjoiRWo5DEwFJcogXZ0Cqt6.', 'mahasiswa', '2026-02-02 02:33:37', NULL, NULL),
(143, 'user2025058', '$2y$10$4sLKPGdx6.G1nqQMoxu8Wuhj6rz8UYWfVA53xIYKRfVO4zrayD5Wu', 'mahasiswa', '2026-02-02 02:33:37', NULL, NULL),
(144, 'user2025059', '$2y$10$n/1ouhXR8YMvY9EQG3D3fe5V8sySuffSD2bdFdlrHUR79jxTa7MHm', 'mahasiswa', '2026-02-02 02:33:37', NULL, NULL),
(145, 'user2025060', '$2y$10$NbRGeDrODwmED3HhXVPvQeWolfhk3MxS2nRomKIDEXi4hbh2rIVam', 'mahasiswa', '2026-02-02 02:33:37', NULL, NULL),
(146, 'user2025061', '$2y$10$xZioYoMyz3JitGjduT0Q.uyCF8EHdgftbt0FX8lSJZrP8QsWI/SRS', 'mahasiswa', '2026-02-02 02:33:37', NULL, NULL),
(147, 'user2025062', '$2y$10$36MNUT.uKvf0zHZlK1j3RuOj.nVUzAjbZmREg955GmTPtIKB1yjWq', 'mahasiswa', '2026-02-02 02:33:37', NULL, NULL),
(148, 'user2025063', '$2y$10$2VCWz9n.QarxHHMVOWuQAe4SesQCV2klqSKVQvZxbZN6jIZ/Nbdxe', 'mahasiswa', '2026-02-02 02:33:37', NULL, NULL),
(149, 'user2025064', '$2y$10$suqkVBsIgtd6Q/P02uypa.rT6uFqW9tVBO5TXbmvpi10ZU3nHokum', 'mahasiswa', '2026-02-02 02:33:37', NULL, NULL),
(150, 'user2025065', '$2y$10$mZtAnUrsAkwByvb/mt4PHuLz3bhSMQky4H1iO60wde5ddozdgpHvG', 'mahasiswa', '2026-02-02 02:33:37', NULL, NULL),
(151, 'user2025066', '$2y$10$l1rk2k2nvOS/QIrSBXspfOx65I.AsPZR1AhV69OQ4hRtCka9Bub1.', 'mahasiswa', '2026-02-02 02:33:37', NULL, NULL),
(152, 'user2025067', '$2y$10$nXtNX18eiV85/dZPP8taC.14Mj95TpZHOXlfw6B4Q1WNg.wBxezIG', 'mahasiswa', '2026-02-02 02:33:37', NULL, NULL),
(153, 'user2025068', '$2y$10$snl9PIXuyy29RriE060W0OUCrieYXDM/MVLJTAbobCkLq/FK/JBd2', 'mahasiswa', '2026-02-02 02:33:38', NULL, NULL),
(154, 'user2025069', '$2y$10$KcUrRrb2Co70isiRufyHseF6W0SWvOK5Ulq707BCJq9hKIgRte.d.', 'mahasiswa', '2026-02-02 02:33:38', NULL, NULL),
(155, 'user2025070', '$2y$10$BFyzuYbejYqGxcp298MYiuxkCRBq22RryexKphzBpeYR7Mwo8kZa6', 'mahasiswa', '2026-02-02 02:33:38', NULL, NULL),
(156, 'user2025071', '$2y$10$6eNPeael2V0ZArCMgx1F0uUQcyELaFqQVWYUfYH31OsDgxkXvukWy', 'mahasiswa', '2026-02-02 02:33:38', NULL, NULL),
(157, 'user2025072', '$2y$10$elQONHdGzQ2UEyLoqlNXxewiJumgNAyX0bvz660KXYu43iNnBSWiK', 'mahasiswa', '2026-02-02 02:33:38', NULL, NULL),
(158, 'user2025073', '$2y$10$ZdCj4svgdFOVaPPMAHa6J.sRW2WpsQWJ/I4hp0boqyh0PC/7zmt1C', 'mahasiswa', '2026-02-02 02:33:38', NULL, NULL),
(159, 'user2025074', '$2y$10$G0RiX1edo9qhY9sH.C4M1OrIyJAlt5mV4cMV4uEZ0DMdskRGpHvZq', 'mahasiswa', '2026-02-02 02:33:38', NULL, NULL),
(160, 'user2025075', '$2y$10$rXjscNqIGnbfIDQGhoyEL.6VCnszy3ljKeJ4LufGWjb/ZKFCOTYRy', 'mahasiswa', '2026-02-02 02:33:38', NULL, NULL),
(161, 'user2025076', '$2y$10$rhGmJfQDEhNxyshkVmGEc.v7ZDNZX50REoXJb8A.WhA4kQmUPYHSO', 'mahasiswa', '2026-02-02 02:33:38', NULL, NULL),
(162, 'user2025077', '$2y$10$X.p/15.G0smcGlicf6ItC.CfWZED0SJW1T2UyAy8.95TKEFmRl.O6', 'mahasiswa', '2026-02-02 02:33:38', NULL, NULL),
(163, 'user2025078', '$2y$10$9m2DIiQgg9Xk3XjeqY97IONP32Oe.RtlPewc/yEQXQ6/Prbaw62qS', 'mahasiswa', '2026-02-02 02:33:38', NULL, NULL),
(164, 'user2025079', '$2y$10$hTH/w/wRY28jlrRZFgn3vuG1M9lHS5U1mY7GI3Ki0X2EPl0UIwojK', 'mahasiswa', '2026-02-02 02:33:39', NULL, NULL),
(165, 'user2025080', '$2y$10$/TAT1fWUs9gD3EVB7B17B.yKtgXPjh8E4uhhIa.YcaHQ6z2u.GY72', 'mahasiswa', '2026-02-02 02:33:39', NULL, NULL),
(166, 'user2025081', '$2y$10$P5WEoVs80ac71DCHNrCW8.YSXz6hPCZKsWxDdWHTglkdHfRIU03ru', 'mahasiswa', '2026-02-02 02:33:39', NULL, NULL),
(167, 'user2025082', '$2y$10$YclXsB5xHMBkiQbhyFOoUuo78L/vAeNXKvKXH.ewq1AXlipwsIdwK', 'mahasiswa', '2026-02-02 02:33:39', NULL, NULL),
(168, 'user2025083', '$2y$10$oXL02e141OK/INBfHA/uTeqkrS.9naGpXh1v4op3yIde7on58frtu', 'mahasiswa', '2026-02-02 02:33:39', NULL, NULL),
(169, 'user2025084', '$2y$10$cJRPSzIi3sGw.2i70rtZU.cBiUmWu0HzNjGtuQ4J/YAkyfHJYiZUq', 'mahasiswa', '2026-02-02 02:33:39', NULL, NULL),
(170, 'user2025085', '$2y$10$xYUBA4yeC3LT8tZghT4ODuUYptv/woYlT.w0aXGZ.c6NtACxvFmOq', 'mahasiswa', '2026-02-02 02:33:39', NULL, NULL),
(171, 'user2025086', '$2y$10$14S3LWHSmklpsKmBzEcdC.QhnPGbShTTfeoa.0LnxWIbssgGBpQpy', 'mahasiswa', '2026-02-02 02:33:39', NULL, NULL),
(172, 'user2025087', '$2y$10$sQQlkkusSiS1vIORaW.i1.CBGoZtYcBe73l0Fdfcziov3U63OZRbS', 'mahasiswa', '2026-02-02 02:33:39', NULL, NULL),
(173, 'user2025088', '$2y$10$aS6zcB7TYn79yA4cXdI7mebxzfCUhAlXH3C3dvZnTbDKJZ0/w547C', 'mahasiswa', '2026-02-02 02:33:39', NULL, NULL),
(174, 'user2025089', '$2y$10$cTxPPO7eOfCOWbVhB4tnVOdqw19D9VvSC2LP.vGlvnHh3ir5Otsga', 'mahasiswa', '2026-02-02 02:33:40', NULL, NULL),
(175, 'user2025090', '$2y$10$9LjrsHBBeC7bnEswWcbUKO8TJGCF.hWUh6ZmfE0pOB8iR.tnMs0ma', 'mahasiswa', '2026-02-02 02:33:40', NULL, NULL),
(176, 'user2025091', '$2y$10$PWgRHXtcTfAg7EZ6h/EuR.uqxwiQFfg4YB8KiyZ.FYSEyG4O0X31S', 'mahasiswa', '2026-02-02 02:33:40', NULL, NULL),
(177, 'user2025092', '$2y$10$BfgvbKAz0w7WcMSJT6QQ7u.pVb4KkI2Ddu8ry4IEgtltdp.3gs8vq', 'mahasiswa', '2026-02-02 02:33:40', NULL, NULL),
(178, 'user2025093', '$2y$10$H7KtRlfQOMFbAh.bxZ3pIupXpVOVnUZ3lrccMAmZpemQTtDgc0aTm', 'mahasiswa', '2026-02-02 02:33:40', NULL, NULL),
(179, 'user2025094', '$2y$10$BPQIhbQoKzu2136lqdBehuzuXabXVyPVCRJlV.KSP5pOen3hFqIri', 'mahasiswa', '2026-02-02 02:33:40', NULL, NULL),
(180, 'user2025095', '$2y$10$u9zuFysHJ3f.SgVs42dEreko47bj8pd3m3oVgsPyAdyKvRUCYciei', 'mahasiswa', '2026-02-02 02:33:40', NULL, NULL),
(181, 'user2025096', '$2y$10$HOr555on0gu9F4ISPkB8G.LQNUGKu1loXAqvDncFtDrx./exiLvAm', 'mahasiswa', '2026-02-02 02:33:40', NULL, NULL),
(182, 'user2025097', '$2y$10$T1cxqv1P9iEBY3KPOvpWSOZEYPgH/UBFbAr1jwa5.G6Qpc.kzHj3C', 'mahasiswa', '2026-02-02 02:33:40', NULL, NULL),
(183, 'user2025098', '$2y$10$ANj3XIjPur.IigfNz7I0EO9dBrLxjUNeCUM6KlVBnDD20JYZDjOd6', 'mahasiswa', '2026-02-02 02:33:40', NULL, NULL),
(184, 'user2025099', '$2y$10$q1jmAQvWc7lqZOGcO7zfZOLSIC0k3qwsRro1u/EpZWWn/JNca5jpG', 'mahasiswa', '2026-02-02 02:33:40', NULL, NULL),
(185, 'user2025100', '$2y$10$cnqLJZC38BnikUW1jYikY.FvRj/K3pbLw7W5FG2RgohyWYNaiLqGq', 'mahasiswa', '2026-02-02 02:33:41', NULL, NULL),
(186, '23108000', '$2y$10$8jnMei7d582zrvyR3BgcaeAOio8K9ym85tX2N0n81B4oBwzB1eiOe', 'asisten', '2026-02-02 02:40:48', NULL, NULL),
(187, '23108001', '$2y$10$D9VR/3BAO0lusLRrjuwbpuS6qpmTTR7Ki2ZxxVrx251K57Fqk9xl6', 'asisten', '2026-02-02 02:41:19', NULL, NULL),
(188, 'Reifan123', '$2y$10$nI33h8EZHyLbVZ8y2zvIgeF/.FOGDabwuY.e6zG9pTKpvhyE2dVB.', 'mahasiswa', '2026-02-23 03:06:16', NULL, NULL);

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
-- Indeks untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indeks untuk tabel `asisten`
--
ALTER TABLE `asisten`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_asisten` (`kode_asisten`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `kode_mk` (`kode_mk`);

--
-- Indeks untuk tabel `berita_acara`
--
ALTER TABLE `berita_acara`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jadwal_id` (`jadwal_id`);

--
-- Indeks untuk tabel `detail_jawaban_kuis`
--
ALTER TABLE `detail_jawaban_kuis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hasil_kuis_id` (`hasil_kuis_id`),
  ADD KEY `soal_id` (`soal_id`);

--
-- Indeks untuk tabel `feedback_praktikum`
--
ALTER TABLE `feedback_praktikum`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `hasil_kuis`
--
ALTER TABLE `hasil_kuis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kuis_id` (`kuis_id`),
  ADD KEY `nim` (`nim`);

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
-- Indeks untuk tabel `jurnal_praktikum`
--
ALTER TABLE `jurnal_praktikum`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jadwal_id` (`jadwal_id`),
  ADD KEY `nim` (`nim`);

--
-- Indeks untuk tabel `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`kode_kelas`);

--
-- Indeks untuk tabel `kuis`
--
ALTER TABLE `kuis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jadwal_id` (`jadwal_id`);

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
-- Indeks untuk tabel `soal_kuis`
--
ALTER TABLE `soal_kuis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kuis_id` (`kuis_id`);

--
-- Indeks untuk tabel `tiket_bantuan`
--
ALTER TABLE `tiket_bantuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `nim` (`nim`);

--
-- Indeks untuk tabel `tukar_jadwal_sementara`
--
ALTER TABLE `tukar_jadwal_sementara`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=104;

--
-- AUTO_INCREMENT untuk tabel `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `asisten`
--
ALTER TABLE `asisten`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `berita_acara`
--
ALTER TABLE `berita_acara`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `detail_jawaban_kuis`
--
ALTER TABLE `detail_jawaban_kuis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `feedback_praktikum`
--
ALTER TABLE `feedback_praktikum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `hasil_kuis`
--
ALTER TABLE `hasil_kuis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1204;

--
-- AUTO_INCREMENT untuk tabel `jurnal_praktikum`
--
ALTER TABLE `jurnal_praktikum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `kuis`
--
ALTER TABLE `kuis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `lab`
--
ALTER TABLE `lab`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `log_presensi`
--
ALTER TABLE `log_presensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1410;

--
-- AUTO_INCREMENT untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT untuk tabel `materi_perkuliahan`
--
ALTER TABLE `materi_perkuliahan`
  MODIFY `id_materi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `penggantian_inhall`
--
ALTER TABLE `penggantian_inhall`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `presensi_mahasiswa`
--
ALTER TABLE `presensi_mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3376;

--
-- AUTO_INCREMENT untuk tabel `qr_code_session`
--
ALTER TABLE `qr_code_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT untuk tabel `soal_kuis`
--
ALTER TABLE `soal_kuis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT untuk tabel `tiket_bantuan`
--
ALTER TABLE `tiket_bantuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `tukar_jadwal_sementara`
--
ALTER TABLE `tukar_jadwal_sementara`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=189;

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
-- Ketidakleluasaan untuk tabel `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `asisten`
--
ALTER TABLE `asisten`
  ADD CONSTRAINT `asisten_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asisten_ibfk_2` FOREIGN KEY (`kode_mk`) REFERENCES `mata_kuliah` (`kode_mk`);

--
-- Ketidakleluasaan untuk tabel `berita_acara`
--
ALTER TABLE `berita_acara`
  ADD CONSTRAINT `berita_acara_ibfk_1` FOREIGN KEY (`jadwal_id`) REFERENCES `jadwal` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `detail_jawaban_kuis`
--
ALTER TABLE `detail_jawaban_kuis`
  ADD CONSTRAINT `fk_detail_hasil` FOREIGN KEY (`hasil_kuis_id`) REFERENCES `hasil_kuis` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detail_soal` FOREIGN KEY (`soal_id`) REFERENCES `soal_kuis` (`id`) ON DELETE CASCADE;

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
