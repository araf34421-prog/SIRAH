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
$where_binaan = getFilterSantriBinaanSQL($conn, $id_ustadz, $role, 's');
$and_binaan = $where_binaan !== '' ? ' AND ' . preg_replace('/^\s*WHERE\s+/i', '', $where_binaan) : '';

// Pastikan seluruh kolom yang dibutuhkan ada di tabel target_hafalan (auto-migrasi)
$kolom_target = [
    'target_ayat_per_hari'     => "INT(11) DEFAULT NULL",
    'target_surat'             => "VARCHAR(100) DEFAULT NULL",
    'total_ayat_target'        => "INT(11) DEFAULT NULL",
    'tanggal_estimasi_selesai' => "DATE DEFAULT NULL",
    'keterangan'               => "TEXT DEFAULT NULL",
];
foreach ($kolom_target as $nama_kol => $definisi) {
    $cek_kol = mysqli_query($conn, "SHOW COLUMNS FROM target_hafalan LIKE '$nama_kol'");
    if ($cek_kol && mysqli_num_rows($cek_kol) === 0) {
        mysqli_query($conn, "ALTER TABLE target_hafalan ADD COLUMN `$nama_kol` $definisi");
    }
}

// Proses Simpan / Update Target
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_santri    = (int) $_POST['id_santri'];
    $target_ayat  = max(1, (int) $_POST['target_ayat']);
    $target_surat = mysqli_real_escape_string($conn, trim($_POST['target_surat']));
    $total_ayat   = max(1, (int) $_POST['total_ayat']);
    $tgl_mulai    = mysqli_real_escape_string($conn, $_POST['tanggal_mulai']);
    $ket          = mysqli_real_escape_string($conn, trim($_POST['keterangan']));
    
    // Hitung estimasi selesai
    $hari_dibutuhkan = (int) ceil($total_ayat / $target_ayat);
    $tgl_selesai = date('Y-m-d', strtotime($tgl_mulai . " + $hari_dibutuhkan days"));

    $cek = mysqli_query($conn, "SELECT id FROM target_hafalan WHERE id_santri = $id_santri");
    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "UPDATE target_hafalan SET 
            target_ayat_per_hari = $target_ayat, 
            target_surat = '$target_surat', 
            total_ayat_target = $total_ayat, 
            tanggal_mulai = '$tgl_mulai', 
            tanggal_estimasi_selesai = '$tgl_selesai', 
            keterangan = '$ket' 
            WHERE id_santri = $id_santri");
    } else {
        mysqli_query($conn, "INSERT INTO target_hafalan 
            (id_santri, target_ayat_per_hari, target_surat, total_ayat_target, tanggal_mulai, tanggal_estimasi_selesai, keterangan) 
            VALUES ($id_santri, $target_ayat, '$target_surat', $total_ayat, '$tgl_mulai', '$tgl_selesai', '$ket')");
    }
    $_SESSION['success'] = "Target hafalan santri berhasil disimpan!";
    header("Location: target_santri.php"); 
    exit;
}

// Hapus Target jika diminta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    $id_hapus = (int) $_POST['hapus'];
    $deleted = mysqli_query($conn, "DELETE t FROM target_hafalan t JOIN santri s ON s.id = t.id_santri WHERE t.id = $id_hapus $and_binaan");
    $_SESSION['success'] = $deleted && mysqli_affected_rows($conn) > 0
        ? "Data target hafalan berhasil dihapus."
        : "Target tidak ditemukan atau berada di luar kewenangan Anda.";
    header("Location: target_santri.php");
    exit;
}

// Filter santri khusus binaan Ustadz/Ustadzah yang login
$santri_query = "SELECT s.id, s.nama, s.nis, h.nama_halaqah 
                 FROM santri s 
                 LEFT JOIN halaqah h ON s.id_halaqah = h.id 
                 $where_binaan 
                 ORDER BY s.nama ASC";
$santri_list = mysqli_fetch_all(mysqli_query($conn, $santri_query), MYSQLI_ASSOC);

// Ambil daftar target khusus santri binaan beserta progres ziyadahnya
$sql_target = "SELECT t.*, s.nama, s.nis, s.jenis_kelamin, hl.nama_halaqah,
               COALESCE((
                   SELECT SUM(h.jumlah_ayat) 
                   FROM hafalan h 
                   WHERE h.id_santri = t.id_santri 
                     AND h.jenis = 'ziyadah' 
                     AND h.tanggal >= t.tanggal_mulai
               ), 0) AS ayat_tercapai
               FROM target_hafalan t 
               JOIN santri s ON t.id_santri = s.id 
               LEFT JOIN halaqah hl ON s.id_halaqah = hl.id
               $where_binaan
               ORDER BY s.nama ASC";
$target_list = mysqli_fetch_all(mysqli_query($conn, $sql_target), MYSQLI_ASSOC);
?>
<?php
$page_title = "Target Hafalan - Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- Header Halaman -->
        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 bg-white border border-slate-200 rounded-xl shadow-sm flex items-center justify-center text-teal-600">
                    <i data-lucide="target" class="w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Perencanaan Target Hafalan</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Susun target capaian ayat harian santri binaan dan pantau estimasi ketuntasannya.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <?= renderBadgeFilterBinaan($role) ?>
                <div class="bg-white border border-slate-200 px-4 py-2 rounded-xl shadow-sm text-xs font-semibold text-slate-600 flex items-center gap-2">
                    <i data-lucide="check-square" class="w-4 h-4 text-teal-600"></i>
                    <span><?= count($target_list) ?> / <?= count($santri_list) ?> Santri Punya Target</span>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 text-xs font-medium flex items-center gap-2.5 shadow-sm">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
                <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- GRID FORM & KALKULATOR ESTIMASI -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            
            <!-- FORM TARGET -->
            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i data-lucide="sliders" class="w-4 h-4 text-teal-600"></i> Formulir Penetapan Target Individual
                    </h3>
                    <span class="text-[11px] bg-teal-50 text-teal-700 font-semibold px-2.5 py-0.5 rounded-full border border-teal-200">
                        Input / Update Otomatis
                    </span>
                </div>

                <form method="POST" id="formTarget" class="p-6 space-y-5" autocomplete="off"><?php echo csrfTokenField(); ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Pilih Santri Binaan <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                                <select name="id_santri" id="id_santri" required class="w-full pl-10 pr-3 py-2.5 bg-slate-50 hover:bg-white border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none cursor-pointer transition">
                                    <option value="">-- Pilih Nama Santri --</option>
                                    <?php foreach ($santri_list as $s): ?>
                                        <option value="<?= $s['id'] ?>">
                                            <?= htmlspecialchars($s['nama']) ?> 
                                            <?= !empty($s['nama_halaqah']) ? ' — [' . htmlspecialchars($s['nama_halaqah']) . ']' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Target Surat / Juz <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <i data-lucide="book-marked" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                                <input type="text" name="target_surat" id="target_surat" list="presetTarget" oninput="cekPresetAyat(this.value)" required 
                                    class="w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none" 
                                    placeholder="Ketik atau pilih surat/juz...">
                                <datalist id="presetTarget">
                                    <option value="Juz 30 (Juz Amma)">564 Ayat</option>
                                    <option value="Juz 29 (Tabarak)">431 Ayat</option>
                                    <option value="Juz 1">148 Ayat</option>
                                    <option value="QS. Al-Baqarah">286 Ayat</option>
                                    <option value="QS. Al-Kahf">110 Ayat</option>
                                    <option value="QS. Yasin">83 Ayat</option>
                                    <option value="QS. Ar-Rahman">78 Ayat</option>
                                    <option value="QS. Al-Waqi'ah">96 Ayat</option>
                                    <option value="QS. Al-Mulk">30 Ayat</option>
                                    <option value="QS. An-Naba'">40 Ayat</option>
                                    <option value="QS. An-Nazi'at">46 Ayat</option>
                                    <option value="QS. 'Abasa">42 Ayat</option>
                                </datalist>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Total Ayat Target <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="number" name="total_ayat" id="total_ayat" min="1" required 
                                    class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-teal-500 outline-none" 
                                    placeholder="Cth: 40">
                                <span class="text-xs text-slate-400 absolute right-3.5 top-3">Ayat</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Target Kecepatan <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="number" name="target_ayat" id="target_ayat" min="1" required 
                                    class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm font-semibold focus:ring-2 focus:ring-teal-500 outline-none" 
                                    placeholder="Cth: 5">
                                <span class="text-xs text-slate-400 absolute right-3.5 top-3">Ayat / Hari</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Mulai <span class="text-rose-500">*</span></label>
                            <input type="date" name="tanggal_mulai" id="tgl_mulai" required value="<?= date('Y-m-d') ?>" 
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none text-slate-700">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Catatan / Fokus Pembinaan (Opsional)</label>
                        <input type="text" name="keterangan" id="keterangan" 
                            class="w-full px-4 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-teal-500 outline-none" 
                            placeholder="Cth: Fokus perbaikan makharijul huruf dan kelancaran...">
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white font-semibold py-2.5 px-7 rounded-xl transition shadow-sm flex items-center gap-2 text-xs">
                            <i data-lucide="save" class="w-4 h-4"></i> Simpan Target Santri
                        </button>
                    </div>
                </form>
            </div>

            <!-- KALKULATOR PREVIEW ESTIMASI -->
            <div class="bg-gradient-to-br from-teal-600 to-emerald-700 rounded-2xl p-6 text-white shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-bold uppercase tracking-widest text-teal-100">Simulasi Waktu</span>
                        <i data-lucide="calculator" class="w-5 h-5 text-teal-200"></i>
                    </div>
                    <h4 class="text-lg font-bold mb-1">Estimasi Ketuntasan</h4>
                    <p class="text-xs text-teal-100 leading-relaxed mb-6">Sistem menghitung otomatis kapan santri diperkirakan khatam materi target berdasarkan kecepatan hariannya.</p>

                    <div class="bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl p-4 space-y-3">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-teal-100">Durasi Menghafal:</span>
                            <span id="preview_hari" class="font-bold text-base text-white">- Hari</span>
                        </div>
                        <div class="border-t border-white/10 pt-3 flex justify-between items-center text-xs">
                            <span class="text-teal-100">Estimasi Selesai:</span>
                            <span id="preview_tanggal" class="font-bold text-sm bg-white text-teal-800 px-2.5 py-1 rounded-lg">Belum dihitung</span>
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-white/15 text-[11px] text-teal-100 flex items-start gap-2">
                    <i data-lucide="sparkles" class="w-4 h-4 shrink-0 mt-0.5"></i>
                    <span>Klik tombol <b>Edit</b> pada tabel di bawah jika ingin menyesuaikan target santri yang sedang berjalan.</span>
                </div>
            </div>

        </div>

        <!-- TABEL MONITORING TARGET AKTIF -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                        <i data-lucide="bar-chart-3" class="w-4 h-4 text-teal-600"></i> Daftar & Realisasi Target Santri Binaan
                    </h3>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/70 text-slate-500 uppercase text-[11px] font-semibold tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-6">Nama Santri & Lokal</th>
                            <th class="py-3.5 px-4">Materi Target</th>
                            <th class="py-3.5 px-4 text-center">Kecepatan</th>
                            <th class="py-3.5 px-4 w-64">Progres Realisasi (Ziyadah)</th>
                            <th class="py-3.5 px-4">Target Selesai</th>
                            <th class="py-3.5 px-6 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs text-slate-600 divide-y divide-slate-100">
                        <?php if (empty($target_list)): ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <i data-lucide="target" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                    <p class="font-medium text-slate-600">Belum ada target hafalan untuk santri binaan ini.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($target_list as $t): 
                                $total_target = max(1, (int) $t['total_ayat_target']);
                                $tercapai     = (int) $t['ayat_tercapai'];
                                $persen       = min(100, round(($tercapai / $total_target) * 100));
                                $bar_color    = $persen >= 100 ? 'bg-emerald-500' : ($persen >= 50 ? 'bg-teal-500' : 'bg-amber-500');
                            ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-6">
                                    <p class="font-bold text-slate-900"><?= htmlspecialchars($t['nama']) ?></p>
                                    <p class="text-[10px] text-slate-400"><?= htmlspecialchars($t['nama_halaqah'] ?: 'Tanpa Lokal') ?> • Mulai: <?= date('d M Y', strtotime($t['tanggal_mulai'])) ?></p>
                                </td>
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-800"><?= htmlspecialchars($t['target_surat'] ?: 'Belum ditentukan') ?></p>
                                    <p class="text-[11px] text-slate-400"><?= htmlspecialchars($t['keterangan'] ?: 'Target ' . $total_target . ' Ayat') ?></p>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg font-bold bg-teal-50 text-teal-700 border border-teal-200">
                                        <?= $t['target_ayat_per_hari'] ?> Ayat/Hari
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="flex justify-between text-[11px] mb-1">
                                        <span class="font-semibold text-slate-700"><?= $tercapai ?> / <?= $total_target ?> Ayat</span>
                                        <span class="font-bold text-teal-700"><?= $persen ?>%</span>
                                    </div>
                                    <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden border border-slate-200">
                                        <div class="h-full <?= $bar_color ?> transition-all duration-500" style="width: <?= $persen ?>%"></div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <?php if (!empty($t['tanggal_estimasi_selesai'])): ?>
                                        <p class="font-semibold text-slate-700"><?= date('d M Y', strtotime($t['tanggal_estimasi_selesai'])) ?></p>
                                    <?php else: ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-6 text-center">
                                    <div class="flex items-center justify-center gap-1">
                                        <button type="button" onclick="muatKeForm(<?= htmlspecialchars(json_encode($t), ENT_QUOTES) ?>)" 
                                            title="Edit Target Ini" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-lg transition">
                                            <i data-lucide="edit-3" class="w-4 h-4"></i>
                                        </button>
                                        <a href="target_santri.php?hapus=<?= $t['id'] ?>" 
                                           onclick="return confirm('Hapus target hafalan untuk <?= htmlspecialchars(addslashes($t['nama'])) ?>?')"
                                           title="Hapus Target" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg transition">
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
<?php include __DIR__ . '/../../includes/footer.php'; ?>
