<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';

// 🔒 Keamanan: Hanya untuk Role Orang Tua (ortu)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ortu') {
    header("Location: login.php");
    exit;
}

$id_ortu = (int) $_SESSION['user_id'];

// Ambil daftar anak yang terhubung dengan akun orang tua ini
$anak_query = "SELECT * FROM santri WHERE id_user_ortu = $id_ortu ORDER BY nama ASC";
$anak_list = mysqli_fetch_all(mysqli_query($conn, $anak_query), MYSQLI_ASSOC);

// Pilih anak aktif (default: anak pertama)
$id_santri_pilih = isset($_GET['id_santri']) ? (int) $_GET['id_santri'] : ($anak_list[0]['id'] ?? 0);
$santri = null;
foreach ($anak_list as $a) {
    if ($a['id'] == $id_santri_pilih) {
        $santri = $a;
        break;
    }
}

// Filter Bulan & Tahun (Default: Bulan & Tahun Saat Ini)
$bulan_pilih = isset($_GET['bulan']) ? str_pad((int)$_GET['bulan'], 2, '0', STR_PAD_LEFT) : date('m');
$tahun_pilih = isset($_GET['tahun']) ? (int) $_GET['tahun'] : (int) date('Y');
$periode     = "$tahun_pilih-$bulan_pilih";

$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];

$Nama_Hari_Indo = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
];

$rekap = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];
$absensi_list = [];
$map_tanggal = []; // Untuk kalender visual

if ($santri) {
    $id_santri = (int) $santri['id'];

    // Ambil data absensi dari tabel `absensi` pada periode terpilih
    $q_absensi = "SELECT a.*, u.nama_lengkap as nama_ustadz 
                  FROM absensi a 
                  LEFT JOIN users u ON a.id_ustadz = u.id 
                  WHERE a.id_santri = $id_santri 
                    AND DATE_FORMAT(a.tanggal, '%Y-%m') = '$periode'
                  ORDER BY a.tanggal DESC, a.id DESC";
    $res_absensi = mysqli_query($conn, $q_absensi);
    if ($res_absensi) {
        $absensi_list = mysqli_fetch_all($res_absensi, MYSQLI_ASSOC);
    }

    foreach ($absensi_list as $row) {
        $inisial = strtoupper(substr(trim($row['status']), 0, 1));
        if (isset($rekap[$inisial])) {
            $rekap[$inisial]++;
        }
        $tgl_angka = (int) date('j', strtotime($row['tanggal']));
        $map_tanggal[$tgl_angka] = [
            'inisial'    => $inisial,
            'keterangan' => $row['keterangan'] ?? '',
            'ustadz'     => $row['nama_ustadz'] ?? 'Ustadz/ah'
        ];
    }
}

$total_pertemuan = array_sum($rekap);
$persentase_hadir = $total_pertemuan > 0 ? round(($rekap['H'] / $total_pertemuan) * 100) : 0;

// Konfigurasi Kalender Bulan Terpilih
$jumlah_hari_bulan = cal_days_in_month(CAL_GREGORIAN, (int)$bulan_pilih, $tahun_pilih);
$hari_pertama_minggu = (int) date('N', strtotime("$tahun_pilih-$bulan_pilih-01")); // 1 (Senin) s/d 7 (Minggu)
?>
<?php
$page_title = "Kehadiran Anak - Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- Header & Filter Periode -->
        <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 flex items-center gap-3">
                    <div class="p-2.5 bg-blue-100 rounded-xl">
                        <i data-lucide="calendar-check" class="w-7 h-7 text-blue-600"></i>
                    </div>
                    Kehadiran & Presensi Ananda
                </h1>
                <p class="text-sm text-slate-500 mt-2">Pantau rekapitulasi kehadiran harian dan kedisiplinan ananda di Surau Tahfizh Firdaus.</p>
            </div>

            <!-- Filter Anak, Bulan & Tahun -->
            <form method="GET" action="absensi_anak.php" class="bg-white border border-slate-200 p-2.5 rounded-2xl shadow-sm flex flex-wrap items-center gap-2">
                <?php if (count($anak_list) > 1): ?>
                    <select name="id_santri" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none cursor-pointer">
                        <?php foreach ($anak_list as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= $a['id'] == $id_santri_pilih ? 'selected' : '' ?>>
                                👦 <?= htmlspecialchars($a['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>

                <select name="bulan" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500 outline-none cursor-pointer">
                    <?php foreach ($nama_bulan as $num => $nama_bln): ?>
                        <option value="<?= $num ?>" <?= $num == $bulan_pilih ? 'selected' : '' ?>><?= $nama_bln ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="tahun" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:ring-2 focus:ring-blue-500 outline-none cursor-pointer">
                    <?php for ($th = date('Y') - 2; $th <= date('Y') + 1; $th++): ?>
                        <option value="<?= $th ?>" <?= $th == $tahun_pilih ? 'selected' : '' ?>><?= $th ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>

        <?php if (!$santri): ?>
            <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-6 rounded-2xl shadow-sm">
                <h3 class="font-bold text-base mb-1 flex items-center gap-2">
                    <i data-lucide="alert-triangle" class="w-5 h-5"></i> Data Anak Belum Terhubung
                </h3>
                <p class="text-xs">Akun orang tua Anda belum ditautkan dengan data santri. Silakan hubungi Ustadz/Ustadzah pembimbing.</p>
            </div>
        <?php else: ?>

        <!-- KARTU IDENTITAS & PERSENTASE KEHADIRAN -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <!-- Profil Ananda -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white font-extrabold text-2xl flex items-center justify-center shrink-0 shadow-md shadow-blue-100">
                    <?= strtoupper(substr($santri['nama'], 0, 1)) ?>
                </div>
                <div class="min-w-0">
                    <span class="text-[10px] font-extrabold uppercase tracking-wider text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-full border border-blue-200">
                        Periode <?= $nama_bulan[$bulan_pilih] ?> <?= $tahun_pilih ?>
                    </span>
                    <h2 class="text-xl font-extrabold text-slate-900 truncate mt-1.5"><?= htmlspecialchars($santri['nama']) ?></h2>
                    <p class="text-xs text-slate-500 mt-0.5">NIS: <?= htmlspecialchars($santri['nis'] ?: '-') ?> • <?= $santri['jenis_kelamin'] === 'L' ? 'Santriwan (Putra)' : 'Santriwati (Putri)' ?></p>
                </div>
            </div>

            <!-- Bar Persentase Kehadiran -->
            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                <div class="flex flex-wrap items-start justify-between gap-4 mb-3">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tingkat Kehadiran Bulan Ini</p>
                        <div class="flex items-center gap-2 mt-1">
                            <h3 class="text-2xl font-black text-slate-900"><?= $persentase_hadir ?>%</h3>
                            <?php if ($total_pertemuan === 0): ?>
                                <span class="text-xs font-semibold px-2.5 py-0.5 rounded-lg bg-slate-100 text-slate-500">Belum ada presensi</span>
                            <?php elseif ($persentase_hadir >= 85): ?>
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-lg bg-emerald-100 text-emerald-700">✨ Sangat Disiplin</span>
                            <?php elseif ($persentase_hadir >= 70): ?>
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-lg bg-amber-100 text-amber-700">👍 Cukup Baik</span>
                            <?php else: ?>
                                <span class="text-xs font-bold px-2.5 py-0.5 rounded-lg bg-rose-100 text-rose-700">⚠️ Perlu Perhatian</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-semibold text-slate-500">Total Hari Tercatat:</span>
                        <p class="text-lg font-extrabold text-slate-800"><?= $total_pertemuan ?> Pertemuan</p>
                    </div>
                </div>

                <div class="w-full h-3.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200 mb-2">
                    <div class="h-full bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full transition-all duration-500" style="width: <?= $persentase_hadir ?>%"></div>
                </div>

                <div class="flex justify-between text-xs text-slate-500">
                    <span>Hadir: <b class="text-emerald-600"><?= $rekap['H'] ?> Hari</b></span>
                    <span>Tidak Hadir (S/I/A): <b class="text-slate-700"><?= $rekap['S'] + $rekap['I'] + $rekap['A'] ?> Hari</b></span>
                </div>
            </div>
        </div>

        <!-- 4 KARTU RINGKASAN STATUS -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="bg-white border border-emerald-200 border-b-4 border-b-emerald-500 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Hadir (H)</p>
                    <p class="text-3xl font-black text-slate-900 mt-1"><?= $rekap['H'] ?></p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Mengikuti halaqah</p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="user-check" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="bg-white border border-blue-200 border-b-4 border-b-blue-500 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-blue-600 uppercase tracking-wider">Sakit (S)</p>
                    <p class="text-3xl font-black text-slate-900 mt-1"><?= $rekap['S'] ?></p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Izin karena sakit</p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center">
                    <i data-lucide="thermometer" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="bg-white border border-amber-200 border-b-4 border-b-amber-500 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-amber-600 uppercase tracking-wider">Izin (I)</p>
                    <p class="text-3xl font-black text-slate-900 mt-1"><?= $rekap['I'] ?></p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Keperluan keluarga/sekolah</p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="file-clock" class="w-6 h-6"></i>
                </div>
            </div>

            <div class="bg-white border border-rose-200 border-b-4 border-b-rose-500 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-rose-600 uppercase tracking-wider">Alfa (A)</p>
                    <p class="text-3xl font-black text-slate-900 mt-1"><?= $rekap['A'] ?></p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Tanpa keterangan</p>
                </div>
                <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center">
                    <i data-lucide="user-x" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <!-- GRID KALENDER VISUAL & TABEL LOG RIWAYAT -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- KALENDER VISUAL BULANAN -->
            <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm h-fit">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                            <i data-lucide="calendar-days" class="w-5 h-5 text-blue-600"></i>
                            Kalender <?= $nama_bulan[$bulan_pilih] ?> <?= $tahun_pilih ?>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Peta visual kehadiran harian</p>
                    </div>
                </div>

                <!-- Header Nama Hari -->
                <div class="grid grid-cols-7 gap-1.5 text-center text-[10px] font-bold uppercase text-slate-400 mb-2">
                    <div>Sen</div><div>Sel</div><div>Rab</div><div>Kam</div><div>Jum</div><div>Sab</div><div class="text-rose-400">Min</div>
                </div>

                <!-- Grid Tanggal -->
                <div class="grid grid-cols-7 gap-1.5 text-xs">
                    <?php
                    // Kotak kosong sebelum tanggal 1
                    for ($kosong = 1; $kosong < $hari_pertama_minggu; $kosong++) {
                        echo '<div class="h-10 rounded-lg bg-slate-50/50"></div>';
                    }

                    // Loop Tanggal 1 s/d akhir bulan
                    for ($tgl = 1; $tgl <= $jumlah_hari_bulan; $tgl++) {
                        $info = $map_tanggal[$tgl] ?? null;
                        $cls_box = 'bg-slate-50 text-slate-600 border-slate-200';
                        $title_tip = "Tanggal $tgl: Belum ada catatan";

                        if ($info) {
                            $st = $info['inisial'];
                            if ($st === 'H') {
                                $cls_box = 'bg-emerald-500 text-white border-emerald-600 font-bold shadow-sm';
                                $title_tip = "Tgl $tgl: HADIR";
                            } elseif ($st === 'S') {
                                $cls_box = 'bg-blue-500 text-white border-blue-600 font-bold shadow-sm';
                                $title_tip = "Tgl $tgl: SAKIT (" . ($info['keterangan'] ?: '-') . ")";
                            } elseif ($st === 'I') {
                                $cls_box = 'bg-amber-500 text-white border-amber-600 font-bold shadow-sm';
                                $title_tip = "Tgl $tgl: IZIN (" . ($info['keterangan'] ?: '-') . ")";
                            } elseif ($st === 'A') {
                                $cls_box = 'bg-rose-500 text-white border-rose-600 font-bold shadow-sm';
                                $title_tip = "Tgl $tgl: ALFA";
                            }
                        }

                        echo '<div title="' . htmlspecialchars($title_tip) . '" class="h-10 rounded-xl border flex flex-col items-center justify-center cursor-default transition ' . $cls_box . '">';
                        echo '<span>' . $tgl . '</span>';
                        if ($info) {
                            echo '<span class="text-[9px] leading-none opacity-90">' . $info['inisial'] . '</span>';
                        }
                        echo '</div>';
                    }
                    ?>
                </div>

                <!-- Legenda Warna -->
                <div class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-2 gap-2 text-[11px] text-slate-600">
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-emerald-500 inline-block"></span> Hadir (H)</div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-blue-500 inline-block"></span> Sakit (S)</div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-amber-500 inline-block"></span> Izin (I)</div>
                    <div class="flex items-center gap-2"><span class="w-3 h-3 rounded bg-rose-500 inline-block"></span> Alfa (A)</div>
                </div>
            </div>

            <!-- TABEL RINCIAN PRESENSI -->
            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex flex-wrap justify-between items-center gap-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <i data-lucide="list-checks" class="w-5 h-5 text-blue-600"></i> Rincian Log Kehadiran
                        </h3>
                        <p class="text-xs text-slate-500">Daftar presensi tercatat pada bulan <?= $nama_bulan[$bulan_pilih] ?> <?= $tahun_pilih ?></p>
                    </div>

                    <select id="filterStatus" onchange="filterLogAbsensi()" class="px-3 py-1.5 bg-white border border-slate-300 rounded-xl text-xs font-semibold text-slate-700 outline-none cursor-pointer">
                        <option value="ALL">Semua Status</option>
                        <option value="H">Hadir Saja</option>
                        <option value="S">Sakit Saja</option>
                        <option value="I">Izin Saja</option>
                        <option value="A">Alfa Saja</option>
                    </select>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-white text-slate-400 uppercase text-[11px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="py-3.5 px-6 font-semibold">Hari & Tanggal</th>
                                <th class="py-3.5 px-4 font-semibold text-center">Status</th>
                                <th class="py-3.5 px-4 font-semibold">Keterangan</th>
                                <th class="py-3.5 px-6 font-semibold">Dicatat Oleh</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-slate-100">
                            <?php if (empty($absensi_list)): ?>
                                <tr>
                                    <td colspan="4" class="py-12 text-center">
                                        <div class="flex flex-col items-center justify-center text-slate-400">
                                            <i data-lucide="calendar-off" class="w-10 h-10 mb-2 opacity-40"></i>
                                            <p class="font-medium text-slate-600">Belum ada catatan kehadiran di bulan ini</p>
                                            <p class="text-xs mt-1">Pilih bulan lain pada filter di kanan atas untuk melihat riwayat sebelumnya.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($absensi_list as $a): 
                                    $in = strtoupper(substr(trim($a['status']), 0, 1));
                                    if ($in === 'H') {
                                        $badge = 'bg-emerald-100 text-emerald-700 border-emerald-200';
                                        $label = 'Hadir';
                                    } elseif ($in === 'S') {
                                        $badge = 'bg-blue-100 text-blue-700 border-blue-200';
                                        $label = 'Sakit';
                                    } elseif ($in === 'I') {
                                        $badge = 'bg-amber-100 text-amber-700 border-amber-200';
                                        $label = 'Izin';
                                    } else {
                                        $badge = 'bg-rose-100 text-rose-700 border-rose-200';
                                        $label = 'Alfa';
                                    }

                                    $nama_hari_en = date('l', strtotime($a['tanggal']));
                                    $hari_indo    = $Nama_Hari_Indo[$nama_hari_en] ?? $nama_hari_en;
                                ?>
                                <tr class="baris-absensi hover:bg-slate-50/80 transition" data-status="<?= $in ?>">
                                    <td class="py-4 px-6">
                                        <p class="font-bold text-slate-800"><?= $hari_indo ?>, <?= date('d M Y', strtotime($a['tanggal'])) ?></p>
                                    </td>
                                    <td class="py-4 px-4 text-center">
                                        <span class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold border <?= $badge ?>">
                                            <?= $label ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-4 text-xs text-slate-600">
                                        <?= !empty($a['keterangan']) ? htmlspecialchars($a['keterangan']) : '<span class="text-slate-400 italic">Hadir tepat waktu / Tanpa catatan</span>' ?>
                                    </td>
                                    <td class="py-4 px-6 text-xs font-medium text-slate-500">
                                        <?= htmlspecialchars($a['nama_ustadz'] ?? 'Ustadz Pembimbing') ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <?php endif; ?>
    </div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>