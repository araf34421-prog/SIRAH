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
    <title>Login Sistem - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .bg-pattern {
            background-image: radial-gradient(rgba(255, 255, 255, 0.12) 1px, transparent 1px);
            background-size: 26px 26px;
        }
        .glass-form {
            background: rgba(255, 255, 255, 0.90);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }
    </style>
</head>
<body class="min-h-screen w-full m-0 p-0 overflow-x-hidden bg-slate-950">

    <!-- Container Utama Full Screen (100% Lebar & Tinggi Layar) -->
    <div class="w-full min-h-screen grid grid-cols-1 lg:grid-cols-12">
        
        <!-- PANEL KIRI: Branding & Identitas Surau (5 Kolom Full Height) -->
        <div class="lg:col-span-5 bg-gradient-to-br from-slate-900 via-indigo-950 to-emerald-950 text-white p-8 md:p-12 xl:p-16 flex flex-col justify-between relative overflow-hidden border-r border-white/10">
            
            <!-- Ornamen Cahaya & Pola Titik -->
            <div class="absolute inset-0 bg-pattern opacity-40"></div>
            <div class="absolute -top-28 -left-28 w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-28 -right-28 w-80 h-80 bg-emerald-500/20 rounded-full blur-3xl"></div>

            <!-- Bagian Atas: Ikon & Nama Surau -->
            <div class="relative z-10">
                <div class="inline-flex items-center gap-4 mb-10">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-950/50 border border-white/20">
                        <i data-lucide="book-open-check" class="w-7 h-7 text-white"></i>
                    </div>
                    <div>
                        <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-400 block">Sistem Informasi</span>
                        <h2 class="text-xl font-extrabold tracking-wide text-white leading-tight">SURAU TAHFIZH</h2>
                    </div>
                </div>

                <h1 class="text-3xl xl:text-4xl font-extrabold leading-tight tracking-tight mb-4">
                    Mushalla Firdaus
                </h1>
                <p class="text-sm xl:text-base text-slate-300 leading-relaxed max-w-md">
                    Platform digital terpadu untuk pencatatan setoran hafalan harian, perencanaan target, evaluasi rapor, dan pemantauan perkembangan santri secara transparan.
                </p>

                <!-- Fitur Highlight -->
                <div class="mt-10 space-y-4 hidden sm:block max-w-md">
                    <div class="flex items-center gap-4 text-xs xl:text-sm text-slate-200 bg-white/5 border border-white/10 p-4 rounded-2xl backdrop-blur-sm">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-300 flex items-center justify-center shrink-0">
                            <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <p class="font-bold text-white">Pencatatan Ziyadah, Muraja'ah & Tasmi'</p>
                            <p class="text-xs text-slate-400 mt-0.5">Terekam otomatis beserta predikat & catatan penguji</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 text-xs xl:text-sm text-slate-200 bg-white/5 border border-white/10 p-4 rounded-2xl backdrop-blur-sm">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/20 text-indigo-300 flex items-center justify-center shrink-0">
                            <i data-lucide="line-chart" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <p class="font-bold text-white">Portal Monitoring Orang Tua</p>
                            <p class="text-xs text-slate-400 mt-0.5">Pantau grafik progres, kehadiran, PR, dan rapor anak</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian Bawah: Lokasi Surau -->
            <div class="relative z-10 mt-10 pt-6 border-t border-white/10 flex items-center gap-3 text-xs text-slate-300">
                <i data-lucide="map-pin" class="w-4 h-4 text-emerald-400 shrink-0"></i>
                <span>Jorong Jalikur Patanangan, Koto Tangah, Kec. Tilatang Kamang, Kab. Agam</span>
            </div>
        </div>

        <!-- PANEL KANAN: Background Foto Surau + Form Login (7 Kolom Full Height) -->
        <div class="lg:col-span-7 relative flex items-center justify-center p-6 sm:p-10 xl:p-16 min-h-screen">
            
            <!-- GAMBAR BACKGROUND SURAU -->
            <!-- Simpan foto surau Anda di: assets/bg-surau.jpg -->
            <div class="absolute inset-0 z-0">
                <img src="assets/surau-sore.jpeg" 
                     onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?w=1920&q=80';" 
                     alt="Background Surau Tahfizh Firdaus" 
                     class="w-full h-full object-cover object-center">
                <!-- Overlay gradasi agar form di atas gambar tetap kontras & jelas -->
                <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-[2px]"></div>
            </div>

            <!-- KARTU FORM LOGIN (Glassmorphism di atas Foto Surau) -->
            <div class="relative z-10 w-full max-w-md glass-form rounded-3xl shadow-2xl border border-white/60 p-8 sm:p-10">
                
                <!-- Header Form -->
                <div class="mb-7">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-200/80 text-indigo-700 text-xs font-bold mb-3">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                        <span>Akses Portal Resmi</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Selamat Datang Kembali</h2>
                    <p class="text-xs sm:text-sm text-slate-600 mt-1.5">Silakan masukkan kredensial akun Anda untuk melanjutkan ke dashboard.</p>
                </div>

                <!-- Notifikasi Error -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3.5 rounded-2xl mb-6 text-xs font-semibold flex items-center gap-3 shadow-sm">
                        <div class="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                            <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                        </div>
                        <span><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Form Input -->
                <form action="proses_login.php" method="POST" class="space-y-5" autocomplete="off">
                    
                    <!-- Username -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Username Akun</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition">
                                <i data-lucide="user" class="w-5 h-5"></i>
                            </div>
                            <input type="text" name="username" required autofocus
                                class="w-full pl-12 pr-4 py-3.5 bg-white/90 hover:bg-white focus:bg-white border border-slate-300 focus:border-indigo-600 rounded-2xl text-sm font-medium text-slate-900 focus:ring-4 focus:ring-indigo-500/15 outline-none transition shadow-sm"
                                placeholder="Cth: ustadz01 / ortu_nama">
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">Kata Sandi (Password)</label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400 group-focus-within:text-indigo-600 transition">
                                <i data-lucide="lock" class="w-5 h-5"></i>
                            </div>
                            <input type="password" name="password" id="inputPassword" required 
                                class="w-full pl-12 pr-12 py-3.5 bg-white/90 hover:bg-white focus:bg-white border border-slate-300 focus:border-indigo-600 rounded-2xl text-sm font-medium text-slate-900 focus:ring-4 focus:ring-indigo-500/15 outline-none transition shadow-sm"
                                placeholder="Masukkan password Anda">
                            <button type="button" onclick="togglePassword()" title="Tampilkan / Sembunyikan Password"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-slate-700 transition">
                                <i data-lucide="eye" id="eyeOpen" class="w-5 h-5"></i>
                                <i data-lucide="eye-off" id="eyeClosed" class="w-5 h-5 hidden"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Tombol Submit -->
                    <div class="pt-2">
                        <button type="submit" 
                            class="w-full bg-indigo-600 hover:bg-indigo-700 active:scale-[0.99] text-white font-bold py-3.5 px-6 rounded-2xl transition-all duration-150 shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2.5 text-sm">
                            <span>Masuk ke Sistem</span>
                            <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </button>
                    </div>
                </form>

                <!-- Informasi Peran Pengguna -->
                <div class="mt-8 pt-6 border-t border-slate-200/80">
                    <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center mb-3">Mendukung Akses Multi-Peran</p>
                    <div class="grid grid-cols-3 gap-2.5">
                        <div class="flex flex-col items-center p-2.5 rounded-xl bg-white/80 border border-slate-200/90 text-center shadow-sm">
                            <i data-lucide="award" class="w-4 h-4 text-indigo-600 mb-1"></i>
                            <span class="text-[11px] font-bold text-slate-700">Ustadz/ah</span>
                        </div>
                        <div class="flex flex-col items-center p-2.5 rounded-xl bg-white/80 border border-slate-200/90 text-center shadow-sm">
                            <i data-lucide="users" class="w-4 h-4 text-emerald-600 mb-1"></i>
                            <span class="text-[11px] font-bold text-slate-700">Wali Santri</span>
                        </div>
                        <div class="flex flex-col items-center p-2.5 rounded-xl bg-white/80 border border-slate-200/90 text-center shadow-sm">
                            <i data-lucide="building-2" class="w-4 h-4 text-amber-600 mb-1"></i>
                            <span class="text-[11px] font-bold text-slate-700">Pimpinan</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <script>
        lucide.createIcons();

        function togglePassword() {
            const input = document.getElementById('inputPassword');
            const eyeOpen = document.getElementById('eyeOpen');
            const eyeClosed = document.getElementById('eyeClosed');

            if (input.type === 'password') {
                input.type = 'text';
                eyeOpen.classList.add('hidden');
                eyeClosed.classList.remove('hidden');
            } else {
                input.type = 'password';
                eyeOpen.classList.remove('hidden');
                eyeClosed.classList.add('hidden');
            }
        }
    </script>
</body>
</html>