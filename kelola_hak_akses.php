<?php
session_start();
require 'config/database.php';
require_once 'includes/init_rbac.php';

// 🔒 Keamanan: HANYA OPERATOR yang boleh mengakses halaman pengaturan hak akses ini
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
    header("Location: dashboard.php");
    exit;
}

// === 1. SIMPAN PERUBAHAN MATRIKS HAK AKSES MENU ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_hak_akses'])) {
    $semua_menu = mysqli_fetch_all(mysqli_query($conn, "SELECT id, kode_menu FROM hak_akses_menu"), MYSQLI_ASSOC);

    foreach ($semua_menu as $m) {
        $id_m = (int)$m['id'];
        // Operator selalu memiliki akses penuh (1) ke kelola_hak_akses
        $op   = ($m['kode_menu'] === 'kelola_hak_akses') ? 1 : (isset($_POST['akses_operator'][$id_m]) ? 1 : 0);
        $pimp = isset($_POST['akses_pimpinan'][$id_m]) ? 1 : 0;
        $ust  = isset($_POST['akses_ustadz'][$id_m]) ? 1 : 0;
        $ortu = isset($_POST['akses_ortu'][$id_m]) ? 1 : 0;

        mysqli_query($conn, "UPDATE hak_akses_menu SET 
                                akses_operator = $op,
                                akses_pimpinan = $pimp,
                                akses_ustadz = $ust,
                                akses_ortu = $ortu
                             WHERE id = $id_m");
    }

    $_SESSION['success'] = "Konfigurasi hak akses menu untuk seluruh Actor berhasil diperbarui secara real-time!";
    header("Location: kelola_hak_akses.php");
    exit;
}

// === 2. TAMBAH AKUN ACTOR BARU (OPERATOR / PIMPINAN / USTADZ) ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_akun_actor'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $username     = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password     = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role_baru    = mysqli_real_escape_string($conn, $_POST['role_baru']);

    $cek = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($cek) > 0) {
        $_SESSION['error'] = "Username '$username' sudah terdaftar!";
    } else {
        mysqli_query($conn, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('$username', '$password', '$nama_lengkap', '$role_baru')");
        $_SESSION['success'] = "Akun '$nama_lengkap' dengan role " . strtoupper($role_baru) . " berhasil dibuat!";
    }
    header("Location: kelola_hak_akses.php");
    exit;
}

// === 3. HAPUS AKUN PENGELOLA ===
if (isset($_GET['hapus_user'])) {
    $id_hapus = (int)$_GET['hapus_user'];
    if ($id_hapus !== (int)$_SESSION['user_id']) {
        mysqli_query($conn, "DELETE FROM users WHERE id = $id_hapus");
        $_SESSION['success'] = "Akun pengguna berhasil dihapus.";
    } else {
        $_SESSION['error'] = "Anda tidak dapat menghapus akun Operator yang sedang Anda gunakan login.";
    }
    header("Location: kelola_hak_akses.php");
    exit;
}

// Ambil seluruh daftar menu dikelompokkan per kategori
$menu_res = mysqli_query($conn, "SELECT * FROM hak_akses_menu ORDER BY urutan ASC");
$menu_list = mysqli_fetch_all($menu_res, MYSQLI_ASSOC);

$menu_by_kategori = [];
foreach ($menu_list as $m) {
    $menu_by_kategori[$m['kategori']][] = $m;
}

// Ambil daftar akun Operator & Pimpinan
$admin_users = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM users WHERE role IN ('operator', 'pimpinan') ORDER BY role ASC, nama_lengkap ASC"), MYSQLI_ASSOC);

// Statistik ringkas sistem untuk Operator
$stat_op   = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='operator'"))['c'];
$stat_pimp = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='pimpinan'"))['c'];
$stat_ust  = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='ustadz'"))['c'];
$stat_ortu = (int)mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role='ortu'"))['c'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Hak Akses Actor - Operator Sistem</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Custom Toggle Switch Modern */
        .toggle-checkbox:checked {
            right: 0;
            border-color: #4f46e5;
        }
        .toggle-checkbox:checked + .toggle-label {
            background-color: #4f46e5;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- HEADER OPERATOR -->
        <div class="flex flex-wrap justify-between items-start gap-4 mb-8">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 bg-rose-600 text-white rounded-2xl shadow-lg shadow-rose-200 flex items-center justify-center">
                    <i data-lucide="shield-check" class="w-6 h-6"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-700 border border-rose-200">
                            Super Admin / Operator Panel
                        </span>
                    </div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight mt-1">Kontrol Hak Akses Menu & Actor</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Atur menu apa saja yang dapat diakses oleh Pimpinan, Ustadz/Ustadzah, dan Orang Tua secara dinamis.</p>
                </div>
            </div>

            <button type="button" onclick="document.getElementById('modalTambahActor').classList.remove('hidden')" 
                    class="bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2.5 px-4 rounded-xl transition shadow-sm flex items-center gap-2 text-xs">
                <i data-lucide="user-plus" class="w-4 h-4 text-rose-400"></i>
                <span>Tambah Akun Operator / Pimpinan</span>
            </button>
        </div>

        <!-- RINGKASAN JUMLAH ACTOR -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-rose-600 uppercase tracking-wider">Actor: Operator</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= $stat_op ?> <span class="text-xs font-normal text-slate-400">Akun</span></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center"><i data-lucide="shield" class="w-5 h-5"></i></div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Actor: Pimpinan</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= $stat_pimp ?> <span class="text-xs font-normal text-slate-400">Akun</span></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center"><i data-lucide="building-2" class="w-5 h-5"></i></div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Actor: Ustadz/ah</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= $stat_ust ?> <span class="text-xs font-normal text-slate-400">Akun</span></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center"><i data-lucide="award" class="w-5 h-5"></i></div>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Actor: Wali Santri</p>
                    <p class="text-2xl font-extrabold text-slate-900 mt-1"><?= $stat_ortu ?> <span class="text-xs font-normal text-slate-400">Akun</span></p>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center"><i data-lucide="users" class="w-5 h-5"></i></div>
            </div>
        </div>

        <!-- NOTIFIKASI -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-6 text-xs font-medium flex items-center gap-2.5 shadow-sm">
                <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl mb-6 text-xs font-medium flex items-center gap-2.5 shadow-sm">
                <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                <span><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- TABEL MATRIKS HAK AKSES MENU PER ACTOR -->
        <form method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-8">
            <input type="hidden" name="simpan_hak_akses" value="1">
            
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-900 text-white flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-sm font-bold flex items-center gap-2">
                        <i data-lucide="sliders" class="w-4 h-4 text-rose-400"></i>
                        Matriks Hak Akses Menu Berdasarkan Actor (Role)
                    </h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">Centang kotak pada kolom Actor untuk mengizinkan menu tampil di sidebar dan dapat diakses oleh Actor tersebut.</p>
                </div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-2 px-5 rounded-xl transition shadow-md flex items-center gap-2 text-xs">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Simpan Hak Akses</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 uppercase text-[11px] font-bold tracking-wider border-b border-slate-200">
                            <th class="py-3.5 px-6">Nama Menu & Modul</th>
                            <th class="py-3.5 px-4">File Utama</th>
                            <th class="py-3.5 px-4 text-center w-36 bg-rose-50/50 text-rose-700">Operator</th>
                            <th class="py-3.5 px-4 text-center w-36 bg-amber-50/50 text-amber-700">Pimpinan</th>
                            <th class="py-3.5 px-4 text-center w-36 bg-indigo-50/50 text-indigo-700">Ustadz / Ustadzah</th>
                            <th class="py-3.5 px-4 text-center w-36 bg-emerald-50/50 text-emerald-700">Orang Tua (Wali)</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs divide-y divide-slate-100">
                        <?php foreach ($menu_by_kategori as $kategori => $items): ?>
                            <!-- Baris Pemisah Kategori -->
                            <tr class="bg-slate-100/80">
                                <td colspan="6" class="py-2 px-6 text-[10px] font-extrabold uppercase tracking-widest text-slate-500">
                                    Kategori: <?= htmlspecialchars($kategori) ?>
                                </td>
                            </tr>
                            <?php foreach ($items as $m): 
                                $id_m = (int)$m['id'];
                                $is_locked_op = ($m['kode_menu'] === 'kelola_hak_akses');
                            ?>
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="py-3.5 px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-slate-900 flex items-center justify-center shrink-0">
                                            <i data-lucide="<?= htmlspecialchars($m['icon']) ?>" class="w-4 h-4 <?= htmlspecialchars($m['warna_icon']) ?>"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($m['nama_menu']) ?></p>
                                            <p class="text-[10px] text-slate-400">Kode: <?= htmlspecialchars($m['kode_menu']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500">
                                    <?= htmlspecialchars($m['file_utama']) ?>
                                </td>

                                <!-- Checkbox Operator -->
                                <td class="py-3.5 px-4 text-center bg-rose-50/20">
                                    <?php if ($is_locked_op): ?>
                                        <input type="checkbox" checked disabled class="w-4 h-4 rounded text-rose-600 cursor-not-allowed opacity-70">
                                        <input type="hidden" name="akses_operator[<?= $id_m ?>]" value="1">
                                    <?php else: ?>
                                        <input type="checkbox" name="akses_operator[<?= $id_m ?>]" value="1" <?= $m['akses_operator'] ? 'checked' : '' ?>
                                               class="w-4 h-4 rounded text-rose-600 focus:ring-rose-500 cursor-pointer">
                                    <?php endif; ?>
                                </td>

                                <!-- Checkbox Pimpinan -->
                                <td class="py-3.5 px-4 text-center bg-amber-50/20">
                                    <input type="checkbox" name="akses_pimpinan[<?= $id_m ?>]" value="1" <?= $m['akses_pimpinan'] ? 'checked' : '' ?>
                                           class="w-4 h-4 rounded text-amber-600 focus:ring-amber-500 cursor-pointer">
                                </td>

                                <!-- Checkbox Ustadz/Ustadzah -->
                                <td class="py-3.5 px-4 text-center bg-indigo-50/20">
                                    <input type="checkbox" name="akses_ustadz[<?= $id_m ?>]" value="1" <?= $m['akses_ustadz'] ? 'checked' : '' ?>
                                           class="w-4 h-4 rounded text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                </td>

                                <!-- Checkbox Orang Tua -->
                                <td class="py-3.5 px-4 text-center bg-emerald-50/20">
                                    <input type="checkbox" name="akses_ortu[<?= $id_m ?>]" value="1" <?= $m['akses_ortu'] ? 'checked' : '' ?>
                                           class="w-4 h-4 rounded text-emerald-600 focus:ring-emerald-500 cursor-pointer">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                <span class="text-xs text-slate-500">Perubahan langsung diterapkan pada menu sidebar dan proteksi halaman masing-masing Actor.</span>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-7 rounded-xl transition shadow-md flex items-center gap-2 text-xs">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Simpan Semua Perubahan Hak Akses</span>
                </button>
            </div>
        </form>

        <!-- DAFTAR AKUN OPERATOR & PIMPINAN -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Daftar Akun Operator & Pimpinan Lembaga</h3>
                    <p class="text-xs text-slate-500">Untuk mengelola akun Ustadz/Ustadzah dan Wali Santri, silakan gunakan menu terkait di Sidebar.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 text-slate-400 text-[11px] font-semibold uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-6">Nama Lengkap</th>
                            <th class="py-3 px-4">Username</th>
                            <th class="py-3 px-4 text-center">Role / Actor</th>
                            <th class="py-3 px-6 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs divide-y divide-slate-100">
                        <?php foreach ($admin_users as $au): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-3.5 px-6 font-bold text-slate-900"><?= htmlspecialchars($au['nama_lengkap']) ?></td>
                            <td class="py-3.5 px-4 font-mono text-slate-600"><?= htmlspecialchars($au['username']) ?></td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase <?= $au['role'] === 'operator' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700' ?>">
                                    <?= htmlspecialchars($au['role']) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-6 text-right">
                                <?php if ((int)$au['id'] !== (int)$_SESSION['user_id']): ?>
                                    <a href="kelola_hak_akses.php?hapus_user=<?= $au['id'] ?>" onclick="return confirm('Hapus akun ini?')" class="text-rose-600 hover:underline font-semibold">Hapus</a>
                                <?php else: ?>
                                    <span class="text-slate-400 italic">Akun Anda (Aktif)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- MODAL TAMBAH AKUN OPERATOR / PIMPINAN -->
    <div id="modalTambahActor" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white border border-slate-200 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="px-6 py-4 bg-slate-900 text-white flex justify-between items-center">
                <h3 class="text-sm font-bold flex items-center gap-2">
                    <i data-lucide="user-plus" class="w-4 h-4 text-rose-400"></i> Tambah Akun Actor Sistem
                </h3>
                <button type="button" onclick="document.getElementById('modalTambahActor').classList.add('hidden')" class="text-slate-400 hover:text-white">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4" autocomplete="off">
                <input type="hidden" name="tambah_akun_actor" value="1">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Actor / Role</label>
                    <select name="role_baru" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold">
                        <option value="operator">Operator (Super Admin Pengelola Web)</option>
                        <option value="pimpinan">Pimpinan Surau</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" required placeholder="Cth: Operator Utama" class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Username Login</label>
                    <input type="text" name="username" required placeholder="Cth: operator02" class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-sm font-mono">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                    <input type="text" name="password" value="operator123" required class="w-full px-3.5 py-2 border border-slate-300 rounded-xl text-sm font-mono">
                </div>
                <div class="pt-2 flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modalTambahActor').classList.add('hidden')" class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold">Batal</button>
                    <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-semibold py-2 px-5 rounded-xl text-xs">Simpan Akun</button>
                </div>
            </form>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>