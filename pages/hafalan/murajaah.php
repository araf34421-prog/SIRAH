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
    mysqli_query($conn, "DELETE h FROM hafalan h JOIN santri s ON s.id = h.id_santri WHERE h.id = $id_hapus AND h.jenis = 'murajaah' $and_binaan");
    $_SESSION['success'] = "Data setoran muraja'ah berhasil dihapus.";
    header("Location: input_murajaah.php");
    exit;
}

$santri_query = "SELECT s.id, s.nama, s.nis, h.nama_halaqah 
                 FROM santri s 
                 LEFT JOIN halaqah h ON s.id_halaqah = h.id 
                 $where_binaan
                 ORDER BY s.nama ASC";
$santri_result = mysqli_query($conn, $santri_query);
$santri_list = mysqli_fetch_all($santri_result, MYSQLI_ASSOC);

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

$total_setoran_hari_ini = count($riwayat_list);
$total_ayat_hari_ini = 0;
foreach ($riwayat_list as $r) {
    $total_ayat_hari_ini += (int)($r['jumlah_ayat'] ?? 0);
}

$daftar_surat = ["Al-Fatihah","Al-Baqarah","Ali 'Imran","An-Nisa'","Al-Ma'idah","Al-An'am","Al-A'raf","Al-Anfal","At-Taubah","Yunus","Hud","Yusuf","Ar-Ra'd","Ibrahim","Al-Hijr","An-Nahl","Al-Isra'","Al-Kahf","Maryam","Taha","Al-Anbiya'","Al-Hajj","Al-Mu'minun","An-Nur","Al-Furqan","Asy-Syu'ara'","An-Naml","Al-Qasas","Al-'Ankabut","Ar-Rum","Luqman","As-Sajdah","Al-Ahzab","Saba'","Fatir","Yasin","As-Saffat","Sad","Az-Zumar","Gafir","Fussilat","Asy-Syura","Az-Zukhruf","Ad-Dukhan","Al-Jasiyah","Al-Ahqaf","Muhammad","Al-Fath","Al-Hujurat","Qaf","Az-Zariyat","At-Tur","An-Najm","Al-Qamar","Ar-Rahman","Al-Waqi'ah","Al-Hadid","Al-Mujadilah","Al-Hasyr","Al-Mumtahanah","As-Saff","Al-Jumu'ah","Al-Munafiqun","At-Tagabun","At-Talaq","At-Tahrim","Al-Mulk","Al-Qalam","Al-Haqqah","Al-Ma'arij","Nuh","Al-Jinn","Al-Muzzammil","Al-Muddassir","Al-Qiyamah","Al-Insan","Al-Mursalat","An-Naba'","An-Nazi'at","'Abasa","At-Takwir","Al-Infitar","Al-Mutaffifin","Al-Insyiqaq","Al-Buruj","At-Tariq","Al-A'la","Al-Gasyiyah","Al-Fajr","Al-Balad","Asy-Syams","Al-Lail","Ad-Duha","Asy-Syarh","At-Tin","Al-'Alaq","Al-Qadr","Al-Bayyinah","Az-Zalzalah","Al-'Adiyat","Al-Qari'ah","At-Takasur","Al-'Asr","Al-Humazah","Al-Fil","Quraisy","Al-Ma'un","Al-Kausar","Al-Kafirun","An-Nasr","Al-Lahab","Al-Ikhlas","Al-Falaq","An-Nas"];

$page_title = "Setoran Muraja'ah — SIRAH Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<body class="bg-slate-50 font-sans text-slate-900">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="ml-64 p-8 max-w-[1440px]">
        
        <!-- TOP NAVIGATION & QUICK SWITCHER -->
        <div class="flex flex-wrap items-center justify-between gap-4 pb-5 mb-6 border-b border-slate-200">
            <div>
                <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                    <span>Pencatatan Harian</span>
                    <span>/</span>
                    <span class="text-slate-900 font-medium">Muraja'ah</span>
                </div>
                <h1 class="text-xl font-semibold text-slate-950 tracking-tight">Input Setoran Muraja'ah</h1>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex items-center bg-slate-100 p-1 rounded-md border border-slate-200 text-xs">
                    <a href="input_ziyadah.php" class="px-3 py-1 rounded text-slate-600 hover:text-slate-950 font-medium transition">Ziyadah</a>
                    <a href="input_murajaah.php" class="px-3 py-1 rounded bg-white text-slate-950 font-semibold shadow-sm border border-slate-200/80">Muraja'ah</a>
                    <a href="input_tasmi.php" class="px-3 py-1 rounded text-slate-600 hover:text-slate-950 font-medium transition">Tasmi'</a>
                    <a href="input_kehadiran.php" class="px-3 py-1 rounded text-slate-600 hover:text-slate-950 font-medium transition">Presensi</a>
                </div>

                <?= renderBadgeFilterBinaan($_SESSION['role']) ?>

                <button type="button" onclick="tampilkanNotifPanduan()" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 px-3 py-1.5 rounded-md text-xs font-medium flex items-center gap-1.5 transition">
                    <i data-lucide="info" class="w-3.5 h-3.5 text-slate-400"></i>
                    <span>Kriteria</span>
                </button>
            </div>
        </div>

        <!-- NOTIFIKASI -->
        <?php if (isset($_SESSION['success'])): ?>
            <div id="alertSuccess" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2.5 rounded-md mb-6 text-xs font-medium flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i data-lucide="check" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                    <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
                <button type="button" onclick="document.getElementById('alertSuccess').remove()" class="text-emerald-600 hover:text-emerald-900"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
        <?php endif; ?>

        <?php if (empty($santri_list)): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-md mb-6 text-xs font-medium flex items-center gap-2.5">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                <span>Belum ada data santri pada filter ini.</span>
            </div>
        <?php else: ?>

        <!-- FORMULIR SETORAN MURAJA'AH -->
        <div class="bg-white border border-slate-200 rounded-lg mb-6 overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200 bg-slate-50/50 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-slate-700">Entri Pengulangan Hafalan (Muraja'ah)</h2>
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-500 font-mono">
                    <span>Sesi Hari Ini: <strong class="text-slate-900"><?= $total_setoran_hari_ini ?></strong></span>
                    <span class="text-slate-300">|</span>
                    <span>Total Diulang: <strong class="text-blue-600"><?= $total_ayat_hari_ini ?> ayat</strong></span>
                </div>
            </div>

            <form action="proses_input_hafalan.php" method="POST" class="p-5" autocomplete="off">
                <?php echo csrfTokenField(); ?>
                <input type="hidden" name="jenis" value="murajaah">

                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 mb-4">
                    <div class="md:col-span-8">
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Santri <span class="text-rose-500">*</span></label>
                        <select name="id_santri" required class="w-full px-3 py-2 text-xs text-slate-900 cursor-pointer">
                            <option value="" disabled selected>Pilih nama santri...</option>
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
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Tanggal Setoran</label>
                        <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 text-xs font-mono text-slate-900">
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border border-slate-200/80 rounded-md mb-4">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Cakupan Mushaf & Ayat</span>
                        <span id="badgeJumlahAyat" class="text-[11px] font-mono font-semibold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200 hidden">0 Ayat</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Juz <span class="text-rose-500">*</span></label>
                            <select name="juz" id="inputJuz" onchange="updateInfoHalamanMushaf()" required class="w-full px-3 py-2 text-xs font-medium text-slate-900 cursor-pointer">
                                <option value="">Pilih</option>
                                <?php for ($j = 1; $j <= 30; $j++): ?>
                                    <option value="<?= $j ?>" <?= $j === 30 ? 'selected' : '' ?>>Juz <?= $j ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="lg:col-span-3">
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-medium text-slate-600">Pojok / Hal</label>
                                <span id="infoHalamanMushaf" class="text-[10px] font-mono text-slate-400"></span>
                            </div>
                            <input type="text" name="pojok" id="inputPojok" list="listPojok" oninput="updateInfoHalamanMushaf()" placeholder="Cth: Pojok 1" class="w-full px-3 py-2 text-xs text-slate-900">
                            <datalist id="listPojok">
                                <?php for ($p = 1; $p <= 20; $p++): ?>
                                    <option value="Pojok <?= $p ?>">
                                <?php endfor; ?>
                            </datalist>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Surat <span class="text-rose-500">*</span></label>
                            <input type="text" name="surat" list="daftarSurat" required placeholder="Ketik nama surat..." class="w-full px-3 py-2 text-xs text-slate-900">
                            <datalist id="daftarSurat">
                                <?php foreach ($daftar_surat as $surah): ?>
                                    <option value="<?= $surah ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>

                        <div class="lg:col-span-3">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Rentang Ayat <span class="text-rose-500">*</span></label>
                            <div class="flex items-center gap-1.5">
                                <input type="number" name="ayat_awal" id="ayatAwal" oninput="hitungRentangAyat()" min="1" required placeholder="Awal" class="w-full py-2 px-2 text-center font-mono text-xs font-semibold text-slate-900">
                                <span class="text-xs text-slate-400">–</span>
                                <input type="number" name="ayat_akhir" id="ayatAkhir" oninput="hitungRentangAyat()" min="1" required placeholder="Akhir" class="w-full py-2 px-2 text-center font-mono text-xs font-semibold text-slate-900">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">
                    <div class="lg:col-span-6">
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Kelancaran Muraja'ah (Predikat) <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-3 gap-2.5">
                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="A" checked required>
                                <div class="border border-slate-200 rounded-md px-3 py-2 bg-white hover:bg-slate-50 transition flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-semibold block">Predikat A</span>
                                        <span class="text-[10px] text-slate-500">Mutqin & Lancar</span>
                                    </div>
                                    <i data-lucide="check" class="check-badge w-3.5 h-3.5 opacity-0 scale-75 transition"></i>
                                </div>
                            </label>

                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="B">
                                <div class="border border-slate-200 rounded-md px-3 py-2 bg-white hover:bg-slate-50 transition flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-semibold block">Predikat B</span>
                                        <span class="text-[10px] text-slate-500">Sedikit Teguran</span>
                                    </div>
                                    <i data-lucide="check" class="check-badge w-3.5 h-3.5 opacity-0 scale-75 transition"></i>
                                </div>
                            </label>

                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="C">
                                <div class="border border-slate-200 rounded-md px-3 py-2 bg-white hover:bg-slate-50 transition flex items-center justify-between">
                                    <div>
                                        <span class="text-xs font-semibold block">Predikat C</span>
                                        <span class="text-[10px] text-slate-500">Terbata / Ulang</span>
                                    </div>
                                    <i data-lucide="check" class="check-badge w-3.5 h-3.5 opacity-0 scale-75 transition"></i>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="lg:col-span-4">
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Catatan Evaluasi (Opsional)</label>
                        <input type="text" name="catatan" placeholder="Catatan penguatan ayat..." class="w-full px-3 py-2.5 text-xs text-slate-900">
                    </div>

                    <div class="lg:col-span-2">
                        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-medium py-2.5 px-4 rounded-md transition flex items-center justify-center gap-1.5 text-xs">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            <span>Simpan</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- TABEL RIWAYAT MURAJA'AH HARI INI -->
        <div class="bg-white border border-slate-200 rounded-lg overflow-hidden">
            <div class="px-5 py-3.5 border-b border-slate-200 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-700">Log Muraja'ah Hari Ini</h3>
                    <span class="text-[11px] font-mono px-1.5 py-0.5 rounded bg-slate-100 text-slate-600"><?= $total_setoran_hari_ini ?></span>
                </div>
                <div class="relative w-60">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5"></i>
                    <input type="text" id="cariRiwayat" oninput="filterRiwayatHariIni()" placeholder="Filter santri atau surat..." class="w-full pl-8 pr-3 py-1.5 text-xs">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr>
                            <th class="py-2.5 px-5 w-20">Jam</th>
                            <th class="py-2.5 px-4">Santri</th>
                            <th class="py-2.5 px-4">Mushaf</th>
                            <th class="py-2.5 px-4">Materi Surat</th>
                            <th class="py-2.5 px-4 text-center">Volume</th>
                            <th class="py-2.5 px-4 text-center">Nilai</th>
                            <th class="py-2.5 px-4">Catatan</th>
                            <th class="py-2.5 px-5 text-right w-28">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs text-slate-600 divide-y divide-slate-100">
                        <?php if (empty($riwayat_list)): ?>
                            <tr>
                                <td colspan="8" class="py-10 text-center text-slate-400 text-xs">
                                    Belum ada setoran muraja'ah yang dicatat hari ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $r): 
                                $badge = $r['predikat'] === 'A' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($r['predikat'] === 'B' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200');
                            ?>
                            <tr class="baris-riwayat" data-search="<?= strtolower(htmlspecialchars($r['nama_santri'] . ' ' . $r['surat'] . ' ' . ($r['pojok'] ?? ''))) ?>">
                                <td class="py-3 px-5 font-mono text-slate-400"><?= date('H:i', strtotime($r['created_at'])) ?></td>
                                <td class="py-3 px-4">
                                    <span class="font-medium text-slate-900 block"><?= htmlspecialchars($r['nama_santri']) ?></span>
                                    <?php if (!empty($r['nama_halaqah'])): ?>
                                        <span class="text-[11px] text-slate-400"><?= htmlspecialchars($r['nama_halaqah']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px]">
                                    <?= !empty($r['juz']) ? 'Juz ' . (int)$r['juz'] : '-' ?>
                                    <?= !empty($r['pojok']) ? ' • ' . htmlspecialchars($r['pojok']) : '' ?>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-medium text-slate-900">QS. <?= htmlspecialchars($r['surat']) ?></span>
                                    <span class="text-slate-400 font-mono ml-1">: <?= $r['ayat_awal'] ?>–<?= $r['ayat_akhir'] ?></span>
                                </td>
                                <td class="py-3 px-4 text-center font-mono text-slate-700"><?= (int)$r['jumlah_ayat'] ?> ayat</td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center justify-center px-2 py-0.5 rounded font-mono font-semibold border <?= $badge ?>">
                                        <?= htmlspecialchars($r['predikat']) ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-slate-500 max-w-xs truncate" title="<?= htmlspecialchars($r['catatan'] ?? '') ?>">
                                    <?= htmlspecialchars($r['catatan'] ?: '—') ?>
                                </td>
                                <td class="py-3 px-5 text-right">
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
                                               class="px-2 py-1 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded inline-flex items-center gap-1 text-[11px] font-medium transition">
                                                <i data-lucide="send" class="w-3 h-3 text-emerald-600"></i> WA
                                            </a>
                                        <?php endif; ?>

                                        <a href="?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus catatan setoran ini?')" title="Hapus"
                                           class="p-1 text-slate-400 hover:text-rose-600 rounded transition">
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

    <div id="floatingPanduan" class="fixed bottom-5 right-5 z-50 max-w-xs bg-slate-950 text-white rounded-lg border border-slate-800 p-4 hidden">
        <div class="flex items-start justify-between gap-3 mb-2">
            <h4 class="text-xs font-semibold text-white">Kriteria Predikat Muraja'ah</h4>
            <button type="button" onclick="tutupNotifPanduan()" class="text-slate-400 hover:text-white"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>
        </div>
        <ul class="text-[11px] text-slate-300 space-y-1">
            <li><strong class="text-white">A:</strong> Hafalan mutqin (lancar tanpa bantuan).</li>
            <li><strong class="text-white">B:</strong> Lancar, ada sedikit teguran/lupa.</li>
            <li><strong class="text-white">C:</strong> Terbata-bata, wajib diulang kembali.</li>
        </ul>
    </div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>