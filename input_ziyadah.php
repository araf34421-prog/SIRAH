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

// Ubah 'ziyadah' sesuai jenis halaman (murajaah/tasmi)
$jenis_halaman = 'ziyadah'; 
$riwayat_query = "SELECT h.*, s.nama as nama_santri FROM hafalan h JOIN santri s ON h.id_santri = s.id WHERE h.tanggal = CURDATE() AND h.jenis = '$jenis_halaman' ORDER BY h.id DESC";
$riwayat_result = mysqli_query($conn, $riwayat_query);
$riwayat_list = mysqli_fetch_all($riwayat_result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Ziyadah - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <!-- Ganti Icon dan Judul sesuai halaman -->
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="book-plus" class="w-7 h-7 text-emerald-600"></i> Input Ziyadah
                </h1>
                <p class="text-sm text-slate-500 mt-1">Catat hafalan baru (ziyadah) santri hari ini.</p>
            </div>
            <div class="bg-white border border-slate-200 px-4 py-2 rounded-lg shadow-sm text-sm text-slate-600 flex items-center gap-2">
                <i data-lucide="calendar" class="w-4 h-4"></i> <?= date('d F Y') ?>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded mb-6 text-sm flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($santri_list)): ?>
            <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-4 rounded mb-6 text-sm flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4"></i> Belum ada data santri. Silakan tambah di menu Kelola Santri.
            </div>
        <?php else: ?>

        <!-- Form Input -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="edit-3" class="w-5 h-5 text-emerald-600"></i> Form Setoran Ziyadah
            </h3>
            <form action="proses_input_hafalan.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4" autocomplete="off">
                <input type="hidden" name="jenis" value="ziyadah"> <!-- Ganti value ini untuk Murajaah/Tasmi -->
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Nama Santri</label>
                    <select name="id_santri" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        <option value="">-- Pilih Santri --</option>
                        <?php foreach ($santri_list as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Nama Surat</label>
                    <input type="text" name="surat" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="Cth: An-Naba">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Ayat Awal</label>
                        <input type="number" name="ayat_awal" min="1" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1">Ayat Akhir</label>
                        <input type="number" name="ayat_akhir" min="1" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Predikat</label>
                    <select name="predikat" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        <option value="A">A (Lancar & Tajwid Sempurna)</option>
                        <option value="B">B (Lancar, Tajwid Kurang)</option>
                        <option value="C">C (Terdapat Kesalahan)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Catatan (Opsional)</label>
                    <input type="text" name="catatan" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="Cth: Perbaiki makhraj">
                </div>
                <div class="md:col-span-2 flex justify-end pt-2">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-6 rounded-lg transition flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Simpan Ziyadah
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Tabel Riwayat -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                    <i data-lucide="history" class="w-5 h-5 text-slate-400"></i> Riwayat Setoran Hari Ini
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                        <tr>
                            <th class="py-3 px-6 font-medium">Waktu</th>
                            <th class="py-3 px-6 font-medium">Santri</th>
                            <th class="py-3 px-6 font-medium">Surat</th>
                            <th class="py-3 px-6 font-medium">Ayat</th>
                            <th class="py-3 px-6 font-medium">Predikat</th>
                            <th class="py-3 px-6 font-medium">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-600 divide-y divide-slate-100">
                        <?php if (empty($riwayat_list)): ?>
                            <tr><td colspan="6" class="py-8 text-center text-slate-400">Belum ada input hari ini.</td></tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $r): 
                                $p_color = $r['predikat'] == 'A' ? 'text-emerald-600 font-semibold' : ($r['predikat'] == 'B' ? 'text-amber-600 font-semibold' : 'text-red-600 font-semibold');
                            ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="py-4 px-6 text-xs"><?= date('H:i', strtotime($r['created_at'])) ?></td>
                                    <td class="py-4 px-6 font-medium text-slate-900"><?= htmlspecialchars($r['nama_santri']) ?></td>
                                    <td class="py-4 px-6"><?= htmlspecialchars($r['surat']) ?></td>
                                    <td class="py-4 px-6"><?= $r['ayat_awal'] ?> - <?= $r['ayat_akhir'] ?></td>
                                    <td class="py-4 px-6 <?= $p_color ?>"><?= $r['predikat'] ?></td>
                                    <td class="py-4 px-6 text-xs text-slate-500"><?= htmlspecialchars($r['catatan'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>