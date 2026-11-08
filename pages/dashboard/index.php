<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$nama = $_SESSION['nama_lengkap'];
$role = $_SESSION['role'];

$jam_sekarang = (int) date('H');
$sapaan_waktu = ($jam_sekarang >= 4 && $jam_sekarang < 11) ? 'Selamat Pagi' : (($jam_sekarang >= 11 && $jam_sekarang < 15) ? 'Selamat Siang' : (($jam_sekarang >= 15 && $jam_sekarang < 18) ? 'Selamat Sore' : 'Selamat Malam'));

$chart_labels = [];
$chart_ziyadah = [];
$chart_murajaah = [];
$stat = ['total_ayat' => 0, 'total_murajaah' => 0, 'total_tasmi' => 0, 'santri_aktif' => 0];
$total_santri = 0;
$total_ustadz = 0;
$predikat_data = ['A' => 0, 'B' => 0, 'C' => 0];
$aktivitas_list = [];
$target_widget = ['total' => 0, 'tercapai' => 0, 'persentase' => 0, 'label' => ''];

if (in_array($role, ['operator', 'ustadz', 'pimpinan'])) {
    $total_santri = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM santri"))['total'];
    $total_ustadz = (int) mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'ustadz'"))['total'];

    $bulan_ini = date('Y-m');
    $stat_query = "SELECT 
                    COUNT(DISTINCT id_santri) as santri_aktif,
                    COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as total_ayat,
                    COALESCE(SUM(CASE WHEN jenis = 'murajaah' THEN 1 ELSE 0 END), 0) as total_murajaah,
                    COALESCE(SUM(CASE WHEN jenis = 'tasmi' THEN 1 ELSE 0 END), 0) as total_tasmi
                  FROM hafalan 
                  WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
    $stat = mysqli_fetch_assoc(mysqli_query($conn, $stat_query));

    for ($i = 5; $i >= 0; $i--) {
        $bulan = date('Y-m', strtotime("-$i months"));
        $chart_labels[] = date('M Y', strtotime("-$i months"));
        $q = "SELECT 
                COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as z,
                COALESCE(SUM(CASE WHEN jenis = 'murajaah' THEN 1 ELSE 0 END), 0) as m
              FROM hafalan WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'";
        $d = mysqli_fetch_assoc(mysqli_query($conn, $q));
        $chart_ziyadah[] = (int) $d['z'];
        $chart_murajaah[] = (int) $d['m'];
    }

    $predikat_res = mysqli_query($conn, "SELECT predikat, COUNT(*) as jumlah FROM hafalan WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini' GROUP BY predikat");
    while ($row = mysqli_fetch_assoc($predikat_res)) {
        if (isset($predikat_data[$row['predikat']])) {
            $predikat_data[$row['predikat']] = (int) $row['jumlah'];
        }
    }

    $target_widget['label'] = in_array($role, ['operator', 'pimpinan']) ? 'Realisasi Target Bulanan Lembaga' : 'Realisasi Target Santri Binaan';
    $target_widget['total'] = $total_santri;
    $capaian_query = "SELECT COUNT(DISTINCT s.id) as tercapai
                      FROM santri s
                      JOIN target_hafalan t ON s.id = t.id_santri
                      LEFT JOIN (
                          SELECT id_santri, SUM(jumlah_ayat) as total_ayat
                          FROM hafalan 
                          WHERE jenis = 'ziyadah' AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'
                          GROUP BY id_santri
                      ) h ON s.id = h.id_santri
                      WHERE (t.target_ayat_per_hari * 20) <= COALESCE(h.total_ayat, 0)";
    $target_widget['tercapai'] = (int) mysqli_fetch_assoc(mysqli_query($conn, $capaian_query))['tercapai'];
    $target_widget['persentase'] = $target_widget['total'] > 0 ? round(($target_widget['tercapai'] / $target_widget['total']) * 100) : 0;

    $aktivitas_query = "SELECT h.*, s.nama as nama_santri, u.nama_lengkap as nama_ustadz
                        FROM hafalan h
                        JOIN santri s ON h.id_santri = s.id
                        LEFT JOIN users u ON h.id_ustadz = u.id
                        ORDER BY h.created_at DESC LIMIT 6";
    $aktivitas_list = mysqli_fetch_all(mysqli_query($conn, $aktivitas_query), MYSQLI_ASSOC);

} elseif ($role === 'ortu') {
    $id_ortu = (int) $_SESSION['user_id'];
    $santri = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM santri WHERE id_user_ortu = $id_ortu LIMIT 1"));

    if ($santri) {
        $id_santri = $santri['id'];
        $bulan_ini = date('Y-m');

        $stat_query = "SELECT 
                        COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as total_ayat,
                        COALESCE(SUM(CASE WHEN jenis = 'murajaah' THEN 1 ELSE 0 END), 0) as total_murajaah,
                        COALESCE(SUM(CASE WHEN jenis = 'tasmi' THEN 1 ELSE 0 END), 0) as total_tasmi
                      FROM hafalan 
                      WHERE id_santri = $id_santri AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
        $stat = mysqli_fetch_assoc(mysqli_query($conn, $stat_query));

        for ($i = 5; $i >= 0; $i--) {
            $bulan = date('Y-m', strtotime("-$i months"));
            $chart_labels[] = date('M Y', strtotime("-$i months"));
            $q = "SELECT 
                    COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as z,
                    COALESCE(SUM(CASE WHEN jenis = 'murajaah' THEN 1 ELSE 0 END), 0) as m
                  FROM hafalan WHERE id_santri = $id_santri AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'";
            $d = mysqli_fetch_assoc(mysqli_query($conn, $q));
            $chart_ziyadah[] = (int) $d['z'];
            $chart_murajaah[] = (int) $d['m'];
        }

        $target_q = mysqli_query($conn, "SELECT target_ayat_per_hari FROM target_hafalan WHERE id_santri = $id_santri ORDER BY id DESC LIMIT 1");
        $target_anak = ($target_q && mysqli_num_rows($target_q) > 0) ? mysqli_fetch_assoc($target_q) : null;
        $target_ayat_harian = $target_anak ? (int) $target_anak['target_ayat_per_hari'] : 0;
        
        $target_widget['label'] = 'Capaian Target Bulanan Ananda';
        $target_widget['total'] = $target_ayat_harian * 20;
        $target_widget['tercapai'] = (int) $stat['total_ayat'];
        $target_widget['persentase'] = $target_widget['total'] > 0 ? min(100, round(($target_widget['tercapai'] / $target_widget['total']) * 100)) : 0;

        $aktivitas_query = "SELECT h.*, u.nama_lengkap as nama_ustadz
                            FROM hafalan h
                            LEFT JOIN users u ON h.id_ustadz = u.id
                            WHERE h.id_santri = $id_santri
                            ORDER BY h.created_at DESC LIMIT 6";
        $aktivitas_list = mysqli_fetch_all(mysqli_query($conn, $aktivitas_query), MYSQLI_ASSOC);
    }
}

$page_title = "Dashboard Eksekutif — SIRAH Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<body class="bg-slate-50 font-sans text-slate-900">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="ml-64 p-8 max-w-[1440px]">
        
        <!-- MODERN HERO GREETING BANNER -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-7 mb-7 shadow-xl border border-indigo-500/20">
            <div class="pointer-events-none absolute -right-12 -top-12 w-64 h-64 rounded-full bg-indigo-500/20 blur-3xl"></div>
            <div class="pointer-events-none absolute right-32 -bottom-16 w-52 h-52 rounded-full bg-emerald-500/15 blur-2xl"></div>

            <div class="relative z-10 flex flex-wrap items-center justify-between gap-6">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 border border-white/15 text-indigo-200 text-xs font-medium mb-3">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span><?= $sapaan_waktu ?>, <?= htmlspecialchars($nama) ?></span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-white">
                        <?php if ($role === 'ortu' && isset($santri)): ?>
                            Pantauan Tahfizh: <?= htmlspecialchars($santri['nama']) ?>
                        <?php else: ?>
                            Pusat Kendali Akademik & Tahfizh
                        <?php endif; ?>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-300 mt-1.5 max-w-xl">
                        Pantau perkembangan setoran Ziyadah, konsistensi Muraja'ah, serta evaluasi capaian target santri secara *real-time*.
                    </p>
                </div>

                <!-- QUICK ACTION BUTTONS DI BANNER -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <?php if (in_array($role, ['ustadz', 'operator'])): ?>
                        <a href="input_ziyadah.php" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-lg shadow-emerald-500/25">
                            <i data-lucide="book-plus" class="w-4 h-4"></i> Input Ziyadah
                        </a>
                        <a href="input_kehadiran.php" class="bg-white/10 hover:bg-white/20 text-white border border-white/15 font-semibold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2">
                            <i data-lucide="calendar-check" class="w-4 h-4 text-indigo-300"></i> Presensi Harian
                        </a>
                        <a href="input_rapor.php" class="bg-white/10 hover:bg-white/20 text-white border border-white/15 font-semibold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2">
                            <i data-lucide="table-2" class="w-4 h-4 text-amber-300"></i> Leger Rapor
                        </a>
                    <?php elseif ($role === 'pimpinan'): ?>
                        <a href="rekap_bulanan.php" class="bg-indigo-500 hover:bg-indigo-400 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-lg shadow-indigo-500/30">
                            <i data-lucide="file-bar-chart" class="w-4 h-4"></i> Rekap Bulanan
                        </a>
                        <a href="rapor_santri.php" class="bg-white/10 hover:bg-white/20 text-white border border-white/15 font-semibold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2">
                            <i data-lucide="printer" class="w-4 h-4 text-emerald-300"></i> Cetak Rapor
                        </a>
                    <?php elseif ($role === 'ortu'): ?>
                        <a href="monitoring_anak.php" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2 shadow-lg shadow-emerald-500/25">
                            <i data-lucide="line-chart" class="w-4 h-4"></i> Progres Lengkap
                        </a>
                        <a href="rapor_santri.php" class="bg-white/10 hover:bg-white/20 text-white border border-white/15 font-semibold text-xs px-4 py-2.5 rounded-xl transition flex items-center gap-2">
                            <i data-lucide="award" class="w-4 h-4 text-amber-300"></i> Lihat Rapor
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- BENTO METRIK CARDS (COLOR-CODED & INTERACTIVE) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-7">
            <?php if (in_array($role, ['operator', 'pimpinan'])): ?>
            <a href="kelola-ustadz.php" class="bg-white border border-slate-200 rounded-2xl p-5 flex items-start justify-between group">
                <div>
                    <span class="text-xs font-semibold text-slate-500">Total Asatidz</span>
                    <p class="text-3xl font-extrabold text-slate-900 font-mono mt-1.5 group-hover:text-indigo-600 transition"><?= $total_ustadz ?></p>
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-indigo-600 mt-2">
                        Kelola Pengajar <i data-lucide="arrow-up-right" class="w-3 h-3"></i>
                    </span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
            </a>
            <?php endif; ?>

            <?php if (in_array($role, ['operator', 'ustadz', 'pimpinan'])): ?>
            <a href="kelola_santri.php" class="bg-white border border-slate-200 rounded-2xl p-5 flex items-start justify-between group">
                <div>
                    <span class="text-xs font-semibold text-slate-500">Total Santri</span>
                    <div class="flex items-baseline gap-2 mt-1.5">
                        <p class="text-3xl font-extrabold text-slate-900 font-mono group-hover:text-emerald-600 transition"><?= $total_santri ?></p>
                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200"><?= $stat['santri_aktif'] ?? 0 ?> aktif</span>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium text-slate-400 mt-2">
                        Terdaftar dalam halaqah
                    </span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="graduation-cap" class="w-6 h-6"></i>
                </div>
            </a>
            <?php endif; ?>

            <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-start justify-between group">
                <div>
                    <span class="text-xs font-semibold text-slate-500">Capaian Ziyadah (<?= date('M') ?>)</span>
                    <div class="flex items-baseline gap-1.5 mt-1.5">
                        <p class="text-3xl font-extrabold text-slate-900 font-mono"><?= $stat['total_ayat'] ?? 0 ?></p>
                        <span class="text-xs font-semibold text-emerald-600">Ayat</span>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 mt-2">
                        Akumulasi hafalan baru
                    </span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-teal-50 border border-teal-100 text-teal-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="book-plus" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-start justify-between group">
                <div>
                    <span class="text-xs font-semibold text-slate-500">Sesi Muraja'ah</span>
                    <div class="flex items-baseline gap-1.5 mt-1.5">
                        <p class="text-3xl font-extrabold text-slate-900 font-mono"><?= $stat['total_murajaah'] ?? 0 ?></p>
                        <span class="text-xs font-semibold text-sky-600">Sesi</span>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 mt-2">
                        Pengulangan hafalan bulan ini
                    </span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-sky-50 border border-sky-100 text-sky-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="refresh-cw" class="w-6 h-6"></i>
                </div>
            </div>

            <?php if (!in_array($role, ['operator', 'pimpinan'])): ?>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 flex items-start justify-between group">
                <div>
                    <span class="text-xs font-semibold text-slate-500">Ujian Tasmi'</span>
                    <div class="flex items-baseline gap-1.5 mt-1.5">
                        <p class="text-3xl font-extrabold text-slate-900 font-mono"><?= $stat['total_tasmi'] ?? 0 ?></p>
                        <span class="text-xs font-semibold text-purple-600">Ujian</span>
                    </div>
                    <span class="inline-flex items-center gap-1 text-[11px] text-slate-400 mt-2">
                        Sekali duduk bulan ini
                    </span>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-purple-50 border border-purple-100 text-purple-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                    <i data-lucide="award" class="w-6 h-6"></i>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- GRAFIK & PREDIKAT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-7">
            <div class="bg-white border border-slate-200 rounded-2xl p-6 <?= in_array($role, ['operator', 'ustadz', 'pimpinan']) ? 'lg:col-span-2' : 'lg:col-span-3' ?>">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Tren Setoran 6 Bulan Terakhir</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Perbandingan volume Ziyadah (ayat) dan sesi Muraja'ah</p>
                    </div>
                    <div class="flex gap-3 text-xs font-semibold">
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200"><span class="w-2.5 h-2.5 bg-emerald-500 rounded-full"></span>Ziyadah</div>
                        <div class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-200"><span class="w-2.5 h-2.5 bg-blue-500 rounded-full"></span>Muraja'ah</div>
                    </div>
                </div>
                <div style="height: 270px;">
                    <canvas id="progresChart"></canvas>
                </div>
            </div>

            <?php if (in_array($role, ['operator', 'ustadz', 'pimpinan'])): ?>
            <div class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Distribusi Kualitas Bacaan</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Proporsi predikat setoran bulan berjalan</p>
                </div>
                <div style="height: 175px;" class="my-4 flex items-center justify-center">
                    <canvas id="predikatChart"></canvas>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="p-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200/80">
                        <span class="block font-mono font-extrabold text-emerald-700 text-base"><?= $predikat_data['A'] ?></span>
                        <span class="text-[11px] font-semibold text-emerald-600">Mumtaz (A)</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-amber-50/70 border border-amber-200/80">
                        <span class="block font-mono font-extrabold text-amber-700 text-base"><?= $predikat_data['B'] ?></span>
                        <span class="text-[11px] font-semibold text-amber-600">Jayyid (B)</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-rose-50/70 border border-rose-200/80">
                        <span class="block font-mono font-extrabold text-rose-700 text-base"><?= $predikat_data['C'] ?></span>
                        <span class="text-[11px] font-semibold text-rose-600">Ulang (C)</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- WIDGET TARGET & TIMELINE AKTIVITAS -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Realisasi Target -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-start justify-between mb-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center border border-indigo-100">
                                <i data-lucide="target" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900"><?= $target_widget['label'] ?></h3>
                                <p class="text-xs text-slate-500">
                                    <?= $role === 'ortu' ? 'Kalkulasi berdasarkan target harian × 20 hari efektif' : 'Proporsi santri yang memenuhi kuota ayat bulanan' ?>
                                </p>
                            </div>
                        </div>
                        <span class="text-2xl font-mono font-extrabold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-xl border border-indigo-100"><?= $target_widget['persentase'] ?>%</span>
                    </div>

                    <div class="w-full bg-slate-100 rounded-full h-3.5 mb-3 overflow-hidden p-0.5 border border-slate-200/80">
                        <div class="bg-gradient-to-r from-indigo-600 via-violet-600 to-emerald-500 h-full rounded-full transition-all duration-700" style="width: <?= $target_widget['persentase'] ?>%"></div>
                    </div>

                    <div class="flex justify-between text-xs font-medium text-slate-500 mt-3">
                        <span>Tercapai: <strong class="font-mono text-slate-900"><?= $target_widget['tercapai'] ?></strong> <?= $role === 'ortu' ? 'ayat' : 'santri' ?></span>
                        <span>Target: <strong class="font-mono text-slate-900"><?= $target_widget['total'] ?></strong> <?= $role === 'ortu' ? 'ayat' : 'santri' ?></span>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Evaluasi analitik terperinci</span>
                    <a href="<?= $role === 'ortu' ? 'monitoring_anak.php' : 'progres_santri.php' ?>" class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1">
                        Buka Analitik Progres <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </a>
                </div>
            </div>

            <!-- Log Aktivitas Terbaru -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <i data-lucide="activity" class="w-4 h-4"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Aktivitas Setoran Terakhir</h3>
                    </div>
                    <span class="text-[11px] font-mono px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-semibold">Live Feed</span>
                </div>

                <?php if (empty($aktivitas_list)): ?>
                    <div class="text-center py-10 text-slate-400 text-xs">
                        Belum ada data setoran yang tercatat.
                    </div>
                <?php else: ?>
                    <div class="divide-y divide-slate-100">
                        <?php foreach ($aktivitas_list as $akt): 
                            $j_color = $akt['jenis'] === 'ziyadah' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($akt['jenis'] === 'murajaah' ? 'bg-sky-50 text-sky-700 border-sky-200' : 'bg-purple-50 text-purple-700 border-purple-200');
                        ?>
                            <div class="py-3 flex items-center justify-between gap-3 hover:bg-slate-50/80 px-2 rounded-xl transition">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-20 text-center py-1 rounded-lg text-[10px] font-mono font-bold uppercase border shrink-0 <?= $j_color ?>">
                                        <?= htmlspecialchars($akt['jenis']) ?>
                                    </span>
                                    <div class="truncate">
                                        <p class="text-xs font-bold text-slate-900 truncate">
                                            <?= $role === 'ortu' ? 'QS. ' . htmlspecialchars($akt['surat']) : htmlspecialchars($akt['nama_santri']) . ' — QS. ' . htmlspecialchars($akt['surat']) ?>
                                        </p>
                                        <p class="text-[11px] text-slate-400 font-mono">
                                            Ayat <?= $akt['ayat_awal'] ?>–<?= $akt['ayat_akhir'] ?> • <?= date('d/m H:i', strtotime($akt['created_at'])) ?>
                                        </p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-lg font-mono font-bold text-xs border <?= $akt['predikat'] == 'A' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($akt['predikat'] == 'B' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200') ?>">
                                    <?= $akt['predikat'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>

    <?php
    $page_data = [
        'chart_labels' => $chart_labels,
        'chart_ziyadah' => $chart_ziyadah,
        'chart_murajaah' => $chart_murajaah,
        'predikat_data' => in_array($role, ['operator', 'ustadz', 'pimpinan']) ? $predikat_data : null,
    ];
    include __DIR__ . '/../../includes/page_data.php';
    ?>
<?php include __DIR__ . '/../../includes/footer.php'; ?>