<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ortu') {
    header("Location: login.php");
    exit;
}

$id_ortu = (int) $_SESSION['user_id'];

$santri_query = "SELECT * FROM santri WHERE id_user_ortu = $id_ortu LIMIT 1";
$santri_result = mysqli_query($conn, $santri_query);
$santri = mysqli_fetch_assoc($santri_result);

$target_ayat = 0;
$target_ket = "Belum ditentukan";
$riwayat_list = [];
$chart_labels = [];
$chart_ziyadah = [];

if ($santri) {
    $id_santri = $santri['id'];
    $bulan_ini = date('Y-m');

    // Target
    $target_query = "SELECT target_ayat_per_hari, keterangan FROM target_hafalan WHERE id_santri = $id_santri ORDER BY id DESC LIMIT 1";
    $target_result = mysqli_query($conn, $target_query);
    if ($target_result && $target_data = mysqli_fetch_assoc($target_result)) {
        $target_ayat = $target_data['target_ayat_per_hari'];
        $target_ket = $target_data['keterangan'];
    }

    // Statistik bulan ini
    $stat_query = "SELECT 
                    COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as total_ziyadah,
                    COALESCE(SUM(CASE WHEN jenis = 'murajaah' THEN 1 ELSE 0 END), 0) as total_murajaah,
                    COALESCE(SUM(CASE WHEN jenis = 'tasmi' THEN 1 ELSE 0 END), 0) as total_tasmi
                   FROM hafalan 
                   WHERE id_santri = $id_santri AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
    $stat_result = mysqli_query($conn, $stat_query);
    $stat_data = mysqli_fetch_assoc($stat_result);

    // Grafik 6 bulan terakhir
    for ($i = 5; $i >= 0; $i--) {
        $bulan = date('Y-m', strtotime("-$i months"));
        $nama_bulan = date('M Y', strtotime("-$i months"));
        $chart_labels[] = $nama_bulan;

        $query = "SELECT COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as z
                  FROM hafalan 
                  WHERE id_santri = $id_santri AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'";
        $result = mysqli_query($conn, $query);
        $data = mysqli_fetch_assoc($result);
        $chart_ziyadah[] = (int) $data['z'];
    }

    // Riwayat 10 terakhir
    $riwayat_query = "SELECT * FROM hafalan WHERE id_santri = $id_santri ORDER BY tanggal DESC, id DESC LIMIT 10";
    $riwayat_result = mysqli_query($conn, $riwayat_query);
    $riwayat_list = mysqli_fetch_all($riwayat_result, MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitoring Progres Anak - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 font-sans">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <?php if (!$santri): ?>
            <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-6 rounded-lg">
                <h3 class="font-semibold text-lg mb-2">Data Anak Tidak Ditemukan</h3>
                <p class="text-sm">Akun orang tua Anda belum terhubung dengan data santri. Silakan hubungi Ustadz/Ustadzah.</p>
            </div>
        <?php else: ?>

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Monitoring Progres Ananda</h1>
            <p class="text-sm text-slate-500 mt-1">Pantau perkembangan hafalan <b><?= htmlspecialchars($santri['nama']) ?></b> secara langsung.</p>
        </div>

        <!-- Info Anak Card -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h2 class="text-xl font-semibold text-slate-800"><?= htmlspecialchars($santri['nama']) ?></h2>
                    <p class="text-sm text-slate-500">NIS: <?= htmlspecialchars($santri['nis']) ?> • <?= $santri['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-slate-500 uppercase tracking-wider">Target Harian</p>
                    <p class="text-2xl font-bold text-indigo-600"><?= $target_ayat ?> <span class="text-sm font-normal text-slate-500">ayat</span></p>
                    <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($target_ket) ?></p>
                </div>
            </div>
        </div>

        <!-- Statistik Bulan Ini -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Ziyadah Bulan Ini</p>
                <p class="text-3xl font-bold text-slate-800"><?= $stat_data['total_ziyadah'] ?></p>
                <p class="text-xs text-slate-500 mt-1">Total ayat baru</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Muraja'ah Bulan Ini</p>
                <p class="text-3xl font-bold text-slate-800"><?= $stat_data['total_murajaah'] ?></p>
                <p class="text-xs text-slate-500 mt-1"> Kali pengulangan</p>
            </div>
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Tasmi' Bulan Ini</p>
                <p class="text-3xl font-bold text-slate-800"><?= $stat_data['total_tasmi'] ?></p>
                <p class="text-xs text-slate-500 mt-1"> Kali ujian juz</p>
            </div>
        </div>

        <!-- GRAFIK PROGRES ANAK -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-slate-800">Tren Hafalan 6 Bulan Terakhir</h2>
                <p class="text-sm text-slate-500">Total ayat Ziyadah yang dicapai ananda per bulan</p>
            </div>
            <div style="height: 300px;">
                <canvas id="progresAnakChart"></canvas>
            </div>
        </div>

        <!-- Riwayat Setoran -->
        <div class="bg-white rounded-xl border border-slate-200 p-6">
            <h3 class="text-lg font-semibold text-slate-800 mb-4">Riwayat Setoran Terbaru</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-3 px-4 text-xs font-medium text-slate-500 uppercase">Tanggal</th>
                            <th class="py-3 px-4 text-xs font-medium text-slate-500 uppercase">Jenis</th>
                            <th class="py-3 px-4 text-xs font-medium text-slate-500 uppercase">Surat</th>
                            <th class="py-3 px-4 text-xs font-medium text-slate-500 uppercase">Ayat</th>
                            <th class="py-3 px-4 text-xs font-medium text-slate-500 uppercase">Predikat</th>
                            <th class="py-3 px-4 text-xs font-medium text-slate-500 uppercase">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php if (empty($riwayat_list)): ?>
                            <tr><td colspan="6" class="py-8 text-center text-slate-500">Belum ada riwayat setoran.</td></tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $r): 
                                $badge = $r['jenis'] == 'ziyadah' ? 'bg-emerald-100 text-emerald-700' : ($r['jenis'] == 'murajaah' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700');
                                $predikat = $r['predikat'] == 'A' ? 'text-emerald-600 font-semibold' : ($r['predikat'] == 'B' ? 'text-amber-600 font-semibold' : 'text-red-600 font-semibold');
                            ?>
                                <tr class="border-b border-slate-100 hover:bg-slate-50">
                                    <td class="py-3 px-4 text-slate-600"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                                    <td class="py-3 px-4"><span class="px-2 py-1 rounded text-xs <?= $badge ?>"><?= strtoupper($r['jenis']) ?></span></td>
                                    <td class="py-3 px-4 text-slate-800 font-medium"><?= htmlspecialchars($r['surat']) ?></td>
                                    <td class="py-3 px-4 text-slate-600"><?= $r['ayat_awal'] ?> - <?= $r['ayat_akhir'] ?></td>
                                    <td class="py-3 px-4 <?= $predikat ?>"><?= $r['predikat'] ?></td>
                                    <td class="py-3 px-4 text-slate-500 text-xs"><?= htmlspecialchars($r['catatan'] ?? '-') ?></td>
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
        const ctx = document.getElementById('progresAnakChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Ziyadah (Ayat)',
                    data: <?= json_encode($chart_ziyadah) ?>,
                    backgroundColor: '#6366f1',
                    borderRadius: 6,
                    barThickness: 30
                }]
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
    </script>

</body>
</html>