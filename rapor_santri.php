<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role    = $_SESSION['role'];
$user_id = (int) $_SESSION['user_id'];

// Fungsi konversi angka ke kata Terbilang Bahasa Indonesia
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

// Daftar 37 Surat Juz 30 untuk fallback tampilan jika belum diinput
$default_surat_juz30 = [
    "An- Naba'", "An- Nazi'at", "Abasa", "At- Takwir", "Al- Infithar",
    "Al- Muthaffifin", "Al- Insyiqaq", "Al- Buruj", "Ath- Thariq", "Al-A'la",
    "Al- Ghasyiyyah", "Al- Fajr", "Al- Balad", "Asy- Syams", "Al- Lail",
    "Ad- Dhuha", "Asy- Syarth", "At- Tiin", "Al-'Alaq", "Al-Qadr",
    "Al- Bayyinah", "Az- Zalzalah", "Al- 'Adiyat", "Al- Qari'ah", "At- Takatsur",
    "Al- 'Ashr", "Al- Humazah", "Al- Fiil", "Quraisy", "Al- Ma'un",
    "Al- Kautsar", "Al- Kafirun", "An- Nashr", "Al- Lahab", "Al- Ikhlash",
    "Al- Falaq", "An- Naas"
];

// Ambil daftar santri beserta Lokal/Kelas & Ustadz Pengampunya
$where_ortu = ($role === 'ortu') ? "WHERE s.id_user_ortu = $user_id" : "";
$santri_query = "SELECT s.*, h.nama_halaqah, h.keterangan as ket_lokal,
                        COALESCE(u.nama_lengkap, h.nama_pengajar) as pengajar_lokal
                 FROM santri s
                 LEFT JOIN halaqah h ON s.id_halaqah = h.id
                 LEFT JOIN users u ON h.id_ustadz = u.id
                 $where_ortu
                 ORDER BY s.nama ASC";
$santri_res = mysqli_query($conn, $santri_query);
$santri_list = mysqli_fetch_all($santri_res, MYSQLI_ASSOC);

$id_santri    = isset($_GET['id_santri']) ? (int) $_GET['id_santri'] : ($santri_list[0]['id'] ?? 0);
// Dukungan parameter mode_juz ('30', '1-2', '2-3', atau '1', '2', dst.)
$mode_juz     = $_GET['mode_juz'] ?? ($_GET['juz'] ?? '30');
$semester     = isset($_GET['semester']) ? $_GET['semester'] : '1 (Satu)';
$tahun_ajaran = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : date('Y');

$santri_aktif = null;
foreach ($santri_list as $s) {
    if ((int)$s['id'] === $id_santri) {
        $santri_aktif = $s;
        break;
    }
}

// Parse daftar Juz yang akan ditampilkan (Bisa 1 Juz seperti [30] atau 2 Juz seperti [1, 2])
$juz_tampil_list = [];
if (strpos($mode_juz, '-') !== false) {
    $parts = explode('-', $mode_juz);
    $juz_tampil_list[] = (int) $parts[0];
    $juz_tampil_list[] = (int) $parts[1];
} else {
    $juz_tampil_list[] = (int) $mode_juz;
}

// Fungsi ambil data 1 Juz dari database (beserta fallback 37 surat atau 20 pojok)
function ambilDataRaporJuz($conn, $id_santri, $juz_num, $semester, $tahun_ajaran, $default_surat_juz30) {
    $sem_esc = mysqli_real_escape_string($conn, $semester);
    $thn_esc = mysqli_real_escape_string($conn, $tahun_ajaran);
    
    $header = null;
    $details = [];
    $cek_tbl = mysqli_query($conn, "SHOW TABLES LIKE 'rapor_tahfidz'");
    if ($cek_tbl && mysqli_num_rows($cek_tbl) > 0 && $id_santri > 0) {
        $q_h = mysqli_query($conn, "SELECT * FROM rapor_tahfidz WHERE id_santri = $id_santri AND juz = $juz_num AND semester = '$sem_esc' AND tahun_ajaran = '$thn_esc' LIMIT 1");
        if ($q_h) {
            $header = mysqli_fetch_assoc($q_h);
            if ($header) {
                $q_d = mysqli_query($conn, "SELECT * FROM rapor_tahfidz_detail WHERE id_rapor = {$header['id']} ORDER BY urutan ASC");
                $details = mysqli_fetch_all($q_d, MYSQLI_ASSOC);
            }
        }
    }

    // Jika belum ada detail di DB, bangun kerangka baris default (37 surat utk Juz 30, 20 pojok utk lainnya)
    if (empty($details)) {
        if ($juz_num === 30) {
            foreach ($default_surat_juz30 as $idx => $srt) {
                $details[] = ['urutan' => $idx + 1, 'nama_materi' => $srt, 'nilai' => null];
            }
        } else {
            for ($i = 1; $i <= 20; $i++) {
                $hal = ($juz_num - 1) * 20 + $i + 1;
                $materi = ($juz_num === 1 && $i === 1) ? "Al-fatihah + Halaman 2" : "Halaman $hal";
                $details[] = ['urutan' => $i, 'nama_materi' => $materi, 'nilai' => null];
            }
        }
    }

    return ['header' => $header, 'details' => $details];
}

$rapor_per_juz = [];
$header_utama  = null;
foreach ($juz_tampil_list as $jz) {
    $data_jz = ambilDataRaporJuz($conn, $santri_aktif['id'] ?? 0, $jz, $semester, $tahun_ajaran, $default_surat_juz30);
    $rapor_per_juz[$jz] = $data_jz;
    if (!$header_utama && $data_jz['header']) {
        $header_utama = $data_jz['header'];
    }
}

// Kelas Otomatis dari Lokal Halaqah Santri
if (!empty($santri_aktif['nama_halaqah'])) {
    $kelas_tampil = $santri_aktif['nama_halaqah'];
} elseif (!empty($header_utama['kelas'])) {
    $kelas_tampil = $header_utama['kelas'];
} else {
    $kelas_tampil = ($santri_aktif && $santri_aktif['jenis_kelamin'] === 'P') ? 'Khusus Perempuan' : 'Khusus Laki-Laki';
}

$wali_kelas_tampil = !empty($header_utama['wali_kelas']) ? $header_utama['wali_kelas'] : (!empty($santri_aktif['pengajar_lokal']) ? $santri_aktif['pengajar_lokal'] : 'Ust. Hadi Latif, M.Pd');
$kepala_stq_tampil = !empty($header_utama['kepala_stq']) ? $header_utama['kepala_stq'] : 'Ust. Aipda Syaiful Hendra';
$tempat_tampil     = !empty($header_utama['tempat_cetak']) ? $header_utama['tempat_cetak'] : 'Jalikur Patanangan';
$tanggal_tampil    = !empty($header_utama['tanggal_cetak']) ? $header_utama['tanggal_cetak'] : date('d F Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapor Tahfidz (Juz <?= htmlspecialchars($mode_juz) ?>) - <?= htmlspecialchars($santri_aktif['nama'] ?? 'Santri') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .kertas-rapor { 
            font-family: 'Times New Roman', Times, serif; 
            color: #000;
            background: #fff;
            max-width: 820px;
            margin: 0 auto;
            padding: 32px 40px;
        }
        .kop-surat {
            display: flex;
            align-items: center;
            border-bottom: 3px double #000;
            padding-bottom: 6px;
            margin-bottom: 12px;
        }
        .kop-logo { width: 115px; text-align: center; }
        .kop-logo img { max-width: 100%; height: auto; }
        .kop-text { flex: 1; text-align: center; }
        
        .tabel-identitas {
            width: 100%;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .tabel-identitas td { padding: 1.5px 0; }
        
        .tabel-rapor { 
            width: 100%; 
            border-collapse: collapse; 
            border: 2px solid #000; 
            margin-bottom: 14px;
        }
        .tabel-rapor th, .tabel-rapor td { 
            border: 1px solid #000; 
            padding: 3px 7px; 
            font-size: 12px; 
            color: #000; 
        }
        .tabel-rapor th { font-weight: bold; text-align: center; background-color: #f8fafc; }

        .tabel-mini {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            font-size: 11.5px;
        }
        .tabel-mini th, .tabel-mini td {
            border: 1px solid #000;
            padding: 2.5px 6px;
        }

        @media print {
            @page { size: A4 portrait; margin: 0.8cm 1.2cm; }
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
        
        <!-- TOOLBAR FILTER & PILIHAN MODE RAPOR -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 mb-6 flex flex-wrap items-end justify-between gap-4 no-print">
            <form method="GET" action="rapor_santri.php" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Santri</label>
                    <select name="id_santri" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold">
                        <?php foreach ($santri_list as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (int)$s['id'] === $id_santri ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nama']) ?> (<?= htmlspecialchars($s['nama_halaqah'] ?: 'Tanpa Lokal') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Pilih Format / Juz Rapor</label>
                    <select name="mode_juz" onchange="this.form.submit()" class="px-3 py-2 bg-indigo-50 border border-indigo-300 text-indigo-900 rounded-xl text-xs font-bold">
                        <option value="30" <?= $mode_juz === '30' ? 'selected' : '' ?>>★ Rapor Khusus Juz 30 (37 Surat)</option>
                        <optgroup label="Rapor Gabungan 2 Juz (2 Tabel / Halaman)">
                            <?php for ($g = 1; $g <= 29; $g++): 
                                $pair = $g . '-' . ($g + 1);
                            ?>
                                <option value="<?= $pair ?>" <?= $mode_juz === $pair ? 'selected' : '' ?>>Juz <?= $pair ?> (20 Pojok + 20 Pojok)</option>
                            <?php endfor; ?>
                        </optgroup>
                        <optgroup label="Rapor 1 Juz Tunggal (20 Pojok)">
                            <?php for ($j = 1; $j <= 29; $j++): ?>
                                <option value="<?= $j ?>" <?= (string)$mode_juz === (string)$j ? 'selected' : '' ?>>Juz <?= $j ?> Saja (20 Pojok)</option>
                            <?php endfor; ?>
                        </optgroup>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Semester</label>
                    <select name="semester" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold">
                        <option value="1 (Satu)" <?= $semester === '1 (Satu)' ? 'selected' : '' ?>>1 (Satu)</option>
                        <option value="2 (Dua)" <?= $semester === '2 (Dua)' ? 'selected' : '' ?>>2 (Dua)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">T. Pelajaran</label>
                    <input type="text" name="tahun_ajaran" value="<?= htmlspecialchars($tahun_ajaran) ?>" class="px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold w-24">
                </div>

                <button type="submit" class="bg-slate-900 text-white px-4 py-2 rounded-xl text-xs font-semibold">Tampilkan</button>
            </form>

            <div class="flex gap-2">
                <?php if (in_array($role, ['ustadz', 'pimpinan']) && $santri_aktif): ?>
                <a href="input_rapor.php?id_santri=<?= $id_santri ?>&juz=<?= $juz_tampil_list[0] ?>&semester=<?= urlencode($semester) ?>&tahun_ajaran=<?= urlencode($tahun_ajaran) ?>" 
                   class="bg-amber-500 hover:bg-amber-600 text-white font-semibold py-2 px-4 rounded-xl text-xs flex items-center gap-1.5">
                    <i data-lucide="edit-3" class="w-4 h-4"></i> Input / Edit Leger Juz <?= $juz_tampil_list[0] ?>
                </a>
                <?php if (isset($juz_tampil_list[1])): ?>
                <a href="input_rapor.php?id_santri=<?= $id_santri ?>&juz=<?= $juz_tampil_list[1] ?>&semester=<?= urlencode($semester) ?>&tahun_ajaran=<?= urlencode($tahun_ajaran) ?>" 
                   class="bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2 px-4 rounded-xl text-xs flex items-center gap-1.5">
                    <i data-lucide="edit-3" class="w-4 h-4"></i> Edit Juz <?= $juz_tampil_list[1] ?>
                </a>
                <?php endif; ?>
                <?php endif; ?>
                <button onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-xl text-xs flex items-center gap-1.5 shadow-sm">
                    <i data-lucide="printer" class="w-4 h-4"></i> Cetak Rapor
                </button>
            </div>
        </div>

        <!-- LEMBAR CETAK RAPOR -->
        <div class="kertas-rapor shadow-md border border-slate-200">
            
            <!-- KOP SURAT -->
            <div class="kop-surat">
                <div class="kop-logo">
                    <img src="assets/logo.png" alt="Logo Surau" onerror="this.style.display='none'">
                </div>
                <div class="kop-text">
                    <h1 style="font-size: 16px; font-weight: bold; text-transform: uppercase;">LAPORAN PENILAIAN HAFALAN SANTRI</h1>
                    <h2 style="font-size: 18px; font-weight: bold; text-transform: uppercase;">SURAU TAHFIDZUL QUR'AN MUSHALLA FIRDAUS</h2>
                    <p style="font-size: 12px; font-style: italic; margin-top: 2px;">Jorong Jalikur Patanangan Kenagarian Koto Tangah Kec. Tilatang Kamang Kab. Agam</p>
                    <div style="display: flex; justify-content: space-between; font-size: 11.5px; font-weight: bold; margin-top: 3px; padding: 0 20px;">
                        <span>Hp: 0853-7597-2939</span>
                        <span>Kode Pos: 26152</span>
                    </div>
                </div>
            </div>

            <!-- IDENTITAS SANTRI -->
            <table class="tabel-identitas">
                <tr>
                    <td style="width: 80px;">Nama</td>
                    <td style="width: 12px;">:</td>
                    <td style="width: 48%;"><?= htmlspecialchars($santri_aktif['nama'] ?? '-') ?></td>
                    
                    <td style="width: 95px;">Semester</td>
                    <td style="width: 12px;">:</td>
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
                    <td><?= htmlspecialchars($mode_juz) ?></td>
                </tr>
            </table>

            <!-- LOOP TABEL MATERI (1 TABEL UNTUK JUZ 30 / TUNGGAL, ATAU 2 TABEL ATAS-BAWAH UNTUK JUZ 1-2, 2-3, DST.) -->
            <?php foreach ($juz_tampil_list as $jz_num): 
                $blok = $rapor_per_juz[$jz_num];
                $h_jz = $blok['header'];
                $d_jz = $blok['details'];
                $judul_kolom = !empty($h_jz['judul_materi']) ? $h_jz['judul_materi'] : "Materi Tahfidz (Juz $jz_num)";
                $pembagi_default = ($jz_num === 30) ? 37 : 20;
            ?>
            <table class="tabel-rapor">
                <thead>
                    <tr>
                        <th rowspan="2" style="width: 38px;">NO</th>
                        <th rowspan="2"><?= htmlspecialchars($judul_kolom) ?></th>
                        <th colspan="2">Nilai</th>
                        <th rowspan="2" style="width: 135px;">Keterangan</th>
                    </tr>
                    <tr>
                        <th style="width: 75px;">Angka</th>
                        <th style="width: 85px;">Prediket</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_nilai = 0;
                    $jml_terisi  = 0;

                    foreach ($d_jz as $idx => $baris):
                        $raw_val = $baris['nilai'];
                        $has_val = ($raw_val !== null && $raw_val !== '');
                        $angka   = $has_val ? (int) $raw_val : null;

                        if ($has_val) {
                            $total_nilai += $angka;
                            $jml_terisi++;
                            // Rumus Excel Surau: >=90 = A (Baik), >=85 = B (Diterima), <=84 = C (Gagal)
                            if ($angka >= 90) {
                                $prediket = 'A'; $ket = 'Baik';
                            } elseif ($angka >= 85) {
                                $prediket = 'B'; $ket = 'Diterima';
                            } else {
                                $prediket = 'C'; $ket = 'Gagal';
                            }
                        } else {
                            $prediket = ''; $ket = '';
                        }
                    ?>
                    <tr>
                        <td style="text-align: center;"><?= $baris['urutan'] ?? ($idx + 1) ?></td>
                        <td><?= htmlspecialchars($baris['nama_materi']) ?></td>
                        <td style="text-align: center;"><?= $has_val ? $angka : '' ?></td>
                        <td style="text-align: center;"><?= $prediket ?></td>
                        <td style="text-align: center;"><?= $ket ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php 
                    $pembagi = $jml_terisi > 0 ? $jml_terisi : $pembagi_default;
                    $rata_rata = $total_nilai > 0 ? ($total_nilai / $pembagi) : 0;
                    $terbilang_total = $total_nilai > 0 ? terbilang_indonesia($total_nilai) : '-';
                    $terbilang_rata  = $rata_rata > 0 ? terbilang_indonesia(round($rata_rata)) : '-';
                    ?>
                    <tr style="font-weight: bold;">
                        <td colspan="2">Total Nilai</td>
                        <td style="text-align: center;"><?= $total_nilai > 0 ? $total_nilai : '' ?></td>
                        <td colspan="2" style="text-align: center; font-style: italic; font-size: 11px;"><?= $terbilang_total ?></td>
                    </tr>
                    <tr style="font-weight: bold;">
                        <td colspan="2"><?= $jz_num === 30 ? 'Rata-Rata Nilai' : 'Rata-Rata Nilai Tahfidz' ?></td>
                        <td style="text-align: center;"><?= $rata_rata > 0 ? round($rata_rata, 2) : '' ?></td>
                        <td colspan="2" style="text-align: center; font-style: italic; font-size: 11px;"><?= $terbilang_rata ?></td>
                    </tr>
                </tbody>
            </table>
            <?php endforeach; ?>

            <!-- BAGIAN KHUSUS RAPOR JUZ 30: TABEL UJIAN JUZ, TAHSIN, KEPRIBADIAN, ABSENSI & CATATAN -->
            <?php if ((int)$juz_tampil_list[0] === 30): 
                $u30 = $header_utama['ujian_juz_30'] ?? null;
                $u1  = $header_utama['ujian_juz_1'] ?? null;
                $u2  = $header_utama['ujian_juz_2'] ?? null;
                $u3  = $header_utama['ujian_juz_3'] ?? null;
                $u4  = $header_utama['ujian_juz_4'] ?? null;
                $arr_ujian = array_filter([$u30, $u1, $u2, $u3, $u4], fn($v) => $v !== null && $v !== '');
                $jml_ujian = array_sum($arr_ujian);
                $rata_ujian = count($arr_ujian) > 0 ? round($jml_ujian / 5, 1) : 0;
            ?>
            <div style="display: flex; justify-content: space-between; gap: 20px; margin-top: 8px; margin-bottom: 12px;">
                <!-- Tabel Ujian Lintas Juz -->
                <div style="width: 48%;">
                    <table class="tabel-mini">
                        <thead>
                            <tr style="background: #f8fafc; font-weight: bold; text-align: center;">
                                <th>Ujian</th>
                                <th style="width: 90px;">Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>Juz 30</td><td style="text-align: center;"><?= $u30 ?? '-' ?></td></tr>
                            <tr><td>Juz 1</td><td style="text-align: center;"><?= $u1 ?? '-' ?></td></tr>
                            <tr><td>Juz 2</td><td style="text-align: center;"><?= $u2 ?? '-' ?></td></tr>
                            <tr><td>Juz 3</td><td style="text-align: center;"><?= $u3 ?? '-' ?></td></tr>
                            <tr><td>Juz 4</td><td style="text-align: center;"><?= $u4 ?? '-' ?></td></tr>
                            <tr style="font-weight: bold;"><td>JUMLAH</td><td style="text-align: center;"><?= $jml_ujian > 0 ? $jml_ujian : '-' ?></td></tr>
                            <tr style="font-weight: bold;"><td>RATA-RATA</td><td style="text-align: center;"><?= $rata_ujian > 0 ? $rata_ujian : '-' ?></td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Nilai Tahsin, Naik ke Juz & Remedi -->
                <div style="width: 48%; font-size: 12.5px; font-weight: bold; padding-top: 4px;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="padding: 4px 0; width: 120px;">Nilai Tahsin</td>
                            <td style="width: 15px;">:</td>
                            <td><?= htmlspecialchars((string)($header_utama['nilai_tahsin'] ?? '-')) ?></td>
                        </tr>
                        <tr><td colspan="3" style="height: 10px;"></td></tr>
                        <tr>
                            <td style="padding: 4px 0;">Naik ke Juz</td>
                            <td>:</td>
                            <td><?= htmlspecialchars($header_utama['naik_juz'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td style="padding: 4px 0;">Remedi di Juz</td>
                            <td>:</td>
                            <td><?= htmlspecialchars($header_utama['remedi_juz'] ?? '-') ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Keterangan Predikat, Kepribadian & Absensi -->
            <p style="font-size: 12px; font-weight: bold; margin-bottom: 4px;">Keterangan:</p>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <table class="tabel-mini">
                    <thead><tr style="background:#f8fafc;"><th colspan="3">Predikat</th></tr></thead>
                    <tbody>
                        <tr><td>Baik</td><td style="text-align:center;">90-100</td><td style="text-align:center; font-weight:bold;">A</td></tr>
                        <tr><td>Diterima</td><td style="text-align:center;">85-89</td><td style="text-align:center; font-weight:bold;">B</td></tr>
                        <tr><td>Gagal</td><td style="text-align:center;">&le; 84</td><td style="text-align:center; font-weight:bold;">C</td></tr>
                    </tbody>
                </table>

                <table class="tabel-mini">
                    <thead><tr style="background:#f8fafc;"><th colspan="2">Kepribadian</th></tr></thead>
                    <tbody>
                        <tr><td>Akhlak</td><td style="text-align:center; font-weight:bold; width:45px;"><?= htmlspecialchars($header_utama['akhlak'] ?? 'A') ?></td></tr>
                        <tr><td>Kerajinan</td><td style="text-align:center; font-weight:bold;"><?= htmlspecialchars($header_utama['kerajinan'] ?? 'B') ?></td></tr>
                        <tr><td>Kebersihan</td><td style="text-align:center; font-weight:bold;"><?= htmlspecialchars($header_utama['kebersihan'] ?? 'A') ?></td></tr>
                        <tr><td>Kerapian</td><td style="text-align:center; font-weight:bold;"><?= htmlspecialchars($header_utama['kerapian'] ?? 'A') ?></td></tr>
                    </tbody>
                </table>

                <table class="tabel-mini">
                    <thead><tr style="background:#f8fafc;"><th colspan="2">Absensi</th></tr></thead>
                    <tbody>
                        <tr><td>Sakit</td><td style="text-align:center; width:75px;"><?= (int)($header_utama['sakit'] ?? 0) ?> Hari</td></tr>
                        <tr><td>Izin</td><td style="text-align:center;"><?= (int)($header_utama['izin'] ?? 0) ?> Hari</td></tr>
                        <tr><td>Tanpa Keterangan</td><td style="text-align:center;"><?= (int)($header_utama['alfa'] ?? 0) ?> Hari</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Kotak Catatan Orang Tua -->
            <div style="margin-bottom: 14px;">
                <p style="font-size: 12px; font-weight: bold; margin-bottom: 3px;">Catatan untuk orang tua/wali:</p>
                <div style="border: 1.5px solid #000; min-height: 48px; padding: 6px 10px; font-size: 12px;">
                    <?= nl2br(htmlspecialchars($header_utama['catatan_ortu'] ?? '')) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- BLOK TANDA TANGAN RESMI SURAU -->
            <div style="font-size: 12.5px; margin-top: 14px;">
                <div style="display: flex; justify-content: flex-end; margin-bottom: 8px;">
                    <table style="width: 42%; font-size: 12px;">
                        <tr>
                            <td style="width: 90px;">Diberikan di</td>
                            <td style="width: 10px;">:</td>
                            <td><?= htmlspecialchars($tempat_tampil) ?></td>
                        </tr>
                        <tr>
                            <td>Tanggal</td>
                            <td>:</td>
                            <td><?= htmlspecialchars($tanggal_tampil) ?></td>
                        </tr>
                    </table>
                </div>

                <div style="display: flex; justify-content: space-between; text-align: center;">
                    <div style="width: 42%;">
                        <p style="margin-bottom: 55px;">Orang Tua/ Wali Santri,</p>
                        <p style="font-weight: bold; text-decoration: underline;">
                            <?= !empty($santri_aktif['nama_ortu']) ? htmlspecialchars($santri_aktif['nama_ortu']) : '________________________' ?>
                        </p>
                    </div>
                    <div style="width: 42%;">
                        <p style="margin-bottom: 55px;">Wali Kelas,</p>
                        <p style="font-weight: bold; text-decoration: underline;"><?= htmlspecialchars($wali_kelas_tampil) ?></p>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 18px;">
                    <p>Mengetahui,</p>
                    <p style="margin-bottom: 55px;">Kepala STQ- MF</p>
                    <p style="font-weight: bold; text-decoration: underline;"><?= htmlspecialchars($kepala_stq_tampil) ?></p>
                </div>
            </div>

        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>