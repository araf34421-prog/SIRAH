<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/init_rbac.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

$cek_juz = mysqli_query($conn, "SHOW COLUMNS FROM hafalan LIKE 'juz'");
if ($cek_juz && mysqli_num_rows($cek_juz) === 0) {
    mysqli_query($conn, "ALTER TABLE hafalan ADD COLUMN `juz` INT(11) DEFAULT NULL AFTER `jenis`");
    mysqli_query($conn, "ALTER TABLE hafalan ADD COLUMN `pojok` VARCHAR(30) DEFAULT NULL AFTER `juz`");
}

$where_binaan = getFilterSantriBinaanSQL($conn, (int) $_SESSION['user_id'], $_SESSION['role'], 's');
$and_binaan = $where_binaan !== '' ? ' AND ' . preg_replace('/^\s*WHERE\s+/i', '', $where_binaan) : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id_hapus = (int) $_POST['hapus'];
    mysqli_query($conn, "DELETE h FROM hafalan h JOIN santri s ON s.id = h.id_santri WHERE h.id = $id_hapus AND h.jenis = 'ziyadah' $and_binaan");
    $_SESSION['success'] = "Data setoran ziyadah berhasil dihapus.";
    header("Location: input_ziyadah.php");
    exit;
}

$santri_query = "SELECT s.id, s.nama, s.nis, h.nama_halaqah 
                 FROM santri s 
                 LEFT JOIN halaqah h ON s.id_halaqah = h.id 
                 $where_binaan
                 ORDER BY s.nama ASC";
$santri_result = mysqli_query($conn, $santri_query);
$santri_list = mysqli_fetch_all($santri_result, MYSQLI_ASSOC);

$jenis_halaman = 'ziyadah'; 
$and_binaan = !empty($where_binaan) ? str_replace('WHERE', 'AND', $where_binaan) : '';
$riwayat_query = "SELECT h.*, s.nama as nama_santri, s.nama_ortu, s.no_hp_ortu, hl.nama_halaqah 
                  FROM hafalan h 
                  JOIN santri s ON h.id_santri = s.id 
                  LEFT JOIN halaqah hl ON s.id_halaqah = hl.id
                  WHERE h.tanggal = CURDATE() AND h.jenis = '$jenis_halaman' $and_binaan
                  ORDER BY h.id DESC";
$riwayat_result = mysqli_query($conn, $riwayat_query);
$riwayat_list = mysqli_fetch_all($riwayat_result, MYSQLI_ASSOC);

$total_setoran_hari_ini = count($riwayat_list);
$total_ayat_hari_ini = 0;
foreach ($riwayat_list as $r) {
    $total_ayat_hari_ini += (int)($r['jumlah_ayat'] ?? 0);
}

$daftar_surat = ["Al-Fatihah","Al-Baqarah","Ali 'Imran","An-Nisa'","Al-Ma'idah","Al-An'am","Al-A'raf","Al-Anfal","At-Taubah","Yunus","Hud","Yusuf","Ar-Ra'd","Ibrahim","Al-Hijr","An-Nahl","Al-Isra'","Al-Kahf","Maryam","Taha","Al-Anbiya'","Al-Hajj","Al-Mu'minun","An-Nur","Al-Furqan","Asy-Syu'ara'","An-Naml","Al-Qasas","Al-'Ankabut","Ar-Rum","Luqman","As-Sajdah","Al-Ahzab","Saba'","Fatir","Yasin","As-Saffat","Sad","Az-Zumar","Gafir","Fussilat","Asy-Syura","Az-Zukhruf","Ad-Dukhan","Al-Jasiyah","Al-Ahqaf","Muhammad","Al-Fath","Al-Hujurat","Qaf","Az-Zariyat","At-Tur","An-Najm","Al-Qamar","Ar-Rahman","Al-Waqi'ah","Al-Hadid","Al-Mujadilah","Al-Hasyr","Al-Mumtahanah","As-Saff","Al-Jumu'ah","Al-Munafiqun","At-Tagabun","At-Talaq","At-Tahrim","Al-Mulk","Al-Qalam","Al-Haqqah","Al-Ma'arij","Nuh","Al-Jinn","Al-Muzzammil","Al-Muddassir","Al-Qiyamah","Al-Insan","Al-Mursalat","An-Naba'","An-Nazi'at","'Abasa","At-Takwir","Al-Infitar","Al-Mutaffifin","Al-Insyiqaq","Al-Buruj","At-Tariq","Al-A'la","Al-Gasyiyah","Al-Fajr","Al-Balad","Asy-Syams","Al-Lail","Ad-Duha","Asy-Syarh","At-Tin","Al-'Alaq","Al-Qadr","Al-Bayyinah","Az-Zalzalah","Al-'Adiyat","Al-Qari'ah","At-Takasur","Al-'Asr","Al-Humazah","Al-Fil","Quraisy","Al-Ma'un","Al-Kausar","Al-Kafirun","An-Nasr","Al-Lahab","Al-Ikhlas","Al-Falaq","An-Nas"];

$page_title = "Setoran Ziyadah — SIRAH Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<body class="bg-slate-50 font-sans text-slate-900">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="ml-64 p-8 max-w-[1440px]">
        
        <!-- TOP NAVIGATION & MODULE SWITCHER -->
        <div class="flex flex-wrap items-center justify-between gap-4 pb-5 mb-6 border-b border-slate-200">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-lg shadow-emerald-500/25">
                    <i data-lucide="book-plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-950 tracking-tight">Input Setoran Ziyadah (Hafalan Baru)</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Pencatatan tambahan hafalan Al-Qur'an harian santri beserta laporan WhatsApp ke orang tua.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <div class="inline-flex items-center bg-slate-200/70 p-1 rounded-xl border border-slate-200 text-xs">
                    <a href="input_ziyadah.php" class="px-3.5 py-1.5 rounded-lg bg-white text-emerald-700 font-bold shadow-xs border border-emerald-200/80 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Ziyadah
                    </a>
                    <a href="input_murajaah.php" class="px-3.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-950 font-semibold transition">Muraja'ah</a>
                    <a href="input_tasmi.php" class="px-3.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-950 font-semibold transition">Tasmi'</a>
                    <a href="input_kehadiran.php" class="px-3.5 py-1.5 rounded-lg text-slate-600 hover:text-slate-950 font-semibold transition">Presensi</a>
                </div>

                <?= renderBadgeFilterBinaan($_SESSION['role']) ?>
            </div>
        </div>

        <!-- NOTIFIKASI -->
        <?php if (isset($_SESSION['success'])): ?>
            <div id="alertSuccess" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-6 text-xs font-semibold flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                    <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
                <button type="button" onclick="document.getElementById('alertSuccess').remove()" class="text-emerald-600 hover:text-emerald-900"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
        <?php endif; ?>

        <?php if (empty($santri_list)): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-xl mb-6 text-xs font-medium flex items-center gap-2.5">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                <span>Belum ada data santri pada filter ini. Silakan tambahkan santri di menu <b>Kelola Santri</b> atau aktifkan <b>Mode Badal</b>.</span>
            </div>
        <?php else: ?>

        <!-- FORMULIR SETORAN ZIYADAH MODERN -->
        <div class="bg-white border border-slate-200 rounded-2xl mb-7 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200/80 bg-gradient-to-r from-emerald-50/70 via-white to-slate-50 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_#10b981]"></span>
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-800">Formulir Setoran Hafalan Baru</h2>
                </div>
                <div class="flex items-center gap-3 text-xs font-mono">
                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold">Hari Ini: <strong><?= $total_setoran_hari_ini ?> Sesi</strong></span>
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold">+<?= $total_ayat_hari_ini ?> Ayat Baru</span>
                </div>
            </div>

            <form action="proses_input_hafalan.php" method="POST" class="p-6" autocomplete="off">
                <?php echo csrfTokenField(); ?>
                <input type="hidden" name="jenis" value="ziyadah">

                <!-- BARIS 1: SANTRI & TANGGAL -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 mb-5">
                    <div class="md:col-span-8">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Pilih Santri <span class="text-rose-500">*</span></label>
                        <select name="id_santri" required class="w-full px-3.5 py-2.5 text-xs font-semibold text-slate-900 cursor-pointer">
                            <option value="" disabled selected>-- Ketik atau pilih nama santri --</option>
                            <?php foreach ($santri_list as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['nama']) ?> 
                                    <?= !empty($s['nis']) ? '(' . htmlspecialchars($s['nis']) . ')' : '' ?>
                                    <?= !empty($s['nama_halaqah']) ? ' — ' . htmlspecialchars($s['nama_halaqah']) : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Tanggal Setoran</label>
                        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3.5 py-2.5 text-xs font-mono font-semibold text-slate-900">
                    </div>
                </div>

                <!-- BARIS 2: INTERACTIVE MUSHAF BOX -->
                <div class="p-5 bg-slate-50/90 border border-slate-200 rounded-xl mb-5">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <div class="flex items-center gap-2">
                            <i data-lucide="book-open" class="w-4 h-4 text-emerald-600"></i>
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Cakupan Mushaf & Rentang Ayat</span>
                        </div>
                        
                        <!-- Quick Juz Pills -->
                        <div class="flex items-center gap-1.5">
                            <span class="text-[11px] text-slate-400 mr-1">Pintasan:</span>
                            <button type="button" onclick="pilihJuzCepat(30)" class="px-2 py-0.5 rounded-md bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 border border-slate-200 text-[11px] font-semibold transition cursor-pointer">Juz 30</button>
                            <button type="button" onclick="pilihJuzCepat(29)" class="px-2 py-0.5 rounded-md bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 border border-slate-200 text-[11px] font-semibold transition cursor-pointer">Juz 29</button>
                            <button type="button" onclick="pilihJuzCepat(1)" class="px-2 py-0.5 rounded-md bg-white hover:bg-indigo-50 text-slate-600 hover:text-indigo-600 border border-slate-200 text-[11px] font-semibold transition cursor-pointer">Juz 1</button>
                            <span id="badgeJumlahAyat" class="ml-2 text-xs font-mono font-bold px-2.5 py-0.5 rounded-lg bg-emerald-600 text-white shadow-xs hidden">0 Ayat</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Juz <span class="text-rose-500">*</span></label>
                            <select name="juz" id="inputJuz" onchange="updateInfoHalamanMushaf()" required class="w-full px-3 py-2.5 text-xs font-bold text-slate-900 cursor-pointer">
                                <option value="">Pilih</option>
                                <?php for ($j = 1; $j <= 30; $j++): ?>
                                    <option value="<?= $j ?>" <?= $j === 30 ? 'selected' : '' ?>>Juz <?= $j ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="lg:col-span-3">
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-semibold text-slate-600">Pojok / Hal</label>
                                <span id="infoHalamanMushaf" class="text-[10px] font-mono font-semibold text-indigo-600"></span>
                            </div>
                            <input type="text" name="pojok" id="inputPojok" list="listPojok" oninput="updateInfoHalamanMushaf()" placeholder="Cth: Pojok 1" class="w-full px-3 py-2.5 text-xs font-medium text-slate-900">
                            <datalist id="listPojok">
                                <?php for ($p = 1; $p <= 20; $p++): ?>
                                    <option value="Pojok <?= $p ?>">
                                <?php endfor; ?>
                            </datalist>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Surat <span class="text-rose-500">*</span></label>
                            <input type="text" name="surat" list="daftarSurat" required placeholder="Ketik atau pilih surat..." class="w-full px-3 py-2.5 text-xs font-semibold text-slate-900">
                            <datalist id="daftarSurat">
                                <?php foreach ($daftar_surat as $surah): ?>
                                    <option value="<?= $surah ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="lg:col-span-3">
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-semibold text-slate-600">Rentang Ayat <span class="text-rose-500">*</span></label>
                                <div class="flex gap-1">
                                    <button type="button" onclick="tambahRentangCepat(5)" title="Tambah 5 Ayat" class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 hover:bg-emerald-200 cursor-pointer">+5</button>
                                    <button type="button" onclick="tambahRentangCepat(10)" title="Tambah 10 Ayat" class="text-[10px] font-mono font-bold px-1.5 py-0.2 rounded bg-emerald-100 text-emerald-800 hover:bg-emerald-200 cursor-pointer">+10</button>
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <input type="number" name="ayat_awal" id="ayatAwal" oninput="hitungRentangAyat()" min="1" required placeholder="Awal" class="w-full py-2.5 px-2 text-center font-mono text-xs font-bold text-slate-900">
                                <span class="text-xs font-bold text-slate-400">s/d</span>
                                <input type="number" name="ayat_akhir" id="ayatAkhir" oninput="hitungRentangAyat()" min="1" required placeholder="Akhir" class="w-full py-2.5 px-2 text-center font-mono text-xs font-bold text-slate-900">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BARIS 3: PREDIKAT & CATATAN -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-end">
                    <div class="lg:col-span-6">
                        <label class="block text-xs font-bold text-slate-700 mb-2">Kualitas Bacaan (Predikat) <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="A" checked required>
                                <div class="border border-slate-200 rounded-xl p-3 bg-white hover:bg-slate-50 transition flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-extrabold block">Predikat A</span>
                                        <span class="text-[10px] opacity-80">Mumtaz / Lancar</span>
                                    </div>
                                    <i data-lucide="check-circle-2" class="check-badge w-4 h-4 opacity-0 scale-75 transition"></i>
                                </div>
                            </label>

                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="B">
                                <div class="border border-slate-200 rounded-xl p-3 bg-white hover:bg-slate-50 transition flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-extrabold block">Predikat B</span>
                                        <span class="text-[10px] opacity-80">Jayyid / Catatan</span>
                                    </div>
                                    <i data-lucide="check-circle-2" class="check-badge w-4 h-4 opacity-0 scale-75 transition"></i>
                                </div>
                            </label>

                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="C">
                                <div class="border border-slate-200 rounded-xl p-3 bg-white hover:bg-slate-50 transition flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-extrabold block">Predikat C</span>
                                        <span class="text-[10px] opacity-80">Perlu Mengulang</span>
                                    </div>
                                    <i data-lucide="check-circle-2" class="check-badge w-4 h-4 opacity-0 scale-75 transition"></i>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="lg:col-span-4">
                        <label class="block text-xs font-bold text-slate-700 mb-2">Catatan Tajwid / Makhraj (Opsional)</label>
                        <input type="text" name="catatan" placeholder="Cth: Perhatikan dengung ikhfa & mad thabi'i..." class="w-full px-3.5 py-3 text-xs text-slate-900">
                    </div>

                    <div class="lg:col-span-2">
                        <button type="submit" class="w-full bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold py-3 px-4 rounded-xl transition flex items-center justify-center gap-2 text-xs shadow-lg shadow-emerald-600/25 cursor-pointer">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Simpan Setoran</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- TABEL RIWAYAT SETORAN HARI INI -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="history" class="w-4 h-4 text-emerald-600"></i>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Log Setoran Ziyadah Hari Ini</h3>
                    <span class="text-xs font-mono font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200"><?= $total_setoran_hari_ini ?></span>
                </div>
                <div class="relative w-64">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5"></i>
                    <input type="text" id="cariRiwayat" oninput="filterRiwayatHariIni()" placeholder="Cari nama santri atau surat..." class="w-full pl-8 pr-3 py-1.5 text-xs">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr>
                            <th class="py-3 px-5 w-20">Jam</th>
                            <th class="py-3 px-4">Santri</th>
                            <th class="py-3 px-4">Mushaf</th>
                            <th class="py-3 px-4">Materi Surat</th>
                            <th class="py-3 px-4 text-center">Volume</th>
                            <th class="py-3 px-4 text-center">Predikat</th>
                            <th class="py-3 px-4">Catatan</th>
                            <th class="py-3 px-5 text-right w-32">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs text-slate-600 divide-y divide-slate-100">
                        <?php if (empty($riwayat_list)): ?>
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400 text-xs">
                                    Belum ada setoran ziyadah yang dicatat hari ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $r): 
                                $badge = $r['predikat'] === 'A' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($r['predikat'] === 'B' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200');
                            ?>
                            <tr class="baris-riwayat" data-search="<?= strtolower(htmlspecialchars($r['nama_santri'] . ' ' . $r['surat'] . ' ' . ($r['pojok'] ?? ''))) ?>">
                                <td class="py-3.5 px-5 font-mono text-slate-400"><?= date('H:i', strtotime($r['created_at'])) ?></td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-slate-900 block"><?= htmlspecialchars($r['nama_santri']) ?></span>
                                    <?php if (!empty($r['nama_halaqah'])): ?>
                                        <span class="text-[11px] text-slate-400"><?= htmlspecialchars($r['nama_halaqah']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px]">
                                    <?= !empty($r['juz']) ? 'Juz ' . (int)$r['juz'] : '-' ?>
                                    <?= !empty($r['pojok']) ? ' • ' . htmlspecialchars($r['pojok']) : '' ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-slate-900">QS. <?= htmlspecialchars($r['surat']) ?></span>
                                    <span class="text-slate-500 font-mono ml-1">: <?= $r['ayat_awal'] ?>–<?= $r['ayat_akhir'] ?></span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 font-mono font-bold text-slate-700"><?= (int)$r['jumlah_ayat'] ?> ayat</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-lg font-mono font-bold border <?= $badge ?>">
                                        <?= htmlspecialchars($r['predikat']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 max-w-xs truncate" title="<?= htmlspecialchars($r['catatan'] ?? '') ?>">
                                    <?= htmlspecialchars($r['catatan'] ?: '—') ?>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <div class="inline-flex items-center justify-end gap-1.5">
                                        <?php 
                                        $link_wa = buatLinkWaHafalan(
                                            $r['no_hp_ortu'] ?? '', $r['nama_santri'], $r['nama_ortu'] ?? '',
                                            $r['jenis'], $r['tanggal'], $r['juz'] ?? '', $r['pojok'] ?? '',
                                            $r['surat'], $r['ayat_awal'], $r['ayat_akhir'], $r['predikat'],
                                            $r['catatan'] ?? '', $_SESSION['nama_lengkap'] ?? 'Ustadz/Ustadzah'
                                        );
                                        if (!empty($link_wa)): 
                                        ?>
                                            <a href="<?= $link_wa ?>" target="_blank" title="Kirim Laporan ke WhatsApp Wali"
                                               class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200 rounded-lg inline-flex items-center gap-1 text-[11px] font-bold transition">
                                                <i data-lucide="send" class="w-3 h-3"></i> WA
                                            </a>
                                        <?php endif; ?>

                                        <a href="?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus catatan setoran ini?')" title="Hapus"
                                           class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
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

    <script>
    function pilihJuzCepat(juz) {
        const sel = document.getElementById('inputJuz');
        if (sel) {
            sel.value = String(juz);
            if (window.updateInfoHalamanMushaf) window.updateInfoHalamanMushaf();
        }
    }
    function tambahRentangCepat(jml) {
        const awalEl = document.getElementById('ayatAwal');
        const akhirEl = document.getElementById('ayatAkhir');
        if (!awalEl || !akhirEl) return;
        const awal = parseInt(awalEl.value) || 1;
        awalEl.value = awal;
        akhirEl.value = awal + jml - 1;
        if (window.hitungRentangAyat) window.hitungRentangAyat();
    }
    </script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>