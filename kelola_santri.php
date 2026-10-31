<?php
session_start();
require 'config/database.php';

// 🔒 Keamanan: Ustadz & Pimpinan dapat mengakses
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: dashboard.php");
    exit;
}

$query = "SELECT santri.*, users.username as username_ortu 
          FROM santri 
          LEFT JOIN users ON santri.id_user_ortu = users.id 
          ORDER BY santri.nama ASC";
$result = mysqli_query($conn, $query);
$santri_list = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Hitung Statistik Santri
$total_santri = count($santri_list);
$total_putra  = 0;
$total_putri  = 0;
foreach ($santri_list as $s) {
    if ($s['jenis_kelamin'] === 'L') $total_putra++;
    if ($s['jenis_kelamin'] === 'P') $total_putri++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Santri - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .gender-radio input[type="radio"] { display: none; }
        .gender-radio input[value="L"]:checked + div {
            border-color: #3b82f6; background-color: #eff6ff; color: #1d4ed8;
        }
        .gender-radio input[value="P"]:checked + div {
            border-color: #ec4899; background-color: #fdf2f8; color: #be185d;
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
                        <i data-lucide="graduation-cap" class="w-7 h-7 text-indigo-600"></i>
                    </div>
                    Kelola Data Santri
                </h1>
                <p class="text-sm text-slate-500 mt-2">Manajemen data induk santri binaan beserta integrasi akun portal orang tua.</p>
            </div>
            <button type="button" onclick="toggleFormSantri()" id="btnToggleForm" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-5 rounded-xl transition shadow-lg shadow-indigo-200 flex items-center gap-2 text-sm">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Tambah Santri Baru</span>
            </button>
        </div>

        <!-- Notifikasi Sukses & Error -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3.5 rounded-xl mb-6 text-sm flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                    <span class="font-medium"><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3.5 rounded-xl mb-6 text-sm flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-rose-600 shrink-0"></i>
                    <span class="font-medium"><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Kartu Statistik Ringkas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Santri Aktif</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-1"><?= $total_santri ?></p>
                    <p class="text-xs text-slate-400 mt-1">Terdaftar dalam sistem</p>
                </div>
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-blue-500 uppercase tracking-wider">Santriwan (Putra)</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-1"><?= $total_putra ?></p>
                    <p class="text-xs text-slate-400 mt-1"><?= $total_santri > 0 ? round(($total_putra / $total_santri) * 100) : 0 ?>% dari total santri</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center">
                    <i data-lucide="user-check" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-pink-500 uppercase tracking-wider">Santriwati (Putri)</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-1"><?= $total_putri ?></p>
                    <p class="text-xs text-slate-400 mt-1"><?= $total_santri > 0 ? round(($total_putri / $total_santri) * 100) : 0 ?>% dari total santri</p>
                </div>
                <div class="w-12 h-12 bg-pink-50 text-pink-600 rounded-2xl flex items-center justify-center">
                    <i data-lucide="heart-handshake" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- FORM TAMBAH SANTRI (Collapsible) -->
        <div id="formTambahContainer" class="<?= empty($santri_list) ? '' : 'hidden' ?> bg-white border border-indigo-200 rounded-2xl shadow-md mb-8 overflow-hidden transition-all">
            <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-indigo-700 text-white flex justify-between items-center">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="user-plus" class="w-5 h-5"></i>
                    <h3 class="text-base font-semibold">Formulir Pendaftaran Santri & Akun Wali</h3>
                </div>
                <button type="button" onclick="toggleFormSantri()" class="text-indigo-200 hover:text-white transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="proses_tambah_santri.php" method="POST" class="p-6" autocomplete="off">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    
                    <!-- Kolom Kiri: Biodata Santri -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                            <span class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center">1</span>
                            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Biodata Santri</h4>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nama Lengkap Santri <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                                    <input type="text" name="nama_santri" id="inputNamaSantri" oninput="generateUsernameOrtu(this.value)" required placeholder="Cth: Ahmad Fauzan"
                                        class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nomor Induk (NIS) <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <i data-lucide="hash" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                                    <input type="text" name="nis" required placeholder="Cth: 1024"
                                        class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Jenis Kelamin <span class="text-rose-500">*</span></label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label class="gender-radio cursor-pointer">
                                        <input type="radio" name="jenis_kelamin" value="L" checked required>
                                        <div class="border border-slate-300 rounded-xl py-2 px-3 text-center text-xs font-bold text-slate-600 transition flex items-center justify-center gap-1.5">
                                            <span>👦</span> Laki-laki
                                        </div>
                                    </label>
                                    <label class="gender-radio cursor-pointer">
                                        <input type="radio" name="jenis_kelamin" value="P">
                                        <div class="border border-slate-300 rounded-xl py-2 px-3 text-center text-xs font-bold text-slate-600 transition flex items-center justify-center gap-1.5">
                                            <span>👧</span> Perempuan
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tanggal Lahir <span class="text-rose-500">*</span></label>
                                <input type="date" name="tanggal_lahir" required
                                    class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none text-slate-700">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Alamat Lengkap <span class="text-rose-500">*</span></label>
                            <textarea name="alamat" rows="2" required placeholder="Cth: Jorong Jalikur Patanangan..."
                                class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none resize-none"></textarea>
                        </div>
                    </div>

                    <!-- Kolom Kanan: Data Orang Tua & Akun Login -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                            <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center justify-center">2</span>
                            <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Data Wali & Akun Portal Ortu</h4>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nama Orang Tua / Wali <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <i data-lucide="contact" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                                    <input type="text" name="nama_ortu" required placeholder="Cth: Bpk. Sulaiman"
                                        class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5">No. WhatsApp / HP <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <i data-lucide="phone" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                                    <input type="text" name="no_hp_ortu" required placeholder="Cth: 081234567890"
                                        class="w-full pl-9 pr-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                                    <i data-lucide="key-round" class="w-3.5 h-3.5 text-indigo-600"></i> Kredensial Login Orang Tua
                                </span>
                                <span class="text-[11px] text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded font-medium">Otomatis Dibuat</span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-500 mb-1">Username Login Ortu</label>
                                    <input type="text" name="username_ortu" id="inputUsernameOrtu" required placeholder="ortu_nama"
                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm font-mono text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-medium text-slate-500 mb-1">Password Default</label>
                                    <input type="text" name="password_ortu" value="ortu123" required
                                        class="w-full px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm font-mono text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none">
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400">Orang tua dapat login menggunakan username & password di atas untuk memantau hafalan, kehadiran, dan rapor anak.</p>
                        </div>
                    </div>

                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex justify-end gap-3">
                    <button type="button" onclick="toggleFormSantri()" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-sm font-medium transition">
                        Batal
                    </button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-6 rounded-xl transition shadow-md shadow-indigo-100 flex items-center gap-2 text-sm">
                        <i data-lucide="save" class="w-4 h-4"></i> Simpan Data & Buat Akun
                    </button>
                </div>
            </form>
        </div>

        <!-- TABEL DAFTAR SANTRI -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            
            <!-- Toolbar Pencarian & Filter -->
            <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold text-slate-800">Daftar Santri Binaan</h3>
                    <p class="text-xs text-slate-500">Menampilkan <span id="jumlahTampil" class="font-bold text-slate-700"><?= $total_santri ?></span> santri terdaftar</p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Filter JK -->
                    <select id="filterJK" onchange="filterTabelSantri()" class="px-3 py-2 bg-white border border-slate-300 rounded-xl text-xs font-medium text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer">
                        <option value="ALL">Semua Kategori (L/P)</option>
                        <option value="L">👦 Khusus Laki-laki (Putra)</option>
                        <option value="P">👧 Khusus Perempuan (Putri)</option>
                    </select>

                    <!-- Input Cari -->
                    <div class="relative w-64">
                        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                        <input type="text" id="cariSantri" oninput="filterTabelSantri()" placeholder="Cari nama, NIS, atau ortu..." 
                            class="w-full pl-9 pr-3 py-2 bg-white border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse" id="tabelSantri">
                    <thead class="bg-white text-slate-400 uppercase text-[11px] tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-6 font-semibold w-12 text-center">No</th>
                            <th class="py-3.5 px-4 font-semibold">Profil Santri</th>
                            <th class="py-3.5 px-4 font-semibold text-center">L/P</th>
                            <th class="py-3.5 px-4 font-semibold">Usia & Tgl Lahir</th>
                            <th class="py-3.5 px-4 font-semibold">Orang Tua / Wali</th>
                            <th class="py-3.5 px-4 font-semibold">Akun Portal</th>
                            <th class="py-3.5 px-6 font-semibold text-center">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-600 divide-y divide-slate-100">
                        <?php if (empty($santri_list)): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <i data-lucide="users" class="w-10 h-10 mb-2 opacity-40"></i>
                                        <p class="font-medium text-slate-600">Belum ada data santri</p>
                                        <p class="text-xs mt-1">Gunakan formulir di atas untuk menambahkan santri pertama Anda.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($santri_list as $i => $s): 
                                $is_putra = ($s['jenis_kelamin'] === 'L');
                                $avatar_bg = $is_putra ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700';
                                $jk_badge  = $is_putra ? 'bg-blue-50 text-blue-600 border-blue-200' : 'bg-pink-50 text-pink-600 border-pink-200';
                                
                                // Hitung usia jika tanggal lahir tersedia
                                $usia_teks = '-';
                                if (!empty($s['tanggal_lahir'])) {
                                    $tgl_lhr = new DateTime($s['tanggal_lahir']);
                                    $hari_ini = new DateTime();
                                    $usia = $hari_ini->diff($tgl_lhr)->y;
                                    $usia_teks = $usia . ' Thn';
                                }
                            ?>
                                <tr class="baris-santri hover:bg-slate-50/90 transition group" 
                                    data-jk="<?= htmlspecialchars($s['jenis_kelamin']) ?>"
                                    data-search="<?= strtolower(htmlspecialchars($s['nama'] . ' ' . $s['nis'] . ' ' . $s['nama_ortu'] . ' ' . ($s['username_ortu'] ?? ''))) ?>">
                                    
                                    <td class="py-4 px-6 text-center text-xs font-semibold text-slate-400 nomor-urut"><?= $i + 1 ?></td>
                                    
                                    <!-- Profil Santri -->
                                    <td class="py-4 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-xl <?= $avatar_bg ?> font-bold text-sm flex items-center justify-center shrink-0">
                                                <?= strtoupper(substr($s['nama'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-900 leading-tight"><?= htmlspecialchars($s['nama']) ?></p>
                                                <div class="flex items-center gap-2 mt-1">
                                                    <span class="text-[11px] font-mono bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded">NIS: <?= htmlspecialchars($s['nis'] ?: '-') ?></span>
                                                    <?php if (!empty($s['alamat'])): ?>
                                                        <span class="text-[11px] text-slate-400 truncate max-w-[160px]" title="<?= htmlspecialchars($s['alamat']) ?>">
                                                            • <?= htmlspecialchars($s['alamat']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Jenis Kelamin -->
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border <?= $jk_badge ?>">
                                            <?= $is_putra ? 'Putra (L)' : 'Putri (P)' ?>
                                        </span>
                                    </td>

                                    <!-- Usia & Tgl Lahir -->
                                    <td class="py-4 px-4">
                                        <p class="font-semibold text-slate-700 text-xs"><?= $usia_teks ?></p>
                                        <p class="text-[11px] text-slate-400"><?= !empty($s['tanggal_lahir']) ? date('d M Y', strtotime($s['tanggal_lahir'])) : '-' ?></p>
                                    </td>

                                    <!-- Orang Tua -->
                                    <td class="py-4 px-4">
                                        <p class="font-medium text-slate-800 text-xs"><?= htmlspecialchars($s['nama_ortu'] ?: '-') ?></p>
                                        <?php if (!empty($s['no_hp_ortu'])): ?>
                                            <a href="https://wa.me/<?= preg_replace('/^0/', '62', preg_replace('/[^0-9]/', '', $s['no_hp_ortu'])) ?>" target="_blank" 
                                               class="inline-flex items-center gap-1 text-[11px] text-emerald-600 hover:underline mt-0.5">
                                                <i data-lucide="message-circle" class="w-3 h-3"></i> <?= htmlspecialchars($s['no_hp_ortu']) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-[11px] text-slate-400">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Username Ortu -->
                                    <td class="py-4 px-4">
                                        <?php if (!empty($s['username_ortu'])): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-100 text-slate-700 rounded-lg font-mono text-xs border border-slate-200">
                                                <i data-lucide="key" class="w-3 h-3 text-slate-400"></i>
                                                <?= htmlspecialchars($s['username_ortu']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-xs text-slate-400 italic">Tidak ada akun</span>
                                        <?php endif; ?>
                                    </td>

                                    <!-- Aksi Cepat -->
                                    <td class="py-4 px-6 text-center">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <a href="progres_santri.php?id_santri=<?= $s['id'] ?>" title="Lihat Progres Hafalan"
                                               class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition">
                                                <i data-lucide="line-chart" class="w-4 h-4"></i>
                                            </a>
                                            <a href="rapor_santri.php?id_santri=<?= $s['id'] ?>" title="Lihat Rapor Tahfidz"
                                               class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition">
                                                <i data-lucide="file-text" class="w-4 h-4"></i>
                                            </a>
                                            <!-- Tombol Buka Modal Edit Santri -->
                                            <button type="button" 
                                                onclick='bukaModalEdit(<?= json_encode($s, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                title="Edit Data Santri"
                                                class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition">
                                                <i data-lucide="edit-3" class="w-4 h-4"></i>
                                            </button>
                                            <a href="proses_hapus_santri.php?id=<?= $s['id'] ?>" 
                                               onclick="return confirm('Apakah Anda yakin ingin menghapus data santri <?= htmlspecialchars(addslashes($s['nama'])) ?> beserta akun orang tuanya?')" 
                                               title="Hapus Santri"
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

    <!-- MODAL POP-UP EDIT SANTRI -->
    <div id="modalEditSantri" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden animate-fade-in">
            <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center">
                <h3 class="text-base font-bold flex items-center gap-2">
                    <i data-lucide="user-cog" class="w-5 h-5 text-amber-400"></i> Edit Data Santri & Akun Wali
                </h3>
                <button type="button" onclick="tutupModalEdit()" class="text-slate-400 hover:text-white transition">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="proses_edit_santri.php" method="POST" class="p-6 space-y-5" autocomplete="off">
                <input type="hidden" name="id_santri" id="edit_id_santri">
                <input type="hidden" name="id_user_ortu" id="edit_id_user_ortu">

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Lengkap Santri</label>
                        <input type="text" name="nama_santri" id="edit_nama" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">NIS</label>
                        <input type="text" name="nis" id="edit_nis" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Jenis Kelamin</label>
                        <select name="jenis_kelamin" id="edit_jk" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="L">👦 Laki-laki (Putra)</option>
                            <option value="P">👧 Perempuan (Putri)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" id="edit_tgl_lahir" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Alamat</label>
                    <textarea name="alamat" id="edit_alamat" rows="2" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none resize-none"></textarea>
                </div>

                <div class="border-t border-slate-200 pt-4">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Data Orang Tua & Reset Password</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Orang Tua</label>
                            <input type="text" name="nama_ortu" id="edit_nama_ortu" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">No. HP / WhatsApp</label>
                            <input type="text" name="no_hp_ortu" id="edit_no_hp" required class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 p-3.5 rounded-xl border border-slate-200">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Username Ortu</label>
                            <input type="text" name="username_ortu" id="edit_username_ortu" required class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Password Baru <span class="text-[10px] font-normal text-slate-400">(Kosongkan jika tetap)</span></label>
                            <input type="text" name="password_baru" placeholder="Ketik utk reset password..." class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="tutupModalEdit()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 hover:bg-slate-100 text-sm font-medium transition">Batal</button>
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 px-6 rounded-xl transition shadow-md flex items-center gap-2 text-sm">
                        <i data-lucide="check" class="w-4 h-4"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        // Buka / Tutup Form Tambah Santri
        function toggleFormSantri() {
            const form = document.getElementById('formTambahContainer');
            form.classList.toggle('hidden');
            if (!form.classList.contains('hidden')) {
                document.getElementById('inputNamaSantri').focus();
            }
        }

        // Otomatis sarankan username ortu dari kata pertama nama santri
        function generateUsernameOrtu(nama) {
            const inputUser = document.getElementById('inputUsernameOrtu');
            const bersih = nama.trim().toLowerCase().replace(/[^a-z0-9\s]/g, '');
            const kataPertama = bersih.split(/\s+/)[0] || '';
            inputUser.value = kataPertama ? 'ortu_' + kataPertama : '';
        }

        // Filter Live Search & Jenis Kelamin
        function filterTabelSantri() {
            const keyword = document.getElementById('cariSantri').value.toLowerCase();
            const jk = document.getElementById('filterJK').value;
            const baris = document.querySelectorAll('.baris-santri');
            let tampil = 0;

            baris.forEach((tr) => {
                const dataSearch = tr.getAttribute('data-search');
                const dataJk = tr.getAttribute('data-jk');
                
                const cocokKata = dataSearch.includes(keyword);
                const cocokJk = (jk === 'ALL' || dataJk === jk);

                if (cocokKata && cocokJk) {
                    tr.style.display = '';
                    tampil++;
                    tr.querySelector('.nomor-urut').innerText = tampil;
                } else {
                    tr.style.display = 'none';
                }
            });

            document.getElementById('jumlahTampil').innerText = tampil;
        }

        function bukaModalEdit(data) {
            document.getElementById('edit_id_santri').value = data.id;
            document.getElementById('edit_id_user_ortu').value = data.id_user_ortu || 0;
            document.getElementById('edit_nama').value = data.nama || '';
            document.getElementById('edit_nis').value = data.nis || '';
            document.getElementById('edit_jk').value = data.jenis_kelamin || 'L';
            document.getElementById('edit_tgl_lahir').value = data.tanggal_lahir || '';
            document.getElementById('edit_alamat').value = data.alamat || '';
            document.getElementById('edit_nama_ortu').value = data.nama_ortu || '';
            document.getElementById('edit_no_hp').value = data.no_hp_ortu || '';
            document.getElementById('edit_username_ortu').value = data.username_ortu || '';
            
            document.getElementById('modalEditSantri').classList.remove('hidden');
            lucide.createIcons();
        }

        function tutupModalEdit() {
            document.getElementById('modalEditSantri').classList.add('hidden');
        }
    </script>
</body>
</html>
