-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 29 Jan 2026 pada 09.18
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
(9, '123456789', 68, 'Mulyono', '6283841426400', NULL, 'MK003', 'aktif');

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
(1013, 1, '2026-01-29', '15:00:00', '23:00:00', 'LAB001', 'A', 0, 'MK002', 'Pertemuan 1 - Pengenalan', '231064013', NULL, 'materi', NULL, '2026-01-29 08:02:46'),
(1014, 2, '2026-02-05', '15:00:00', '23:00:00', 'LAB002', 'A', 0, 'MK002', 'Pertemuan 2 - Dasar', '231064013', NULL, 'materi', NULL, '2026-01-29 08:02:46'),
(1015, 3, '2026-02-12', '15:00:00', '23:00:00', 'LAB001', 'A', 0, 'MK002', 'Pertemuan 3 - Lanjutan I', '231064013', NULL, 'materi', NULL, '2026-01-29 08:02:46'),
(1016, 4, '2026-02-19', '15:00:00', '23:00:00', 'LAB002', 'A', 0, 'MK002', 'Pertemuan 4 - Lanjutan II', '231064013', NULL, 'materi', NULL, '2026-01-29 08:02:46'),
(1017, 5, '2026-02-26', '15:00:00', '23:00:00', 'LAB001', 'A', 0, 'MK002', 'Pertemuan 5 - Praktik I', '231064013', NULL, 'materi', NULL, '2026-01-29 08:02:46'),
(1018, 6, '2026-03-05', '15:00:00', '23:00:00', 'LAB002', 'A', 0, 'MK002', 'Pertemuan 6 - Praktik II', '231064013', NULL, 'materi', NULL, '2026-01-29 08:02:46'),
(1019, 7, '2026-03-12', '15:00:00', '23:00:00', 'LAB001', 'A', 0, 'MK002', 'Pertemuan 7 - Praktik III', '231064013', NULL, 'materi', NULL, '2026-01-29 08:02:46'),
(1020, 8, '2026-03-19', '15:00:00', '23:00:00', 'LAB002', 'A', 0, 'MK002', 'Pertemuan 8 - Review', '231064013', NULL, 'materi', NULL, '2026-01-29 08:02:46'),
(1021, 9, '2026-03-26', '15:00:00', '23:00:00', 'LAB001', 'A', 0, 'MK002', 'Praresponsi', '231064013', NULL, 'praresponsi', NULL, '2026-01-29 08:02:46'),
(1022, 9, '2026-03-26', '23:00:00', '07:00:00', 'LAB001', 'A', 0, 'MK002', 'Inhall', '231064013', NULL, 'inhall', NULL, '2026-01-29 08:02:46'),
(1023, 10, '2026-04-02', '15:00:00', '23:00:00', 'LAB002', 'A', 0, 'MK002', 'Responsi', '231064013', NULL, 'responsi', NULL, '2026-01-29 08:02:46');

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
('E', 'STATISTIK-2024', 'Statistik S1', '2024/2025');

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
(2, 'MK002'),
(2, 'STP2503'),
(3, 'MK003'),
(3, 'MK004'),
(4, 'MK003'),
(4, 'MK004'),
(4, 'STP2503');

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
(46, '230607', 60, 'Nanda Hanif Abyan Bromo Putra', 'A', 1, 'Pemrogaman', '083841426400', 'uploads/profil/mhs_230607_1767856437.png', '2025-12-15 03:32:00', 'aktif'),
(47, '24346554', 61, 'AAAA', 'E', 1, 'Statistik S1', '', NULL, '2025-12-15 07:01:00', 'aktif'),
(48, '765434567', 62, 'ccccc', 'E', 1, 'Statistik S1', '', NULL, '2025-12-15 07:15:00', 'aktif'),
(49, '9532753', 63, 'gggg', 'E', 1, 'Statistik S1', '', NULL, '2025-12-15 07:15:00', 'aktif'),
(50, '12345678', 64, 'Muhammad Iniesta Wildan Bromo Putra', 'A', 1, 'Pemrogaman', '24356786576', 'uploads/profil/mhs_12345678_1765910430.jpg', '2025-12-16 16:00:00', 'aktif'),
(51, '12072010', 65, 'Anik Yuliana', 'A', 1, 'Pemrogaman', '-', NULL, '2025-12-17 02:10:00', 'aktif'),
(52, '070771', 66, 'Muhammad Iniesta Wildan Bromo Putra', 'B', 1, 'Teknik Informatika', '083841426422', 'uploads/profil/mhs_070771_1766543709.jpg', '2025-12-19 05:38:00', 'aktif'),
(53, '11112222', 69, 'Massayu Sekar Anindita', 'B', 1, 'Stastatika', '085727662393', NULL, '2025-12-29 06:33:00', 'aktif'),
(55, '070772', 72, 'Simba', 'E', 1, 'Statistik S1', '', NULL, '2026-01-09 01:57:00', 'aktif'),
(56, '0000123', 74, 'Budi Purnama', 'E', 1, 'Statistik S1', '08126007900', 'uploads/profil/mhs_0000123_1769001059.webp', '2026-01-19 06:08:59', 'aktif'),
(58, '0000456', 76, 'Budi Purbaya', 'E', 1, 'Statistik S1', '', 'uploads/profil/mhs_0000456_1768885505.jpg', '2026-01-19 06:30:00', 'aktif'),
(63, '10167021', 82, 'Alexander Sucipto', 'E', 1, 'Teknik Informatika', '62212423341', 'uploads/profil/mhs_10167021_1769060212.png', '2026-01-19 07:51:00', 'aktif'),
(65, '12451731', 84, 'Alexander Kurdian', 'E', 1, 'Teknik Informatika', '62212436341', NULL, '2026-01-19 07:56:00', 'aktif'),
(66, '12455531', 85, 'Alexander Kurniawan', 'E', 1, 'Teknik Informatika', '62212423341', NULL, '2026-01-20 06:34:00', 'aktif');

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
('MK006', 'Rekayasa Perangkat Lunak', 3, 'Ganjil'),
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
  `kunci_jawaban` enum('A','B','C','D') NOT NULL
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
(60, '230607', '$2y$10$2E1rNMxwXdMrz/mL7uOpWecU38O4Er7AntBVBGo/3O9xT6.aiy7P2', 'mahasiswa', '2025-12-15 03:32:47', NULL, NULL),
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
(74, 'Budi12', '$2y$10$6x9ipPSjVtMxH8/ACX5pd./Zk3w4nx/COK/lRU7q1gNQDiZ69sEvK', 'mahasiswa', '2026-01-19 06:08:59', NULL, NULL),
(76, 'Purbaya123', '$2y$10$TkTRnI5/vlDfIb9IcEm96.CWENDAFYWgyyc0v5zUcntoTl963E1lu', 'mahasiswa', '2026-01-19 06:31:17', NULL, NULL),
(82, '10167021', '$2y$10$hB5.DECrOvy3y.sw1LLOBOHjiUwoTES0/in14xL4R8Oo3EgExzw6y', 'mahasiswa', '2026-01-19 07:51:28', NULL, NULL),
(84, '12451731', '$2y$10$PSNUMpXGf1A9tA2WSL5GBObifhBCMEoL4SxsazdYn729ZFKr3Natu', 'mahasiswa', '2026-01-19 07:57:05', NULL, NULL),
(85, 'Kurniawan123', '$2y$10$p8hMgb/ueIZTnYwO22xle.vgwuYm2Km7KFoIaH7jn//UL6kngzsRa', 'mahasiswa', '2026-01-20 06:34:24', NULL, NULL);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT untuk tabel `asisten`
--
ALTER TABLE `asisten`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `berita_acara`
--
ALTER TABLE `berita_acara`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `detail_jawaban_kuis`
--
ALTER TABLE `detail_jawaban_kuis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `feedback_praktikum`
--
ALTER TABLE `feedback_praktikum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `hasil_kuis`
--
ALTER TABLE `hasil_kuis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1024;

--
-- AUTO_INCREMENT untuk tabel `jurnal_praktikum`
--
ALTER TABLE `jurnal_praktikum`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `kuis`
--
ALTER TABLE `kuis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `lab`
--
ALTER TABLE `lab`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `log_presensi`
--
ALTER TABLE `log_presensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=942;

--
-- AUTO_INCREMENT untuk tabel `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT untuk tabel `materi_perkuliahan`
--
ALTER TABLE `materi_perkuliahan`
  MODIFY `id_materi` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `penggantian_inhall`
--
ALTER TABLE `penggantian_inhall`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `presensi_mahasiswa`
--
ALTER TABLE `presensi_mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1338;

--
-- AUTO_INCREMENT untuk tabel `qr_code_session`
--
ALTER TABLE `qr_code_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=147;

--
-- AUTO_INCREMENT untuk tabel `soal_kuis`
--
ALTER TABLE `soal_kuis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT untuk tabel `tiket_bantuan`
--
ALTER TABLE `tiket_bantuan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

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
