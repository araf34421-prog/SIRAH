<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require_once __DIR__ . '/../../includes/init_rbac.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<?php
$page_title = "Masuk — SIRAH Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<body class="min-h-screen bg-white text-slate-950 antialiased selection:bg-slate-900 selection:text-white">

    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-2">
        
        <!-- PANEL KIRI: FORM AUTENTIKASI -->
        <div class="flex flex-col justify-between p-8 sm:p-12 lg:p-16 border-r border-slate-200 bg-white">
            
            <!-- Top Brand -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-6 h-6 rounded bg-slate-950 text-white flex items-center justify-center font-mono text-xs font-semibold">
                        S
                    </div>
                    <span class="text-xs font-semibold tracking-tight text-slate-950">SIRAH</span>
                    <span class="text-slate-300">/</span>
                    <span class="text-xs text-slate-500">Surau Tahfizh Firdaus</span>
                </div>
                <span class="text-[11px] font-mono text-slate-400">v2.6</span>
            </div>

            <!-- Center Login Box -->
            <div class="w-full max-w-[340px] mx-auto my-12">
                <div class="mb-6">
                    <h1 class="text-xl font-semibold text-slate-950 tracking-tight">Masuk ke akun Anda</h1>
                    <p class="text-xs text-slate-500 mt-1">Gunakan kredensial Asatidz, Wali Santri, atau Pengelola.</p>
                </div>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-rose-50 border border-rose-200 text-rose-700 px-3.5 py-2.5 rounded-md mb-5 text-xs font-medium flex items-center gap-2">
                        <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                        <span><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
                    </div>
                <?php endif; ?>

                <form action="proses_login.php" method="POST" class="space-y-4" autocomplete="off">
                    <?php echo csrfTokenField(); ?>
                    
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Username</label>
                        <input type="text" name="username" required autofocus
                            class="w-full px-3 py-2 bg-white border border-slate-300 rounded-md text-xs text-slate-950 placeholder:text-slate-400 outline-none"
                            placeholder="nama_pengguna">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-1.5">Kata Sandi</label>
                        <div class="relative">
                            <input type="password" name="password" id="inputPassword" required 
                                class="w-full pl-3 pr-9 py-2 bg-white border border-slate-300 rounded-md text-xs text-slate-950 placeholder:text-slate-400 outline-none"
                                placeholder="••••••••••••">
                            <button type="button" onclick="togglePassword()" tabindex="-1"
                                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-700">
                                <i data-lucide="eye" id="eyeOpen" class="w-3.5 h-3.5"></i>
                                <i data-lucide="eye-off" id="eyeClosed" class="w-3.5 h-3.5 hidden"></i>
                            </button>
                        </div>
                    </div>

                    <div class="pt-1">
                        <button type="submit" 
                            class="w-full bg-slate-950 hover:bg-slate-800 text-white font-medium py-2.5 px-4 rounded-md transition text-xs flex items-center justify-center gap-2">
                            <span>Lanjutkan</span>
                            <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Bottom Metadata -->
            <div class="flex items-center justify-between text-[11px] text-slate-400 border-t border-slate-100 pt-4">
                <span>Jorong Jalikur Patanangan, Kab. Agam</span>
                <span class="font-mono">Mushalla Firdaus</span>
            </div>
        </div>

        <!-- PANEL KANAN: FULL-BLEED PHOTOGRAPHY -->
        <div class="hidden lg:flex relative bg-slate-950 items-end p-14 overflow-hidden">
            <img src="assets/surau-sore.jpeg" 
                 onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1584551246679-0daf3d275d0f?w=1920&q=80';" 
                 alt="Surau Tahfizh Firdaus" 
                 class="absolute inset-0 w-full h-full object-cover object-center opacity-80">
            
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

            <div class="relative z-10 max-w-md text-white">
                <p class="font-mono text-[11px] uppercase tracking-widest text-white/60 mb-2">Sistem Informasi Akademik Tahfizh</p>
                <blockquote class="text-lg font-medium leading-snug text-white/95">
                    "Pencatatan setoran Ziyadah, Muraja'ah, ujian Tasmi', dan pelaporan wali santri dalam satu ruang kerja terpadu."
                </blockquote>
            </div>
        </div>

    </div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>