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
$badge_role_cls = 'bg-slate-500/20 text-slate-300 border-slate-500/30';
if ($role === 'operator') {
    $label_role = 'Operator';
    $badge_role_cls = 'bg-rose-500/20 text-rose-300 border-rose-500/30';
} elseif ($role === 'ustadz') {
    $label_role = isPengajarUstadzah($conn, $id_user_login) ? 'Ustadzah' : 'Ustadz';
    $badge_role_cls = 'bg-indigo-500/20 text-indigo-300 border-indigo-500/30';
} elseif ($role === 'ortu') {
    $label_role = 'Wali Santri';
    $badge_role_cls = 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30';
} elseif ($role === 'pimpinan') {
    $label_role = 'Pimpinan';
    $badge_role_cls = 'bg-amber-500/20 text-amber-300 border-amber-500/30';
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
$active_menu_info = ['nama_menu' => 'Workspace', 'kategori' => 'SIRAH', 'icon' => 'sparkles'];

foreach ($sidebar_menus as $sm) {
    $grouped_sidebar[$sm['kategori']][] = $sm;
    $terkait_arr = array_filter(array_map('trim', explode(',', $sm['file_terkait'] ?? '')));
    if (in_array($halaman_aktif, array_merge([$sm['file_utama']], $terkait_arr))) {
        $active_menu_info = $sm;
    }
}

function getModernIconStyle($warna_db, $aktif) {
    if ($aktif) {
        return 'bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-md shadow-indigo-500/40 ring-1 ring-white/20';
    }
    if (strpos($warna_db, 'emerald') !== false || strpos($warna_db, 'teal') !== false) {
        return 'bg-emerald-500/10 text-emerald-400 group-hover:bg-emerald-500/20 group-hover:text-emerald-300';
    }
    if (strpos($warna_db, 'amber') !== false || strpos($warna_db, 'orange') !== false) {
        return 'bg-amber-500/10 text-amber-400 group-hover:bg-amber-500/20 group-hover:text-amber-300';
    }
    if (strpos($warna_db, 'rose') !== false) {
        return 'bg-rose-500/10 text-rose-400 group-hover:bg-rose-500/20 group-hover:text-rose-300';
    }
    if (strpos($warna_db, 'purple') !== false) {
        return 'bg-purple-500/10 text-purple-400 group-hover:bg-purple-500/20 group-hover:text-purple-300';
    }
    if (strpos($warna_db, 'blue') !== false || strpos($warna_db, 'sky') !== false) {
        return 'bg-sky-500/10 text-sky-400 group-hover:bg-sky-500/20 group-hover:text-sky-300';
    }
    return 'bg-indigo-500/10 text-indigo-400 group-hover:bg-indigo-500/20 group-hover:text-indigo-300';
}
?>

<!-- DARK LUXURY COLLAPSIBLE MINI-RAIL SIDEBAR -->
<aside id="appSidebar" class="sirah-sidebar bg-[#0B0F19] text-slate-300 flex flex-col h-screen fixed left-0 top-0 border-r border-slate-800/80 z-40 select-none shadow-2xl">
    
    <!-- AMBIENT GLOW BACKGROUND -->
    <div class="pointer-events-none absolute top-0 left-0 w-full h-48 bg-gradient-to-b from-indigo-600/15 via-violet-600/5 to-transparent"></div>

    <!-- WORKSPACE HEADER -->
    <div class="relative h-16 px-3.5 border-b border-slate-800/80 flex items-center justify-between shrink-0">
        <a href="dashboard.php" class="sidebar-brand flex items-center gap-3 min-w-0 group">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-700 flex items-center justify-center shrink-0 text-white font-mono text-sm font-bold shadow-lg shadow-indigo-500/30 ring-1 ring-white/20 group-hover:scale-105 transition-transform">
                S
            </div>
            <div class="sidebar-hide-on-collapse flex flex-col min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-white tracking-tight">SIRAH</span>
                    <span class="text-[10px] font-mono font-semibold px-1.5 py-0.5 rounded-md border <?= $badge_role_cls ?>">
                        <?= htmlspecialchars($label_role) ?>
                    </span>
                </div>
                <span class="text-[11px] text-slate-400 truncate">Surau Tahfizh Firdaus</span>
            </div>
        </a>

        <button type="button" id="btnToggleSidebar" onclick="toggleSirahSidebar()" 
                title="Kecilkan / Perlebar Sidebar (Ctrl+B)"
                class="w-8 h-8 rounded-lg border border-slate-700/80 bg-slate-800/60 hover:bg-indigo-600 hover:border-indigo-500 text-slate-400 hover:text-white flex items-center justify-center transition-all shrink-0 cursor-pointer">
            <i data-lucide="panel-left-close" class="w-4 h-4 icon-collapse-open"></i>
            <i data-lucide="panel-left-open" class="w-4 h-4 icon-collapse-closed hidden"></i>
        </button>
    </div>

    <!-- QUICK SEARCH TRIGGER DI SIDEBAR -->
    <div class="px-3 pt-3 pb-1 shrink-0 sidebar-hide-on-collapse">
        <button type="button" onclick="openCommandPalette()" class="w-full flex items-center justify-between px-3 py-2 rounded-xl bg-slate-900/90 hover:bg-slate-800/90 border border-slate-800 hover:border-slate-700 text-slate-400 hover:text-slate-200 text-xs transition group cursor-pointer">
            <span class="flex items-center gap-2">
                <i data-lucide="search" class="w-3.5 h-3.5 text-indigo-400 group-hover:scale-110 transition-transform"></i>
                <span>Loncat ke menu...</span>
            </span>
            <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-slate-800 text-slate-400 rounded border border-slate-700">Ctrl K</kbd>
        </button>
    </div>

    <!-- NAVIGATION LIST -->
    <nav class="flex-1 py-3 px-2.5 overflow-y-auto overflow-x-hidden sidebar-scroll space-y-5">
        <?php foreach ($grouped_sidebar as $nama_kategori => $items): ?>
        <div class="sidebar-group">
            <div class="sidebar-cat-header flex items-center gap-2 px-2.5 mb-2">
                <span class="sidebar-hide-on-collapse text-[10px] font-bold text-slate-500 uppercase tracking-widest truncate">
                    <?= htmlspecialchars($nama_kategori) ?>
                </span>
                <div class="sidebar-cat-divider hidden h-px bg-slate-800 w-full my-1"></div>
            </div>

            <ul class="space-y-1">
                <?php foreach ($items as $item): 
                    $terkait_arr = array_filter(array_map('trim', explode(',', $item['file_terkait'] ?? '')));
                    $all_files   = array_merge([$item['file_utama']], $terkait_arr);
                    $aktif       = in_array($halaman_aktif, $all_files);
                    
                    $cls_item = $aktif 
                        ? 'menu-active bg-gradient-to-r from-indigo-600/20 via-indigo-600/10 to-transparent text-white font-semibold border-indigo-500/30 shadow-sm' 
                        : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-100 font-medium border-transparent';
                    
                    $icon_box_cls = getModernIconStyle($item['warna_icon'] ?? '', $aktif);
                ?>
                <li class="relative">
                    <a href="<?= htmlspecialchars($item['file_utama']) ?>" 
                       data-tooltip="<?= htmlspecialchars($item['nama_menu']) ?>"
                       class="sidebar-link group flex items-center justify-between px-2 py-1.5 rounded-xl text-[13px] border transition-all duration-200 <?= $cls_item ?>">
                        
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 transition-all duration-200 group-hover:scale-105 <?= $icon_box_cls ?>">
                                <i data-lucide="<?= htmlspecialchars($item['icon']) ?>" class="w-4 h-4"></i>
                            </div>
                            <span class="sidebar-hide-on-collapse truncate"><?= htmlspecialchars($item['nama_menu']) ?></span>
                        </div>

                        <?php if ($aktif): ?>
                            <span class="sidebar-hide-on-collapse w-1.5 h-1.5 rounded-full bg-indigo-400 shadow-[0_0_8px_#818cf8] shrink-0 mr-1"></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endforeach; ?>
    </nav>

    <!-- COMPACT USER FOOTER -->
    <div class="p-3 border-t border-slate-800/80 bg-[#080B12] shrink-0">
        <div class="sidebar-user-card flex items-center justify-between gap-2.5 p-2 rounded-xl bg-slate-900/90 border border-slate-800/90 mb-2">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center text-xs font-bold shrink-0 shadow-md shadow-emerald-500/20">
                    <?= strtoupper(substr($nama, 0, 1)) ?>
                </div>
                <div class="sidebar-hide-on-collapse min-w-0">
                    <p class="text-xs font-semibold text-white truncate"><?= htmlspecialchars($nama) ?></p>
                    <p class="text-[10px] text-emerald-400 font-medium flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span> Online
                    </p>
                </div>
            </div>
        </div>

        <div class="sidebar-footer-actions grid grid-cols-2 gap-1.5">
            <a href="profil_saya.php" title="Pengaturan Akun"
               class="flex items-center justify-center gap-1.5 bg-slate-900 hover:bg-slate-800 text-slate-300 hover:text-white border border-slate-800 py-1.5 px-2 rounded-lg transition text-xs font-medium">
                <i data-lucide="user-cog" class="w-3.5 h-3.5 text-indigo-400 shrink-0"></i>
                <span class="sidebar-hide-on-collapse">Profil</span>
            </a>
            <a href="logout.php" onclick="return confirm('Keluar dari sesi ini?')" title="Keluar"
               class="flex items-center justify-center gap-1.5 bg-slate-900 hover:bg-rose-950/60 text-slate-400 hover:text-rose-300 border border-slate-800 hover:border-rose-800/60 py-1.5 px-2 rounded-lg transition text-xs font-medium">
                <i data-lucide="log-out" class="w-3.5 h-3.5 shrink-0"></i>
                <span class="sidebar-hide-on-collapse">Keluar</span>
            </a>
        </div>
    </div>
</aside>

<!-- GLOBAL STICKY TOP CONTEXT BAR (Menempel di atas setiap halaman) -->
<header class="sirah-topbar no-print sticky top-0 z-30 ml-64 bg-white/80 backdrop-blur-md border-b border-slate-200/80 px-8 h-14 flex items-center justify-between transition-all">
    <div class="flex items-center gap-2.5 text-xs">
        <span class="px-2 py-1 rounded-md bg-indigo-50 text-indigo-600 font-semibold flex items-center gap-1.5 border border-indigo-100">
            <i data-lucide="<?= htmlspecialchars($active_menu_info['icon'] ?? 'layers') ?>" class="w-3.5 h-3.5"></i>
            <?= htmlspecialchars($active_menu_info['kategori'] ?? 'SIRAH') ?>
        </span>
        <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-slate-300"></i>
        <span class="font-bold text-slate-800"><?= htmlspecialchars($active_menu_info['nama_menu'] ?? 'Halaman Aktif') ?></span>
    </div>

    <div class="flex items-center gap-3">
        <!-- Quick Command Palette Button -->
        <button type="button" onclick="openCommandPalette()" class="hidden sm:flex items-center gap-3 px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200/70 text-slate-500 text-xs transition border border-slate-200/80 cursor-pointer">
            <i data-lucide="command" class="w-3.5 h-3.5 text-indigo-600"></i>
            <span>Cari Menu / Aksi Cepat...</span>
            <kbd class="px-1.5 py-0.5 text-[10px] font-mono bg-white text-slate-600 rounded shadow-2xs border border-slate-200">Ctrl K</kbd>
        </button>

        <div class="h-4 w-px bg-slate-200 hidden sm:block"></div>

        <!-- Live Clock Pill -->
        <div class="flex items-center gap-1.5 text-xs font-mono font-medium text-slate-600 bg-slate-100/80 px-2.5 py-1 rounded-lg border border-slate-200/60">
            <i data-lucide="clock" class="w-3.5 h-3.5 text-emerald-600"></i>
            <span id="sirahLiveClock"><?= date('H:i') ?> WIB</span>
        </div>
    </div>
</header>

<!-- FLOATING TOOLTIP UNTUK MINI-RAIL -->
<div id="sidebarRailTooltip" class="fixed z-50 pointer-events-none opacity-0 transition-all duration-150 bg-slate-900 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-xl border border-slate-700 whitespace-nowrap hidden"></div>

<!-- COMMAND PALETTE MODAL (CTRL + K) -->
<div id="sirahCommandModal" class="fixed inset-0 z-50 hidden bg-slate-950/60 backdrop-blur-xs flex items-start justify-center pt-20 p-4">
    <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-in">
        <div class="p-3.5 border-b border-slate-200 flex items-center gap-3 bg-slate-50/50">
            <i data-lucide="search" class="w-4 h-4 text-indigo-600 shrink-0 ml-1"></i>
            <input type="text" id="cmdSearchInput" oninput="filterCommandPalette(this.value)" placeholder="Ketik nama menu atau fitur (contoh: Ziyadah, Rapor, Santri)..." 
                   class="w-full bg-transparent text-sm font-medium text-slate-900 outline-none border-none! shadow-none! p-0!">
            <button type="button" onclick="closeCommandPalette()" class="text-[10px] font-mono px-2 py-1 rounded bg-slate-200/70 text-slate-600 hover:bg-slate-300">ESC</button>
        </div>
        <div class="max-h-80 overflow-y-auto p-2 divide-y divide-slate-100" id="cmdMenuList">
            <?php foreach ($sidebar_menus as $m): ?>
            <a href="<?= htmlspecialchars($m['file_utama']) ?>" 
               data-cmd="<?= strtolower(htmlspecialchars($m['nama_menu'] . ' ' . $m['kategori'])) ?>"
               class="cmd-item flex items-center justify-between p-2.5 rounded-xl hover:bg-indigo-50/80 group transition">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-slate-100 group-hover:bg-indigo-600 text-slate-600 group-hover:text-white flex items-center justify-center transition">
                        <i data-lucide="<?= htmlspecialchars($m['icon']) ?>" class="w-4 h-4"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-800 group-hover:text-indigo-950"><?= htmlspecialchars($m['nama_menu']) ?></p>
                        <p class="text-[10px] text-slate-400"><?= htmlspecialchars($m['kategori']) ?></p>
                    </div>
                </div>
                <i data-lucide="arrow-right" class="w-4 h-4 text-slate-300 group-hover:text-indigo-600 group-hover:translate-x-0.5 transition-all"></i>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="px-4 py-2.5 bg-slate-50 border-t border-slate-200/80 flex items-center justify-between text-[11px] text-slate-400">
            <span>Gunakan <strong class="text-slate-600">Ctrl + B</strong> untuk lipat sidebar</span>
            <span>SIRAH Quick Navigation</span>
        </div>
    </div>
</div>

<style>
:root {
    --sidebar-expanded: 16rem;
    --sidebar-collapsed: 4.5rem;
}
#appSidebar.sirah-sidebar {
    width: var(--sidebar-expanded) !important;
    transition: width 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}
.ml-64 {
    margin-left: var(--sidebar-expanded) !important;
    transition: margin-left 0.22s cubic-bezier(0.4, 0, 0.2, 1);
}
html.sidebar-collapsed #appSidebar.sirah-sidebar {
    width: var(--sidebar-collapsed) !important;
}
html.sidebar-collapsed .ml-64 {
    margin-left: var(--sidebar-collapsed) !important;
}
html.sidebar-collapsed .sidebar-hide-on-collapse,
html.sidebar-collapsed .sidebar-brand {
    display: none !important;
}
html.sidebar-collapsed .sidebar-cat-divider {
    display: block !important;
}
html.sidebar-collapsed .sidebar-link,
html.sidebar-collapsed .sidebar-user-card {
    justify-content: center !important;
    padding-left: 0.375rem !important;
    padding-right: 0.375rem !important;
}
html.sidebar-collapsed .sidebar-link > div,
html.sidebar-collapsed .sidebar-user-card > div {
    justify-content: center !important;
    gap: 0 !important;
}
html.sidebar-collapsed .sidebar-footer-actions {
    grid-template-columns: 1fr !important;
}
html.sidebar-collapsed #btnToggleSidebar {
    margin-left: auto !important;
    margin-right: auto !important;
}
</style>

<script>
function syncSidebarIcons() {
    const isCollapsed = document.documentElement.classList.contains('sidebar-collapsed');
    const openIcon = document.querySelector('.icon-collapse-open');
    const closedIcon = document.querySelector('.icon-collapse-closed');
    if (openIcon && closedIcon) {
        openIcon.classList.toggle('hidden', isCollapsed);
        closedIcon.classList.toggle('hidden', !isCollapsed);
    }
}

function toggleSirahSidebar() {
    const root = document.documentElement;
    const isCollapsed = root.classList.toggle('sidebar-collapsed');
    try { localStorage.setItem('sirah_sidebar_collapsed', isCollapsed ? '1' : '0'); } catch (e) {}
    syncSidebarIcons();
    const tooltip = document.getElementById('sidebarRailTooltip');
    if (tooltip) tooltip.classList.add('opacity-0', 'hidden');
}

function openCommandPalette() {
    const modal = document.getElementById('sirahCommandModal');
    const inp = document.getElementById('cmdSearchInput');
    if (!modal) return;
    modal.classList.remove('hidden');
    if (inp) { inp.value = ''; filterCommandPalette(''); setTimeout(() => inp.focus(), 50); }
}

function closeCommandPalette() {
    document.getElementById('sirahCommandModal')?.classList.add('hidden');
}

function filterCommandPalette(q) {
    const kw = (q || '').toLowerCase();
    document.querySelectorAll('.cmd-item').forEach(el => {
        el.style.display = el.getAttribute('data-cmd').includes(kw) ? '' : 'none';
    });
}

Object.assign(window, {
    syncSidebarIcons,
    toggleSirahSidebar,
    openCommandPalette,
    closeCommandPalette,
    filterCommandPalette
});

if (window.lucide) {
    try { window.lucide.createIcons(); } catch (e) {}
}
syncSidebarIcons();

document.addEventListener('DOMContentLoaded', () => {
    syncSidebarIcons();

    // Live Clock
    setInterval(() => {
        const el = document.getElementById('sirahLiveClock');
        if (el) {
            const now = new Date();
            el.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';
        }
    }, 15000);

    // Keyboard Shortcuts (Ctrl+B & Ctrl+K)
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
            e.preventDefault();
            toggleSirahSidebar();
        }
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            openCommandPalette();
        }
        if (e.key === 'Escape') {
            closeCommandPalette();
        }
    });

    // Klik di luar modal command palette untuk menutup
    document.getElementById('sirahCommandModal')?.addEventListener('click', (e) => {
        if (e.target.id === 'sirahCommandModal') closeCommandPalette();
    });

    // Tooltip saat Mini-Rail
    const tooltip = document.getElementById('sidebarRailTooltip');
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.addEventListener('mouseenter', () => {
            if (!document.documentElement.classList.contains('sidebar-collapsed') || !tooltip) return;
            const text = link.getAttribute('data-tooltip');
            if (!text) return;
            const rect = link.getBoundingClientRect();
            tooltip.textContent = text;
            tooltip.style.top = (rect.top + rect.height / 2 - 15) + 'px';
            tooltip.style.left = (rect.right + 12) + 'px';
            tooltip.classList.remove('hidden', 'opacity-0');
            tooltip.classList.add('opacity-100');
        });
        link.addEventListener('mouseleave', () => {
            if (!tooltip) return;
            tooltip.classList.remove('opacity-100');
            tooltip.classList.add('opacity-0', 'hidden');
        });
    });
});
</script>
