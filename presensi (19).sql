-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 27 Jan 2026 pada 05.25
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
(79, 969, '231064013', 'hadir', '14:07:01', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(80, 991, '123456789', 'hadir', '14:16:34', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(81, 1002, '231064013', 'hadir', '08:49:30', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(82, 992, '231064018', 'hadir', '11:55:33', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(83, 993, '231064018', 'hadir', '13:15:44', NULL, NULL, NULL, 'pending', NULL, NULL, NULL),
(84, 1004, '231064013', 'hadir', '09:25:53', NULL, NULL, NULL, 'pending', NULL, NULL, NULL);

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

--
-- Dumping data untuk tabel `berita_acara`
--

INSERT INTO `berita_acara` (`id`, `jadwal_id`, `kode_asisten`, `waktu_mulai_real`, `waktu_selesai_real`, `catatan`, `foto_bukti`, `created_at`) VALUES
(1, 993, '231064018', '2026-01-20 08:00:00', '2026-01-20 15:00:00', 'normal tanpa kendala', 'uploads/bap/bap_993_1769052546.jpeg', '2026-01-22 02:43:48');

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
(1, 11, 32, 'C', 1),
(2, 11, 33, 'B', 0),
(3, 11, 34, 'B', 1),
(4, 11, 35, 'D', 1),
(5, 11, 36, 'C', 1),
(6, 11, 37, 'C', 1),
(7, 11, 38, 'C', 1),
(8, 11, 39, 'B', 1),
(9, 11, 40, 'B', 1),
(10, 11, 41, 'B', 1);

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

--
-- Dumping data untuk tabel `feedback_praktikum`
--

INSERT INTO `feedback_praktikum` (`id`, `jadwal_id`, `nim`, `rating`, `komentar`, `is_anonim`, `created_at`) VALUES
(3, 993, '0000123', 5, 'test', 0, '2026-01-22 11:56:46'),
(4, 993, '10167021', 2, 'buruk banget ini ', 0, '2026-01-22 12:02:48'),
(5, 993, '0000456', 3, 'Biasa aja, yang penting kelar walaupun bikin ngantuk asistennya', 0, '2026-01-26 08:40:17'),
(6, 993, '251062026', 5, 'bagussss', 0, '2026-01-26 14:28:27');

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
(4, 2, '12451731', 10, 10, 0, '2026-01-26 14:02:34', '2026-01-26 14:05:22'),
(5, 2, '10167021', 4, 4, 6, '2026-01-26 14:01:49', '2026-01-26 14:12:50'),
(6, 2, '0000456', 10, 10, 0, '2026-01-26 14:16:44', '2026-01-26 14:18:55'),
(9, 2, '251062026', 6, 6, 4, '2026-01-26 15:04:54', '2026-01-26 15:05:27'),
(10, 2, '242062004', 5, 5, 5, '2026-01-27 10:20:09', '2026-01-27 10:20:50'),
(11, 2, '241068006', 9, 9, 1, '2026-01-27 11:10:23', '2026-01-27 11:11:24');

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
(969, 1, '2026-01-08', '08:00:00', '15:00:00', 'LAB001', 'A', 'MK002', 'Pertemuan 1 - Pengenalan', '231064013', NULL, 'materi', NULL, '2026-01-08 07:04:13'),
(970, 2, '2026-01-15', '08:00:00', '15:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 2 - Dasar', '231064013', NULL, 'materi', NULL, '2026-01-08 07:04:13'),
(971, 3, '2026-01-22', '08:00:00', '15:00:00', 'LAB001', 'A', 'MK002', 'Pertemuan 3 - Lanjutan I', '231064013', NULL, 'materi', NULL, '2026-01-08 07:04:13'),
(972, 4, '2026-01-29', '08:00:00', '15:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 4 - Lanjutan II', '231064013', NULL, 'materi', NULL, '2026-01-08 07:04:13'),
(973, 5, '2026-02-05', '08:00:00', '15:00:00', 'LAB001', 'A', 'MK002', 'Pertemuan 5 - Praktik I', '231064013', NULL, 'materi', NULL, '2026-01-08 07:04:13'),
(974, 6, '2026-02-12', '08:00:00', '15:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 6 - Praktik II', '231064013', NULL, 'materi', NULL, '2026-01-08 07:04:13'),
(975, 7, '2026-02-19', '08:00:00', '15:00:00', 'LAB001', 'A', 'MK002', 'Pertemuan 7 - Praktik III', '231064013', NULL, 'materi', NULL, '2026-01-08 07:04:13'),
(976, 8, '2026-02-26', '08:00:00', '15:00:00', 'LAB002', 'A', 'MK002', 'Pertemuan 8 - Review', '231064013', NULL, 'materi', NULL, '2026-01-08 07:04:13'),
(977, 9, '2026-03-05', '08:00:00', '15:00:00', 'LAB001', 'A', 'MK002', 'Praresponsi', '231064013', NULL, 'praresponsi', NULL, '2026-01-08 07:04:13'),
(978, 9, '2026-03-05', '15:00:00', '22:00:00', 'LAB001', 'A', 'MK002', 'Inhall', '231064013', NULL, 'inhall', NULL, '2026-01-08 07:04:13'),
(979, 10, '2026-03-12', '08:00:00', '15:00:00', 'LAB002', 'A', 'MK002', 'Responsi', '231064013', NULL, 'responsi', NULL, '2026-01-08 07:04:13'),
(980, 1, '2026-01-08', '08:00:00', '15:00:00', 'LAB003', 'B', 'MK003', 'Pertemuan 1 - Pengenalan', '23108012', NULL, 'materi', NULL, '2026-01-08 07:04:45'),
(981, 2, '2026-01-15', '08:00:00', '09:20:00', 'LAB004', 'B', 'MK003', 'Pertemuan 2 - Dasar', '23108012', NULL, 'materi', NULL, '2026-01-08 07:04:45'),
(982, 3, '2026-01-22', '08:00:00', '15:00:00', 'LAB003', 'B', 'MK003', 'Pertemuan 3 - Lanjutan I', '23108012', NULL, 'materi', NULL, '2026-01-08 07:04:45'),
(983, 4, '2026-01-29', '08:00:00', '15:00:00', 'LAB004', 'B', 'MK003', 'Pertemuan 4 - Lanjutan II', '23108012', NULL, 'materi', NULL, '2026-01-08 07:04:45'),
(984, 5, '2026-02-05', '08:00:00', '15:00:00', 'LAB003', 'B', 'MK003', 'Pertemuan 5 - Praktik I', '23108012', NULL, 'materi', NULL, '2026-01-08 07:04:45'),
(985, 6, '2026-02-12', '08:00:00', '15:00:00', 'LAB004', 'B', 'MK003', 'Pertemuan 6 - Praktik II', '23108012', NULL, 'materi', NULL, '2026-01-08 07:04:45'),
(986, 7, '2026-02-19', '08:00:00', '15:00:00', 'LAB003', 'B', 'MK003', 'Pertemuan 7 - Praktik III', '23108012', NULL, 'materi', NULL, '2026-01-08 07:04:45'),
(987, 8, '2026-02-26', '08:00:00', '15:00:00', 'LAB004', 'B', 'MK003', 'Pertemuan 8 - Review', '23108012', NULL, 'materi', NULL, '2026-01-08 07:04:45'),
(988, 9, '2026-03-05', '08:00:00', '15:00:00', 'LAB003', 'B', 'MK003', 'Praresponsi', '23108012', NULL, 'praresponsi', NULL, '2026-01-08 07:04:45'),
(989, 9, '2026-03-05', '15:00:00', '22:00:00', 'LAB003', 'B', 'MK003', 'Inhall', '23108012', NULL, 'inhall', NULL, '2026-01-08 07:04:45'),
(990, 10, '2026-03-12', '08:00:00', '15:00:00', 'LAB004', 'B', 'MK003', 'Responsi', '23108012', NULL, 'responsi', NULL, '2026-01-08 07:04:45'),
(991, 1, '2026-01-08', '08:00:00', '15:00:00', 'LAB002', 'E', 'STP2503', 'Pertemuan 1 - Pengenalan', '231064018', '123456789', 'materi', NULL, '2026-01-08 07:06:16'),
(992, 2, '2026-01-15', '08:00:00', '15:00:00', 'LAB001', 'E', 'STP2503', 'Pertemuan 2 - Dasar', '231064018', '123456789', 'materi', NULL, '2026-01-08 07:06:16'),
(993, 3, '2026-01-20', '08:00:00', '15:00:00', 'LAB004', 'E', 'STP2503', 'Pertemuan 3 - Lanjutan I', '231064018', '123456789', 'materi', NULL, '2026-01-08 07:06:16'),
(994, 4, '2026-01-29', '08:00:00', '15:00:00', 'LAB001', 'E', 'STP2503', 'Pertemuan 4 - Lanjutan II', '231064018', '123456789', 'materi', NULL, '2026-01-08 07:06:16'),
(995, 5, '2026-02-05', '08:00:00', '15:00:00', 'LAB002', 'E', 'STP2503', 'Pertemuan 5 - Praktik I', '231064018', '123456789', 'materi', NULL, '2026-01-08 07:06:16'),
(996, 6, '2026-02-12', '08:00:00', '15:00:00', 'LAB001', 'E', 'STP2503', 'Pertemuan 6 - Praktik II', '231064018', '123456789', 'materi', NULL, '2026-01-08 07:06:16'),
(997, 7, '2026-02-19', '08:00:00', '15:00:00', 'LAB002', 'E', 'STP2503', 'Pertemuan 7 - Praktik III', '231064018', '123456789', 'materi', NULL, '2026-01-08 07:06:16'),
(998, 8, '2026-02-26', '08:00:00', '15:00:00', 'LAB001', 'E', 'STP2503', 'Pertemuan 8 - Review', '231064018', '123456789', 'materi', NULL, '2026-01-08 07:06:16'),
(999, 9, '2026-03-05', '08:00:00', '15:00:00', 'LAB004', 'E', 'STP2503', 'Praresponsi', '231064018', '123456789', 'praresponsi', NULL, '2026-01-08 07:06:16'),
(1000, 9, '2026-03-05', '15:00:00', '22:00:00', 'LAB004', 'E', 'STP2503', 'Inhall', '231064018', '123456789', 'inhall', NULL, '2026-01-08 07:06:16'),
(1001, 10, '2026-03-12', '08:00:00', '15:00:00', 'LAB001', 'E', 'STP2503', 'Responsi', '231064018', '123456789', 'responsi', NULL, '2026-01-08 07:06:16'),
(1002, 1, '2026-01-09', '08:48:00', '13:00:00', 'LAB001', 'E', 'STP2503', 'Pertemuan 1 - Pengenalan', '231064013', '231064018', 'materi', NULL, '2026-01-09 01:46:19'),
(1003, 2, '2026-01-16', '08:48:00', '13:00:00', 'LAB002', 'E', 'STP2503', 'Pertemuan 2 - Dasar', '231064013', '231064018', 'materi', NULL, '2026-01-09 01:46:19'),
(1004, 3, '2026-01-23', '08:48:00', '13:00:00', 'LAB004', 'E', 'STP2503', 'Pertemuan 3 - Lanjutan I', '231064013', '231064018', 'materi', NULL, '2026-01-09 01:46:19'),
(1005, 4, '2026-01-30', '08:48:00', '13:00:00', 'LAB001', 'E', 'STP2503', 'Pertemuan 4 - Lanjutan II', '231064013', '231064018', 'materi', NULL, '2026-01-09 01:46:19'),
(1006, 5, '2026-02-06', '08:48:00', '13:00:00', 'LAB002', 'E', 'STP2503', 'Pertemuan 5 - Praktik I', '231064013', '231064018', 'materi', NULL, '2026-01-09 01:46:19'),
(1007, 6, '2026-02-13', '08:48:00', '13:00:00', 'LAB004', 'E', 'STP2503', 'Pertemuan 6 - Praktik II', '231064013', '231064018', 'materi', NULL, '2026-01-09 01:46:19'),
(1008, 7, '2026-02-20', '08:48:00', '13:00:00', 'LAB001', 'E', 'STP2503', 'Pertemuan 7 - Praktik III', '231064013', '231064018', 'materi', NULL, '2026-01-09 01:46:19'),
(1009, 8, '2026-02-27', '08:48:00', '13:00:00', 'LAB002', 'E', 'STP2503', 'Pertemuan 8 - Review', '231064013', '231064018', 'materi', NULL, '2026-01-09 01:46:19'),
(1010, 9, '2026-03-06', '08:48:00', '13:00:00', 'LAB004', 'E', 'STP2503', 'Praresponsi', '231064013', '231064018', 'praresponsi', NULL, '2026-01-09 01:46:19'),
(1011, 9, '2026-03-06', '13:00:00', '17:12:00', 'LAB004', 'E', 'STP2503', 'Inhall', '231064013', '231064018', 'inhall', NULL, '2026-01-09 01:46:19'),
(1012, 10, '2026-03-13', '08:48:00', '13:00:00', 'LAB001', 'E', 'STP2503', 'Responsi', '231064013', '231064018', 'responsi', NULL, '2026-01-09 01:46:19');

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

--
-- Dumping data untuk tabel `jurnal_praktikum`
--

INSERT INTO `jurnal_praktikum` (`id`, `jadwal_id`, `nim`, `kegiatan`, `hasil`, `created_at`) VALUES
(1, 993, '0000123', 'Statistika adalah cabang ilmu matematika yang mempelajari cara mengumpulkan, mengolah, menyajikan, menganalisis, dan menafsirkan data. Data yang dikumpulkan bisa berupa angka maupun kategori, yang diperoleh dari hasil pengamatan, survei, atau eksperimen. Dalam statistika, data biasanya disajikan dalam bentuk tabel, diagram, grafik, atau ukuran-ukuran tertentu seperti rata-rata, median, dan modus agar lebih mudah dipahami. Dengan penyajian yang tepat, informasi dari data dapat dibaca secara jelas dan tidak menimbulkan salah tafsir.\\r\\n\\r\\nSelain itu, statistika juga berperan penting dalam pengambilan keputusan di berbagai bidang, seperti pendidikan, ekonomi, kesehatan, dan teknologi. Melalui analisis data, statistika membantu menarik kesimpulan, memprediksi kejadian di masa depan, serta mengevaluasi suatu permasalahan berdasarkan fakta. Oleh karena itu, pemahaman statistika sangat diperlukan agar seseorang mampu berpikir logis, kritis, dan objektif dalam menilai informasi yang ada di sekitarnya.', 'Kesimpulannya, statistika merupakan ilmu yang sangat penting karena membantu manusia dalam mengolah dan memahami data secara sistematis. Dengan statistika, data yang awalnya tidak bermakna dapat diubah menjadi informasi yang berguna untuk menarik kesimpulan dan mendukung pengambilan keputusan yang tepat berdasarkan fakta.', '2026-01-21 17:49:58');

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

--
-- Dumping data untuk tabel `kuis`
--

INSERT INTO `kuis` (`id`, `jadwal_id`, `judul`, `deskripsi`, `durasi_menit`, `metode_penilaian`, `bobot_per_soal`, `status`, `created_at`) VALUES
(1, 993, 'Konsep Dasar Statistika', 'Konsep Dasar Statistika adalah materi yang membahas pengertian statistika, tujuan penggunaannya, serta peran statistika dalam mengolah dan menganalisis data. Dalam konsep dasar ini, dipelajari bagaimana data dikumpulkan, disusun, disajikan, dan diinterpretasikan agar dapat menghasilkan informasi yang bermakna dan mudah dipahami.\\r\\n\\r\\nMateri ini juga mencakup jenis-jenis data, cara penyajian data dalam bentuk tabel dan diagram, serta pengenalan ukuran pemusatan data seperti rata-rata (mean), nilai tengah (median), dan nilai yang paling sering muncul (modus). Dengan memahami konsep dasar statistika, siswa diharapkan mampu membaca data dengan benar dan menggunakan statistika untuk membantu pengambilan keputusan dalam kehidupan sehari-hari.', 15, 'skala_100', 0, 'selesai', '2026-01-26 02:56:21'),
(2, 993, 'Konsep Dasar Statistika', 'Konsep Dasar Statistika adalah materi yang membahas pengertian statistika, tujuan penggunaannya, serta peran statistika dalam mengolah dan menganalisis data. Dalam konsep dasar ini, dipelajari bagaimana data dikumpulkan, disusun, disajikan, dan diinterpretasikan agar dapat menghasilkan informasi yang bermakna dan mudah dipahami.\\r\\n\\r\\nMateri ini juga mencakup jenis-jenis data, cara penyajian data dalam bentuk tabel dan diagram, serta pengenalan ukuran pemusatan data seperti rata-rata (mean), nilai tengah (median), dan nilai yang paling sering muncul (modus). Dengan memahami konsep dasar statistika, siswa diharapkan mampu membaca data dengan benar dan menggunakan statistika untuk membantu pengambilan keputusan dalam kehidupan sehari-hari.', 15, 'poin_murni', 0, 'aktif', '2026-01-26 03:35:38');

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

--
-- Dumping data untuk tabel `log_presensi`
--

INSERT INTO `log_presensi` (`id`, `user_id`, `aksi`, `tabel`, `id_record`, `detail`, `created_at`) VALUES
(681, 57, 'GENERATE_QR', 'qr_code_session', 139, 'QR Code untuk jadwal #969, expired: 2026-01-08 15:00:00', '2026-01-08 07:07:01'),
(682, NULL, 'PRESENSI_QR', 'presensi_mahasiswa', 969, 'Mahasiswa 12072010 presensi via QR di Laboratorium Basis Data', '2026-01-08 07:07:11'),
(683, 64, 'LOGIN', 'users', 64, 'User login berhasil sebagai mahasiswa', '2026-01-08 07:07:40'),
(684, NULL, 'PRESENSI_QR', 'presensi_mahasiswa', 969, 'Mahasiswa 12345678 presensi via QR di Laboratorium Basis Data', '2026-01-08 07:12:13'),
(685, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-01-08 07:12:39'),
(686, 60, 'PENGAJUAN_IZIN', 'penggantian_inhall', 26, 'Mahasiswa 230607 mengajukan sakit (pending approval): sakit bro tolong ya aku izin', '2026-01-08 07:14:25'),
(687, 57, 'APPROVE_IZIN', 'penggantian_inhall', 26, 'Asisten 231064013 menyetujui sakit mahasiswa 230607', '2026-01-08 07:14:57'),
(688, 68, 'LOGIN', 'users', 68, 'User login berhasil sebagai asisten', '2026-01-08 07:16:17'),
(689, 68, 'GENERATE_QR', 'qr_code_session', 140, 'QR Code untuk jadwal #991, expired: 2026-01-08 15:00:00', '2026-01-08 07:16:34'),
(690, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 24346554 - hadir', '2026-01-08 07:16:42'),
(691, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 251062026 - hadir', '2026-01-08 07:16:43'),
(692, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 765434567 - hadir', '2026-01-08 07:16:44'),
(693, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 241068006 - hadir', '2026-01-08 07:16:45'),
(694, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 242062004 - hadir', '2026-01-08 07:16:45'),
(695, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 241064004 - hadir', '2026-01-08 07:16:46'),
(696, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 251062025 - hadir', '2026-01-08 07:16:47'),
(697, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 241064009 - hadir', '2026-01-08 07:16:47'),
(698, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 9532753 - hadir', '2026-01-08 07:16:48'),
(699, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 241068005 - hadir', '2026-01-08 07:16:49'),
(700, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 242062004 - hadir', '2026-01-08 07:16:50'),
(701, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 242062001 - hadir', '2026-01-08 07:16:51'),
(702, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 241064013 - hadir', '2026-01-08 07:16:53'),
(703, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 241064002 - hadir', '2026-01-08 07:16:55'),
(704, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 241067010 - hadir', '2026-01-08 07:16:56'),
(705, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 241064001 - hadir', '2026-01-08 07:16:57'),
(706, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 241067011 - hadir', '2026-01-08 07:16:59'),
(707, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 251062022 - alpha', '2026-01-08 07:17:04'),
(708, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 211063024 - alpha', '2026-01-08 07:17:06'),
(709, 68, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 991, 'Presensi manual: 241064007 - alpha', '2026-01-08 07:17:09'),
(710, 49, 'LOGIN', 'users', 49, 'User login berhasil sebagai mahasiswa', '2026-01-08 07:21:07'),
(711, 49, 'PENGAJUAN_IZIN', 'penggantian_inhall', 27, 'Mahasiswa 241064014 mengajukan izin (pending approval): gak bisa masuk karena ada acara keluarga maaf', '2026-01-08 07:21:35'),
(712, 68, 'APPROVE_IZIN', 'penggantian_inhall', 27, 'Asisten 123456789 menyetujui izin mahasiswa 241064014', '2026-01-08 07:21:44'),
(713, 46, 'LOGIN', 'users', 46, 'User login berhasil sebagai mahasiswa', '2026-01-08 07:22:44'),
(714, 46, 'PENGAJUAN_IZIN', 'penggantian_inhall', 28, 'Mahasiswa 241064008 mengajukan sakit (pending approval): sakit habis kecelakaan jadi gak bisa masuk', '2026-01-08 07:23:06'),
(715, 68, 'APPROVE_IZIN', 'penggantian_inhall', 28, 'Asisten 123456789 menyetujui sakit mahasiswa 241064008', '2026-01-08 07:24:38'),
(716, 39, 'LOGIN', 'users', 39, 'User login berhasil sebagai mahasiswa', '2026-01-08 07:26:59'),
(717, 39, 'PENGAJUAN_IZIN', 'penggantian_inhall', 29, 'Mahasiswa 251062022 mengajukan sakit (pending approval): malas masuk kwkkw', '2026-01-08 07:27:14'),
(718, 68, 'REJECT_IZIN', 'penggantian_inhall', 29, 'Asisten 123456789 menolak sakit mahasiswa 251062022: gak bisa anda bohong, saya alpha kamu\\\\r\\\\n', '2026-01-08 07:28:14'),
(719, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-09 01:25:38'),
(720, 68, 'LOGIN', 'users', 68, 'User login berhasil sebagai asisten', '2026-01-09 01:27:54'),
(721, 56, 'LOGIN', 'users', 56, 'User login berhasil sebagai mahasiswa', '2026-01-09 01:43:23'),
(722, 57, 'LOGIN', 'users', 57, 'User login berhasil sebagai asisten', '2026-01-09 01:48:16'),
(723, 57, 'GENERATE_QR', 'qr_code_session', 141, 'QR Code untuk jadwal #1002, expired: 2026-01-09 13:00:00', '2026-01-09 01:49:30'),
(724, NULL, 'PRESENSI_QR', 'presensi_mahasiswa', 1002, 'Mahasiswa 211063024 presensi via QR di Laboratorium Basis Data', '2026-01-09 01:49:45'),
(725, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 24346554 - hadir', '2026-01-09 01:50:28'),
(726, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 251062026 - hadir', '2026-01-09 01:50:29'),
(727, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 765434567 - hadir', '2026-01-09 01:50:31'),
(728, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241068006 - hadir', '2026-01-09 01:50:31'),
(729, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 242062004 - hadir', '2026-01-09 01:50:32'),
(730, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241064004 - izin', '2026-01-09 01:50:34'),
(731, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 251062025 - izin', '2026-01-09 01:50:36'),
(732, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241064009 - sakit', '2026-01-09 01:50:37'),
(733, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 9532753 - sakit', '2026-01-09 01:50:38'),
(734, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241068005 - hadir', '2026-01-09 01:50:41'),
(735, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241064013 - hadir', '2026-01-09 01:50:43'),
(736, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 242062001 - hadir', '2026-01-09 01:50:45'),
(737, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241064002 - hadir', '2026-01-09 01:50:47'),
(738, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241067010 - hadir', '2026-01-09 01:50:49'),
(739, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241067011 - hadir', '2026-01-09 01:50:51'),
(740, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241064001 - hadir', '2026-01-09 01:50:53'),
(741, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 251062022 - hadir', '2026-01-09 01:50:55'),
(742, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241064007 - alpha', '2026-01-09 01:51:01'),
(743, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241064008 - alpha', '2026-01-09 01:51:03'),
(744, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241064014 - alpha', '2026-01-09 01:51:05'),
(745, 71, 'LOGIN', 'users', 71, 'User login berhasil sebagai mahasiswa', '2026-01-09 01:55:05'),
(746, 72, 'LOGIN', 'users', 72, 'User login berhasil sebagai mahasiswa', '2026-01-09 01:57:35'),
(747, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241064007 - alpha', '2026-01-09 04:20:17'),
(748, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241064008 - alpha', '2026-01-09 04:32:43'),
(749, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 241064014 - alpha', '2026-01-09 04:32:46'),
(750, 57, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 1002, 'Presensi manual: 070772 - alpha', '2026-01-09 04:32:49'),
(751, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-15 01:54:31'),
(752, 66, 'LOGIN', 'users', 66, 'User login berhasil sebagai mahasiswa', '2026-01-15 02:58:03'),
(753, 66, 'LOGIN', 'users', 66, 'User login berhasil sebagai mahasiswa', '2026-01-15 04:52:49'),
(754, 41, 'LOGIN', 'users', 41, 'User login berhasil sebagai mahasiswa', '2026-01-15 04:54:18'),
(755, 58, 'LOGIN', 'users', 58, 'User login berhasil sebagai asisten', '2026-01-15 04:55:28'),
(756, 58, 'GENERATE_QR', 'qr_code_session', 142, 'QR Code untuk jadwal #992, expired: 2026-01-15 15:00:00', '2026-01-15 04:55:33'),
(757, NULL, 'PRESENSI_QR', 'presensi_mahasiswa', 992, 'Mahasiswa 251062026 presensi via QR di Laboratorium Basis Data', '2026-01-15 04:55:49'),
(758, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 24346554 - hadir', '2026-01-15 05:14:47'),
(759, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 765434567 - hadir', '2026-01-15 05:14:48'),
(760, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 241068006 - hadir', '2026-01-15 05:14:50'),
(761, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 242062004 - hadir', '2026-01-15 05:14:50'),
(762, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 241064004 - hadir', '2026-01-15 05:14:51'),
(763, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 251062025 - hadir', '2026-01-15 05:14:53'),
(764, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 241064009 - hadir', '2026-01-15 05:14:54'),
(765, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 9532753 - hadir', '2026-01-15 05:14:56'),
(766, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 241068005 - hadir', '2026-01-15 05:14:57'),
(767, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 242062001 - hadir', '2026-01-15 05:14:58'),
(768, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 241064013 - hadir', '2026-01-15 05:14:59'),
(769, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 241064002 - hadir', '2026-01-15 05:15:01'),
(770, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 241067010 - hadir', '2026-01-15 05:15:05'),
(771, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 241067011 - hadir', '2026-01-15 05:15:09'),
(772, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 241064001 - hadir', '2026-01-15 05:15:11'),
(773, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 992, 'Presensi manual: 251062022 - hadir', '2026-01-15 05:15:12'),
(774, 66, 'LOGIN', 'users', 66, 'User login berhasil sebagai mahasiswa', '2026-01-19 03:45:39'),
(775, 73, 'LOGIN', 'users', 73, 'User login berhasil sebagai mahasiswa', '2026-01-19 03:50:11'),
(776, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-19 06:10:57'),
(777, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-19 06:17:04'),
(778, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-19 06:17:21'),
(779, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-19 06:17:43'),
(780, 76, 'LOGIN', 'users', 76, 'User login berhasil sebagai mahasiswa', '2026-01-19 06:32:02'),
(781, 76, 'LOGIN', 'users', 76, 'User login berhasil sebagai mahasiswa', '2026-01-19 06:32:46'),
(782, 68, 'LOGIN', 'users', 68, 'User login berhasil sebagai asisten', '2026-01-20 02:10:43'),
(783, 58, 'LOGIN', 'users', 58, 'User login berhasil sebagai asisten', '2026-01-20 02:14:33'),
(784, 76, 'LOGIN', 'users', 76, 'User login berhasil sebagai mahasiswa', '2026-01-20 04:57:41'),
(785, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-20 05:04:07'),
(786, 58, 'GENERATE_QR', 'qr_code_session', 143, 'QR Code untuk jadwal #993, expired: 2026-01-20 15:00:00', '2026-01-20 06:15:44'),
(787, NULL, 'PRESENSI_QR', 'presensi_mahasiswa', 993, 'Mahasiswa 0000456 presensi via QR di Laboratorium Statistika', '2026-01-20 06:16:07'),
(788, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 24346554 - hadir', '2026-01-20 06:17:14'),
(789, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 12451731 - hadir', '2026-01-20 06:17:16'),
(790, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 10167021 - hadir', '2026-01-20 06:17:17'),
(791, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 251062026 - hadir', '2026-01-20 06:17:18'),
(792, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 0000123 - hadir', '2026-01-20 06:17:19'),
(793, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 765434567 - hadir', '2026-01-20 06:17:20'),
(794, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 241068006 - hadir', '2026-01-20 06:17:21'),
(795, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 242062004 - hadir', '2026-01-20 06:17:22'),
(796, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 241064004 - hadir', '2026-01-20 06:17:23'),
(797, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 241068005 - hadir', '2026-01-20 06:17:26'),
(798, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 242062001 - hadir', '2026-01-20 06:17:28'),
(799, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 241064002 - hadir', '2026-01-20 06:17:32'),
(800, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 241067010 - hadir', '2026-01-20 06:17:34'),
(801, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 241067011 - hadir', '2026-01-20 06:17:36'),
(802, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 241064001 - hadir', '2026-01-20 06:17:38'),
(803, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 211063024 - hadir', '2026-01-20 06:17:41'),
(804, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 241064007 - hadir', '2026-01-20 06:17:44'),
(805, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 241064008 - hadir', '2026-01-20 06:17:46'),
(806, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 241064014 - hadir', '2026-01-20 06:17:48'),
(807, 58, 'PRESENSI_MANUAL', 'presensi_mahasiswa', 993, 'Presensi manual: 070772 - hadir', '2026-01-20 06:17:50'),
(808, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-20 06:32:01'),
(809, 85, 'LOGIN', 'users', 85, 'User login berhasil sebagai mahasiswa', '2026-01-20 06:56:13'),
(810, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-21 02:04:52'),
(811, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-21 02:06:06'),
(812, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-21 02:31:19'),
(813, 74, 'KIRIM_BANTUAN', 'tiket_bantuan', 1, 'Mengirim tiket: bug pada menu profil mahasiswa', '2026-01-21 02:59:19'),
(814, 38, 'BALAS_TIKET', 'tiket_bantuan', 1, 'Admin membalas tiket dari NIM 0000123', '2026-01-21 03:06:05'),
(815, 74, 'KIRIM_BANTUAN', 'tiket_bantuan', 2, 'Mengirim tiket: Menu Inhall', '2026-01-21 03:09:02'),
(816, 38, 'BALAS_TIKET', 'tiket_bantuan', 2, 'Admin membalas tiket dari NIM 0000123', '2026-01-21 03:16:33'),
(817, 38, 'BALAS_TIKET', 'tiket_bantuan', 2, 'Admin membalas tiket dari NIM 0000123', '2026-01-21 03:36:36'),
(818, 38, 'HAPUS_TIKET', 'tiket_bantuan', 2, 'Admin menghapus tiket bantuan', '2026-01-21 04:03:25'),
(819, 38, 'HAPUS_TANGGAPAN', 'tiket_bantuan', 1, 'Admin menghapus tanggapan tiket', '2026-01-21 04:06:19'),
(820, 38, 'BALAS_TIKET', 'tiket_bantuan', 1, 'Admin membalas tiket dari NIM 0000123', '2026-01-21 04:06:39'),
(821, 74, 'KIRIM_BANTUAN', 'tiket_bantuan', 3, 'Mengirim tiket: Menu Pusat Bantuan (Tanpa Lampiran)', '2026-01-21 04:16:39'),
(822, 38, 'HAPUS_TIKET', 'tiket_bantuan', 3, 'Admin menghapus tiket bantuan', '2026-01-21 04:20:31'),
(823, 74, 'KIRIM_BANTUAN', 'tiket_bantuan', 4, 'Mengirim tiket: Menu Pusat Bantuan', '2026-01-21 04:20:59'),
(824, 38, 'BALAS_TIKET', 'tiket_bantuan', 4, 'Admin membalas tiket dari NIM 0000123', '2026-01-21 04:50:18'),
(825, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-21 05:53:41'),
(826, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-21 07:47:20'),
(827, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-21 07:48:42'),
(828, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-21 08:10:47'),
(829, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-21 08:17:30'),
(830, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-21 12:24:57'),
(831, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-21 12:34:39'),
(832, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-21 12:44:27'),
(833, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-21 12:45:39'),
(834, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-21 12:54:54'),
(835, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-21 12:55:07'),
(836, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-21 13:01:36'),
(837, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-21 13:04:05'),
(838, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-21 13:05:14'),
(839, 74, 'KIRIM_BANTUAN', 'tiket_bantuan', 5, 'Mengirim tiket: Lupa Password', '2026-01-21 13:06:30'),
(840, 38, 'BALAS_TIKET', 'tiket_bantuan', 5, 'Admin membalas tiket dari NIM 0000123', '2026-01-21 13:08:42'),
(841, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-21 13:09:58'),
(842, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-21 13:12:39'),
(843, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-21 13:14:42'),
(844, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-01-21 13:15:58'),
(845, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-21 13:27:16'),
(846, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-01-21 14:53:45'),
(847, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-21 15:13:47'),
(848, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-21 17:40:32'),
(849, 58, 'LOGIN', 'users', 58, 'User login berhasil sebagai asisten', '2026-01-21 17:51:20'),
(853, 74, 'PENGAJUAN_IZIN', 'penggantian_inhall', 32, 'Mahasiswa 0000123 mengajukan sakit (pending approval): Izin menyampaikan, pada hari ini saya tidak dapat mengikuti kegiatan seperti biasa karena sedang mengalami sakit kepala yang cukup mengganggu kondisi tubuh saya. Untuk sementara, saya perlu beristirahat agar kondisi bisa kembali membaik. Oleh karena itu, saya mohon izin untuk tidak mengikuti kegiatan hari ini. Atas perhatian dan pengertiannya, saya ucapkan terima kasih.', '2026-01-21 18:24:41'),
(854, 74, 'HAPUS_PENGAJUAN', 'penggantian_inhall', 32, 'Mahasiswa 0000123 menghapus pengajuan izin/sakit', '2026-01-22 00:38:40'),
(855, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-22 01:13:52'),
(856, 58, 'LOGIN', 'users', 58, 'User login berhasil sebagai asisten', '2026-01-22 01:15:43'),
(857, 84, 'LOGIN', 'users', 84, 'User login berhasil sebagai mahasiswa', '2026-01-22 01:17:03'),
(858, 84, 'PENGAJUAN_IZIN', 'penggantian_inhall', 33, 'Mahasiswa 12451731 mengajukan izin (pending approval): mau nyoba test notifikasi whatsapp apakah berfungsi dengan baik atau tidak, maka dari itu dari pada eror di test dulu fungsinya', '2026-01-22 01:18:11'),
(859, 84, 'HAPUS_PENGAJUAN', 'penggantian_inhall', 33, 'Mahasiswa 12451731 menghapus pengajuan izin/sakit', '2026-01-22 01:19:16'),
(860, 84, 'PENGAJUAN_IZIN', 'penggantian_inhall', 34, 'Mahasiswa 12451731 mengajukan izin (pending approval): mau test notifikasi whatsapp ini berfungsi atau tidak, kalau tidak berfungsi saya bisa memperbaikin fungsinya dengan normal', '2026-01-22 01:20:00'),
(861, 84, 'HAPUS_PENGAJUAN', 'penggantian_inhall', 34, 'Mahasiswa 12451731 menghapus pengajuan izin/sakit', '2026-01-22 01:21:07'),
(862, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-22 04:28:25'),
(863, 82, 'LOGIN', 'users', 82, 'User login berhasil sebagai mahasiswa', '2026-01-22 04:39:03'),
(864, 58, 'LOGIN', 'users', 58, 'User login berhasil sebagai asisten', '2026-01-22 04:49:53'),
(865, 82, 'BERI_ULASAN', 'feedback_praktikum', 1, 'Rating: 1 bintang', '2026-01-22 04:50:42'),
(866, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-22 04:51:47'),
(867, 74, 'BERI_ULASAN', 'feedback_praktikum', 2, 'Rating: 1 bintang', '2026-01-22 04:52:03'),
(868, 74, 'BERI_ULASAN', 'feedback_praktikum', 3, 'Rating: 5 bintang', '2026-01-22 04:56:46'),
(869, 82, 'LOGIN', 'users', 82, 'User login berhasil sebagai mahasiswa', '2026-01-22 05:02:24'),
(870, 82, 'BERI_ULASAN', 'feedback_praktikum', 4, 'Rating: 2 bintang', '2026-01-22 05:02:49'),
(871, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-23 01:29:23'),
(872, 57, 'LOGIN', 'users', 57, 'User login berhasil sebagai asisten', '2026-01-23 01:29:43'),
(873, 84, 'LOGIN', 'users', 84, 'User login berhasil sebagai mahasiswa', '2026-01-23 01:30:04'),
(874, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-23 02:01:41'),
(875, 57, 'GENERATE_QR', 'qr_code_session', 144, 'QR Code untuk jadwal #1004, expired: 2026-01-23 13:00:00', '2026-01-23 02:25:53'),
(876, NULL, 'PRESENSI_QR', 'presensi_mahasiswa', 1004, 'Mahasiswa 0000123 presensi via QR di Laboratorium Statistika', '2026-01-23 02:26:05'),
(877, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-23 03:39:58'),
(878, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-23 03:40:43'),
(879, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-23 03:45:15'),
(880, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-23 05:26:59'),
(881, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-23 05:27:28'),
(882, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-23 05:50:59'),
(883, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-23 05:56:28'),
(884, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-23 06:16:48'),
(885, 74, 'LOGIN', 'users', 74, 'User login berhasil sebagai mahasiswa', '2026-01-23 06:38:23'),
(886, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-26 01:23:16'),
(887, 76, 'LOGIN', 'users', 76, 'User login berhasil sebagai mahasiswa', '2026-01-26 01:31:23'),
(888, 76, 'BERI_ULASAN', 'feedback_praktikum', 5, 'Rating: 3 bintang', '2026-01-26 01:40:17'),
(889, 76, 'KIRIM_BANTUAN', 'tiket_bantuan', 6, 'Mengirim tiket: Jurnal harian', '2026-01-26 01:41:49'),
(890, 38, 'BALAS_TIKET', 'tiket_bantuan', 6, 'Admin membalas tiket dari NIM 0000456', '2026-01-26 01:42:39'),
(891, 38, 'BALAS_TIKET', 'tiket_bantuan', 6, 'Admin membalas tiket dari NIM 0000456', '2026-01-26 01:44:54'),
(892, 38, 'BALAS_TIKET', 'tiket_bantuan', 6, 'Admin membalas tiket dari NIM 0000456', '2026-01-26 01:45:27'),
(893, 68, 'LOGIN', 'users', 68, 'User login berhasil sebagai asisten', '2026-01-26 01:59:08'),
(894, 38, 'BROADCAST_WA', 'system', 0, 'Mengirim broadcast ke semua_asisten (4 sukses)', '2026-01-26 02:40:01'),
(895, 38, 'BROADCAST_WA', 'system', 0, 'Mengirim broadcast ke semua_asisten (4 sukses)', '2026-01-26 02:48:09'),
(896, 84, 'LOGIN', 'users', 84, 'User login berhasil sebagai mahasiswa', '2026-01-26 02:52:44'),
(897, 84, 'KERJAKAN_KUIS', 'hasil_kuis', 1, 'Nilai: 33.333333333333', '2026-01-26 03:17:17'),
(898, 85, 'LOGIN', 'users', 85, 'User login berhasil sebagai mahasiswa', '2026-01-26 03:19:43'),
(899, 85, 'KERJAKAN_KUIS', 'hasil_kuis', 2, 'Nilai: 33.333333333333', '2026-01-26 03:21:26'),
(900, 82, 'LOGIN', 'users', 82, 'User login berhasil sebagai mahasiswa', '2026-01-26 03:28:04'),
(901, 82, 'KERJAKAN_KUIS', 'hasil_kuis', 3, 'Nilai: 33.333333333333', '2026-01-26 03:28:15'),
(902, 84, 'KERJAKAN_KUIS', 'hasil_kuis', 4, 'Nilai: 10', '2026-01-26 07:05:22'),
(903, 82, 'KERJAKAN_KUIS', 'hasil_kuis', 5, 'Nilai: 4', '2026-01-26 07:12:50'),
(904, 82, 'LOGIN', 'users', 82, 'User login berhasil sebagai mahasiswa', '2026-01-26 07:14:58'),
(905, 41, 'LOGIN', 'users', 41, 'User login berhasil sebagai mahasiswa', '2026-01-26 07:15:27'),
(906, 76, 'LOGIN', 'users', 76, 'User login berhasil sebagai mahasiswa', '2026-01-26 07:16:29'),
(907, 76, 'KERJAKAN_KUIS', 'hasil_kuis', 6, 'Nilai: 10', '2026-01-26 07:18:56'),
(908, 41, 'BERI_ULASAN', 'feedback_praktikum', 6, 'Rating: 5 bintang', '2026-01-26 07:28:27'),
(909, 41, 'KERJAKAN_KUIS', 'hasil_kuis', 7, 'Nilai: 0', '2026-01-26 07:43:37'),
(910, 41, 'KERJAKAN_KUIS', 'hasil_kuis', 8, 'Nilai: 0', '2026-01-26 08:04:37'),
(911, 41, 'KERJAKAN_KUIS', 'hasil_kuis', 9, 'Nilai: 6', '2026-01-26 08:05:27'),
(912, 38, 'LOGIN', 'users', 38, 'User login berhasil sebagai admin', '2026-01-27 02:56:43'),
(913, 60, 'LOGIN', 'users', 60, 'User login berhasil sebagai mahasiswa', '2026-01-27 02:57:35'),
(914, 57, 'LOGIN', 'users', 57, 'User login berhasil sebagai asisten', '2026-01-27 03:08:30'),
(915, 58, 'LOGIN', 'users', 58, 'User login berhasil sebagai asisten', '2026-01-27 03:10:32'),
(916, 55, 'LOGIN', 'users', 55, 'User login berhasil sebagai mahasiswa', '2026-01-27 03:12:12'),
(917, 55, 'LOGIN', 'users', 55, 'User login berhasil sebagai mahasiswa', '2026-01-27 03:14:59'),
(918, 55, 'KERJAKAN_KUIS', 'hasil_kuis', 10, 'Nilai: 5', '2026-01-27 03:20:50'),
(919, 55, 'LOGIN', 'users', 55, 'User login berhasil sebagai mahasiswa', '2026-01-27 04:06:24'),
(920, 53, 'LOGIN', 'users', 53, 'User login berhasil sebagai mahasiswa', '2026-01-27 04:10:08'),
(921, 53, 'KERJAKAN_KUIS', 'hasil_kuis', 10, 'Nilai: 9', '2026-01-27 04:11:24');

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
  `tanggal_daftar` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `nim`, `user_id`, `nama`, `kode_kelas`, `prodi`, `no_hp`, `foto`, `tanggal_daftar`, `status`) VALUES
(28, '251062022', 39, 'NUNUT SITUMORANG', 'E', 'Statistik S1', '0', 'uploads/profil/mhs_251062022_1765869042.png', '2025-12-12 03:55:00', 'aktif'),
(29, '251062025', 40, 'FLORENS SANTA AGUSTIN .S', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(30, '251062026', 41, 'ARIZA MUHAIMIN .Z', 'E', 'Statistik S1', '083841426400', 'uploads/profil/mhs_251062026_1765519581.png', '2025-12-12 03:55:00', 'aktif'),
(31, '241064001', 42, 'NATALIA ALBERGATI NIPU', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(32, '241064002', 43, 'MAGDALENA B. S. SOBANG', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(33, '241064004', 44, 'ERA AMALIA PUTRI', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(34, '241064007', 45, 'ROSWITA ASMELITA NESTI .P', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(35, '241064008', 46, 'SANRY FRIDOLING OKI NAAT', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(36, '241064009', 47, 'FREDERICK HARDIMAN', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(37, '241064013', 48, 'KEZIA GREDALYA SITANIA', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(38, '241064014', 49, 'SEPTI NURELISA', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(39, '241067010', 50, 'MIKAELA MAYANTRIS', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(40, '241067011', 51, 'MUHAMMAD KHOLIK KHOIRI', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(41, '241068005', 52, 'IKHSANUDDIN MUKHLISH', 'E', 'Statistik S1', '0', 'uploads/profil/mhs_241068005_1765870189.jpg', '2025-12-12 03:55:00', 'aktif'),
(42, '241068006', 53, 'CORAZON RATU MARA', 'E', 'Statistik S1', '0', 'uploads/profil/mhs_241068006_1769487195.jpg', '2025-12-12 03:55:00', 'aktif'),
(43, '242062001', 54, 'KAMELIA', 'E', 'Statistik S1', '0', NULL, '2025-12-12 03:55:00', 'aktif'),
(44, '242062004', 55, 'DINA SITTONGA', 'E', 'Statistik S1', '0', 'uploads/profil/mhs_242062004_1769483954.jpg', '2025-12-12 03:55:00', 'aktif'),
(45, '211063024', 56, 'OLIN PUTRA PRATAMA', 'E', 'Statistik S1', '0', 'uploads/profil/mhs_211063024_1765769283.png', '2025-12-12 03:55:00', 'aktif'),
(46, '230607', 60, 'Nanda Hanif Abyan Bromo Putra', 'A', 'Pemrogaman', '083841426400', 'uploads/profil/mhs_230607_1767856437.png', '2025-12-15 03:32:00', 'aktif'),
(47, '24346554', 61, 'AAAA', 'E', 'Statistik S1', '', NULL, '2025-12-15 07:01:00', 'aktif'),
(48, '765434567', 62, 'ccccc', 'E', 'Statistik S1', '', NULL, '2025-12-15 07:15:00', 'aktif'),
(49, '9532753', 63, 'gggg', 'E', 'Statistik S1', '', NULL, '2025-12-15 07:15:00', 'aktif'),
(50, '12345678', 64, 'Muhammad Iniesta Wildan Bromo Putra', 'A', 'Pemrogaman', '24356786576', 'uploads/profil/mhs_12345678_1765910430.jpg', '2025-12-16 16:00:00', 'aktif'),
(51, '12072010', 65, 'Anik Yuliana', 'A', 'Pemrogaman', '-', NULL, '2025-12-17 02:10:00', 'aktif'),
(52, '070771', 66, 'Muhammad Iniesta Wildan Bromo Putra', 'B', 'Teknik Informatika', '083841426422', 'uploads/profil/mhs_070771_1766543709.jpg', '2025-12-19 05:38:00', 'aktif'),
(53, '11112222', 69, 'Massayu Sekar Anindita', 'B', 'Stastatika', '085727662393', NULL, '2025-12-29 06:33:00', 'aktif'),
(55, '070772', 72, 'Simba', 'E', 'Statistik S1', '', NULL, '2026-01-09 01:57:00', 'aktif'),
(56, '0000123', 74, 'Budi Purnama', 'E', 'Statistik S1', '08126007900', 'uploads/profil/mhs_0000123_1769001059.webp', '2026-01-19 06:08:59', 'aktif'),
(58, '0000456', 76, 'Budi Purbaya', 'E', 'Statistik S1', '', 'uploads/profil/mhs_0000456_1768885505.jpg', '2026-01-19 06:30:00', 'aktif'),
(63, '10167021', 82, 'Alexander Sucipto', 'E', 'Teknik Informatika', '62212423341', 'uploads/profil/mhs_10167021_1769060212.png', '2026-01-19 07:51:00', 'aktif'),
(65, '12451731', 84, 'Alexander Kurdian', 'E', 'Teknik Informatika', '62212436341', NULL, '2026-01-19 07:56:00', 'aktif'),
(66, '12455531', 85, 'Alexander Kurniawan', 'E', 'Teknik Informatika', '62212423341', NULL, '2026-01-20 06:34:00', 'aktif');

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

--
-- Dumping data untuk tabel `materi_perkuliahan`
--

INSERT INTO `materi_perkuliahan` (`id_materi`, `id_jadwal`, `judul_materi`, `deskripsi`, `nama_file`, `path_file`, `tgl_upload`, `uploader_id`) VALUES
(10, 993, 'Statistika dalam kehidupan sehari hari', 'Statistika adalah cabang ilmu matematika yang mempelajari cara mengumpulkan, mengolah, menyajikan, menganalisis, dan menafsirkan data. Data yang dikumpulkan bisa berupa angka maupun kategori, yang diperoleh dari hasil pengamatan, survei, atau eksperimen. Dalam statistika, data biasanya disajikan dalam bentuk tabel, diagram, grafik, atau ukuran-ukuran tertentu seperti rata-rata, median, dan modus agar lebih mudah dipahami. Dengan penyajian yang tepat, informasi dari data dapat dibaca secara jelas dan tidak menimbulkan salah tafsir.\\r\\n\\r\\nSelain itu, statistika juga berperan penting dalam pengambilan keputusan di berbagai bidang, seperti pendidikan, ekonomi, kesehatan, dan teknologi. Melalui analisis data, statistika membantu menarik kesimpulan, memprediksi kejadian di masa depan, serta mengevaluasi suatu permasalahan berdasarkan fakta. Oleh karena itu, pemahaman statistika sangat diperlukan agar seseorang mampu berpikir logis, kritis, dan objektif dalam menilai informasi yang ada di sekitarnya.', NULL, NULL, '2026-01-21 17:52:14', 58);

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
(26, '230607', 969, NULL, 'sakit', 'terdaftar', 'sakit bro tolong ya aku izin', 'bukti_230607_1767856464.png', 'approved', '231064013', '2026-01-08 14:14:57', NULL, '2026-01-08 07:14:24'),
(27, '241064014', 991, NULL, 'izin', 'terdaftar', 'gak bisa masuk karena ada acara keluarga maaf', 'bukti_241064014_1767856895.png', 'approved', '123456789', '2026-01-08 14:21:44', NULL, '2026-01-08 07:21:35'),
(28, '241064008', 991, NULL, 'sakit', 'terdaftar', 'sakit habis kecelakaan jadi gak bisa masuk', 'bukti_241064008_1767856986.png', 'approved', '123456789', '2026-01-08 14:24:38', NULL, '2026-01-08 07:23:06'),
(29, '251062022', 991, NULL, 'sakit', 'terdaftar', 'malas masuk kwkkw', 'bukti_251062022_1767857234.png', 'rejected', '123456789', '2026-01-08 14:28:14', 'gak bisa anda bohong, saya alpha kamu\r\n', '2026-01-08 07:27:14');

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
(5, 'Informasi Peringatan Ujian', 'Mulai minggu depan akan diadakan sebuah Ujian Semester untuk semua mahasiswa, maka dari itu diharapkan agar semua mahasiswa bisa hadir mengikuti Ujian Semester. Terimakasih...', 'semua', '2026-01-15 07:30:03', 38, 'inactive');

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
(973, 969, '230607', 'sakit', '2026-01-08 07:14:57', 'manual', '231064013', NULL, NULL, NULL, 0),
(974, 969, '12345678', 'hadir', '2026-01-08 07:12:13', 'qr', NULL, 'Laboratorium Basis Data', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Sa', '::1', 1),
(975, 969, '12072010', 'hadir', '2026-01-08 07:07:11', 'qr', NULL, 'Laboratorium Basis Data', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Sa', '::1', 1),
(977, 991, '251062025', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(978, 991, '251062026', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(979, 991, '241064001', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(980, 991, '241064002', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(981, 991, '241064004', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(983, 991, '241064008', 'sakit', '2026-01-08 07:24:38', 'manual', '123456789', NULL, NULL, NULL, 0),
(984, 991, '241064009', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(985, 991, '241064013', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(986, 991, '241064014', 'izin', '2026-01-08 07:21:44', 'manual', '123456789', NULL, NULL, NULL, 0),
(987, 991, '241067010', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(988, 991, '241067011', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(989, 991, '241068005', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(990, 991, '241068006', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(991, 991, '242062001', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(992, 991, '242062004', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(994, 991, '24346554', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(995, 991, '765434567', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(996, 991, '9532753', 'hadir', '2026-01-08 07:16:34', 'manual', '123456789', NULL, NULL, NULL, 0),
(1002, 1002, '251062022', 'hadir', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1003, 1002, '251062025', 'izin', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1004, 1002, '251062026', 'hadir', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1005, 1002, '241064001', 'hadir', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1006, 1002, '241064002', 'hadir', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1007, 1002, '241064004', 'izin', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1010, 1002, '241064009', 'sakit', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1011, 1002, '241064013', 'hadir', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1013, 1002, '241067010', 'hadir', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1014, 1002, '241067011', 'hadir', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1015, 1002, '241068005', 'hadir', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1016, 1002, '241068006', 'hadir', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1017, 1002, '242062001', 'hadir', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1018, 1002, '242062004', 'hadir', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1019, 1002, '211063024', 'hadir', '2026-01-09 01:49:45', 'qr', NULL, 'Laboratorium Basis Data', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Sa', '::1', 1),
(1020, 1002, '24346554', 'hadir', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1021, 1002, '765434567', 'hadir', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1022, 1002, '9532753', 'sakit', '2026-01-09 01:49:30', 'manual', '231064013', NULL, NULL, NULL, 0),
(1023, 980, '070771', 'alpha', '2026-01-09 04:19:36', 'auto', NULL, NULL, NULL, NULL, 1),
(1024, 980, '11112222', 'alpha', '2026-01-09 04:19:36', 'auto', NULL, NULL, NULL, NULL, 1),
(1025, 991, '251062022', 'alpha', '2026-01-09 04:19:36', 'auto', NULL, NULL, NULL, NULL, 1),
(1026, 991, '241064007', 'alpha', '2026-01-09 04:19:36', 'auto', NULL, NULL, NULL, NULL, 1),
(1027, 991, '211063024', 'alpha', '2026-01-09 04:19:36', 'auto', NULL, NULL, NULL, NULL, 1),
(1028, 1002, '241064007', 'alpha', '2026-01-09 04:20:17', 'manual', '231064013', NULL, NULL, NULL, 0),
(1029, 1002, '241064008', 'alpha', '2026-01-09 04:32:43', 'manual', '231064013', NULL, NULL, NULL, 0),
(1030, 1002, '241064014', 'alpha', '2026-01-09 04:32:46', 'manual', '231064013', NULL, NULL, NULL, 0),
(1031, 1002, '070772', 'alpha', '2026-01-09 04:32:49', 'manual', '231064013', NULL, NULL, NULL, 0),
(1032, 981, '070771', 'alpha', '2026-01-15 02:20:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1033, 981, '11112222', 'alpha', '2026-01-15 02:20:04', 'auto', NULL, NULL, NULL, NULL, 1),
(1034, 992, '251062022', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1035, 992, '251062025', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1036, 992, '251062026', 'hadir', '2026-01-15 04:55:49', 'qr', NULL, 'Laboratorium Basis Data', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Sa', '::1', 1),
(1037, 992, '241064001', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1038, 992, '241064002', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1039, 992, '241064004', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1040, 992, '241064007', 'alpha', '2026-01-15 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1041, 992, '241064008', 'alpha', '2026-01-15 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1042, 992, '241064009', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1043, 992, '241064013', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1044, 992, '241064014', 'alpha', '2026-01-15 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1045, 992, '241067010', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1046, 992, '241067011', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1047, 992, '241068005', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1048, 992, '241068006', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1049, 992, '242062001', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1050, 992, '242062004', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1051, 992, '211063024', 'alpha', '2026-01-15 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1052, 992, '24346554', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1053, 992, '765434567', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1054, 992, '9532753', 'hadir', '2026-01-15 04:55:33', 'manual', '231064018', NULL, NULL, NULL, 0),
(1055, 992, '070772', 'alpha', '2026-01-15 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1056, 970, '230607', 'alpha', '2026-01-15 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1057, 970, '12345678', 'alpha', '2026-01-15 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1058, 970, '12345678', 'alpha', '2026-01-15 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1059, 970, '12072010', 'alpha', '2026-01-15 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1060, 970, '12072010', 'alpha', '2026-01-15 08:00:09', 'auto', NULL, NULL, NULL, NULL, 1),
(1061, 1003, '251062022', 'alpha', '2026-01-19 01:26:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1062, 1003, '251062025', 'alpha', '2026-01-19 01:26:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1063, 1003, '251062026', 'alpha', '2026-01-19 01:26:21', 'auto', NULL, NULL, NULL, NULL, 1),
(1064, 1003, '241064001', 'alpha', '2026-01-19 01:26:21', 'auto', NULL, NULL, NULL, NULL, 1),
(1065, 1003, '241064002', 'alpha', '2026-01-19 01:26:21', 'auto', NULL, NULL, NULL, NULL, 1),
(1066, 1003, '241064004', 'alpha', '2026-01-19 01:26:21', 'auto', NULL, NULL, NULL, NULL, 1),
(1067, 1003, '241064007', 'alpha', '2026-01-19 01:26:22', 'auto', NULL, NULL, NULL, NULL, 1),
(1068, 1003, '241064008', 'alpha', '2026-01-19 01:26:22', 'auto', NULL, NULL, NULL, NULL, 1),
(1069, 1003, '241064009', 'alpha', '2026-01-19 01:26:24', 'auto', NULL, NULL, NULL, NULL, 1),
(1070, 1003, '241064013', 'alpha', '2026-01-19 01:26:24', 'auto', NULL, NULL, NULL, NULL, 1),
(1071, 1003, '241064014', 'alpha', '2026-01-19 01:26:27', 'auto', NULL, NULL, NULL, NULL, 1),
(1072, 1003, '241067010', 'alpha', '2026-01-19 01:26:36', 'auto', NULL, NULL, NULL, NULL, 1),
(1073, 1003, '241067011', 'alpha', '2026-01-19 01:26:41', 'auto', NULL, NULL, NULL, NULL, 1),
(1074, 1003, '241068005', 'alpha', '2026-01-19 01:26:42', 'auto', NULL, NULL, NULL, NULL, 1),
(1075, 1003, '241068006', 'alpha', '2026-01-19 01:26:43', 'auto', NULL, NULL, NULL, NULL, 1),
(1076, 1003, '242062001', 'alpha', '2026-01-19 01:26:43', 'auto', NULL, NULL, NULL, NULL, 1),
(1077, 1003, '242062004', 'alpha', '2026-01-19 01:26:44', 'auto', NULL, NULL, NULL, NULL, 1),
(1078, 1003, '211063024', 'alpha', '2026-01-19 01:26:46', 'auto', NULL, NULL, NULL, NULL, 1),
(1079, 1003, '24346554', 'alpha', '2026-01-19 01:26:47', 'auto', NULL, NULL, NULL, NULL, 1),
(1080, 1003, '765434567', 'alpha', '2026-01-19 01:26:48', 'auto', NULL, NULL, NULL, NULL, 1),
(1081, 1003, '9532753', 'alpha', '2026-01-19 01:26:49', 'auto', NULL, NULL, NULL, NULL, 1),
(1082, 1003, '070772', 'alpha', '2026-01-19 01:26:51', 'auto', NULL, NULL, NULL, NULL, 1),
(1083, 993, '251062022', 'alpha', '2026-01-20 08:00:46', 'auto', NULL, NULL, NULL, NULL, 1),
(1084, 993, '251062025', 'alpha', '2026-01-20 08:00:46', 'auto', NULL, NULL, NULL, NULL, 1),
(1085, 993, '251062026', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1086, 993, '241064001', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1087, 993, '241064002', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1088, 993, '241064004', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1089, 993, '241064007', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1090, 993, '241064008', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1091, 993, '241064009', 'alpha', '2026-01-20 08:00:46', 'auto', NULL, NULL, NULL, NULL, 1),
(1092, 993, '241064013', 'alpha', '2026-01-20 08:00:46', 'auto', NULL, NULL, NULL, NULL, 1),
(1093, 993, '241064014', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1094, 993, '241067010', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1095, 993, '241067011', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1096, 993, '241068005', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1097, 993, '241068006', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1098, 993, '242062001', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1099, 993, '242062004', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1100, 993, '211063024', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1101, 993, '24346554', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1102, 993, '765434567', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1103, 993, '9532753', 'alpha', '2026-01-20 08:00:46', 'auto', NULL, NULL, NULL, NULL, 1),
(1104, 993, '070772', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1105, 993, '0000123', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1106, 993, '0000456', 'hadir', '2026-01-20 06:16:07', 'qr', NULL, 'Laboratorium Statistika', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Sa', '192.168.2.101', 1),
(1107, 993, '10167021', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1108, 993, '12451731', 'hadir', '2026-01-20 06:15:44', 'manual', '231064018', NULL, NULL, NULL, 0),
(1109, 993, '12455531', 'alpha', '2026-01-20 08:00:46', 'auto', NULL, NULL, NULL, NULL, 1),
(1115, 971, '230607', 'alpha', '2026-01-23 01:29:23', 'auto', NULL, NULL, NULL, NULL, 1),
(1116, 971, '12345678', 'alpha', '2026-01-23 01:29:23', 'auto', NULL, NULL, NULL, NULL, 1),
(1117, 971, '12072010', 'alpha', '2026-01-23 01:29:23', 'auto', NULL, NULL, NULL, NULL, 1),
(1118, 982, '070771', 'alpha', '2026-01-23 01:29:23', 'auto', NULL, NULL, NULL, NULL, 1),
(1119, 982, '11112222', 'alpha', '2026-01-23 01:29:23', 'auto', NULL, NULL, NULL, NULL, 1),
(1120, 1004, '251062022', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1121, 1004, '251062025', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1122, 1004, '251062026', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1123, 1004, '241064001', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1124, 1004, '241064002', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1125, 1004, '241064004', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1126, 1004, '241064007', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1127, 1004, '241064008', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1128, 1004, '241064009', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1129, 1004, '241064013', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1130, 1004, '241064014', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1131, 1004, '241067010', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1132, 1004, '241067011', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1133, 1004, '241068005', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1134, 1004, '241068006', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1135, 1004, '242062001', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1136, 1004, '242062004', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1137, 1004, '211063024', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1138, 1004, '24346554', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1139, 1004, '765434567', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1140, 1004, '9532753', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1141, 1004, '070772', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1142, 1004, '0000123', 'hadir', '2026-01-23 02:26:05', 'qr', NULL, 'Laboratorium Statistika', 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Sa', '192.168.2.104', 1),
(1143, 1004, '0000456', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1144, 1004, '10167021', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1145, 1004, '12451731', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1),
(1146, 1004, '12455531', 'alpha', '2026-01-23 06:00:20', 'auto', NULL, NULL, NULL, NULL, 1);

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
(139, 969, '45bdeb2abada27d9866298bbf1bbb922_1767856021', '2026-01-08 15:00:00', '2026-01-08 07:07:01'),
(140, 991, 'c00d0ee0d092a534e9db20a9dbc16999_1767856594', '2026-01-08 15:00:00', '2026-01-08 07:16:34'),
(141, 1002, 'c1aff56273b3ce4daf55ad2b7c9c18b2_1767923370', '2026-01-09 13:00:00', '2026-01-09 01:49:30'),
(142, 992, '8a2c516b2f86d3c1377449cd9942b917_1768452933', '2026-01-15 15:00:00', '2026-01-15 04:55:33'),
(143, 993, '49972641a0bfd1d09dbd4bacb0ff1371_1768889744', '2026-01-20 15:00:00', '2026-01-20 06:15:44'),
(144, 1004, '83ea8e0b18959abdb1c7126b39fd9b5e_1769135153', '2026-01-23 13:00:00', '2026-01-23 02:25:53');

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

--
-- Dumping data untuk tabel `soal_kuis`
--

INSERT INTO `soal_kuis` (`id`, `kuis_id`, `pertanyaan`, `opsi_a`, `opsi_b`, `opsi_c`, `opsi_d`, `kunci_jawaban`) VALUES
(1, 1, 'Statistika adalah ilmu yang mempelajari tentang …', 'Cara menghitung luas bangun', 'Pengumpulan, pengolahan, dan penyajian data', 'Perhitungan bilangan bulat', 'Pengukuran panjang dan berat', 'B'),
(2, 1, 'Kumpulan fakta atau informasi yang diperoleh dari hasil pengamatan disebut …', 'Diagram', 'Tabel', 'Data', 'Grafik', 'C'),
(3, 1, 'Tujuan utama statistika adalah …', 'Menghafal rumus', 'Menyimpan data sebanyak mungkin', 'Membantu pengambilan keputusan', 'Menggambar diagram', 'C'),
(32, 2, 'Kumpulan fakta atau informasi yang diperoleh dari hasil pengamatan disebut …', 'Diagram', 'Tabel', 'Data', 'Grafik', 'C'),
(33, 2, 'Tujuan utama statistika adalah …', 'Menghafal rumus', 'Menyimpan data sebanyak mungkin', 'Membantu pengambilan keputusan', 'Menggambar diagram', 'C'),
(34, 2, 'Data yang berbentuk angka disebut data …', 'Kualitatif', 'Kuantitatif', 'Primer', 'Sekunder', 'B'),
(35, 2, 'Data yang diperoleh langsung dari sumber pertama disebut data …', 'Sekunder', 'Kuantitatif', 'kualitatif', 'primer', 'D'),
(36, 2, 'penyajian data yang menggunakan gambar lingkaran disebut', 'diagram batang', 'diagram garis', 'diagram lingkaran', 'tabel', 'C'),
(37, 2, 'Nilai rata rata dari suatu kumpula data disebut', 'Modus', 'Median', 'Mean', 'Frekuensi', 'C'),
(38, 2, 'Nilai yang paling sering muncul dalam suatu data disebut', 'Mean', 'Median', 'Modus', 'Rata Rata', 'C'),
(39, 2, 'Nilai tengah dari data yang telah diurutkan disebut', 'Mean', 'Median', 'Modus', 'Interval', 'B'),
(40, 2, 'Statistika sering dignakan dalam kehidupan sehari hari contohnya', 'Menghafal rumus matematika', 'Menghitung nilai rata rata ujian', 'Menggambar bangun ruang', 'Mengukur panjang meja', 'B'),
(41, 2, 'Statistika adalah ilmu yang mempelajari tentang …', 'Cara menghitung luas bangun', 'Pengumpulan, pengolahan, dan penyajian data', 'Perhitungan bilangan bulat', 'Pengukuran panjang dan berat', 'B');

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

--
-- Dumping data untuk tabel `tiket_bantuan`
--

INSERT INTO `tiket_bantuan` (`id`, `nim`, `kategori`, `subjek`, `pesan`, `lampiran`, `status`, `tanggapan`, `created_at`, `updated_at`) VALUES
(1, '0000123', 'Masalah Sistem', 'bug pada menu profil mahasiswa', 'di profil ada masalah yang dimana ketika akun mahasiswa itu baru di buat harusnya jumlah status belum itu dia menghitung ketika mahasiswa baru itu terdafttar. Misal mahasiswa terdaftarnya di pertemuan ke 3 berarti jadwal presensi yang sebelum pertemuan ke 3 itu gak ada jadi status belumnya itu ngikutin jumlah ketika mahasiswa baru itu membuat akun', NULL, 'selesai', 'sebenernya system seperti ini sudah bener kak status belum pada profil mahasiswa saya bikin seperti itu untuk mencegah adanya manipulasi data kehadiran presensi karena kalau saya buatkan seperti saran punya kakak nanti system bakal rusak dan untuk mengatasi maslah itu saya bikin status belum nya di samakan semua, kalau mahasiswa ternyata di pertemuan sebelumnya dia hadir maka nanti di system nya ini bisa di masukkan atau di edit data presensinya gitu kak, terimakasih', '2026-01-21 02:59:19', '2026-01-21 04:06:39'),
(4, '0000123', 'Saran', 'Menu Pusat Bantuan', 'bisa engga untuk UI dari halaman pusat bantuan itu di perbagus lagi? yang di riwayat tiket anda..karena kalau dilihat itu tampilannya terlalu basic desainnya', 'uploads/bantuan/ticket_1768969259_6970542b8cbf2.png', 'proses', 'saya akan memperbaiki tampilan pada halaman pusat bantuan, jadi tolong untuk bersabar ya', '2026-01-21 04:20:59', '2026-01-21 04:50:18'),
(5, '0000123', 'Pertanyaan', 'Lupa Password', 'Bisa tolong saya untuk mengganti password akun? Saya lupa soalnya', 'uploads/bantuan/ticket_1769000790_6970cf5663d62.jpg', 'selesai', 'wkwkw kan anda bisa melalukan untuk reset password di menu profil mas/mba disitu bagian bawah sendiri ada namanya tab keamanan, itu tinggal di pencet nah disitu mas/mba nya bisa reset password tanpa perlu verifikasi password lama,  dan juga kalau mba/mas nya lupa password gimana tadi cara loginnya?', '2026-01-21 13:06:30', '2026-01-21 13:08:42'),
(6, '0000456', 'Pertanyaan', 'Jurnal harian', 'Fungsi jurnal harian di system presensi apa ya?', 'uploads/bantuan/ticket_1769391709_6976c65dc9c05.jpg', 'selesai', 'ya fungsi jurnal harian / pratikum itu buat mengulang pembelajaran yang udah di dapat sebelumnya, biar mahasiswa yang belum paham tentang materi tadi bisa di buat belajar lagi, gitu..kalau sekiranya udah paham fungsi jurnal pratikum juga bisa sebagai pengingat materi ketika ada ujian kedepannya gitu mas /mba', '2026-01-26 01:41:49', '2026-01-26 01:45:27');

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
(53, '241068006', '$2y$10$B.rUobMRDlZoFpXXFrLCuuIEMpnvFJFGuFuwRSVHX3gM/JU8.NpyG', 'mahasiswa', '2025-12-12 03:55:18', 'f0388a3b620181adcc0baac8f0955f6f535498ceb19b9df23b78b014b3408d06', '2026-02-26 11:10:08'),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `hasil_kuis`
--
ALTER TABLE `hasil_kuis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1013;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=922;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `presensi_mahasiswa`
--
ALTER TABLE `presensi_mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1147;

--
-- AUTO_INCREMENT untuk tabel `qr_code_session`
--
ALTER TABLE `qr_code_session`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

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
