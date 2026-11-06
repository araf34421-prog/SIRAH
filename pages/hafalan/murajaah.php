<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/init_rbac.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

// Pastikan kolom juz & pojok ada di tabel hafalan
$cek_juz = mysqli_query($conn, "SHOW COLUMNS FROM hafalan LIKE 'juz'");
if ($cek_juz && mysqli_num_rows($cek_juz) === 0) {
    mysqli_query($conn, "ALTER TABLE hafalan ADD COLUMN `juz` INT(11) DEFAULT NULL AFTER `jenis`");
    mysqli_query($conn, "ALTER TABLE hafalan ADD COLUMN `pojok` VARCHAR(30) DEFAULT NULL AFTER `juz`");
}

// Hapus riwayat setoran murajaah jika diminta
$where_binaan = getFilterSantriBinaanSQL($conn, (int) $_SESSION['user_id'], $_SESSION['role'], 's');
$and_binaan = $where_binaan !== '' ? ' AND ' . preg_replace('/^\s*WHERE\s+/i', '', $where_binaan) : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id_hapus = (int) $_POST['hapus'];
    mysqli_query($conn, "DELETE h FROM hafalan h JOIN santri s ON s.id = h.id_santri WHERE h.id = $id_hapus AND h.jenis = 'murajaah' $and_binaan");
    $_SESSION['success'] = "Data setoran muraja'ah berhasil dihapus.";
    header("Location: input_murajaah.php");
    exit;
}

// Ambil daftar santri khusus lokal binaan Ustadz/Ustadzah yang login
$santri_query = "SELECT s.id, s.nama, s.nis, h.nama_halaqah 
                 FROM santri s 
                 LEFT JOIN halaqah h ON s.id_halaqah = h.id 
                 $where_binaan
                 ORDER BY s.nama ASC";
$santri_result = mysqli_query($conn, $santri_query);
$santri_list = mysqli_fetch_all($santri_result, MYSQLI_ASSOC);

// Ambil riwayat murajaah hari ini (khusus santri binaan)
$jenis_halaman = 'murajaah'; 
$and_binaan = !empty($where_binaan) ? str_replace('WHERE', 'AND', $where_binaan) : '';
$riwayat_query = "SELECT h.*, s.nama as nama_santri, s.nama_ortu, s.no_hp_ortu, hl.nama_halaqah 
                  FROM hafalan h 
                  JOIN santri s ON h.id_santri = s.id 
                  LEFT JOIN halaqah hl ON s.id_halaqah = hl.id
                  WHERE h.tanggal = CURDATE() AND h.jenis = '$jenis_halaman' $and_binaan
                  ORDER BY h.id DESC";
$riwayat_result = mysqli_query($conn, $riwayat_query);
$riwayat_list = mysqli_fetch_all($riwayat_result, MYSQLI_ASSOC);

// Hitung ringkasan hari ini
$total_setoran_hari_ini = count($riwayat_list);
$total_ayat_hari_ini = 0;
foreach ($riwayat_list as $r) {
    $total_ayat_hari_ini += (int)($r['jumlah_ayat'] ?? 0);
}

// Daftar 114 Surat untuk Autocomplete
$daftar_surat = ["Al-Fatihah","Al-Baqarah","Ali 'Imran","An-Nisa'","Al-Ma'idah","Al-An'am","Al-A'raf","Al-Anfal","At-Taubah","Yunus","Hud","Yusuf","Ar-Ra'd","Ibrahim","Al-Hijr","An-Nahl","Al-Isra'","Al-Kahf","Maryam","Taha","Al-Anbiya'","Al-Hajj","Al-Mu'minun","An-Nur","Al-Furqan","Asy-Syu'ara'","An-Naml","Al-Qasas","Al-'Ankabut","Ar-Rum","Luqman","As-Sajdah","Al-Ahzab","Saba'","Fatir","Yasin","As-Saffat","Sad","Az-Zumar","Gafir","Fussilat","Asy-Syura","Az-Zukhruf","Ad-Dukhan","Al-Jasiyah","Al-Ahqaf","Muhammad","Al-Fath","Al-Hujurat","Qaf","Az-Zariyat","At-Tur","An-Najm","Al-Qamar","Ar-Rahman","Al-Waqi'ah","Al-Hadid","Al-Mujadilah","Al-Hasyr","Al-Mumtahanah","As-Saff","Al-Jumu'ah","Al-Munafiqun","At-Tagabun","At-Talaq","At-Tahrim","Al-Mulk","Al-Qalam","Al-Haqqah","Al-Ma'arij","Nuh","Al-Jinn","Al-Muzzammil","Al-Muddassir","Al-Qiyamah","Al-Insan","Al-Mursalat","An-Naba'","An-Nazi'at","'Abasa","At-Takwir","Al-Infitar","Al-Mutaffifin","Al-Insyiqaq","Al-Buruj","At-Tariq","Al-A'la","Al-Gasyiyah","Al-Fajr","Al-Balad","Asy-Syams","Al-Lail","Ad-Duha","Asy-Syarh","At-Tin","Al-'Alaq","Al-Qadr","Al-Bayyinah","Az-Zalzalah","Al-'Adiyat","Al-Qari'ah","At-Takasur","Al-'Asr","Al-Humazah","Al-Fil","Quraisy","Al-Ma'un","Al-Kausar","Al-Kafirun","An-Nasr","Al-Lahab","Al-Ikhlas","Al-Falaq","An-Nas"];
?>
<?php
$page_title = "Input Muraja'ah - Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- HEADER HALAMAN -->
        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 bg-white border border-slate-200 rounded-xl shadow-sm flex items-center justify-center text-blue-600">
                    <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Input Setoran Muraja'ah</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Pencatatan pengulangan hafalan Al-Qur'an santri per Juz, Pojok, dan Surat.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <?= renderBadgeFilterBinaan($_SESSION['role']) ?>
                <!-- Tombol pemanggil Notif Panduan jika sudah disilang -->
                <button type="button" onclick="tampilkanNotifPanduan()" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 px-3.5 py-2 rounded-xl shadow-sm text-xs font-semibold flex items-center gap-1.5 transition">
                    <i data-lucide="help-circle" class="w-4 h-4 text-blue-600"></i>
                    <span>Panduan Nilai</span>
                </button>

                <div class="bg-white border border-slate-200 px-4 py-2 rounded-xl shadow-sm text-xs font-semibold text-slate-700 flex items-center gap-2">
                    <i data-lucide="calendar" class="w-4 h-4 text-blue-600"></i>
                    <span><?= date('d F Y') ?></span>
                </div>
            </div>
        </div>

        <!-- ALERT NOTIFIKASI SUKSES / ERROR -->
        <?php if (isset($_SESSION['success'])): ?>
            <div id="alertSuccess" class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-xl mb-6 text-xs font-medium flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-blue-600 shrink-0"></i>
                    <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
                <button type="button" onclick="document.getElementById('alertSuccess').remove()" class="text-blue-600 hover:text-blue-900">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div id="alertError" class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl mb-6 text-xs font-medium flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                    <span><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
                <button type="button" onclick="document.getElementById('alertError').remove()" class="text-rose-600 hover:text-rose-900">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if (empty($santri_list)): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-xl mb-6 text-xs font-medium flex items-center gap-2.5">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                <span>Belum ada data santri. Silakan tambahkan santri di menu <b>Kelola Santri</b> terlebih dahulu.</span>
            </div>
        <?php else: ?>

        <!-- FORMULIR SETORAN MURAJA'AH (CLEAN FULL-WIDTH CARD) -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    <h2 class="text-sm font-bold text-slate-800">Formulir Setoran Muraja'ah (Pengulangan Hafalan)</h2>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-500">
                    <span>Total Hari Ini: <strong class="text-slate-800"><?= $total_setoran_hari_ini ?> Sesi</strong></span>
                    <span>•</span>
                    <span>Total Diulang: <strong class="text-blue-600"><?= $total_ayat_hari_ini ?> Ayat</strong></span>
                </div>
            </div>

            <form action="proses_input_hafalan.php" method="POST" class="p-6" autocomplete="off"><?php echo csrfTokenField(); ?>
                <input type="hidden" name="jenis" value="murajaah">

                <!-- BARIS 1: PILIH SANTRI & TANGGAL -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 mb-5">
                    <div class="md:col-span-8">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Santri <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <select name="id_santri" required class="w-full pl-10 pr-4 py-2.5 bg-slate-50 hover:bg-white focus:bg-white border border-slate-300 focus:border-blue-600 rounded-xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/15 outline-none transition cursor-pointer">
                                <option value="" disabled selected>-- Pilih Nama Santri --</option>
                                <?php foreach ($santri_list as $s): ?>
                                    <option value="<?= $s['id'] ?>">
                                        <?= htmlspecialchars($s['nama']) ?> 
                                        <?= !empty($s['nis']) ? '(NIS: ' . htmlspecialchars($s['nis']) . ')' : '' ?>
                                        <?= !empty($s['nama_halaqah']) ? ' — [' . htmlspecialchars($s['nama_halaqah']) . ']' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Setoran</label>
                        <div class="relative">
                            <i data-lucide="calendar-days" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required
                                   class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-blue-600 rounded-xl text-sm font-medium text-slate-800 outline-none transition">
                        </div>
                    </div>
                </div>

                <!-- BARIS 2: JUZ, POJOK, SURAT MURAJA'AH, DAN RENTANG AYAT -->
                <div class="p-4 bg-slate-50/70 border border-slate-200/80 rounded-xl mb-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                            <i data-lucide="book-open" class="w-3.5 h-3.5 text-blue-600"></i> Detail Materi Muraja'ah (Juz, Pojok, Surat & Ayat)
                        </span>
                        <span id="badgeJumlahAyat" class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-blue-100 text-blue-800 hidden">
                            0 Ayat
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">
                        
                        <!-- Pilihan Juz (1 - 30) -->
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Juz <span class="text-rose-500">*</span></label>
                            <select name="juz" id="inputJuz" onchange="updateInfoHalamanMushaf()" required class="w-full px-3 py-2.5 bg-white border border-slate-300 focus:border-blue-600 rounded-xl text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-blue-500/15 outline-none cursor-pointer">
                                <option value="">-- Juz --</option>
                                <?php for ($j = 1; $j <= 30; $j++): ?>
                                    <option value="<?= $j ?>" <?= $j === 30 ? 'selected' : '' ?>>Juz <?= $j ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Pilihan Pojok (1 - 20) -->
                        <div class="lg:col-span-3">
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-semibold text-slate-600">Pojok / Halaman</label>
                                <span id="infoHalamanMushaf" class="text-[10px] font-medium text-slate-400"></span>
                            </div>
                            <input type="text" name="pojok" id="inputPojok" list="listPojok" oninput="updateInfoHalamanMushaf()" placeholder="Pilih / Ketik Pojok..."
                                   class="w-full px-3 py-2.5 bg-white border border-slate-300 focus:border-blue-600 rounded-xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/15 outline-none">
                            <datalist id="listPojok">
                                <?php for ($p = 1; $p <= 20; $p++): ?>
                                    <option value="Pojok <?= $p ?>">
                                <?php endfor; ?>
                            </datalist>
                        </div>

                        <!-- Nama Surat -->
                        <div class="lg:col-span-4">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Surat <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <i data-lucide="scroll" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                                <input type="text" name="surat" list="daftarSurat" required placeholder="Ketik / pilih nama surat..."
                                       class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 focus:border-blue-600 rounded-xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/15 outline-none">
                                <datalist id="daftarSurat">
                                    <?php foreach ($daftar_surat as $surah): ?>
                                        <option value="<?= $surah ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>

                        <!-- Rentang Ayat -->
                        <div class="lg:col-span-3">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Rentang Ayat <span class="text-rose-500">*</span></label>
                            <div class="flex items-center gap-1.5">
                                <input type="number" name="ayat_awal" id="ayatAwal" oninput="hitungRentangAyat()" min="1" required placeholder="Awal"
                                       class="w-full py-2.5 px-2 text-center bg-white border border-slate-300 focus:border-blue-600 rounded-xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-blue-500/15 outline-none">
                                <span class="text-xs font-bold text-slate-400">s/d</span>
                                <input type="number" name="ayat_akhir" id="ayatAkhir" oninput="hitungRentangAyat()" min="1" required placeholder="Akhir"
                                       class="w-full py-2.5 px-2 text-center bg-white border border-slate-300 focus:border-blue-600 rounded-xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-blue-500/15 outline-none">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- BARIS 3: KUALITAS KELANCARAN (PREDIKAT) & CATATAN PENGUJI -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                    
                    <!-- Pilihan Predikat -->
                    <div class="lg:col-span-7">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Predikat Kelancaran Muraja'ah <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="A" checked required>
                                <div class="border border-slate-200 rounded-xl p-3 bg-white hover:bg-slate-50 transition relative">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-sm font-extrabold">Predikat A</span>
                                        <i data-lucide="check-circle-2" class="check-badge w-4 h-4 opacity-0 scale-75 transition"></i>
                                    </div>
                                    <p class="text-[11px] text-slate-500">Mutqin & Sangat Lancar</p>
                                </div>
                            </label>

                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="B">
                                <div class="border border-slate-200 rounded-xl p-3 bg-white hover:bg-slate-50 transition relative">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-sm font-extrabold">Predikat B</span>
                                        <i data-lucide="check-circle-2" class="check-badge w-4 h-4 opacity-0 scale-75 transition"></i>
                                    </div>
                                    <p class="text-[11px] text-slate-500">Lancar, Sedikit Teguran</p>
                                </div>
                            </label>

                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="C">
                                <div class="border border-slate-200 rounded-xl p-3 bg-white hover:bg-slate-50 transition relative">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-sm font-extrabold">Predikat C</span>
                                        <i data-lucide="check-circle-2" class="check-badge w-4 h-4 opacity-0 scale-75 transition"></i>
                                    </div>
                                    <p class="text-[11px] text-slate-500">Terbata / Wajib Ulang</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Catatan Penguji -->
                    <div class="lg:col-span-5">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Catatan Evaluasi Ustadz/ah (Opsional)</label>
                        <input type="text" name="catatan" placeholder="Cth: Lancar, namun perkuat lagi di ayat-ayat akhir..."
                               class="w-full px-3.5 py-4 bg-slate-50 focus:bg-white border border-slate-300 focus:border-blue-600 rounded-xl text-xs font-medium text-slate-800 focus:ring-2 focus:ring-blue-500/15 outline-none transition">
                    </div>
                </div>

                <!-- TOMBOL SIMPAN -->
                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">Pastikan Juz, Pojok, Surat, dan Ayat telah sesuai sebelum menyimpan.</span>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 active:scale-[0.99] text-white font-semibold py-2.5 px-7 rounded-xl transition shadow-sm flex items-center gap-2 text-xs">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Simpan Setoran Muraja'ah</span>
                    </button>
                </div>

            </form>
        </div>
        <?php endif; ?>

        <!-- TABEL RIWAYAT MURAJA'AH HARI INI -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4 bg-slate-50/40">
                <div class="flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-slate-500"></i>
                    <h3 class="text-sm font-bold text-slate-800">Riwayat Setoran Muraja'ah Hari Ini</h3>
                </div>
                <div class="relative w-60">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5"></i>
                    <input type="text" id="cariRiwayat" oninput="filterRiwayatHariIni()" placeholder="Cari santri atau surat..." 
                           class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none focus:border-blue-600">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/70 text-slate-500 text-[11px] font-semibold uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-5 w-24">Waktu</th>
                            <th class="py-3 px-4">Nama Santri</th>
                            <th class="py-3 px-4">Juz & Pojok</th>
                            <th class="py-3 px-4">Surat & Ayat</th>
                            <th class="py-3 px-4 text-center">Jml Ayat</th>
                            <th class="py-3 px-4 text-center">Predikat</th>
                            <th class="py-3 px-4">Catatan</th>
                            <th class="py-3 px-5 text-right w-16">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs text-slate-600 divide-y divide-slate-100">
                        <?php if (empty($riwayat_list)): ?>
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                    <p class="font-medium text-slate-600">Belum ada setoran muraja'ah yang tercatat hari ini</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $r): 
                                if ($r['predikat'] === 'A') {
                                    $badge = 'bg-blue-50 text-blue-700 border-blue-200';
                                } elseif ($r['predikat'] === 'B') {
                                    $badge = 'bg-amber-50 text-amber-700 border-amber-200';
                                } else {
                                    $badge = 'bg-rose-50 text-rose-700 border-rose-200';
                                }
                            ?>
                            <tr class="baris-riwayat hover:bg-slate-50/80 transition"
                                data-search="<?= strtolower(htmlspecialchars($r['nama_santri'] . ' ' . $r['surat'] . ' ' . ($r['pojok'] ?? ''))) ?>">
                                <td class="py-3.5 px-5 font-mono text-slate-400">
                                    <?= date('H:i', strtotime($r['created_at'])) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($r['nama_santri']) ?></p>
                                    <?php if (!empty($r['nama_halaqah'])): ?>
                                        <span class="text-[10px] text-slate-400"><?= htmlspecialchars($r['nama_halaqah']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="inline-flex items-center gap-1.5">
                                        <?php if (!empty($r['juz'])): ?>
                                            <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold text-[11px]">Juz <?= (int)$r['juz'] ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($r['pojok'])): ?>
                                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-semibold text-[11px]"><?= htmlspecialchars($r['pojok']) ?></span>
                                        <?php endif; ?>
                                        <?php if (empty($r['juz']) && empty($r['pojok'])): ?>
                                            <span class="text-slate-400">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-slate-800">QS. <?= htmlspecialchars($r['surat']) ?></span>
                                    <span class="text-slate-500 ml-1">: <?= $r['ayat_awal'] ?>–<?= $r['ayat_akhir'] ?></span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded"><?= (int)$r['jumlah_ayat'] ?> Ayat</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg font-bold border <?= $badge ?>">
                                        <?= htmlspecialchars($r['predikat']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 italic max-w-xs truncate" title="<?= htmlspecialchars($r['catatan'] ?? '') ?>">
                                    <?= htmlspecialchars($r['catatan'] ?: '-') ?>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <?php 
                                        $link_wa = buatLinkWaHafalan(
                                            $r['no_hp_ortu'] ?? '',
                                            $r['nama_santri'],
                                            $r['nama_ortu'] ?? '',
                                            $r['jenis'],
                                            $r['tanggal'],
                                            $r['juz'] ?? '',
                                            $r['pojok'] ?? '',
                                            $r['surat'],
                                            $r['ayat_awal'],
                                            $r['ayat_akhir'],
                                            $r['predikat'],
                                            $r['catatan'] ?? '',
                                            $_SESSION['nama_lengkap'] ?? 'Ustadz/Ustadzah'
                                        );
                                        if (!empty($link_wa)): 
                                        ?>
                                            <a href="<?= $link_wa ?>" target="_blank" title="Kirim Laporan ke WhatsApp Ortu"
                                               class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200 rounded-lg inline-flex items-center gap-1 text-[11px] font-bold transition">
                                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i> WA Ortu
                                            </a>
                                        <?php endif; ?>

                                        <a href="?hapus=<?= $r['id'] ?>" 
                                           onclick="return confirm('Hapus catatan setoran ini?')"
                                           title="Hapus Setoran"
                                           class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg inline-flex transition">
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

    <!-- NOTIFIKASI PANDUAN MENGAMBANG (FLOATING DISMISSIBLE TOAST) -->
    <div id="floatingPanduan" class="fixed bottom-5 right-5 z-50 max-w-sm bg-slate-900 text-white rounded-2xl shadow-2xl border border-slate-700/80 p-4 transition-all duration-300 hidden">
        <div class="flex items-start justify-between gap-3 mb-2">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-blue-500/20 text-blue-400 flex items-center justify-center shrink-0">
                    <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                </div>
                <h4 class="text-xs font-bold tracking-wide text-white">Panduan Penilaian Muraja'ah</h4>
            </div>
            <button type="button" onclick="tutupNotifPanduan()" title="Tutup Panduan" class="text-slate-400 hover:text-white transition p-0.5">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <ul class="text-[11px] text-slate-300 space-y-1.5 pl-1">
            <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-blue-400"></span> <b>Predikat A:</b> Hafalan mutqin (lancar tanpa bantuan).</li>
            <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber-400"></span> <b>Predikat B:</b> Lancar, namun ada sedikit teguran/lupa.</li>
            <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-rose-400"></span> <b>Predikat C:</b> Terbata-bata, wajib diulang kembali.</li>
        </ul>
    </div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
