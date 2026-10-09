<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

$query = "SELECT santri.*, users.username as username_ortu 
          FROM santri 
          LEFT JOIN users ON santri.id_user_ortu = users.id 
          ORDER BY santri.id DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error query: " . mysqli_error($conn));
}

$santri_list = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Santri - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <!-- PANGGIL SIDEBAR -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- KONTEN UTAMA (Geser ke kanan) -->
    <div class="ml-64 p-8">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800"> Kelola Santri</h2>
        </div>

        <!-- Pesan Sukses -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 text-sm">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Pesan Error -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 text-sm">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- FORM TAMBAH SANTRI -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h3 class="text-lg font-bold text-emerald-700 mb-4"> Tambah Santri Baru (Sekaligus Buat Akun Ortu)</h3>
            
            <form action="proses_tambah_santri.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4" autocomplete="off">
                
                <!-- Header Data Santri -->
                <div class="md:col-span-2 bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <h4 class="font-semibold text-gray-700 text-sm"> Data Santri</h4>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Nama Lengkap Santri</label>
                    <input type="text" name="nama_santri" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">NIS</label>
                    <input type="text" name="nis" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Jenis Kelamin</label>
                    <select name="jenis_kelamin" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Alamat</label>
                    <textarea name="alamat" rows="2" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none"></textarea>
                </div>

                <!-- Header Data Ortu -->
                <div class="md:col-span-2 bg-purple-50 p-3 rounded-lg border border-purple-200 mt-2">
                    <h4 class="font-semibold text-purple-700 text-sm"> Data & Akun Login Orang Tua</h4>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Nama Orang Tua</label>
                    <input type="text" name="nama_ortu" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">No. HP Orang Tua</label>
                    <input type="text" name="no_hp_ortu" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Username untuk Ortu</label>
                    <input type="text" name="username_ortu" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none" placeholder="Cth: ortu_ahmad">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Password untuk Ortu</label>
                    <input type="text" name="password_ortu" value="ortu123" required class="w-full px-3 py-2 border rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 outline-none">
                </div>

                <div class="md:col-span-2 mt-2">
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2 px-6 rounded-lg transition text-sm">
                        💾 Simpan Data Santri & Buat Akun Ortu
                    </button>
                </div>
            </form>
        </div>

        <!-- TABEL DAFTAR SANTRI -->
        <div class="bg-white rounded-xl shadow p-6">
            <h3 class="text-lg font-bold text-emerald-700 mb-4"> Daftar Santri</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 uppercase text-xs">
                            <th class="py-3 px-4">No</th>
                            <th class="py-3 px-4">Nama Santri</th>
                            <th class="py-3 px-4">NIS</th>
                            <th class="py-3 px-4">JK</th>
                            <th class="py-3 px-4">Nama Ortu</th>
                            <th class="py-3 px-4">Username Ortu</th>
                            <th class="py-3 px-4 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 text-sm">
                        <?php if (empty($santri_list)): ?>
                            <tr>
                                <td colspan="7" class="py-4 text-center text-gray-500">Belum ada data santri.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($santri_list as $index => $santri): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-3 px-4"><?= $index + 1 ?></td>
                                    <td class="py-3 px-4 font-medium"><?= htmlspecialchars($santri['nama']) ?></td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($santri['nis']) ?></td>
                                    <td class="py-3 px-4"><?= $santri['jenis_kelamin'] ?></td>
                                    <td class="py-3 px-4"><?= htmlspecialchars($santri['nama_ortu']) ?></td>
                                    <td class="py-3 px-4 text-xs"><?= htmlspecialchars($santri['username_ortu'] ?? '-') ?></td>
                                    <td class="py-3 px-4 text-center">
                                        <a href="proses_hapus_santri.php?id=<?= $santri['id'] ?>" 
                                           onclick="return confirm('Yakin ingin menghapus santri ini? Akun orang tuanya juga akan ikut terhapus.')"
                                           class="bg-red-500 hover:bg-red-600 text-white py-1 px-3 rounded text-xs transition">
                                           ️ Hapus
                                        </a>
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