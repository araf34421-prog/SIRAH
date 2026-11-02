<?php
$role = $_SESSION['role'] ?? '';
$nama = $_SESSION['nama_lengkap'] ?? 'Pengguna';
$halaman_aktif = basename($_SERVER['PHP_SELF']);

// Penyesuaian label role & warna badge
$label_role = 'Pengguna';
$badge_role_class = 'bg-slate-800 text-slate-300 border-slate-700';

if ($role === 'ustadz') {
    if (stripos($nama, 'ustadzah') !== false) {
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

// Helper class untuk item menu
function navItemClass($target_files, $current_file) {
    $targets = (array) $target_files;
    $is_active = in_array($current_file, $targets);
    if ($is_active) {
        return 'relative bg-gradient-to-r from-indigo-600/90 to-indigo-600/70 text-white font-semibold shadow-md shadow-indigo-950/50 border border-indigo-400/20';
    }
    return 'text-slate-400 hover:bg-slate-800/70 hover:text-slate-100 border border-transparent';
}

function isNavActive($target_files, $current_file) {
    return in_array($current_file, (array) $target_files);
}
?>
<script src="https://unpkg.com/lucide@latest"></script>
<style>
    /* Custom Scrollbar tipis untuk Sidebar */
    .sidebar-scroll::-webkit-scrollbar { width: 5px; }
    .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
    .sidebar-scroll::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 9999px; }
    .sidebar-scroll:hover::-webkit-scrollbar-thumb { background: #334155; }
</style>

<aside class="w-64 bg-slate-950 text-slate-300 flex flex-col h-screen fixed left-0 top-0 border-r border-slate-800/80 shadow-2xl z-40 select-none">
    
    <!-- BRAND HEADER -->
    <div class="p-5 border-b border-slate-800/80 bg-gradient-to-b from-slate-900 to-slate-950">
        <a href="dashboard.php" class="flex items-center gap-3 group">
            <!-- Logo mark (vector, tajam di semua ukuran) -->
            <div class="relative shrink-0">
                <div class="absolute -inset-0.5 rounded-2xl bg-gradient-to-br from-indigo-500 via-violet-500 to-emerald-400 opacity-45 blur-[6px] group-hover:opacity-80 transition-opacity duration-300"></div>
                <div class="relative w-11 h-11 rounded-2xl bg-gradient-to-br from-indigo-500 via-violet-600 to-indigo-800 ring-1 ring-white/25 shadow-lg shadow-indigo-950/70 flex items-center justify-center overflow-hidden group-hover:scale-105 transition-transform duration-300">
                    <svg viewBox="0 0 24 24" class="w-[22px] h-[22px]" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" stroke="#ffffff"></path>
                        <path d="M19 3v3.5" stroke="#fbbf24"></path>
                        <path d="M20.75 4.75h-3.5" stroke="#fbbf24"></path>
                    </svg>
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

    <!-- NAVIGATION LINKS -->
    <nav class="flex-1 py-5 px-3.5 overflow-y-auto sidebar-scroll space-y-6">
        
        <!-- MENU UTAMA -->
        <div>
            <p class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-2.5 px-3">Menu Utama</p>
            <ul class="space-y-1">
                <li>
                    <?php $aktif = isNavActive('dashboard.php', $halaman_aktif); ?>
                    <a href="dashboard.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('dashboard.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?>
                            <span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span>
                        <?php endif; ?>
                        <i data-lucide="layout-dashboard" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400' ?> transition"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <?php if ($role === 'ortu'): ?>
                <li>
                    <?php $aktif = isNavActive('monitoring_anak.php', $halaman_aktif); ?>
                    <a href="monitoring_anak.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('monitoring_anak.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="book-open-check" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-emerald-400 group-hover:text-emerald-300' ?> transition"></i>
                        <span>Progres Hafalan</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('absensi_anak.php', $halaman_aktif); ?>
                    <a href="absensi_anak.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('absensi_anak.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="calendar-check" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-blue-400 group-hover:text-blue-300' ?> transition"></i>
                        <span>Kehadiran Anak</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('pr_anak.php', $halaman_aktif); ?>
                    <a href="pr_anak.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('pr_anak.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="clipboard-list" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-orange-400 group-hover:text-orange-300' ?> transition"></i>
                        <span>Tugas Rumah (PR)</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('rapor_santri.php', $halaman_aktif); ?>
                    <a href="rapor_santri.php" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('rapor_santri.php', $halaman_aktif) ?>">
                        <div class="flex items-center gap-3">
                            <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                            <i data-lucide="award" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-amber-400 group-hover:text-amber-300' ?> transition"></i>
                            <span>Rapor Tahfidz</span>
                        </div>
                        <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-amber-500/20 text-amber-300 border border-amber-400/20">Resmi</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- MENU PIMPINAN -->
        <?php if ($role === 'pimpinan'): ?>
        <div>
            <p class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-2.5 px-3">Manajemen Lembaga</p>
            <ul class="space-y-1">
                <li>
                    <?php $aktif = isNavActive('kelola-ustadz.php', $halaman_aktif); ?>
                    <a href="kelola-ustadz.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('kelola-ustadz.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="users" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-indigo-400' ?>"></i>
                        <span>Ustadz & Ustadzah</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('kelola_kelas.php', $halaman_aktif); ?>
                    <a href="kelola_kelas.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('kelola_kelas.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="school" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-amber-400 group-hover:text-amber-300' ?>"></i>
                        <span>Kelas / Lokal Halaqah</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('kelola_santri.php', $halaman_aktif); ?>
                    <a href="kelola_santri.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('kelola_santri.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="graduation-cap" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-emerald-400' ?>"></i>
                        <span>Data Santri</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('progres_santri.php', $halaman_aktif); ?>
                    <a href="progres_santri.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('progres_santri.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="line-chart" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-blue-400' ?>"></i>
                        <span>Analisis Progres</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('rapor_santri.php', $halaman_aktif); ?>
                    <a href="rapor_santri.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('rapor_santri.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="printer" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-amber-400' ?>"></i>
                        <span>Cetak Rapor Santri</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('rekap_bulanan.php', $halaman_aktif); ?>
                    <a href="rekap_bulanan.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('rekap_bulanan.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="file-bar-chart" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-purple-400' ?>"></i>
                        <span>Rekap Bulanan</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

        <!-- MENU USTADZ / USTADZAH -->
        <?php if ($role === 'ustadz'): ?>
        <div>
            <p class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-2.5 px-3">Pencatatan Harian</p>
            <ul class="space-y-1">
                <li>
                    <?php $aktif = isNavActive('input_ziyadah.php', $halaman_aktif); ?>
                    <a href="input_ziyadah.php" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('input_ziyadah.php', $halaman_aktif) ?>">
                        <div class="flex items-center gap-3">
                            <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                            <i data-lucide="book-plus" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-emerald-400 group-hover:text-emerald-300' ?>"></i>
                            <span>Input Ziyadah</span>
                        </div>
                        <span class="w-2 h-2 rounded-full bg-emerald-500/80"></span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('input_murajaah.php', $halaman_aktif); ?>
                    <a href="input_murajaah.php" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('input_murajaah.php', $halaman_aktif) ?>">
                        <div class="flex items-center gap-3">
                            <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                            <i data-lucide="refresh-cw" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-blue-400 group-hover:text-blue-300' ?>"></i>
                            <span>Input Muraja'ah</span>
                        </div>
                        <span class="w-2 h-2 rounded-full bg-blue-500/80"></span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('input_tasmi.php', $halaman_aktif); ?>
                    <a href="input_tasmi.php" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('input_tasmi.php', $halaman_aktif) ?>">
                        <div class="flex items-center gap-3">
                            <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                            <i data-lucide="award" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-purple-400 group-hover:text-purple-300' ?>"></i>
                            <span>Input Tasmi'</span>
                        </div>
                        <span class="w-2 h-2 rounded-full bg-purple-500/80"></span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive(['input_kehadiran.php', 'input_absensi.php'], $halaman_aktif); ?>
                    <a href="input_kehadiran.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass(['input_kehadiran.php', 'input_absensi.php'], $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="calendar-check" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-indigo-400 group-hover:text-indigo-300' ?>"></i>
                        <span>Input Kehadiran</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('input_pr.php', $halaman_aktif); ?>
                    <a href="input_pr.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('input_pr.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="clipboard-list" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-orange-400 group-hover:text-orange-300' ?>"></i>
                        <span>Berikan PR</span>
                    </a>
                </li>
            </ul>
        </div>

        <div>
            <p class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest mb-2.5 px-3">Manajemen & Evaluasi</p>
            <ul class="space-y-1">
                <li>
                    <?php $aktif = isNavActive('kelola_kelas.php', $halaman_aktif); ?>
                    <a href="kelola_kelas.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('kelola_kelas.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="school" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-amber-400 group-hover:text-amber-300' ?>"></i>
                        <span>Kelas / Lokal Halaqah</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('kelola_santri.php', $halaman_aktif); ?>
                    <a href="kelola_santri.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('kelola_santri.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="graduation-cap" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>"></i>
                        <span>Kelola Santri</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('target_santri.php', $halaman_aktif); ?>
                    <a href="target_santri.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('target_santri.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="target" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-teal-400 group-hover:text-teal-300' ?>"></i>
                        <span>Target Hafalan</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('progres_santri.php', $halaman_aktif); ?>
                    <a href="progres_santri.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('progres_santri.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="line-chart" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-sky-400 group-hover:text-sky-300' ?>"></i>
                        <span>Analisis Progres</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('input_rapor.php', $halaman_aktif); ?>
                    <a href="input_rapor.php" class="group flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('input_rapor.php', $halaman_aktif) ?>">
                        <div class="flex items-center gap-3">
                            <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                            <i data-lucide="table-2" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-amber-400 group-hover:text-amber-300' ?>"></i>
                            <span>Input Leger Rapor</span>
                        </div>
                        <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-300 border border-indigo-400/20">Baru</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('rapor_santri.php', $halaman_aktif); ?>
                    <a href="rapor_santri.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('rapor_santri.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="printer" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>"></i>
                        <span>Cetak Rapor</span>
                    </a>
                </li>
                <li>
                    <?php $aktif = isNavActive('rekap_bulanan.php', $halaman_aktif); ?>
                    <a href="rekap_bulanan.php" class="group flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all duration-150 <?= navItemClass('rekap_bulanan.php', $halaman_aktif) ?>">
                        <?php if ($aktif): ?><span class="absolute left-0 top-2 bottom-2 w-1 bg-white rounded-r-full"></span><?php endif; ?>
                        <i data-lucide="file-bar-chart" class="w-4 h-4 <?= $aktif ? 'text-white' : 'text-slate-400 group-hover:text-white' ?>"></i>
                        <span>Rekap Bulanan</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

    </nav>

    <!-- USER PROFILE & LOGOUT FOOTER -->
    <div class="p-3.5 border-t border-slate-800/80 bg-slate-900/60">
        <div class="p-2.5 rounded-xl bg-slate-900 border border-slate-800/90 flex items-center gap-3 mb-2.5">
            <div class="relative shrink-0">
                <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-sm">
                    <?= strtoupper(substr($nama, 0, 1)) ?>
                </div>
                <!-- Status Online Dot -->
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

        <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari sistem?')" 
           class="flex items-center justify-center gap-2 w-full bg-rose-500/10 hover:bg-rose-600 text-rose-400 hover:text-white border border-rose-500/20 hover:border-rose-600 py-2 rounded-xl transition-all duration-150 text-xs font-semibold">
            <i data-lucide="log-out" class="w-3.5 h-3.5"></i>
            <span>Keluar Sistem</span>
        </a>
    </div>
</aside>
<script>lucide.createIcons();</script>