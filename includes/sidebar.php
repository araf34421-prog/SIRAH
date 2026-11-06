<?php
require_once __DIR__ . '/init_rbac.php';

$role          = $_SESSION['role'] ?? '';
$nama          = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$id_user_login = (int) ($_SESSION['user_id'] ?? 0);
$halaman_aktif = basename($_SERVER['PHP_SELF']);

if (!empty($role) && !cekIzinAksesFile($conn, $role, $halaman_aktif)) {
    $_SESSION['error'] = "Akses ditolak! Role Anda tidak memiliki izin untuk membuka halaman tersebut.";
    header("Location: dashboard.php");
    exit;
}

$label_role = 'Pengguna';
$badge_role_class = 'bg-emerald-950 text-emerald-200 border-emerald-700/50';

if ($role === 'operator') {
    $label_role = 'Operator Sistem';
    $badge_role_class = 'bg-amber-500/20 text-amber-300 border-amber-400/40';
} elseif ($role === 'ustadz') {
    if (isPengajarUstadzah($conn, $id_user_login)) {
        $label_role = 'Ustadzah';
        $badge_role_class = 'bg-teal-500/20 text-teal-200 border-teal-400/30';
    } else {
        $label_role = 'Ustadz';
        $badge_role_class = 'bg-emerald-500/20 text-emerald-200 border-emerald-400/30';
    }
} elseif ($role === 'ortu') {
    $label_role = 'Wali Santri';
    $badge_role_class = 'bg-amber-500/15 text-amber-200 border-amber-400/30';
} elseif ($role === 'pimpinan') {
    $label_role = 'Pimpinan Surau';
    $badge_role_class = 'bg-yellow-500/20 text-yellow-200 border-yellow-400/40';
}

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
<aside class="w-64 bg-[#061B15] islamic-pattern text-stone-200 flex flex-col h-screen fixed left-0 top-0 border-r border-amber-500/20 shadow-2xl z-40 select-none">
    
    <!-- GARIS ORNAMEN EMAS ATAS -->
    <div class="h-1 w-full bg-gradient-to-r from-amber-700 via-amber-400 to-amber-700"></div>

    <!-- BRAND HEADER BERGAYA MIHRAB / MUSHAF -->
    <div class="p-5 border-b border-emerald-900/80 bg-gradient-to-b from-[#092920]/90 to-[#061B15]/90 backdrop-blur-sm">
        <a href="dashboard.php" class="flex items-center gap-3.5 group">
            <!-- Bingkai Segi Delapan / Rub el Hizb Modern -->
            <div class="relative w-11 h-11 rounded-xl bg-gradient-to-br from-amber-300 via-amber-500 to-amber-700 p-[1.5px] shadow-lg shadow-black/40 flex items-center justify-center shrink-0">
                <div class="w-full h-full bg-[#061B15] rounded-[10px] flex items-center justify-center">
                    <i data-lucide="book-open-check" class="w-5 h-5 text-amber-400 group-hover:scale-110 transition-transform"></i>
                </div>
            </div>
            <div class="min-w-0">
                <span class="text-[9px] font-bold uppercase tracking-[0.22em] text-amber-400/90 block">Sistem Tahfizh</span>
                <h1 class="text-sm font-bold text-stone-100 tracking-wide leading-tight group-hover:text-amber-300 transition font-display">
                    SURAU FIRDAUS
                </h1>
                <p class="text-[10px] text-emerald-300/70 truncate mt-0.5">Jalikur Patanangan • Agam</p>
            </div>
        </a>
    </div>

    <!-- DYNAMIC NAVIGATION LINKS -->
    <nav class="flex-1 py-5 px-3.5 overflow-y-auto sidebar-scroll space-y-5">
        <?php foreach ($grouped_sidebar as $nama_kategori => $items): ?>
        <div>
            <div class="flex items-center gap-2 px-3 mb-2">
                <span class="w-1.5 h-1.5 rotate-45 bg-amber-500/60"></span>
                <p class="text-[10px] font-extrabold text-amber-400/75 uppercase tracking-[0.15em]">
                    <?= htmlspecialchars($nama_kategori) ?>
                </p>
            </div>
            <ul class="space-y-1">
                <?php foreach ($items as $item): 
                    $terkait_arr = array_filter(array_map('trim', explode(',', $item['file_terkait'] ?? '')));
                    $all_files   = array_merge([$item['file_utama']], $terkait_arr);
                    $aktif       = in_array($halaman_aktif, $all_files);
                    $cls_item    = $aktif 
                        ? 'relative bg-gradient-to-r from-amber-500/20 via-amber-500/10 to-transparent text-amber-200 font-semibold border border-amber-400/30 shadow-inner' 
                        : 'text-stone-300/75 hover:bg-emerald-900/40 hover:text-stone-100 border border-transparent';
                ?>
                <li>
                    <a href="<?= htmlspecialchars($item['file_utama']) ?>" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= $cls_item ?>">
                        <div class="flex items-center gap-3 min-w-0">
                            <?php if ($aktif): ?>
                                <span class="absolute left-0 top-2 bottom-2 w-1 bg-amber-400 rounded-r-full shadow-[0_0_8px_#fbbf24]"></span>
                            <?php endif; ?>
                            <i data-lucide="<?= htmlspecialchars($item['icon']) ?>" class="w-4 h-4 shrink-0 <?= $aktif ? 'text-amber-400' : 'text-emerald-400/70 group-hover:text-amber-300' ?> transition"></i>
                            <span class="truncate"><?= htmlspecialchars($item['nama_menu']) ?></span>
                        </div>
                        <?php if ($item['kode_menu'] === 'kelola_hak_akses'): ?>
                            <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-400/30">Inti</span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </nav>

    <!-- USER PROFILE & LOGOUT FOOTER -->
    <div class="p-3.5 border-t border-emerald-900/80 bg-[#04120E]/90 backdrop-blur-sm">
        <div class="p-2.5 rounded-xl bg-[#08221A] border border-emerald-800/60 flex items-center gap-3 mb-2.5">
            <div class="relative shrink-0">
                <div class="w-9 h-9 bg-gradient-to-br from-amber-500 to-amber-700 rounded-xl flex items-center justify-center text-[#061B15] font-extrabold text-sm shadow-sm">
                    <?= strtoupper(substr($nama, 0, 1)) ?>
                </div>
                <span class="w-2.5 h-2.5 bg-emerald-400 border-2 border-[#08221A] rounded-full absolute -bottom-0.5 -right-0.5"></span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-stone-100 truncate" title="<?= htmlspecialchars($nama) ?>">
                    <?= htmlspecialchars($nama) ?>
                </p>
                <span class="inline-block mt-0.5 text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded border <?= $badge_role_class ?>">
                    <?= htmlspecialchars($label_role) ?>
                </span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <a href="profil_saya.php" 
               class="flex items-center justify-center gap-1.5 bg-emerald-950/80 hover:bg-amber-600 text-stone-200 hover:text-white border border-emerald-800/70 hover:border-amber-500 py-2 rounded-xl transition-all duration-150 text-xs font-semibold">
                <i data-lucide="user-cog" class="w-3.5 h-3.5"></i>
                <span>Profil</span>
            </a>
            <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')" 
               class="flex items-center justify-center gap-1.5 bg-rose-950/40 hover:bg-rose-600 text-rose-300 hover:text-white border border-rose-800/40 hover:border-rose-500 py-2 rounded-xl transition-all duration-150 text-xs font-semibold">
                <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
                <span>Keluar</span>
            </a>
        </div>
    </div>
</aside>