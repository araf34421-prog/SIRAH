<?php

$role = $_SESSION['role'] ?? '';
$nama = $_SESSION['nama_lengkap'] ?? 'User';

$halaman_aktif = basename($_SERVER['PHP_SELF']);
?>

<!-- SIDEBAR -->
<aside class="w-64 bg-emerald-800 text-white flex flex-col shadow-lg h-screen fixed left-0 top-0 overflow-y-auto">
    <!-- Logo -->
    <div class="p-6 text-center border-b border-emerald-700">
        <h1 class="text-xl font-bold">🕌 Surau Tahfizh</h1>
        <p class="text-xs text-emerald-200 mt-1">Firdaus</p>
    </div>

    <!-- Menu Navigasi -->
    <nav class="flex-1 py-4">
        <ul class="space-y-1">
            
            <!-- Dashboard (Semua Role) -->
            <li>
                <a href="dashboard.php" class="flex items-center px-6 py-3 <?= $halaman_aktif == 'dashboard.php' ? 'bg-emerald-900 border-l-4 border-white' : 'hover:bg-emerald-700' ?> text-white transition">
                    <span class="mr-3"></span> Dashboard
                </a>
            </li>

            <!-- Menu PIMPINAN -->
            <?php if ($role === 'pimpinan'): ?>
                <li>
                    <a href="kelola-ustadz.php" class="flex items-center px-6 py-3 <?= $halaman_aktif == 'kelola-ustadz.php' ? 'bg-emerald-900 border-l-4 border-white' : 'hover:bg-emerald-700' ?> text-emerald-100 transition">
                        <span class="mr-3"></span> Kelola Ustadz
                    </a>
                </li>
               <li>
                    <a href="rekap_bulanan.php" class="flex items-center px-6 py-3 <?= $halaman_aktif == 'rekap_bulanan.php' ? 'bg-emerald-900 border-l-4 border-white' : 'hover:bg-emerald-700' ?> text-emerald-100 transition">
                        <span class="mr-3"></span> Rekap Bulanan
                    </a>
                </li>
            <?php endif; ?>

            <!-- Menu USTADZ -->
            <?php if ($role === 'ustadz'): ?>
              <li>
        <a href="kelola_santri.php" class="flex items-center px-6 py-3 <?= $halaman_aktif == 'kelola_santri.php' ? 'bg-emerald-900 border-l-4 border-white' : 'hover:bg-emerald-700' ?> text-emerald-100 transition">
            <span class="mr-3"></span> Kelola Santri
        </a>
    </li>
    
    <!-- Sub-menu Input Hafalan (dipisah jadi 3) -->
    <li class="px-6 pt-3 pb-1 text-xs font-semibold text-emerald-300 uppercase tracking-wider">
         Input Hafalan
    </li>
    <li>
        <a href="input_ziyadah.php" class="flex items-center px-6 py-2 pl-10 text-sm <?= $halaman_aktif == 'input_ziyadah.php' ? 'bg-emerald-900 border-l-4 border-white' : 'hover:bg-emerald-700' ?> text-emerald-100 transition">
            <span class="mr-2"></span> Ziyadah
        </a>
    </li>
    <li>
        <a href="input_murajaah.php" class="flex items-center px-6 py-2 pl-10 text-sm <?= $halaman_aktif == 'input_murajaah.php' ? 'bg-emerald-900 border-l-4 border-white' : 'hover:bg-emerald-700' ?> text-emerald-100 transition">
            <span class="mr-2"></span> Muraja'ah
        </a>
    </li>
    <li>
        <a href="input_tasmi.php" class="flex items-center px-6 py-2 pl-10 text-sm <?= $halaman_aktif == 'input_tasmi.php' ? 'bg-emerald-900 border-l-4 border-white' : 'hover:bg-emerald-700' ?> text-emerald-100 transition">
            <span class="mr-2"></span> Tasmi'
        </a>
    </li>
    
    <li>
    <a href="target_santri.php" class="flex items-center px-6 py-3 <?= $halaman_aktif == 'target_santri.php' ? 'bg-emerald-900 border-l-4 border-white' : 'hover:bg-emerald-700' ?> text-emerald-100 transition">
        <span class="mr-3"></span> Target Santri
                    </a>
                </li>
            <?php endif; ?>

            <!-- Menu ORTU -->
            <?php if ($role === 'ortu'): ?>
                <li>
                <a href="monitoring_anak.php" class="flex items-center px-6 py-3 <?= $halaman_aktif == 'monitoring_anak.php' ? 'bg-emerald-900 border-l-4 border-white' : 'hover:bg-emerald-700' ?> text-emerald-100 transition">
            <span class="mr-3"></span> Progres Anak
                </a>
                </li>
                <?php endif; ?>

        </ul>
    </nav>

    <!-- Tombol Logout -->
    <div class="p-4 border-t border-emerald-700 bg-emerald-900">
        <div class="flex items-center mb-3 px-2">
            <div class="ml-2">
                <p class="text-sm font-medium text-white"><?= htmlspecialchars($nama) ?></p>
                <p class="text-xs text-emerald-300 uppercase"><?= $role ?></p>
            </div>
        </div>
        <a href="logout.php" class="flex items-center justify-center w-full bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg transition text-sm">
            🚪 Logout
        </a>
    </div>
</aside>