<?php
session_start();
require 'config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ortu') { header("Location: login.php"); exit; }
$id_ortu = $_SESSION['user_id'];
$santri = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM santri WHERE id_user_ortu = $id_ortu"));
$pr_list = $santri ? mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM pr WHERE id_santri = {$santri['id']} ORDER BY tanggal DESC"), MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Tugas Rumah Anak</title><script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><style>body{font-family:'Inter',sans-serif;}</style></head>
<body class="bg-slate-50 text-slate-800">
<?php include 'includes/sidebar.php'; ?>
<div class="ml-64 p-8">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">Tugas Rumah (PR) Ananda</h1>
    <?php if (!$santri): ?><div class="bg-amber-100 text-amber-700 p-4 rounded">Data anak tidak ditemukan.</div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($pr_list as $p): ?>
        <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
            <div class="flex justify-between items-start mb-2">
                <h3 class="font-semibold text-slate-900"><?= htmlspecialchars($p['judul']) ?></h3>
                <span class="text-xs text-slate-500 bg-slate-100 px-2 py-1 rounded"><?= date('d M Y', strtotime($p['tanggal'])) ?></span>
            </div>
            <p class="text-sm text-slate-600"><?= htmlspecialchars($p['deskripsi']) ?></p>
        </div>
        <?php endforeach; ?>
        <?php if (empty($pr_list)): ?><div class="col-span-2 bg-white p-8 text-center text-slate-500 rounded-xl border border-slate-200">Belum ada PR yang diberikan.</div><?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</body>
</html>