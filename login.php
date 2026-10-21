<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center relative overflow-hidden">

    <!-- Background Foto -->
    <div class="absolute inset-0 z-0">
        <img src="https://images.unsplash.com/photo-1585036156171-384164a8c159?w=1920&q=80" 
             alt="Background" 
             class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-slate-900/70"></div>
    </div>

    <!-- Konten Login -->
    <div class="relative z-10 w-full max-w-md px-6">
        
        <!-- Card Login -->
        <div class="glass-effect rounded-2xl shadow-2xl p-8 border border-white/20">
            
            <!-- Logo & Judul -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-600 rounded-xl mb-4 shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-slate-800">Surau Tahfizh Firdaus</h1>
                <p class="text-sm text-slate-500 mt-1">Sistem Informasi Perencanaan Program Hafalan</p>
            </div>

            <!-- Pesan Error -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 px-4 py-3 rounded mb-6 text-sm">
                    <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Form Login -->
            <form action="proses_login.php" method="POST" class="space-y-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Username</label>
                    <input type="text" name="username" required 
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-white/80"
                        placeholder="Masukkan username">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Password</label>
                    <input type="password" name="password" required 
                        class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-white/80"
                        placeholder="Masukkan password">
                </div>

                <button type="submit" 
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-lg transition shadow-lg hover:shadow-xl">
                    Masuk ke Sistem
                </button>
            </form>

            <!-- Info Login -->
            <div class="mt-6 pt-6 border-t border-slate-200">
                <p class="text-xs text-slate-500 text-center">
                    Akses terbatas untuk Ustadz/Ustadzah, Orang Tua, dan Pimpinan Surau Tahfizh Firdaus
                </p>
            </div>
        </div>

        <!-- Copyright -->
        <p class="text-center text-xs text-white/70 mt-6">
            &copy; Surau Tahfizh Firdaus. Jorong Jalikur Patanangan.
        </p>
    </div>

</body>
</html>