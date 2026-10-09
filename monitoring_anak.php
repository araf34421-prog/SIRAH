<?php
session_start();
require 'config/database.php';

// 🔒 Keamanan: HANYA Ortu yang boleh akses halaman ini
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ortu') {
    header("Location: login.php");
    exit;
}

$id_ortu = (int) $_SESSION['user_id'];

// 1. Cari data santri yang terhubung dengan akun ortu ini
$santri_query = "SELECT * FROM santri WHERE id_user_ortu = $id_ortu LIMIT 1";
$santri_result = mysqli_query($conn, $santri_query);
$santri = mysqli_fetch_assoc($santri_result);

// Variabel default jika santri tidak ditemukan
$target_ayat = 0;
$target_ket = "Belum ditentukan";
$ziyadah_bulan_ini = 0;
$murajaah_bulan_ini = 0;
$tasmi_bulan_ini = 0;
$riwayat_list = [];

if ($santri) {
    $id_santri = $santri['id'];
    $bulan_ini = date('Y-m');

    // 2. Ambil Target Hafalan Terkini
    $target_query = "SELECT target_ayat_per_hari, keterangan FROM target_hafalan WHERE id_santri = $id_santri ORDER BY id DESC LIMIT 1";
    $target_result = mysqli_query($conn, $target_query);
    if ($target_result && $target_data = mysqli_fetch_assoc($target_result)) {
        $target_ayat = $target_data['target_ayat_per_hari'];
        $target_ket = $target_data['keterangan'];
    }

    // 3. Hitung Statistik Bulan Ini
    $stat_query = "SELECT 
                    COALESCE(SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END), 0) as total_ziyadah,
                    COALESCE(SUM(CASE WHEN jenis = 'murajaah' THEN 1 ELSE 0 END), 0) as total_murajaah,
                    COALESCE(SUM(CASE WHEN jenis = 'tasmi' THEN 1 ELSE 0 END), 0) as total_tasmi
                   FROM hafalan 
                   WHERE id_santri = $id_santri AND DATE_FORMAT(tanggal, '%Y-%m') = '$bulan_ini'";
    $stat_result = mysqli_query($conn, $stat_query);
    $stat_data = mysqli_fetch_assoc($stat_result);

    $ziyadah_bulan_ini = $stat_data['total_ziyadah'];
    $murajaah_bulan_ini = $stat_data['total_murajaah'];
    $tasmi_bulan_ini = $stat_data['total_tasmi'];

    // 4. Ambil Riwayat Setoran Terbaru (10 Terakhir)
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
</head>
<body class="bg-gray-100 font-sans">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <?php if (!$santri): ?>
            <!-- Jika Akun Ortu belum terhubung ke Santri -->
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-6 rounded shadow">
                <h3 class="font-bold text-lg mb-2"> Data Anak Tidak Ditemukan</h3>
                <p>Akun orang tua Anda belum terhubung dengan data santri di sistem. Silakan hubungi Ustadz/Ustadzah untuk mendaftarkan data anak Anda.</p>
            </div>
        <?php else: ?>

        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-800"> Monitoring Hafalan Ananda</h2>
                <p class="text-gray-600 text-sm mt-1">Pantau perkembangan hafalan <b><?= htmlspecialchars($santri['nama']) ?></b> secara langsung.</p>
            </div>
            <div class="bg-white px-4 py-2 rounded-lg shadow-sm text-sm text-gray-600">
                Periode: <b><?= date('F Y') ?></b>
            </div>
        </div>

        <!-- KARTU STATISTIK -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <!-- Info Anak -->
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-emerald-500">
                <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Nama Santri</h3>
                <p class="text-xl font-bold text-gray-800 mt-2"><?= htmlspecialchars($santri['nama']) ?></p>
                <p class="text-sm text-gray-500">NIS: <?= htmlspecialchars($santri['nis']) ?></p>
            </div>
            
            <!-- Target -->
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-orange-500">
                <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Target Harian</h3>
                <p class="text-xl font-bold text-orange-600 mt-2"><?= $target_ayat ?> Ayat</p>
                <p class="text-xs text-gray-500 truncate" title="<?= htmlspecialchars($target_ket) ?>"><?= htmlspecialchars($target_ket) ?></p>
            </div>

            <!-- Capaian Ziyadah -->
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-blue-500">
                <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Ziyadah Bulan Ini</h3>
                <p class="text-3xl font-bold text-blue-600 mt-2"><?= $ziyadah_bulan_ini ?></p>
                <p class="text-xs text-gray-500">Total Ayat</p>
            </div>

            <!-- Capaian Murajaah & Tasmi -->
            <div class="bg-white p-6 rounded-xl shadow border-l-4 border-purple-500">
                <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Muraja'ah & Tasmi'</h3>
                <div class="flex justify-between mt-2">
                    <div>
                        <p class="text-xl font-bold text-purple-600"><?= $murajaah_bulan_ini ?></p>
                        <p class="text-xs text-gray-500">Muraja'ah</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xl font-bold text-purple-600"><?= $tasmi_bulan_ini ?></p>
                        <p class="text-xs text-gray-500">Tasmi'</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL RIWAYAT SETORAN -->
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4"> Riwayat Setoran Terbaru</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <th class="py-3 px-4">Tanggal</th>
                            <th class="py-3 px-4">Jenis Setoran</th>
                            <th class="py-3 px-4">Surat / Juz</th>
                            <th class="py-3 px-4">Ayat</th>
                            <th class="py-3 px-4">Predikat</th>
                            <th class="py-3 px-4">Catatan Ustadz</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm">
                        <?php if (empty($riwayat_list)): ?>
                            <tr><td colspan="6" class="py-4 text-center text-gray-500">Belum ada riwayat setoran untuk ananda.</td></tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $r): 
                                // Warna Badge Jenis
                                $badge_color = 'bg-gray-100 text-gray-700';
                                if ($r['jenis'] == 'ziyadah') $badge_color = 'bg-emerald-100 text-emerald-700';
                                if ($r['jenis'] == 'murajaah') $badge_color = 'bg-blue-100 text-blue-700';
                                if ($r['jenis'] == 'tasmi') $badge_color = 'bg-purple-100 text-purple-700';

                                // Warna Predikat
                                $predikat_color = 'text-gray-600';
                                if ($r['predikat'] == 'A') $predikat_color = 'text-green-600 font-bold';
                                if ($r['predikat'] == 'B') $predikat_color = 'text-yellow-600 font-bold';
                                if ($r['predikat'] == 'C') $predikat_color = 'text-red-600 font-bold';
                            ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4"><?= date('d M Y', strtotime($r['tanggal'])) ?></td>
                                    <td class="py-3 px-4"><span class="px-2 py-1 rounded-full text-xs <?= $badge_color ?>"><?= strtoupper($r['jenis']) ?></span></td>
                                    <td class="py-3 px-4 font-medium"><?= htmlspecialchars($r['surat']) ?></td>
                                    <td class="py-3 px-4"><?= $r['ayat_awal'] ?> - <?= $r['ayat_akhir'] ?></td>
                                    <td class="py-3 px-4 <?= $predikat_color ?>"><?= $r['predikat'] ?></td>
                                    <td class="py-3 px-4 text-xs italic"><?= htmlspecialchars($r['catatan'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>

    </div>

</body>
</html>