<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

$bulan_dipilih = isset($_GET['bulan']) ? $_GET['bulan'] : date('m');
$tahun_dipilih = isset($_GET['tahun']) ? $_GET['tahun'] : date('Y');
$periode = $tahun_dipilih . '-' . $bulan_dipilih;

$nama_bulan = ['01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April', '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus', '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'];

// Hitung total hari kerja di bulan tersebut (asumsi 26 hari)
$total_hari_kerja = 26;

// Query lengkap: hafalan + kehadiran/absensi
$sql_santri = "SELECT s.id, s.nama, s.nis,
              COALESCE(SUM(CASE WHEN h.jenis = 'ziyadah' THEN h.jumlah_ayat ELSE 0 END), 0) as total_ayat,
              COALESCE(SUM(CASE WHEN h.jenis = 'murajaah' THEN 1 ELSE 0 END), 0) as total_murajaah,
              COALESCE(SUM(CASE WHEN h.jenis = 'tasmi' THEN 1 ELSE 0 END), 0) as total_tasmi,
              COALESCE(SUM(CASE WHEN a.status = 'Hadir' OR k.status = 'H' THEN 1 ELSE 0 END), 0) as hadir,
              COALESCE(SUM(CASE WHEN a.status = 'Sakit' OR k.status = 'S' THEN 1 ELSE 0 END), 0) as sakit,
              COALESCE(SUM(CASE WHEN a.status = 'Izin' OR k.status = 'I' THEN 1 ELSE 0 END), 0) as izin,
              COALESCE(SUM(CASE WHEN a.status = 'Alfa' OR k.status = 'A' THEN 1 ELSE 0 END), 0) as alpa
              FROM santri s 
              LEFT JOIN hafalan h ON s.id = h.id_santri AND DATE_FORMAT(h.tanggal, '%Y-%m') = '$periode'
              LEFT JOIN absensi a ON s.id = a.id_santri AND DATE_FORMAT(a.tanggal, '%Y-%m') = '$periode'
              LEFT JOIN kehadiran k ON s.id = k.id_santri AND DATE_FORMAT(k.tanggal, '%Y-%m') = '$periode'
              GROUP BY s.id 
              ORDER BY s.nama ASC";
$data_santri = mysqli_fetch_all(mysqli_query($conn, $sql_santri), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Bulanan - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        @media print {
            @page {
                size: A4 landscape;
                margin: 1.5cm 2cm 1.5cm 2cm;
            }
            .no-print, aside, nav, .sidebar, script, button { display: none !important; }
            .ml-64 { margin-left: 0 !important; padding: 0 !important; }
            body { background: white !important; margin: 0 !important; padding: 0 !important; }
            .shadow-sm, .shadow, .rounded-xl { box-shadow: none !important; border-radius: 0 !important; }
            
            .kop-surat {
                text-align: center !important;
                border-bottom: 3px double #000 !important;
                padding-bottom: 15px !important;
                margin-bottom: 30px !important;
            }
            
            .tabel-rekap {
                width: 100% !important;
                border-collapse: collapse !important;
                border: 2px solid #000 !important;
                margin-top: 20px !important;
            }
            
            .tabel-rekap th {
                border: 1.5px solid #000 !important;
                padding: 10px 6px !important;
                font-weight: 700 !important;
                font-size: 10px !important;
                text-transform: uppercase !important;
                text-align: center !important;
                background-color: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .tabel-rekap td {
                border: 1px solid #000 !important;
                padding: 8px 6px !important;
                font-size: 10px !important;
                text-align: center !important;
            }
            
            .tabel-rekap td.nama-santri {
                text-align: left !important;
                font-weight: 500 !important;
            }
            
            .tanda-tangan {
                margin-top: 60px !important;
                text-align: right !important;
                padding-right: 50px !important;
                font-size: 12px !important;
            }
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-8 no-print">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="file-bar-chart" class="w-7 h-7 text-indigo-600"></i> Rekapitulasi Bulanan
                </h1>
                <p class="text-sm text-slate-500 mt-1">Laporan detail perkembangan hafalan dan kehadiran per santri.</p>
            </div>
            <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition flex items-center gap-2">
                <i data-lucide="printer" class="w-4 h-4"></i> Cetak PDF
            </button>
        </div>

        <!-- Filter -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6 no-print">
            <form action="rekap_bulanan.php" method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Bulan</label>
                    <select name="bulan" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <?php foreach ($nama_bulan as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $num == $bulan_dipilih ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tahun</label>
                    <input type="number" name="tahun" value="<?= $tahun_dipilih ?>" min="2024" max="2030" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none w-24">
                </div>
                <button type="submit" class="bg-slate-800 hover:bg-slate-900 text-white font-medium py-2 px-6 rounded-lg transition flex items-center gap-2">
                    <i data-lucide="search" class="w-4 h-4"></i> Tampilkan
                </button>
            </form>
        </div>

        <!-- Laporan -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-8">
            
            <!-- Kop Surat -->
            <div class="kop-surat text-center mb-8 pb-6 border-b-4 border-double border-slate-800">
                <h2 class="text-2xl font-bold text-slate-900 tracking-wider">SURAU TAHFIZH FIRDAUS</h2>
                <h3 class="text-base font-semibold text-slate-700 mt-2">Laporan Rekapitulasi Hafalan & Kehadiran Santri</h3>
                <p class="text-sm text-slate-600 mt-3">Periode: <b class="text-slate-900"><?= $nama_bulan[$bulan_dipilih] ?> <?= $tahun_dipilih ?></b></p>
                <p class="text-xs text-slate-500 mt-2">Dicetak pada: <?= date('d F Y, H:i') ?></p>
            </div>

            <!-- Tabel Rekap -->
            <div class="overflow-x-auto">
                <table class="tabel-rekap w-full text-left border-collapse border-2 border-slate-800">
                    <thead>
                        <tr class="bg-slate-100">
                            <th class="py-3 px-4 border border-slate-800 text-center font-semibold text-xs uppercase tracking-wider w-10">No</th>
                            <th class="py-3 px-4 border border-slate-800 font-semibold text-xs uppercase tracking-wider">Nama Santri</th>
                            <th colspan="4" class="py-3 px-4 border border-slate-800 text-center font-semibold text-xs uppercase tracking-wider bg-slate-200">Kehadiran</th>
                            <th class="py-3 px-4 border border-slate-800 text-center font-semibold text-xs uppercase tracking-wider">%</th>
                            <th class="py-3 px-4 border border-slate-800 text-center font-semibold text-xs uppercase tracking-wider bg-emerald-100">Ziyadah</th>
                            <th class="py-3 px-4 border border-slate-800 text-center font-semibold text-xs uppercase tracking-wider bg-blue-100">Muraja'ah</th>
                            <th class="py-3 px-4 border border-slate-800 text-center font-semibold text-xs uppercase tracking-wider bg-purple-100">Tasmi'</th>
                        </tr>
                        <tr class="bg-slate-50">
                            <th class="py-2 px-4 border border-slate-800"></th>
                            <th class="py-2 px-4 border border-slate-800"></th>
                            <th class="py-2 px-4 border border-slate-800 text-center font-semibold text-xs">H</th>
                            <th class="py-2 px-4 border border-slate-800 text-center font-semibold text-xs">S</th>
                            <th class="py-2 px-4 border border-slate-800 text-center font-semibold text-xs">I</th>
                            <th class="py-2 px-4 border border-slate-800 text-center font-semibold text-xs">A</th>
                            <th class="py-2 px-4 border border-slate-800"></th>
                            <th class="py-2 px-4 border border-slate-800 text-center font-semibold text-xs">(Ayat)</th>
                            <th class="py-2 px-4 border border-slate-800 text-center font-semibold text-xs">(Kali)</th>
                            <th class="py-2 px-4 border border-slate-800 text-center font-semibold text-xs">(Kali)</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-700">
                        <?php if (empty($data_santri)): ?>
                            <tr>
                                <td colspan="10" class="py-8 text-center text-slate-400 border border-slate-800">Tidak ada data santri untuk periode ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; foreach ($data_santri as $santri): 
                                $total_hadir = $santri['hadir'] + $santri['sakit'] + $santri['izin'] + $santri['alpa'];
                                $persentase_kehadiran = $total_hari_kerja > 0 ? round(($santri['hadir'] / $total_hari_kerja) * 100) : 0;
                            ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-3 px-4 border border-slate-800 text-center font-medium"><?= $no ?></td>
                                    <td class="py-3 px-4 border border-slate-800 nama-santri"><?= htmlspecialchars($santri['nama']) ?></td>
                                    <td class="py-3 px-4 border border-slate-800 text-center font-semibold text-emerald-600"><?= $santri['hadir'] ?></td>
                                    <td class="py-3 px-4 border border-slate-800 text-center font-semibold text-blue-600"><?= $santri['sakit'] ?></td>
                                    <td class="py-3 px-4 border border-slate-800 text-center font-semibold text-amber-600"><?= $santri['izin'] ?></td>
                                    <td class="py-3 px-4 border border-slate-800 text-center font-semibold text-red-600"><?= $santri['alpa'] ?></td>
                                    <td class="py-3 px-4 border border-slate-800 text-center font-bold"><?= $persentase_kehadiran ?>%</td>
                                    <td class="py-3 px-4 border border-slate-800 text-center font-semibold text-emerald-600"><?= $santri['total_ayat'] ?></td>
                                    <td class="py-3 px-4 border border-slate-800 text-center font-semibold text-blue-600"><?= $santri['total_murajaah'] ?></td>
                                    <td class="py-3 px-4 border border-slate-800 text-center font-semibold text-purple-600"><?= $santri['total_tasmi'] ?></td>
                                </tr>
                            <?php $no++; endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tanda Tangan -->
            <div class="tanda-tangan mt-16 text-right pr-12">
                <p class="text-sm text-slate-700 mb-1">Jorong Jalikur Patanangan, <?= date('d F Y') ?></p>
                <p class="text-sm text-slate-700 mb-20">Pimpinan Surau Tahfizh Firdaus</p>
                <p class="text-sm text-slate-900 font-bold underline">( ................................... )</p>
            </div>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>