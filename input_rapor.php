<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

// Daftar 37 Surat Juz 30 sesuai urutan di Excel Surau
$surat_juz_30 = [
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
require_once 'includes/init_rbac.php';
$where_binaan = getFilterSantriBinaanSQL($conn, $_SESSION['user_id'], $_SESSION['role'], 's');
$santri_query = "SELECT s.id, s.nama, s.nis, s.jenis_kelamin, s.id_halaqah, 
                        h.nama_halaqah, h.keterangan as ket_lokal,
                        COALESCE(u.nama_lengkap, h.nama_pengajar) as pengajar_lokal
                 FROM santri s
                 LEFT JOIN halaqah h ON s.id_halaqah = h.id
                 LEFT JOIN users u ON h.id_ustadz = u.id
                 $where_binaan
                 ORDER BY s.nama ASC";
$santri_res = mysqli_query($conn, $santri_query);
$santri_list = mysqli_fetch_all($santri_res, MYSQLI_ASSOC);

$lokal_res = mysqli_query($conn, "SELECT id, nama_halaqah, keterangan FROM halaqah ORDER BY id ASC");
$lokal_list = $lokal_res ? mysqli_fetch_all($lokal_res, MYSQLI_ASSOC) : [];

$id_santri    = isset($_GET['id_santri']) ? (int) $_GET['id_santri'] : ($santri_list[0]['id'] ?? 0);
$juz          = isset($_GET['juz']) ? (int) $_GET['juz'] : 30; // Default Juz 30
$semester     = isset($_GET['semester']) ? $_GET['semester'] : '1 (Satu)';
$tahun_ajaran = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : date('Y');

$santri_aktif = null;
foreach ($santri_list as $s) {
    if ((int)$s['id'] === $id_santri) {
        $santri_aktif = $s;
        break;
    }
}

// Ambil Data Rapor jika sudah pernah disimpan
$header_data = null;
$detail_data = [];
$absensi_db  = ['S' => 0, 'I' => 0, 'A' => 0];

if ($santri_aktif) {
    $sem_esc = mysqli_real_escape_string($conn, $semester);
    $thn_esc = mysqli_real_escape_string($conn, $tahun_ajaran);
    
    // Cek tabel rapor_tahfidz
    $cek_tbl = mysqli_query($conn, "SHOW TABLES LIKE 'rapor_tahfidz'");
    if ($cek_tbl && mysqli_num_rows($cek_tbl) > 0) {
        $header_q = mysqli_query($conn, "SELECT * FROM rapor_tahfidz WHERE id_santri = $id_santri AND juz = $juz AND semester = '$sem_esc' AND tahun_ajaran = '$thn_esc' LIMIT 1");
        if ($header_q) {
            $header_data = mysqli_fetch_assoc($header_q);
            if ($header_data) {
                $detail_q = mysqli_query($conn, "SELECT * FROM rapor_tahfidz_detail WHERE id_rapor = {$header_data['id']} ORDER BY urutan ASC");
                $detail_data = mysqli_fetch_all($detail_q, MYSQLI_ASSOC);
            }
        }
    }

    // Hitung otomatis Sakit, Izin, Alfa dari tabel absensi sebagai nilai default
    $q_abs = mysqli_query($conn, "SELECT status, COUNT(*) as jml FROM absensi WHERE id_santri = $id_santri GROUP BY status");
    if ($q_abs) {
        while ($ab = mysqli_fetch_assoc($q_abs)) {
            $in = strtoupper(substr(trim($ab['status']), 0, 1));
            if (isset($absensi_db[$in])) $absensi_db[$in] += (int)$ab['jml'];
        }
    }
}

// Kelas Otomatis dari Lokal Halaqah Santri
if (!empty($santri_aktif['nama_halaqah'])) {
    $default_kelas = $santri_aktif['nama_halaqah'];
} elseif (!empty($header_data['kelas'])) {
    $default_kelas = $header_data['kelas'];
} else {
    $default_kelas = ($santri_aktif && $santri_aktif['jenis_kelamin'] === 'P') ? 'Khusus Perempuan' : 'Khusus Laki-Laki';
}

$default_judul = $header_data['judul_materi'] ?? "Materi Tahfidz (Juz $juz)";
$default_wali  = $header_data['wali_kelas'] ?? ($santri_aktif['pengajar_lokal'] ?: 'Ust. Hadi Latif, M.Pd');

// Siapkan daftar baris materi default jika belum pernah disimpan di DB
$baris_form = [];
if (count($detail_data) > 0) {
    foreach ($detail_data as $d) {
        $baris_form[] = ['materi' => $d['nama_materi'], 'nilai' => $d['nilai']];
    }
} else {
    if ($juz === 30) {
        // Khusus Juz 30: Otomatis 37 Surat
        foreach ($surat_juz_30 as $nama_srt) {
            $baris_form[] = ['materi' => $nama_srt, 'nilai' => ''];
        }
    } else {
        // Selain Juz 30 (Juz 1 - 29): Otomatis 20 Pojok sesuai halaman Mushaf
        for ($i = 1; $i <= 20; $i++) {
            $hal = ($juz - 1) * 20 + $i + 1;
            $label_hal = ($juz === 1 && $i === 1) ? "Al-fatihah + Halaman 2" : "Halaman $hal";
            $baris_form[] = ['materi' => $label_hal, 'nilai' => ''];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Leger Rapor - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- HEADER -->
        <div class="flex flex-wrap justify-between items-start gap-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2.5">
                    <div class="p-2 bg-indigo-100 rounded-xl"><i data-lucide="table-2" class="w-6 h-6 text-indigo-600"></i></div>
                    Input Leger Nilai Rapor
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    Pilih <b>Juz 30</b> untuk format 37 Surat, atau pilih <b>Juz 1–29</b> untuk format 20 Pojok per Juz.
                </p>
            </div>

            <?php if ($id_santri > 0): ?>
            <div class="flex flex-wrap gap-2">
                <a href="rapor_santri.php?id_santri=<?= $id_santri ?>&mode_juz=<?= $juz ?>&semester=<?= urlencode($semester) ?>&tahun_ajaran=<?= urlencode($tahun_ajaran) ?>" 
                   class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 px-4 rounded-xl transition shadow-sm flex items-center gap-2 text-xs">
                    <i data-lucide="printer" class="w-4 h-4"></i> Cetak Rapor Juz <?= $juz ?>
                </a>
                <?php if ($juz < 30): 
                    $pasangan = ($juz % 2 == 1) ? "$juz-" . ($juz + 1) : ($juz - 1) . "-$juz";
                ?>
                <a href="rapor_santri.php?id_santri=<?= $id_santri ?>&mode_juz=<?= $pasangan ?>&semester=<?= urlencode($semester) ?>&tahun_ajaran=<?= urlencode($tahun_ajaran) ?>" 
                   class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-4 rounded-xl transition shadow-sm flex items-center gap-2 text-xs">
                    <i data-lucide="columns" class="w-4 h-4"></i> Cetak Gabungan Juz <?= $pasangan ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- NOTIFIKASI -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-6 text-xs font-medium flex items-center gap-2.5 shadow-sm">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <!-- FILTER PILIH SANTRI & JUZ -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-6">
            <form method="GET" action="input_rapor.php" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Pilih Santri</label>
                    <select name="id_santri" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                        <?php foreach ($santri_list as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (int)$s['id'] === $id_santri ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nama']) ?> — [<?= htmlspecialchars($s['nama_halaqah'] ?: 'Tanpa Lokal') ?>]
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="md:col-span-4">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Pilih Jenis / Juz Leger</label>
                    <select name="juz" onchange="this.form.submit()" class="w-full px-3.5 py-2.5 bg-indigo-50/70 border border-indigo-300 text-indigo-900 rounded-xl text-xs font-bold focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="30" <?= $juz === 30 ? 'selected' : '' ?>>★ Rapor Khusus Juz 30 (37 Surat)</option>
                        <optgroup label="Rapor Pojok 1-20 (Bisa Dicetak Gabungan 1-2, 2-3, dst.)">
                            <?php for ($j = 1; $j <= 29; $j++): 
                                $h_awal = ($j - 1) * 20 + 2;
                                $h_akhir = $j * 20 + 1;
                            ?>
                                <option value="<?= $j ?>" <?= $j === $juz ? 'selected' : '' ?>>
                                    Juz <?= $j ?> (20 Pojok: Hal <?= $h_awal ?> - <?= $h_akhir ?>)
                                </option>
                            <?php endfor; ?>
                        </optgroup>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Semester</label>
                    <select name="semester" onchange="this.form.submit()" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold outline-none">
                        <option value="1 (Satu)" <?= $semester === '1 (Satu)' ? 'selected' : '' ?>>1 (Satu)</option>
                        <option value="2 (Dua)" <?= $semester === '2 (Dua)' ? 'selected' : '' ?>>2 (Dua)</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tahun Pelajaran</label>
                    <input type="text" name="tahun_ajaran" value="<?= htmlspecialchars($tahun_ajaran) ?>" onchange="this.form.submit()" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold outline-none">
                </div>
            </form>
        </div>

        <?php if ($santri_aktif): ?>
        <!-- PINTASAN ANTAR JUZ BERPASANGAN (Contoh: Sedang di Juz 1, tombol cepat ke Juz 2) -->
        <?php if ($juz < 30): 
            $juz_sebelah = ($juz % 2 == 1) ? $juz + 1 : $juz - 1;
        ?>
        <div class="bg-indigo-900 text-white rounded-2xl p-4 mb-6 flex flex-wrap items-center justify-between gap-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center font-bold text-sm">
                    <?= $juz ?>
                </div>
                <div>
                    <p class="text-xs font-bold">Mode Input Leger 20 Pojok — Juz <?= $juz ?></p>
                    <p class="text-[11px] text-indigo-200">Untuk mencetak Rapor 2 Juz sekaligus (misal <b>Juz <?= min($juz, $juz_sebelah) ?>-<?= max($juz, $juz_sebelah) ?></b>), pastikan Anda mengisi nilai Juz <?= $juz ?> dan Juz <?= $juz_sebelah ?>.</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="input_rapor.php?id_santri=<?= $id_santri ?>&juz=<?= $juz_sebelah ?>&semester=<?= urlencode($semester) ?>&tahun_ajaran=<?= urlencode($tahun_ajaran) ?>" 
                   class="bg-white/15 hover:bg-white/25 text-white text-xs font-semibold px-3.5 py-2 rounded-xl transition flex items-center gap-1.5">
                    <span>Pindah Input ke Juz <?= $juz_sebelah ?></span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- FORM UTAMA INPUT LEGER -->
        <form action="proses_input_rapor.php" method="POST" class="space-y-6">
            <input type="hidden" name="id_santri" value="<?= $id_santri ?>">
            <input type="hidden" name="juz" value="<?= $juz ?>">
            <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
            <input type="hidden" name="tahun_ajaran" value="<?= htmlspecialchars($tahun_ajaran) ?>">

            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
                
                <!-- Pengaturan Header Rapor & Tombol Reset Template -->
                <div class="flex flex-wrap items-end justify-between gap-4 pb-5 mb-6 border-b border-slate-100">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 flex-1 max-w-2xl">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-semibold text-slate-600">Nama Kelas / Lokal (Otomatis)</label>
                                <span class="text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded">Terhubung Menu Kelas</span>
                            </div>
                            <input type="text" name="kelas" list="daftarLokalRapor" value="<?= htmlspecialchars($default_kelas) ?>" required 
                                   class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-800 outline-none">
                            <datalist id="daftarLokalRapor">
                                <?php foreach ($lokal_list as $lk): ?>
                                    <option value="<?= htmlspecialchars($lk['nama_halaqah']) ?>"><?= htmlspecialchars($lk['keterangan']) ?></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Judul Kolom Materi</label>
                            <input type="text" name="judul_materi" value="<?= htmlspecialchars($default_judul) ?>" required 
                                   class="w-full px-3.5 py-2 bg-slate-50 focus:bg-white border border-slate-300 rounded-xl text-xs font-bold text-slate-800 outline-none">
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="isiNilaiMassal(98)" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                            Isi Semua Kosong = 98
                        </button>
                        <?php if ($juz === 30): ?>
                            <button type="button" onclick="muatPreset37Surat()" class="px-3 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold transition flex items-center gap-1.5">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Reset 37 Surat Juz 30
                            </button>
                        <?php else: ?>
                            <button type="button" onclick="muatPreset20Pojok(<?= $juz ?>)" class="px-3 py-2 rounded-xl bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold transition flex items-center gap-1.5">
                                <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Reset 20 Pojok Juz <?= $juz ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- GRID INPUT MATERI (37 SURAT ATAU 20 POJOK) -->
                <div id="pojok-container" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3.5 mb-5">
                    <?php foreach ($baris_form as $idx => $item): 
                        $no = $idx + 1;
                    ?>
                    <div class="pojok-item p-3 bg-slate-50 border border-slate-200 rounded-xl relative group transition hover:border-indigo-400 hover:bg-white">
                        <button type="button" onclick="hapusPojok(this)" title="Hapus Baris" class="absolute top-2 right-2 text-slate-300 hover:text-rose-500 opacity-0 group-hover:opacity-100 transition">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                        </button>
                        <label class="block text-[11px] font-bold text-slate-500 mb-1">
                            <?= $juz === 30 ? 'Surat' : 'Pojok' ?> <span class="nomor-pojok"><?= $no ?></span>
                        </label>
                        <input type="text" name="materi[]" value="<?= htmlspecialchars($item['materi']) ?>" required
                               class="w-full px-2.5 py-1.5 mb-2 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 focus:border-indigo-500 outline-none">
                        <input type="number" name="nilai[]" value="<?= htmlspecialchars((string)$item['nilai']) ?>" min="0" max="100" placeholder="Nilai (0-100)"
                               class="input-nilai-rapor w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-sm font-bold text-center text-indigo-700 focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="button" onclick="tambahPojok()" class="w-full py-2.5 rounded-xl border border-dashed border-slate-300 bg-slate-50 hover:bg-slate-100 text-slate-600 text-xs font-semibold flex items-center justify-center gap-1.5 transition">
                    <i data-lucide="plus" class="w-4 h-4 text-indigo-600"></i> Tambah Baris Materi Manual
                </button>
            </div>

            <!-- BAGIAN TAMBAHAN KHUSUS RAPOR JUZ 30 (SESUAI FILE EXCEL P.02 HALIM) -->
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                            <i data-lucide="award" class="w-4 h-4 text-indigo-600"></i>
                            Data Pelengkap Rapor (Ujian Juz, Tahsin, Kepribadian, Absensi & Penandatangan)
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Bagian ini akan tercetak lengkap pada format Rapor Juz 30 maupun bagian tanda tangan Rapor Pojok.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Kolom 1: Rekap Ujian Lintas Juz & Tahsin -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 space-y-3">
                        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">1. Nilai Ujian Juz & Tahsin</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Ujian Juz 30</label>
                                <input type="number" name="ujian_juz_30" value="<?= htmlspecialchars((string)($header_data['ujian_juz_30'] ?? '')) ?>" placeholder="Cth: 97" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Ujian Juz 1</label>
                                <input type="number" name="ujian_juz_1" value="<?= htmlspecialchars((string)($header_data['ujian_juz_1'] ?? '')) ?>" placeholder="Cth: 95" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Ujian Juz 2</label>
                                <input type="number" name="ujian_juz_2" value="<?= htmlspecialchars((string)($header_data['ujian_juz_2'] ?? '')) ?>" placeholder="Cth: 91" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Ujian Juz 3</label>
                                <input type="number" name="ujian_juz_3" value="<?= htmlspecialchars((string)($header_data['ujian_juz_3'] ?? '')) ?>" placeholder="Cth: 92" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Ujian Juz 4</label>
                                <input type="number" name="ujian_juz_4" value="<?= htmlspecialchars((string)($header_data['ujian_juz_4'] ?? '')) ?>" placeholder="Cth: 0" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-indigo-600 mb-1">Nilai Tahsin</label>
                                <input type="number" name="nilai_tahsin" value="<?= htmlspecialchars((string)($header_data['nilai_tahsin'] ?? '')) ?>" placeholder="Cth: 65" class="w-full px-3 py-1.5 bg-white border border-indigo-300 rounded-lg text-xs font-bold text-indigo-700">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 pt-2 border-t border-slate-200">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Naik ke Juz</label>
                                <input type="text" name="naik_juz" value="<?= htmlspecialchars($header_data['naik_juz'] ?? '-') ?>" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Remedi di Juz</label>
                                <input type="text" name="remedi_juz" value="<?= htmlspecialchars($header_data['remedi_juz'] ?? '-') ?>" class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold">
                            </div>
                        </div>
                    </div>

                    <!-- Kolom 2: Kepribadian & Absensi -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 space-y-3">
                        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">2. Kepribadian & Absensi</p>
                        <div class="grid grid-cols-2 gap-3">
                            <?php 
                            $aspek = ['akhlak' => 'Akhlak', 'kerajinan' => 'Kerajinan', 'kebersihan' => 'Kebersihan', 'kerapian' => 'Kerapian'];
                            foreach ($aspek as $k_asp => $lbl_asp): 
                                $val_asp = $header_data[$k_asp] ?? 'A';
                            ?>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1"><?= $lbl_asp ?></label>
                                <select name="<?= $k_asp ?>" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold">
                                    <option value="A" <?= $val_asp === 'A' ? 'selected' : '' ?>>A (Baik)</option>
                                    <option value="B" <?= $val_asp === 'B' ? 'selected' : '' ?>>B (Cukup)</option>
                                    <option value="C" <?= $val_asp === 'C' ? 'selected' : '' ?>>C (Kurang)</option>
                                </select>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-200">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Sakit (Hari)</label>
                                <input type="number" name="sakit" value="<?= htmlspecialchars((string)($header_data['sakit'] ?? $absensi_db['S'])) ?>" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Izin (Hari)</label>
                                <input type="number" name="izin" value="<?= htmlspecialchars((string)($header_data['izin'] ?? $absensi_db['I'])) ?>" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Alfa (Hari)</label>
                                <input type="number" name="alfa" value="<?= htmlspecialchars((string)($header_data['alfa'] ?? $absensi_db['A'])) ?>" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-bold">
                            </div>
                        </div>
                    </div>

                    <!-- Kolom 3: Catatan & Penandatangan -->
                    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-4 space-y-3">
                        <p class="text-xs font-bold text-slate-700 uppercase tracking-wider">3. Catatan & Tanda Tangan</p>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-500 mb-1">Catatan untuk Orang Tua / Wali</label>
                            <textarea name="catatan_ortu" rows="2" placeholder="Tuliskan pesan evaluasi untuk orang tua..." class="w-full px-3 py-1.5 bg-white border border-slate-300 rounded-lg text-xs"><?= htmlspecialchars($header_data['catatan_ortu'] ?? '') ?></textarea>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Diberikan di</label>
                                <input type="text" name="tempat_cetak" value="<?= htmlspecialchars($header_data['tempat_cetak'] ?? 'Jalikur Patanangan') ?>" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Tanggal Rapor</label>
                                <input type="text" name="tanggal_cetak" value="<?= htmlspecialchars($header_data['tanggal_cetak'] ?? date('d F Y')) ?>" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Nama Wali Kelas</label>
                                <input type="text" name="wali_kelas" value="<?= htmlspecialchars($default_wali) ?>" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold">
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-500 mb-1">Kepala STQ-MF</label>
                                <input type="text" name="kepala_stq" value="<?= htmlspecialchars($header_data['kepala_stq'] ?? 'Ust. Aipda Syaiful Hendra') ?>" class="w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-xs font-semibold">
                            </div>
                        </div>
                    </div>

                </div>

                <div class="mt-6 pt-4 border-t border-slate-200 flex flex-wrap items-center justify-between gap-4">
                    <span class="text-xs text-slate-500">
                        Skala Predikat Rapor Surau: <b>90–100 = A (Baik)</b> • <b>85–89 = B (Diterima)</b> • <b>&le; 84 = C (Gagal)</b>
                    </span>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-8 rounded-xl transition shadow-md flex items-center gap-2 text-xs">
                        <i data-lucide="save" class="w-4 h-4"></i> Simpan Leger Rapor Juz <?= $juz ?>
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>

    </div>

    <script>
        lucide.createIcons();

        const daftar37Surat = <?= json_encode($surat_juz_30) ?>;

        function hapusPojok(btn) {
            btn.closest('.pojok-item').remove();
            updateNomorPojok();
        }

        function tambahPojok(namaDefault = '', nilaiDefault = '') {
            const container = document.getElementById('pojok-container');
            const div = document.createElement('div');
            div.className = 'pojok-item p-3 bg-slate-50 border border-slate-200 rounded-xl relative group transition hover:border-indigo-400 hover:bg-white';
            div.innerHTML = `
                <button type="button" onclick="hapusPojok(this)" class="absolute top-2 right-2 text-slate-300 hover:text-rose-500 opacity-0 group-hover:opacity-100 transition">
                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                </button>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Materi <span class="nomor-pojok"></span></label>
                <input type="text" name="materi[]" value="${namaDefault}" required class="w-full px-2.5 py-1.5 mb-2 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-800 outline-none" placeholder="Nama Surat / Halaman">
                <input type="number" name="nilai[]" value="${nilaiDefault}" min="0" max="100" placeholder="Nilai (0-100)" class="input-nilai-rapor w-full px-2.5 py-1.5 bg-white border border-slate-300 rounded-lg text-sm font-bold text-center text-indigo-700 outline-none">
            `;
            container.appendChild(div);
            updateNomorPojok();
            lucide.createIcons();
        }

        function updateNomorPojok() {
            document.querySelectorAll('.nomor-pojok').forEach((el, i) => el.innerText = i + 1);
        }

        function muatPreset37Surat() {
            if (!confirm('Reset tampilan form menjadi 37 Surat Juz 30 standar?')) return;
            const container = document.getElementById('pojok-container');
            container.innerHTML = '';
            daftar37Surat.forEach(s => tambahPojok(s, ''));
        }

        function muatPreset20Pojok(juz) {
            if (!confirm('Reset tampilan form menjadi 20 Pojok standar untuk Juz ' + juz + '?')) return;
            const container = document.getElementById('pojok-container');
            container.innerHTML = '';
            for (let i = 1; i <= 20; i++) {
                let hal = (juz - 1) * 20 + i + 1;
                let label = (juz === 1 && i === 1) ? 'Al-fatihah + Halaman 2' : 'Halaman ' + hal;
                tambahPojok(label, '');
            }
        }

        function isiNilaiMassal(angka) {
            document.querySelectorAll('.input-nilai-rapor').forEach(inp => {
                if (inp.value === '') inp.value = angka;
            });
        }
    </script>
</body>
</html>