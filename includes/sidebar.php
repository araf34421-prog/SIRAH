<?php
$role = $_SESSION['role'] ?? '';
$nama = $_SESSION['nama_lengkap'] ?? 'User';
$halaman_aktif = basename($_SERVER['PHP_SELF']);
?>
<script src="https://unpkg.com/lucide@latest"></script>
<aside class="w-64 bg-slate-900 text-slate-300 flex flex-col shadow-2xl h-screen fixed left-0 top-0 overflow-y-auto border-r border-slate-800">
    <div class="p-5 border-b border-slate-800 bg-slate-950">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center">
                <i data-lucide="book-open" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h1 class="text-base font-bold text-white tracking-wide">Surau Tahfizh</h1>
                <p class="text-xs text-slate-500">Firdaus</p>
            </div>
        </div>
    </div>
    <nav class="flex-1 py-4 px-3">
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 px-3">Menu Utama</p>
        <ul class="space-y-1 mb-4">
            <li><a href="dashboard.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= $halaman_aktif == 'dashboard.php' ? 'bg-blue-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?> transition"><i data-lucide="layout-dashboard" class="w-4 h-4"></i>Dashboard</a></li>
            <?php if ($role === 'ortu'): ?>
            <li><a href="monitoring_anak.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= $halaman_aktif == 'monitoring_anak.php' ? 'bg-blue-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?> transition"><i data-lucide="book-open-check" class="w-4 h-4"></i>Progres Hafalan</a></li>
            <li><a href="absensi_anak.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= $halaman_aktif == 'absensi_anak.php' ? 'bg-blue-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?> transition"><i data-lucide="calendar-check" class="w-4 h-4"></i>Kehadiran Anak</a></li>
            <li><a href="pr_anak.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= $halaman_aktif == 'pr_anak.php' ? 'bg-blue-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?> transition"><i data-lucide="clipboard-list" class="w-4 h-4"></i>Tugas Rumah (PR)</a></li>
            <?php endif; ?>
        </ul>

        <?php if ($role === 'pimpinan'): ?>
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 px-3">Manajemen Lembaga</p>
        <ul class="space-y-1 mb-4">
            <li><a href="kelola-ustadz.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= $halaman_aktif == 'kelola-ustadz.php' ? 'bg-blue-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?> transition"><i data-lucide="users" class="w-4 h-4"></i>Kelola Ustadz</a></li>
        
            <li><a href="rekap_bulanan.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= $halaman_aktif == 'rekap_bulanan.php' ? 'bg-blue-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?> transition"><i data-lucide="file-bar-chart" class="w-4 h-4"></i>Rekap Bulanan</a></li>
        </ul>
        <?php endif; ?>

        <?php if ($role === 'ustadz'): ?>
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 px-3">Pencatatan Harian</p>
        <ul class="space-y-1 mb-4">
            <li><a href="input_ziyadah.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= $halaman_aktif == 'input_ziyadah.php' ? 'bg-blue-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?> transition"><i data-lucide="book-plus" class="w-4 h-4"></i>Input Ziyadah</a></li>
            <li><a href="input_murajaah.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= $halaman_aktif == 'input_murajaah.php' ? 'bg-blue-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?> transition"><i data-lucide="refresh-cw" class="w-4 h-4"></i>Input Muraja'ah</a></li>
            <li><a href="input_tasmi.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= $halaman_aktif == 'input_tasmi.php' ? 'bg-blue-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?> transition"><i data-lucide="award" class="w-4 h-4"></i>Input Tasmi'</a></li>
            <li><a href="input_kehadiran.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= $halaman_aktif == 'input_absensi.php' ? 'bg-blue-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?> transition"><i data-lucide="calendar-check" class="w-4 h-4"></i>Input Absensi</a></li>
            <li><a href="input_pr.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= $halaman_aktif == 'input_pr.php' ? 'bg-blue-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?> transition"><i data-lucide="clipboard-list" class="w-4 h-4"></i>Berikan PR</a></li>
        </ul>
        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2 px-3">Manajemen Kelas</p>
        <ul class="space-y-1 mb-4">
            <li><a href="kelola_santri.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= $halaman_aktif == 'kelola_santri.php' ? 'bg-blue-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?> transition"><i data-lucide="graduation-cap" class="w-4 h-4"></i>Kelola Santri</a></li>
            <li><a href="target_santri.php" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm <?= $halaman_aktif == 'target_santri.php' ? 'bg-blue-600 text-white font-medium' : 'hover:bg-slate-800 hover:text-white' ?> transition"><i data-lucide="target" class="w-4 h-4"></i>Target Hafalan</a></li>
        </ul>
        <?php endif; ?>
    </nav>
    <div class="p-4 border-t border-slate-800 bg-slate-950">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-9 h-9 bg-slate-700 rounded-full flex items-center justify-center text-white font-semibold text-sm"><?= strtoupper(substr($nama, 0, 1)) ?></div>
            <div class="flex-1 min-w-0"><p class="text-sm font-medium text-white truncate"><?= htmlspecialchars($nama) ?></p><p class="text-xs text-slate-500 uppercase"><?= $role ?></p></div>
        </div>
        <a href="logout.php" class="flex items-center justify-center gap-2 w-full bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg transition text-sm font-medium"><i data-lucide="log-out" class="w-4 h-4"></i>Logout</a>
    </div>
</aside>
<script>lucide.createIcons();</script>