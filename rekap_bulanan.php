<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

$bulan_dipilih = isset($_GET['bulan']) ? str_pad((int)$_GET['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$tahun_dipilih = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');
$id_lokal      = isset($_GET['id_lokal']) ? (int)$_GET['id_lokal'] : 0;
$periode       = $tahun_dipilih . '-' . $bulan_dipilih;

$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

// Ambil daftar lokal untuk filter
$lokal_list = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama_halaqah FROM halaqah ORDER BY id ASC"), MYSQLI_ASSOC);
$nama_lokal_terpilih = "Semua Kelas / Lokal";
foreach ($lokal_list as $lk) {
    if ((int)$lk['id'] === $id_lokal) {
        $nama_lokal_terpilih = $lk['nama_halaqah'];
        break;
    }
}

$where_lokal = $id_lokal > 0 ? "WHERE s.id_halaqah = $id_lokal" : "";

// Query akurat menggunakan Subquery terpisah untuk Hafalan (h) dan Absensi (a)
// agar TIDAK terjadi duplikasi perkalian baris (Cartesian Product)
$sql_santri = "SELECT 
                  s.id, s.nama, s.nis, hl.nama_halaqah,
                  COALESCE(h.total_ayat, 0) as total_ayat,
                  COALESCE(h.total_murajaah, 0) as total_murajaah,
                  COALESCE(h.total_tasmi, 0) as total_tasmi,
                  COALESCE(a.hadir, 0) as hadir,
                  COALESCE(a.sakit, 0) as sakit,
                  COALESCE(a.izin, 0) as izin,
                  COALESCE(a.alpa, 0) as alpa
               FROM santri s
               LEFT JOIN halaqah hl ON s.id_halaqah = hl.id
               LEFT JOIN (
                   SELECT id_santri,
                          SUM(CASE WHEN jenis = 'ziyadah' THEN jumlah_ayat ELSE 0 END) as total_ayat,
                          SUM(CASE WHEN jenis = 'murajaah' THEN 1 ELSE 0 END) as total_murajaah,
                          SUM(CASE WHEN jenis = 'tasmi' THEN 1 ELSE 0 END) as total_tasmi
                   FROM hafalan
                   WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$periode'
                   GROUP BY id_santri
               ) h ON s.id = h.id_santri
               LEFT JOIN (
                   SELECT id_santri,
                          SUM(CASE WHEN UPPER(LEFT(TRIM(status), 1)) = 'H' THEN 1 ELSE 0 END) as hadir,
                          SUM(CASE WHEN UPPER(LEFT(TRIM(status), 1)) = 'S' THEN 1 ELSE 0 END) as sakit,
                          SUM(CASE WHEN UPPER(LEFT(TRIM(status), 1)) = 'I' THEN 1 ELSE 0 END) as izin,
                          SUM(CASE WHEN UPPER(LEFT(TRIM(status), 1)) = 'A' THEN 1 ELSE 0 END) as alpa
                   FROM absensi
                   WHERE DATE_FORMAT(tanggal, '%Y-%m') = '$periode'
                   GROUP BY id_santri
               ) a ON s.id = a.id_santri
               $where_lokal
               ORDER BY s.nama ASC";

$data_santri = mysqli_fetch_all(mysqli_query($conn, $sql_santri), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Bulanan (<?= $nama_bulan[$bulan_dipilih] ?> <?= $tahun_dipilih ?>) - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        @media print {
            @page {
                size: A4 landscape;
                margin: 1.2cm 1.5cm;
            }
            .no-print, aside, nav, .sidebar, script, button { display: none !important; }
            .ml-64 { margin-left: 0 !important; padding: 0 !important; }
            body { background: white !important; margin: 0 !important; padding: 0 !important; }
            .shadow-sm, .shadow, .rounded-xl, .rounded-2xl { box-shadow: none !important; border-radius: 0 !important; border: none !important; }
            
            .kop-surat {
                text-align: center !important;
                border-bottom: 3px double #000 !important;
                padding-bottom: 12px !important;
                margin-bottom: 20px !important;
            }
            
            .tabel-rekap {
                width: 100% !important;
                border-collapse: collapse !important;
                border: 2px solid #000 !important;
            }
            
            .tabel-rekap th {
                border: 1.5px solid #000 !important;
                padding: 8px 6px !important;
                font-weight: 700 !important;
                font-size: 10px !important;
                text-transform: uppercase !important;
                text-align: center !important;
                background-color: #f1f5f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .tabel-rekap td {
                border: 1px solid #000 !important;
                padding: 6px !important;
                font-size: 10.5px !important;
                text-align: center !important;
                color: #000 !important;
            }
            
            .tabel-rekap td.teks-kiri {
                text-align: left !important;
            }
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        <!-- Header Halaman -->
        <div class="flex flex-wrap justify-between items-center gap-4 mb-6 no-print">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 bg-white border border-slate-200 rounded-xl shadow-sm flex items-center justify-center text-indigo-600">
                    <i data-lucide="file-bar-chart" class="w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Rekapitulasi Bulanan</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Laporan terpadu capaian hafalan (Ziyadah, Muraja'ah, Tasmi') dan kehadiran santri.</p>
                </div>
            </div>
            <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-5 rounded-xl transition shadow-sm flex items-center gap-2 text-xs">
                <i data-lucide="printer" class="w-4 h-4"></i> Cetak Laporan (PDF)
            </button>
        </div>

        <!-- Filter Periode & Lokal -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-6 no-print">
            <form action="rekap_bulanan.php" method="GET" class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Filter Kelas / Lokal</label>
                    <select name="id_lokal" onchange="this.form.submit()" class="px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="0">Semua Kelas / Lokal</option>
                        <?php foreach ($lokal_list as $lk): ?>
                            <option value="<?= $lk['id'] ?>" <?= (int)$lk['id'] === $id_lokal ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lk['nama_halaqah']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Bulan</label>
                    <select name="bulan" onchange="this.form.submit()" class="px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                        <?php foreach ($nama_bulan as $num => $name): ?>
                            <option value="<?= $num ?>" <?= $num == $bulan_dipilih ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tahun</label>
                    <input type="number" name="tahun" value="<?= $tahun_dipilih ?>" min="2024" max="2035" class="px-3.5 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold w-24 outline-none">
                </div>
                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2 px-5 rounded-xl transition flex items-center gap-2 text-xs">
                    <i data-lucide="search" class="w-3.5 h-3.5"></i> Tampilkan
                </button>
            </form>
        </div>

        <!-- Lembar Laporan -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-8">
            
            <!-- Kop Surat -->
            <div class="kop-surat text-center mb-6 pb-5 border-b-4 border-double border-slate-800">
                <h2 class="text-xl font-extrabold text-slate-900 tracking-wide uppercase">SURAU TAHFIDZUL QUR'AN MUSHALLA FIRDAUS</h2>
                <p class="text-xs text-slate-600 mt-0.5">Jorong Jalikur Patanangan Kenagarian Koto Tangah Kec. Tilatang Kamang Kab. Agam</p>
                <h3 class="text-sm font-bold text-slate-800 mt-3 uppercase underline">Laporan Rekapitulasi Hafalan & Kehadiran Santri</h3>
                <div class="flex justify-center gap-6 text-xs text-slate-600 mt-2">
                    <span>Periode: <b class="text-slate-900"><?= $nama_bulan[$bulan_dipilih] ?> <?= $tahun_dipilih ?></b></span>
                    <span>•</span>
                    <span>Lokal: <b class="text-slate-900"><?= htmlspecialchars($nama_lokal_terpilih) ?></b></span>
                </div>
            </div>

            <!-- Tabel Rekap -->
            <div class="overflow-x-auto">
                <table class="tabel-rekap w-full text-left border-collapse border-2 border-slate-800">
                    <thead>
                        <tr class="bg-slate-100">
                            <th rowspan="2" class="py-3 px-3 border border-slate-800 text-center font-bold text-xs uppercase w-10">No</th>
                            <th rowspan="2" class="py-3 px-4 border border-slate-800 font-bold text-xs uppercase">Nama Santri</th>
                            <th rowspan="2" class="py-3 px-3 border border-slate-800 font-bold text-xs uppercase">NIS</th>
                            <th rowspan="2" class="py-3 px-4 border border-slate-800 font-bold text-xs uppercase">Kelas / Lokal</th>
                            <th colspan="5" class="py-2 px-3 border border-slate-800 text-center font-bold text-xs uppercase bg-slate-200">Rekap Kehadiran</th>
                            <th colspan="3" class="py-2 px-3 border border-slate-800 text-center font-bold text-xs uppercase bg-indigo-50">Capaian Hafalan</th>
                        </tr>
                        <tr class="bg-slate-50">
                            <th class="py-2 px-2 border border-slate-800 text-center font-bold text-xs w-12">Hadir</th>
                            <th class="py-2 px-2 border border-slate-800 text-center font-bold text-xs w-12">Sakit</th>
                            <th class="py-2 px-2 border border-slate-800 text-center font-bold text-xs w-12">Izin</th>
                            <th class="py-2 px-2 border border-slate-800 text-center font-bold text-xs w-12">Alfa</th>
                            <th class="py-2 px-2 border border-slate-800 text-center font-bold text-xs w-16">% Hadir</th>
                            <th class="py-2 px-3 border border-slate-800 text-center font-bold text-xs bg-emerald-50">Ziyadah (Ayat)</th>
                            <th class="py-2 px-3 border border-slate-800 text-center font-bold text-xs bg-blue-50">Muraja'ah (Kali)</th>
                            <th class="py-2 px-3 border border-slate-800 text-center font-bold text-xs bg-purple-50">Tasmi' (Kali)</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs text-slate-700">
                        <?php if (empty($data_santri)): ?>
                            <tr>
                                <td colspan="12" class="py-8 text-center text-slate-400 border border-slate-800">Tidak ada data santri untuk ditampilkan.</td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $no = 1; 
                            foreach ($data_santri as $santri): 
                                $jml_pertemuan = (int)$santri['hadir'] + (int)$santri['sakit'] + (int)$santri['izin'] + (int)$santri['alpa'];
                                // Persentase dihitung dari jumlah hari pertemuan tercatat (atau dari 26 hari kerja jika belum ada presensi)
                                $persentase_kehadiran = $jml_pertemuan > 0 ? round(((int)$santri['hadir'] / $jml_pertemuan) * 100) : 0;
                            ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-2.5 px-3 border border-slate-800 text-center font-medium"><?= $no++ ?></td>
                                    <td class="py-2.5 px-4 border border-slate-800 teks-kiri font-bold text-slate-900"><?= htmlspecialchars($santri['nama']) ?></td>
                                    <td class="py-2.5 px-3 border border-slate-800 text-center font-mono"><?= htmlspecialchars($santri['nis'] ?: '-') ?></td>
                                    <td class="py-2.5 px-4 border border-slate-800 teks-kiri"><?= htmlspecialchars($santri['nama_halaqah'] ?: '-') ?></td>
                                    <td class="py-2.5 px-2 border border-slate-800 text-center font-bold text-emerald-600"><?= (int)$santri['hadir'] ?></td>
                                    <td class="py-2.5 px-2 border border-slate-800 text-center font-bold text-blue-600"><?= (int)$santri['sakit'] ?></td>
                                    <td class="py-2.5 px-2 border border-slate-800 text-center font-bold text-amber-600"><?= (int)$santri['izin'] ?></td>
                                    <td class="py-2.5 px-2 border border-slate-800 text-center font-bold text-rose-600"><?= (int)$santri['alpa'] ?></td>
                                    <td class="py-2.5 px-2 border border-slate-800 text-center font-extrabold"><?= $persentase_kehadiran ?>%</td>
                                    <td class="py-2.5 px-3 border border-slate-800 text-center font-bold text-emerald-700"><?= (int)$santri['total_ayat'] ?></td>
                                    <td class="py-2.5 px-3 border border-slate-800 text-center font-bold text-blue-700"><?= (int)$santri['total_murajaah'] ?></td>
                                    <td class="py-2.5 px-3 border border-slate-800 text-center font-bold text-purple-700"><?= (int)$santri['total_tasmi'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tanda Tangan -->
            <div class="tanda-tangan mt-12 text-right pr-8">
                <p class="text-xs text-slate-700 mb-1">Jorong Jalikur Patanangan, <?= date('d F Y') ?></p>
                <p class="text-xs text-slate-700 mb-16">Pimpinan Surau Tahfizh Firdaus</p>
                <p class="text-xs text-slate-900 font-bold underline">( Ust. Aipda Syaiful Hendra )</p>
            </div>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>