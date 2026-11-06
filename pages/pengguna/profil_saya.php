<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/init_rbac.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = (int) $_SESSION['user_id'];
$role    = $_SESSION['role'];

// Ambil data user saat ini
$user_q = mysqli_query($conn, "SELECT u.*, h.nama_halaqah 
                               FROM users u 
                               LEFT JOIN halaqah h ON (u.id_halaqah = h.id OR h.id_ustadz = u.id) 
                               WHERE u.id = $id_user LIMIT 1");
$user_data = mysqli_fetch_assoc($user_q);

// Jika role ortu, ambil data santri terkait untuk menampilkan/mengedit No. HP WhatsApp
$santri_ortu = null;
if ($role === 'ortu') {
    $s_q = mysqli_query($conn, "SELECT * FROM santri WHERE id_user_ortu = $id_user LIMIT 1");
    $santri_ortu = mysqli_fetch_assoc($s_q);
}

// === 1. PROSES UPDATE PROFIL ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama_baru   = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $username_br = mysqli_real_escape_string($conn, trim($_POST['username']));
    
    // Cek apakah username dipakai orang lain
    $cek_u = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username_br' AND id != $id_user");
    if (mysqli_num_rows($cek_u) > 0) {
        $_SESSION['error'] = "Username '$username_br' sudah digunakan oleh pengguna lain!";
    } else {
        mysqli_query($conn, "UPDATE users SET nama_lengkap = '$nama_baru', username = '$username_br' WHERE id = $id_user");
        $_SESSION['nama_lengkap'] = stripslashes($nama_baru);
        $_SESSION['username']     = stripslashes($username_br);

        // Jika ustadz/ah, sinkronkan juga nama_pengajar di tabel halaqah
        if ($role === 'ustadz') {
            mysqli_query($conn, "UPDATE halaqah SET nama_pengajar = '$nama_baru' WHERE id_ustadz = $id_user");
        }

        // Jika ortu, update juga nama_ortu & no_hp_ortu di tabel santri
        if ($role === 'ortu' && isset($_POST['no_hp_ortu'])) {
            $hp_baru = mysqli_real_escape_string($conn, trim($_POST['no_hp_ortu']));
            mysqli_query($conn, "UPDATE santri SET nama_ortu = '$nama_baru', no_hp_ortu = '$hp_baru' WHERE id_user_ortu = $id_user");
        }

        $_SESSION['success'] = "Data profil Anda berhasil diperbarui!";
    }
    header("Location: profil_saya.php");
    exit;
}

// === 2. PROSES GANTI PASSWORD ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    $pass_lama       = $_POST['password_lama'] ?? '';
    $pass_baru       = $_POST['password_baru'] ?? '';
    $pass_konfirmasi = $_POST['konfirmasi_password'] ?? '';

    if (!password_verify($pass_lama, $user_data['password'])) {
        $_SESSION['error'] = "Password saat ini yang Anda masukkan salah!";
    } elseif (strlen($pass_baru) < 5) {
        $_SESSION['error'] = "Password baru minimal harus terdiri dari 5 karakter!";
    } elseif ($pass_baru !== $pass_konfirmasi) {
        $_SESSION['error'] = "Konfirmasi password baru tidak cocok!";
    } else {
        $hash_baru = password_hash($pass_baru, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password = '$hash_baru' WHERE id = $id_user");
        $_SESSION['success'] = "Password akun Anda berhasil diubah! Gunakan password baru saat login berikutnya.";
    }
    header("Location: profil_saya.php");
    exit;
}

$label_role = ucfirst($role);
if ($role === 'ustadz') {
    $label_role = isPengajarUstadzah($conn, $id_user) ? 'Ustadzah (Pengajar Putri)' : 'Ustadz (Pengajar Putra)';
} elseif ($role === 'ortu') {
    $label_role = 'Wali Santri / Orang Tua';
} elseif ($role === 'operator') {
    $label_role = 'Operator Utama Sistem';
}
?>
<?php
$page_title = "Profil & Keamanan Akun - Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-3">
                <div class="p-2.5 bg-indigo-100 rounded-xl"><i data-lucide="user-cog" class="w-6 h-6 text-indigo-600"></i></div>
                Pengaturan Profil & Kata Sandi
            </h1>
            <p class="text-sm text-slate-500 mt-1">Kelola informasi identitas akun Anda dan perbarui kata sandi secara berkala untuk menjaga keamanan.</p>
        </div>

        <!-- Notifikasi -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3.5 rounded-xl mb-6 text-xs font-semibold flex items-center gap-2.5 shadow-sm">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3.5 rounded-xl mb-6 text-xs font-semibold flex items-center gap-2.5 shadow-sm">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                <span><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
            
            <!-- KOLOM KIRI: KARTU PROFIL & FORM IDENTITAS (6 KOLOM) -->
            <div class="lg:col-span-6 space-y-6">
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="bg-gradient-to-r from-slate-900 to-indigo-950 p-6 text-white flex items-center gap-4">
                        <div class="w-16 h-16 rounded-2xl bg-white/15 border border-white/20 flex items-center justify-center text-2xl font-extrabold text-white shrink-0">
                            <?= strtoupper(substr($user_data['nama_lengkap'], 0, 1)) ?>
                        </div>
                        <div>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-indigo-500/30 border border-indigo-400/40 text-indigo-200">
                                <?= htmlspecialchars($label_role) ?>
                            </span>
                            <h2 class="text-xl font-bold mt-1"><?= htmlspecialchars($user_data['nama_lengkap']) ?></h2>
                            <p class="text-xs text-slate-300 font-mono">@<?= htmlspecialchars($user_data['username']) ?></p>
                        </div>
                    </div>

                    <form method="POST" class="p-6 space-y-4" autocomplete="off"><?php echo csrfTokenField(); ?>
                        <input type="hidden" name="update_profil" value="1">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-100 pb-2">Informasi Dasar Akun</h3>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
                            <div class="relative">
                                <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                                <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($user_data['nama_lengkap']) ?>" required
                                       class="w-full pl-10 pr-4 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-indigo-600 rounded-xl text-sm font-medium outline-none transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Username Login</label>
                            <div class="relative">
                                <i data-lucide="at-sign" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                                <input type="text" name="username" value="<?= htmlspecialchars($user_data['username']) ?>" required
                                       class="w-full pl-10 pr-4 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-indigo-600 rounded-xl text-sm font-mono outline-none transition">
                            </div>
                        </div>

                        <?php if ($role === 'ustadz'): ?>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Lokal / Halaqah Binaan</label>
                            <input type="text" value="<?= htmlspecialchars($user_data['nama_halaqah'] ?: 'Belum ditentukan') ?>" disabled
                                   class="w-full px-3.5 py-2.5 bg-slate-100 border border-slate-200 rounded-xl text-xs font-semibold text-slate-500 cursor-not-allowed">
                        </div>
                        <?php endif; ?>

                        <?php if ($role === 'ortu' && $santri_ortu): ?>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nomor WhatsApp Aktif (Untuk Laporan Ustadz/ah)</label>
                            <div class="relative">
                                <i data-lucide="phone" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                                <input type="text" name="no_hp_ortu" value="<?= htmlspecialchars($santri_ortu['no_hp_ortu'] ?? '') ?>" required
                                       placeholder="Cth: 081234567890"
                                       class="w-full pl-10 pr-4 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-indigo-600 rounded-xl text-sm font-medium outline-none transition">
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="pt-3 flex justify-end">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-6 rounded-xl transition shadow-sm flex items-center gap-2 text-xs">
                                <i data-lucide="save" class="w-4 h-4"></i> Simpan Perubahan Profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- KOLOM KANAN: FORM GANTI PASSWORD MANDIRI (6 KOLOM) -->
            <div class="lg:col-span-6">
                <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center gap-2.5">
                        <i data-lucide="key-round" class="w-5 h-5 text-amber-600"></i>
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Ubah Kata Sandi (Password)</h3>
                            <p class="text-[11px] text-slate-500">Pastikan Anda mengganti password bawaan sistem demi keamanan akun.</p>
                        </div>
                    </div>

                    <form method="POST" class="p-6 space-y-4" autocomplete="off"><?php echo csrfTokenField(); ?>
                        <input type="hidden" name="ganti_password" value="1">

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password Saat Ini <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                                <input type="password" name="password_lama" id="pass1" required placeholder="Masukkan password lama Anda"
                                       class="w-full pl-10 pr-10 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-indigo-600 rounded-xl text-sm outline-none transition">
                                <button type="button" onclick="togglePass('pass1')" class="absolute right-3.5 top-3 text-slate-400 hover:text-slate-600">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password Baru <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <i data-lucide="shield" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                                <input type="password" name="password_baru" id="pass2" required minlength="5" placeholder="Minimal 5 karakter"
                                       class="w-full pl-10 pr-10 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-indigo-600 rounded-xl text-sm outline-none transition">
                                <button type="button" onclick="togglePass('pass2')" class="absolute right-3.5 top-3 text-slate-400 hover:text-slate-600">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ulangi Password Baru <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <i data-lucide="check-square" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                                <input type="password" name="konfirmasi_password" id="pass3" required minlength="5" placeholder="Ketik ulang password baru"
                                       class="w-full pl-10 pr-10 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-indigo-600 rounded-xl text-sm outline-none transition">
                                <button type="button" onclick="togglePass('pass3')" class="absolute right-3.5 top-3 text-slate-400 hover:text-slate-600">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>

                        <div class="p-3.5 bg-amber-50 border border-amber-200/80 rounded-xl text-[11px] text-amber-800 leading-relaxed">
                            <b>Tips Keamanan:</b> Gunakan kombinasi huruf dan angka yang mudah Anda ingat namun sulit ditebak oleh orang lain.
                        </div>

                        <div class="pt-2 flex justify-end">
                            <button type="submit" class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2.5 px-6 rounded-xl transition shadow-sm flex items-center gap-2 text-xs">
                                <i data-lucide="key" class="w-4 h-4 text-amber-400"></i> Perbarui Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>

    </div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>