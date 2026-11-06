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

// Otomatis tambahkan kolom status_ortu di tabel `pr` jika belum ada
$cek_kolom = mysqli_query($conn, "SHOW COLUMNS FROM pr LIKE 'status_ortu'");
if (mysqli_num_rows($cek_kolom) === 0) {
    mysqli_query($conn, "ALTER TABLE pr ADD COLUMN status_ortu ENUM('Belum','Selesai') DEFAULT 'Belum' AFTER deskripsi");
}

// Ambil daftar anak yang terhubung dengan akun orang tua ini
$anak_list = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM santri WHERE id_user_ortu = $id_ortu ORDER BY nama ASC"), MYSQLI_ASSOC);

$id_santri_pilih = isset($_GET['id_santri']) ? (int) $_GET['id_santri'] : ($anak_list[0]['id'] ?? 0);
$santri = null;
foreach ($anak_list as $a) {
    if ($a['id'] == $id_santri_pilih) {
        $santri = $a;
        break;
    }
}

// Proses Toggle Status PR ("Sudah Disimak di Rumah" / "Belum")
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status_pr']) && $santri) {
    $id_pr = (int) $_POST['id_pr'];
    $status_baru = ($_POST['status_sekarang'] === 'Selesai') ? 'Belum' : 'Selesai';
    
    mysqli_query($conn, "UPDATE pr SET status_ortu = '$status_baru' WHERE id = $id_pr AND id_santri = {$santri['id']}");
    $_SESSION['success'] = ($status_baru === 'Selesai') 
        ? "Alhamdulillah, tugas rumah ditandai sudah disimak di rumah!" 
        : "Status tugas rumah dikembalikan menjadi belum disimak.";
    
    header("Location: pr_anak.php?id_santri=" . $santri['id']);
    exit;
}

$pr_list = [];
$total_pr = 0;
$pr_selesai = 0;
$pr_belum = 0;

if ($santri) {
    $q = "SELECT p.*, u.nama_lengkap as nama_ustadz 
          FROM pr p 
          LEFT JOIN users u ON p.id_ustadz = u.id 
          WHERE p.id_santri = {$santri['id']} 
          ORDER BY FIELD(p.status_ortu, 'Belum', 'Selesai'), p.tanggal DESC, p.id DESC";
    $pr_list = mysqli_fetch_all(mysqli_query($conn, $q), MYSQLI_ASSOC);

    $total_pr = count($pr_list);
    foreach ($pr_list as $item) {
        if (($item['status_ortu'] ?? 'Belum') === 'Selesai') {
            $pr_selesai++;
        } else {
            $pr_belum++;
        }
    }
}

$persen_selesai = $total_pr > 0 ? round(($pr_selesai / $total_pr) * 100) : 0;

$Nama_Hari_Indo = [
    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
];
?>
<?php
$page_title = "Tugas Rumah (PR) Ananda - Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- Header Halaman -->
        <div class="flex flex-wrap justify-between items-end gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 flex items-center gap-3">
                    <div class="p-2.5 bg-orange-100 rounded-xl">
                        <i data-lucide="clipboard-list" class="w-7 h-7 text-orange-600"></i>
                    </div>
                    Tugas Rumah (PR) Ananda
                </h1>
                <p class="text-sm text-slate-500 mt-2">Dampingi ananda menyelesaikan tugas hafalan atau muraja'ah mandiri di rumah.</p>
            </div>

            <?php if (count($anak_list) > 1): ?>
            <form method="GET" action="pr_anak.php" class="bg-white border border-slate-200 px-3 py-2 rounded-xl shadow-sm flex items-center gap-2">
                <label class="text-xs font-bold text-slate-400 uppercase">Pilih Ananda:</label>
                <select name="id_santri" onchange="this.form.submit()" class="text-sm font-bold text-slate-700 outline-none cursor-pointer">
                    <?php foreach ($anak_list as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $a['id'] == $id_santri_pilih ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
        </div>

        <!-- Notifikasi Sukses -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3.5 rounded-xl mb-6 text-sm flex items-center gap-3 shadow-sm">
                <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
                <span class="font-medium"><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!$santri): ?>
            <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-6 rounded-2xl shadow-sm text-sm">
                Data santri belum terhubung dengan akun orang tua Anda. Silakan hubungi Ustadz/Ustadzah pembimbing.
            </div>
        <?php else: ?>

        <!-- RINGKASAN PROFIL & STATISTIK PR -->
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-5 mb-8">
            
            <!-- Kartu Profil & Progress -->
            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between">
                <div class="flex items-center justify-between gap-4 mb-4">
                    <div class="flex items-center gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-600 text-white font-extrabold text-lg flex items-center justify-center shrink-0 shadow-sm">
                            <?= strtoupper(substr($santri['nama'], 0, 1)) ?>
                        </div>
                        <div>
                            <h2 class="text-base font-extrabold text-slate-900"><?= htmlspecialchars($santri['nama']) ?></h2>
                            <p class="text-xs text-slate-500">NIS: <?= htmlspecialchars($santri['nis'] ?: '-') ?> • Progres Pendampingan Rumah</p>
                        </div>
                    </div>
                    <span class="text-xl font-black text-orange-600 bg-orange-50 px-3 py-1 rounded-xl border border-orange-200">
                        <?= $persen_selesai ?>%
                    </span>
                </div>

                <div>
                    <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden border border-slate-200 mb-1.5">
                        <div class="h-full bg-gradient-to-r from-orange-500 to-emerald-500 rounded-full transition-all duration-500" style="width: <?= $persen_selesai ?>%"></div>
                    </div>
                    <div class="flex justify-between text-[11px] text-slate-500 font-medium">
                        <span>Disimak Ortu: <b class="text-emerald-600"><?= $pr_selesai ?> Tugas</b></span>
                        <span>Total Tugas: <b class="text-slate-700"><?= $total_pr ?> Tugas</b></span>
                    </div>
                </div>
            </div>

            <!-- Belum Disimak -->
            <div class="bg-white border border-amber-200 border-b-4 border-b-amber-500 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-amber-600 uppercase tracking-wider">Perlu Disimak</p>
                    <p class="text-3xl font-black text-slate-900 mt-1"><?= $pr_belum ?></p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Tugas aktif di rumah</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
                    <i data-lucide="clock" class="w-6 h-6"></i>
                </div>
            </div>

            <!-- Sudah Disimak -->
            <div class="bg-white border border-emerald-200 border-b-4 border-b-emerald-500 rounded-2xl p-5 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Sudah Disimak</p>
                    <p class="text-3xl font-black text-slate-900 mt-1"><?= $pr_selesai ?></p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Selesai didampingi</p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                    <i data-lucide="check-circle-2" class="w-6 h-6"></i>
                </div>
            </div>

        </div>

        <!-- TOOLBAR FILTER & PENCARIAN -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 mb-6 shadow-sm flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <button type="button" onclick="setFilterPR('ALL', this)" class="tab-filter bg-slate-900 text-white text-xs font-bold px-4 py-2 rounded-xl transition">
                    Semua Tugas (<?= $total_pr ?>)
                </button>
                <button type="button" onclick="setFilterPR('Belum', this)" class="tab-filter bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold px-4 py-2 rounded-xl transition">
                    ⏳ Belum Disimak (<?= $pr_belum ?>)
                </button>
                <button type="button" onclick="setFilterPR('Selesai', this)" class="tab-filter bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold px-4 py-2 rounded-xl transition">
                    ✅ Sudah Disimak (<?= $pr_selesai ?>)
                </button>
            </div>

            <div class="relative w-full sm:w-64">
                <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
                <input type="text" id="cariPR" oninput="terapkanFilterPR()" placeholder="Cari judul atau ayat PR..." 
                    class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-orange-500 outline-none">
            </div>
        </div>

        <!-- DAFTAR KARTU PR -->
        <?php if (empty($pr_list)): ?>
            <div class="bg-white border border-slate-200 rounded-2xl p-12 text-center shadow-sm">
                <div class="w-16 h-16 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="sparkles" class="w-8 h-8"></i>
                </div>
                <h3 class="text-base font-bold text-slate-800">Belum Ada Tugas Rumah</h3>
                <p class="text-xs text-slate-500 mt-1">Saat ini belum ada PR yang diberikan oleh Ustadz/Ustadzah pembimbing.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6" id="gridPR">
                <?php foreach ($pr_list as $p): 
                    $status = $p['status_ortu'] ?? 'Belum';
                    $is_selesai = ($status === 'Selesai');
                    $is_baru = (!$is_selesai && strtotime($p['tanggal']) >= strtotime('-2 days'));
                    
                    $hari_en = date('l', strtotime($p['tanggal']));
                    $hari_indo = $Nama_Hari_Indo[$hari_en] ?? $hari_en;
                ?>
                <div class="kartu-pr bg-white border <?= $is_selesai ? 'border-emerald-200 bg-emerald-50/10' : ($is_baru ? 'border-orange-300 ring-2 ring-orange-100' : 'border-slate-200') ?> rounded-2xl p-6 shadow-sm flex flex-col justify-between transition hover:shadow-md"
                     data-status="<?= $status ?>"
                     data-search="<?= strtolower(htmlspecialchars($p['judul'] . ' ' . $p['deskripsi'])) ?>">
                    
                    <div>
                        <!-- Header Kartu -->
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-lg">
                                <i data-lucide="calendar" class="w-3.5 h-3.5 text-orange-500"></i>
                                <?= $hari_indo ?>, <?= date('d M Y', strtotime($p['tanggal'])) ?>
                            </span>

                            <div class="flex items-center gap-1.5">
                                <?php if ($is_baru): ?>
                                    <span class="bg-orange-100 text-orange-700 text-[10px] font-extrabold uppercase px-2.5 py-1 rounded-full">
                                        Tugas Baru
                                    </span>
                                <?php endif; ?>

                                <?php if ($is_selesai): ?>
                                    <span class="inline-flex items-center gap-1 bg-emerald-100 text-emerald-700 text-[11px] font-bold px-2.5 py-1 rounded-full">
                                        <i data-lucide="check-circle" class="w-3 h-3"></i> Sudah Disimak
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 bg-amber-100 text-amber-700 text-[11px] font-bold px-2.5 py-1 rounded-full">
                                        <i data-lucide="clock" class="w-3 h-3"></i> Belum Disimak
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Judul & Instruksi -->
                        <h3 class="text-lg font-extrabold <?= $is_selesai ? 'text-slate-600 line-through decoration-emerald-500/50' : 'text-slate-900' ?> mb-2">
                            <?= htmlspecialchars($p['judul']) ?>
                        </h3>
                        <div class="p-4 bg-slate-50 border border-slate-100 rounded-xl text-sm text-slate-700 leading-relaxed mb-5">
                            <?= nl2br(htmlspecialchars($p['deskripsi'])) ?>
                        </div>
                    </div>

                    <!-- Footer Kartu & Tombol Aksi Orang Tua -->
                    <div class="pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2 text-xs text-slate-500">
                            <div class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                                <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                            </div>
                            <div>
                                <p class="text-[10px] text-slate-400 leading-none">Pemberi Tugas</p>
                                <p class="font-bold text-slate-700 mt-0.5"><?= htmlspecialchars($p['nama_ustadz'] ?? 'Ustadz/ah Pembimbing') ?></p>
                            </div>
                        </div>

                        <form method="POST" class="m-0"><?php echo csrfTokenField(); ?>
                            <input type="hidden" name="toggle_status_pr" value="1">
                            <input type="hidden" name="id_pr" value="<?= $p['id'] ?>">
                            <input type="hidden" name="status_sekarang" value="<?= $status ?>">
                            
                            <?php if ($is_selesai): ?>
                                <button type="submit" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 text-xs font-semibold transition flex items-center gap-1.5">
                                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i> Batalkan Tanda
                                </button>
                            <?php else: ?>
                                <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition shadow-sm shadow-emerald-100 flex items-center gap-1.5">
                                    <i data-lucide="check" class="w-4 h-4"></i> Tandai Sudah Disimak
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>