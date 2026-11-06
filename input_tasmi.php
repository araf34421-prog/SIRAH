<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

$id_ustadz = (int) $_SESSION['user_id'];
$role      = $_SESSION['role'];
$nama_user = $_SESSION['nama_lengkap'] ?? '';
$is_pengelola_penuh = in_array($role, ['operator', 'pimpinan']);

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

// Filter santri sesuai kelas ustadz jika bukan operator/pimpinan
$where_santri = "";
if (!$is_pengelola_penuh) {
    $u_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_halaqah, nama_lengkap FROM users WHERE id = $id_ustadz"));
    $id_h_user = (int) ($u_info['id_halaqah'] ?? 0);
    $nm_esc    = mysqli_real_escape_string($conn, trim($u_info['nama_lengkap'] ?? $nama_user));
    
    // Ambil ID lokal yang diampu ustadz ini
    $q_lokal_ust = mysqli_query($conn, "SELECT id FROM halaqah WHERE id_ustadz = $id_ustadz OR id = $id_h_user OR LOWER(nama_pengajar) = LOWER('$nm_esc')");
    $lokal_ids = [];
    while ($lk = mysqli_fetch_assoc($q_lokal_ust)) {
        $lokal_ids[] = (int)$lk['id'];
    }

    if (!empty($lokal_ids)) {
        $list_id_str = implode(',', $lokal_ids);
        $where_santri = "WHERE s.id_halaqah IN ($list_id_str)";
    } else {
        // Jika tidak mengampu lokal manapun, tampilkan kosong
        $where_santri = "WHERE 1 = 0";
    }
}

$santri_query = "SELECT s.id, s.nama, s.nis, h.nama_halaqah 
                 FROM santri s 
                 LEFT JOIN halaqah h ON s.id_halaqah = h.id 
                 $where_santri 
                 ORDER BY s.nama ASC";
$santri_result = mysqli_query($conn, $santri_query);
$santri_list = mysqli_fetch_all($santri_result, MYSQLI_ASSOC);

require_once 'includes/init_rbac.php';
$where_binaan = getFilterSantriBinaanSQL($conn, $_SESSION['user_id'], $_SESSION['role'], 's');

// Riwayat tasmi hari ini
$jenis_halaman = 'tasmi';
$and_binaan = !empty($where_binaan) ? str_replace('WHERE', 'AND', $where_binaan) : '';
$riwayat_query = "SELECT h.*, s.nama as nama_santri, s.nama_ortu, s.no_hp_ortu, hl.nama_halaqah 
                  FROM hafalan h 
                  JOIN santri s ON h.id_santri = s.id 
                  LEFT JOIN halaqah hl ON s.id_halaqah = hl.id
                  WHERE h.tanggal = CURDATE() AND h.jenis = '$jenis_halaman' $and_binaan
                  ORDER BY h.id DESC";
$riwayat_list = mysqli_fetch_all(mysqli_query($conn, $riwayat_query), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Tasmi' - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .predikat-radio input[type="radio"] { display: none; }
        .predikat-radio input[value="A"]:checked + div {
            border-color: #9333ea; background-color: #faf5ff; color: #7e22ce;
            box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.15);
        }
        .predikat-radio input[value="B"]:checked + div {
            border-color: #f59e0b; background-color: #fffbeb; color: #b45309;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }
        .predikat-radio input[value="C"]:checked + div {
            border-color: #f43f5e; background-color: #fff1f2; color: #be123c;
            box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.15);
        }
        .predikat-radio input[type="radio"]:checked + div .check-badge { opacity: 1; transform: scale(1); }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- Header Halaman -->
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

            <div class="flex items-center gap-2.5">
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
                    <h2 class="text-sm font-bold text-slate-800">Formulir Ujian Tasmi' Baru</h2>
                </div>
                <span class="text-xs text-slate-500">Ujian Sekali Duduk (1 Juz / Beberapa Juz)</span>
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
                        <i data-lucide="book-open" class="w-3.5 h-3.5 text-purple-600 inline mr-1"></i> Materi Ujian Tasmi' (Juz, Pojok & Keterangan Surat)
                    </span>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">
                        <div class="lg:col-span-3">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Juz <span class="text-rose-500">*</span></label>
                            <select name="juz" id="inputJuz" required class="w-full px-3 py-2.5 bg-white border border-slate-300 focus:border-purple-600 rounded-xl text-sm font-semibold text-slate-800 outline-none cursor-pointer">
                                <option value="">-- Pilih Juz --</option>
                                <?php for ($j = 1; $j <= 30; $j++): ?>
                                    <option value="<?= $j ?>" <?= $j === 30 ? 'selected' : '' ?>>Juz <?= $j ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="lg:col-span-4">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Pojok / Catatan Halaman (Opsional)</label>
                            <input type="text" name="pojok" placeholder="Cth: Pojok 1 s.d 20" class="w-full px-3 py-2.5 bg-white border border-slate-300 focus:border-purple-600 rounded-xl text-sm font-medium text-slate-800 outline-none">
                        </div>

                        <div class="lg:col-span-5">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Judul / Surat Ujian <span class="text-rose-500">*</span></label>
                            <input type="text" name="surat" required placeholder="Cth: Juz 30 Full / Surat Al-Baqarah" class="w-full px-3 py-2.5 bg-white border border-slate-300 focus:border-purple-600 rounded-xl text-sm font-medium text-slate-800 outline-none">
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
                        <input type="text" name="catatan" placeholder="Cth: Siap lanjut ke juz berikutnya..." class="w-full px-3.5 py-4 bg-slate-50 border border-slate-300 focus:border-purple-600 rounded-xl text-xs font-medium text-slate-800 outline-none">
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">Pastikan seluruh ketentuan ujian tasmi' telah terpenuhi.</span>
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2.5 px-7 rounded-xl transition shadow-sm flex items-center gap-2 text-xs">
                        <i data-lucide="save" class="w-4 h-4"></i> Simpan Hasil Tasmi'
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- TABEL RIWAYAT HARI INI -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-slate-500"></i> Riwayat Ujian Tasmi' Hari Ini
                </h3>
                <span class="bg-slate-100 text-slate-600 text-xs font-bold px-3 py-1 rounded-full"><?= count($riwayat_list) ?> Ujian</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/70 text-slate-500 text-[11px] font-semibold uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-6 w-24">Waktu</th>
                            <th class="py-3 px-4">Nama Santri</th>
                            <th class="py-3 px-4">Juz & Pojok</th>
                            <th class="py-3 px-4">Materi Ujian</th>
                            <th class="py-3 px-4 text-center">Predikat</th>
                            <th class="py-3 px-4">Catatan Penguji</th>
                            <th class="py-3 px-6 text-right w-16">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs text-slate-600 divide-y divide-slate-100">
                        <?php if (empty($riwayat_list)): ?>
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-400">
                                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                    <p class="font-medium text-slate-600">Belum ada ujian tasmi' yang tercatat hari ini</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $r): 
                                $badge = $r['predikat'] === 'A' ? 'bg-purple-50 text-purple-700 border-purple-200' : ($r['predikat'] === 'B' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200');
                            ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3.5 px-6 font-mono text-slate-400"><?= date('H:i', strtotime($r['created_at'])) ?></td>
                                <td class="py-3.5 px-4 font-bold text-slate-900"><?= htmlspecialchars($r['nama_santri']) ?></td>
                                <td class="py-3.5 px-4">
                                    <?php if (!empty($r['juz'])): ?>
                                        <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold text-[11px]">Juz <?= (int)$r['juz'] ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($r['pojok'])): ?>
                                        <span class="text-slate-600 ml-1"><?= htmlspecialchars($r['pojok']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-slate-800"><?= htmlspecialchars($r['surat']) ?></td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg font-bold border <?= $badge ?>">
                                        <?= htmlspecialchars($r['predikat']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 italic max-w-xs truncate"><?= htmlspecialchars($r['catatan'] ?: '-') ?></td>
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
            <li><b>Predikat A (Mumtaz):</b> Lulus sempurna, siap lanjut juz baru.</li>
            <li><b>Predikat B (Jayyid):</b> Lulus dengan sedikit catatan.</li>
            <li><b>Predikat C (Ulang):</b> Belum memenuhi standar, wajib ujian ulang.</li>
        </ul>
    </div>

    <script>
        lucide.createIcons();
        const toastEl = document.getElementById('floatingPanduan');
        if (localStorage.getItem('hide_panduan_tasmi') !== '1') toastEl.classList.remove('hidden');
        function tutupNotifPanduan() { toastEl.classList.add('hidden'); localStorage.setItem('hide_panduan_tasmi', '1'); }
        function tampilkanNotifPanduan() { toastEl.classList.remove('hidden'); localStorage.removeItem('hide_panduan_tasmi'); }
    </script>
</body>
</html>