<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

$santri_query = "SELECT id, nama FROM santri ORDER BY nama ASC";
$santri_result = mysqli_query($conn, $santri_query);
$santri_list = mysqli_fetch_all($santri_result, MYSQLI_ASSOC);

// Ambil riwayat TASMI saja hari ini
$riwayat_query = "SELECT h.*, s.nama as nama_santri 
                  FROM hafalan h 
                  JOIN santri s ON h.id_santri = s.id 
                  WHERE h.tanggal = CURDATE() AND h.jenis = 'tasmi'
                  ORDER BY h.id DESC";
$riwayat_result = mysqli_query($conn, $riwayat_query);
$riwayat_list = mysqli_fetch_all($riwayat_result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Tasmi' - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800" Input Tasmi' (Ujian Juz)</h2>
            <div class="bg-white px-4 py-2 rounded-lg shadow-sm text-sm text-gray-600">
                Tanggal: <b><?= date('d F Y') ?></b>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 text-sm">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 text-sm">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($santri_list)): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-6 text-sm">
                ⚠️ Belum ada data santri. Silakan tambahkan santri di menu <b>Kelola Santri</b> terlebih dahulu.
            </div>
        <?php else: ?>

        <!-- FORM TASMI -->
        <div class="bg-white rounded-xl shadow p-6 mb-6 border-l-4 border-purple-500">
            <h3 class="text-lg font-bold text-purple-700 mb-4"> Form Input Tasmi'</h3>
            <form action="proses_input_hafalan.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4" autocomplete="off">
                <input type="hidden" name="jenis" value="tasmi">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Santri</label>
                    <select name="id_santri" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-purple-500 outline-none">
                        <option value="">-- Pilih Santri --</option>
                        <?php foreach ($santri_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Juz / Surat yang Ditasmikan</label>
                    <input type="text" name="surat" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-purple-500 outline-none" placeholder="Cth: Juz 30 atau Al-Baqarah">
                </div>
                <!-- Untuk Tasmi, ayat default 1-1 -->
                <input type="hidden" name="ayat_awal" value="1">
                <input type="hidden" name="ayat_akhir" value="1">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Predikat</label>
                    <select name="predikat" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-purple-500 outline-none">
                        <option value="A">A (Lulus Sempurna)</option>
                        <option value="B">B (Lulus dengan Catatan)</option>
                        <option value="C">C (Belum Lulus)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Penguji</label>
                    <input type="text" name="catatan" class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-purple-500 outline-none" placeholder="Cth: Lancar, tajwid baik">
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-6 rounded-lg transition text-sm">
                         Simpan Tasmi'
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- TABEL RIWAYAT TASMI HARI INI -->
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4"> Riwayat Tasmi' Hari Ini (<?= date('d M Y') ?>)</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-purple-50 text-purple-700 uppercase text-xs">
                            <th class="py-3 px-4">Waktu</th>
                            <th class="py-3 px-4">Nama Santri</th>
                            <th class="py-3 px-4">Juz / Surat</th>
                            <th class="py-3 px-4">Predikat</th>
                            <th class="py-3 px-4">Catatan Penguji</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm">
                        <?php if (empty($riwayat_list)): ?>
                            <tr><td colspan="5" class="py-4 text-center text-gray-500">Belum ada input Tasmi' hari ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $r): 
                                $predikat_color = match($r['predikat']) {
                                    'A' => 'text-green-600 font-bold',
                                    'B' => 'text-yellow-600 font-bold',
                                    'C' => 'text-red-600 font-bold',
                                    default => 'text-gray-600'
                                };
                            ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4"><?= date('H:i', strtotime($r['created_at'])) ?></td>
                                    <td class="py-3 px-4 font-medium"><?= htmlspecialchars($r['nama_santri']) ?></td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($r['surat']) ?></td>
                                    <td class="py-3 px-4 <?= $predikat_color ?>"><?= $r['predikat'] ?></td>
                                    <td class="py-3 px-4 text-xs"><?= htmlspecialchars($r['catatan'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>