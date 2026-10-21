<?php
session_start();
require 'config/database.php';

// 🔒 HANYA USTADZ YANG BAKS AKSES (Pimpinan dilempar ke dashboard)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ustadz') {
    header("Location: dashboard.php");
    exit;
}

$query = "SELECT santri.*, users.username as username_ortu FROM santri LEFT JOIN users ON santri.id_user_ortu = users.id ORDER BY santri.id DESC";
$result = mysqli_query($conn, $query);
$santri_list = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Santri - Surau Tahfizh Firdaus</title>
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
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="graduation-cap" class="w-7 h-7 text-indigo-600"></i> Kelola Santri
                </h1>
                <p class="text-sm text-slate-500 mt-1">Tambah data santri dan buat akun otomatis untuk orang tua.</p>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded mb-6 text-sm flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Form Tambah Santri -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6">
            <h3 class="text-lg font-semibold text-slate-900 mb-4 flex items-center gap-2">
                <i data-lucide="user-plus" class="w-5 h-5 text-indigo-600"></i> Tambah Santri Baru
            </h3>
            <form action="proses_tambah_santri.php" method="POST" class="space-y-6" autocomplete="off">
                
                <!-- Data Santri -->
                <div>
                    <h4 class="text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wider">Data Santri</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Nama Lengkap</label>
                            <input type="text" name="nama_santri" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">NIS</label>
                            <input type="text" name="nis" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Jenis Kelamin</label>
                            <select name="jenis_kelamin" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-slate-600 mb-1">Alamat</label>
                            <textarea name="alamat" rows="2" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></textarea>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-200 pt-6">
                    <h4 class="text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wider">Data Orang Tua (Untuk Login)</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Nama Orang Tua</label>
                            <input type="text" name="nama_ortu" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">No. HP</label>
                            <input type="text" name="no_hp_ortu" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Username Ortu</label>
                            <input type="text" name="username_ortu" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Cth: ortu_ahmad">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Password Ortu</label>
                            <input type="text" name="password_ortu" value="ortu123" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg transition flex items-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i> Simpan Data Santri
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabel Santri -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-200">
                <h3 class="text-lg font-semibold text-slate-900">Daftar Santri Binaan</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 text-slate-500 uppercase text-xs">
                        <tr>
                            <th class="py-3 px-6 font-medium">No</th>
                            <th class="py-3 px-6 font-medium">Nama Santri</th>
                            <th class="py-3 px-6 font-medium">NIS</th>
                            <th class="py-3 px-6 font-medium">JK</th>
                            <th class="py-3 px-6 font-medium">Nama Ortu</th>
                            <th class="py-3 px-6 font-medium">Username Ortu</th>
                            <th class="py-3 px-6 font-medium text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-600 divide-y divide-slate-100">
                        <?php if (empty($santri_list)): ?>
                            <tr><td colspan="7" class="py-8 text-center text-slate-400">Belum ada data santri.</td></tr>
                        <?php else: ?>
                            <?php foreach ($santri_list as $i => $s): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-4 px-6"><?= $i + 1 ?></td>
                                    <td class="py-4 px-6 font-medium text-slate-900"><?= htmlspecialchars($s['nama']) ?></td>
                                    <td class="py-4 px-6"><?= htmlspecialchars($s['nis']) ?></td>
                                    <td class="py-4 px-6"><?= $s['jenis_kelamin'] ?></td>
                                    <td class="py-4 px-6"><?= htmlspecialchars($s['nama_ortu']) ?></td>
                                    <td class="py-4 px-6 text-xs"><?= htmlspecialchars($s['username_ortu'] ?? '-') ?></td>
                                    <td class="py-4 px-6 text-center">
                                        <a href="proses_hapus_santri.php?id=<?= $s['id'] ?>" onclick="return confirm('Hapus santri ini?')" class="text-red-600 hover:text-red-700 flex items-center justify-center gap-1 mx-auto w-fit">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i> Hapus
                                        </a>
                                    </td>
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