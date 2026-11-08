<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/init_rbac.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$role    = $_SESSION['role'];
$id_user = (int) $_SESSION['user_id'];

if ($role === 'ortu') {
    $where_santri = "WHERE s.id_user_ortu = $id_user";
} else {
    $where_santri = getFilterSantriBinaanSQL($conn, $id_user, $role, 's');
}

$q_santri = "SELECT s.*, h.nama_halaqah, h.nama_pengajar 
             FROM santri s 
             LEFT JOIN halaqah h ON s.id_halaqah = h.id 
             $where_santri 
             ORDER BY s.nama ASC";
$santri_list = mysqli_fetch_all(mysqli_query($conn, $q_santri), MYSQLI_ASSOC);

$id_santri_pilih = isset($_GET['id_santri']) ? (int) $_GET['id_santri'] : ($santri_list[0]['id'] ?? 0);
$id_tasmi_pilih  = isset($_GET['id_tasmi']) ? (int) $_GET['id_tasmi'] : 0;

$santri_aktif = null;
foreach ($santri_list as $s) {
    if ((int)$s['id'] === $id_santri_pilih) {
        $santri_aktif = $s;
        break;
    }
}

$tasmi_list = [];
$tasmi_aktif = null;

if ($santri_aktif) {
    $id_s = (int) $santri_aktif['id'];
    $q_tasmi = "SELECT t.*, u.nama_lengkap as nama_penguji 
                FROM hafalan t 
                LEFT JOIN users u ON t.id_ustadz = u.id 
                WHERE t.id_santri = $id_s AND t.jenis = 'tasmi' 
                ORDER BY t.tanggal DESC, t.id DESC";
    $res_tasmi = mysqli_query($conn, $q_tasmi);
    if ($res_tasmi) {
        $tasmi_list = mysqli_fetch_all($res_tasmi, MYSQLI_ASSOC);
    }

    if (!empty($tasmi_list)) {
        $tasmi_aktif = $tasmi_list[0];
        if ($id_tasmi_pilih > 0) {
            foreach ($tasmi_list as $tm) {
                if ((int)$tm['id'] === $id_tasmi_pilih) {
                    $tasmi_aktif = $tm;
                    break;
                }
            }
        }
    }
}

$juz_syahadah    = $tasmi_aktif['juz'] ?? 30;
$materi_syahadah = $tasmi_aktif['surat'] ?? "Juz 30 (An-Naba' s/d An-Nas)";
$predikat_kode   = $tasmi_aktif['predikat'] ?? 'A';
$predikat_teks   = ($predikat_kode === 'A') ? 'Mumtaz (Sangat Memuaskan)' : (($predikat_kode === 'B') ? 'Jayyid Jiddan (Baik Sekali)' : 'Maqbul (Lulus)');
$tgl_ujian       = !empty($tasmi_aktif['tanggal']) ? date('d F Y', strtotime($tasmi_aktif['tanggal'])) : date('d F Y');
$nama_penguji    = $tasmi_aktif['nama_penguji'] ?? ($santri_aktif['nama_pengajar'] ?? ($_SESSION['nama_lengkap'] ?? 'Ustadz Pembimbing'));
$kepala_surau    = 'Ust. Aipda Syaiful Hendra';
$no_sertifikat   = sprintf("SYH/%03d/STQ-MF/J%s/%s", ($tasmi_aktif['id'] ?? 1), $juz_syahadah, date('Y'));

$page_title = "Syahadah Tasmi' — Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
<style>
    .font-serif-elegant { font-family: 'Cormorant Garamond', Georgia, serif; }
    @media print {
        @page { size: A4 landscape; margin: 0.8cm; }
        body { background: #fff !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        .no-print, aside, nav { display: none !important; }
        .ml-64 { margin-left: 0 !important; padding: 0 !important; }
        .sertifikat-card { box-shadow: none !important; max-width: 100% !important; }
    }
</style>

<body class="bg-slate-50 font-sans text-slate-800">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- BAR KONTROL (TIDAK TERCETAK) -->
        <div class="no-print bg-white border border-slate-200 rounded-2xl p-5 mb-8">
            <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-100">
                <div>
                    <h1 class="text-lg font-bold text-slate-900">Sertifikat Syahadah Tasmi'</h1>
                    <p class="text-xs text-slate-500">Desain sertifikat minimalis modern (Siap cetak ukuran A4 Landscape).</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="input_tasmi.php" class="px-3.5 py-2 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold transition">
                        Kembali
                    </a>
                    <button type="button" onclick="window.print()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-xl text-xs flex items-center gap-2 transition">
                        <i data-lucide="printer" class="w-4 h-4"></i> Cetak Sertifikat
                    </button>
                </div>
            </div>

            <form method="GET" action="cetak_syahadah.php" class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">Santri</label>
                    <select name="id_santri" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 outline-none">
                        <?php foreach ($santri_list as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (int)$s['id'] === $id_santri_pilih ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nama']) ?> (<?= htmlspecialchars($s['nis'] ?: '-') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">Riwayat Ujian Tasmi'</label>
                    <select name="id_tasmi" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 outline-none">
                        <?php if (empty($tasmi_list)): ?>
                            <option value="0">Mode Pratinjau</option>
                        <?php else: ?>
                            <?php foreach ($tasmi_list as $tm): ?>
                                <option value="<?= $tm['id'] ?>" <?= ($tasmi_aktif && (int)$tm['id'] === (int)$tasmi_aktif['id']) ? 'selected' : '' ?>>
                                    Juz <?= (int)$tm['juz'] ?> — <?= htmlspecialchars($tm['surat']) ?> (<?= date('d/m/Y', strtotime($tm['tanggal'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-500 mb-1">Nama Penguji</label>
                    <input type="text" value="<?= htmlspecialchars($nama_penguji) ?>" oninput="document.getElementById('teksPenguji').innerText = this.value"
                           class="w-full px-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 outline-none">
                </div>
            </form>
        </div>

        <?php if ($santri_aktif): ?>
        <!-- KANVAS SERTIFIKAT MINIMALIS MODERN -->
        <div class="sertifikat-card max-w-5xl mx-auto bg-white border border-slate-200 rounded-2xl p-6 sm:p-10 shadow-sm">
            <div class="border border-slate-300 p-8 sm:p-12 relative flex flex-col justify-between min-h-[620px]">
                
                <!-- Garis Aksen Atas Minimalis -->
                <div class="w-16 h-1 bg-indigo-600 mx-auto mb-6"></div>

                <!-- Header Institusi -->
                <div class="text-center">
                    <p class="text-[11px] font-bold uppercase tracking-[0.3em] text-indigo-600">
                        SURAU TAHFIZH MUSHALLA FIRDAUS
                    </p>
                    <p class="text-[11px] text-slate-400 mt-1">
                        Jorong Jalikur Patanangan, Koto Tangah, Kec. Tilatang Kamang, Kab. Agam
                    </p>

                    <h2 class="font-serif-elegant text-4xl sm:text-5xl font-bold text-slate-900 mt-6 tracking-tight">
                        Syahadah Tasmi' Al-Qur'an
                    </h2>
                    <p class="text-[11px] font-mono text-slate-400 mt-1">Nomor: <?= htmlspecialchars($no_sertifikat) ?></p>
                </div>

                <!-- Konten Penerima -->
                <div class="text-center my-8 max-w-2xl mx-auto">
                    <p class="text-xs text-slate-500 uppercase tracking-widest mb-3">Diberikan Kepada</p>
                    
                    <h3 class="font-serif-elegant text-4xl sm:text-5xl font-bold text-indigo-600 pb-3 border-b border-slate-200 px-8 inline-block">
                        <?= htmlspecialchars($santri_aktif['nama']) ?>
                    </h3>

                    <p class="text-xs text-slate-500 mt-3">
                        NIS: <strong class="text-slate-700"><?= htmlspecialchars($santri_aktif['nis'] ?: '-') ?></strong>
                        &nbsp;•&nbsp;
                        Putra/Putri dari <strong class="text-slate-700"><?= htmlspecialchars($santri_aktif['nama_ortu'] ?: '-') ?></strong>
                    </p>

                    <p class="text-sm text-slate-600 mt-6 leading-relaxed">
                        Atas kelulusan ujian hafalan Al-Qur'an sekali duduk (<span class="italic">Tasmi'</span>) pada materi:
                    </p>

                    <!-- Pill Materi & Predikat -->
                    <div class="mt-4 inline-flex items-center gap-6 px-6 py-3 rounded-xl bg-slate-50 border border-slate-200/80">
                        <div class="text-left">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Materi Hafalan</span>
                            <span class="text-sm font-bold text-slate-900">Juz <?= htmlspecialchars((string)$juz_syahadah) ?> — <?= htmlspecialchars($materi_syahadah) ?></span>
                        </div>
                        <div class="h-7 w-[1px] bg-slate-200"></div>
                        <div class="text-left">
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Predikat</span>
                            <span class="text-sm font-bold text-indigo-600"><?= htmlspecialchars($predikat_teks) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Footer Tanda Tangan -->
                <div class="grid grid-cols-3 items-end pt-6 text-center text-xs">
                    <div>
                        <p class="text-slate-400 mb-14">Penguji Tasmi'</p>
                        <p id="teksPenguji" class="font-bold text-slate-900 border-t border-slate-200 pt-2 inline-block px-6">
                            <?= htmlspecialchars($nama_penguji) ?>
                        </p>
                    </div>

                    <div class="flex justify-center">
                        <div class="w-14 h-14 rounded-full border border-slate-200 bg-slate-50 flex items-center justify-center text-indigo-600">
                            <i data-lucide="award" class="w-6 h-6"></i>
                        </div>
                    </div>

                    <div>
                        <p class="text-slate-400 mb-14"><?= htmlspecialchars($tgl_ujian) ?><br>Pimpinan Surau</p>
                        <p class="font-bold text-slate-900 border-t border-slate-200 pt-2 inline-block px-6">
                            <?= htmlspecialchars($kepala_surau) ?>
                        </p>
                    </div>
                </div>

            </div>
        </div>
        <?php endif; ?>

    </div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>