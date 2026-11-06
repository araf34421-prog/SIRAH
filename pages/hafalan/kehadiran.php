<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/init_rbac.php';

// Keamanan: Hanya Operator, Ustadz/ah, dan Pimpinan
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

$id_ustadz = (int) $_SESSION['user_id'];
$role      = $_SESSION['role'];
$nama_user = $_SESSION['nama_lengkap'] ?? '';
$is_pengelola_penuh = in_array($role, ['operator', 'pimpinan']);

// Pastikan kolom status di tabel absensi mendukung format lengkap ('Hadir','Sakit','Izin','Alfa')
// sekaligus konversi data lama ('H','S','I','A') jika pernah tersimpan
mysqli_query($conn, "ALTER TABLE absensi MODIFY COLUMN `status` VARCHAR(15) NOT NULL DEFAULT 'Hadir'");
mysqli_query($conn, "UPDATE absensi SET status = 'Hadir' WHERE status = 'H' OR status = ''");
mysqli_query($conn, "UPDATE absensi SET status = 'Sakit' WHERE status = 'S'");
mysqli_query($conn, "UPDATE absensi SET status = 'Izin'  WHERE status = 'I'");
mysqli_query($conn, "UPDATE absensi SET status = 'Alfa'  WHERE status = 'A'");

// Proses simpan absensi
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
        $id_s       = (int) $id_santri;
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
    
    $_SESSION['success'] = "Data kehadiran tanggal " . date('d F Y', strtotime($tanggal)) . " berhasil disimpan!";
    $q_lokal = $id_lokal > 0 ? "&id_lokal=$id_lokal" : "";
    header("Location: input_kehadiran.php?tanggal=$tanggal" . $q_lokal);
    exit;
}

// Ambil tanggal (default hari ini)
$tanggal  = $_GET['tanggal'] ?? date('Y-m-d');
$id_lokal = isset($_GET['id_lokal']) ? (int) $_GET['id_lokal'] : 0;

// Ambil daftar lokal (Jika Ustadz, utamakan lokal binaannya)
$where_lokal_opsi = "";
if (!$is_pengelola_penuh) {
    $u_info = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_halaqah, nama_lengkap FROM users WHERE id = $id_ustadz"));
    $id_h_user = (int) ($u_info['id_halaqah'] ?? 0);
    $nm_esc    = mysqli_real_escape_string($conn, trim($u_info['nama_lengkap'] ?? $nama_user));
    $where_lokal_opsi = "WHERE (id_ustadz = $id_ustadz OR id = $id_h_user OR LOWER(nama_pengajar) = LOWER('$nm_esc'))";
}
$lokal_list = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama_halaqah FROM halaqah $where_lokal_opsi ORDER BY id ASC"), MYSQLI_ASSOC);

// Jika Ustadz hanya mengampu 1 lokal dan belum memilih filter, otomatis pilih lokalnya
if ($id_lokal === 0 && !$is_pengelola_penuh && count($lokal_list) === 1) {
    $id_lokal = (int) $lokal_list[0]['id'];
}

// Ambil daftar santri sesuai filter lokal
$where_santri = $id_lokal > 0 ? "WHERE s.id_halaqah = $id_lokal" : "";
$santri_query = "SELECT s.id, s.nama, s.nis, h.nama_halaqah 
                 FROM santri s 
                 LEFT JOIN halaqah h ON s.id_halaqah = h.id 
                 $where_santri 
                 ORDER BY s.nama ASC";
$santri_list = mysqli_fetch_all(mysqli_query($conn, $santri_query), MYSQLI_ASSOC);

// Ambil data absensi yang sudah diinput untuk tanggal tersebut
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
?>
<?php
$page_title = "Input Kehadiran - Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<body class="bg-slate-50 font-sans text-slate-800">

<?php include __DIR__ . '/../../includes/sidebar.php'; ?>

<div class="ml-64 p-8">
    
    <!-- Header Halaman -->
    <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
                <div class="p-2.5 bg-indigo-100 rounded-xl"><i data-lucide="calendar-check" class="w-6 h-6 text-indigo-600"></i></div>
                Input Kehadiran Santri
            </h1>
            <p class="text-sm text-slate-500 mt-1">Pilih tanggal dan lokal untuk mencatat presensi harian santri secara terstruktur.</p>
        </div>
        
        <!-- Form Filter Tanggal & Lokal -->
        <form method="GET" class="bg-white border border-slate-200 p-2 rounded-2xl shadow-sm flex flex-wrap items-center gap-2">
            <select name="id_lokal" onchange="this.form.submit()" class="px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 outline-none cursor-pointer">
                <option value="0">Semua Lokal / Kelas</option>
                <?php foreach ($lokal_list as $lk): ?>
                    <option value="<?= $lk['id'] ?>" <?= (int)$lk['id'] === $id_lokal ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lk['nama_halaqah']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="flex items-center gap-2 px-3 py-1.5 bg-slate-50 border border-slate-200 rounded-xl">
                <i data-lucide="calendar" class="w-4 h-4 text-indigo-600"></i>
                <input type="date" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>" onchange="this.form.submit()" 
                    class="bg-transparent text-xs font-bold text-slate-700 outline-none cursor-pointer">
            </div>
        </form>
    </div>

    <!-- Notifikasi Sukses -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-6 text-xs font-medium flex items-center gap-2.5 shadow-sm">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
            <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
        </div>
    <?php endif; ?>

    <!-- Papan Statistik Kehadiran -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Santri Ditampilkan</p>
            <p class="text-2xl font-black text-slate-800"><?= count($santri_list) ?></p>
        </div>
        <div class="bg-white border border-emerald-200 border-b-4 border-b-emerald-500 rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-widest mb-1">Hadir</p>
            <p class="text-2xl font-black text-emerald-600"><?= $statistik['H'] ?></p>
        </div>
        <div class="bg-white border border-blue-200 border-b-4 border-b-blue-500 rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-1">Sakit</p>
            <p class="text-2xl font-black text-blue-600"><?= $statistik['S'] ?></p>
        </div>
        <div class="bg-white border border-amber-200 border-b-4 border-b-amber-500 rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] font-bold text-amber-600 uppercase tracking-widest mb-1">Izin</p>
            <p class="text-2xl font-black text-amber-600"><?= $statistik['I'] ?></p>
        </div>
        <div class="bg-white border border-rose-200 border-b-4 border-b-rose-500 rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] font-bold text-rose-600 uppercase tracking-widest mb-1">Alfa</p>
            <p class="text-2xl font-black text-rose-600"><?= $statistik['A'] ?></p>
        </div>
    </div>

    <?php if (empty($santri_list)): ?>
        <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center shadow-sm">
            <i data-lucide="users" class="w-10 h-10 text-slate-300 mx-auto mb-2"></i>
            <p class="text-sm font-semibold text-slate-700">Tidak ada santri pada filter lokal ini</p>
            <p class="text-xs text-slate-400 mt-1">Pilih "Semua Lokal / Kelas" atau tempatkan santri terlebih dahulu di menu Kelas.</p>
        </div>
    <?php else: ?>

    <!-- Form Absensi Massal -->
    <form method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-10"><?php echo csrfTokenField(); ?>
        <input type="hidden" name="tanggal" value="<?= htmlspecialchars($tanggal) ?>">
        <input type="hidden" name="id_lokal" value="<?= $id_lokal ?>">
        
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-wrap justify-between items-center gap-3">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="list-checks" class="w-4 h-4 text-indigo-600"></i> Daftar Presensi Santri (<?= date('d M Y', strtotime($tanggal)) ?>)
            </h3>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-5 rounded-xl transition shadow-sm flex items-center gap-2 text-xs">
                <i data-lucide="save" class="w-3.5 h-3.5"></i> Simpan Kehadiran
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead class="bg-slate-50/70 text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-3.5 px-6 font-semibold w-12 text-center">No</th>
                        <th class="py-3.5 px-4 font-semibold">Profil Santri & Lokal</th>
                        <th class="py-3.5 px-4 font-semibold text-center w-[340px]">Status Kehadiran</th>
                        <th class="py-3.5 px-6 font-semibold">Keterangan Opsional</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-slate-100">
                    <?php 
                    $opsi_status = ['Hadir', 'Sakit', 'Izin', 'Alfa']; 

                    foreach ($santri_list as $index => $s): 
                        $raw_st = $absensi_hari_ini[$s['id']]['status'] ?? 'Hadir';
                        $in_st  = strtoupper(substr(trim($raw_st), 0, 1));
                        $status_aktif = ($in_st === 'S') ? 'Sakit' : (($in_st === 'I') ? 'Izin' : (($in_st === 'A') ? 'Alfa' : 'Hadir'));
                        $keterangan_aktif = $absensi_hari_ini[$s['id']]['keterangan'] ?? '';
                    ?>
                    <tr class="hover:bg-slate-50 transition group">
                        <td class="py-3.5 px-6 text-slate-400 font-semibold text-xs text-center"><?= $index + 1 ?></td>
                        <td class="py-3.5 px-4">
                            <p class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($s['nama']) ?></p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-[11px] text-slate-400 font-mono">NIS: <?= htmlspecialchars($s['nis'] ?: '-') ?></span>
                                <?php if (!empty($s['nama_halaqah'])): ?>
                                    <span class="text-[10px] font-semibold bg-slate-100 text-slate-600 px-1.5 py-0.2 rounded"><?= htmlspecialchars($s['nama_halaqah']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-3.5 px-4">
                            <div class="flex items-center justify-center gap-1 bg-slate-100 p-1 rounded-xl border border-slate-200 w-max mx-auto">
                                <?php foreach ($opsi_status as $st): ?>
                                <label class="status-radio cursor-pointer">
                                    <input type="radio" name="status[<?= $s['id'] ?>]" value="<?= $st ?>" <?= $status_aktif === $st ? 'checked' : '' ?> required>
                                    <div class="px-3.5 py-1.5 rounded-lg text-xs font-bold text-slate-500 transition-colors border border-transparent">
                                        <?= strtoupper($st) ?>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="py-3.5 px-6">
                            <input type="text" name="keterangan[<?= $s['id'] ?>]" 
                                value="<?= htmlspecialchars($keterangan_aktif) ?>" 
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-indigo-500 outline-none transition" 
                                placeholder="Cth: Demam / Izin acara keluarga...">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-between items-center">
            <p class="text-xs text-slate-500">Data tersimpan ke tabel <b>absensi</b> dan langsung terhubung ke Rekap Bulanan serta Portal Wali Santri.</p>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-7 rounded-xl transition shadow-md flex items-center gap-2 text-xs">
                <i data-lucide="save" class="w-4 h-4"></i> Simpan Data Presensi
            </button>
        </div>
    </form>
    <?php endif; ?>

</div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
