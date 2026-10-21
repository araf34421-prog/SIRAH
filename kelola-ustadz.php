<?php
session_start();
require 'config/database.php';

// 🔒 Keamanan: Hanya Pimpinan
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') {
    header("Location: login.php");
    exit;
}

// === PROSES TAMBAH USTADZ ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_ustadz'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $nama_lokal = mysqli_real_escape_string($conn, $_POST['nama_lokal']);

    // 1. Cek apakah Lokal sudah ada, jika belum buat baru
    $cek_lokal = mysqli_query($conn, "SELECT id FROM halaqah WHERE nama_halaqah = '$nama_lokal'");
    if (mysqli_num_rows($cek_lokal) > 0) {
        $id_halaqah = mysqli_fetch_assoc($cek_lokal)['id'];
    } else {
        mysqli_query($conn, "INSERT INTO halaqah (nama_halaqah) VALUES ('$nama_lokal')");
        $id_halaqah = mysqli_insert_id($conn);
    }

    // 2. Cek Username
    $cek_user = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek_user) > 0) {
        $_SESSION['error'] = "Username sudah digunakan!";
    } else {
        // 3. Simpan Ustadz beserta ID Lokalnya
        $sql = "INSERT INTO users (username, password, nama_lengkap, role, id_halaqah) 
                VALUES ('$username', '$password', '$nama_lengkap', 'ustadz', $id_halaqah)";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "Ustadz berhasil ditambahkan ke $nama_lokal!";
        } else {
            $_SESSION['error'] = "Gagal menyimpan data.";
        }
    }
    header("Location: kelola-ustadz.php");
    exit;
}

// === PROSES HAPUS USTADZ ===
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM users WHERE id = $id AND role = 'ustadz'");
    $_SESSION['success'] = "Ustadz berhasil dihapus.";
    header("Location: kelola-ustadz.php");
    exit;
}

// Ambil data ustadz beserta nama lokalnya
$ustadz_list = mysqli_fetch_all(mysqli_query($conn, "
    SELECT u.*, h.nama_halaqah 
    FROM users u 
    LEFT JOIN halaqah h ON u.id_halaqah = h.id 
    WHERE u.role = 'ustadz' 
    ORDER BY u.id DESC
"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Ustadz - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800">

<?php include 'includes/sidebar.php'; ?>

<div class="ml-64 p-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
            <i data-lucide="users" class="w-7 h-7 text-indigo-600"></i>
            Manajemen Ustadz/Ustadzah
        </h1>
        <p class="text-sm text-slate-500 mt-1">Tambahkan akun pengajar dan tentukan lokal mengajarnya.</p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded-lg mb-6 text-sm flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- FORM TAMBAH USTADZ -->
    <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6 shadow-sm">
        <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <i data-lucide="user-plus" class="w-5 h-5 text-indigo-600"></i> Tambah Ustadz Baru
        </h3>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Cth: Ustadz Ahmad">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Username</label>
                <input type="text" name="username" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Cth: ustadz_ahmad">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Password Default</label>
                <input type="text" name="password" value="ustadz123" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Lokal Mengajar</label>
                <input type="text" name="nama_lokal" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Cth: Lokal 1 / Juz 30">
                <p class="text-[10px] text-slate-400 mt-1">*Otomatis dibuat jika lokal belum ada</p>
            </div>
            <div class="md:col-span-4 flex justify-end">
                <button type="submit" name="tambah_ustadz" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg transition text-sm flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Simpan Ustadz
                </button>
            </div>
        </form>
    </div>

    <!-- TABEL DAFTAR USTADZ -->
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
            <i data-lucide="list" class="w-5 h-5 text-indigo-600"></i> Daftar Ustadz Terdaftar
        </h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">No</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Nama Ustadz</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Username</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider">Lokal Mengajar</th>
                        <th class="py-3 px-4 text-xs font-semibold text-slate-500 uppercase tracking-wider text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-sm">
                    <?php if (empty($ustadz_list)): ?>
                        <tr><td colspan="5" class="py-8 text-center text-slate-500">Belum ada data ustadz.</td></tr>
                    <?php else: ?>
                        <?php foreach ($ustadz_list as $index => $u): ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50">
                            <td class="py-3 px-4 text-slate-500"><?= $index + 1 ?></td>
                            <td class="py-3 px-4 font-medium text-slate-800"><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                            <td class="py-3 px-4 text-slate-600"><?= htmlspecialchars($u['username']) ?></td>
                            <td class="py-3 px-4">
                                <span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded text-xs font-bold border border-indigo-100">
                                    <?= htmlspecialchars($u['nama_halaqah'] ?? 'Belum ditentukan') ?>
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <a href="?hapus=<?= $u['id'] ?>" onclick="return confirm('Hapus ustadz ini?')" class="text-red-600 hover:text-red-800 text-xs font-medium flex items-center justify-center gap-1 mx-auto w-fit">
                                    <i data-lucide="trash-2" class="w-3 h-3"></i> Hapus
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