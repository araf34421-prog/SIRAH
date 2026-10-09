<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$nama = $_SESSION['nama_lengkap'];
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans">

    <!-- PANGGIL SIDEBAR DARI FILE TERPISAH -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- KONTEN UTAMA (Geser ke kanan sejauh lebar sidebar / ml-64) -->
    <div class="ml-64 p-8">
        
        <!-- Header Halaman -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800"> Dashboard</h2>
            <div class="bg-white px-4 py-2 rounded-lg shadow-sm text-sm text-gray-600">
                Hari ini: <b><?= date('d F Y') ?></b>
            </div>
        </div>

        <!-- Kartu Statistik / Info -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-emerald-500">
                <h3 class="text-gray-500 text-sm font-medium">Status Sistem</h3>
                <p class="text-2xl font-bold text-gray-800 mt-2">Aktif</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-blue-500">
                <h3 class="text-gray-500 text-sm font-medium">Role Anda</h3>
                <p class="text-2xl font-bold text-gray-800 mt-2"><?= ucfirst($role) ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow border-l-4 border-purple-500">
                <h3 class="text-gray-500 text-sm font-medium">Pengguna</h3>
                <p class="text-xl font-bold text-gray-800 mt-2 truncate"><?= htmlspecialchars($nama) ?></p>
            </div>
        </div>

        <!-- Pesan Selamat Datang -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Selamat Datang, <?= htmlspecialchars($nama) ?>! 👋</h3>
            
            <!-- Info Singkat Berdasarkan Role (Tetap Dipertahankan) -->
            <?php if($role === 'ustadz'): ?>
                <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-lg">
                    <p class="text-emerald-800">💡 <b>Tips Ustadz:</b> Anda dapat mulai menambahkan data santri di menu <b>Kelola Santri</b> sebelum menginput hafalan harian mereka.</p>
                </div>
            <?php elseif($role === 'pimpinan'): ?>
                <div class="bg-orange-50 border border-orange-200 p-4 rounded-lg">
                    <p class="text-orange-800">💡 <b>Tips Pimpinan:</b> Pastikan semua ustadz/ustadzah sudah terdaftar di menu <b>Kelola Ustadz</b> sebelum memulai kegiatan belajar.</p>
                </div>
            <?php else: ?>
                <div class="bg-purple-50 border border-purple-200 p-4 rounded-lg">
                    <p class="text-purple-800">💡 <b>Info Orang Tua:</b> Anda dapat memantau perkembangan hafalan anak Anda melalui menu di sidebar.</p>
                </div>
            <?php endif; ?>
        </div>

    </div> <!-- End Konten Utama -->

</body>
</html>