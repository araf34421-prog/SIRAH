<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ortu') {
    header("Location: login.php");
    exit;
}

$id_ortu = (int) $_SESSION['user_id'];
$anak_list = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM santri WHERE id_user_ortu = $id_ortu ORDER BY nama ASC"), MYSQLI_ASSOC);

$id_santri_pilih = isset($_GET['id_santri']) ? (int) $_GET['id_santri'] : ($anak_list[0]['id'] ?? 0);
$santri = null;
foreach ($anak_list as $a) {
    if ($a['id'] == $id_santri_pilih) {
        $santri = $a;
        break;
    }
}

$target_data = null;
$ayat_tercapai_target = 0;
$stat_data = ['total_ziyadah' => 0, 'total_murajaah' => 0, 'total_tasmi' => 0];
$riwayat_list = [];
$chart_labels = [];
$chart_ziyadah = [];

if ($santri) {
    $id_santri = $santri['id'];
    $bulan_ini = date('Y-m');

    // Ambil Target Aktif & Progresnya
    $target_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM target_hafalan WHERE id_santri = $id_santri ORDER BY id DESC LIMIT 1"));
    if ($target_data) {
        $tgl_mulai = $target_data['tanggal_mulai'];
        $q_capai = mysqli_query($conn, "SELECT COALESCE(SUM(jumlah_ayat), 0) as capai FROM hafalan WHERE id_santri = $id_santri AND jenis = 'ziyadah' AND tanggal >= '$tgl_mulai'");
        $ayat_tercapai_target = (int) mysqli_fetch_assoc($q_capai)['capai'];
    }

    // Statistik Bulan Ini
    $stat_query = "SELECT 
                    COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as total_ziyadah,
                    COALESCE(SUM(CASE WHEN jenis = 'murajaah' THEN 1 ELSE 0 END), 0) as total_murajaah,
                    COALESCE(SUM(CASE WHEN jenis = 'tasmi' THEN 1 ELSE 0 END), 0) as total_tasmi
                   FROM hafalan 
                   WHERE id_santri = $id_santri AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
    $stat_data = mysqli_fetch_assoc(mysqli_query($conn, $stat_query));

    // Grafik 6 Bulan
    for ($i = 5; $i >= 0; $i--) {
        $bulan = date('Y-m', strtotime("-$i months"));
        $chart_labels[] = date('M Y', strtotime("-$i months"));
        $q = "SELECT COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as z FROM hafalan WHERE id_santri = $id_santri AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'";
        $chart_ziyadah[] = (int) mysqli_fetch_assoc(mysqli_query($conn, $q))['z'];
    }

    // Riwayat 20 Setoran Terakhir
    $riwayat_query = "SELECT h.*, u.nama_lengkap as nama_ustadz 
                      FROM hafalan h 
                      LEFT JOIN users u ON h.id_ustadz = u.id 
                      WHERE h.id_santri = $id_santri 
                      ORDER BY h.tanggal DESC, h.id DESC LIMIT 20";
    $riwayat_list = mysqli_fetch_all(mysqli_query($conn, $riwayat_query), MYSQLI_ASSOC);
}
?>
<?php
$page_title = "Monitoring Progres Anak - Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <?php if (!$santri): ?>
            <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-6 rounded-2xl">
                <h3 class="font-bold text-lg mb-1">Data Anak Belum Terhubung</h3>
                <p class="text-sm">Akun orang tua Anda belum ditautkan dengan data santri. Silakan hubungi Ustadz pembimbing.</p>
            </div>
        <?php else: ?>

        <!-- Header -->
        <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 flex items-center gap-3">
                    <div class="p-2.5 bg-indigo-100 rounded-xl"><i data-lucide="book-open-check" class="w-7 h-7 text-indigo-600"></i></div>
                    Progres Hafalan Ananda
                </h1>
                <p class="text-sm text-slate-500 mt-2">Pantau capaian target, statistik bulanan, dan catatan evaluasi dari Ustadz pembimbing.</p>
            </div>

            <?php if (count($anak_list) > 1): ?>
            <form method="GET" class="bg-white border border-slate-200 px-3 py-2 rounded-xl shadow-sm flex items-center gap-2">
                <label class="text-xs font-bold text-slate-400 uppercase">Pilih Ananda:</label>
                <select name="id_santri" onchange="this.form.submit()" class="text-sm font-bold text-slate-700 outline-none cursor-pointer">
                    <?php foreach ($anak_list as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $a['id'] == $santri['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
        </div>

        <!-- PROFIL & REALISASI TARGET -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-indigo-600 text-white font-extrabold text-2xl flex items-center justify-center shrink-0">
                    <?= strtoupper(substr($santri['nama'], 0, 1)) ?>
                </div>
                <div>
                    <span class="text-[11px] font-bold uppercase tracking-wider text-indigo-600 bg-indigo-50 px-2.5 py-0.5 rounded-full">Santri Aktif</span>
                    <h2 class="text-xl font-extrabold text-slate-900 mt-1"><?= htmlspecialchars($santri['nama']) ?></h2>
                    <p class="text-xs text-slate-500 mt-0.5">NIS: <?= htmlspecialchars($santri['nis']) ?> • <?= $santri['jenis_kelamin'] == 'L' ? 'Putra' : 'Putri' ?></p>
                </div>
            </div>

            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm">
                <?php if ($target_data): 
                    $tot_target = max(1, (int) $target_data['total_ayat_target']);
                    $persen = min(100, round(($ayat_tercapai_target / $tot_target) * 100));
                ?>
                    <div class="flex flex-wrap justify-between items-start gap-4 mb-3">
                        <div>
                            <p class="text-xs font-bold text-teal-600 uppercase tracking-wider">Target Hafalan Berjalan</p>
                            <h3 class="text-lg font-bold text-slate-900 mt-0.5"><?= htmlspecialchars($target_data['target_surat'] ?: 'Program Hafalan') ?></h3>
                            <p class="text-xs text-slate-500"><?= htmlspecialchars($target_data['keterangan'] ?: 'Kecepatan: ' . $target_data['target_ayat_per_hari'] . ' ayat/hari') ?></p>
                        </div>
                        <div class="text-right">
                            <span class="text-2xl font-black text-teal-600"><?= $persen ?>%</span>
                            <p class="text-[11px] text-slate-400">Estimasi: <?= $target_data['tanggal_estimasi_selesai'] ? date('d M Y', strtotime($target_data['tanggal_estimasi_selesai'])) : '-' ?></p>
                        </div>
                    </div>
                    <div class="w-full h-3 bg-slate-100 rounded-full overflow-hidden border border-slate-200 mb-2">
                        <div class="h-full bg-gradient-to-r from-teal-500 to-emerald-500 transition-all duration-500" style="width: <?= $persen ?>%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-slate-500">
                        <span>Tercapai: <b><?= $ayat_tercapai_target ?> Ayat</b></span>
                        <span>Target: <b><?= $tot_target ?> Ayat</b> (<?= $target_data['target_ayat_per_hari'] ?> ayat/hari)</span>
                    </div>
                <?php else: ?>
                    <div class="h-full flex items-center justify-between">
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase">Target Hafalan</p>
                            <p class="text-sm font-semibold text-slate-700 mt-1">Ustadz belum menetapkan target spesifik untuk periode ini.</p>
                        </div>
                        <i data-lucide="target" class="w-8 h-8 text-slate-300"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- STATISTIK BULAN INI -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Ziyadah Bulan Ini</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-1"><?= $stat_data['total_ziyadah'] ?></p>
                    <p class="text-xs text-slate-400 mt-1">Total ayat baru dihafal</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center"><i data-lucide="book-plus" class="w-6 h-6"></i></div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Muraja'ah Bulan Ini</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-1"><?= $stat_data['total_murajaah'] ?></p>
                    <p class="text-xs text-slate-400 mt-1">Kali mengulang hafalan</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center"><i data-lucide="refresh-cw" class="w-6 h-6"></i></div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-purple-600 uppercase tracking-wider">Ujian Tasmi'</p>
                    <p class="text-3xl font-extrabold text-slate-900 mt-1"><?= $stat_data['total_tasmi'] ?></p>
                    <p class="text-xs text-slate-400 mt-1">Kali ujian sekali duduk</p>
                </div>
                <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center"><i data-lucide="award" class="w-6 h-6"></i></div>
            </div>
        </div>

        <!-- GRAFIK PROGRES -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm mb-8">
            <h2 class="text-base font-bold text-slate-900 mb-1 flex items-center gap-2">
                <i data-lucide="bar-chart-2" class="w-5 h-5 text-indigo-600"></i> Grafik Capaian Ziyadah 6 Bulan Terakhir
            </h2>
            <p class="text-xs text-slate-500 mb-6">Jumlah ayat baru yang berhasil disetorkan setiap bulan</p>
            <div style="height: 260px;">
                <canvas id="progresAnakChart"></canvas>
            </div>
        </div>

        <!-- TABEL RIWAYAT SETORAN -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex flex-wrap justify-between items-center gap-4">
                <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                    <i data-lucide="history" class="w-5 h-5 text-indigo-600"></i> Riwayat Setoran & Catatan Ustadz
                </h3>
                <select id="filterJenis" onchange="filterRiwayat()" class="px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 outline-none">
                    <option value="ALL">Semua Jenis Setoran</option>
                    <option value="ziyadah">Ziyadah (Hafalan Baru)</option>
                    <option value="murajaah">Muraja'ah (Pengulangan)</option>
                    <option value="tasmi">Tasmi' (Ujian)</option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-6 font-semibold">Tanggal</th>
                            <th class="py-3.5 px-4 font-semibold">Kategori</th>
                            <th class="py-3.5 px-4 font-semibold">Surat & Ayat</th>
                            <th class="py-3.5 px-4 font-semibold text-center">Predikat</th>
                            <th class="py-3.5 px-4 font-semibold">Catatan Evaluasi Ustadz</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100">
                        <?php if (empty($riwayat_list)): ?>
                            <tr><td colspan="5" class="py-12 text-center text-slate-400">Belum ada riwayat setoran untuk ananda.</td></tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $r): 
                                $badge = $r['jenis'] == 'ziyadah' ? 'bg-emerald-100 text-emerald-700' : ($r['jenis'] == 'murajaah' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700');
                                $p_badge = $r['predikat'] == 'A' ? 'bg-emerald-500 text-white' : ($r['predikat'] == 'B' ? 'bg-amber-500 text-white' : 'bg-rose-500 text-white');
                            ?>
                                <tr class="baris-riwayat hover:bg-slate-50 transition" data-jenis="<?= $r['jenis'] ?>">
                                    <td class="py-4 px-6 text-xs font-medium text-slate-500"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                                    <td class="py-4 px-4"><span class="px-2.5 py-1 rounded-lg text-[11px] font-bold uppercase <?= $badge ?>"><?= $r['jenis'] ?></span></td>
                                    <td class="py-4 px-4">
                                        <p class="font-bold text-slate-800"><?= htmlspecialchars($r['surat']) ?></p>
                                        <p class="text-xs text-slate-400">Ayat <?= $r['ayat_awal'] ?> - <?= $r['ayat_akhir'] ?></p>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg font-bold text-xs <?= $p_badge ?>"><?= $r['predikat'] ?></span>
                                    </td>
                                    <td class="py-4 px-4 text-xs text-slate-600">
                                        <p class="italic">"<?= htmlspecialchars($r['catatan'] ?: 'Lancar, pertahankan.') ?>"</p>
                                        <p class="text-[10px] text-slate-400 mt-0.5">— <?= htmlspecialchars($r['nama_ustadz'] ?? 'Ustadz Pembimbing') ?></p>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <?php
$page_data = [
    'chart_active' => (bool) $santri,
    'chart_labels' => $chart_labels,
    'chart_ziyadah' => $chart_ziyadah,
];
include __DIR__ . '/../../includes/page_data.php';
?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>