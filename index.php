<?php
/** Front controller for SIRAH pages. */
$routes = [
    'dashboard' => ['pages/dashboard/index.php', 'dashboard.php'],
    'login' => ['pages/auth/login.php', 'login.php'],
    'absensi_anak' => ['pages/absensi/index.php', 'absensi_anak.php'],
    'input_kehadiran' => ['pages/hafalan/kehadiran.php', 'input_kehadiran.php'],
    'input_murajaah' => ['pages/hafalan/murajaah.php', 'input_murajaah.php'],
    'input_pr' => ['pages/hafalan/input_pr.php', 'input_pr.php'],
    'input_rapor' => ['pages/rapor/input.php', 'input_rapor.php'],
    'syahadah' => ['pages/rapor/syahadah.php', 'cetak_syahadah.php'],
    'input_tasmi' => ['pages/hafalan/tasmi.php', 'input_tasmi.php'],
    'input_ziyadah' => ['pages/hafalan/ziyadah.php', 'input_ziyadah.php'],
    'kelola-ustadz' => ['pages/pengguna/ustadz.php', 'kelola-ustadz.php'],
    'kelola_hak_akses' => ['pages/pengguna/hak_akses.php', 'kelola_hak_akses.php'],
    'kelola_kelas' => ['pages/halaqah/index.php', 'kelola_kelas.php'],
    'kelola_santri' => ['pages/santri/index.php', 'kelola_santri.php'],
    'monitoring_anak' => ['pages/wali/monitoring.php', 'monitoring_anak.php'],
    'profil_saya' => ['pages/pengguna/profil_saya.php', 'profil_saya.php'],
    'progres_santri' => ['pages/santri/progres.php', 'progres_santri.php'],
    'pr_anak' => ['pages/wali/pr.php', 'pr_anak.php'],
    'rapor_santri' => ['pages/rapor/santri.php', 'rapor_santri.php'],
    'rekap_bulanan' => ['pages/rapor/rekap_bulanan.php', 'rekap_bulanan.php'],
    'target_santri' => ['pages/santri/target.php', 'target_santri.php'],
];

$route = $_GET['page'] ?? 'dashboard';
if (!isset($routes[$route])) {
    http_response_code(404);
    require __DIR__ . '/pages/errors/404.php';
    exit;
}

[$view, $legacyFile] = $routes[$route];
$_SERVER['PHP_SELF'] = '/' . $legacyFile;
require __DIR__ . '/' . $view;
