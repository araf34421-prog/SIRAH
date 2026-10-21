<?php
session_start();
require 'config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ustadz') { header("Location: login.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_santri = (int) $_POST['id_santri'];
    $tanggal = $_POST['tanggal'];
    $judul = mysqli_real_escape_string($conn, $_POST['judul']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $id_ustadz = $_SESSION['user_id'];
    mysqli_query($conn, "INSERT INTO pr (id_santri, tanggal, judul, deskripsi, id_ustadz) VALUES ($id_santri, '$tanggal', '$judul', '$deskripsi', $id_ustadz)");
    $_SESSION['success'] = "PR berhasil diberikan!";
    header("Location: input_pr.php"); exit;
}
$santri_list = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama FROM santri ORDER BY nama ASC"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Berikan PR</title><script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><style>body{font-family:'Inter',sans-serif;}</style></head>
<body class="bg-slate-50 text-slate-800">
<?php include 'includes/sidebar.php'; ?>
<div class="ml-64 p-8">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">Berikan Tugas Rumah (PR)</h1>
    <?php if (isset($_SESSION['success'])): ?><div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-sm"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div><?php endif; ?>
    
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm max-w-2xl">
        <form method="POST" class="space-y-4">
            <div><label class="block text-sm font-medium text-slate-700 mb-1">Tanggal PR</label><input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"></div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1">Santri</label><select name="id_santri" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"><option value="">-- Pilih Santri --</option><?php foreach ($santri_list as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?></option><?php endforeach; ?></select></div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1">Judul PR</label><input type="text" name="judul" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Cth: Murajaah Al-Mulk"></div>
            <div><label class="block text-sm font-medium text-slate-700 mb-1">Deskripsi / Detail Tugas</label><textarea name="deskripsi" rows="3" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Cth: Hafalkan ayat 1-10, setoran besok pagi"></textarea></div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg transition text-sm">Kirim PR</button>
        </form>
    </div>
</div>
</body>
</html>