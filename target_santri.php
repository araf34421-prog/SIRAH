<?php
session_start();
require 'config/database.php';

//  Keamanan: Hanya Ustadz dan Pimpinan
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

// 1. Ambil semua data santri
$santri_query = "SELECT id, nama FROM santri ORDER BY nama ASC";
$santri_result = mysqli_query($conn, $santri_query);
$santri_list = mysqli_fetch_all($santri_result, MYSQLI_ASSOC);

// 2. Ambil data target hafalan TERAKHIR untuk setiap santri
// Kita gabungkan (JOIN) agar bisa ditampilkan di tabel
$target_query = "SELECT s.id, s.nama, t.target_ayat_per_hari, t.keterangan, t.tanggal_mulai 
                 FROM santri s 
                 LEFT JOIN target_hafalan t ON s.id = t.id_santri 
                 ORDER BY s.nama ASC";
$target_result = mysqli_query($conn, $target_query);
$target_list = mysqli_fetch_all($target_result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Target Hafalan Santri - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800"> Target Hafalan Individual</h2>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 text-sm">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($santri_list)): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6 text-sm">
                ️ Belum ada data santri. Silakan tambahkan santri di menu <b>Kelola Santri</b> terlebih dahulu.
            </div>
        <?php else: ?>

        <!-- FORM PENETAPAN TARGET -->
        <div class="bg-white rounded-xl shadow p-6 mb-6 border-l-4 border-orange-500">
            <h3 class="text-lg font-bold text-orange-700 mb-4"> Tetapkan / Update Target Santri</h3>
            <p class="text-sm text-gray-600 mb-4">Gunakan form ini untuk menentukan program hafalan lanjutan (ziyadah) berdasarkan kemampuan individual santri.</p>
            
            <form action="proses_target_santri.php" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4" autocomplete="off">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Pilih Santri</label>
                    <select name="id_santri" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-orange-500 outline-none">
                        <option value="">-- Pilih Santri --</option>
                        <?php foreach ($santri_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Target Ayat / Hari</label>
                    <input type="number" name="target_ayat" min="1" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Cth: 5">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" required value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-orange-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Program / Keterangan</label>
                    <input type="text" name="keterangan" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-orange-500 outline-none" placeholder="Cth: Lanjut Juz 30">
                </div>
                <div class="md:col-span-4">
                    <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-semibold py-2 px-6 rounded-lg transition text-sm">
                        💾 Simpan Target
                    </button>
                </div>
            </form>
        </div>

        <!-- TABEL DAFTAR TARGET SANTRI -->
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4"> Daftar Target Hafalan Santri Saat Ini</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-orange-50 text-orange-700 uppercase text-xs">
                            <th class="py-3 px-4">No</th>
                            <th class="py-3 px-4">Nama Santri</th>
                            <th class="py-3 px-4">Target (Ayat/Hari)</th>
                            <th class="py-3 px-4">Tanggal Mulai</th>
                            <th class="py-3 px-4">Program / Keterangan</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm">
                        <?php if (empty($target_list)): ?>
                            <tr><td colspan="5" class="py-4 text-center text-gray-500">Belum ada target yang ditetapkan.</td></tr>
                        <?php else: ?>
                            <?php foreach ($target_list as $index => $t): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4"><?= $index + 1 ?></td>
                                    <td class="py-3 px-4 font-medium"><?= htmlspecialchars($t['nama']) ?></td>
                                    <td class="py-3 px-4">
                                        <?php if ($t['target_ayat_per_hari']): ?>
                                            <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded text-xs font-bold"><?= $t['target_ayat_per_hari'] ?> Ayat</span>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-xs">Belum ditentukan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4"><?= $t['tanggal_mulai'] ? date('d M Y', strtotime($t['tanggal_mulai'])) : '-' ?></td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($t['keterangan'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php endif; ?>

    </div>

</body>
</html>