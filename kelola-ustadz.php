<?php
session_start();
require 'config/database.php';

// 🔒 Keamanan: Hanya Pimpinan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') {
    header("Location: login.php");
    exit;
}

// Otomatis tambahkan kolom jenis_kelamin di tabel users jika belum ada
$cek_kolom = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'jenis_kelamin'");
if (mysqli_num_rows($cek_kolom) === 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN jenis_kelamin ENUM('L','P') DEFAULT 'L' AFTER nama_lengkap");
}

// Otomatis tambahkan kolom id_halaqah di tabel users jika belum ada
$cek_kolom_h = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'id_halaqah'");
if (mysqli_num_rows($cek_kolom_h) === 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN id_halaqah INT(11) DEFAULT NULL AFTER role");
}

// === 1. PROSES TAMBAH USTADZ / USTADZAH ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_ustadz'])) {
    $nama_lengkap  = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $username      = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password      = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama_lokal    = mysqli_real_escape_string($conn, trim($_POST['nama_lokal']));
    $sebutan       = ($jenis_kelamin === 'P') ? 'Ustadzah' : 'Ustadz';

    // Cek apakah Lokal/Halaqah sudah ada, jika belum buat baru
    $cek_lokal = mysqli_query($conn, "SELECT id FROM halaqah WHERE nama_halaqah = '$nama_lokal'");
    if (mysqli_num_rows($cek_lokal) > 0) {
        $id_halaqah = mysqli_fetch_assoc($cek_lokal)['id'];
    } else {
        mysqli_query($conn, "INSERT INTO halaqah (nama_halaqah) VALUES ('$nama_lokal')");
        $id_halaqah = mysqli_insert_id($conn);
    }

    // Cek duplikasi Username
    $cek_user = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek_user) > 0) {
        $_SESSION['error'] = "Username '$username' sudah digunakan! Silakan gunakan username lain.";
    } else {
        $sql = "INSERT INTO users (username, password, nama_lengkap, jenis_kelamin, role, id_halaqah) 
                VALUES ('$username', '$password', '$nama_lengkap', '$jenis_kelamin', 'ustadz', $id_halaqah)";
        if (mysqli_query($conn, $sql)) {
            $id_baru = mysqli_insert_id($conn);
            mysqli_query($conn, "UPDATE halaqah SET id_ustadz = $id_baru WHERE id = $id_halaqah");
            $_SESSION['success'] = "$sebutan '$nama_lengkap' berhasil ditambahkan ke $nama_lokal!";
        } else {
            $_SESSION['error'] = "Gagal menyimpan data: " . mysqli_error($conn);
        }
    }
    header("Location: kelola-ustadz.php");
    exit;
}

// === 2. PROSES EDIT USTADZ / USTADZAH ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_ustadz'])) {
    $id_user       = (int) $_POST['id_user'];
    $nama_lengkap  = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $jenis_kelamin = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $username      = mysqli_real_escape_string($conn, trim($_POST['username']));
    $nama_lokal    = mysqli_real_escape_string($conn, trim($_POST['nama_lokal']));
    $password_baru = trim($_POST['password_baru'] ?? '');
    $sebutan       = ($jenis_kelamin === 'P') ? 'Ustadzah' : 'Ustadz';

    // Cek username bentrok dengan user lain
    $cek_user = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username' AND id != $id_user");
    if (mysqli_num_rows($cek_user) > 0) {
        $_SESSION['error'] = "Username '$username' sudah dipakai pengguna lain!";
        header("Location: kelola-ustadz.php");
        exit;
    }

    // Cek / Buat Lokal Mengajar
    $cek_lokal = mysqli_query($conn, "SELECT id FROM halaqah WHERE nama_halaqah = '$nama_lokal'");
    if (mysqli_num_rows($cek_lokal) > 0) {
        $id_halaqah = mysqli_fetch_assoc($cek_lokal)['id'];
    } else {
        mysqli_query($conn, "INSERT INTO halaqah (nama_halaqah) VALUES ('$nama_lokal')");
        $id_halaqah = mysqli_insert_id($conn);
    }

    $pass_sql = "";
    if (!empty($password_baru)) {
        $hash = password_hash($password_baru, PASSWORD_DEFAULT);
        $pass_sql = ", password = '$hash'";
    }

    $sql_update = "UPDATE users SET 
                    nama_lengkap = '$nama_lengkap',
                    jenis_kelamin = '$jenis_kelamin',
                    username = '$username',
                    id_halaqah = $id_halaqah
                    $pass_sql
                   WHERE id = $id_user AND role = 'ustadz'";

    if (mysqli_query($conn, $sql_update)) {
        mysqli_query($conn, "UPDATE halaqah SET id_ustadz = $id_user WHERE id = $id_halaqah");
        $_SESSION['success'] = "Data $sebutan '$nama_lengkap' berhasil diperbarui!";
    } else {
        $_SESSION['error'] = "Gagal memperbarui data.";
    }
    header("Location: kelola-ustadz.php");
    exit;
}

// === 3. PROSES HAPUS USTADZ / USTADZAH ===
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($conn, "UPDATE halaqah SET id_ustadz = NULL WHERE id_ustadz = $id");
    mysqli_query($conn, "DELETE FROM users WHERE id = $id AND role = 'ustadz'");
    $_SESSION['success'] = "Data pengajar (Ustadz/Ustadzah) berhasil dihapus.";
    header("Location: kelola-ustadz.php");
    exit;
}

// Ambil data seluruh pengajar beserta nama lokalnya
$query_ustadz = "SELECT u.*, h.nama_halaqah 
                 FROM users u 
                 LEFT JOIN halaqah h ON (u.id_halaqah = h.id OR h.id_ustadz = u.id) 
                 WHERE u.role = 'ustadz' 
                 ORDER BY u.nama_lengkap ASC";
$res_ustadz = mysqli_query($conn, $query_ustadz);
$ustadz_list = $res_ustadz ? mysqli_fetch_all($res_ustadz, MYSQLI_ASSOC) : [];

// Hitung Statistik Ustadz & Ustadzah
$total_pengajar = count($ustadz_list);
$total_ustadz   = 0;
$total_ustadzah = 0;
foreach ($ustadz_list as $u) {
    // Deteksi otomatis: jika jenis_kelamin 'P' atau namanya mengandung kata 'ustadzah'
    $is_putri = (($u['jenis_kelamin'] ?? 'L') === 'P' || stripos($u['nama_lengkap'], 'ustadzah') !== false);
    if ($is_putri) {
        $total_ustadzah++;
    } else {
        $total_ustadz++;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Ustadz & Ustadzah - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gender-radio input[type="radio"] { display: none; }
        .gender-radio input[value="L"]:checked + div {
            border-color: #4f46e5; background-color: #eef2ff; color: #4338ca;
        }
        .gender-radio input[value="P"]:checked + div {
            border-color: #0d9488; background-color: #f0fdfa; color: #0f766e;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- Header Halaman -->
        <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 flex items-center gap-3">
                    <div class="p-2.5 bg-indigo-100 rounded-xl">
                        <i data-lucide="users" class="w-7 h-7 text-indigo-600"></i>
                    </div>
                    Kelola Ustadz & Ustadzah
                </h1>
                <p class="text-sm text-slate-500 mt-2">Manajemen akun pengajar tahfidz (Ustadz & Ustadzah) beserta penempatan lokal halaqah.</p>
            </div>
            <button type="button" onclick="toggleFormPengajar()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-5 rounded-xl transition shadow-lg shadow-indigo-200 flex items-center gap-2 text-sm">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Tambah Ustadz / Ustadzah</span>
            </button>
        </div>

        <!-- Notifikasi -->
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

        <!-- Kartu Statistik Pengajar -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Pengajar</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-1"><?= $total_pengajar ?></p>
                    <p class="text-xs text-slate-400 mt-1">Pembimbing Halaqah Aktif</p>
                </div>
                <div class="w-12 h-12 bg-slate-100 text-slate-700 rounded-2xl flex items-center justify-center">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Ustadz (Pengajar Putra)</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-1"><?= $total_ustadz ?></p>
                    <p class="text-xs text-slate-400 mt-1">Pembimbing Santriwan</p>
                </div>
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center">
                    <i data-lucide="user-check" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-teal-600 uppercase tracking-wider">Ustadzah (Pengajar Putri)</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-1"><?= $total_ustadzah ?></p>
                    <p class="text-xs text-slate-400 mt-1">Pembimbing Santriwati</p>
                </div>
                <div class="w-12 h-12 bg-teal-50 text-teal-600 rounded-2xl flex items-center justify-center">
                    <i data-lucide="sparkles" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- FORM TAMBAH USTADZ / USTADZAH (Collapsible) -->
        <div id="formPengajarContainer" class="<?= empty($ustadz_list) ? '' : 'hidden' ?> bg-white border border-indigo-200 rounded-2xl shadow-md mb-8 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-teal-600 text-white flex justify-between items-center">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="user-plus" class="w-5 h-5"></i>
                    <h3 class="text-base font-semibold">Formulir Penambahan Ustadz / Ustadzah Baru</h3>
                </div>
                <button type="button" onclick="toggleFormPengajar()" class="text-white/80 hover:text-white transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" class="p-6" autocomplete="off">
                <input type="hidden" name="tambah_ustadz" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                    
                    <!-- Kategori Ustadz / Ustadzah -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Kategori Pengajar <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <label class="gender-radio cursor-pointer">
                                <input type="radio" name="jenis_kelamin" value="L" checked onchange="updatePrefixPengajar()">
                                <div class="border border-slate-300 rounded-xl py-2.5 px-3 text-center text-xs font-bold text-slate-600 transition">
                                    👳‍♂️ Ustadz (L)
                                </div>
                            </label>
                            <label class="gender-radio cursor-pointer">
                                <input type="radio" name="jenis_kelamin" value="P" onchange="updatePrefixPengajar()">
                                <div class="border border-slate-300 rounded-xl py-2.5 px-3 text-center text-xs font-bold text-slate-600 transition">
                                    🧕 Ustadzah (P)
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Nama Lengkap -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Nama Lengkap & Gelar <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <input type="text" name="nama_lengkap" id="inputNamaPengajar" oninput="generateUsernamePengajar()" required 
                                class="w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" 
                                placeholder="Cth: Ustadz Ahmad / Ustadzah Aisyah">
                        </div>
                    </div>

                    <!-- Lokal Mengajar -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Lokal / Halaqah Mengajar <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i data-lucide="bookmark" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <input type="text" name="nama_lokal" list="listLokal" required 
                                class="w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none" 
                                placeholder="Cth: Khusus Laki-Laki / Lokal 1">
                            <datalist id="listLokal">
                                <option value="Khusus Laki-Laki">
                                <option value="Khusus Perempuan">
                                <option value="Halaqah Juz 30">
                                <option value="Halaqah Tahfidz Lanjutan">
                            </datalist>
                        </div>
                    </div>

                    <!-- Username -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Username Login <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i data-lucide="at-sign" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <input type="text" name="username" id="inputUsernamePengajar" required 
                                class="w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none" 
                                placeholder="ustadz_nama">
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Password Default <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i data-lucide="key-round" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <input type="text" name="password" id="inputPasswordPengajar" value="ustadz123" required 
                                class="w-full pl-10 pr-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>

                    <!-- Tombol Submit -->
                    <div class="flex items-end justify-end gap-2">
                        <button type="button" onclick="toggleFormPengajar()" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-sm font-medium transition">
                            Batal
                        </button>
                        <button type="submit" class="w-full md:w-auto bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-6 rounded-xl transition shadow-md flex items-center justify-center gap-2 text-sm">
                            <i data-lucide="save" class="w-4 h-4"></i> Simpan Pengajar
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- TABEL DAFTAR USTADZ & USTADZAH -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            
            <!-- Toolbar Filter & Search -->
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Daftar Ustadz & Ustadzah Terdaftar</h3>
                    <p class="text-xs text-slate-500">Menampilkan <span id="jumlahTampil" class="font-bold text-slate-700"><?= $total_pengajar ?></span> akun pengajar</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <select id="filterKategori" onchange="filterPengajar()" class="px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
                        <option value="ALL">Semua Pengajar (Ustadz & Ustadzah)</option>
                        <option value="L">👳‍♂️ Khusus Ustadz (Putra)</option>
                        <option value="P">🧕 Khusus Ustadzah (Putri)</option>
                    </select>

                    <div class="relative w-64">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                        <input type="text" id="cariPengajar" oninput="filterPengajar()" placeholder="Cari nama, username, lokal..." 
                            class="w-full pl-9 pr-3 py-2 bg-white border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-slate-400 uppercase text-[11px] tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-6 font-semibold w-12 text-center">No</th>
                            <th class="py-3.5 px-4 font-semibold">Nama Pengajar</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Kategori</th>
                            <th class="py-3.5 px-4 font-semibold">Username Login</th>
                            <th class="py-3.5 px-4 font-semibold">Lokal / Halaqah</th>
                            <th class="py-3.5 px-6 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100">
                        <?php if (empty($ustadz_list)): ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <i data-lucide="users" class="w-10 h-10 mx-auto mb-2 opacity-40"></i>
                                    <p class="font-medium text-slate-600">Belum ada data Ustadz / Ustadzah</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ustadz_list as $index => $u): 
                                $jk = ($u['jenis_kelamin'] ?? 'L');
                                if (stripos($u['nama_lengkap'], 'ustadzah') !== false) $jk = 'P';
                                $is_ustadzah = ($jk === 'P');
                                
                                $avatar_cls = $is_ustadzah ? 'bg-teal-100 text-teal-700' : 'bg-indigo-100 text-indigo-700';
                                $badge_cls  = $is_ustadzah ? 'bg-teal-50 text-teal-700 border-teal-200' : 'bg-indigo-50 text-indigo-700 border-indigo-200';
                            ?>
                            <tr class="baris-pengajar hover:bg-slate-50/90 transition"
                                data-jk="<?= $jk ?>"
                                data-search="<?= strtolower(htmlspecialchars($u['nama_lengkap'] . ' ' . $u['username'] . ' ' . ($u['nama_halaqah'] ?? ''))) ?>">
                                
                                <td class="py-4 px-6 text-center text-xs font-semibold text-slate-400 nomor-urut"><?= $index + 1 ?></td>
                                
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl <?= $avatar_cls ?> font-bold text-sm flex items-center justify-center shrink-0">
                                            <?= strtoupper(substr($u['nama_lengkap'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900"><?= htmlspecialchars($u['nama_lengkap']) ?></p>
                                            <p class="text-[11px] text-slate-400">Terdaftar: <?= !empty($u['created_at']) ? date('d M Y', strtotime($u['created_at'])) : '-' ?></p>
                                        </div>
                                    </div>
                                </td>

                                <td class="py-4 px-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold border <?= $badge_cls ?>">
                                        <?= $is_ustadzah ? '🧕 Ustadzah' : '👳‍♂️ Ustadz' ?>
                                    </span>
                                </td>

                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg font-mono text-xs border border-slate-200">
                                        <i data-lucide="key" class="w-3 h-3 text-slate-400"></i>
                                        <?= htmlspecialchars($u['username']) ?>
                                    </span>
                                </td>

                                <td class="py-4 px-4">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        <i data-lucide="book-open" class="w-3.5 h-3.5"></i>
                                        <?= htmlspecialchars($u['nama_halaqah'] ?? 'Belum ditentukan') ?>
                                    </span>
                                </td>

                                <td class="py-4 px-6 text-center">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <button type="button" 
                                            onclick='bukaModalEditPengajar(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>, "<?= $jk ?>")'
                                            title="Edit / Reset Password"
                                            class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                        <a href="?hapus=<?= $u['id'] ?>" 
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus akun <?= htmlspecialchars(addslashes($u['nama_lengkap'])) ?>?')" 
                                           title="Hapus Akun"
                                           class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition">
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

    <!-- MODAL EDIT USTADZ / USTADZAH -->
    <div id="modalEditPengajar" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
            <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center">
                <h3 class="text-base font-bold flex items-center gap-2">
                    <i data-lucide="user-cog" class="w-5 h-5 text-amber-400"></i> Edit Data Ustadz / Ustadzah
                </h3>
                <button type="button" onclick="tutupModalEditPengajar()" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" class="p-6 space-y-4" autocomplete="off">
                <input type="hidden" name="edit_ustadz" value="1">
                <input type="hidden" name="id_user" id="edit_id_user">

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Kategori Pengajar</label>
                    <select name="jenis_kelamin" id="edit_jk_pengajar" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="L">👳‍♂️ Ustadz (Pengajar Putra)</option>
                        <option value="P">🧕 Ustadzah (Pengajar Putri)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="edit_nama_pengajar" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Lokal / Halaqah Mengajar</label>
                    <input type="text" name="nama_lokal" id="edit_lokal_pengajar" list="listLokal" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Username Login</label>
                        <input type="text" name="username" id="edit_username_pengajar" required class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Reset Password <span class="text-[10px] font-normal text-slate-400">(Opsional)</span></label>
                        <input type="text" name="password_baru" placeholder="Isi jika ingin ganti..." class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModalEditPengajar()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-sm font-medium">Batal</button>
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 px-6 rounded-xl transition shadow-md flex items-center gap-2 text-sm">
                        <i data-lucide="check" class="w-4 h-4"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function toggleFormPengajar() {
            const form = document.getElementById('formPengajarContainer');
            form.classList.toggle('hidden');
            if (!form.classList.contains('hidden')) {
                document.getElementById('inputNamaPengajar').focus();
            }
        }

        function updatePrefixPengajar() {
            const jk = document.querySelector('input[name="jenis_kelamin"]:checked').value;
            const passInput = document.getElementById('inputPasswordPengajar');
            if (passInput.value === 'ustadz123' || passInput.value === 'ustadzah123') {
                passInput.value = (jk === 'P') ? 'ustadzah123' : 'ustadz123';
            }
            generateUsernamePengajar();
        }

        function generateUsernamePengajar() {
            const nama = document.getElementById('inputNamaPengajar').value;
            const jk = document.querySelector('input[name="jenis_kelamin"]:checked').value;
            const prefix = (jk === 'P') ? 'ustadzah_' : 'ustadz_';

            // Hilangkan kata sapaan ustadz/ustadzah dari nama agar tidak dobel di username
            const bersih = nama.toLowerCase()
                .replace(/\bustadzah\b/g, '')
                .replace(/\bustadz\b/g, '')
                .replace(/\bust\b/g, '')
                .replace(/[^a-z0-9\s]/g, '')
                .trim();

            const kataPertama = bersih.split(/\s+/)[0] || '';
            document.getElementById('inputUsernamePengajar').value = kataPertama ? prefix + kataPertama : '';
        }

        function filterPengajar() {
            const keyword = document.getElementById('cariPengajar').value.toLowerCase();
            const kat = document.getElementById('filterKategori').value;
            const baris = document.querySelectorAll('.baris-pengajar');
            let tampil = 0;

            baris.forEach(tr => {
                const cocokKata = tr.dataset.search.includes(keyword);
                const cocokKat  = (kat === 'ALL' || tr.dataset.jk === kat);
                if (cocokKata && cocokKat) {
                    tr.style.display = '';
                    tampil++;
                    tr.querySelector('.nomor-urut').innerText = tampil;
                } else {
                    tr.style.display = 'none';
                }
            });
            document.getElementById('jumlahTampil').innerText = tampil;
        }

        function bukaModalEditPengajar(data, jk) {
            document.getElementById('edit_id_user').value = data.id;
            document.getElementById('edit_jk_pengajar').value = jk;
            document.getElementById('edit_nama_pengajar').value = data.nama_lengkap || '';
            document.getElementById('edit_lokal_pengajar').value = data.nama_halaqah || '';
            document.getElementById('edit_username_pengajar').value = data.username || '';
            document.getElementById('modalEditPengajar').classList.remove('hidden');
        }

        function tutupModalEditPengajar() {
            document.getElementById('modalEditPengajar').classList.add('hidden');
        }
    </script>
</body>
</html>
