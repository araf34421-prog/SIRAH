<?php
// Pastikan koneksi database sudah ada
if (!isset($conn)) {
    require_once __DIR__ . '/../config/database.php';
}

// 1. Pastikan ENUM role di tabel `users` mendukung 'operator'
$cek_role_col = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
if ($cek_role_col) {
    $col_info = mysqli_fetch_assoc($cek_role_col);
    if (stripos($col_info['Type'], 'operator') === false) {
        mysqli_query($conn, "ALTER TABLE users MODIFY COLUMN `role` ENUM('operator','pimpinan','ustadz','ortu') NOT NULL DEFAULT 'ustadz'");
    }
}

// Pastikan kolom jenis_kelamin & id_halaqah ada di tabel users
$cek_jk_u = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'jenis_kelamin'");
if ($cek_jk_u && mysqli_num_rows($cek_jk_u) === 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN jenis_kelamin ENUM('L','P') DEFAULT 'L' AFTER nama_lengkap");
}

// 2. Buat akun Operator Default jika belum ada sama sekali (user: operator | pass: operator123)
$cek_op = mysqli_query($conn, "SELECT id FROM users WHERE role = 'operator' LIMIT 1");
if ($cek_op && mysqli_num_rows($cek_op) === 0) {
    $pass_op = password_hash('operator123', PASSWORD_DEFAULT);
    mysqli_query($conn, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('operator', '$pass_op', 'Operator Utama Sistem', 'operator')");
}

// 3. Buat tabel `hak_akses_menu` untuk mengatur izin menu per aktor secara dinamis
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `hak_akses_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_menu` varchar(60) NOT NULL,
  `nama_menu` varchar(100) NOT NULL,
  `file_utama` varchar(100) NOT NULL,
  `file_terkait` text DEFAULT NULL,
  `kategori` varchar(50) NOT NULL,
  `icon` varchar(50) NOT NULL DEFAULT 'circle',
  `warna_icon` varchar(50) DEFAULT 'text-slate-400',
  `urutan` int(11) NOT NULL DEFAULT 1,
  `akses_operator` tinyint(1) NOT NULL DEFAULT 1,
  `akses_pimpinan` tinyint(1) NOT NULL DEFAULT 0,
  `akses_ustadz` tinyint(1) NOT NULL DEFAULT 0,
  `akses_ortu` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unik_kode_menu` (`kode_menu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// 4. Seed (Isi Awal) Daftar Menu seluruh Web jika tabel masih kosong
$cek_isi_menu = mysqli_query($conn, "SELECT COUNT(*) as total FROM hak_akses_menu");
$total_menu_db = $cek_isi_menu ? (int)mysqli_fetch_assoc($cek_isi_menu)['total'] : 0;

if ($total_menu_db === 0) {
    $default_menus = [
        ['dashboard', 'Dashboard Utama', 'dashboard.php', 'index.php', 'Menu Utama', 'layout-dashboard', 'text-indigo-400', 1, 1, 1, 1, 1],
        ['kelola_hak_akses', 'Hak Akses & Menu Actor', 'kelola_hak_akses.php', '', 'Manajemen Sistem', 'shield-check', 'text-rose-400', 2, 1, 0, 0, 0],
        ['kelola_ustadz', 'Kelola Ustadz & Ustadzah', 'kelola-ustadz.php', 'proses_tambah_ustadz.php,proses_hapus_ustadz.php', 'Manajemen Lembaga', 'users', 'text-indigo-400', 3, 1, 1, 0, 0],
        ['kelola_kelas', 'Kelas / Lokal Halaqah', 'kelola_kelas.php', '', 'Manajemen Lembaga', 'layout-grid', 'text-amber-400', 4, 1, 1, 1, 0],
        ['kelola_santri', 'Kelola Data Santri', 'kelola_santri.php', 'proses_tambah_santri.php,proses_edit_santri.php,proses_hapus_santri.php', 'Manajemen Lembaga', 'graduation-cap', 'text-emerald-400', 5, 1, 1, 1, 0],
        ['target_santri', 'Target Hafalan Santri', 'target_santri.php', 'proses_target_santri.php', 'Manajemen Lembaga', 'target', 'text-teal-400', 6, 1, 1, 1, 0],
        ['input_ziyadah', 'Input Setoran Ziyadah', 'input_ziyadah.php', 'proses_input_hafalan.php', 'Pencatatan Harian', 'book-plus', 'text-emerald-400', 7, 1, 0, 1, 0],
        ['input_murajaah', 'Input Setoran Muraja\'ah', 'input_murajaah.php', 'proses_input_hafalan.php', 'Pencatatan Harian', 'refresh-cw', 'text-blue-400', 8, 1, 0, 1, 0],
        ['input_tasmi', 'Input Ujian Tasmi\'', 'input_tasmi.php', 'proses_input_hafalan.php', 'Pencatatan Harian', 'award', 'text-purple-400', 9, 1, 0, 1, 0],
        ['input_kehadiran', 'Input Kehadiran Harian', 'input_kehadiran.php', 'input_absensi.php', 'Pencatatan Harian', 'calendar-check', 'text-indigo-400', 10, 1, 0, 1, 0],
        ['input_pr', 'Berikan Tugas Rumah (PR)', 'input_pr.php', '', 'Pencatatan Harian', 'clipboard-list', 'text-orange-400', 11, 1, 0, 1, 0],
        ['progres_santri', 'Analisis Progres Santri', 'progres_santri.php', '', 'Evaluasi & Rapor', 'line-chart', 'text-sky-400', 12, 1, 1, 1, 0],
        ['input_rapor', 'Input Leger Rapor', 'input_rapor.php', 'proses_input_rapor.php', 'Evaluasi & Rapor', 'table-2', 'text-amber-400', 13, 1, 0, 1, 0],
        ['rapor_santri', 'Cetak Rapor Tahfidz', 'rapor_santri.php', '', 'Evaluasi & Rapor', 'printer', 'text-emerald-400', 14, 1, 1, 1, 1],
        ['rekap_bulanan', 'Rekapitulasi Bulanan', 'rekap_bulanan.php', '', 'Evaluasi & Rapor', 'file-bar-chart', 'text-purple-400', 15, 1, 1, 1, 0],
        ['monitoring_anak', 'Progres Hafalan Anak', 'monitoring_anak.php', '', 'Portal Wali Santri', 'book-open-check', 'text-emerald-400', 16, 1, 0, 0, 1],
        ['absensi_anak', 'Riwayat Kehadiran Anak', 'absensi_anak.php', '', 'Portal Wali Santri', 'calendar-check', 'text-blue-400', 17, 1, 0, 0, 1],
        ['pr_anak', 'Tugas Rumah (PR) Anak', 'pr_anak.php', '', 'Portal Wali Santri', 'clipboard-list', 'text-orange-400', 18, 1, 0, 0, 1]
    ];

    foreach ($default_menus as $m) {
        $kode  = mysqli_real_escape_string($conn, $m[0]);
        $nama  = mysqli_real_escape_string($conn, $m[1]);
        $utama = mysqli_real_escape_string($conn, $m[2]);
        $terk  = mysqli_real_escape_string($conn, $m[3]);
        $kat   = mysqli_real_escape_string($conn, $m[4]);
        $icon  = mysqli_real_escape_string($conn, $m[5]);
        $warna = mysqli_real_escape_string($conn, $m[6]);
        $urut  = (int)$m[7];
        $op    = (int)$m[8];
        $pimp  = (int)$m[9];
        $ust   = (int)$m[10];
        $ortu  = (int)$m[11];

        mysqli_query($conn, "INSERT IGNORE INTO hak_akses_menu 
            (kode_menu, nama_menu, file_utama, file_terkait, kategori, icon, warna_icon, urutan, akses_operator, akses_pimpinan, akses_ustadz, akses_ortu)
            VALUES ('$kode', '$nama', '$utama', '$terk', '$kat', '$icon', '$warna', $urut, $op, $pimp, $ust, $ortu)");
    }
}

require_once __DIR__ . '/functions.php';
?>
