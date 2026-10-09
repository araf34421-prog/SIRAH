<?php
session_start();
require 'config/database.php';

// 🔒 Keamanan: Hanya Ustadz dan Pimpinan
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

// 1. Tentukan Bulan & Tahun yang dipilih (Default: Bulan & Tahun saat ini)
$bulan_dipilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_dipilih = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$periode = $tahun_dipilih . '-' . $bulan_dipilih;

// Nama Bulan dalam Bahasa Indonesia
$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// 2. Query Rekapitulasi Otomatis
// Kita menggunakan LEFT JOIN agar santri yang TIDAK ada setoran di bulan itu tetap muncul (dengan nilai 0)
$sql_rekap = "SELECT 
                s.id, 
                s.nama, 
                COALESCE(SUM(CASE WHEN h.jenis = 'ziyadah' THEN h.jumlah_ayat ELSE 0 END), 0) as total_ayat_ziyadah,
                COALESCE(SUM(CASE WHEN h.jenis = 'murajaah' THEN 1 ELSE 0 END), 0) as total_murajaah,
                COALESCE(SUM(CASE WHEN h.jenis = 'tasmi' THEN 1 ELSE 0 END), 0) as total_tasmi
              FROM santri s
              LEFT JOIN hafalan h ON s.id = h.id_santri AND DATE_FORMAT(h.tanggal, '%Y-%m') = '$periode'
              GROUP BY s.id
              ORDER BY s.nama ASC";

$result_rekap = mysqli_query($conn, $sql_rekap);
$data_rekap = mysqli_fetch_all($result_rekap, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Bulanan - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* CSS Khusus agar tabel rapi saat di-Print */
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .ml-64 { margin-left: 0 !important; }
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- Header & Filter Bulan -->
        <div class="flex justify-between items-center mb-6 no-print">
            <h2 class="text-2xl font-bold text-gray-800">📊 Rekapitulasi Hafalan Bulanan</h2>
            <button onclick="window.print()" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition text-sm flex items-center">
                Cetak / Simpan PDF
            </button>
        </div>

        <!-- Form Filter (Disembunyikan saat Print) -->
        <div class="bg-white rounded-xl shadow p-6 mb-6 no-print border-l-4 border-cyan-500">
            <h3 class="text-lg font-bold text-cyan-700 mb-4">📅 Pilih Periode Rekap</h3>
            <form action="rekap_bulanan.php" method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Bulan</label>
                    <select name="bulan" class="px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-cyan-500 outline-none">
                        <?php foreach ($nama_bulan as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $num == $bulan_dipilih ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tahun</label>
                    <input type="number" name="tahun" value="<?= $tahun_dipilih ?>" min="2024" max="2030" class="px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-cyan-500 outline-none w-24">
                </div>
                <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold py-2 px-6 rounded-lg transition text-sm">
                    Tampilkan Rekap
                </button>
            </form>
        </div>

        <!-- Laporan Rekap (Area yang akan di-Print) -->
        <div class="bg-white rounded-xl shadow p-6">
            <div class="text-center mb-6">
                <h2 class="text-xl font-bold text-gray-800">SURAU TAHFIZH FIRDAUS</h2>
                <h3 class="text-lg font-semibold text-gray-700">Laporan Rekapitulasi Hafalan Santri</h3>
                <p class="text-gray-600">Periode: <b><?= $nama_bulan[$bulan_dipilih] ?> <?= $tahun_dipilih ?></b></p>
                <p class="text-xs text-gray-500 mt-1">Dicetak pada: <?= date('d F Y, H:i') ?></p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100 text-gray-800 uppercase text-xs">
                            <th class="py-3 px-4 border border-gray-300 text-center">No</th>
                            <th class="py-3 px-4 border border-gray-300">Nama Santri</th>
                            <th class="py-3 px-4 border border-gray-300 text-center">Total Ziyadah (Ayat)</th>
                            <th class="py-3 px-4 border border-gray-300 text-center">Total Muraja'ah (Kali)</th>
                            <th class="py-3 px-4 border border-gray-300 text-center">Total Tasmi' (Kali)</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-700 text-sm">
                        <?php if (empty($data_rekap)): ?>
                            <tr><td colspan="5" class="py-4 text-center text-gray-500">Tidak ada data santri.</td></tr>
                        <?php else: ?>
                            <?php foreach ($data_rekap as $index => $d): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4 border border-gray-300 text-center"><?= $index + 1 ?></td>
                                    <td class="py-3 px-4 border border-gray-300 font-medium"><?= htmlspecialchars($d['nama']) ?></td>
                                    <td class="py-3 px-4 border border-gray-300 text-center">
                                        <span class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded text-xs font-bold"><?= $d['total_ayat_ziyadah'] ?></span>
                                    </td>
                                    <td class="py-3 px-4 border border-gray-300 text-center">
                                        <span class="bg-blue-100 text-blue-700 px-2 py-1 rounded text-xs font-bold"><?= $d['total_murajaah'] ?></span>
                                    </td>
                                    <td class="py-3 px-4 border border-gray-300 text-center">
                                        <span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-bold"><?= $d['total_tasmi'] ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tanda Tangan (Muncul saat Print) -->
            <div class="hidden print:block mt-12 flex justify-end">
                <div class="text-center">
                    <p>Tangerang Selatan, <?= date('d F Y') ?></p>
                    <p class="mb-16">Pimpinan Surau Tahfizh Firdaus</p>
                    <p class="font-bold underline">( ................................... )</p>
                </div>
            </div>
        </div>

    </div>

</body>
</html>