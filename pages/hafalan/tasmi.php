<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/init_rbac.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

$id_ustadz = (int) $_SESSION['user_id'];
$role      = $_SESSION['role'];

// Pastikan kolom juz & pojok ada di tabel hafalan
$cek_juz = mysqli_query($conn, "SHOW COLUMNS FROM hafalan LIKE 'juz'");
if ($cek_juz && mysqli_num_rows($cek_juz) === 0) {
    mysqli_query($conn, "ALTER TABLE hafalan ADD COLUMN `juz` INT(11) DEFAULT NULL AFTER `jenis`");
    mysqli_query($conn, "ALTER TABLE hafalan ADD COLUMN `pojok` VARCHAR(30) DEFAULT NULL AFTER `juz`");
}

// Hapus riwayat tasmi jika diminta
if (isset($_GET['hapus'])) {
    $id_hapus = (int) $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM hafalan WHERE id = $id_hapus AND jenis = 'tasmi'");
    $_SESSION['success'] = "Data ujian tasmi' berhasil dihapus.";
    header("Location: input_tasmi.php");
    exit;
}

// Filter santri khusus lokal binaan Ustadz/Ustadzah
$where_binaan = getFilterSantriBinaanSQL($conn, $id_ustadz, $role, 's');
$santri_query = "SELECT s.id, s.nama, s.nis, h.nama_halaqah 
                 FROM santri s 
                 LEFT JOIN halaqah h ON s.id_halaqah = h.id 
                 $where_binaan 
                 ORDER BY s.nama ASC";
$santri_list = mysqli_fetch_all(mysqli_query($conn, $santri_query), MYSQLI_ASSOC);

// Riwayat ujian tasmi (menampilkan 25 ujian terakhir dari santri binaan)
$and_binaan = !empty($where_binaan) ? str_replace('WHERE', 'AND', $where_binaan) : '';
$riwayat_query = "SELECT h.*, s.nama as nama_santri, s.nama_ortu, s.no_hp_ortu, hl.nama_halaqah 
                  FROM hafalan h 
                  JOIN santri s ON h.id_santri = s.id 
                  LEFT JOIN halaqah hl ON s.id_halaqah = hl.id
                  WHERE h.jenis = 'tasmi' $and_binaan 
                  ORDER BY h.tanggal DESC, h.id DESC LIMIT 25";
$riwayat_list = mysqli_fetch_all(mysqli_query($conn, $riwayat_query), MYSQLI_ASSOC);
?>
<?php
$page_title = "Input Tasmi' - Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- HEADER HALAMAN -->
        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 bg-white border border-slate-200 rounded-xl shadow-sm flex items-center justify-center text-purple-600">
                    <i data-lucide="award" class="w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Input Ujian Tasmi'</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Pencatatan hasil ujian hafalan sekali duduk (tasmi') santri per Juz dan Pojok.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <?= renderBadgeFilterBinaan($_SESSION['role']) ?>
                <button type="button" onclick="tampilkanNotifPanduan()" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 px-3.5 py-2 rounded-xl shadow-sm text-xs font-semibold flex items-center gap-1.5 transition">
                    <i data-lucide="help-circle" class="w-4 h-4 text-purple-600"></i>
                    <span>Panduan Nilai</span>
                </button>
                <div class="bg-white border border-slate-200 px-4 py-2 rounded-xl shadow-sm text-xs font-semibold text-slate-700 flex items-center gap-2">
                    <i data-lucide="calendar" class="w-4 h-4 text-purple-600"></i>
                    <span><?= date('d F Y') ?></span>
                </div>
            </div>
        </div>

        <!-- ALERT NOTIFIKASI -->
        <?php if (isset($_SESSION['success'])): ?>
            <div id="alertSuccess" class="bg-purple-50 border border-purple-200 text-purple-800 px-4 py-3 rounded-xl mb-6 text-xs font-medium flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-purple-600 shrink-0"></i>
                    <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
                <button type="button" onclick="document.getElementById('alertSuccess').remove()" class="text-purple-600 hover:text-purple-900"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
        <?php endif; ?>

        <?php if (empty($santri_list)): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-xl mb-6 text-xs font-medium flex items-center gap-2.5">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                <span>Belum ada santri di lokal binaan Anda. Silakan cek penempatan kelas di menu <b>Kelas / Lokal Halaqah</b>.</span>
            </div>
        <?php else: ?>

        <!-- FORMULIR TASMI' (CLEAN FULL-WIDTH CARD) -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                    <h2 class="text-sm font-bold text-slate-800">Formulir Ujian Tasmi' Sekali Duduk</h2>
                </div>
                <span class="text-xs text-slate-500">Santri yang lulus berhak mendapatkan <b>Syahadah Tasmi'</b></span>
            </div>

            <form action="proses_input_hafalan.php" method="POST" class="p-6" autocomplete="off">
                <input type="hidden" name="jenis" value="tasmi">
                <input type="hidden" name="ayat_awal" value="1">
                <input type="hidden" name="ayat_akhir" value="1">

                <!-- BARIS 1: PILIH SANTRI & TANGGAL -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 mb-5">
                    <div class="md:col-span-8">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Santri <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <select name="id_santri" required class="w-full pl-10 pr-4 py-2.5 bg-slate-50 hover:bg-white focus:bg-white border border-slate-300 focus:border-purple-600 rounded-xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-purple-500/15 outline-none transition cursor-pointer">
                                <option value="" disabled selected>-- Pilih Nama Santri Binaan --</option>
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
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Ujian</label>
                        <div class="relative">
                            <i data-lucide="calendar-days" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required
                                   class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-purple-600 rounded-xl text-sm font-medium text-slate-800 outline-none transition">
                        </div>
                    </div>
                </div>

                <!-- BARIS 2: JUZ, POJOK, DAN MATERI SURAT / JUZ -->
                <div class="p-4 bg-slate-50/70 border border-slate-200/80 rounded-xl mb-5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 block mb-3">
                        <i data-lucide="book-open" class="w-3.5 h-3.5 text-purple-600 inline mr-1"></i> Detail Materi Ujian Tasmi' (Juz, Pojok & cakupan materi)
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-3">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Juz yang Diujikan <span class="text-rose-500">*</span></label>
                            <select name="juz" id="inputJuz" onchange="isiOtomatisMateriTasmi(this.value)" required class="w-full px-3 py-2.5 bg-white border border-slate-300 focus:border-purple-600 rounded-xl text-sm font-semibold text-slate-800 outline-none cursor-pointer">
                                <option value="">-- Pilih Juz --</option>
                                <?php for ($j = 1; $j <= 30; $j++): ?>
                                    <option value="<?= $j ?>" <?= $j === 30 ? 'selected' : '' ?>>Juz <?= $j ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Cakupan Pojok / Halaman</label>
                            <input type="text" name="pojok" list="presetPojokTasmi" value="Pojok 1 s/d 20 (1 Juz Penuh)" placeholder="Cth: Pojok 1 s/d 20" class="w-full px-3 py-2.5 bg-white border border-slate-300 focus:border-purple-600 rounded-xl text-sm font-medium text-slate-800 outline-none">
                            <datalist id="presetPojokTasmi">
                                <option value="Pojok 1 s/d 20 (1 Juz Penuh)">
                                <option value="Pojok 1 s/d 10 (Setengah Juz Awal)">
                                <option value="Pojok 11 s/d 20 (Setengah Juz Akhir)">
                                <option value="37 Surat Juz Amma">
                            </datalist>
                        </div>

                        <div class="lg:col-span-5">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Materi / Surat Tasmi' <span class="text-rose-500">*</span></label>
                            <input type="text" name="surat" id="inputMateriTasmi" value="Juz 30 (An-Naba' s/d An-Nas)" required placeholder="Cth: Juz 30 Full / QS. Al-Baqarah" class="w-full px-3 py-2.5 bg-white border border-slate-300 focus:border-purple-600 rounded-xl text-sm font-medium text-slate-800 outline-none">
                        </div>
                    </div>
                </div>

                <!-- BARIS 3: PREDIKAT & CATATAN -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                    <div class="lg:col-span-7">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Hasil Kelulusan Ujian <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="A" checked required>
                                <div class="border border-slate-200 rounded-xl p-3 bg-white hover:bg-slate-50 transition relative">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-sm font-extrabold">Predikat A</span>
                                        <i data-lucide="check-circle-2" class="check-badge w-4 h-4 opacity-0 scale-75 transition"></i>
                                    </div>
                                    <p class="text-[11px] text-slate-500">Mumtaz (Sempurna)</p>
                                </div>
                            </label>

                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="B">
                                <div class="border border-slate-200 rounded-xl p-3 bg-white hover:bg-slate-50 transition relative">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-sm font-extrabold">Predikat B</span>
                                        <i data-lucide="check-circle-2" class="check-badge w-4 h-4 opacity-0 scale-75 transition"></i>
                                    </div>
                                    <p class="text-[11px] text-slate-500">Jayyid (Lulus Catatan)</p>
                                </div>
                            </label>

                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="C">
                                <div class="border border-slate-200 rounded-xl p-3 bg-white hover:bg-slate-50 transition relative">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-sm font-extrabold">Predikat C</span>
                                        <i data-lucide="check-circle-2" class="check-badge w-4 h-4 opacity-0 scale-75 transition"></i>
                                    </div>
                                    <p class="text-[11px] text-slate-500">Belum Lulus / Ulang</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="lg:col-span-5">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Catatan Penguji (Opsional)</label>
                        <input type="text" name="catatan" placeholder="Cth: Alhamdulillah lulus Mumtaz, siap lanjut ke Juz berikutnya..." class="w-full px-3.5 py-4 bg-slate-50 focus:bg-white border border-slate-300 focus:border-purple-600 rounded-xl text-xs font-medium text-slate-800 outline-none transition">
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">Pastikan seluruh ketentuan ujian tasmi' telah terpenuhi.</span>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 active:scale-[0.99] text-white font-semibold py-2.5 px-7 rounded-xl transition shadow-sm flex items-center gap-2 text-xs">
                        <i data-lucide="save" class="w-4 h-4"></i> Simpan Hasil Tasmi'
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- TABEL RIWAYAT TASMI' -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/40 flex flex-wrap justify-between items-center gap-4">
                <div class="flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-slate-500"></i>
                    <h3 class="text-sm font-bold text-slate-800">Daftar Riwayat Ujian Tasmi' Santri</h3>
                </div>
                <div class="relative w-60">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5"></i>
                    <input type="text" id="cariTasmi" oninput="filterRiwayatTasmi()" placeholder="Cari santri atau juz..." 
                           class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none focus:border-purple-600">
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/70 text-slate-500 text-[11px] font-semibold uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-5 w-28">Tanggal</th>
                            <th class="py-3.5 px-4">Nama Santri</th>
                            <th class="py-3.5 px-4">Juz & Pojok</th>
                            <th class="py-3.5 px-4">Materi Ujian</th>
                            <th class="py-3.5 px-4 text-center">Predikat</th>
                            <th class="py-3.5 px-4">Catatan Penguji</th>
                            <th class="py-3.5 px-5 text-right w-44">Aksi & Syahadah</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs text-slate-600 divide-y divide-slate-100">
                        <?php if (empty($riwayat_list)): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                    <p class="font-medium text-slate-600">Belum ada riwayat ujian tasmi' tercatat</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $r): 
                                $badge = $r['predikat'] === 'A' ? 'bg-purple-50 text-purple-700 border-purple-200' : ($r['predikat'] === 'B' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200');
                            ?>
                            <tr class="baris-tasmi hover:bg-slate-50/80 transition"
                                data-search="<?= strtolower(htmlspecialchars($r['nama_santri'] . ' ' . $r['surat'] . ' juz ' . ($r['juz'] ?? ''))) ?>">
                                <td class="py-3.5 px-5 font-medium text-slate-500 whitespace-nowrap">
                                    <?= date('d M Y', strtotime($r['tanggal'])) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($r['nama_santri']) ?></p>
                                    <?php if (!empty($r['nama_halaqah'])): ?>
                                        <span class="text-[10px] text-slate-400"><?= htmlspecialchars($r['nama_halaqah']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if (!empty($r['juz'])): ?>
                                        <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold text-[11px]">Juz <?= (int)$r['juz'] ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($r['pojok'])): ?>
                                        <span class="text-slate-600 ml-1 text-[11px]"><?= htmlspecialchars($r['pojok']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-800"><?= htmlspecialchars($r['surat']) ?></td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg font-bold border <?= $badge ?>">
                                        <?= htmlspecialchars($r['predikat']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 italic max-w-xs truncate"><?= htmlspecialchars($r['catatan'] ?: '-') ?></td>
                                <td class="py-3.5 px-5 text-right">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <?php if (in_array($r['predikat'], ['A', 'B'])): ?>
                                            <a href="cetak_syahadah.php?id_santri=<?= $r['id_santri'] ?>&id_tasmi=<?= $r['id'] ?>" 
                                               title="Cetak Piagam Syahadah Tasmi'"
                                               class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-500 text-amber-700 hover:text-white border border-amber-200 rounded-lg inline-flex items-center gap-1 text-[11px] font-bold transition">
                                                <i data-lucide="award" class="w-3.5 h-3.5"></i> Syahadah
                                            </a>
                                        <?php endif; ?>

                                        <?php 
                                        if (function_exists('buatLinkWaHafalan')) {
                                            $wa_link = buatLinkWaHafalan($r['no_hp_ortu'] ?? '', $r['nama_santri'], $r['nama_ortu'] ?? '', 'tasmi', $r['tanggal'], $r['juz'] ?? '', $r['pojok'] ?? '', $r['surat'], 1, 1, $r['predikat'], $r['catatan'] ?? '', $_SESSION['nama_lengkap'] ?? 'Ustadz/ah');
                                            if (!empty($wa_link)):
                                        ?>
                                            <a href="<?= $wa_link ?>" target="_blank" title="Kirim Hasil Tasmi' ke WhatsApp Ortu"
                                               class="px-2 py-1.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200 rounded-lg inline-flex items-center gap-1 text-[11px] font-bold transition">
                                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i>
                                            </a>
                                        <?php 
                                            endif;
                                        }
                                        ?>

                                        <a href="input_tasmi.php?hapus=<?= $r['id'] ?>" onclick="return confirm('Hapus data ujian tasmi ini?')" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg inline-flex transition">
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

    <!-- TOAST PANDUAN MENGAMBANG -->
    <div id="floatingPanduan" class="fixed bottom-5 right-5 z-50 max-w-sm bg-slate-900 text-white rounded-2xl shadow-2xl border border-slate-700/80 p-4 transition-all duration-300 hidden">
        <div class="flex items-start justify-between gap-3 mb-2">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center shrink-0">
                    <i data-lucide="award" class="w-4 h-4"></i>
                </div>
                <h4 class="text-xs font-bold text-white">Panduan Kelulusan Tasmi'</h4>
            </div>
            <button type="button" onclick="tutupNotifPanduan()" class="text-slate-400 hover:text-white transition"><i data-lucide="x" class="w-4 h-4"></i></button>
        </div>
        <ul class="text-[11px] text-slate-300 space-y-1 pl-1">
            <li>• <b>Predikat A (Mumtaz):</b> Lulus sempurna, berhak cetak Syahadah.</li>
            <li>• <b>Predikat B (Jayyid):</b> Lulus dengan catatan perbaikan.</li>
            <li>• <b>Predikat C (Ulang):</b> Belum memenuhi standar kelulusan.</li>
        </ul>
    </div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>