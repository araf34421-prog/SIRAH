<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$nama = $_SESSION['nama_lengkap'];
$role = $_SESSION['role'];

// ============================================
// VARIABEL DEFAULT
// ============================================
$chart_labels = [];
$chart_ziyadah = [];
$chart_murajaah = [];
$stat = ['total_ayat' => 0, 'total_murajaah' => 0, 'total_tasmi' => 0, 'santri_aktif' => 0];
$total_santri = 0;
$total_ustadz = 0;
$predikat_data = ['A' => 0, 'B' => 0, 'C' => 0];
$aktivitas_list = [];

// Widget Capaian vs Target
$target_widget = ['total' => 0, 'tercapai' => 0, 'persentase' => 0, 'label' => ''];

// ============================================
// LOGIKA DATA DASHBOARD
// ============================================
if (in_array($role, ['ustadz', 'pimpinan'])) {
    
    // 1. Statistik Global
    $total_santri_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM santri");
    $total_santri = mysqli_fetch_assoc($total_santri_res)['total'];

    $total_ustadz_res = mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'ustadz'");
    $total_ustadz = mysqli_fetch_assoc($total_ustadz_res)['total'];

    $bulan_ini = date('Y-m');
    $stat_query = "SELECT 
                    COUNT(DISTINCT id_santri) as santri_aktif,
                    COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as total_ayat,
                    COALESCE(SUM(CASE WHEN jenis = 'murajaah' THEN 1 ELSE 0 END), 0) as total_murajaah,
                    COALESCE(SUM(CASE WHEN jenis = 'tasmi' THEN 1 ELSE 0 END), 0) as total_tasmi
                  FROM hafalan 
                  WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
    $stat_result = mysqli_query($conn, $stat_query);
    $stat = mysqli_fetch_assoc($stat_result);

    // 2. Grafik Tren 6 Bulan
    for ($i = 5; $i >= 0; $i--) {
        $bulan = date('Y-m', strtotime("-$i months"));
        $chart_labels[] = date('M Y', strtotime("-$i months"));
        $query = "SELECT 
                    COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as z,
                    COALESCE(SUM(CASE WHEN jenis = 'murajaah' THEN 1 ELSE 0 END), 0) as m
                  FROM hafalan WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'";
        $result = mysqli_query($conn, $query);
        $data = mysqli_fetch_assoc($result);
        $chart_ziyadah[] = (int) $data['z'];
        $chart_murajaah[] = (int) $data['m'];
    }

    // 3. Distribusi Predikat Bulan Ini (Untuk Grafik Donat)
    $predikat_query = "SELECT predikat, COUNT(*) as jumlah 
                       FROM hafalan 
                       WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini' 
                       GROUP BY predikat";
    $predikat_result = mysqli_query($conn, $predikat_query);
    while ($row = mysqli_fetch_assoc($predikat_result)) {
        $predikat_data[$row['predikat']] = (int) $row['jumlah'];
    }

    // 4. Widget Capaian vs Target
    // Hitung santri yang total ziyadah bulan ini >= (target_ayat_per_hari * 20 hari kerja)
    $target_widget['label'] = $role === 'pimpinan' ? 'Santri Mencapai Target Bulanan' : 'Santri Binaan Mencapai Target';
    
    if ($role === 'pimpinan') {
        // Semua santri di surau
        $target_widget['total'] = $total_santri;
        $capaian_query = "SELECT COUNT(DISTINCT s.id) as tercapai
                          FROM santri s
                          LEFT JOIN target_hafalan t ON s.id = t.id_santri
                          LEFT JOIN (
                              SELECT id_santri, SUM(jumlah_ayat) as total_ayat
                              FROM hafalan 
                              WHERE jenis = 'ziyadah' AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'
                              GROUP BY id_santri
                          ) h ON s.id = h.id_santri
                          WHERE (t.target_ayat_per_hari * 20) <= COALESCE(h.total_ayat, 0)";
    } else {
        // Khusus ustadz: tampilkan jumlah santri binaan (asumsi 8-10 per ustadz)
        // Karena belum ada tabel halaqah yang terisi, kita pakai total santri yang pernah diinput ustadz ini
        // Untuk sementara, kita pakai data global dengan catatan
        $target_widget['total'] = min($total_santri, 10); // Asumsi ustadz membina ~10 santri
        $capaian_query = "SELECT COUNT(DISTINCT s.id) as tercapai
                          FROM santri s
                          LEFT JOIN target_hafalan t ON s.id = t.id_santri
                          LEFT JOIN (
                              SELECT id_santri, SUM(jumlah_ayat) as total_ayat
                              FROM hafalan 
                              WHERE jenis = 'ziyadah' AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'
                              GROUP BY id_santri
                          ) h ON s.id = h.id_santri
                          WHERE (t.target_ayat_per_hari * 20) <= COALESCE(h.total_ayat, 0)
                          LIMIT 10";
    }
    $capaian_result = mysqli_query($conn, $capaian_query);
    $target_widget['tercapai'] = (int) mysqli_fetch_assoc($capaian_result)['tercapai'];
    $target_widget['persentase'] = $target_widget['total'] > 0 
        ? round(($target_widget['tercapai'] / $target_widget['total']) * 100) 
        : 0;

    // 5. Aktivitas Terbaru (5 terakhir)
    $aktivitas_query = "SELECT h.*, s.nama as nama_santri, u.nama_lengkap as nama_ustadz
                        FROM hafalan h
                        JOIN santri s ON h.id_santri = s.id
                        LEFT JOIN users u ON h.id_ustadz = u.id
                        ORDER BY h.created_at DESC LIMIT 5";
    $aktivitas_result = mysqli_query($conn, $aktivitas_query);
    $aktivitas_list = mysqli_fetch_all($aktivitas_result, MYSQLI_ASSOC);

} elseif ($role === 'ortu') {
    // Logika untuk Ortu
    $id_ortu = (int) $_SESSION['user_id'];
    $santri_query = "SELECT * FROM santri WHERE id_user_ortu = $id_ortu LIMIT 1";
    $santri_result = mysqli_query($conn, $santri_query);
    $santri = mysqli_fetch_assoc($santri_result);

    if ($santri) {
        $id_santri = $santri['id'];
        $bulan_ini = date('Y-m');

        $stat_query = "SELECT 
                        COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as total_ayat,
                        COALESCE(SUM(CASE WHEN jenis = 'murajaah' THEN 1 ELSE 0 END), 0) as total_murajaah,
                        COALESCE(SUM(CASE WHEN jenis = 'tasmi' THEN 1 ELSE 0 END), 0) as total_tasmi
                      FROM hafalan 
                      WHERE id_santri = $id_santri AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
        $stat_result = mysqli_query($conn, $stat_query);
        $stat = mysqli_fetch_assoc($stat_result);

        for ($i = 5; $i >= 0; $i--) {
            $bulan = date('Y-m', strtotime("-$i months"));
            $chart_labels[] = date('M Y', strtotime("-$i months"));
            $query = "SELECT COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as z FROM hafalan WHERE id_santri = $id_santri AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'";
            $result = mysqli_query($conn, $query);
            $chart_ziyadah[] = (int) mysqli_fetch_assoc($result)['z'];
        }

        // Target anak
        $target_query = "SELECT target_ayat_per_hari FROM target_hafalan WHERE id_santri = $id_santri ORDER BY id DESC LIMIT 1";
        $target_result = mysqli_query($conn, $target_query);
        $target_anak = mysqli_fetch_assoc($target_result);
        $target_ayat_harian = $target_anak ? (int) $target_anak['target_ayat_per_hari'] : 0;
        
        // Widget capaian untuk ortu
        $target_widget['label'] = 'Capaian Target Ananda Bulan Ini';
        $target_widget['total'] = $target_ayat_harian * 20; // Asumsi 20 hari kerja
        $target_widget['tercapai'] = (int) $stat['total_ayat'];
        $target_widget['persentase'] = $target_widget['total'] > 0 
            ? min(100, round(($target_widget['tercapai'] / $target_widget['total']) * 100)) 
            : 0;

        // Aktivitas anak
        $aktivitas_query = "SELECT h.*, u.nama_lengkap as nama_ustadz
                            FROM hafalan h
                            LEFT JOIN users u ON h.id_ustadz = u.id
                            WHERE h.id_santri = $id_santri
                            ORDER BY h.created_at DESC LIMIT 5";
        $aktivitas_result = mysqli_query($conn, $aktivitas_query);
        $aktivitas_list = mysqli_fetch_all($aktivitas_result, MYSQLI_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Dashboard <?= ucfirst($role) ?></h1>
                <p class="text-sm text-slate-500 mt-1">Selamat datang, <?= htmlspecialchars($nama) ?>. Berikut ringkasan sistem hari ini.</p>
            </div>
            <div class="bg-white border border-slate-200 px-4 py-2 rounded-lg shadow-sm text-sm text-slate-600 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <?= date('d F Y') ?>
            </div>
        </div>

        <?php if ($role === 'ortu' && isset($santri)): ?>
            <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6 shadow-sm">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-indigo-50 rounded-full flex items-center justify-center">
                        <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900"><?= htmlspecialchars($santri['nama']) ?></h2>
                        <p class="text-sm text-slate-500">NIS: <?= htmlspecialchars($santri['nis']) ?> • <?= $santri['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- KARTU STATISTIK -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            
            <?php if ($role === 'pimpinan'): ?>
                <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Ustadz</p>
                        <div class="w-8 h-8 bg-indigo-50 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-slate-900"><?= $total_ustadz ?></p>
                    <p class="text-xs text-slate-500 mt-1">Pengajar aktif</p>
                </div>
            <?php endif; ?>

            <?php if (in_array($role, ['ustadz', 'pimpinan'])): ?>
                <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Santri</p>
                        <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-slate-900"><?= $total_santri ?></p>
                    <p class="text-xs text-slate-500 mt-1">Santri terdaftar</p>
                </div>
            <?php endif; ?>

            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ziyadah Bulan Ini</p>
                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    </div>
                </div>
                <p class="text-3xl font-bold text-slate-900"><?= $stat['total_ayat'] ?? 0 ?></p>
                <p class="text-xs text-slate-500 mt-1">Total ayat baru</p>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Muraja'ah & Tasmi'</p>
                    <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div>
                        <p class="text-xl font-bold text-slate-900"><?= $stat['total_murajaah'] ?? 0 ?></p>
                        <p class="text-xs text-slate-500">Muraja'ah</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-slate-900"><?= $stat['total_tasmi'] ?? 0 ?></p>
                        <p class="text-xs text-slate-500">Tasmi'</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRAFIK TREN + GRAFIK DONAT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            
            <!-- Grafik Tren (Line Chart) - 2/3 lebar -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            <?= $role === 'ortu' ? 'Tren Hafalan Ananda' : 'Tren Progres Hafalan Surau' ?>
                        </h2>
                        <p class="text-sm text-slate-500">Data 6 bulan terakhir</p>
                    </div>
                    <div class="flex gap-4 text-xs">
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-emerald-500 rounded-full"></div>
                            <span class="text-slate-600">Ziyadah (Ayat)</span>
                        </div>
                        <?php if ($role !== 'ortu'): ?>
                        <div class="flex items-center gap-2">
                            <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                            <span class="text-slate-600">Muraja'ah (Kali)</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="height: 300px;">
                    <canvas id="progresChart"></canvas>
                </div>
            </div>

            <!-- Grafik Donat (Doughnut Chart) - 1/3 lebar -->
            <?php if (in_array($role, ['ustadz', 'pimpinan'])): ?>
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                <div class="mb-4">
                    <h2 class="text-lg font-semibold text-slate-900">Distribusi Predikat</h2>
                    <p class="text-sm text-slate-500">Kualitas hafalan bulan ini</p>
                </div>
                <div style="height: 200px;" class="flex items-center justify-center">
                    <canvas id="predikatChart"></canvas>
                </div>
                <div class="flex justify-center gap-4 mt-4 text-xs">
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 bg-emerald-500 rounded-full"></div>
                        <span class="text-slate-600">A (<?= $predikat_data['A'] ?>)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 bg-amber-500 rounded-full"></div>
                        <span class="text-slate-600">B (<?= $predikat_data['B'] ?>)</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <span class="text-slate-600">C (<?= $predikat_data['C'] ?>)</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- WIDGET CAPAIAN VS TARGET + AKTIVITAS TERBARU -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            
            <!-- Widget Capaian vs Target -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-slate-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <?= $target_widget['label'] ?>
                        </h3>
                        <p class="text-sm text-slate-500 mt-1">
                            <?php if ($role === 'pimpinan'): ?>
                                Berdasarkan target individual masing-masing santri (asumsi 20 hari kerja)
                            <?php elseif ($role === 'ustadz'): ?>
                                Dari santri binaan Anda (8-10 santri per ustadz)
                            <?php else: ?>
                                Target: <?= ($target_widget['total'] > 0 ? $target_widget['total'] : 0) ?> ayat bulan ini
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-3xl font-bold text-indigo-600"><?= $target_widget['persentase'] ?>%</p>
                        <p class="text-xs text-slate-500">Tercapai</p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="w-full bg-slate-100 rounded-full h-3 mb-3">
                    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 h-3 rounded-full transition-all duration-500" 
                         style="width: <?= $target_widget['persentase'] ?>%"></div>
                </div>

                <div class="flex justify-between text-sm">
                    <div class="text-slate-600">
                        <span class="font-semibold text-slate-900"><?= $target_widget['tercapai'] ?></span> santri tercapai
                    </div>
                    <div class="text-slate-500">
                        dari <span class="font-semibold text-slate-900"><?= $target_widget['total'] ?></span> total
                    </div>
                </div>

                <?php if ($role === 'pimpinan'): ?>
                <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <p class="text-slate-500">Rata-rata Santri/Ustadz</p>
                        <p class="text-lg font-bold text-slate-900"><?= $total_ustadz > 0 ? round($total_santri / $total_ustadz) : 0 ?></p>
                    </div>
                    <div>
                        <p class="text-slate-500">Total Halaqah Aktif</p>
                        <p class="text-lg font-bold text-slate-900"><?= $total_ustadz ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Widget Aktivitas Terbaru -->
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="font-semibold text-slate-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Aktivitas Terbaru
                        </h3>
                        <p class="text-sm text-slate-500 mt-1">
                            <?= $role === 'ortu' ? 'Riwayat setoran ananda' : 'Aktivitas pencatatan di surau' ?>
                        </p>
                    </div>
                </div>

                <?php if (empty($aktivitas_list)): ?>
                    <div class="text-center py-8 text-slate-400 text-sm">
                        Belum ada aktivitas tercatat.
                    </div>
                <?php else: ?>
                    <ul class="space-y-3">
                        <?php foreach ($aktivitas_list as $akt): 
                            $warna_dot = $akt['jenis'] == 'ziyadah' ? 'bg-emerald-500' : ($akt['jenis'] == 'murajaah' ? 'bg-blue-500' : 'bg-purple-500');
                            $label_jenis = strtoupper($akt['jenis']);
                            $waktu = strtotime($akt['created_at']);
                            $selisih = time() - $waktu;
                            if ($selisih < 60) $waktu_str = 'Baru saja';
                            elseif ($selisih < 3600) $waktu_str = floor($selisih/60) . ' menit lalu';
                            elseif ($selisih < 86400) $waktu_str = floor($selisih/3600) . ' jam lalu';
                            else $waktu_str = floor($selisih/86400) . ' hari lalu';
                        ?>
                            <li class="flex items-start gap-3 pb-3 border-b border-slate-100 last:border-0 last:pb-0">
                                <div class="w-2 h-2 mt-2 <?= $warna_dot ?> rounded-full flex-shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-slate-800 truncate">
                                        <?php if ($role === 'ortu'): ?>
                                            Setoran <b><?= $label_jenis ?></b> - <?= htmlspecialchars($akt['surat']) ?>
                                            <?php if ($akt['ayat_awal'] > 0): ?>
                                                <span class="text-slate-500">(Ayat <?= $akt['ayat_awal'] ?>-<?= $akt['ayat_akhir'] ?>)</span>
                                            <?php endif; ?>
                                            <span class="font-semibold <?= $akt['predikat'] == 'A' ? 'text-emerald-600' : ($akt['predikat'] == 'B' ? 'text-amber-600' : 'text-red-600') ?>">[<?= $akt['predikat'] ?>]</span>
                                        <?php else: ?>
                                            <b><?= htmlspecialchars($akt['nama_ustadz'] ?? 'Ustadz') ?></b> menginput <b><?= $label_jenis ?></b> untuk <b><?= htmlspecialchars($akt['nama_santri']) ?></b>
                                        <?php endif; ?>
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5"><?= $waktu_str ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

        </div>

        <!-- INFO / AKSI CEPAT -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php if ($role === 'pimpinan'): ?>
                <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Ringkasan Lembaga
                    </h3>
                    <p class="text-sm text-slate-600 mb-4">Surau Tahfizh Firdaus menaungi <?= $total_santri ?> santri yang terbagi ke dalam <?= $total_ustadz ?> halaqah. Pantau perkembangan keseluruhan program melalui laporan bulanan.</p>
                    <a href="rekap_bulanan.php" class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                        Lihat Laporan Bulanan
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        Manajemen Pengajar
                    </h3>
                    <p class="text-sm text-slate-600 mb-4">Kelola akun ustadz/ustadzah yang memiliki akses untuk menginput data santri dan hafalan.</p>
                    <a href="kelola-ustadz.php" class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                        Kelola Ustadz
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            <?php elseif ($role === 'ustadz'): ?>
                <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Panduan Cepat
                    </h3>
                    <ul class="space-y-2 text-sm text-slate-600">
                        <li class="flex items-start gap-2"><span class="text-indigo-600 mt-1">•</span><span>Gunakan menu <b>Input Ziyadah</b> untuk mencatat hafalan baru santri binaan Anda.</span></li>
                        <li class="flex items-start gap-2"><span class="text-indigo-600 mt-1">•</span><span>Atur <b>Target Hafalan</b> untuk program individual masing-masing santri.</span></li>
                        <li class="flex items-start gap-2"><span class="text-indigo-600 mt-1">•</span><span>Pantau kualitas melalui <b>Distribusi Predikat</b> di atas.</span></li>
                    </ul>
                </div>
                <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                    <h3 class="font-semibold text-slate-900 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Laporan
                    </h3>
                    <p class="text-sm text-slate-600 mb-4">Unduh rekapitulasi bulanan dalam format PDF untuk arsip atau laporan ke pimpinan.</p>
                    <a href="rekap_bulanan.php" class="inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700 font-medium">
                        Buka Rekap Bulanan
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            <?php else: ?>
                <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm md:col-span-2">
                    <h3 class="font-semibold text-slate-900 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Informasi untuk Orang Tua
                    </h3>
                    <p class="text-sm text-slate-600">Grafik di atas menunjukkan perkembangan hafalan ananda dalam 6 bulan terakhir. Widget <b>Capaian Target</b> menunjukkan persentase pencapaian ananda terhadap target yang ditetapkan ustadz. Untuk melihat detail per sesi setoran, silakan kunjungi menu <b>Progres Anak</b> di sidebar.</p>
                </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- Script Chart.js -->
    <script>
        // 1. Grafik Tren (Line Chart)
        const ctxTren = document.getElementById('progresChart').getContext('2d');
        const datasetsTren = [
            {
                label: 'Ziyadah (Ayat)',
                data: <?= json_encode($chart_ziyadah) ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointBackgroundColor: '#10b981'
            }
        ];

        <?php if ($role !== 'ortu'): ?>
        datasetsTren.push({
            label: "Muraja'ah (Kali)",
            data: <?= json_encode($chart_murajaah) ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            borderWidth: 2,
            tension: 0.4,
            fill: true,
            pointRadius: 4,
            pointBackgroundColor: '#3b82f6'
        });
        <?php endif; ?>

        new Chart(ctxTren, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: datasetsTren
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'Inter', size: 13 },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 12,
                        cornerRadius: 8
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: { font: { family: 'Inter', size: 11 }, color: '#64748b' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Inter', size: 11 }, color: '#64748b' }
                    }
                }
            }
        });

        // 2. Grafik Donat (Doughnut Chart) - Hanya untuk Ustadz & Pimpinan
        <?php if (in_array($role, ['ustadz', 'pimpinan'])): ?>
        const ctxDonat = document.getElementById('predikatChart').getContext('2d');
        new Chart(ctxDonat, {
            type: 'doughnut',
            data: {
                labels: ['Predikat A', 'Predikat B', 'Predikat C'],
                datasets: [{
                    data: [<?= $predikat_data['A'] ?>, <?= $predikat_data['B'] ?>, <?= $predikat_data['C'] ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: { 
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e293b',
                        titleFont: { family: 'Inter', size: 13 },
                        bodyFont: { family: 'Inter', size: 12 },
                        padding: 12,
                        cornerRadius: 8
                    }
                }
            }
        });
        <?php endif; ?>
    </script>

</body>
</html>