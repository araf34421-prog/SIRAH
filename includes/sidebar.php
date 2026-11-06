<?php
require_once __DIR__ . '/init_rbac.php';

$role          = $_SESSION['role'] ?? '';
$nama          = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$id_user_login = (int) ($_SESSION['user_id'] ?? 0);
$halaman_aktif = basename($_SERVER['PHP_SELF']);

// Proteksi Otomatis: Cek apakah role yang sedang login diizinkan mengakses file halaman ini
if (!empty($role) && !cekIzinAksesFile($conn, $role, $halaman_aktif)) {
    $_SESSION['error'] = "Akses ditolak! Role Anda tidak memiliki izin untuk membuka halaman tersebut.";
    header("Location: dashboard.php");
    exit;
}

// Penyesuaian label role & warna badge (Deteksi Akurat Ustadz vs Ustadzah dari DB & Lokal)
$label_role = 'Pengguna';
$badge_role_class = 'bg-slate-800 text-slate-300 border-slate-700';

if ($role === 'operator') {
    $label_role = 'Operator Sistem';
    $badge_role_class = 'bg-rose-500/20 text-rose-300 border-rose-500/40';
} elseif ($role === 'ustadz') {
    if (isPengajarUstadzah($conn, $id_user_login)) {
        $label_role = 'Ustadzah';
        $badge_role_class = 'bg-teal-500/15 text-teal-300 border-teal-500/30';
    } else {
        $label_role = 'Ustadz';
        $badge_role_class = 'bg-indigo-500/15 text-indigo-300 border-indigo-500/30';
    }
} elseif ($role === 'ortu') {
    $label_role = 'Wali Santri';
    $badge_role_class = 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30';
} elseif ($role === 'pimpinan') {
    $label_role = 'Pimpinan';
    $badge_role_class = 'bg-amber-500/15 text-amber-300 border-amber-500/30';
}

// Ambil daftar menu dari database `hak_akses_menu` sesuai Role yang sedang login
$kolom_akses = [
    'operator' => 'akses_operator',
    'pimpinan' => 'akses_pimpinan',
    'ustadz'   => 'akses_ustadz',
    'ortu'     => 'akses_ortu'
][$role] ?? 'akses_ortu';

$q_menu_sidebar = mysqli_query($conn, "SELECT * FROM hak_akses_menu WHERE $kolom_akses = 1 ORDER BY urutan ASC");
$sidebar_menus = $q_menu_sidebar ? mysqli_fetch_all($q_menu_sidebar, MYSQLI_ASSOC) : [];

$grouped_sidebar = [];
foreach ($sidebar_menus as $sm) {
    $grouped_sidebar[$sm['kategori']][] = $sm;
}
?>
<aside class="w-64 bg-slate-950 text-slate-300 flex flex-col h-screen fixed left-0 top-0 border-r border-slate-800/80 shadow-2xl z-40 select-none">
    
    <!-- BRAND HEADER -->
    <div class="p-5 border-b border-slate-800/80 bg-gradient-to-b from-slate-900 to-slate-950">
        <a href="dashboard.php" class="flex items-center gap-3.5 group">
            <div class="relative w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 p-0.5 shadow-lg shadow-indigo-950/60 flex items-center justify-center shrink-0">
                <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center overflow-hidden">
                    <i data-lucide="book-open-check" class="w-5 h-5 text-indigo-400"></i>
                </div>
            </div>
            <div class="min-w-0">
                <h1 class="text-sm font-extrabold text-white tracking-wide leading-tight group-hover:text-indigo-300 transition">
                    SURAU TAHFIZH
                </h1>
                <p class="text-[11px] font-medium text-slate-400 truncate mt-0.5">Mushalla Firdaus</p>
            </div>
        </a>
    </div>

    <!-- DYNAMIC NAVIGATION LINKS BERDASARKAN HAK AKSES ACTOR -->
    <nav class="flex-1 py-5 px-3.5 overflow-y-auto sidebar-scroll space-y-5">
        <?php foreach ($grouped_sidebar as $nama_kategori => $items): ?>
        <div>
            <p class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-2 px-3">
                <?= htmlspecialchars($nama_kategori) ?>
            </p>
            <ul class="space-y-1">
                <?php foreach ($items as $item): 
                    $terkait_arr = array_filter(array_map('trim', explode(',', $item['file_terkait'] ?? '')));
                    $all_files   = array_merge([$item['file_utama']], $terkait_arr);
                    $aktif       = in_array($halaman_aktif, $all_files);
                    $cls_item    = $aktif 
                        ? 'relative bg-gradient-to-r from-indigo-600/90 to-indigo-600/70 text-white font-semibold shadow-md shadow-indigo-950/50 border border-indigo-400/20' 
                        : 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-100 border border-transparent';
                ?>
                <li>
                    <a href="<?= htmlspecialchars($item['file_utama']) ?>" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= $cls_item ?>">
                        <div class="flex items-center gap-3 min-w-0">
                            <?php if ($aktif): ?>
                                <span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span>
                            <?php endif; ?>
                            <i data-lucide="<?= htmlspecialchars($item['icon']) ?>" class="w-4 h-4 shrink-0 <?= $aktif ? 'text-white' : htmlspecialchars($item['warna_icon']) ?> transition"></i>
                            <span class="truncate"><?= htmlspecialchars($item['nama_menu']) ?></span>
                        </div>
                        <?php if ($item['kode_menu'] === 'kelola_hak_akses'): ?>
                            <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-400/30">Admin</span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </nav>

    <!-- USER PROFILE & LOGOUT FOOTER -->
    <div class="p-3.5 border-t border-slate-800/80 bg-slate-900/60">
        <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800/90 flex items-center gap-3 mb-2.5">
            <div class="relative shrink-0">
                <div class="w-9 h-9 bg-gradient-to-br <?= $role === 'operator' ? 'from-rose-500 to-orange-600' : ($label_role === 'Ustadzah' ? 'from-teal-500 to-emerald-600' : 'from-indigo-500 to-purple-600') ?> rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-sm">
                    <?= strtoupper(substr($nama, 0, 1)) ?>
                </div>
                <span class="w-2.5 h-2.5 bg-emerald-500 border-2 border-slate-900 rounded-full absolute -bottom-0.5 -right-0.5"></span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-white truncate" title="<?= htmlspecialchars($nama) ?>">
                    <?= htmlspecialchars($nama) ?>
                </p>
                <span class="inline-block mt-0.5 text-[10px] font-bold uppercase tracking-wider px-2 py-0.2 rounded border <?= $badge_role_class ?>">
                    <?= htmlspecialchars($label_role) ?>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <a href="profil_saya.php" 
               class="flex items-center justify-center gap-1.5 bg-slate-800 hover:bg-indigo-600 text-slate-200 hover:text-white border border-slate-700 hover:border-indigo-500 py-2 rounded-xl transition-all duration-150 text-xs font-semibold">
                <i data-lucide="user-cog" class="w-3.5 h-3.5"></i>
                <span>Profil & Sandi</span>
            </a>
            <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')" 
               class="flex items-center justify-center gap-1.5 bg-rose-500/10 hover:bg-rose-600 text-rose-400 hover:text-white border border-rose-500/20 hover:border-rose-600 py-2 rounded-xl transition-all duration-150 text-xs font-semibold">
                <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                <span>Keluar</span>
            </a>
        </div>
    </div>
</aside>
