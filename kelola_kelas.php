<?php
session_start();
require 'config/database.php';

// 🔒 Keamanan: Hanya Ustadz & Pimpinan yang dapat mengakses
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];

// Pastikan kolom `keterangan` dan `nama_pengajar_manual` tersedia di tabel `halaqah`
$cek_ket = mysqli_query($conn, "SHOW COLUMNS FROM halaqah LIKE 'keterangan'");
if (mysqli_num_rows($cek_ket) === 0) {
    mysqli_query($conn, "ALTER TABLE halaqah ADD COLUMN keterangan VARCHAR(150) DEFAULT 'Tahfidz Sore ( 16.30 - Isya )' AFTER tingkat");
}
$cek_pengajar = mysqli_query($conn, "SHOW COLUMNS FROM halaqah LIKE 'nama_pengajar'");
if (mysqli_num_rows($cek_pengajar) === 0) {
    mysqli_query($conn, "ALTER TABLE halaqah ADD COLUMN nama_pengajar VARCHAR(100) DEFAULT NULL AFTER id_ustadz");
}

// === 1. FITUR 1-KLIK: MUAT 31 LOKAL DEFAULT SESUAI GAMBAR ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seed_default_lokal'])) {
    $preset_lokal = [
        // Asrama (1 - 4)
        ['Karantina 1 Tahun 30 Juz (Ikhwan)', 'Muhammad Zaid Al Harits', 'Asrama'],
        ['Karantina 1 Tahun 30 Juz (Akhwat)', 'Amimul Hummah', 'Asrama'],
        ['Karantina 1 Tahun 30 Juz (Akhwat) 2', 'Misbahul Hidayah', 'Asrama'],
        ['Karantina Full', 'Elma Yulianti Dalimunte, SE.,ME', 'Asrama'],
        // Tahfidz Sore + Menginap sekali seminggu (5 - 9)
        ['Lokal Unggul 1 Laki-Laki', 'Rokika Fauzi', 'Tahfidz Sore + Menginap sekali seminggu'],
        ['Lokal Unggul 2 Laki-Laki', 'M. Faiz Ali, S.H', 'Tahfidz Sore + Menginap sekali seminggu'],
        ['Lokal Unggul 1 Perempuan', 'Annisa Harifa Ramadhan', 'Tahfidz Sore + Menginap sekali seminggu'],
        ['Lokal Unggul 2 Perempuan', 'Sani Lahiya Putri', 'Tahfidz Sore + Menginap sekali seminggu'],
        ['Lokal Unggul 3 Perempuan', 'Sona Lestari', 'Tahfidz Sore + Menginap sekali seminggu'],
        // Tahfidz Sore ( 16.30 - Isya ) (10 - 28)
        ['Lokal Khusus Laki-Laki', 'Muhammad Yusuf Siregar, S.H', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal Khusus Perempuan', 'Selmailia', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal Utama 1 Laki-Laki', 'Rival Mauli Latif', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal Utama 2 Laki-Laki', 'Rafli Hidayat', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal Utama 1 Perempuan', 'Niswa Hezhiratul Qudsi', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal Utama 2 Perempuan', 'Irfa Yulianti, SE', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal A Laki-Laki', 'Abdul Shaleh Simatupang, S.H', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal A Perempuan', 'Putri Ramadhani, S.HI', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal B1 Laki-Laki', 'Muhammad Zaki Ramadhan', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal B2 Laki-Laki', 'Muhammad Ihsan', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal B1 Perempuan', 'Putri Ramadhani', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal B2 Perempuan', 'Annisa Rahmita, S.Pd', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal C Khusus 1 Perempuan', 'Avira Tri Wahyuni', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal C Khusus 2 Perempuan', 'Aziana Sertiya Firly', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal Reguler 1 Laki-Laki', 'Yogi Azhari Nainggolan, S.Pd', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal Reguler 2 Laki-Laki', 'M. Nurul Hadi Pasaribu, S.Ag', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal Reguler 1 Perempuan', 'Tiara Andina Nasution', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal Reguler 2 Perempuan', 'Zar\'aini', 'Tahfidz Sore ( 16.30 - Isya )'],
        ['Lokal Reguler 3 Perempuan', 'Junesa Yulanda', 'Tahfidz Sore ( 16.30 - Isya )'],
        // Tahfidz Sore ( 16.30 - Sebelum Maghrib ) (29 - 31)
        ['Lokal C1', 'Vania Gusvarina, A.Md', 'Tahfidz Sore ( 16.30 - Sebelum Maghrib )'],
        ['Lokal C2', 'Thalia Adara Shakila', 'Tahfidz Sore ( 16.30 - Sebelum Maghrib )'],
        ['Lokal C3', 'Niswatun Fil Madina, S.Pd', 'Tahfidz Sore ( 16.30 - Sebelum Maghrib )']
    ];

    $berhasil = 0;
    foreach ($preset_lokal as $item) {
        $nm_lokal = mysqli_real_escape_string($conn, $item[0]);
        $nm_ust   = mysqli_real_escape_string($conn, $item[1]);
        $ket_prog = mysqli_real_escape_string($conn, $item[2]);

        $cek = mysqli_query($conn, "SELECT id FROM halaqah WHERE nama_halaqah = '$nm_lokal'");
        if (mysqli_num_rows($cek) === 0) {
            mysqli_query($conn, "INSERT INTO halaqah (nama_halaqah, nama_pengajar, keterangan) VALUES ('$nm_lokal', '$nm_ust', '$ket_prog')");
            $berhasil++;
        }
    }
    $_SESSION['success'] = "Berhasil memuat $berhasil daftar Kelas / Lokal standar beserta Ustadz/Ustadzah pengampunya!";
    header("Location: kelola_kelas.php");
    exit;
}

// === 2. TAMBAH KELAS / LOKAL BARU ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_kelas'])) {
    $nama_halaqah  = mysqli_real_escape_string($conn, trim($_POST['nama_halaqah']));
    $id_ustadz     = !empty($_POST['id_ustadz']) ? (int) $_POST['id_ustadz'] : 'NULL';
    $nama_pengajar = mysqli_real_escape_string($conn, trim($_POST['nama_pengajar'] ?? ''));
    $keterangan    = mysqli_real_escape_string($conn, trim($_POST['keterangan']));

    if ($id_ustadz !== 'NULL' && empty($nama_pengajar)) {
        $u_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_lengkap FROM users WHERE id = $id_ustadz"));
        $nama_pengajar = mysqli_real_escape_string($conn, $u_res['nama_lengkap'] ?? '');
    }

    $sql = "INSERT INTO halaqah (nama_halaqah, id_ustadz, nama_pengajar, keterangan) 
            VALUES ('$nama_halaqah', $id_ustadz, '$nama_pengajar', '$keterangan')";
    if (mysqli_query($conn, $sql)) {
        $new_id = mysqli_insert_id($conn);
        if ($id_ustadz !== 'NULL') {
            mysqli_query($conn, "UPDATE users SET id_halaqah = $new_id WHERE id = $id_ustadz");
        }
        $_SESSION['success'] = "Kelas/Lokal '$nama_halaqah' berhasil ditambahkan!";
    } else {
        $_SESSION['error'] = "Gagal menambahkan lokal: " . mysqli_error($conn);
    }
    header("Location: kelola_kelas.php");
    exit;
}

// === 3. EDIT KELAS / LOKAL ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_kelas'])) {
    $id_kelas      = (int) $_POST['id_kelas'];
    $nama_halaqah  = mysqli_real_escape_string($conn, trim($_POST['nama_halaqah']));
    $id_ustadz     = !empty($_POST['id_ustadz']) ? (int) $_POST['id_ustadz'] : 'NULL';
    $nama_pengajar = mysqli_real_escape_string($conn, trim($_POST['nama_pengajar'] ?? ''));
    $keterangan    = mysqli_real_escape_string($conn, trim($_POST['keterangan']));

    if ($id_ustadz !== 'NULL' && empty($nama_pengajar)) {
        $u_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT nama_lengkap FROM users WHERE id = $id_ustadz"));
        $nama_pengajar = mysqli_real_escape_string($conn, $u_res['nama_lengkap'] ?? '');
    }

    $sql = "UPDATE halaqah SET 
                nama_halaqah = '$nama_halaqah', 
                id_ustadz = $id_ustadz, 
                nama_pengajar = '$nama_pengajar', 
                keterangan = '$keterangan' 
            WHERE id = $id_kelas";
    if (mysqli_query($conn, $sql)) {
        if ($id_ustadz !== 'NULL') {
            mysqli_query($conn, "UPDATE users SET id_halaqah = $id_kelas WHERE id = $id_ustadz");
        }
        $_SESSION['success'] = "Data Lokal '$nama_halaqah' berhasil diperbarui!";
    }
    header("Location: kelola_kelas.php?id_kelas=$id_kelas");
    exit;
}

// === 4. HAPUS KELAS / LOKAL ===
if (isset($_GET['hapus_kelas'])) {
    $id_hapus = (int) $_GET['hapus_kelas'];
    mysqli_query($conn, "UPDATE santri SET id_halaqah = NULL WHERE id_halaqah = $id_hapus");
    mysqli_query($conn, "DELETE FROM halaqah WHERE id = $id_hapus");
    $_SESSION['success'] = "Lokal/Kelas berhasil dihapus.";
    header("Location: kelola_kelas.php");
    exit;
}

// === 5. MASUKKAN SANTRI KE KELAS INI ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_santri'])) {
    $id_kelas = (int) $_POST['id_kelas'];
    if (!empty($_POST['id_santri_arr']) && is_array($_POST['id_santri_arr'])) {
        $ids = array_map('intval', $_POST['id_santri_arr']);
        $id_list = implode(',', $ids);
        mysqli_query($conn, "UPDATE santri SET id_halaqah = $id_kelas WHERE id IN ($id_list)");
        $_SESSION['success'] = count($ids) . " santri berhasil dimasukkan ke lokal ini!";
    }
    header("Location: kelola_kelas.php?id_kelas=$id_kelas");
    exit;
}

// === 6. KELUARKAN SANTRI DARI KELAS ===
if (isset($_GET['keluarkan_santri']) && isset($_GET['id_kelas'])) {
    $id_s = (int) $_GET['keluarkan_santri'];
    $id_k = (int) $_GET['id_kelas'];
    mysqli_query($conn, "UPDATE santri SET id_halaqah = NULL WHERE id = $id_s");
    $_SESSION['success'] = "Santri berhasil dikeluarkan dari lokal.";
    header("Location: kelola_kelas.php?id_kelas=$id_k");
    exit;
}

// Ambil semua daftar Kelas / Lokal beserta jumlah santrinya
$q_kelas = "SELECT h.*, u.nama_lengkap as nama_user_ustadz, u.username as user_ustadz,
            (SELECT COUNT(*) FROM santri s WHERE s.id_halaqah = h.id) as jumlah_santri
            FROM halaqah h
            LEFT JOIN users u ON h.id_ustadz = u.id
            ORDER BY h.id ASC";
$kelas_list = mysqli_fetch_all(mysqli_query($conn, $q_kelas), MYSQLI_ASSOC);

// Ambil semua Ustadz/Ustadzah untuk dropdown pilihan pengajar
$ustadz_list = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama_lengkap, username FROM users WHERE role = 'ustadz' ORDER BY nama_lengkap ASC"), MYSQLI_ASSOC);

// Ambil semua santri untuk modal masukkan santri ke kelas
$semua_santri = mysqli_fetch_all(mysqli_query($conn, "SELECT s.*, h.nama_halaqah FROM santri s LEFT JOIN halaqah h ON s.id_halaqah = h.id ORDER BY s.nama ASC"), MYSQLI_ASSOC);

// Kelas yang sedang diklik / dipilih untuk dilihat detail santrinya
$id_kelas_aktif = isset($_GET['id_kelas']) ? (int) $_GET['id_kelas'] : 0;
$kelas_aktif = null;
$santri_di_kelas = [];

if ($id_kelas_aktif > 0) {
    foreach ($kelas_list as $kl) {
        if ((int)$kl['id'] === $id_kelas_aktif) {
            $kelas_aktif = $kl;
            break;
        }
    }
    if ($kelas_aktif) {
        $santri_di_kelas = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM santri WHERE id_halaqah = $id_kelas_aktif ORDER BY nama ASC"), MYSQLI_ASSOC);
    }
}

// Helper warna berdasarkan Keterangan Program (Sesuai Warna Tabel di Gambar)
function warnaKategoriLokal($ket) {
    if (stripos($ket, 'Asrama') !== false) {
        return [
            'bg_row' => 'bg-amber-50/70 hover:bg-amber-100/60',
            'badge'  => 'bg-amber-100 text-amber-800 border-amber-300',
            'border' => 'border-l-4 border-l-amber-500'
        ];
    } elseif (stripos($ket, 'Menginap') !== false) {
        return [
            'bg_row' => 'bg-sky-50/70 hover:bg-sky-100/60',
            'badge'  => 'bg-sky-100 text-sky-800 border-sky-300',
            'border' => 'border-l-4 border-l-sky-500'
        ];
    } elseif (stripos($ket, 'Sebelum Maghrib') !== false) {
        return [
            'bg_row' => 'bg-lime-50/70 hover:bg-lime-100/60',
            'badge'  => 'bg-lime-100 text-lime-800 border-lime-300',
            'border' => 'border-l-4 border-l-lime-600'
        ];
    }
    // Default: Tahfidz Sore (16.30 - Isya) -> Warna Rose/Merah Muda sesuai gambar
    return [
        'bg_row' => 'bg-rose-50/60 hover:bg-rose-100/50',
        'badge'  => 'bg-rose-100 text-rose-800 border-rose-300',
        'border' => 'border-l-4 border-l-rose-500'
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Kelas & Lokal - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- HEADER HALAMAN -->
        <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 flex items-center gap-3">
                    <div class="p-2.5 bg-indigo-100 rounded-xl">
                        <i data-lucide="school" class="w-7 h-7 text-indigo-600"></i>
                    </div>
                    Daftar Kelas & Lokal Halaqah
                </h1>
                <p class="text-sm text-slate-500 mt-2">Klik pada salah satu lokal untuk melihat daftar santri dan Ustadz/Ustadzah yang ditugaskan.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <?php if (empty($kelas_list)): ?>
                <form method="POST" class="m-0">
                    <input type="hidden" name="seed_default_lokal" value="1">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-4 rounded-xl transition shadow-md flex items-center gap-2 text-xs">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>Muat 31 Lokal Sesuai Tabel</span>
                    </button>
                </form>
                <?php endif; ?>

                <button type="button" onclick="bukaModalTambahKelas()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-5 rounded-xl transition shadow-lg shadow-indigo-200 flex items-center gap-2 text-sm">
                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                    <span>Tambah Lokal Baru</span>
                </button>
            </div>
        </div>

        <!-- NOTIFIKASI -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3.5 rounded-xl mb-6 text-sm flex items-center gap-3 shadow-sm">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                <span class="font-medium"><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3.5 rounded-xl mb-6 text-sm flex items-center gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 shrink-0"></i>
                <span class="font-medium"><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- PANEL DETAIL KELAS (MUNCUL SAAT SALAH SATU LOKAL DIKLIK) -->
        <?php if ($kelas_aktif): 
            $nama_pengajar_tampil = !empty($kelas_aktif['nama_user_ustadz']) ? $kelas_aktif['nama_user_ustadz'] : ($kelas_aktif['nama_pengajar'] ?: 'Belum Ditentukan');
            $tema = warnaKategoriLokal($kelas_aktif['keterangan'] ?? '');
        ?>
        <div id="panelDetailKelas" class="bg-white border-2 border-indigo-500 rounded-2xl shadow-xl mb-8 overflow-hidden">
            <!-- Header Detail Lokal -->
            <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-6 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-3 py-0.5 rounded-full text-[11px] font-bold uppercase bg-indigo-500/30 border border-indigo-400/40 text-indigo-200">
                            Detail Kelas / Lokal Terpilih
                        </span>
                        <span class="px-3 py-0.5 rounded-full text-[11px] font-bold bg-white/10 text-white">
                            <?= htmlspecialchars($kelas_aktif['keterangan'] ?: 'Program Reguler') ?>
                        </span>
                    </div>
                    <h2 class="text-2xl font-extrabold tracking-tight"><?= htmlspecialchars($kelas_aktif['nama_halaqah']) ?></h2>
                    <p class="text-xs text-slate-300 mt-1 flex items-center gap-2">
                        <i data-lucide="user-check" class="w-4 h-4 text-emerald-400"></i>
                        <span>Ustadz / Ustadzah Pengampu: <b class="text-white"><?= htmlspecialchars($nama_pengajar_tampil) ?></b></span>
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <button type="button" onclick="bukaModalAssignSantri()" class="bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-md">
                        <i data-lucide="user-plus" class="w-4 h-4"></i> Masukkan Santri ke Lokal Ini
                    </button>
                    <button type="button" onclick='bukaModalEditKelas(<?= json_encode($kelas_aktif, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold px-3.5 py-2.5 rounded-xl transition flex items-center gap-1.5">
                        <i data-lucide="edit-3" class="w-4 h-4"></i> Edit Lokal
                    </button>
                    <a href="kelola_kelas.php" class="bg-white/10 hover:bg-white/20 text-white text-xs font-semibold px-3.5 py-2.5 rounded-xl transition flex items-center gap-1">
                        <i data-lucide="x" class="w-4 h-4"></i> Tutup
                    </a>
                </div>
            </div>

            <!-- Daftar Santri di Lokal Ini -->
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="graduation-cap" class="w-4 h-4 text-indigo-600"></i>
                        Daftar Santri di <?= htmlspecialchars($kelas_aktif['nama_halaqah']) ?> (<?= count($santri_di_kelas) ?> Santri)
                    </h3>
                </div>

                <?php if (empty($santri_di_kelas)): ?>
                    <div class="bg-slate-50 border border-dashed border-slate-300 rounded-xl p-8 text-center">
                        <i data-lucide="users" class="w-8 h-8 text-slate-400 mx-auto mb-2"></i>
                        <p class="text-sm font-semibold text-slate-700">Belum ada santri di lokal ini</p>
                        <p class="text-xs text-slate-500 mt-1 mb-4">Klik tombol "Masukkan Santri ke Lokal Ini" untuk memilih santri yang menghafal di kelas ini.</p>
                        <button type="button" onclick="bukaModalAssignSantri()" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2 rounded-lg transition inline-flex items-center gap-1.5">
                            <i data-lucide="user-plus" class="w-3.5 h-3.5"></i> Pilih & Masukkan Santri
                        </button>
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="w-full text-left border-collapse">
                            <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="py-3 px-4 w-12 text-center">No</th>
                                    <th class="py-3 px-4">Nama Santri</th>
                                    <th class="py-3 px-4">NIS</th>
                                    <th class="py-3 px-4 text-center">L/P</th>
                                    <th class="py-3 px-4">Orang Tua / Wali</th>
                                    <th class="py-3 px-4 text-center">Pintasan & Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y divide-slate-100">
                                <?php foreach ($santri_di_kelas as $idx => $s): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-3 px-4 text-center text-xs font-semibold text-slate-400"><?= $idx + 1 ?></td>
                                    <td class="py-3 px-4 font-bold text-slate-900"><?= htmlspecialchars($s['nama']) ?></td>
                                    <td class="py-3 px-4 font-mono text-xs text-slate-600"><?= htmlspecialchars($s['nis'] ?: '-') ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="px-2 py-0.5 rounded text-xs font-bold <?= $s['jenis_kelamin'] === 'L' ? 'bg-blue-50 text-blue-700' : 'bg-pink-50 text-pink-700' ?>">
                                            <?= $s['jenis_kelamin'] === 'L' ? 'Putra' : 'Putri' ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-xs text-slate-600"><?= htmlspecialchars($s['nama_ortu'] ?: '-') ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="progres_santri.php?id_santri=<?= $s['id'] ?>" class="text-xs font-semibold text-indigo-600 hover:underline flex items-center gap-1">
                                                <i data-lucide="line-chart" class="w-3.5 h-3.5"></i> Progres
                                            </a>
                                            <span class="text-slate-300">|</span>
                                            <a href="rapor_santri.php?id_santri=<?= $s['id'] ?>" class="text-xs font-semibold text-emerald-600 hover:underline flex items-center gap-1">
                                                <i data-lucide="file-text" class="w-3.5 h-3.5"></i> Rapor
                                            </a>
                                            <span class="text-slate-300">|</span>
                                            <a href="kelola_kelas.php?id_kelas=<?= $kelas_aktif['id'] ?>&keluarkan_santri=<?= $s['id'] ?>" 
                                               onclick="return confirm('Keluarkan <?= htmlspecialchars(addslashes($s['nama'])) ?> dari lokal ini?')"
                                               class="text-xs font-semibold text-rose-600 hover:underline flex items-center gap-1">
                                                <i data-lucide="user-minus" class="w-3.5 h-3.5"></i> Keluarkan
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- TOOLBAR FILTER & PENCARIAN LOKAL -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 mb-6 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" onclick="filterKategoriLokal('ALL', this)" class="btn-kat bg-slate-900 text-white text-xs font-bold px-3.5 py-2 rounded-xl transition">
                    Semua Lokal (<?= count($kelas_list) ?>)
                </button>
                <button type="button" onclick="filterKategoriLokal('Asrama', this)" class="btn-kat bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-xs font-bold px-3.5 py-2 rounded-xl transition">
                    🏠 Asrama
                </button>
                <button type="button" onclick="filterKategoriLokal('Menginap', this)" class="btn-kat bg-sky-50 hover:bg-sky-100 text-sky-800 border border-sky-200 text-xs font-bold px-3.5 py-2 rounded-xl transition">
                    🌙 Tahfidz Sore + Menginap
                </button>
                <button type="button" onclick="filterKategoriLokal('16.30 - Isya', this)" class="btn-kat bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 text-xs font-bold px-3.5 py-2 rounded-xl transition">
                    📖 Tahfidz Sore (16.30 - Isya)
                </button>
                <button type="button" onclick="filterKategoriLokal('Sebelum Maghrib', this)" class="btn-kat bg-lime-50 hover:bg-lime-100 text-lime-800 border border-lime-200 text-xs font-bold px-3.5 py-2 rounded-xl transition">
                    ☀️ Sebelum Maghrib
                </button>
            </div>

            <div class="relative w-full sm:w-64">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                <input type="text" id="cariLokal" oninput="terapkanFilterLokal()" placeholder="Cari lokal atau ustadz/ah..." 
                    class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
        </div>

        <!-- TABEL DAFTAR LOKAL (PERSIS STRUKTUR GAMBAR + KLIK INTERAKTIF) -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-900 text-white uppercase text-xs tracking-wider">
                        <tr>
                            <th class="py-4 px-5 font-bold w-14 text-center border-r border-slate-700">NO</th>
                            <th class="py-4 px-5 font-bold border-r border-slate-700">LOKAL / KELAS</th>
                            <th class="py-4 px-5 font-bold border-r border-slate-700">USTADZ / USTADZAH</th>
                            <th class="py-4 px-5 font-bold border-r border-slate-700">KET (PROGRAM / WAKTU)</th>
                            <th class="py-4 px-5 font-bold text-center w-32 border-r border-slate-700">SANTRI</th>
                            <th class="py-4 px-5 font-bold text-center w-36">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-200">
                        <?php if (empty($kelas_list)): ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    <i data-lucide="school" class="w-10 h-10 mx-auto mb-2 text-slate-400 opacity-50"></i>
                                    <p class="font-bold text-slate-700">Belum ada daftar Lokal / Kelas</p>
                                    <p class="text-xs text-slate-400 mt-1">Klik tombol <b>"Muat 31 Lokal Sesuai Tabel"</b> di kanan atas untuk mengisi otomatis, atau klik <b>"Tambah Lokal Baru"</b>.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($kelas_list as $i => $k): 
                                $pengajar = !empty($k['nama_user_ustadz']) ? $k['nama_user_ustadz'] : ($k['nama_pengajar'] ?: 'Belum ditentukan');
                                $ket_teks = $k['keterangan'] ?: 'Tahfidz Sore ( 16.30 - Isya )';
                                $warna    = warnaKategoriLokal($ket_teks);
                                $is_selected = ($id_kelas_aktif === (int)$k['id']);
                            ?>
                            <tr onclick="window.location.href='kelola_kelas.php?id_kelas=<?= $k['id'] ?>'" 
                                class="baris-lokal cursor-pointer transition <?= $warna['bg_row'] ?> <?= $warna['border'] ?> <?= $is_selected ? 'ring-2 ring-inset ring-indigo-600 font-semibold' : '' ?>"
                                data-ket="<?= htmlspecialchars($ket_teks) ?>"
                                data-search="<?= strtolower(htmlspecialchars($k['nama_halaqah'] . ' ' . $pengajar . ' ' . $ket_teks)) ?>">
                                
                                <td class="py-3.5 px-5 text-center font-bold text-slate-700 border-r border-slate-200/80 nomor-lokal"><?= $i + 1 ?></td>
                                
                                <td class="py-3.5 px-5 font-bold text-slate-900 border-r border-slate-200/80">
                                    <div class="flex items-center justify-between gap-2">
                                        <span><?= htmlspecialchars($k['nama_halaqah']) ?></span>
                                        <span class="text-[11px] font-semibold text-indigo-600 opacity-80 flex items-center gap-1">
                                            Lihat Santri <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                                        </span>
                                    </div>
                                </td>

                                <td class="py-3.5 px-5 font-medium text-slate-800 border-r border-slate-200/80">
                                    <?= htmlspecialchars($pengajar) ?>
                                </td>

                                <td class="py-3.5 px-5 border-r border-slate-200/80">
                                    <span class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold border <?= $warna['badge'] ?>">
                                        <?= htmlspecialchars($ket_teks) ?>
                                    </span>
                                </td>

                                <td class="py-3.5 px-5 text-center border-r border-slate-200/80">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-white border border-slate-300 text-slate-700 shadow-sm">
                                        <i data-lucide="users" class="w-3 h-3 text-indigo-600"></i>
                                        <?= (int)$k['jumlah_santri'] ?>
                                    </span>
                                </td>

                                <td class="py-3.5 px-5 text-center" onclick="event.stopPropagation();">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" 
                                            onclick='bukaModalEditKelas(<?= json_encode($k, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            title="Edit Lokal"
                                            class="p-1.5 bg-white hover:bg-amber-50 text-amber-600 border border-slate-200 rounded-lg transition">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                        <a href="kelola_kelas.php?hapus_kelas=<?= $k['id'] ?>" 
                                           onclick="return confirm('Hapus lokal <?= htmlspecialchars(addslashes($k['nama_halaqah'])) ?>? (Data santri di dalamnya tidak akan terhapus)')"
                                           title="Hapus Lokal"
                                           class="p-1.5 bg-white hover:bg-rose-50 text-rose-600 border border-slate-200 rounded-lg transition">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL 1: TAMBAH LOKAL / KELAS BARU -->
    <div id="modalTambahKelas" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center">
                <h3 class="text-base font-bold flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-5 h-5 text-indigo-400"></i> Tambah Kelas / Lokal Baru
                </h3>
                <button type="button" onclick="tutupModalTambahKelas()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form method="POST" class="p-6 space-y-4" autocomplete="off">
                <input type="hidden" name="tambah_kelas" value="1">
                
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Nama Lokal / Kelas <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_halaqah" required placeholder="Cth: Lokal Unggul 1 Laki-Laki / Karantina Full"
                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Pilih dari Akun Ustadz/Ustadzah (Opsional)</label>
                    <select name="id_ustadz" onchange="isiNamaDariDropdown(this, 'tambah_nama_pengajar')" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">-- Atau Ketik Manual Nama Pengajar di Bawah --</option>
                        <?php foreach ($ustadz_list as $u): ?>
                            <option value="<?= $u['id'] ?>" data-nama="<?= htmlspecialchars($u['nama_lengkap']) ?>">
                                <?= htmlspecialchars($u['nama_lengkap']) ?> (@<?= htmlspecialchars($u['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Nama Ustadz / Ustadzah Pengampu <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_pengajar" id="tambah_nama_pengajar" required placeholder="Cth: Muhammad Zaid Al Harits"
                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Keterangan Program / Waktu (KET) <span class="text-rose-500">*</span></label>
                    <input type="text" name="keterangan" list="listKeterangan" required placeholder="Pilih atau ketik keterangan jadwal..."
                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    <datalist id="listKeterangan">
                        <option value="Asrama">
                        <option value="Tahfidz Sore + Menginap sekali seminggu">
                        <option value="Tahfidz Sore ( 16.30 - Isya )">
                        <option value="Tahfidz Sore ( 16.30 - Sebelum Maghrib )">
                    </datalist>
                </div>

                <div class="pt-3 flex justify-end gap-2">
                    <button type="button" onclick="tutupModalTambahKelas()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 text-xs font-bold">Batal</button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-xl text-xs shadow-md">Simpan Lokal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDIT LOKAL / KELAS -->
    <div id="modalEditKelas" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center">
                <h3 class="text-base font-bold flex items-center gap-2">
                    <i data-lucide="edit-3" class="w-5 h-5 text-amber-400"></i> Edit Kelas / Lokal
                </h3>
                <button type="button" onclick="tutupModalEditKelas()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form method="POST" class="p-6 space-y-4" autocomplete="off">
                <input type="hidden" name="edit_kelas" value="1">
                <input type="hidden" name="id_kelas" id="edit_id_kelas">
                
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Nama Lokal / Kelas</label>
                    <input type="text" name="nama_halaqah" id="edit_nama_halaqah" required
                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Tautkan Akun Ustadz/Ustadzah (Opsional)</label>
                    <select name="id_ustadz" id="edit_id_ustadz" onchange="isiNamaDariDropdown(this, 'edit_nama_pengajar')" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">-- Ketik Manual di Bawah --</option>
                        <?php foreach ($ustadz_list as $u): ?>
                            <option value="<?= $u['id'] ?>" data-nama="<?= htmlspecialchars($u['nama_lengkap']) ?>">
                                <?= htmlspecialchars($u['nama_lengkap']) ?> (@<?= htmlspecialchars($u['username']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Nama Ustadz / Ustadzah Pengampu</label>
                    <input type="text" name="nama_pengajar" id="edit_nama_pengajar" required
                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Keterangan Program / Waktu (KET)</label>
                    <input type="text" name="keterangan" id="edit_keterangan" list="listKeterangan" required
                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="pt-3 flex justify-end gap-2">
                    <button type="button" onclick="tutupModalEditKelas()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 text-xs font-bold">Batal</button>
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2.5 px-6 rounded-xl text-xs shadow-md">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: MASUKKAN SANTRI KE LOKAL INI -->
    <?php if ($kelas_aktif): ?>
    <div id="modalAssignSantri" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-xl overflow-hidden">
            <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center">
                <div>
                    <h3 class="text-base font-bold">Masukkan Santri ke <?= htmlspecialchars($kelas_aktif['nama_halaqah']) ?></h3>
                    <p class="text-xs text-slate-400">Centang nama santri yang ingin ditempatkan di lokal ini</p>
                </div>
                <button type="button" onclick="tutupModalAssignSantri()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            <form method="POST" class="p-6">
                <input type="hidden" name="assign_santri" value="1">
                <input type="hidden" name="id_kelas" value="<?= $kelas_aktif['id'] ?>">

                <div class="max-h-72 overflow-y-auto border border-slate-200 rounded-xl divide-y divide-slate-100 mb-5">
                    <?php foreach ($semua_santri as $st): 
                        $sudah_di_sini = ((int)$st['id_halaqah'] === (int)$kelas_aktif['id']);
                    ?>
                    <label class="flex items-center justify-between p-3 hover:bg-slate-50 cursor-pointer">
                        <div class="flex items-center gap-3">
                            <input type="checkbox" name="id_santri_arr[]" value="<?= $st['id'] ?>" <?= $sudah_di_sini ? 'checked' : '' ?>
                                class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($st['nama']) ?></p>
                                <p class="text-[11px] text-slate-400">NIS: <?= htmlspecialchars($st['nis'] ?: '-') ?> • <?= $st['jenis_kelamin'] === 'L' ? 'Laki-Laki' : 'Perempuan' ?></p>
                            </div>
                        </div>
                        <span class="text-[11px] px-2.5 py-0.5 rounded-full font-semibold <?= $sudah_di_sini ? 'bg-emerald-100 text-emerald-700' : (!empty($st['nama_halaqah']) ? 'bg-slate-100 text-slate-600' : 'bg-amber-50 text-amber-700') ?>">
                            <?= $sudah_di_sini ? 'Sudah di Lokal Ini' : ($st['nama_halaqah'] ?: 'Belum punya lokal') ?>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" onclick="tutupModalAssignSantri()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 text-xs font-bold">Batal</button>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-6 rounded-xl text-xs shadow-md">Simpan Penempatan Santri</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        lucide.createIcons();

        let filterKetAktif = 'ALL';

        function filterKategoriLokal(ket, btn) {
            filterKetAktif = ket.toLowerCase();
            document.querySelectorAll('.btn-kat').forEach(b => b.classList.remove('ring-2', 'ring-indigo-600'));
            btn.classList.add('ring-2', 'ring-indigo-600');
            terapkanFilterLokal();
        }

        function terapkanFilterLokal() {
            const keyword = document.getElementById('cariLokal').value.toLowerCase();
            let urut = 0;
            document.querySelectorAll('.baris-lokal').forEach(tr => {
                const ket = tr.dataset.ket.toLowerCase();
                const cocokKet = (filterKetAktif === 'all' || ket.includes(filterKetAktif));
                const cocokKata = tr.dataset.search.includes(keyword);

                if (cocokKet && cocokKata) {
                    tr.style.display = '';
                    urut++;
                    tr.querySelector('.nomor-lokal').innerText = urut;
                } else {
                    tr.style.display = 'none';
                }
            });
        }

        function isiNamaDariDropdown(selectEl, targetInputId) {
            const opt = selectEl.options[selectEl.selectedIndex];
            const nama = opt.getAttribute('data-nama');
            if (nama) {
                document.getElementById(targetInputId).value = nama;
            }
        }

        function bukaModalTambahKelas() { document.getElementById('modalTambahKelas').classList.remove('hidden'); }
        function tutupModalTambahKelas() { document.getElementById('modalTambahKelas').classList.add('hidden'); }

        function bukaModalEditKelas(data) {
            document.getElementById('edit_id_kelas').value = data.id;
            document.getElementById('edit_nama_halaqah').value = data.nama_halaqah || '';
            document.getElementById('edit_id_ustadz').value = data.id_ustadz || '';
            document.getElementById('edit_nama_pengajar').value = data.nama_user_ustadz || data.nama_pengajar || '';
            document.getElementById('edit_keterangan').value = data.keterangan || '';
            document.getElementById('modalEditKelas').classList.remove('hidden');
        }
        function tutupModalEditKelas() { document.getElementById('modalEditKelas').classList.add('hidden'); }

        function bukaModalAssignSantri() { document.getElementById('modalAssignSantri').classList.remove('hidden'); }
        function tutupModalAssignSantri() { document.getElementById('modalAssignSantri').classList.add('hidden'); }
    </script>
</body>
</html>