<?php
session_start();
require 'config/database.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') {
    header("Location: login.php");
    exit;
}

// Ambil data ustadz
$query = "SELECT * FROM users WHERE role = 'ustadz' ORDER BY id DESC";
$result = mysqli_query($conn, $query);
$ustadz_list = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Ustadz - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <!-- PANGGIL SIDEBAR DI SINI -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- KONTEN UTAMA (Geser ke kanan sejauh lebar sidebar / ml-64) -->
    <div class="ml-64 p-8">
        
        <!-- Header Halaman -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">👨‍🏫 Kelola Ustadz/Ustadzah</h2>
        </div>

        <!-- Form Tambah Ustadz -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h3 class="text-lg font-bold text-emerald-700 mb-4">Tambah Ustadz Baru</h3>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-2 rounded mb-4 text-sm">
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form action="proses_tambah_ustadz.php" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4" autocomplete="off">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="text" name="password" value="ustadz123" required class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div class="md:col-span-3">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                        💾 Simpan Ustadz
                    </button>
                </div>
            </form>
        </div>

        <!-- Tabel Daftar Ustadz -->
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-emerald-700 mb-4">Daftar Ustadz Terdaftar</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 uppercase text-sm">
                            <th class="py-3 px-4">No</th>
                            <th class="py-3 px-4">Nama</th>
                            <th class="py-3 px-4">Username</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm">
                        <?php if (empty($ustadz_list)): ?>
                            <tr><td colspan="4" class="py-4 text-center">Belum ada data.</td></tr>
                        <?php else: ?>
                            <?php foreach ($ustadz_list as $i => $u): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4"><?= $i + 1 ?></td>
                                    <td class="py-3 px-4 font-medium"><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($u['username']) ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <a href="proses_hapus_ustadz.php?id=<?= $u['id'] ?>" onclick="return confirm('Hapus ustadz ini?')" class="bg-red-500 text-white px-3 py-1 rounded text-xs hover:bg-red-600">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div> <!-- End Konten Utama -->

</body>
</html>