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

// 1. Pastikan kolom juz & pojok tersedia di tabel hafalan agar tidak pernah error
$cek_juz = mysqli_query($conn, "SHOW COLUMNS FROM hafalan LIKE 'juz'");
if ($cek_juz && mysqli_num_rows($cek_juz) === 0) {
    mysqli_query($conn, "ALTER TABLE hafalan ADD COLUMN `juz` INT(11) DEFAULT NULL AFTER `jenis`");
    mysqli_query($conn, "ALTER TABLE hafalan ADD COLUMN `pojok` VARCHAR(30) DEFAULT NULL AFTER `juz`");
}

// 2. Filter santri sesuai lokal binaan jika yang login adalah Ustadz/Ustadzah
$where_santri = "";
$tampil_semua = isset($_GET['semua']) && $_GET['semua'] == '1';

if (!$is_pengelola_penuh && !$tampil_semua) {
    $u_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_halaqah, nama_lengkap FROM users WHERE id = $id_ustadz"));
    $id_h_user = (int) ($u_info['id_halaqah'] ?? 0);
    $nm_esc    = mysqli_real_escape_string($conn, trim($u_info['nama_lengkap'] ?? $nama_user));
    
    $q_lokal_ust = mysqli_query($conn, "SELECT id FROM halaqah WHERE id_ustadz = $id_ustadz OR id = $id_h_user OR (nama_pengajar IS NOT NULL AND nama_pengajar != '' AND LOWER(nama_pengajar) = LOWER('$nm_esc'))");
    $lokal_ids = [];
    if ($q_lokal_ust) {
        while ($lk = mysqli_fetch_assoc($q_lokal_ust)) {
            $lokal_ids[] = (int)$lk['id'];
        }
    }

    if (!empty($lokal_ids)) {
        $list_id_str = implode(',', $lokal_ids);
        $where_santri = "WHERE (s.id_halaqah IN ($list_id_str) OR s.id_ustadz = $id_ustadz)";
    } else {
        // Jika ustadz belum ditautkan ke lokal manapun, tampilkan santri yang pernah diinputnya atau semua santri sebagai fallback
        $where_santri = "";
    }
}

$q_santri = "SELECT s.*, h.nama_halaqah, h.keterangan as ket_lokal 
             FROM santri s 
             LEFT JOIN halaqah h ON s.id_halaqah = h.id 
             $where_santri 
             ORDER BY s.nama ASC";
$res_santri  = mysqli_query($conn, $q_santri);
$santri_list = $res_santri ? mysqli_fetch_all($res_santri, MYSQLI_ASSOC) : [];

// 3. Perbaikan Pemilihan Santri Aktif (Selalu Fallback ke Santri Pertama jika ID tidak cocok/kosong)
$id_santri_pilih = isset($_GET['id_santri']) ? (int) $_GET['id_santri'] : 0;
$santri_aktif = null;

if (!empty($santri_list)) {
    foreach ($santri_list as $s) {
        if ((int)$s['id'] === $id_santri_pilih) {
            $santri_aktif = $s;
            break;
        }
    }
    // Jika ID di URL kosong atau tidak ditemukan di daftar, otomatis pilih santri urutan pertama!
    if (!$santri_aktif) {
        $santri_aktif = $santri_list[0];
        $id_santri_pilih = (int) $santri_aktif['id'];
    }
}

$riwayat_hafalan = [];
$chart_labels    = [];
$chart_ziyadah   = [];
$stat            = ['ziyadah' => 0, 'murajaah' => 0, 'tasmi' => 0];
$target_aktif    = null;

if ($santri_aktif) {
    $id_s = (int) $santri_aktif['id'];
    
    // Statistik Total Santri Ini
    $q_st = mysqli_query($conn, "SELECT jenis, COUNT(*) as jml, COALESCE(SUM(jumlah_ayat), 0) as tot_ayat FROM hafalan WHERE id_santri = $id_s GROUP BY jenis");
    if ($q_st) {
        while ($st = mysqli_fetch_assoc($q_st)) {
            if ($st['jenis'] === 'ziyadah')  $stat['ziyadah']  = (int)$st['tot_ayat'];
            if ($st['jenis'] === 'murajaah') $stat['murajaah'] = (int)$st['jml'];
            if ($st['jenis'] === 'tasmi')    $stat['tasmi']    = (int)$st['jml'];
        }
    }

    // Target Hafalan Santri Ini
    $q_tgt = mysqli_query($conn, "SELECT * FROM target_hafalan WHERE id_santri = $id_s ORDER BY id DESC LIMIT 1");
    if ($q_tgt && mysqli_num_rows($q_tgt) > 0) {
        $target_aktif = mysqli_fetch_assoc($q_tgt);
    }

    // Riwayat Lengkap Setoran
    $q_riw = mysqli_query($conn, "SELECT h.*, u.nama_lengkap as nama_ustadz FROM hafalan h LEFT JOIN users u ON h.id_ustadz = u.id WHERE h.id_santri = $id_s ORDER BY h.tanggal DESC, h.id DESC");
    if ($q_riw) {
        $riwayat_hafalan = mysqli_fetch_all($q_riw, MYSQLI_ASSOC);
    }

    // Grafik 6 Bulan Terakhir
    for ($i = 5; $i >= 0; $i--) {
        $bulan = date('Y-m', strtotime("-$i months"));
        $chart_labels[] = date('M Y', strtotime("-$i months"));
        $q_z = mysqli_query($conn, "SELECT COALESCE(SUM(jumlah_ayat), 0) as z FROM hafalan WHERE id_santri = $id_s AND jenis = 'ziyadah' AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'");
        $chart_ziyadah[] = $q_z ? (int) mysqli_fetch_assoc($q_z)['z'] : 0;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Progres Santri - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- Header & Filter Santri -->
        <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
                    <div class="p-2.5 bg-sky-100 rounded-xl"><i data-lucide="line-chart" class="w-6 h-6 text-sky-600"></i></div>
                    Analisis Progres Santri
                </h1>
                <p class="text-sm text-slate-500 mt-1">Pantau grafik pertumbuhan hafalan, rekam jejak juz, dan riwayat setoran santri.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <?php if (!$is_pengelola_penuh): ?>
                    <a href="progres_santri.php?semua=<?= $tampil_semua ? '0' : '1' ?>" 
                       class="px-3.5 py-2.5 rounded-xl border text-xs font-semibold transition <?= $tampil_semua ? 'bg-indigo-50 border-indigo-200 text-indigo-700' : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50' ?>">
                        <?= $tampil_semua ? 'Filter Lokal Binaan Saya' : 'Tampilkan Semua Santri' ?>
                    </a>
                <?php endif; ?>

                <form method="GET" class="bg-white border border-slate-200 px-4 py-2 rounded-2xl shadow-sm flex items-center gap-3">
                    <?php if ($tampil_semua): ?>
                        <input type="hidden" name="semua" value="1">
                    <?php endif; ?>
                    <label class="text-xs font-bold text-slate-400 uppercase">Pilih Santri:</label>
                    <select name="id_santri" onchange="this.form.submit()" class="text-xs font-bold text-slate-800 outline-none cursor-pointer">
                        <?php foreach ($santri_list as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (int)$s['id'] === $id_santri_pilih ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nama']) ?> <?= !empty($s['nama_halaqah']) ? '[' . htmlspecialchars($s['nama_halaqah']) . ']' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </div>
        </div>

        <?php if (!$santri_aktif): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-800 p-6 rounded-2xl flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600"></i>
                    <span class="text-sm font-medium">Tidak ada data santri ditemukan di lokal binaan Anda.</span>
                </div>
                <a href="progres_santri.php?semua=1" class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition">
                    Lihat Seluruh Santri Surau
                </a>
            </div>
        <?php else: ?>

        <!-- BANNER PROFIL SANTRI AKTIF & PINTASAN CEPAT -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-6 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 rounded-2xl <?= $santri_aktif['jenis_kelamin'] === 'L' ? 'bg-blue-600' : 'bg-pink-600' ?> text-white font-extrabold text-xl flex items-center justify-center shrink-0 shadow-sm">
                    <?= strtoupper(substr($santri_aktif['nama'], 0, 1)) ?>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-extrabold text-slate-900"><?= htmlspecialchars($santri_aktif['nama']) ?></h2>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                            <?= htmlspecialchars($santri_aktif['nama_halaqah'] ?: 'Belum Ada Lokal') ?>
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-1">
                        NIS: <b><?= htmlspecialchars($santri_aktif['nis'] ?: '-') ?></b> • 
                        Wali: <b><?= htmlspecialchars($santri_aktif['nama_ortu'] ?: '-') ?></b>
                        <?php if ($target_aktif): ?>
                            • Target Aktif: <b class="text-teal-600"><?= htmlspecialchars($target_aktif['target_surat'] ?: $target_aktif['target_ayat_per_hari'] . ' Ayat/Hari') ?></b>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="input_ziyadah.php" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-4 py-2 rounded-xl transition flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="book-plus" class="w-4 h-4"></i> Input Ziyadah
                </a>
                <a href="rapor_santri.php?id_santri=<?= $santri_aktif['id'] ?>" class="bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold px-4 py-2 rounded-xl transition flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="printer" class="w-4 h-4"></i> Lihat Rapor
                </a>
            </div>
        </div>

        <!-- KARTU STATISTIK -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Total Ziyadah</p>
                    <p class="text-3xl font-black text-slate-900 mt-1"><?= $stat['ziyadah'] ?> <span class="text-sm font-normal text-slate-500">Ayat</span></p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Akumulasi ayat baru dihafal</p>
                </div>
                <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center"><i data-lucide="book-plus" class="w-6 h-6"></i></div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Total Muraja'ah</p>
                    <p class="text-3xl font-black text-slate-900 mt-1"><?= $stat['murajaah'] ?> <span class="text-sm font-normal text-slate-500">Sesi</span></p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Frekuensi pengulangan hafalan</p>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center"><i data-lucide="refresh-cw" class="w-6 h-6"></i></div>
            </div>
            <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-purple-600 uppercase tracking-wider">Total Tasmi'</p>
                    <p class="text-3xl font-black text-slate-900 mt-1"><?= $stat['tasmi'] ?> <span class="text-sm font-normal text-slate-500">Ujian</span></p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Sesi ujian sekali duduk</p>
                </div>
                <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-2xl flex items-center justify-center"><i data-lucide="award" class="w-6 h-6"></i></div>
            </div>
        </div>

        <!-- GRAFIK TREN -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm mb-6">
            <h2 class="text-sm font-bold text-slate-900 mb-1">Grafik Pertumbuhan Ziyadah (6 Bulan Terakhir)</h2>
            <p class="text-xs text-slate-500 mb-5">Jumlah ayat baru yang disetorkan oleh <?= htmlspecialchars($santri_aktif['nama']) ?> setiap bulan</p>
            <div style="height: 260px;">
                <canvas id="grafikProgres"></canvas>
            </div>
        </div>

        <!-- TABEL RIWAYAT LENGKAP -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Riwayat Setoran & Ujian Lengkap</h3>
                    <p class="text-xs text-slate-500">Total <?= count($riwayat_hafalan) ?> aktivitas tercatat</p>
                </div>
                <select id="filterJenisProgres" onchange="filterTabelProgres()" class="px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 outline-none cursor-pointer">
                    <option value="ALL">Semua Jenis (Ziyadah, Muraja'ah, Tasmi')</option>
                    <option value="ziyadah">Ziyadah Saja</option>
                    <option value="murajaah">Muraja'ah Saja</option>
                    <option value="tasmi">Tasmi' Saja</option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/70 text-slate-500 text-[11px] font-semibold uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-6">Tanggal</th>
                            <th class="py-3.5 px-4">Jenis</th>
                            <th class="py-3.5 px-4">Juz & Pojok</th>
                            <th class="py-3.5 px-4">Surat & Ayat</th>
                            <th class="py-3.5 px-4 text-center">Jml Ayat</th>
                            <th class="py-3.5 px-4 text-center">Predikat</th>
                            <th class="py-3.5 px-4">Catatan Evaluasi</th>
                            <th class="py-3.5 px-6">Penguji</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs divide-y divide-slate-100">
                        <?php if (empty($riwayat_hafalan)): ?>
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                    <p class="font-medium text-slate-600">Belum ada riwayat setoran hafalan untuk santri ini.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_hafalan as $r): 
                                $badge = $r['jenis'] === 'ziyadah' ? 'bg-emerald-100 text-emerald-700' : ($r['jenis'] === 'murajaah' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700');
                                $p_bg  = $r['predikat'] === 'A' ? 'bg-emerald-500 text-white' : ($r['predikat'] === 'B' ? 'bg-amber-500 text-white' : 'bg-rose-500 text-white');
                            ?>
                            <tr class="baris-progres hover:bg-slate-50 transition" data-jenis="<?= htmlspecialchars($r['jenis']) ?>">
                                <td class="py-3.5 px-6 font-medium text-slate-600"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                                <td class="py-3.5 px-4"><span class="px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase <?= $badge ?>"><?= htmlspecialchars($r['jenis']) ?></span></td>
                                <td class="py-3.5 px-4">
                                    <?php if (!empty($r['juz'])): ?>
                                        <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold">Juz <?= (int)$r['juz'] ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($r['pojok'])): ?>
                                        <span class="text-slate-600 ml-1 font-medium"><?= htmlspecialchars($r['pojok']) ?></span>
                                    <?php endif; ?>
                                    <?php if (empty($r['juz']) && empty($r['pojok'])): ?>
                                        <span class="text-slate-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-800">
                                    <?= htmlspecialchars($r['surat']) ?> 
                                    <?php if ($r['jenis'] !== 'tasmi'): ?>
                                        <span class="font-normal text-slate-500">: Ayat <?= $r['ayat_awal'] ?>–<?= $r['ayat_akhir'] ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 text-center font-semibold text-slate-700">
                                    <?= $r['jenis'] === 'tasmi' ? '1 Sesi' : ((int)$r['jumlah_ayat'] . ' Ayat') ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg font-bold <?= $p_bg ?>"><?= htmlspecialchars($r['predikat']) ?></span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 italic"><?= htmlspecialchars($r['catatan'] ?: '-') ?></td>
                                <td class="py-3.5 px-6 text-slate-500 font-medium"><?= htmlspecialchars($r['nama_ustadz'] ?? 'Ustadz/ah') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>
    </div>

    <script>
        lucide.createIcons();

        function filterTabelProgres() {
            const jenis = document.getElementById('filterJenisProgres').value;
            document.querySelectorAll('.baris-progres').forEach(tr => {
                tr.style.display = (jenis === 'ALL' || tr.dataset.jenis === jenis) ? '' : 'none';
            });
        }

        <?php if ($santri_aktif): ?>
        const ctx = document.getElementById('grafikProgres').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Ziyadah (Ayat)',
                    data: <?= json_encode($chart_ziyadah) ?>,
                    borderColor: '#0284c7',
                    backgroundColor: 'rgba(2, 132, 199, 0.1)',
                    borderWidth: 2.5,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#0284c7'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                    x: { grid: { display: false } }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>