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

// 5. Fungsi Helper Cek Izin Akses File
function cekIzinAksesFile($conn, $role, $nama_file) {
    if ($role === 'operator') return true;
    if (in_array($nama_file, ['login.php', 'proses_login.php', 'logout.php', 'dashboard.php', 'index.php', 'profil_saya.php'])) return true;

    $kolom_role = [
        'pimpinan' => 'akses_pimpinan',
        'ustadz'   => 'akses_ustadz',
        'ortu'     => 'akses_ortu'
    ][$role] ?? '';

    if (empty($kolom_role)) return false;

    $file_esc = mysqli_real_escape_string($conn, $nama_file);
    $q = "SELECT $kolom_role FROM hak_akses_menu 
          WHERE file_utama = '$file_esc' OR FIND_IN_SET('$file_esc', file_terkait) > 0";
    $res = mysqli_query($conn, $q);

    if ($res && mysqli_num_rows($res) > 0) {
        while ($r = mysqli_fetch_assoc($res)) {
            if ((int)$r[$kolom_role] === 1) return true;
        }
        return false;
    }
    return true;
}

// 6. Fungsi Pintar: Deteksi apakah user yang login adalah Ustadzah (Perempuan) atau Ustadz (Laki-laki)
function isPengajarUstadzah($conn, $id_user) {
    $id_user = (int) $id_user;
    if ($id_user <= 0) return false;

    $q = mysqli_query($conn, "SELECT u.jenis_kelamin, u.nama_lengkap, u.username, h.nama_halaqah 
                              FROM users u 
                              LEFT JOIN halaqah h ON (u.id_halaqah = h.id OR h.id_ustadz = u.id) 
                              WHERE u.id = $id_user LIMIT 1");
    if ($q && $row = mysqli_fetch_assoc($q)) {
        // Cek 1: Kolom jenis_kelamin di tabel users = 'P'
        if (($row['jenis_kelamin'] ?? '') === 'P') return true;
        // Cek 2: Username diawali 'ustadzah' atau nama mengandung 'ustadzah'
        if (stripos($row['username'] ?? '', 'ustadzah') !== false) return true;
        if (stripos($row['nama_lengkap'] ?? '', 'ustadzah') !== false) return true;
        // Cek 3: Mengajar di lokal yang namanya mengandung 'Perempuan' atau 'Akhwat'
        if (stripos($row['nama_halaqah'] ?? '', 'perempuan') !== false || stripos($row['nama_halaqah'] ?? '', 'akhwat') !== false) {
            // Sekalian perbaiki kolom jenis_kelamin di database agar permanen 'P'
            mysqli_query($conn, "UPDATE users SET jenis_kelamin = 'P' WHERE id = $id_user");
            return true;
        }
    }
    return false;
}

// 7. Fungsi Terpusat: Ambil SQL Filter Santri Khusus Binaan Ustadz/Ustadzah
function getFilterSantriBinaanSQL($conn, $id_user, $role, $alias_tabel_santri = 's') {
    // Operator & Pimpinan bisa melihat seluruh santri
    if (in_array($role, ['operator', 'pimpinan'])) {
        return "";
    }

    $id_user = (int) $id_user;
    $u_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_halaqah, nama_lengkap, jenis_kelamin FROM users WHERE id = $id_user"));
    $id_h_user = (int) ($u_info['id_halaqah'] ?? 0);
    $nm_esc    = mysqli_real_escape_string($conn, trim($u_info['nama_lengkap'] ?? ''));

    // Cari semua ID lokal yang diampu oleh Ustadz/Ustadzah ini
    $q_lokal = mysqli_query($conn, "SELECT id, nama_halaqah FROM halaqah 
                                    WHERE id_ustadz = $id_user 
                                       OR id = $id_h_user 
                                       OR (nama_pengajar IS NOT NULL AND nama_pengajar != '' AND LOWER(nama_pengajar) = LOWER('$nm_esc'))");
    $lokal_ids = [];
    if ($q_lokal) {
        while ($lk = mysqli_fetch_assoc($q_lokal)) {
            $lokal_ids[] = (int) $lk['id'];
        }
    }

    $prefix = !empty($alias_tabel_santri) ? "$alias_tabel_santri." : "";

    // Jika ustadz/ustadzah sudah punya lokal binaan, filter HANYA santri di lokal tersebut!
    if (!empty($lokal_ids)) {
        $list_id = implode(',', $lokal_ids);
        return " WHERE {$prefix}id_halaqah IN ($list_id) ";
    }

    // Jika ustadz/ustadzah belum punya lokal spesifik, filter berdasarkan santri yang id_ustadz = beliau ATAU sesuai jenis kelaminnya (Putri utk Ustadzah, Putra utk Ustadz)
    $is_ustadzah = isPengajarUstadzah($conn, $id_user);
    $jk_target   = $is_ustadzah ? 'P' : 'L';
    return " WHERE ({$prefix}id_ustadz = $id_user OR ({$prefix}id_halaqah IS NULL AND {$prefix}jenis_kelamin = '$jk_target')) ";
}

// 8. Fungsi Helper: Buat Link WhatsApp Otomatis untuk Laporan Setoran & PR ke Orang Tua
function buatLinkWaHafalan($no_hp, $nama_santri, $nama_ortu, $jenis, $tanggal, $juz, $pojok, $surat, $ayat_awal, $ayat_akhir, $predikat, $catatan, $nama_pengajar = 'Ustadz/Ustadzah') {
    $hp = preg_replace('/[^0-9]/', '', $no_hp ?? '');
    if (empty($hp)) return '';
    if (strpos($hp, '0') === 0) {
        $hp = '62' . substr($hp, 1);
    }

    $kat = strtoupper($jenis);
    $tgl_indo = date('d/m/Y', strtotime($tanggal));
    $ortu_sapaan = !empty($nama_ortu) ? "Bapak/Ibu *$nama_ortu*" : "Orang Tua/Wali";
    $info_juz = !empty($juz) ? "Juz $juz" . (!empty($pojok) ? " ($pojok)" : "") : "-";
    $info_ayat = ($jenis === 'tasmi') ? "Ujian Sekali Duduk" : "QS. $surat : Ayat $ayat_awal - $ayat_akhir";
    $ket_predikat = ($predikat === 'A') ? "A (Sangat Baik / Lancar)" : (($predikat === 'B') ? "B (Lancar dengan Catatan)" : "C (Perlu Mengulang)");
    $cat_teks = !empty($catatan) ? $catatan : "Alhamdulillah lancar, mohon terus dimotivasi di rumah.";

    $pesan  = "Assalamu'alaikum Warahmatullahi Wabarakatuh,\n";
    $pesan .= "Yth. $ortu_sapaan,\n\n";
    $pesan .= "Berikut laporan setoran *$kat* Ananda di *Surau Tahfizh Firdaus*:\n";
    $pesan .= "----------------------------------\n";
    $pesan .= "• *Nama Santri:* $nama_santri\n";
    $pesan .= "• *Tanggal:* $tgl_indo\n";
    $pesan .= "• *Juz / Pojok:* $info_juz\n";
    $pesan .= "• *Materi:* $info_ayat\n";
    $pesan .= "• *Predikat:* *$ket_predikat*\n";
    $pesan .= "• *Catatan:* _\"$cat_teks\"_\n";
    $pesan .= "----------------------------------\n";
    $pesan .= "Disimak oleh: *$nama_pengajar*\n";
    $pesan .= "Terima kasih atas kerja sama Ayah/Bunda dalam mendampingi muroja'ah Ananda di rumah.";

    return "https://wa.me/$hp?text=" . rawurlencode($pesan);
}

function buatLinkWaPR($no_hp, $nama_santri, $nama_ortu, $tanggal, $judul, $deskripsi, $nama_pengajar = 'Ustadz/Ustadzah') {
    $hp = preg_replace('/[^0-9]/', '', $no_hp ?? '');
    if (empty($hp)) return '';
    if (strpos($hp, '0') === 0) {
        $hp = '62' . substr($hp, 1);
    }

    $tgl_indo = date('d/m/Y', strtotime($tanggal));
    $ortu_sapaan = !empty($nama_ortu) ? "Bapak/Ibu *$nama_ortu*" : "Orang Tua/Wali";

    $pesan  = "Assalamu'alaikum Warahmatullahi Wabarakatuh,\n";
    $pesan .= "Yth. $ortu_sapaan,\n\n";
    $pesan .= "Terdapat *Tugas Rumah (PR)* untuk Ananda *$nama_santri* dari *Surau Tahfizh Firdaus*:\n";
    $pesan .= "----------------------------------\n";
    $pesan .= "• *Tanggal:* $tgl_indo\n";
    $pesan .= "• *Judul Tugas:* $judul\n";
    $pesan .= "• *Instruksi:* $deskripsi\n";
    $pesan .= "----------------------------------\n";
    $pesan .= "Mohon bantuan Ayah/Bunda untuk menyimak tugas Ananda di rumah dan menandainya selesai di Portal Orang Tua. Terima kasih.\n";
    $pesan .= "— *$nama_pengajar*";

    return "https://wa.me/$hp?text=" . rawurlencode($pesan);
}
?>