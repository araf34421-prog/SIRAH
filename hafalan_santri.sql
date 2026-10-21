-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 21 Okt 2026 pada 07.27
-- Versi server: 12.2.2-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `hafalan_santri`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi`
--

CREATE TABLE `absensi` (
  `id` int(11) NOT NULL,
  `id_santri` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('Hadir','Sakit','Izin','Alfa') NOT NULL,
  `keterangan` text DEFAULT NULL,
  `id_ustadz` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `hafalan`
--

CREATE TABLE `hafalan` (
  `id` int(11) NOT NULL,
  `id_santri` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `jenis` enum('ziyadah','murajaah','tasmi') NOT NULL,
  `surat` varchar(50) DEFAULT NULL,
  `ayat_awal` int(11) DEFAULT NULL,
  `ayat_akhir` int(11) DEFAULT NULL,
  `jumlah_ayat` int(11) DEFAULT NULL,
  `predikat` enum('A','B','C') DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `id_ustadz` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data untuk tabel `hafalan`
--

INSERT INTO `hafalan` (`id`, `id_santri`, `tanggal`, `jenis`, `surat`, `ayat_awal`, `ayat_akhir`, `jumlah_ayat`, `predikat`, `catatan`, `id_ustadz`, `created_at`) VALUES
(1, 3, '2026-10-11', 'ziyadah', 'an-naba', 1, 20, 20, 'B', 'perbaiki tajwidnya', 1, '2026-10-11 13:54:36');

-- --------------------------------------------------------

--
-- Struktur dari tabel `halaqah`
--

CREATE TABLE `halaqah` (
  `id` int(11) NOT NULL,
  `nama_halaqah` varchar(50) NOT NULL,
  `id_ustadz` int(11) DEFAULT NULL,
  `tingkat` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kehadiran`
--

CREATE TABLE `kehadiran` (
  `id` int(11) NOT NULL,
  `id_santri` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('H','S','I','A') NOT NULL,
  `keterangan` text DEFAULT NULL,
  `id_ustadz` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pr`
--

CREATE TABLE `pr` (
  `id` int(11) NOT NULL,
  `id_santri` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `judul` varchar(100) NOT NULL,
  `deskripsi` text DEFAULT NULL,
  `id_ustadz` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `santri`
--

CREATE TABLE `santri` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `nis` varchar(20) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') NOT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `nama_ortu` varchar(100) DEFAULT NULL,
  `no_hp_ortu` varchar(20) DEFAULT NULL,
  `id_user_ortu` int(11) DEFAULT NULL,
  `id_halaqah` int(11) DEFAULT NULL,
  `id_ustadz` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data untuk tabel `santri`
--

INSERT INTO `santri` (`id`, `nama`, `nis`, `jenis_kelamin`, `tanggal_lahir`, `alamat`, `nama_ortu`, `no_hp_ortu`, `id_user_ortu`, `id_halaqah`, `id_ustadz`, `created_at`) VALUES
(3, 'Aurora Faradiba', '876543234567', 'P', '2010-07-03', 'Jawa Barat\r\nGunung sindur', 'metra dona', '083174931334', 8, NULL, NULL, '2026-10-08 04:47:54'),
(4, 'halim', '6544567', 'L', '2010-04-01', 'jorong jalikur', 'bu oka', '08133434532', 9, NULL, NULL, '2026-10-10 05:06:18');

-- --------------------------------------------------------

--
-- Struktur dari tabel `target_hafalan`
--

CREATE TABLE `target_hafalan` (
  `id` int(11) NOT NULL,
  `id_santri` int(11) NOT NULL,
  `tanggal_mulai` date NOT NULL,
  `target_ayat_per_hari` int(11) DEFAULT NULL,
  `target_surat` varchar(50) DEFAULT NULL,
  `total_ayat_target` int(11) DEFAULT NULL,
  `tanggal_estimasi_selesai` date DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data untuk tabel `target_hafalan`
--

INSERT INTO `target_hafalan` (`id`, `id_santri`, `tanggal_mulai`, `target_ayat_per_hari`, `target_surat`, `total_ayat_target`, `tanggal_estimasi_selesai`, `keterangan`, `created_at`) VALUES
(1, 3, '2026-10-11', 5, NULL, NULL, NULL, 'lanjut juz1', '2026-10-11 13:58:33');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('ustadz','ortu','pimpinan') NOT NULL,
  `id_halaqah` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `id_halaqah`, `created_at`) VALUES
(1, 'ustadz01', '$2y$10$UGx5dtppkLRdde3JNiC38up47oovuRF6.M0/X7MIL0ZIlzFn806Ne', 'Ustadz Ahmad', 'ustadz', NULL, '2026-10-07 12:48:38'),
(3, 'pimpinan', '$2y$10$.RJQFFHPHDbxNxXt9EoFN.m8Dey5Xc8GnsSIACuYY2YQijvzz24F2', 'Pimpinan Surau', 'pimpinan', NULL, '2026-10-07 12:48:38'),
(8, 'ortu_aura', '$2y$10$SiDqLkV9enN7HxOYtUR3r.GotA8zds1MZ0DtRAWtCoqgrgpiYXXtW', 'metra dona', 'ortu', NULL, '2026-10-08 04:47:54'),
(9, 'ortu_halim', '$2y$10$Q3UbV4UOMJB7UICl/vxgdu1QcmkUSx91wN1E.XUqHqwauhf6dySa2', 'bu oka', 'ortu', NULL, '2026-10-10 05:06:18');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_santri` (`id_santri`),
  ADD KEY `id_ustadz` (`id_ustadz`);

--
-- Indeks untuk tabel `hafalan`
--
ALTER TABLE `hafalan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_santri` (`id_santri`),
  ADD KEY `id_ustadz` (`id_ustadz`);

--
-- Indeks untuk tabel `halaqah`
--
ALTER TABLE `halaqah`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_ustadz` (`id_ustadz`);

--
-- Indeks untuk tabel `kehadiran`
--
ALTER TABLE `kehadiran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_santri` (`id_santri`),
  ADD KEY `id_ustadz` (`id_ustadz`);

--
-- Indeks untuk tabel `pr`
--
ALTER TABLE `pr`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_santri` (`id_santri`),
  ADD KEY `id_ustadz` (`id_ustadz`);

--
-- Indeks untuk tabel `santri`
--
ALTER TABLE `santri`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD KEY `id_user_ortu` (`id_user_ortu`),
  ADD KEY `id_halaqah` (`id_halaqah`);

--
-- Indeks untuk tabel `target_hafalan`
--
ALTER TABLE `target_hafalan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_santri` (`id_santri`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `hafalan`
--
ALTER TABLE `hafalan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `halaqah`
--
ALTER TABLE `halaqah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kehadiran`
--
ALTER TABLE `kehadiran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pr`
--
ALTER TABLE `pr`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `santri`
--
ALTER TABLE `santri`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `target_hafalan`
--
ALTER TABLE `target_hafalan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi`
--
ALTER TABLE `absensi`
  ADD CONSTRAINT `1` FOREIGN KEY (`id_santri`) REFERENCES `santri` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `2` FOREIGN KEY (`id_ustadz`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `hafalan`
--
ALTER TABLE `hafalan`
  ADD CONSTRAINT `1` FOREIGN KEY (`id_santri`) REFERENCES `santri` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `2` FOREIGN KEY (`id_ustadz`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `halaqah`
--
ALTER TABLE `halaqah`
  ADD CONSTRAINT `1` FOREIGN KEY (`id_ustadz`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `kehadiran`
--
ALTER TABLE `kehadiran`
  ADD CONSTRAINT `1` FOREIGN KEY (`id_santri`) REFERENCES `santri` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `2` FOREIGN KEY (`id_ustadz`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pr`
--
ALTER TABLE `pr`
  ADD CONSTRAINT `1` FOREIGN KEY (`id_santri`) REFERENCES `santri` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `2` FOREIGN KEY (`id_ustadz`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `santri`
--
ALTER TABLE `santri`
  ADD CONSTRAINT `1` FOREIGN KEY (`id_user_ortu`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `2` FOREIGN KEY (`id_halaqah`) REFERENCES `halaqah` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `target_hafalan`
--
ALTER TABLE `target_hafalan`
  ADD CONSTRAINT `1` FOREIGN KEY (`id_santri`) REFERENCES `santri` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
