<?php
session_start();
require 'config/database.php';

// 🔒 Keamanan: Hanya Ustadz (dan Pimpinan jika mau)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['user_id'];
$role = $_SESSION['role'];

// 1. Ambil daftar santri
// Untuk sementara, tampilkan semua santri di dropdown
// (Nanti bisa difilter berdasarkan halaqah jika tabel halaqah sudah terisi)
$santri_list_query = "SELECT id, nama FROM santri ORDER BY nama ASC";
$santri_list = mysqli_fetch_all(mysqli_query($conn, $santri_list_query), MYSQLI_ASSOC);

// 2. Tentukan Santri yang Dipilih (Default: Santri pertama di list)
$id_santri_pilih = isset($_GET['id_santri']) ? (int) $_GET['id_santri'] : ($santri_list[0]['id'] ?? 0);
$santri_dipilih = null;
$chart_labels = [];
$chart_ziyadah = [];
$chart_murajaah = [];
$stat = ['total_ayat' => 0, 'total_murajaah' => 0, 'total_tasmi' => 0];
$riwayat_list = [];

if ($id_santri_pilih > 0) {
    // Ambil Detail Santri
    $santri_dipilih = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM santri WHERE id = $id_santri_pilih"));
    
    // Ambil Target Terkini
    $target_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT target_ayat_per_hari, keterangan FROM target_hafalan WHERE id_santri = $id_santri_pilih ORDER BY id DESC LIMIT 1"));
    $target_ayat = $target_data ? $target_data['target_ayat_per_hari'] : 0;
    $target_ket = $target_data ? $target_data['keterangan'] : 'Belum ada target';

    $bulan_ini = date('Y-m');

    // Statistik Bulan Ini (Khusus Santri Ini)
    $stat_query = "SELECT 
                    COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as total_ayat,
                    COALESCE(SUM(CASE WHEN jenis = 'murajaah' THEN 1 ELSE 0 END), 0) as total_murajaah,
                    COALESCE(SUM(CASE WHEN jenis = 'tasmi' THEN 1 ELSE 0 END), 0) as total_tasmi
                  FROM hafalan 
                  WHERE id_santri = $id_santri_pilih AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
    $stat = mysqli_fetch_assoc(mysqli_query($conn, $stat_query));

    // Grafik Tren 6 Bulan (Khusus Santri Ini)
    for ($i = 5; $i >= 0; $i--) {
        $bulan = date('Y-m', strtotime("-$i months"));
        $chart_labels[] = date('M Y', strtotime("-$i months"));
        $query = "SELECT 
                    COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as z,
                    COALESCE(SUM(CASE WHEN jenis = 'murajaah' THEN 1 ELSE 0 END), 0) as m
                  FROM hafalan WHERE id_santri = $id_santri_pilih AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan'";
        $result = mysqli_query($conn, $query);
        $data = mysqli_fetch_assoc($result);
        $chart_ziyadah[] = (int) $data['z'];
        $chart_murajaah[] = (int) $data['m'];
    }

    // Riwayat 10 Terakhir
    $riwayat_query = "SELECT * FROM hafalan WHERE id_santri = $id_santri_pilih ORDER BY tanggal DESC, id DESC LIMIT 10";
    $riwayat_list = mysqli_fetch_all(mysqli_query($conn, $riwayat_query), MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Progres Detail Santri - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                <i data-lucide="user-search" class="w-7 h-7 text-indigo-600"></i> Analisis Progres Individual Santri
            </h1>
            <p class="text-sm text-slate-500 mt-1">Pilih santri untuk melihat detail perkembangan, grafik tren, dan riwayat setoran.</p>
        </div>

        <!-- SEARCH / DROPDOWN SANTRI -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6">
            <form action="progres_santri.php" method="GET" class="flex items-end gap-4">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Cari / Pilih Nama Santri</label>
                    <select name="id_santri" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">-- Pilih Santri --</option>
                        <?php foreach ($santri_list as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $s['id'] == $id_santri_pilih ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg transition flex items-center gap-2">
                    <i data-lucide="search" class="w-4 h-4"></i> Tampilkan
                </button>
            </form>
        </div>

        <?php if (!$santri_dipilih): ?>
            <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-6 rounded-lg text-sm">
                Silakan pilih nama santri di atas untuk melihat detail progresnya.
            </div>
        <?php else: ?>

        <!-- INFO SANTRI & TARGET -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm md:col-span-2">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-indigo-100 rounded-full flex items-center justify-center">
                        <i data-lucide="user" class="w-7 h-7 text-indigo-600"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-slate-900"><?= htmlspecialchars($santri_dipilih['nama']) ?></h2>
                        <p class="text-sm text-slate-500">NIS: <?= htmlspecialchars($santri_dipilih['nis']) ?> • <?= $santri_dipilih['jenis_kelamin'] == 'L' ? 'Laki-laki' : 'Perempuan' ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Target Hafalan Saat Ini</p>
                <p class="text-2xl font-bold text-indigo-600"><?= $target_ayat ?> <span class="text-sm font-normal text-slate-500">Ayat/Hari</span></p>
                <p class="text-xs text-slate-500 mt-1 truncate" title="<?= htmlspecialchars($target_ket) ?>"><?= htmlspecialchars($target_ket) ?></p>
            </div>
        </div>

        <!-- STATISTIK BULAN INI -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ziyadah Bulan Ini</p>
                    <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                        <i data-lucide="book-plus" class="w-4 h-4 text-emerald-600"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-slate-900"><?= $stat['total_ayat'] ?></p>
                <p class="text-xs text-slate-500 mt-1">Total ayat baru</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Muraja'ah Bulan Ini</p>
                    <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                        <i data-lucide="refresh-cw" class="w-4 h-4 text-blue-600"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-slate-900"><?= $stat['total_murajaah'] ?></p>
                <p class="text-xs text-slate-500 mt-1"> Kali pengulangan</p>
            </div>
            <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tasmi' Bulan Ini</p>
                    <div class="w-8 h-8 bg-purple-50 rounded-lg flex items-center justify-center">
                        <i data-lucide="award" class="w-4 h-4 text-purple-600"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-slate-900"><?= $stat['total_tasmi'] ?></p>
                <p class="text-xs text-slate-500 mt-1"> Kali ujian juz</p>
            </div>
        </div>

        <!-- GRAFIK TREN INDIVIDUAL -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Tren Hafalan <?= htmlspecialchars($santri_dipilih['nama']) ?></h2>
                    <p class="text-sm text-slate-500">Perbandingan Ziyadah dan Muraja'ah 6 bulan terakhir</p>
                </div>
                <div class="flex gap-4 text-xs">
                    <div class="flex items-center gap-2"><div class="w-3 h-3 bg-emerald-500 rounded-full"></div><span class="text-slate-600">Ziyadah (Ayat)</span></div>
                    <div class="flex items-center gap-2"><div class="w-3 h-3 bg-blue-500 rounded-full"></div><span class="text-slate-600">Muraja'ah (Kali)</span></div>
                </div>
            </div>
            <div style="height: 300px;">
                <canvas id="santriChart"></canvas>
            </div>
        </div>

        <!-- RIWAYAT SETORAN -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                    <i data-lucide="history" class="w-5 h-5 text-slate-400"></i> 10 Setoran Terakhir
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                        <tr>
                            <th class="py-3 px-6 font-medium">Tanggal</th>
                            <th class="py-3 px-6 font-medium">Jenis</th>
                            <th class="py-3 px-6 font-medium">Surat / Juz</th>
                            <th class="py-3 px-6 font-medium">Ayat</th>
                            <th class="py-3 px-6 font-medium">Predikat</th>
                            <th class="py-3 px-6 font-medium">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-600 divide-y divide-slate-100">
                        <?php if (empty($riwayat_list)): ?>
                            <tr><td colspan="6" class="py-8 text-center text-slate-400">Belum ada riwayat setoran untuk santri ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $r): 
                                $badge = $r['jenis'] == 'ziyadah' ? 'bg-emerald-100 text-emerald-700' : ($r['jenis'] == 'murajaah' ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700');
                                $predikat = $r['predikat'] == 'A' ? 'text-emerald-600 font-semibold' : ($r['predikat'] == 'B' ? 'text-amber-600 font-semibold' : 'text-red-600 font-semibold');
                            ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-4 px-6"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                                    <td class="py-4 px-6"><span class="px-2 py-1 rounded text-xs <?= $badge ?>"><?= strtoupper($r['jenis']) ?></span></td>
                                    <td class="py-4 px-6 font-medium text-slate-900"><?= htmlspecialchars($r['surat']) ?></td>
                                    <td class="py-4 px-6"><?= $r['ayat_awal'] ?> - <?= $r['ayat_akhir'] ?></td>
                                    <td class="py-4 px-6 <?= $predikat ?>"><?= $r['predikat'] ?></td>
                                    <td class="py-4 px-6 text-xs text-slate-500"><?= htmlspecialchars($r['catatan'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>

    </div>

    <?php if ($santri_dipilih): ?>
    <script>
        const ctx = document.getElementById('santriChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [
                    {
                        label: 'Ziyadah (Ayat)',
                        data: <?= json_encode($chart_ziyadah) ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        borderWidth: 2, tension: 0.4, fill: true, pointRadius: 4, pointBackgroundColor: '#10b981'
                    },
                    {
                        label: "Muraja'ah (Kali)",
                        data: <?= json_encode($chart_murajaah) ?>,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderWidth: 2, tension: 0.4, fill: true, pointRadius: 4, pointBackgroundColor: '#3b82f6'
                    }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { family: 'Inter', size: 11 }, color: '#64748b' } },
                    x: { grid: { display: false }, ticks: { font: { family: 'Inter', size: 11 }, color: '#64748b' } }
                }
            }
        });
    </script>
    <?php endif; ?>
    <script>lucide.createIcons();</script>
</body>
</html>