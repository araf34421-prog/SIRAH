<?php
/** Shared application helpers for permissions, santri filters, and notifications. */
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

    // Mode Badal: Ustadz/Ustadzah menampilkan semua santri (?semua=1)
    if (isset($_GET['semua']) && $_GET['semua'] === '1') {
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

// 9. Fungsi Render Tombol Switcher "Khusus Lokal Binaan" vs "Tampilkan Semua Santri (Badal)"
if (!function_exists('renderBadgeFilterBinaan')) {
    function renderBadgeFilterBinaan($role) {
        if (in_array($role, ['operator', 'pimpinan'])) return '';

        $is_semua = (isset($_GET['semua']) && $_GET['semua'] === '1');
        $params   = $_GET;
        $params['semua'] = $is_semua ? '0' : '1';
        $url_toggle = basename($_SERVER['PHP_SELF']) . '?' . http_build_query($params);

        if ($is_semua) {
            return '
            <a href="' . htmlspecialchars($url_toggle) . '" title="Kembali ke santri lokal binaan saya"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100 transition shadow-sm">
                <i data-lucide="users" class="w-3.5 h-3.5"></i>
                <span>Mode Badal: Semua Santri</span>
                <span class="underline ml-1 font-bold">Reset ke Binaan</span>
            </a>';
        }
        return '
            <a href="' . htmlspecialchars($url_toggle) . '" title="Klik jika Anda sedang menggantikan ustadz/ah lain (Badal)"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition shadow-sm">
                <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                <span>Filter: Santri Binaan Saya</span>
                <span class="text-slate-400">|</span>
                <span class="underline text-slate-600 hover:text-slate-900">Tampilkan Semua</span>
            </a>';
    }
}
