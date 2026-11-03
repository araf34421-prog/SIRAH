<?php
session_start();
require 'config/database.php';

// Keamanan: Hanya Ustadz atau Pimpinan
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'])) { 
    header("Location: login.php"); 
    exit; 
}

// Otomatis tambahkan kolom status_ortu di tabel pr jika belum ada
$cek_kolom_pr = mysqli_query($conn, "SHOW COLUMNS FROM pr LIKE 'status_ortu'");
if (mysqli_num_rows($cek_kolom_pr) === 0) {
    mysqli_query($conn, "ALTER TABLE pr ADD COLUMN status_ortu ENUM('Belum','Selesai') DEFAULT 'Belum' AFTER deskripsi");
}

$id_ustadz = $_SESSION['user_id'];
$santri_list = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama, nis FROM santri ORDER BY nama ASC"), MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {   
    $tanggal = $_POST['tanggal'];
    $judul = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
    
    // Fitur Baru: Bisa berikan PR ke semua santri sekaligus
    if ($_POST['id_santri'] === 'semua') {
        foreach ($santri_list as $s) {
            $id_s = $s['id'];
            mysqli_query($conn, "INSERT INTO pr (id_santri, tanggal, judul, deskripsi, id_ustadz) VALUES ($id_s, '$tanggal', '$judul', '$deskripsi', $id_ustadz)");
        }
        $_SESSION['success'] = "PR berhasil diberikan kepada SEMUA santri!";
    } else {
        $id_santri = (int) $_POST['id_santri'];
        mysqli_query($conn, "INSERT INTO pr (id_santri, tanggal, judul, deskripsi, id_ustadz) VALUES ($id_santri, '$tanggal', '$judul', '$deskripsi', $id_ustadz)");
        $_SESSION['success'] = "PR berhasil diberikan kepada santri yang dipilih!";
    }
    
    header("Location: input_pr.php"); 
    exit;
}

// Ambil riwayat PR yang diberikan hari ini
$riwayat_query = "SELECT p.*, s.nama as nama_santri FROM pr p JOIN santri s ON p.id_santri = s.id WHERE p.tanggal = CURDATE() ORDER BY p.id DESC";
$riwayat_list = mysqli_fetch_all(mysqli_query($conn, $riwayat_query), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berikan PR - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- Header Halaman -->
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 flex items-center gap-3">
                    <div class="p-2 bg-orange-100 rounded-lg"><i data-lucide="clipboard-list" class="w-6 h-6 text-orange-600"></i></div>
                    Berikan Tugas Rumah (PR)
                </h1>
                <p class="text-sm text-slate-500 mt-2">Berikan target hafalan atau muraja'ah mandiri di rumah untuk santri.</p>
            </div>
            <div class="bg-white border border-slate-200 px-5 py-2.5 rounded-xl shadow-sm text-sm font-medium text-slate-700 flex items-center gap-2">
                <i data-lucide="calendar" class="w-4 h-4 text-orange-500"></i> <?= date('d F Y') ?>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-orange-50 border border-orange-200 text-orange-700 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-3 shadow-sm">
                <i data-lucide="check-circle" class="w-5 h-5"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- GRID 2 KOLOM (FORM & INFORMASI) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            
            <!-- FORM INPUT PR -->
            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                        <i data-lucide="pen-tool" class="w-4 h-4 text-orange-600"></i> Formulir Penugasan
                    </h3>
                </div>
                
                <form method="POST" class="p-6 space-y-6" autocomplete="off">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Tanggal -->
                        <div>
                            <label class="block text-xs font-bold tracking-wide text-slate-500 uppercase mb-2">Tanggal PR</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="calendar-days" class="h-4 w-4 text-slate-400"></i>
                                </div>
                                <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none text-slate-700">
                            </div>
                        </div>

                        <!-- Pilihan Santri -->
                        <div>
                            <label class="block text-xs font-bold tracking-wide text-slate-500 uppercase mb-2">Penerima Tugas</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="users" class="h-4 w-4 text-slate-400"></i>
                                </div>
                                <select name="id_santri" required class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 bg-slate-50 hover:bg-white transition outline-none appearance-none cursor-pointer">
                                    <option value="" disabled selected>-- Pilih Santri --</option>
                                    <option value="semua" class="font-bold text-orange-600">✨ BERIKAN KE SEMUA SANTRI ✨</option>
                                    <optgroup label="Daftar Individu">
                                        <?php foreach ($santri_list as $s): ?>
                                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?> <?= $s['nis'] ? '('.$s['nis'].')' : '' ?></option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Judul PR -->
                    <div>
                        <label class="block text-xs font-bold tracking-wide text-slate-500 uppercase mb-2">Judul Tugas / PR</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="type" class="h-4 w-4 text-slate-400"></i>
                            </div>
                            <input type="text" name="judul" required class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none" placeholder="Cth: Muraja'ah Surat Al-Mulk">
                        </div>
                    </div>

                    <!-- Deskripsi PR -->
                    <div>
                        <label class="block text-xs font-bold tracking-wide text-slate-500 uppercase mb-2">Deskripsi / Instruksi Detail</label>
                        <textarea name="deskripsi" rows="4" required class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none resize-none" placeholder="Cth: Lakukan muraja'ah mandiri untuk ayat 1-15 bersama orang tua di rumah. Besok pagi akan disimak kembali."></textarea>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button type="submit" class="bg-orange-600 hover:bg-orange-700 text-white font-semibold py-3 px-8 rounded-xl transition shadow-lg shadow-orange-200 flex items-center gap-2 focus:ring-4 focus:ring-orange-100">
                            <i data-lucide="send" class="w-5 h-5"></i> Kirim Tugas (PR)
                        </button>
                    </div>
                </form>
            </div>

            <!-- SIDE INFO / TIPS -->
            <div class="space-y-6">
                <div class="bg-gradient-to-br from-orange-500 to-amber-600 rounded-2xl p-6 text-white shadow-sm">
                    <h4 class="font-semibold mb-2 flex items-center gap-2"><i data-lucide="lightbulb" class="w-5 h-5"></i> Tips Penugasan</h4>
                    <p class="text-sm text-orange-50 leading-relaxed mb-4">Tugas rumah (PR) akan langsung terhubung dan dapat dilihat oleh akun Wali Santri / Orang Tua.</p>
                    <ul class="text-xs text-orange-100 space-y-3">
                        <li class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i> <b>Lebih Cepat:</b> Gunakan opsi "BERIKAN KE SEMUA SANTRI" jika PR bersifat umum (kelas).</li>
                        <li class="flex items-start gap-2"><i data-lucide="check-circle" class="w-4 h-4 shrink-0"></i> <b>Spesifik:</b> Berikan deskripsi yang jelas (surat dan ayatnya) agar orang tua mudah membimbing di rumah.</li>
                    </ul>
                </div>
            </div>

        </div>

        <!-- TABEL RIWAYAT HARI INI -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                    <i data-lucide="clock" class="w-5 h-5 text-slate-400"></i> PR yang Diberikan Hari Ini
                </h3>
                <span class="bg-slate-200 text-slate-600 text-xs font-bold px-3 py-1 rounded-full"><?= count($riwayat_list) ?> Tugas</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-slate-400 text-xs uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-4 px-6 font-semibold w-12 text-center">No</th>
                            <th class="py-4 px-6 font-semibold">Nama Santri</th>
                            <th class="py-4 px-6 font-semibold">Judul PR</th>
                            <th class="py-4 px-6 font-semibold">Instruksi / Deskripsi</th>
                            <th class="py-4 px-6 font-semibold text-center">Status Wali Santri</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-600 divide-y divide-slate-100">
                        <?php if (empty($riwayat_list)): ?>
                            <tr>
                                <td colspan="5" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <i data-lucide="inbox" class="w-10 h-10 mb-3 opacity-50"></i>
                                        <p>Belum ada PR yang diberikan hari ini.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $index => $r): ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-4 px-6 text-center text-slate-400 font-medium"><?= $index + 1 ?></td>
                                    <td class="py-4 px-6 font-bold text-slate-800">
                                        <div class="flex items-center gap-2">
                                            <div class="w-7 h-7 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center text-xs font-bold">
                                                <?= strtoupper(substr($r['nama_santri'], 0, 1)) ?>
                                            </div>
                                            <?= htmlspecialchars($r['nama_santri']) ?>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 font-semibold text-slate-700">
                                        <?= htmlspecialchars($r['judul']) ?>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-slate-500 leading-relaxed max-w-md">
                                        <?= nl2br(htmlspecialchars($r['deskripsi'])) ?>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold <?= ($r['status_ortu'] ?? 'Belum') === 'Selesai' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                                            <?= ($r['status_ortu'] ?? 'Belum') === 'Selesai' ? '✅ Sudah Disimak Ortu' : '⏳ Belum Disimak' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
