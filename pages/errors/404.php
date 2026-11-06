<?php
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Tidak Ditemukan - SIRAH</title>
    <?php include __DIR__ . '/../../includes/head_assets.php'; ?>
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 flex items-center justify-center p-6">
    <main class="max-w-lg text-center bg-white border border-slate-200 rounded-2xl p-8 shadow-sm">
        <p class="text-sm font-bold uppercase tracking-widest text-indigo-600">404</p>
        <h1 class="mt-2 text-2xl font-bold">Halaman tidak ditemukan</h1>
        <p class="mt-2 text-slate-500">Halaman yang Anda cari tidak tersedia.</p>
        <a href="index.php" class="inline-flex mt-6 px-4 py-2 rounded-xl bg-indigo-600 text-white font-semibold">Kembali ke beranda</a>
    </main>
</body>
</html>