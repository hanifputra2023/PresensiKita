-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 25 Feb 2026 pada 08.30
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
(86, 1024, '231064013', 'hadir', '21:59:25', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(87, 1046, '23108001', 'hadir', '10:09:19', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(88, 1036, '231064013', 'hadir', '13:15:40', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(89, 1026, '231064013', 'hadir', '13:25:56', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(90, 1047, '23108000', 'hadir', '09:07:24', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(91, 1058, '23108001', 'hadir', '10:56:49', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(92, 1037, '231064013', 'hadir', '19:19:46', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(93, 1027, '231064013', 'hadir', '21:31:26', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(94, 1049, '23108000', 'hadir', '15:11:04', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(99, 1116, '231064018', 'hadir', '14:20:54', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(101, 1117, '231064018', 'hadir', '09:21:31', NULL, NULL, NULL, 'pending', NULL, NULL, NULL);

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
('app_name', 'PresensiKita', 'Nama Aplikasi'),
('contact_wa', '6285727662393', 'Nomor WhatsApp Admin'),
('instansi_name', 'Universitas AKPRIND', 'Nama Instansi'),
('maintenance_mode', '0', 'Mode Maintenance (1=Ya, 0=Tidak)'),
('semester_aktif', 'Ganjil', 'Semester Aktif'),
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

--
-- Dumping data untuk tabel `detail_jawaban_kuis`
--

INSERT INTO `detail_jawaban_kuis` (`id`, `hasil_kuis_id`, `soal_id`, `jawaban_mahasiswa`, `is_benar`) VALUES
(11, 12, 42, 'A', 0),
(12, 12, 43, 'C', 0),
(13, 12, 54, 'B', 1),
(14, 12, 55, 'B', 1),
(15, 12, 56, 'C', 1),
(16, 12, 57, 'B', 1),
(17, 12, 58, 'B', 0),
(18, 12, 59, 'C', 1),
(19, 12, 60, 'B', 1),
(20, 12, 61, 'B', 1),
(21, 12, 62, 'D', 1),
(22, 12, 63, 'D', 0),
(23, 12, 64, 'B', 0),
(24, 12, 65, 'B', 1),
(25, 12, 66, 'B', 1);

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

--
-- Dumping data untuk tabel `hasil_kuis`
--

INSERT INTO `hasil_kuis` (`id`, `kuis_id`, `nim`, `nilai`, `benar`, `salah`, `waktu_mulai`, `waktu_selesai`) VALUES
(12, 3, '12345678', 10, 10, 5, '2026-02-06 09:51:55', '2026-02-06 10:11:11');

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
(1024, 1, '2026-01-29', '16:26:00', '23:00:00', 'LAB001', 'A', 1, 'MK002', 'Pertemuan 1 - Pengenalan', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1025, 2, '2026-02-05', '16:26:00', '23:00:00', 'LAB002', 'A', 1, 'MK002', 'Pertemuan 2 - Dasar', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1026, 3, '2026-02-12', '16:26:00', '23:00:00', 'LAB001', 'A', 1, 'MK002', 'Pertemuan 3 - Lanjutan I', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1027, 4, '2026-02-19', '16:26:00', '23:00:00', 'LAB002', 'A', 1, 'MK002', 'Pertemuan 4 - Lanjutan II', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1028, 5, '2026-02-26', '16:26:00', '23:00:00', 'LAB001', 'A', 1, 'MK002', 'Pertemuan 5 - Praktik I', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1029, 6, '2026-03-05', '16:26:00', '23:00:00', 'LAB002', 'A', 1, 'MK002', 'Pertemuan 6 - Praktik II', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1030, 7, '2026-03-12', '16:26:00', '23:00:00', 'LAB001', 'A', 1, 'MK002', 'Pertemuan 7 - Praktik III', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1031, 8, '2026-03-19', '16:26:00', '23:00:00', 'LAB002', 'A', 1, 'MK002', 'Pertemuan 8 - Review', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1032, 9, '2026-03-26', '16:26:00', '23:00:00', 'LAB001', 'A', 1, 'MK002', 'Praresponsi', '231064013', NULL, 'praresponsi', NULL, '2026-01-29 09:26:59'),
(1033, 9, '2026-03-26', '23:00:00', '05:34:00', 'LAB001', 'A', 1, 'MK002', 'Inhall', '231064013', NULL, 'inhall', NULL, '2026-01-29 09:26:59'),
(1034, 10, '2026-04-02', '16:26:00', '23:00:00', 'LAB002', 'A', 1, 'MK002', 'Responsi', '231064013', NULL, 'responsi', NULL, '2026-01-29 09:26:59'),
(1035, 1, '2026-01-30', '16:26:00', '23:00:00', 'LAB001', 'A', 2, 'MK002', 'Pertemuan 1 - Pengenalan', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1036, 2, '2026-02-06', '09:00:00', '23:00:00', 'LAB002', 'A', 2, 'MK002', 'Pertemuan 2 - Dasar', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1037, 3, '2026-02-13', '16:26:00', '23:00:00', 'LAB001', 'A', 2, 'MK002', 'Pertemuan 3 - Lanjutan I', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1038, 4, '2026-02-20', '16:26:00', '23:00:00', 'LAB002', 'A', 2, 'MK002', 'Pertemuan 4 - Lanjutan II', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1039, 5, '2026-02-27', '16:26:00', '23:00:00', 'LAB001', 'A', 2, 'MK002', 'Pertemuan 5 - Praktik I', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1040, 6, '2026-03-06', '16:26:00', '23:00:00', 'LAB002', 'A', 2, 'MK002', 'Pertemuan 6 - Praktik II', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1041, 7, '2026-03-13', '16:26:00', '23:00:00', 'LAB001', 'A', 2, 'MK002', 'Pertemuan 7 - Praktik III', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1042, 8, '2026-03-20', '16:26:00', '23:00:00', 'LAB002', 'A', 2, 'MK002', 'Pertemuan 8 - Review', '231064013', NULL, 'materi', NULL, '2026-01-29 09:26:59'),
(1043, 9, '2026-03-27', '16:26:00', '23:00:00', 'LAB001', 'A', 2, 'MK002', 'Praresponsi', '231064013', NULL, 'praresponsi', NULL, '2026-01-29 09:26:59'),
(1044, 9, '2026-03-27', '23:00:00', '05:34:00', 'LAB001', 'A', 2, 'MK002', 'Inhall', '231064013', NULL, 'inhall', NULL, '2026-01-29 09:26:59'),
(1045, 10, '2026-04-03', '16:26:00', '23:00:00', 'LAB002', 'A', 2, 'MK002', 'Responsi', '231064013', NULL, 'responsi', NULL, '2026-01-29 09:26:59'),
(1046, 1, '2026-02-02', '08:00:00', '15:00:00', 'LAB003', 'F', 1, 'MK006', 'Pertemuan 1 - Pengenalan', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1047, 2, '2026-02-09', '08:00:00', '15:00:00', 'LAB004', 'F', 1, 'MK006', 'Pertemuan 2 - Dasar', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1048, 3, '2026-02-16', '08:00:00', '15:00:00', 'LAB003', 'F', 1, 'MK006', 'Pertemuan 3 - Lanjutan I', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1049, 4, '2026-02-23', '08:00:00', '23:00:00', 'LAB004', 'F', 1, 'MK006', 'Pertemuan 4 - Lanjutan II', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1050, 5, '2026-03-02', '08:00:00', '15:00:00', 'LAB003', 'F', 1, 'MK006', 'Pertemuan 5 - Praktik I', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1051, 6, '2026-03-09', '08:00:00', '15:00:00', 'LAB004', 'F', 1, 'MK006', 'Pertemuan 6 - Praktik II', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1052, 7, '2026-03-16', '08:00:00', '15:00:00', 'LAB003', 'F', 1, 'MK006', 'Pertemuan 7 - Praktik III', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1053, 8, '2026-03-23', '08:00:00', '15:00:00', 'LAB004', 'F', 1, 'MK006', 'Pertemuan 8 - Review', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1054, 9, '2026-03-30', '08:00:00', '15:00:00', 'LAB003', 'F', 1, 'MK006', 'Praresponsi', '23108001', '23108000', 'praresponsi', NULL, '2026-02-02 02:53:01'),
(1055, 9, '2026-03-30', '15:00:00', '22:00:00', 'LAB003', 'F', 1, 'MK006', 'Inhall', '23108001', '23108000', 'inhall', NULL, '2026-02-02 02:53:01'),
(1056, 10, '2026-04-06', '08:00:00', '15:00:00', 'LAB004', 'F', 1, 'MK006', 'Responsi', '23108001', '23108000', 'responsi', NULL, '2026-02-02 02:53:01'),
(1057, 1, '2026-02-03', '10:00:00', '15:00:00', 'LAB003', 'F', 2, 'MK006', 'Pertemuan 1 - Pengenalan', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1058, 2, '2026-02-10', '10:00:00', '15:00:00', 'LAB004', 'F', 2, 'MK006', 'Pertemuan 2 - Dasar', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1059, 3, '2026-02-17', '10:00:00', '15:00:00', 'LAB003', 'F', 2, 'MK006', 'Pertemuan 3 - Lanjutan I', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1060, 4, '2026-02-24', '10:00:00', '15:00:00', 'LAB004', 'F', 2, 'MK006', 'Pertemuan 4 - Lanjutan II', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1061, 5, '2026-03-03', '10:00:00', '15:00:00', 'LAB003', 'F', 2, 'MK006', 'Pertemuan 5 - Praktik I', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1062, 6, '2026-03-10', '10:00:00', '15:00:00', 'LAB004', 'F', 2, 'MK006', 'Pertemuan 6 - Praktik II', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1063, 7, '2026-03-17', '10:00:00', '15:00:00', 'LAB003', 'F', 2, 'MK006', 'Pertemuan 7 - Praktik III', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1064, 8, '2026-03-24', '10:00:00', '15:00:00', 'LAB004', 'F', 2, 'MK006', 'Pertemuan 8 - Review', '23108001', '23108000', 'materi', NULL, '2026-02-02 02:53:01'),
(1065, 9, '2026-03-31', '10:00:00', '15:00:00', 'LAB003', 'F', 2, 'MK006', 'Praresponsi', '23108001', '23108000', 'praresponsi', NULL, '2026-02-02 02:53:01'),
(1066, 9, '2026-03-31', '15:00:00', '20:00:00', 'LAB003', 'F', 2, 'MK006', 'Inhall', '23108001', '23108000', 'inhall', NULL, '2026-02-02 02:53:01'),
(1067, 10, '2026-04-07', '10:00:00', '15:00:00', 'LAB004', 'F', 2, 'MK006', 'Responsi', '23108001', '23108000', 'responsi', NULL, '2026-02-02 02:53:01'),
(1116, 1, '2026-02-24', '14:20:00', '23:20:00', 'LAB003', 'B', 0, 'MK003', 'Pertemuan 1 - Pengenalan', '231064018', NULL, 'materi', NULL, '2026-02-24 07:20:46'),
(1117, 2, '2026-02-25', '09:30:00', '23:45:00', 'LAB004', 'B', 0, 'MK004', 'Pertemuan 2 - Dasar', '231064018', NULL, 'materi', NULL, '2026-02-25 01:46:16');

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

--
-- Dumping data untuk tabel `kuis`
--

INSERT INTO `kuis` (`id`, `jadwal_id`, `judul`, `deskripsi`, `durasi_menit`, `metode_penilaian`, `bobot_per_soal`, `status`, `created_at`) VALUES
(3, 1036, 'Pemrograman Dasar Next Level', 'Asah Otak dalam kuis hari ini untuk meningkatkan fungsi berfikir logika', 30, 'poin_murni', 0, 'aktif', '2026-02-06 02:03:16');

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
(942, 64, 'LOGIN', 'users', 64, 'User login berhasil sebagai mahasiswa', '2026-01-29 08:33:22'),
(943, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1024, 'Presensi manual: 12072010 - hadir', '2026-01-29 14:59:25'),
(944, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1024, 'Presensi manual: 230607 - hadir', '2026-01-29 14:59:27'),
(945, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-02 01:22:15'),
(946, 57, 'LOGIN', 'users', 57, 'User login berhasil sebagai asisten', '2026-02-02 01:28:41'),
(947, 187, 'LOGIN', 'users', 187, 'User login berhasil sebagai asisten', '2026-02-02 02:53:31'),
(948, 187, 'GENERATE_QR', 'qr_code_session', 147, 'QR Code untuk jadwal #1046, expired: 2026-02-02 15:00:00', '2026-02-02 03:09:19'),
(949, 118, 'LOGIN', 'users', 118, 'User login berhasil sebagai mahasiswa', '2026-02-02 03:11:54'),
(950, NULL, 'PRESENSI_QR', 'presensi_mahasiswa', 1046, 'Mahasiswa 2025033 presensi via QR di Laboratorium Jaringan', '2026-02-02 03:12:33'),
(951, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025022 - hadir', '2026-02-02 03:13:49'),
(952, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025055 - hadir', '2026-02-02 03:13:50'),
(953, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025064 - hadir', '2026-02-02 03:13:51'),
(954, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025067 - hadir', '2026-02-02 03:13:51'),
(955, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025034 - hadir', '2026-02-02 03:13:52'),
(956, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025078 - hadir', '2026-02-02 03:13:53'),
(957, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025046 - hadir', '2026-02-02 03:13:54'),
(958, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025080 - hadir', '2026-02-02 03:13:55'),
(959, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025091 - hadir', '2026-02-02 03:13:55'),
(960, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025004 - hadir', '2026-02-02 03:13:57'),
(961, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025054 - hadir', '2026-02-02 03:14:00'),
(962, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025020 - hadir', '2026-02-02 03:14:01'),
(963, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025043 - hadir', '2026-02-02 03:14:03'),
(964, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025040 - hadir', '2026-02-02 03:14:04'),
(965, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025096 - hadir', '2026-02-02 03:14:07'),
(966, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025013 - hadir', '2026-02-02 03:14:09'),
(967, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025061 - hadir', '2026-02-02 03:14:16'),
(968, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025068 - hadir', '2026-02-02 03:14:17'),
(969, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025073 - hadir', '2026-02-02 03:14:19'),
(970, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025085 - hadir', '2026-02-02 03:14:20'),
(971, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025015 - hadir', '2026-02-02 03:14:22'),
(972, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025028 - hadir', '2026-02-02 03:14:23'),
(973, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025072 - hadir', '2026-02-02 03:14:25'),
(974, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025055 - hadir', '2026-02-02 03:20:39'),
(975, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025067 - hadir', '2026-02-02 03:20:40'),
(976, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025091 - hadir', '2026-02-02 03:20:42'),
(977, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025087 - hadir', '2026-02-02 03:20:43'),
(978, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025097 - hadir', '2026-02-02 03:20:44'),
(979, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025039 - hadir', '2026-02-02 03:20:45'),
(980, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025043 - hadir', '2026-02-02 03:20:45'),
(981, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025027 - hadir', '2026-02-02 03:20:46'),
(982, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025077 - hadir', '2026-02-02 03:20:46'),
(983, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025013 - hadir', '2026-02-02 03:20:47'),
(984, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025073 - hadir', '2026-02-02 03:20:48'),
(985, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025061 - hadir', '2026-02-02 03:20:48'),
(986, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025085 - hadir', '2026-02-02 03:20:49'),
(987, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025015 - hadir', '2026-02-02 03:20:50'),
(988, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025071 - hadir', '2026-02-02 03:20:51'),
(989, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025093 - hadir', '2026-02-02 03:20:53'),
(990, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025047 - hadir', '2026-02-02 03:20:55'),
(991, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025081 - hadir', '2026-02-02 03:20:57'),
(992, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025019 - hadir', '2026-02-02 03:20:58'),
(993, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025031 - hadir', '2026-02-02 03:20:59'),
(994, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025099 - hadir', '2026-02-02 03:21:01'),
(995, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025059 - hadir', '2026-02-02 03:21:02'),
(996, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025017 - hadir', '2026-02-02 03:21:03'),
(997, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025035 - hadir', '2026-02-02 03:21:05'),
(998, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025065 - hadir', '2026-02-02 03:21:07'),
(999, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025057 - hadir', '2026-02-02 03:21:10'),
(1000, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025011 - hadir', '2026-02-02 03:21:13'),
(1001, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025053 - hadir', '2026-02-02 03:21:15'),
(1002, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025009 - hadir', '2026-02-02 03:21:17'),
(1003, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025025 - hadir', '2026-02-02 03:21:19'),
(1004, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025049 - hadir', '2026-02-02 03:21:21'),
(1005, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025069 - hadir', '2026-02-02 03:21:23'),
(1006, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025089 - hadir', '2026-02-02 03:21:25'),
(1007, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025079 - hadir', '2026-02-02 03:21:28'),
(1008, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025005 - hadir', '2026-02-02 03:21:30'),
(1009, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025037 - hadir', '2026-02-02 03:21:32'),
(1010, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025051 - hadir', '2026-02-02 03:21:35'),
(1011, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025003 - hadir', '2026-02-02 03:21:36'),
(1012, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025023 - hadir', '2026-02-02 03:21:39'),
(1013, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025029 - hadir', '2026-02-02 03:21:40'),
(1014, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025063 - hadir', '2026-02-02 03:21:42'),
(1015, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025083 - hadir', '2026-02-02 03:21:44'),
(1016, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1046, 'Presensi manual: 2025021 - izin', '2026-02-02 03:21:46'),
(1017, 86, 'LOGIN', 'users', 86, 'User login berhasil sebagai mahasiswa', '2026-02-02 06:16:29'),
(1018, 76, 'LOGIN', 'users', 76, 'User login berhasil sebagai mahasiswa', '2026-02-02 07:10:42'),
(1019, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-02 07:11:12'),
(1020, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-03 01:17:06'),
(1021, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-05 02:50:35'),
(1022, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-02-05 03:18:32'),
(1023, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-02-05 03:22:39'),
(1024, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-05 03:32:37'),
(1025, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-02-05 03:33:57'),
(1026, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-05 07:38:08'),
(1027, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-02-05 07:38:29'),
(1028, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-05 19:02:00'),
(1029, 186, 'LOGIN', 'users', 186, 'User login berhasil sebagai asisten', '2026-02-05 19:02:24'),
(1030, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-06 01:39:01'),
(1031, 64, 'LOGIN', 'users', 64, 'User login berhasil sebagai mahasiswa', '2026-02-06 01:58:34'),
(1032, 57, 'LOGIN', 'users', 57, 'User login berhasil sebagai asisten', '2026-02-06 02:00:27'),
(1033, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-06 02:09:07'),
(1034, 64, 'KERJAKAN_KUIS', 'hasil_kuis', 25, 'Nilai: 10', '2026-02-06 03:11:11'),
(1035, 57, 'GENERATE_QR', 'qr_code_session', 148, 'QR Code untuk jadwal #1036, expired: 2026-02-06 23:00:00', '2026-02-06 06:15:40'),
(1036, NULL, 'PRESENSI_QR', 'presensi_mahasiswa', 1036, 'Mahasiswa 12345678 presensi via QR di Laboratorium Pemrograman', '2026-02-06 06:15:58'),
(1037, 57, 'GENERATE_QR', 'qr_code_session', 149, 'QR Code untuk jadwal #1026, expired: 2026-02-12 23:00:00', '2026-02-06 06:25:56'),
(1038, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-02-06 06:52:39'),
(1039, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-09 02:05:35'),
(1040, 186, 'LOGIN', 'users', 186, 'User login berhasil sebagai asisten', '2026-02-09 02:07:13'),
(1041, 186, 'GENERATE_QR', 'qr_code_session', 150, 'QR Code untuk jadwal #1047, expired: 2026-02-09 15:00:00', '2026-02-09 02:07:25'),
(1042, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025055 - hadir', '2026-02-09 02:08:19'),
(1043, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025067 - hadir', '2026-02-09 02:08:21'),
(1044, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025091 - hadir', '2026-02-09 02:08:22'),
(1045, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025087 - hadir', '2026-02-09 02:08:23'),
(1046, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025097 - hadir', '2026-02-09 02:08:24'),
(1047, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025033 - hadir', '2026-02-09 02:08:25'),
(1048, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025039 - hadir', '2026-02-09 02:08:26'),
(1049, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025043 - hadir', '2026-02-09 02:08:28'),
(1050, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025027 - hadir', '2026-02-09 02:08:29'),
(1051, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025077 - hadir', '2026-02-09 02:08:31'),
(1052, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025013 - hadir', '2026-02-09 02:08:32'),
(1053, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025061 - hadir', '2026-02-09 02:08:35'),
(1054, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025073 - hadir', '2026-02-09 02:08:38'),
(1055, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025085 - hadir', '2026-02-09 02:08:40'),
(1056, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025015 - hadir', '2026-02-09 02:08:42'),
(1057, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025001 - hadir', '2026-02-09 02:08:45'),
(1058, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025045 - hadir', '2026-02-09 02:09:28'),
(1059, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025075 - hadir', '2026-02-09 02:09:30'),
(1060, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025095 - hadir', '2026-02-09 02:09:52'),
(1061, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025007 - hadir', '2026-02-09 02:09:54'),
(1062, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025041 - hadir', '2026-02-09 02:09:56'),
(1063, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025071 - hadir', '2026-02-09 02:09:58'),
(1064, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025093 - hadir', '2026-02-09 02:10:02'),
(1065, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025047 - hadir', '2026-02-09 02:10:05'),
(1066, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025081 - hadir', '2026-02-09 02:10:49'),
(1067, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025019 - hadir', '2026-02-09 02:10:52'),
(1068, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025031 - hadir', '2026-02-09 02:10:55'),
(1069, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025099 - hadir', '2026-02-09 02:10:58'),
(1070, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025059 - hadir', '2026-02-09 02:11:01'),
(1071, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025017 - hadir', '2026-02-09 02:11:12'),
(1072, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025035 - hadir', '2026-02-09 02:11:15'),
(1073, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025065 - hadir', '2026-02-09 02:11:18'),
(1074, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025057 - hadir', '2026-02-09 02:11:23'),
(1075, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025011 - hadir', '2026-02-09 02:11:26'),
(1076, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025053 - hadir', '2026-02-09 02:11:30'),
(1077, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025009 - hadir', '2026-02-09 02:11:33'),
(1078, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025025 - hadir', '2026-02-09 02:11:36'),
(1079, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025049 - hadir', '2026-02-09 02:11:40'),
(1080, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025069 - hadir', '2026-02-09 02:11:43'),
(1081, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025089 - hadir', '2026-02-09 02:11:53'),
(1082, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025079 - hadir', '2026-02-09 02:11:58'),
(1083, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025005 - hadir', '2026-02-09 02:12:02'),
(1084, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025037 - hadir', '2026-02-09 02:12:06'),
(1085, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025051 - hadir', '2026-02-09 02:12:09'),
(1086, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025003 - hadir', '2026-02-09 02:12:11'),
(1087, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025023 - hadir', '2026-02-09 02:12:15'),
(1088, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025029 - hadir', '2026-02-09 02:12:18'),
(1089, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025063 - hadir', '2026-02-09 02:12:20'),
(1090, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025083 - hadir', '2026-02-09 02:12:25'),
(1091, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025021 - hadir', '2026-02-09 02:12:28'),
(1092, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-09 02:17:13'),
(1093, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-02-09 02:19:50'),
(1094, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025021 - izin', '2026-02-09 02:23:52'),
(1095, 106, 'LOGIN', 'users', 106, 'User login berhasil sebagai mahasiswa', '2026-02-09 02:24:23'),
(1096, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1047, 'Presensi manual: 2025083 - sakit', '2026-02-09 02:28:30'),
(1097, 168, 'LOGIN', 'users', 168, 'User login berhasil sebagai mahasiswa', '2026-02-09 02:29:09'),
(1098, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-10 02:09:04'),
(1099, 187, 'LOGIN', 'users', 187, 'User login berhasil sebagai asisten', '2026-02-10 03:56:22'),
(1100, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025022 - hadir', '2026-02-10 03:56:49'),
(1101, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025064 - hadir', '2026-02-10 03:56:50'),
(1102, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025078 - hadir', '2026-02-10 03:56:51'),
(1103, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025034 - hadir', '2026-02-10 03:56:52'),
(1104, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025046 - hadir', '2026-02-10 03:56:52'),
(1105, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025080 - hadir', '2026-02-10 03:56:53'),
(1106, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025024 - hadir', '2026-02-10 03:56:53'),
(1107, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025004 - hadir', '2026-02-10 03:56:54'),
(1108, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025054 - hadir', '2026-02-10 03:56:55'),
(1109, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025020 - hadir', '2026-02-10 03:56:55'),
(1110, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025042 - hadir', '2026-02-10 03:56:56'),
(1111, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025096 - hadir', '2026-02-10 03:56:57'),
(1112, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025010 - hadir', '2026-02-10 03:56:57'),
(1113, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025040 - hadir', '2026-02-10 03:56:59'),
(1114, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025068 - hadir', '2026-02-10 03:57:01'),
(1115, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025028 - hadir', '2026-02-10 03:57:03'),
(1116, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025072 - hadir', '2026-02-10 03:57:05'),
(1117, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025084 - hadir', '2026-02-10 03:57:08'),
(1118, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025012 - izin', '2026-02-10 03:57:11'),
(1119, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025076 - izin', '2026-02-10 03:57:13'),
(1120, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025008 - izin', '2026-02-10 03:57:15'),
(1121, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025086 - izin', '2026-02-10 03:57:18'),
(1122, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025002 - sakit', '2026-02-10 03:57:20'),
(1123, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025032 - sakit', '2026-02-10 03:57:22'),
(1124, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025058 - sakit', '2026-02-10 03:57:24'),
(1125, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025074 - sakit', '2026-02-10 03:57:27'),
(1126, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025026 - sakit', '2026-02-10 03:57:30'),
(1127, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025006 - alpha', '2026-02-10 03:57:32'),
(1128, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025066 - alpha', '2026-02-10 03:57:35'),
(1129, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025098 - hadir', '2026-02-10 03:57:37'),
(1130, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025062 - hadir', '2026-02-10 03:57:38'),
(1131, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025018 - hadir', '2026-02-10 03:57:40'),
(1132, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025044 - hadir', '2026-02-10 03:57:43'),
(1133, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025060 - hadir', '2026-02-10 03:57:45'),
(1134, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025070 - hadir', '2026-02-10 03:57:48'),
(1135, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025094 - hadir', '2026-02-10 03:57:51'),
(1136, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025088 - hadir', '2026-02-10 03:57:53'),
(1137, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025036 - hadir', '2026-02-10 03:57:55'),
(1138, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025038 - hadir', '2026-02-10 03:57:57'),
(1139, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025050 - hadir', '2026-02-10 03:57:59'),
(1140, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025052 - hadir', '2026-02-10 03:58:01'),
(1141, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025048 - hadir', '2026-02-10 03:58:05'),
(1142, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025090 - hadir', '2026-02-10 03:58:07'),
(1143, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025016 - hadir', '2026-02-10 03:58:09'),
(1144, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025092 - hadir', '2026-02-10 03:58:11'),
(1145, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025100 - hadir', '2026-02-10 03:58:13'),
(1146, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025030 - hadir', '2026-02-10 03:58:15'),
(1147, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025014 - hadir', '2026-02-10 03:58:17'),
(1148, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025082 - hadir', '2026-02-10 03:58:19'),
(1149, 187, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1058, 'Presensi manual: 2025056 - hadir', '2026-02-10 03:58:21'),
(1150, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-10 03:58:33'),
(1151, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-11 01:08:35'),
(1152, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-11 01:09:32'),
(1153, 38, 'UPDATE', 'users', 38, 'Admin mengupload foto profil', '2026-02-11 12:24:26'),
(1154, 38, 'UPDATE', 'admin', 1, 'Admin mengubah profil', '2026-02-11 12:56:56'),
(1155, 38, 'UPDATE', 'admin', 1, 'Admin mengupload foto profil', '2026-02-11 12:57:11'),
(1156, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-02-11 12:59:45'),
(1157, 38, 'UPDATE', 'admin', 1, 'Admin mengupload foto profil', '2026-02-12 02:29:53'),
(1158, 38, 'UPDATE', 'admin', 1, 'Admin mengupload foto profil', '2026-02-12 02:54:03'),
(1159, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-02-12 03:06:53'),
(1160, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-02-12 03:11:43'),
(1161, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-02-12 03:14:11'),
(1162, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-02-12 03:16:43'),
(1163, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-02-13 12:09:58'),
(1164, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-13 12:12:00'),
(1165, 57, 'LOGIN', 'users', 57, 'User login berhasil sebagai asisten', '2026-02-13 12:19:23'),
(1166, 57, 'GENERATE_QR', 'qr_code_session', 151, 'QR Code untuk jadwal #1037, expired: 2026-02-13 23:00:00', '2026-02-13 12:19:46'),
(1190, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-23 02:27:31'),
(1191, 38, 'BROADCAST_WA', 'system', 0, 'Mengirim broadcast ke kelas_tertentu (2 sukses)', '2026-02-23 02:52:19'),
(1192, 86, 'LOGIN', 'users', 86, 'User login berhasil sebagai mahasiswa', '2026-02-23 04:55:53'),
(1193, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-02-23 04:59:56'),
(1194, 64, 'LOGIN', 'users', 64, 'User login berhasil sebagai mahasiswa', '2026-02-23 05:01:08'),
(1195, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-02-23 07:22:04'),
(1196, 87, 'LOGIN', 'users', 87, 'User login berhasil sebagai mahasiswa', '2026-02-23 07:23:52'),
(1197, 64, 'LOGIN', 'users', 64, 'User login berhasil sebagai mahasiswa', '2026-02-23 07:30:56'),
(1198, 57, 'LOGIN', 'users', 57, 'User login berhasil sebagai asisten', '2026-02-23 07:41:49'),
(1199, 64, 'LOGIN', 'users', 64, 'User login berhasil sebagai mahasiswa', '2026-02-23 07:44:21'),
(1200, 87, 'LOGIN', 'users', 87, 'User login berhasil sebagai mahasiswa', '2026-02-23 08:06:32'),
(1201, 186, 'LOGIN', 'users', 186, 'User login berhasil sebagai asisten', '2026-02-23 08:07:10'),
(1202, 186, 'GENERATE_QR', 'qr_code_session', 154, 'QR Code untuk jadwal #1049, expired: 2026-02-23 23:00:00', '2026-02-23 08:11:04'),
(1203, NULL, 'PRESENSI_QR', 'presensi_mahasiswa', 1049, 'Mahasiswa 2025002 presensi via QR di Laboratorium Statistika', '2026-02-23 08:11:49'),
(1204, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025055 - hadir', '2026-02-23 08:12:18'),
(1205, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025067 - hadir', '2026-02-23 08:12:20'),
(1206, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025091 - hadir', '2026-02-23 08:12:20'),
(1207, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025087 - hadir', '2026-02-23 08:12:21'),
(1208, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025097 - hadir', '2026-02-23 08:12:21'),
(1209, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025033 - hadir', '2026-02-23 08:12:22'),
(1210, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025039 - hadir', '2026-02-23 08:12:22'),
(1211, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025043 - hadir', '2026-02-23 08:12:23'),
(1212, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025027 - hadir', '2026-02-23 08:12:24'),
(1213, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025077 - hadir', '2026-02-23 08:12:24'),
(1214, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025013 - hadir', '2026-02-23 08:12:25'),
(1215, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025061 - hadir', '2026-02-23 08:12:26'),
(1216, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025073 - hadir', '2026-02-23 08:12:26'),
(1217, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025085 - hadir', '2026-02-23 08:12:28'),
(1218, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025015 - hadir', '2026-02-23 08:12:31'),
(1219, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025001 - hadir', '2026-02-23 08:12:33'),
(1220, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025045 - hadir', '2026-02-23 08:12:35'),
(1221, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025075 - hadir', '2026-02-23 08:12:38'),
(1222, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025095 - hadir', '2026-02-23 08:12:39'),
(1223, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025007 - hadir', '2026-02-23 08:12:42'),
(1224, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025071 - hadir', '2026-02-23 08:12:44'),
(1225, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025093 - hadir', '2026-02-23 08:12:45'),
(1226, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025047 - hadir', '2026-02-23 08:12:48'),
(1227, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025081 - hadir', '2026-02-23 08:12:50'),
(1228, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025019 - hadir', '2026-02-23 08:12:52'),
(1229, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025031 - hadir', '2026-02-23 08:12:57'),
(1230, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025099 - hadir', '2026-02-23 08:12:59'),
(1231, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025059 - hadir', '2026-02-23 08:13:01'),
(1232, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025017 - hadir', '2026-02-23 08:13:03'),
(1233, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025035 - hadir', '2026-02-23 08:13:04'),
(1234, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025065 - hadir', '2026-02-23 08:13:06'),
(1235, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025057 - hadir', '2026-02-23 08:13:08'),
(1236, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025011 - hadir', '2026-02-23 08:13:10'),
(1237, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025053 - hadir', '2026-02-23 08:13:12'),
(1238, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025009 - hadir', '2026-02-23 08:13:14'),
(1239, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025025 - hadir', '2026-02-23 08:13:16'),
(1240, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025049 - hadir', '2026-02-23 08:13:18'),
(1241, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025069 - hadir', '2026-02-23 08:13:20'),
(1242, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025089 - hadir', '2026-02-23 08:13:21'),
(1243, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025079 - hadir', '2026-02-23 08:13:23'),
(1244, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025005 - hadir', '2026-02-23 08:13:26'),
(1245, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025037 - hadir', '2026-02-23 08:13:28'),
(1246, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025051 - hadir', '2026-02-23 08:13:29'),
(1247, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025003 - izin', '2026-02-23 08:13:32'),
(1248, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025023 - izin', '2026-02-23 08:13:34'),
(1249, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025029 - sakit', '2026-02-23 08:13:36'),
(1250, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025063 - sakit', '2026-02-23 08:13:39'),
(1251, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025083 - sakit', '2026-02-23 08:13:41'),
(1252, 186, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1049, 'Presensi manual: 2025021 - sakit', '2026-02-23 08:13:43'),
(1253, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-02-24 01:34:16'),
(1254, 38, 'BROADCAST_WA', 'system', 0, 'Mengirim broadcast ke kelas_tertentu (3 sukses)', '2026-02-24 01:40:35'),
(1255, 69, 'LOGIN', 'users', 69, 'User login berhasil sebagai mahasiswa', '2026-02-24 05:34:24'),
(1256, 58, 'LOGIN', 'users', 58, 'User login berhasil sebagai asisten', '2026-02-24 05:35:11'),
(1257, 58, 'GENERATE_QR', 'qr_code_session', 155, 'QR Code untuk jadwal #1114, expired: 2026-02-24 12:40:00', '2026-02-24 05:35:45'),
(1258, 58, 'GENERATE_QR', 'qr_code_session', 156, 'QR Code untuk jadwal #1114, expired: 2026-02-24 13:00:00', '2026-02-24 05:37:00'),
(1259, 58, 'GENERATE_QR', 'qr_code_session', 157, 'QR Code untuk jadwal #1114, expired: 2026-02-24 13:06:00', '2026-02-24 05:48:16'),
(1260, 58, 'GENERATE_QR', 'qr_code_session', 158, 'QR Code untuk jadwal #1114, expired: 2026-02-24 13:06:00', '2026-02-24 05:48:28'),
(1261, 58, 'GENERATE_QR', 'qr_code_session', 159, 'QR Code untuk jadwal #1114, expired: 2026-02-24 13:06:00', '2026-02-24 05:48:32'),
(1262, 58, 'GENERATE_QR', 'qr_code_session', 160, 'QR Code untuk jadwal #1114, expired: 2026-02-24 13:06:00', '2026-02-24 05:49:15'),
(1263, 58, 'GENERATE_QR', 'qr_code_session', 161, 'QR Code untuk jadwal #1114, expired: 2026-02-24 13:14:00', '2026-02-24 05:50:42'),
(1264, 58, 'GENERATE_QR', 'qr_code_session', 162, 'QR Code untuk jadwal #1114, expired: 2026-02-24 13:14:00', '2026-02-24 05:51:26'),
(1265, 58, 'GENERATE_QR', 'qr_code_session', 163, 'QR Code untuk jadwal #1114, expired: 2026-02-24 14:14:00', '2026-02-24 05:52:08'),
(1266, 58, 'GENERATE_QR', 'qr_code_session', 164, 'QR Code untuk jadwal #1114, expired: 2026-02-24 14:14:00', '2026-02-24 05:54:11'),
(1267, 58, 'GENERATE_QR', 'qr_code_session', 165, 'QR Code untuk jadwal #1115, expired: 2026-02-24 13:25:00', '2026-02-24 05:59:24'),
(1268, 58, 'GENERATE_QR', 'qr_code_session', 166, 'QR Code untuk jadwal #1116, expired: 2026-02-24 14:50:00', '2026-02-24 07:20:54'),
(1269, 58, 'GENERATE_QR', 'qr_code_session', 167, 'QR Code untuk jadwal #1116, expired: 2026-02-24 14:50:00', '2026-02-24 07:20:54'),
(1270, NULL, 'PRESENSI_QR', 'presensi_mahasiswa', 1116, 'Mahasiswa 11112222 presensi via QR di Laboratorium Jaringan', '2026-02-24 07:42:16'),
(1271, 66, 'LOGIN', 'users', 66, 'User login berhasil sebagai mahasiswa', '2026-02-24 07:43:33'),
(1272, 58, 'LOGIN', 'users', 58, 'User login berhasil sebagai asisten', '2026-02-25 01:48:12'),
(1273, 58, 'GENERATE_QR', 'qr_code_session', 168, 'QR Code untuk jadwal #1117, expired: 2026-02-25 09:15:00', '2026-02-25 01:48:38'),
(1274, 69, 'LOGIN', 'users', 69, 'User login berhasil sebagai mahasiswa', '2026-02-25 01:50:57'),
(1275, 58, 'GENERATE_QR', 'qr_code_session', 169, 'QR Code untuk jadwal #1117, expired: 2026-02-25 10:00:00', '2026-02-25 02:21:31'),
(1276, NULL, 'PRESENSI_QR', 'presensi_mahasiswa', 1117, 'Mahasiswa 11112222 presensi via QR di Laboratorium Statistika', '2026-02-25 02:42:55'),
(1277, 66, 'LOGIN', 'users', 66, 'User login berhasil sebagai mahasiswa', '2026-02-25 02:48:52'),
(1278, NULL, 'PRESENSI_QR', 'presensi_mahasiswa', 1117, 'Mahasiswa 070771 presensi via QR di Laboratorium Statistika', '2026-02-25 02:49:50'),
(1279, 99, 'LOGIN', 'users', 99, 'User login berhasil sebagai mahasiswa', '2026-02-25 05:21:17'),
(1280, 149, 'LOGIN', 'users', 149, 'User login berhasil sebagai mahasiswa', '2026-02-25 05:52:31'),
(1281, 187, 'LOGIN', 'users', 187, 'User login berhasil sebagai asisten', '2026-02-25 05:56:30'),
(1282, 149, 'LOGIN', 'users', 149, 'User login berhasil sebagai mahasiswa', '2026-02-25 06:05:14'),
(1283, 107, 'LOGIN', 'users', 107, 'User login berhasil sebagai mahasiswa', '2026-02-25 06:27:07');

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
(88, '2025022', 107, 'Aditya Putra Wibowo', 'F', 2, 'Pengembangan Perangkat Lunak dan Gim', '88952680929', NULL, '2026-02-02 02:33:00', 'aktif'),
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
(121, '2025055', 140, 'Aditya Putra Wibowo', 'F', 1, 'Pengembangan Perangkat Lunak dan Gim', '88234664417', NULL, '2026-02-02 02:33:00', 'aktif'),
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

--
-- Dumping data untuk tabel `penggantian_inhall`
--

INSERT INTO `penggantian_inhall` (`id`, `nim`, `jadwal_asli_id`, `jadwal_inhall_id`, `materi_diulang`, `status`, `alasan_izin`, `bukti_file`, `status_approval`, `approved_by`, `approved_at`, `alasan_reject`, `tanggal_daftar`) VALUES
(36, '2025021', 1047, NULL, 'izin', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108000', '2026-02-09 09:23:52', NULL, '2026-02-09 02:23:52'),
(37, '2025083', 1047, NULL, 'sakit', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108000', '2026-02-09 09:28:30', NULL, '2026-02-09 02:28:30'),
(38, '2025012', 1058, NULL, 'izin', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108001', '2026-02-10 10:57:11', NULL, '2026-02-10 03:57:11'),
(39, '2025076', 1058, NULL, 'izin', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108001', '2026-02-10 10:57:13', NULL, '2026-02-10 03:57:13'),
(40, '2025008', 1058, NULL, 'izin', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108001', '2026-02-10 10:57:15', NULL, '2026-02-10 03:57:15'),
(41, '2025086', 1058, NULL, 'izin', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108001', '2026-02-10 10:57:18', NULL, '2026-02-10 03:57:18'),
(42, '2025002', 1058, NULL, 'sakit', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108001', '2026-02-10 10:57:20', NULL, '2026-02-10 03:57:20'),
(43, '2025032', 1058, NULL, 'sakit', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108001', '2026-02-10 10:57:22', NULL, '2026-02-10 03:57:22'),
(44, '2025058', 1058, NULL, 'sakit', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108001', '2026-02-10 10:57:24', NULL, '2026-02-10 03:57:24'),
(45, '2025074', 1058, NULL, 'sakit', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108001', '2026-02-10 10:57:27', NULL, '2026-02-10 03:57:27'),
(46, '2025026', 1058, NULL, 'sakit', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108001', '2026-02-10 10:57:30', NULL, '2026-02-10 03:57:30'),
(47, '2025003', 1049, NULL, 'izin', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108000', '2026-02-23 15:13:32', NULL, '2026-02-23 08:13:32'),
(48, '2025023', 1049, NULL, 'izin', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108000', '2026-02-23 15:13:34', NULL, '2026-02-23 08:13:34'),
(49, '2025029', 1049, NULL, 'sakit', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108000', '2026-02-23 15:13:36', NULL, '2026-02-23 08:13:36'),
(50, '2025063', 1049, NULL, 'sakit', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108000', '2026-02-23 15:13:39', NULL, '2026-02-23 08:13:39'),
(51, '2025083', 1049, NULL, 'sakit', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108000', '2026-02-23 15:13:41', NULL, '2026-02-23 08:13:41'),
(52, '2025021', 1049, NULL, 'sakit', 'terdaftar', 'Diset oleh asisten via presensi manual', NULL, 'approved', '23108000', '2026-02-23 15:13:43', NULL, '2026-02-23 08:13:43');

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
(6, 'Pengumuman Gangguan Sistem', 'Saat ini sistem mengalami kendala teknis akibat lonjakan permintaan (over request) pada server yang menyebabkan website menjadi lambat saat diakses. Tim kami sedang melakukan proses evaluasi dan perbaikan untuk mengoptimalkan kinerja sistem agar kembali normal. Kami mohon maaf atas ketidaknyamanan yang terjadi dan mengucapkan terima kasih atas kesabaran serta pengertiannya.', 'semua', '2026-02-02 07:11:33', 38, 'active');

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
(1338, 1024, '12072010', 'hadir', NULL, '2026-01-29 14:59:25', 'manual', '231064013', NULL, NULL, NULL, 0),
(1339, 1024, '230607', 'hadir', NULL, '2026-01-29 14:59:27', 'manual', '231064013', NULL, NULL, NULL, 0),
(1341, 1046, '2025001', 'alpha', NULL, '2026-02-02 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1342, 1046, '2025003', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1343, 1046, '2025005', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1344, 1046, '2025007', 'alpha', NULL, '2026-02-02 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1345, 1046, '2025009', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1346, 1046, '2025011', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1349, 1046, '2025017', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1350, 1046, '2025019', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1351, 1046, '2025021', 'izin', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1352, 1046, '2025023', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1353, 1046, '2025025', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1354, 1046, '2025027', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1355, 1046, '2025029', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1356, 1046, '2025031', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1357, 1046, '2025033', 'hadir', NULL, '2026-02-02 03:12:33', 'qr', NULL, 'Laboratorium Jaringan', 'dev_ml4lhbe30wa1t1m4t9s', '192.168.2.102', 1),
(1358, 1046, '2025035', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1359, 1046, '2025037', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1360, 1046, '2025039', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1361, 1046, '2025041', 'alpha', NULL, '2026-02-02 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1363, 1046, '2025045', 'alpha', NULL, '2026-02-02 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1364, 1046, '2025047', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1365, 1046, '2025049', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1366, 1046, '2025051', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1367, 1046, '2025053', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1369, 1046, '2025057', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1370, 1046, '2025059', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1372, 1046, '2025063', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1373, 1046, '2025065', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1375, 1046, '2025069', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1376, 1046, '2025071', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1378, 1046, '2025075', 'alpha', NULL, '2026-02-02 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1379, 1046, '2025077', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1380, 1046, '2025079', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1381, 1046, '2025081', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1382, 1046, '2025083', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1384, 1046, '2025087', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1385, 1046, '2025089', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1387, 1046, '2025093', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1388, 1046, '2025095', 'alpha', NULL, '2026-02-02 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1389, 1046, '2025097', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1390, 1046, '2025099', 'hadir', NULL, '2026-02-02 03:09:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1405, 1046, '2025055', 'hadir', NULL, '2026-02-02 03:20:39', 'manual', '23108001', NULL, NULL, NULL, 0),
(1406, 1046, '2025067', 'hadir', NULL, '2026-02-02 03:20:40', 'manual', '23108001', NULL, NULL, NULL, 0),
(1407, 1046, '2025091', 'hadir', NULL, '2026-02-02 03:20:42', 'manual', '23108001', NULL, NULL, NULL, 0),
(1408, 1046, '2025043', 'hadir', NULL, '2026-02-02 03:20:45', 'manual', '23108001', NULL, NULL, NULL, 0),
(1409, 1046, '2025013', 'hadir', NULL, '2026-02-02 03:20:47', 'manual', '23108001', NULL, NULL, NULL, 0),
(1410, 1046, '2025073', 'hadir', NULL, '2026-02-02 03:20:48', 'manual', '23108001', NULL, NULL, NULL, 0),
(1411, 1046, '2025061', 'hadir', NULL, '2026-02-02 03:20:48', 'manual', '23108001', NULL, NULL, NULL, 0),
(1412, 1046, '2025085', 'hadir', NULL, '2026-02-02 03:20:49', 'manual', '23108001', NULL, NULL, NULL, 0),
(1413, 1046, '2025015', 'hadir', NULL, '2026-02-02 03:20:50', 'manual', '23108001', NULL, NULL, NULL, 0),
(1414, 1057, '2025002', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1415, 1057, '2025004', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1416, 1057, '2025006', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1417, 1057, '2025008', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1418, 1057, '2025010', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1419, 1057, '2025012', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1420, 1057, '2025014', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1421, 1057, '2025016', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1422, 1057, '2025018', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1423, 1057, '2025020', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1425, 1057, '2025024', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1426, 1057, '2025026', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1427, 1057, '2025028', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1428, 1057, '2025030', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1429, 1057, '2025032', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1430, 1057, '2025034', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1431, 1057, '2025036', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1432, 1057, '2025038', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1433, 1057, '2025040', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1434, 1057, '2025042', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1435, 1057, '2025044', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1436, 1057, '2025046', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1437, 1057, '2025048', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1438, 1057, '2025050', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1439, 1057, '2025052', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1440, 1057, '2025054', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1441, 1057, '2025056', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1442, 1057, '2025058', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1443, 1057, '2025060', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1444, 1057, '2025062', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1445, 1057, '2025064', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1446, 1057, '2025066', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1447, 1057, '2025068', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1448, 1057, '2025070', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1449, 1057, '2025072', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1450, 1057, '2025074', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1451, 1057, '2025076', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1452, 1057, '2025078', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1453, 1057, '2025080', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1454, 1057, '2025082', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1455, 1057, '2025084', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1456, 1057, '2025086', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1457, 1057, '2025088', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1458, 1057, '2025090', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1459, 1057, '2025092', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1460, 1057, '2025094', 'alpha', NULL, '2026-02-05 02:50:35', 'auto', NULL, NULL, NULL, NULL, 1),
(1461, 1057, '2025096', 'alpha', NULL, '2026-02-05 02:50:36', 'auto', NULL, NULL, NULL, NULL, 1),
(1462, 1057, '2025098', 'alpha', NULL, '2026-02-05 02:50:36', 'auto', NULL, NULL, NULL, NULL, 1),
(1463, 1057, '2025100', 'alpha', NULL, '2026-02-05 02:50:36', 'auto', NULL, NULL, NULL, NULL, 1),
(1464, 1025, '230607', 'alpha', NULL, '2026-02-05 19:02:00', 'auto', NULL, NULL, NULL, NULL, 1),
(1465, 1025, '12072010', 'alpha', NULL, '2026-02-05 19:02:00', 'auto', NULL, NULL, NULL, NULL, 1),
(1466, 1036, '12345678', 'hadir', NULL, '2026-02-06 06:15:57', 'qr', NULL, 'Laboratorium Pemrograman', 'dev_mlahrxumy6ci6izry88', '::1', 1),
(1467, 1026, '230607', 'alpha', NULL, '2026-02-12 16:00:54', 'auto', NULL, NULL, NULL, NULL, 1),
(1468, 1026, '12072010', 'alpha', NULL, '2026-02-12 16:00:54', 'auto', NULL, NULL, NULL, NULL, 1),
(1469, 1047, '2025001', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1470, 1047, '2025003', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1471, 1047, '2025005', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1472, 1047, '2025007', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1473, 1047, '2025009', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1474, 1047, '2025011', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1475, 1047, '2025013', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1476, 1047, '2025015', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1477, 1047, '2025017', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1478, 1047, '2025019', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1479, 1047, '2025021', 'izin', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1480, 1047, '2025023', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1481, 1047, '2025025', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1482, 1047, '2025027', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1483, 1047, '2025029', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1484, 1047, '2025031', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1485, 1047, '2025033', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1486, 1047, '2025035', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1487, 1047, '2025037', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1488, 1047, '2025039', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1489, 1047, '2025041', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1490, 1047, '2025043', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1491, 1047, '2025045', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1492, 1047, '2025047', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1493, 1047, '2025049', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1494, 1047, '2025051', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1495, 1047, '2025053', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1496, 1047, '2025055', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1497, 1047, '2025057', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1498, 1047, '2025059', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1499, 1047, '2025061', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1500, 1047, '2025063', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1501, 1047, '2025065', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1502, 1047, '2025067', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1503, 1047, '2025069', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1504, 1047, '2025071', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1505, 1047, '2025073', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1506, 1047, '2025075', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1507, 1047, '2025077', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1508, 1047, '2025079', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1509, 1047, '2025081', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1510, 1047, '2025083', 'sakit', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1511, 1047, '2025085', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1512, 1047, '2025087', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1513, 1047, '2025089', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1514, 1047, '2025091', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1515, 1047, '2025093', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1516, 1047, '2025095', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1517, 1047, '2025097', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1518, 1047, '2025099', 'hadir', NULL, '2026-02-09 02:07:25', 'manual', '23108000', NULL, NULL, NULL, 0),
(1519, 1058, '2025022', 'hadir', NULL, '2026-02-10 03:56:49', 'manual', '23108001', NULL, NULL, NULL, 0),
(1520, 1058, '2025064', 'hadir', NULL, '2026-02-10 03:56:50', 'manual', '23108001', NULL, NULL, NULL, 0),
(1521, 1058, '2025078', 'hadir', NULL, '2026-02-10 03:56:51', 'manual', '23108001', NULL, NULL, NULL, 0),
(1522, 1058, '2025034', 'hadir', NULL, '2026-02-10 03:56:51', 'manual', '23108001', NULL, NULL, NULL, 0),
(1523, 1058, '2025046', 'hadir', NULL, '2026-02-10 03:56:52', 'manual', '23108001', NULL, NULL, NULL, 0),
(1524, 1058, '2025080', 'hadir', NULL, '2026-02-10 03:56:53', 'manual', '23108001', NULL, NULL, NULL, 0),
(1525, 1058, '2025024', 'hadir', NULL, '2026-02-10 03:56:53', 'manual', '23108001', NULL, NULL, NULL, 0),
(1526, 1058, '2025004', 'hadir', NULL, '2026-02-10 03:56:54', 'manual', '23108001', NULL, NULL, NULL, 0),
(1527, 1058, '2025054', 'hadir', NULL, '2026-02-10 03:56:55', 'manual', '23108001', NULL, NULL, NULL, 0),
(1528, 1058, '2025020', 'hadir', NULL, '2026-02-10 03:56:55', 'manual', '23108001', NULL, NULL, NULL, 0),
(1529, 1058, '2025042', 'hadir', NULL, '2026-02-10 03:56:56', 'manual', '23108001', NULL, NULL, NULL, 0),
(1530, 1058, '2025096', 'hadir', NULL, '2026-02-10 03:56:57', 'manual', '23108001', NULL, NULL, NULL, 0),
(1531, 1058, '2025010', 'hadir', NULL, '2026-02-10 03:56:57', 'manual', '23108001', NULL, NULL, NULL, 0),
(1532, 1058, '2025040', 'hadir', NULL, '2026-02-10 03:56:59', 'manual', '23108001', NULL, NULL, NULL, 0),
(1533, 1058, '2025068', 'hadir', NULL, '2026-02-10 03:57:01', 'manual', '23108001', NULL, NULL, NULL, 0),
(1534, 1058, '2025028', 'hadir', NULL, '2026-02-10 03:57:03', 'manual', '23108001', NULL, NULL, NULL, 0),
(1535, 1058, '2025072', 'hadir', NULL, '2026-02-10 03:57:05', 'manual', '23108001', NULL, NULL, NULL, 0),
(1536, 1058, '2025084', 'hadir', NULL, '2026-02-10 03:57:08', 'manual', '23108001', NULL, NULL, NULL, 0),
(1537, 1058, '2025012', 'izin', NULL, '2026-02-10 03:57:10', 'manual', '23108001', NULL, NULL, NULL, 0),
(1538, 1058, '2025076', 'izin', NULL, '2026-02-10 03:57:13', 'manual', '23108001', NULL, NULL, NULL, 0),
(1539, 1058, '2025008', 'izin', NULL, '2026-02-10 03:57:15', 'manual', '23108001', NULL, NULL, NULL, 0),
(1540, 1058, '2025086', 'izin', NULL, '2026-02-10 03:57:18', 'manual', '23108001', NULL, NULL, NULL, 0),
(1541, 1058, '2025002', 'sakit', NULL, '2026-02-10 03:57:20', 'manual', '23108001', NULL, NULL, NULL, 0),
(1542, 1058, '2025032', 'sakit', NULL, '2026-02-10 03:57:22', 'manual', '23108001', NULL, NULL, NULL, 0),
(1543, 1058, '2025058', 'sakit', NULL, '2026-02-10 03:57:24', 'manual', '23108001', NULL, NULL, NULL, 0),
(1544, 1058, '2025074', 'sakit', NULL, '2026-02-10 03:57:27', 'manual', '23108001', NULL, NULL, NULL, 0),
(1545, 1058, '2025026', 'sakit', NULL, '2026-02-10 03:57:30', 'manual', '23108001', NULL, NULL, NULL, 0),
(1546, 1058, '2025006', 'alpha', NULL, '2026-02-10 03:57:32', 'manual', '23108001', NULL, NULL, NULL, 0),
(1547, 1058, '2025066', 'alpha', NULL, '2026-02-10 03:57:35', 'manual', '23108001', NULL, NULL, NULL, 0),
(1548, 1058, '2025098', 'hadir', NULL, '2026-02-10 03:57:37', 'manual', '23108001', NULL, NULL, NULL, 0),
(1549, 1058, '2025062', 'hadir', NULL, '2026-02-10 03:57:38', 'manual', '23108001', NULL, NULL, NULL, 0),
(1550, 1058, '2025018', 'hadir', NULL, '2026-02-10 03:57:40', 'manual', '23108001', NULL, NULL, NULL, 0),
(1551, 1058, '2025044', 'hadir', NULL, '2026-02-10 03:57:43', 'manual', '23108001', NULL, NULL, NULL, 0),
(1552, 1058, '2025060', 'hadir', NULL, '2026-02-10 03:57:45', 'manual', '23108001', NULL, NULL, NULL, 0),
(1553, 1058, '2025070', 'hadir', NULL, '2026-02-10 03:57:48', 'manual', '23108001', NULL, NULL, NULL, 0),
(1554, 1058, '2025094', 'hadir', NULL, '2026-02-10 03:57:51', 'manual', '23108001', NULL, NULL, NULL, 0),
(1555, 1058, '2025088', 'hadir', NULL, '2026-02-10 03:57:53', 'manual', '23108001', NULL, NULL, NULL, 0),
(1556, 1058, '2025036', 'hadir', NULL, '2026-02-10 03:57:55', 'manual', '23108001', NULL, NULL, NULL, 0),
(1557, 1058, '2025038', 'hadir', NULL, '2026-02-10 03:57:57', 'manual', '23108001', NULL, NULL, NULL, 0),
(1558, 1058, '2025050', 'hadir', NULL, '2026-02-10 03:57:59', 'manual', '23108001', NULL, NULL, NULL, 0),
(1559, 1058, '2025052', 'hadir', NULL, '2026-02-10 03:58:01', 'manual', '23108001', NULL, NULL, NULL, 0),
(1560, 1058, '2025048', 'hadir', NULL, '2026-02-10 03:58:05', 'manual', '23108001', NULL, NULL, NULL, 0),
(1561, 1058, '2025090', 'hadir', NULL, '2026-02-10 03:58:07', 'manual', '23108001', NULL, NULL, NULL, 0),
(1562, 1058, '2025016', 'hadir', NULL, '2026-02-10 03:58:09', 'manual', '23108001', NULL, NULL, NULL, 0),
(1563, 1058, '2025092', 'hadir', NULL, '2026-02-10 03:58:11', 'manual', '23108001', NULL, NULL, NULL, 0),
(1564, 1058, '2025100', 'hadir', NULL, '2026-02-10 03:58:13', 'manual', '23108001', NULL, NULL, NULL, 0),
(1565, 1058, '2025030', 'hadir', NULL, '2026-02-10 03:58:15', 'manual', '23108001', NULL, NULL, NULL, 0),
(1566, 1058, '2025014', 'hadir', NULL, '2026-02-10 03:58:17', 'manual', '23108001', NULL, NULL, NULL, 0),
(1567, 1058, '2025082', 'hadir', NULL, '2026-02-10 03:58:19', 'manual', '23108001', NULL, NULL, NULL, 0),
(1568, 1058, '2025056', 'hadir', NULL, '2026-02-10 03:58:21', 'manual', '23108001', NULL, NULL, NULL, 0),
(1570, 1048, '2025001', 'alpha', NULL, '2026-02-16 15:41:24', 'auto', NULL, NULL, NULL, NULL, 1),
(1571, 1048, '2025003', 'alpha', NULL, '2026-02-16 15:41:24', 'auto', NULL, NULL, NULL, NULL, 1),
(1572, 1048, '2025005', 'alpha', NULL, '2026-02-16 15:41:24', 'auto', NULL, NULL, NULL, NULL, 1),
(1573, 1048, '2025007', 'alpha', NULL, '2026-02-16 15:41:24', 'auto', NULL, NULL, NULL, NULL, 1),
(1574, 1048, '2025009', 'alpha', NULL, '2026-02-16 15:41:24', 'auto', NULL, NULL, NULL, NULL, 1),
(1575, 1048, '2025011', 'alpha', NULL, '2026-02-16 15:41:24', 'auto', NULL, NULL, NULL, NULL, 1),
(1576, 1048, '2025013', 'alpha', NULL, '2026-02-16 15:41:24', 'auto', NULL, NULL, NULL, NULL, 1),
(1577, 1048, '2025015', 'alpha', NULL, '2026-02-16 15:41:24', 'auto', NULL, NULL, NULL, NULL, 1),
(1578, 1048, '2025017', 'alpha', NULL, '2026-02-16 15:41:26', 'auto', NULL, NULL, NULL, NULL, 1),
(1579, 1048, '2025019', 'alpha', NULL, '2026-02-16 15:41:26', 'auto', NULL, NULL, NULL, NULL, 1),
(1580, 1048, '2025021', 'alpha', NULL, '2026-02-16 15:41:29', 'auto', NULL, NULL, NULL, NULL, 1),
(1581, 1048, '2025023', 'alpha', NULL, '2026-02-16 15:41:30', 'auto', NULL, NULL, NULL, NULL, 1),
(1582, 1048, '2025025', 'alpha', NULL, '2026-02-16 15:41:32', 'auto', NULL, NULL, NULL, NULL, 1),
(1583, 1048, '2025027', 'alpha', NULL, '2026-02-16 15:41:33', 'auto', NULL, NULL, NULL, NULL, 1),
(1584, 1048, '2025029', 'alpha', NULL, '2026-02-16 15:41:33', 'auto', NULL, NULL, NULL, NULL, 1),
(1585, 1048, '2025031', 'alpha', NULL, '2026-02-16 15:41:33', 'auto', NULL, NULL, NULL, NULL, 1),
(1586, 1048, '2025033', 'alpha', NULL, '2026-02-16 15:41:33', 'auto', NULL, NULL, NULL, NULL, 1),
(1587, 1048, '2025035', 'alpha', NULL, '2026-02-16 15:41:33', 'auto', NULL, NULL, NULL, NULL, 1),
(1588, 1048, '2025037', 'alpha', NULL, '2026-02-16 15:41:33', 'auto', NULL, NULL, NULL, NULL, 1),
(1589, 1048, '2025039', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1590, 1048, '2025041', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1591, 1048, '2025043', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1592, 1048, '2025045', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1593, 1048, '2025047', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1594, 1048, '2025049', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1595, 1048, '2025051', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1596, 1048, '2025053', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1597, 1048, '2025055', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1598, 1048, '2025057', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1599, 1048, '2025059', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1600, 1048, '2025061', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1601, 1048, '2025063', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1602, 1048, '2025065', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1603, 1048, '2025067', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1604, 1048, '2025069', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1605, 1048, '2025071', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1606, 1048, '2025073', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1607, 1048, '2025075', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1608, 1048, '2025077', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1609, 1048, '2025079', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1610, 1048, '2025081', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1611, 1048, '2025083', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1612, 1048, '2025085', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1613, 1048, '2025087', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1614, 1048, '2025089', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1615, 1048, '2025091', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1616, 1048, '2025093', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1617, 1048, '2025095', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1618, 1048, '2025097', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1619, 1048, '2025099', 'alpha', NULL, '2026-02-16 15:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1620, 1059, '2025002', 'alpha', NULL, '2026-02-19 08:41:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1621, 1059, '2025004', 'alpha', NULL, '2026-02-19 08:41:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1622, 1059, '2025006', 'alpha', NULL, '2026-02-19 08:41:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1623, 1059, '2025008', 'alpha', NULL, '2026-02-19 08:41:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1624, 1059, '2025010', 'alpha', NULL, '2026-02-19 08:41:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1625, 1059, '2025012', 'alpha', NULL, '2026-02-19 08:41:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1627, 1059, '2025016', 'alpha', NULL, '2026-02-19 08:41:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1628, 1059, '2025018', 'alpha', NULL, '2026-02-19 08:41:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1629, 1059, '2025020', 'alpha', NULL, '2026-02-19 08:41:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1631, 1059, '2025024', 'alpha', NULL, '2026-02-19 08:41:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1632, 1059, '2025026', 'alpha', NULL, '2026-02-19 08:41:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1633, 1059, '2025028', 'alpha', NULL, '2026-02-19 08:41:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1634, 1059, '2025030', 'alpha', NULL, '2026-02-19 08:41:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1635, 1059, '2025032', 'alpha', NULL, '2026-02-19 08:41:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1636, 1059, '2025034', 'alpha', NULL, '2026-02-19 08:41:05', 'auto', NULL, NULL, NULL, NULL, 1),
(1637, 1059, '2025036', 'alpha', NULL, '2026-02-19 08:41:05', 'auto', NULL, NULL, NULL, NULL, 1),
(1638, 1059, '2025038', 'alpha', NULL, '2026-02-19 08:41:05', 'auto', NULL, NULL, NULL, NULL, 1),
(1639, 1059, '2025040', 'alpha', NULL, '2026-02-19 08:41:05', 'auto', NULL, NULL, NULL, NULL, 1),
(1640, 1059, '2025042', 'alpha', NULL, '2026-02-19 08:41:05', 'auto', NULL, NULL, NULL, NULL, 1),
(1641, 1059, '2025044', 'alpha', NULL, '2026-02-19 08:41:05', 'auto', NULL, NULL, NULL, NULL, 1),
(1642, 1059, '2025046', 'alpha', NULL, '2026-02-19 08:41:06', 'auto', NULL, NULL, NULL, NULL, 1),
(1643, 1059, '2025048', 'alpha', NULL, '2026-02-19 08:41:08', 'auto', NULL, NULL, NULL, NULL, 1),
(1644, 1059, '2025050', 'alpha', NULL, '2026-02-19 08:41:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1645, 1059, '2025052', 'alpha', NULL, '2026-02-19 08:41:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1646, 1059, '2025054', 'alpha', NULL, '2026-02-19 08:41:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1647, 1059, '2025056', 'alpha', NULL, '2026-02-19 08:41:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1648, 1059, '2025058', 'alpha', NULL, '2026-02-19 08:41:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1649, 1059, '2025060', 'alpha', NULL, '2026-02-19 08:41:10', 'auto', NULL, NULL, NULL, NULL, 1),
(1650, 1059, '2025062', 'alpha', NULL, '2026-02-19 08:41:10', 'auto', NULL, NULL, NULL, NULL, 1),
(1652, 1059, '2025066', 'alpha', NULL, '2026-02-19 08:41:10', 'auto', NULL, NULL, NULL, NULL, 1),
(1653, 1059, '2025068', 'alpha', NULL, '2026-02-19 08:41:10', 'auto', NULL, NULL, NULL, NULL, 1),
(1654, 1059, '2025070', 'alpha', NULL, '2026-02-19 08:41:11', 'auto', NULL, NULL, NULL, NULL, 1),
(1655, 1059, '2025072', 'alpha', NULL, '2026-02-19 08:41:15', 'auto', NULL, NULL, NULL, NULL, 1),
(1656, 1059, '2025074', 'alpha', NULL, '2026-02-19 08:41:17', 'auto', NULL, NULL, NULL, NULL, 1),
(1657, 1059, '2025076', 'alpha', NULL, '2026-02-19 08:41:19', 'auto', NULL, NULL, NULL, NULL, 1),
(1658, 1059, '2025078', 'alpha', NULL, '2026-02-19 08:41:25', 'auto', NULL, NULL, NULL, NULL, 1),
(1659, 1059, '2025080', 'alpha', NULL, '2026-02-19 08:41:29', 'auto', NULL, NULL, NULL, NULL, 1),
(1660, 1059, '2025082', 'alpha', NULL, '2026-02-19 08:41:32', 'auto', NULL, NULL, NULL, NULL, 1),
(1661, 1059, '2025084', 'alpha', NULL, '2026-02-19 08:41:34', 'auto', NULL, NULL, NULL, NULL, 1),
(1662, 1059, '2025086', 'alpha', NULL, '2026-02-19 08:41:36', 'auto', NULL, NULL, NULL, NULL, 1),
(1663, 1059, '2025088', 'alpha', NULL, '2026-02-19 08:41:38', 'auto', NULL, NULL, NULL, NULL, 1),
(1664, 1059, '2025090', 'alpha', NULL, '2026-02-19 08:41:43', 'auto', NULL, NULL, NULL, NULL, 1),
(1665, 1059, '2025092', 'alpha', NULL, '2026-02-19 08:41:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1666, 1059, '2025094', 'alpha', NULL, '2026-02-19 08:41:46', 'auto', NULL, NULL, NULL, NULL, 1),
(1667, 1059, '2025096', 'alpha', NULL, '2026-02-19 08:41:47', 'auto', NULL, NULL, NULL, NULL, 1),
(1668, 1059, '2025098', 'alpha', NULL, '2026-02-19 08:41:48', 'auto', NULL, NULL, NULL, NULL, 1),
(1669, 1059, '2025100', 'alpha', NULL, '2026-02-19 08:41:48', 'auto', NULL, NULL, NULL, NULL, 1),
(1670, 1027, '230607', 'alpha', NULL, '2026-02-19 16:00:48', 'auto', NULL, NULL, NULL, NULL, 1),
(1671, 1027, '12072010', 'alpha', NULL, '2026-02-19 16:00:48', 'auto', NULL, NULL, NULL, NULL, 1),
(1673, 1057, '2025001', 'alpha', NULL, '2026-02-23 04:57:03', 'auto', NULL, NULL, NULL, NULL, 1),
(1674, 1058, '2025001', 'alpha', NULL, '2026-02-23 04:57:03', 'auto', NULL, NULL, NULL, NULL, 1),
(1675, 1059, '2025001', 'alpha', NULL, '2026-02-23 04:57:03', 'auto', NULL, NULL, NULL, NULL, 1),
(1680, 1035, '12345678', 'alpha', NULL, '2026-02-23 07:29:59', 'auto', NULL, NULL, NULL, NULL, 1),
(1681, 1037, '12345678', 'alpha', NULL, '2026-02-23 07:29:59', 'auto', NULL, NULL, NULL, NULL, 1),
(1682, 1038, '12345678', 'alpha', NULL, '2026-02-23 07:29:59', 'auto', NULL, NULL, NULL, NULL, 1),
(1683, 1049, '2025001', 'hadir', NULL, '2026-02-23 08:00:15', 'manual', '23108000', NULL, NULL, NULL, 1),
(1684, 1049, '2025003', 'izin', NULL, '2026-02-23 08:00:15', 'manual', '23108000', NULL, NULL, NULL, 1),
(1685, 1049, '2025005', 'hadir', NULL, '2026-02-23 08:00:15', 'manual', '23108000', NULL, NULL, NULL, 1),
(1686, 1049, '2025007', 'hadir', NULL, '2026-02-23 08:00:15', 'manual', '23108000', NULL, NULL, NULL, 1),
(1687, 1049, '2025009', 'hadir', NULL, '2026-02-23 08:00:15', 'manual', '23108000', NULL, NULL, NULL, 1),
(1688, 1049, '2025011', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1689, 1049, '2025013', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1690, 1049, '2025015', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1691, 1049, '2025017', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1692, 1049, '2025019', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1693, 1049, '2025021', 'sakit', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1694, 1049, '2025023', 'izin', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1695, 1049, '2025025', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1696, 1049, '2025027', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1697, 1049, '2025029', 'sakit', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1698, 1049, '2025031', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1699, 1049, '2025033', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1700, 1049, '2025035', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1701, 1049, '2025037', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1702, 1049, '2025039', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1703, 1049, '2025041', 'alpha', NULL, '2026-02-23 08:00:16', 'auto', NULL, NULL, NULL, NULL, 1),
(1704, 1049, '2025043', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1705, 1049, '2025045', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1706, 1049, '2025047', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1707, 1049, '2025049', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1708, 1049, '2025051', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1709, 1049, '2025053', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1710, 1049, '2025055', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1711, 1049, '2025057', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1712, 1049, '2025059', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1713, 1049, '2025061', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1714, 1049, '2025063', 'sakit', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1715, 1049, '2025065', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1716, 1049, '2025067', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1717, 1049, '2025069', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1718, 1049, '2025071', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1719, 1049, '2025073', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1720, 1049, '2025075', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1721, 1049, '2025077', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1722, 1049, '2025079', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1723, 1049, '2025081', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1724, 1049, '2025083', 'sakit', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1725, 1049, '2025085', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1726, 1049, '2025087', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1727, 1049, '2025089', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1728, 1049, '2025091', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1729, 1049, '2025093', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1730, 1049, '2025095', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1731, 1049, '2025097', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1732, 1049, '2025099', 'hadir', NULL, '2026-02-23 08:00:16', 'manual', '23108000', NULL, NULL, NULL, 1),
(1733, 1049, '2025002', 'hadir', NULL, '2026-02-23 08:11:49', 'qr', NULL, 'Laboratorium Statistika', 'dev_mkw5quqoh7x9ncawc5q', '::1', 1),
(1736, 1102, '12072010', 'alpha', NULL, '2026-02-24 04:55:46', 'auto', NULL, NULL, NULL, NULL, 1),
(1737, 1102, '120341456', 'alpha', NULL, '2026-02-24 04:55:46', 'auto', NULL, NULL, NULL, NULL, 1),
(1745, 1116, '070771', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1746, 1116, '11112222', 'hadir', NULL, '2026-02-24 07:42:16', 'qr', NULL, 'Laboratorium Jaringan', 'dev_mkw5quqoh7x9ncawc5q', '::1', 1),
(1747, 1060, '2025004', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1748, 1060, '2025006', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1749, 1060, '2025008', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1750, 1060, '2025010', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1751, 1060, '2025012', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1752, 1060, '2025014', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1753, 1060, '2025016', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1754, 1060, '2025018', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1755, 1060, '2025020', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1757, 1060, '2025024', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1758, 1060, '2025026', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1759, 1060, '2025028', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1760, 1060, '2025030', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1761, 1060, '2025032', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1762, 1060, '2025034', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1763, 1060, '2025036', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1764, 1060, '2025038', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1765, 1060, '2025040', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1766, 1060, '2025042', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1767, 1060, '2025044', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1768, 1060, '2025046', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1769, 1060, '2025048', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1770, 1060, '2025050', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1771, 1060, '2025052', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1772, 1060, '2025054', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1773, 1060, '2025056', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1774, 1060, '2025058', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1775, 1060, '2025060', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1776, 1060, '2025062', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1777, 1060, '2025064', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1778, 1060, '2025066', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1779, 1060, '2025068', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1780, 1060, '2025070', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1781, 1060, '2025072', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1782, 1060, '2025074', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1783, 1060, '2025076', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1784, 1060, '2025078', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1785, 1060, '2025080', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1786, 1060, '2025082', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1787, 1060, '2025084', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1788, 1060, '2025086', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1789, 1060, '2025088', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1790, 1060, '2025090', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1791, 1060, '2025092', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1792, 1060, '2025094', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1793, 1060, '2025096', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1794, 1060, '2025098', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1795, 1060, '2025100', 'alpha', NULL, '2026-02-24 08:11:45', 'auto', NULL, NULL, NULL, NULL, 1),
(1798, 1117, '070771', 'hadir', NULL, '2026-02-25 02:49:50', 'qr', NULL, 'Laboratorium Statistika', 'dev_mkw5quqoh7x9ncawc5q', '::1', 1),
(1799, 1117, '11112222', 'hadir', NULL, '2026-02-25 02:42:55', 'qr', NULL, 'Laboratorium Statistika', 'dev_mkw5quqoh7x9ncawc5q', '::1', 1),
(1801, 1059, '2025014', 'izin', NULL, '2026-02-25 05:19:50', 'manual', NULL, NULL, NULL, NULL, 0),
(1803, 1059, '2025064', 'sakit', NULL, '2026-02-25 06:24:49', 'manual', NULL, NULL, NULL, NULL, 0),
(1807, 1057, '2025022', 'hadir', NULL, '2026-02-25 06:26:32', 'manual', NULL, NULL, NULL, NULL, 0),
(1808, 1059, '2025022', 'hadir', NULL, '2026-02-25 06:31:28', 'manual', NULL, NULL, NULL, NULL, 0),
(1813, 1060, '2025022', 'sakit', NULL, '2026-02-25 07:28:47', 'manual', NULL, NULL, NULL, NULL, 0);

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
(147, 1046, '34d77844924f0c2a4c585fb6f285cb17_1770001759', '2026-02-02 15:00:00', '2026-02-02 03:09:19'),
(148, 1036, '4b844b8e8608e07e2e53c5b336c1673a_1770358540', '2026-02-06 23:00:00', '2026-02-06 06:15:40'),
(149, 1026, 'dd154e7cce020a294b3c04541055f967_1770359156', '2026-02-12 23:00:00', '2026-02-06 06:25:56'),
(150, 1047, 'cffcd011524f3dbda571ac9464a0543b_1770602844', '2026-02-09 15:00:00', '2026-02-09 02:07:25'),
(152, 1037, '2ddb7b80711248a4f050a292cc267d53_1770985524', '2026-02-13 23:00:00', '2026-02-13 12:25:24'),
(153, 1027, '12cbdb944e6bba1e19fdcda1afbb4979_1771511486', '2026-02-19 23:00:00', '2026-02-19 14:31:26'),
(154, 1049, '78102c50e1a5f5842bce4ac37bb79446_1771834264', '2026-02-23 23:00:00', '2026-02-23 08:11:04'),
(167, 1116, '0dfa4fe86978a6088f3a77d09dd2b3f2_1771917654', '2026-02-24 14:50:00', '2026-02-24 07:20:54'),
(169, 1117, '93c7d4aec2fab9932a4b57c6ca50c250_1771986091', '2026-02-25 10:00:00', '2026-02-25 02:21:31');

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

--
-- Dumping data untuk tabel `soal_kuis`
--

INSERT INTO `soal_kuis` (`id`, `kuis_id`, `pertanyaan`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `kunci_jawaban`, `gambar`) VALUES
(42, 3, 'Kompleksitas waktu dari algoritma berikut adalah …', 'O(1)', 'O(log n)', 'O(n)', 'O(n²)', 'C', 'uploads/soal_kuis/soal_3_1770345751.png'),
(43, 3, 'Output dari potongan kode berikut adalah …', '7', '10', '13', 'Eror', 'A', 'uploads/soal_kuis/soal_3_1770345865.png'),
(54, 3, 'Manakah definisi paling tepat dari algoritma?', 'Program yang ditulis menggunakan bahasa pemrograman', 'Langkah terbatas dan terurut untuk menyelesaikan masalah', 'Kode sumber yang dapat dikompilasi', 'Struktur data untuk menyimpan informasi', 'B', NULL),
(55, 3, 'Dalam pemrograman, time complexity digunakan untuk mengukur …', 'Kecepatan CPU', 'Waktu eksekusi algoritma terhadap ukuran input', 'Besarnya memori RAM', 'Jumlah baris kode', 'B', NULL),
(56, 3, 'Apa perbedaan utama compiler dan interpreter?', 'Compiler lebih lambat dari interpreter', 'Interpreter menghasilkan file .exe', 'Compiler menerjemahkan seluruh program sebelum dijalankan', 'Interpreter hanya digunakan untuk bahasa C', 'C', NULL),
(57, 3, 'Tipe data immutable berarti …', 'Datanya bisa diubah kapan saja', 'Datanya tidak bisa diubah setelah dibuat', 'Datanya selalu kosong', 'Datanya hanya untuk angka', 'B', NULL),
(58, 3, 'Konsep encapsulation dalam OOP bertujuan untuk …', 'Menjalankan banyak program sekaligus', 'Menggabungkan banyak class', 'Melindungi data dan membatasi akses langsung', 'Menghapus data yang tidak terpakai', 'C', NULL),
(59, 3, 'Manakah contoh relasi inheritance yang benar?', 'Mobil → Mesin', 'Dosen → Kampus', 'Mahasiswa → Manusia', 'Buku → Perpustakaan', 'C', NULL),
(60, 3, 'Apa fungsi utama dari exception handling?', 'Mempercepat program', 'Menangani kesalahan saat runtime', 'Menghapus bug dari program', 'Mengompilasi kode', 'B', NULL),
(61, 3, 'Dalam struktur data, stack menggunakan prinsip …', 'FIFO (First In First Out)', 'LIFO (Last In First Out)', 'Random Access', 'Priority Based', 'B', NULL),
(62, 3, 'Manakah yang termasuk struktur data non-linear?', 'Array', 'Stack', 'Queue', 'Tree', 'D', NULL),
(63, 3, 'Normalisasi database bertujuan untuk …', 'Mempercepat query', 'Menghilangkan redundansi data', 'Memperbesar ukuran tabel', 'Menambah jumlah relasi', 'B', NULL),
(64, 3, 'Pernyataan SQL yang digunakan untuk mengambil data adalah …', 'INSERT', 'UPDATE', 'DELETE', 'SELECT', 'D', NULL),
(65, 3, 'Deadlock pada sistem terjadi ketika …', 'Program berjalan terlalu cepat', 'Dua atau lebih proses saling menunggu resource', 'CPU kehabisan daya', 'Database kosong', 'B', NULL),
(66, 3, 'Dalam pemrograman paralel, tujuan utama penggunaan thread adalah …', 'Mengurangi ukuran file', 'Meningkatkan kinerja dengan eksekusi bersamaan', 'Menghapus proses', 'Menghindari error sintaks', 'B', NULL);

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
(128, 'user2025043', '$2y$10$FI5d3H57nb43/UYCCqCmRetdIjnJaOJHGKBIc7rsZ.7.gDMM3PVRG', 'mahasiswa', '2026-02-02 02:33:35', NULL, NULL),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=102;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1118;

--
-- AUTO_INCREMENT untuk tabel `jurnal_praktikum`
--
ALTER TABLE `jurnal_praktikum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `kuis`
--
ALTER TABLE `kuis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `lab`
--
ALTER TABLE `lab`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `log_presensi`
--
ALTER TABLE `log_presensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1284;

--
-- AUTO_INCREMENT untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT untuk tabel `materi_perkuliahan`
--
ALTER TABLE `materi_perkuliahan`
  MODIFY `id_materi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `penggantian_inhall`
--
ALTER TABLE `penggantian_inhall`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `presensi_mahasiswa`
--
ALTER TABLE `presensi_mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1814;

--
-- AUTO_INCREMENT untuk tabel `qr_code_session`
--
ALTER TABLE `qr_code_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=170;

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
