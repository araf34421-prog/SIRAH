<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/init_rbac.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

$id_ustadz = (int) $_SESSION['user_id'];
$role      = $_SESSION['role'];
$nama_user = $_SESSION['nama_lengkap'] ?? '';
$is_pengelola_penuh = in_array($role, ['operator', 'pimpinan']);

mysqli_query($conn, "ALTER TABLE absensi MODIFY COLUMN `status` VARCHAR(15) NOT NULL DEFAULT 'Hadir'");
mysqli_query($conn, "UPDATE absensi SET status = 'Hadir' WHERE status = 'H' OR status = ''");
mysqli_query($conn, "UPDATE absensi SET status = 'Sakit' WHERE status = 'S'");
mysqli_query($conn, "UPDATE absensi SET status = 'Izin'  WHERE status = 'I'");
mysqli_query($conn, "UPDATE absensi SET status = 'Alfa'  WHERE status = 'A'");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $tanggal  = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $id_lokal = isset($_POST['id_lokal']) ? (int) $_POST['id_lokal'] : 0;

    $map_lengkap = [
        'H' => 'Hadir', 'Hadir' => 'Hadir',
        'S' => 'Sakit', 'Sakit' => 'Sakit',
        'I' => 'Izin',  'Izin'  => 'Izin',
        'A' => 'Alfa',  'Alfa'  => 'Alfa'
    ];

    foreach ($_POST['status'] as $id_santri => $st_raw) {
        $id_s = (int) $id_santri;
        if (!santriDalamBinaan($conn, $id_s, $id_ustadz, $role)) {
            continue;
        }
        $status_fix = $map_lengkap[$st_raw] ?? 'Hadir';
        $keterangan = mysqli_real_escape_string($conn, trim($_POST['keterangan'][$id_s] ?? ''));
        
        $cek = mysqli_query($conn, "SELECT id FROM absensi WHERE id_santri = $id_s AND tanggal = '$tanggal'");
        if ($cek && mysqli_num_rows($cek) > 0) {
            mysqli_query($conn, "UPDATE absensi SET status = '$status_fix', keterangan = '$keterangan', id_ustadz = $id_ustadz WHERE id_santri = $id_s AND tanggal = '$tanggal'");
        } else {
            mysqli_query($conn, "INSERT INTO absensi (id_santri, tanggal, status, keterangan, id_ustadz) VALUES ($id_s, '$tanggal', '$status_fix', '$keterangan', $id_ustadz)");
        }
    }
    
    $_SESSION['success'] = "Presensi tanggal " . date('d M Y', strtotime($tanggal)) . " berhasil disimpan.";
    $q_lokal = $id_lokal > 0 ? "&id_lokal=$id_lokal" : "";
    header("Location: input_kehadiran.php?tanggal=$tanggal" . $q_lokal);
    exit;
}

$tanggal  = $_GET['tanggal'] ?? date('Y-m-d');
$id_lokal = isset($_GET['id_lokal']) ? (int) $_GET['id_lokal'] : 0;

$where_lokal_opsi = "";
if (!$is_pengelola_penuh) {
    $u_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_halaqah, nama_lengkap FROM users WHERE id = $id_ustadz"));
    $id_h_user = (int) ($u_info['id_halaqah'] ?? 0);
    $nm_esc    = mysqli_real_escape_string($conn, trim($u_info['nama_lengkap'] ?? $nama_user));
    $where_lokal_opsi = "WHERE (id_ustadz = $id_ustadz OR id = $id_h_user OR LOWER(nama_pengajar) = LOWER('$nm_esc'))";
}
$lokal_list = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama_halaqah FROM halaqah $where_lokal_opsi ORDER BY id ASC"), MYSQLI_ASSOC);

if ($id_lokal === 0 && !$is_pengelola_penuh && count($lokal_list) === 1) {
    $id_lokal = (int) $lokal_list[0]['id'];
}

$where_santri = $id_lokal > 0 ? "WHERE s.id_halaqah = $id_lokal" : "";
$santri_query = "SELECT s.id, s.nama, s.nis, h.nama_halaqah 
                 FROM santri s 
                 LEFT JOIN halaqah h ON s.id_halaqah = h.id 
                 $where_santri 
                 ORDER BY s.nama ASC";
$santri_list = mysqli_fetch_all(mysqli_query($conn, $santri_query), MYSQLI_ASSOC);

$absensi_hari_ini = [];
$statistik = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];

if (!empty($santri_list)) {
    $tgl_esc = mysqli_real_escape_string($conn, $tanggal);
    $absensi_result = mysqli_query($conn, "SELECT * FROM absensi WHERE tanggal = '$tgl_esc'");
    while ($row = mysqli_fetch_assoc($absensi_result)) {
        $absensi_hari_ini[$row['id_santri']] = $row;
    }

    foreach ($santri_list as $s) {
        if (isset($absensi_hari_ini[$s['id']])) {
            $in = strtoupper(substr(trim($absensi_hari_ini[$s['id']]['status']), 0, 1));
            if (isset($statistik[$in])) $statistik[$in]++;
        }
    }
}

$page_title = "Presensi Harian — SIRAH Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<body class="bg-slate-50 font-sans text-slate-900">

<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="ml-64 p-8 max-w-[1440px]">
    
    <!-- TOP HEADER & QUICK SWITCHER -->
    <div class="flex flex-wrap items-center justify-between gap-4 pb-5 mb-6 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span>Pencatatan Harian</span>
                <span>/</span>
                <span class="text-slate-900 font-medium">Presensi Kehadiran</span>
            </div>
            <h1 class="text-xl font-semibold text-slate-950 tracking-tight">Presensi Kehadiran Santri</h1>
        </div>
        
        <div class="flex flex-wrap items-center gap-2">
            <div class="inline-flex items-center bg-slate-100 p-1 rounded-md border border-slate-200 text-xs">
                <a href="input_ziyadah.php" class="px-3 py-1 rounded text-slate-600 hover:text-slate-950 font-medium transition">Ziyadah</a>
                <a href="input_murajaah.php" class="px-3 py-1 rounded text-slate-600 hover:text-slate-950 font-medium transition">Muraja'ah</a>
                <a href="input_tasmi.php" class="px-3 py-1 rounded text-slate-600 hover:text-slate-950 font-medium transition">Tasmi'</a>
                <a href="input_kehadiran.php" class="px-3 py-1 rounded bg-white text-slate-950 font-semibold shadow-sm border border-slate-200/80">Presensi</a>
            </div>

            <form method="GET" class="flex items-center gap-2">
                <select name="id_lokal" onchange="this.form.submit()" class="px-3 py-1.5 text-xs font-medium text-slate-800 cursor-pointer">
                    <option value="0">Semua Lokal</option>
                    <?php foreach ($lokal_list as $lk): ?>
                        <option value="<?= $lk['id'] ?>" <?= (int)$lk['id'] === $id_lokal ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lk['nama_halaqah']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>" onchange="this.form.submit()" 
                    class="px-3 py-1.5 text-xs font-mono font-medium text-slate-800 cursor-pointer">
            </form>
        </div>
    </div>

    <!-- NOTIFIKASI -->
    <?php if (isset($_SESSION['success'])): ?>
        <div id="alertSuccess" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-2.5 rounded-md mb-6 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="check" class="w-4 h-4 text-emerald-600"></i>
                <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
            <button type="button" onclick="document.getElementById('alertSuccess').remove()" class="text-emerald-600 hover:text-emerald-900"><i data-lucide="x" class="w-4 h-4"></i></button>
        </div>
    <?php endif; ?>

    <!-- HAIRLINE METRIC STRIP -->
    <div class="grid grid-cols-2 md:grid-cols-5 bg-white border border-slate-200 rounded-lg divide-y md:divide-y-0 md:divide-x divide-slate-200 mb-6">
        <div class="p-4 flex items-center justify-between">
            <span class="text-xs text-slate-500 font-medium">Total Santri</span>
            <span class="text-lg font-mono font-semibold text-slate-950"><?= count($santri_list) ?></span>
        </div>
        <div class="p-4 flex items-center justify-between">
            <span class="text-xs text-slate-500 font-medium flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-slate-900"></span>Hadir</span>
            <span class="text-lg font-mono font-semibold text-slate-950"><?= $statistik['H'] ?></span>
        </div>
        <div class="p-4 flex items-center justify-between">
            <span class="text-xs text-slate-500 font-medium flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-sky-600"></span>Sakit</span>
            <span class="text-lg font-mono font-semibold text-sky-600"><?= $statistik['S'] ?></span>
        </div>
        <div class="p-4 flex items-center justify-between">
            <span class="text-xs text-slate-500 font-medium flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-600"></span>Izin</span>
            <span class="text-lg font-mono font-semibold text-amber-600"><?= $statistik['I'] ?></span>
        </div>
        <div class="p-4 flex items-center justify-between">
            <span class="text-xs text-slate-500 font-medium flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-rose-600"></span>Alfa</span>
            <span class="text-lg font-mono font-semibold text-rose-600"><?= $statistik['A'] ?></span>
        </div>
    </div>

    <?php if (empty($santri_list)): ?>
        <div class="bg-white border border-slate-200 rounded-lg p-12 text-center">
            <p class="text-xs font-medium text-slate-600">Tidak ada santri pada filter lokal ini.</p>
        </div>
    <?php else: ?>

    <!-- FORM PRESENSI MASSAL -->
    <form method="POST" class="bg-white border border-slate-200 rounded-lg overflow-hidden mb-10">
        <?php echo csrfTokenField(); ?>
        <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>">
        <input type="hidden" name="id_lokal" value="<?= $id_lokal ?>">
        
        <div class="px-5 py-3.5 border-b border-slate-200 bg-slate-50/50 flex flex-wrap justify-between items-center gap-3">
            <div class="flex items-center gap-3">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-700">
                    Lembar Presensi — <?= date('d M Y', strtotime($tanggal)) ?>
                </h3>
                <button type="button" onclick="tandaiSemuaHadir()" class="px-2.5 py-1 rounded bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 text-[11px] font-medium transition">
                    Set Semua Hadir
                </button>
            </div>

            <div class="flex items-center gap-2">
                <div class="relative w-52">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2"></i>
                    <input type="text" id="cariPresensi" oninput="filterTabelPresensi()" placeholder="Cari nama santri..." class="w-full pl-8 pr-3 py-1 text-xs">
                </div>
                <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-medium py-1.5 px-4 rounded-md transition flex items-center gap-1.5 text-xs">
                    <i data-lucide="check" class="w-3.5 h-3.5"></i> Simpan Presensi
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="py-2.5 px-5 w-12 text-center">No</th>
                        <th class="py-2.5 px-4">Santri & Lokal</th>
                        <th class="py-2.5 px-4 text-center w-[300px]">Status Kehadiran</th>
                        <th class="py-2.5 px-5">Keterangan Opsional</th>
                    </tr>
                </thead>
                <tbody class="text-xs divide-y divide-slate-100">
                    <?php 
                    $opsi_status = ['Hadir', 'Sakit', 'Izin', 'Alfa']; 

                    foreach ($santri_list as $index => $s): 
                        $raw_st = $absensi_hari_ini[$s['id']]['status'] ?? 'Hadir';
                        $in_st  = strtoupper(substr(trim($raw_st), 0, 1));
                        $status_aktif = ($in_st === 'S') ? 'Sakit' : (($in_st === 'I') ? 'Izin' : (($in_st === 'A') ? 'Alfa' : 'Hadir'));
                        $keterangan_aktif = $absensi_hari_ini[$s['id']]['keterangan'] ?? '';
                    ?>
                    <tr class="baris-presensi" data-search="<?= strtolower(htmlspecialchars($s['nama'] . ' ' . ($s['nis'] ?? '') . ' ' . ($s['nama_halaqah'] ?? ''))) ?>">
                        <td class="py-2.5 px-5 text-slate-400 font-mono text-center"><?= $index + 1 ?></td>
                        <td class="py-2.5 px-4">
                            <span class="font-medium text-slate-900 block"><?= htmlspecialchars($s['nama']) ?></span>
                            <div class="flex items-center gap-2 mt-0.5 text-[11px] text-slate-400">
                                <span class="font-mono"><?= htmlspecialchars($s['nis'] ?: '-') ?></span>
                                <?php if (!empty($s['nama_halaqah'])): ?>
                                    <span>•</span>
                                    <span><?= htmlspecialchars($s['nama_halaqah']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-2.5 px-4">
                            <div class="flex items-center justify-center gap-1 bg-slate-100 p-1 rounded-md border border-slate-200 w-max mx-auto">
                                <?php foreach ($opsi_status as $st): ?>
                                <label class="status-radio cursor-pointer">
                                    <input type="radio" name="status[<?= $s['id'] ?>]" value="<?= $st ?>" <?= $status_aktif === $st ? 'checked' : '' ?> required>
                                    <div class="px-3 py-1 rounded text-[11px] font-semibold text-slate-600 transition-colors border border-transparent">
                                        <?= $st ?>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="py-2.5 px-5">
                            <input type="text" name="keterangan[<?= $s['id'] ?>]" 
                                value="<?= htmlspecialchars($keterangan_aktif) ?>" 
                                class="w-full px-3 py-1.5 text-xs" 
                                placeholder="Catatan izin / sakit...">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex justify-between items-center">
            <span class="text-xs text-slate-500">Tersinkronisasi otomatis dengan Rekap Bulanan & Portal Wali Santri.</span>
            <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-medium py-2 px-5 rounded-md transition flex items-center gap-1.5 text-xs">
                <i data-lucide="check" class="w-3.5 h-3.5"></i> Simpan Perubahan
            </button>
        </div>
    </form>
    <?php endif; ?>

</div>

<script>
function tandaiSemuaHadir() {
    document.querySelectorAll('input[type="radio"][value="Hadir"]').forEach(r => { r.checked = true; });
}
function filterTabelPresensi() {
    const q = (document.getElementById('cariPresensi')?.value || '').toLowerCase();
    document.querySelectorAll('.baris-presensi').forEach(tr => {
        tr.style.display = tr.dataset.search.includes(q) ? '' : 'none';
    });
}
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>