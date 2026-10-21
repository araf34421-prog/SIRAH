<?php
session_start();
require 'config/database.php';

// Keamanan: Hanya Ustadz
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ustadz') {
    header("Location: login.php");
    exit;
}

$id_ustadz = $_SESSION['user_id'];

// Proses simpan absensi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'];
    
    foreach ($_POST['status'] as $id_santri => $status) {
        $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan'][$id_santri] ?? '');
        
        // Cek apakah sudah ada absensi untuk santri ini di tanggal tersebut
        $cek = mysqli_query($conn, "SELECT id FROM absensi WHERE id_santri = $id_santri AND tanggal = '$tanggal'");
        
        if (mysqli_num_rows($cek) > 0) {
            // Update jika sudah ada
            mysqli_query($conn, "UPDATE absensi SET status = '$status', keterangan = '$keterangan', id_ustadz = $id_ustadz WHERE id_santri = $id_santri AND tanggal = '$tanggal'");
        } else {
            // Insert jika belum ada
            mysqli_query($conn, "INSERT INTO absensi (id_santri, tanggal, status, keterangan, id_ustadz) VALUES ($id_santri, '$tanggal', '$status', '$keterangan', $id_ustadz)");
        }
    }
    
    $_SESSION['success'] = "Absensi tanggal $tanggal berhasil disimpan!";
    header("Location: input_kehadiran.php?tanggal=$tanggal");
    exit;
}

// Ambil tanggal (default hari ini)
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// Ambil SEMUA santri (tanpa filter halaqah)
$santri_list = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama, nis FROM santri ORDER BY nama ASC"), MYSQLI_ASSOC);

// Ambil data absensi yang sudah diinput untuk tanggal tersebut
$absensi_hari_ini = [];
if (!empty($santri_list)) {
    $absensi_result = mysqli_query($conn, "SELECT * FROM absensi WHERE tanggal = '$tanggal'");
    while ($row = mysqli_fetch_assoc($absensi_result)) {
        $absensi_hari_ini[$row['id_santri']] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Kehadiran - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800">

<?php include 'includes/sidebar.php'; ?>

<div class="ml-64 p-8">
    
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
            <i data-lucide="calendar-check" class="w-7 h-7 text-indigo-600"></i>
            Input Kehadiran Santri
        </h1>
        <p class="text-sm text-slate-500 mt-1">Catat kehadiran harian santri (Hadir, Sakit, Izin, Alfa).</p>
    </div>

    <!-- Notifikasi Sukses -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4"></i>
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($santri_list)): ?>
        <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-700 px-4 py-3 rounded-lg text-sm flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
            Belum ada data santri. Silakan tambahkan santri di menu <b>Kelola Santri</b> terlebih dahulu.
        </div>
    <?php else: ?>

    <!-- Form Absensi -->
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        
        <!-- Pilih Tanggal -->
        <div class="mb-6">
            <form method="GET" class="flex items-center gap-3">
                <label class="text-sm font-medium text-slate-700">Tanggal:</label>
                <input type="date" name="tanggal" value="<?= $tanggal ?>" onchange="this.form.submit()" 
                    class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </form>
        </div>

        <form method="POST">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-slate-200">
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">No</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama Santri</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">NIS</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Hadir</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Sakit</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Izin</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Alfa</th>
                            <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php foreach ($santri_list as $index => $s): 
                            $status_aktif = $absensi_hari_ini[$s['id']]['status'] ?? 'Hadir';
                            $keterangan_aktif = $absensi_hari_ini[$s['id']]['keterangan'] ?? '';
                        ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="py-3 px-4 text-slate-500"><?= $index + 1 ?></td>
                            <td class="py-3 px-4 font-medium text-slate-800"><?= htmlspecialchars($s['nama']) ?></td>
                            <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($s['nis']) ?></td>
                            <?php foreach (['Hadir', 'Sakit', 'Izin', 'Alfa'] as $st): ?>
                            <td class="py-3 px-4 text-center">
                                <input type="radio" name="status[<?= $s['id'] ?>]" value="<?= $st ?>" 
                                    <?= $status_aktif == $st ? 'checked' : '' ?> 
                                    required
                                    class="w-4 h-4 text-indigo-600 focus:ring-indigo-500">
                            </td>
                            <?php endforeach; ?>
                            <td class="py-3 px-4">
                                <input type="text" name="keterangan[<?= $s['id'] ?>]" 
                                    value="<?= htmlspecialchars($keterangan_aktif) ?>" 
                                    class="w-full px-2 py-1 border border-slate-300 rounded text-xs focus:ring-2 focus:ring-indigo-500 outline-none" 
                                    placeholder="Opsional">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg transition text-sm flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    Simpan Kehadiran
                </button>
            </div>
        </form>
    </div>

    <?php endif; ?>

</div>

<script>lucide.createIcons();</script>

</body>
</html>