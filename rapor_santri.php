<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role    = $_SESSION['role'];
$user_id = (int) $_SESSION['user_id'];

// Fungsi konversi angka ke kata Terbilang (sesuai rumus terbilang di Excel JUZ 1)
function terbilang_indonesia($angka) {
    $angka = abs((int) round($angka));
    $baca  = ["", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas"];
    if ($angka === 0) return "Nol";
    
    $terbilang = "";
    if ($angka < 12) {
        $terbilang = " " . $baca[$angka];
    } elseif ($angka < 20) {
        $terbilang = terbilang_indonesia($angka - 10) . " Belas";
    } elseif ($angka < 100) {
        $terbilang = terbilang_indonesia((int)($angka / 10)) . " Puluh" . ($angka % 10 != 0 ? terbilang_indonesia($angka % 10) : "");
    } elseif ($angka < 200) {
        $terbilang = " Seratus" . ($angka - 100 != 0 ? terbilang_indonesia($angka - 100) : "");
    } elseif ($angka < 1000) {
        $terbilang = terbilang_indonesia((int)($angka / 100)) . " Ratus" . ($angka % 100 != 0 ? terbilang_indonesia($angka % 100) : "");
    } elseif ($angka < 2000) {
        $terbilang = " Seribu" . ($angka - 1000 != 0 ? terbilang_indonesia($angka - 1000) : "");
    } elseif ($angka < 1000000) {
        $terbilang = terbilang_indonesia((int)($angka / 1000)) . " Ribu" . ($angka % 1000 != 0 ? terbilang_indonesia($angka % 1000) : "");
    }
    return trim($terbilang);
}

// Ambil daftar santri (Jika role ortu, hanya tampilkan anaknya)
if ($role === 'ortu') {
    $santri_res = mysqli_query($conn, "SELECT * FROM santri WHERE id_user_ortu = $user_id ORDER BY nama ASC");
} else {
    $santri_res = mysqli_query($conn, "SELECT * FROM santri ORDER BY nama ASC");
}
$santri_list = mysqli_fetch_all($santri_res, MYSQLI_ASSOC);

$id_santri    = isset($_GET['id_santri']) ? (int) $_GET['id_santri'] : ($santri_list[0]['id'] ?? 0);
$juz          = isset($_GET['juz']) ? (int) $_GET['juz'] : 1;
$semester     = isset($_GET['semester']) ? $_GET['semester'] : '2 (Dua)';
$tahun_ajaran = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : date('Y');

$santri_aktif = null;
foreach ($santri_list as $s) {
    if ($s['id'] == $id_santri) {
        $santri_aktif = $s;
        break;
    }
}

$header_data = null;
$detail_data = [];
if ($santri_aktif) {
    $sem_esc = mysqli_real_escape_string($conn, $semester);
    $thn_esc = mysqli_real_escape_string($conn, $tahun_ajaran);
    $q_header = mysqli_query($conn, "SELECT * FROM rapor_tahfidz WHERE id_santri = {$santri_aktif['id']} AND juz = $juz AND semester = '$sem_esc' AND tahun_ajaran = '$thn_esc' LIMIT 1");
    $header_data = mysqli_fetch_assoc($q_header);

    if ($header_data) {
        $detail_q = mysqli_query($conn, "SELECT * FROM rapor_tahfidz_detail WHERE id_rapor = {$header_data['id']} ORDER BY urutan ASC");
        $detail_data = mysqli_fetch_all($detail_q, MYSQLI_ASSOC);
    }
}

$kelas_tampil = $header_data['kelas'] ?? (($santri_aktif && $santri_aktif['jenis_kelamin'] == 'P') ? 'Khusus Perempuan' : 'Khusus Laki-Laki');
$judul_header = $header_data['judul_materi'] ?? "Materi Tahfidz (Juz $juz)";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapor Tahfidz - <?= htmlspecialchars($santri_aktif['nama'] ?? 'Santri') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Arial:wght@400;700&display=swap');
        
        .kertas-rapor { 
            font-family: Arial, Helvetica, sans-serif; 
            color: black;
            background: white;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px;
        }
        
        /* Kop Surat persis gambar */
        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 2px solid black;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .kop-logo {
            width: 130px;
            text-align: center;
        }
        .kop-logo img {
            max-width: 100%;
            height: auto;
        }
        .kop-text {
            flex: 1;
            text-align: center;
        }
        
        /* Identitas Santri */
        .tabel-identitas {
            width: 100%;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .tabel-identitas td {
            padding: 2px 0;
        }
        
        /* Tabel Rapor */
        .tabel-rapor { 
            width: 100%; 
            border-collapse: collapse; 
            border: 2px solid black; 
        }
        .tabel-rapor th, .tabel-rapor td { 
            border: 1px solid black; 
            padding: 5px 8px; 
            font-size: 13px; 
            color: black; 
        }
        .tabel-rapor th {
            font-weight: bold;
            text-align: center;
        }

        @media print {
            @page { size: A4 portrait; margin: 1cm; }
            .no-print, aside, nav { display: none !important; }
            .ml-64 { margin-left: 0 !important; padding: 0 !important; }
            body { background: #fff !important; }
            .kertas-rapor { box-shadow: none !important; border: none !important; padding: 0 !important; max-width: 100%; }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        <!-- Toolbar Filter & Tombol Cetak -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-4 mb-6 flex flex-wrap items-end justify-between gap-4 no-print">
            <form method="GET" action="rapor_santri.php" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Santri</label>
                    <select name="id_santri" onchange="this.form.submit()" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
                        <?php foreach ($santri_list as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $s['id'] == $id_santri ? 'selected' : '' ?>><?= htmlspecialchars($s['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Juz</label>
                    <select name="juz" onchange="this.form.submit()" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
                        <?php for ($j = 1; $j <= 30; $j++): ?>
                            <option value="<?= $j ?>" <?= $j == $juz ? 'selected' : '' ?>>Juz <?= $j ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Semester</label>
                    <select name="semester" onchange="this.form.submit()" class="px-3 py-2 border border-slate-300 rounded-lg text-sm">
                        <option value="1 (Satu)" <?= $semester == '1 (Satu)' ? 'selected' : '' ?>>1 (Satu)</option>
                        <option value="2 (Dua)" <?= $semester == '2 (Dua)' ? 'selected' : '' ?>>2 (Dua)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">T. Pelajaran</label>
                    <input type="text" name="tahun_ajaran" value="<?= htmlspecialchars($tahun_ajaran) ?>" class="px-3 py-2 border border-slate-300 rounded-lg text-sm w-24">
                </div>
                <button type="submit" class="bg-slate-800 text-white px-4 py-2 rounded-lg text-sm font-medium">Tampilkan</button>
            </form>

            <div class="flex gap-2">
                <?php if (in_array($role, ['ustadz', 'pimpinan']) && $santri_aktif): ?>
                <a href="input_rapor.php?id_santri=<?= $id_santri ?>&juz=<?= $juz ?>&semester=<?= urlencode($semester) ?>&tahun_ajaran=<?= urlencode($tahun_ajaran) ?>" 
                   class="bg-amber-500 hover:bg-amber-600 text-white font-medium py-2 px-4 rounded-lg text-sm flex items-center gap-2">
                    <i data-lucide="edit" class="w-4 h-4"></i> Edit Nilai
                </a>
                <?php endif; ?>
                <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg text-sm flex items-center gap-2">
                    <i data-lucide="printer" class="w-4 h-4"></i> Cetak Rapor
                </button>
            </div>
        </div>

        <!-- LEMBAR CETAK RAPOR -->
        <div class="kertas-rapor shadow-md border border-slate-200">
            
            <!-- KOP SURAT PERSIS EXCEL -->
            <div class="kop-surat">
                <div class="kop-logo">
                    <!-- File logo.png di folder assets -->
                    <img src="assets/logo.png" alt="Logo Surau Tahfidz" onerror="this.style.display='none'">
                </div>
                <div class="kop-text">
                    <h1 class="text-lg font-bold uppercase tracking-wide">LAPORAN PENILAIAN HAFALAN SANTRI</h1>
                    <h2 class="text-xl font-bold uppercase tracking-wide">SURAU TAHFIDZUL QUR'AN MUSHALLA FIRDAUS</h2>
                    <p class="italic text-sm mt-1">Jorong Jalikur Patanangan Kenagarian Koto Tangah Kec. Tilatang Kamang Kab. Agam</p>
                    <div class="flex justify-between text-xs font-semibold mt-1 px-4">
                        <span>Hp: 0853-7597-2939</span>
                        <span>Kode Pos: 26152</span>
                    </div>
                </div>
            </div>

            <!-- IDENTITAS SANTRI -->
            <table class="tabel-identitas">
                <tr>
                    <td style="width: 80px;">Nama</td>
                    <td style="width: 15px;">:</td>
                    <td style="width: 45%;"><?= htmlspecialchars($santri_aktif['nama'] ?? '-') ?></td>
                    
                    <td style="width: 100px;">Semester</td>
                    <td style="width: 15px;">:</td>
                    <td><?= htmlspecialchars($semester) ?></td>
                </tr>
                <tr>
                    <td>Kelas</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($kelas_tampil) ?></td>
                    
                    <td>T Pelajaran</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($tahun_ajaran) ?></td>
                </tr>
                <tr>
                    <td>No. Induk</td>
                    <td>:</td>
                    <td><?= htmlspecialchars($santri_aktif['nis'] ?? '-') ?></td>
                    
                    <td>Juz</td>
                    <td>:</td>
                    <td><?= $juz ?></td>
                </tr>
            </table>

            <!-- TABEL NILAI -->
            <table class="tabel-rapor">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 40px;">NO</th>
                        <th rowspan="2"><?= htmlspecialchars($judul_header) ?></th>
                        <th colspan="2">Nilai</th>
                        <th rowspan="2" style="width: 140px;">Keterangan</th>
                    </tr>
                    <tr>
                        <th style="width: 80px;">Angka</th>
                        <th style="width: 90px;">Prediket</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_nilai = 0;
                    $jumlah_materi_bernilai = 0;

                    if (count($detail_data) > 0) {
                        foreach ($detail_data as $index => $d) {
                            $has_val = ($d['nilai'] !== null && $d['nilai'] !== '');
                            $angka   = $has_val ? (int) $d['nilai'] : null;

                            if ($has_val) {
                                $total_nilai += $angka;
                                $jumlah_materi_bernilai++;
                                if ($angka >= 98) { $prediket = 'A'; $ket = 'Baik'; } 
                                elseif ($angka >= 95) { $prediket = 'B'; $ket = 'Diterima'; } 
                                else { $prediket = 'C'; $ket = 'Gagal'; }
                            } else {
                                $prediket = '-'; $ket = '-';
                            }
                            ?>
                            <tr>
                                <td class="text-center"><?= $d['urutan'] ?></td>
                                <td><?= htmlspecialchars($d['nama_materi']) ?></td>
                                <td class="text-center"><?= $has_val ? $angka : '' ?></td>
                                <td class="text-center"><?= $has_val ? $prediket : '' ?></td>
                                <td class="text-center"><?= $has_val ? $ket : '' ?></td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="5" class="text-center py-4">Data nilai belum diinput.</td></tr>';
                    }

                    $rata_rata = $jumlah_materi_bernilai > 0 ? ($total_nilai / $jumlah_materi_bernilai) : 0;
                    $terbilang_total = $total_nilai > 0 ? terbilang_indonesia($total_nilai) : '-';
                    $terbilang_rata  = $rata_rata > 0 ? terbilang_indonesia(floor($rata_rata)) : '-';
                    ?>

                    <tr style="font-weight: bold;">
                        <td colspan="2">Total Nilai</td>
                        <td class="text-center"><?= $total_nilai > 0 ? $total_nilai : 0 ?></td>
                        <td colspan="2" class="text-center" style="font-style: italic; font-size: 11px; font-weight: normal;"><?= $terbilang_total ?></td>
                    </tr>
                    <tr style="font-weight: bold;">
                        <td colspan="2">Rata-Rata Nilai Tahfidz</td>
                        <td class="text-center"><?= $rata_rata > 0 ? number_format($rata_rata, 2, ',', '.') : 0 ?></td>
                        <td colspan="2" class="text-center" style="font-style: italic; font-size: 11px; font-weight: normal;"><?= $terbilang_rata ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- TANDA TANGAN -->
            <div style="display: flex; justify-content: space-between; margin-top: 30px; text-align: center; font-size: 13px;">
                <div style="width: 40%;">
                    <p style="margin-bottom: 70px;">Mengetahui,<br>Orang Tua / Wali Santri</p>
                    <p style="font-weight: bold; text-decoration: underline;">( <?= htmlspecialchars($santri_aktif['nama_ortu'] ?? '...................................') ?> )</p>
                </div>
                <div style="width: 40%;">
                    <p style="margin-bottom: 70px;">Jorong Jalikur Patanangan, <?= date('d F Y') ?><br>Ustadz Pembimbing / Pimpinan</p>
                    <p style="font-weight: bold; text-decoration: underline;">( ................................... )</p>
                </div>
            </div>

        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>