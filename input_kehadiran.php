<?php
session_start();
require 'config/database.php';

// Keamanan: Hanya Ustadz / Pimpinan
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

$id_ustadz = $_SESSION['user_id'];

// Proses simpan absensi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'];
    
    foreach ($_POST['status'] as $id_santri => $status) {
        $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan'][$id_santri] ?? '');
        
        // Cek apakah sudah ada absensi untuk santri ini di tanggal tersebut
        $cek = mysqli_query($conn, "SELECT id FROM absensi WHERE id_santri = $id_santri AND tanggal = '$tanggal'");
        
        if (mysqli_num_rows($cek) > 0) {
            // Update jika sudah ada
            mysqli_query($conn, "UPDATE absensi SET status = '$status', keterangan = '$keterangan', id_ustadz = $id_ustadz WHERE id_santri = $id_santri AND tanggal = '$tanggal'");
        } else {
            // Insert jika belum ada
            mysqli_query($conn, "INSERT INTO absensi (id_santri, tanggal, status, keterangan, id_ustadz) VALUES ($id_santri, '$tanggal', '$status', '$keterangan', $id_ustadz)");
        }
    }
    
    $_SESSION['success'] = "Data absensi tanggal " . date('d F Y', strtotime($tanggal)) . " berhasil disimpan!";
    header("Location: input_kehadiran.php?tanggal=$tanggal");
    exit;
}

// Ambil tanggal (default hari ini)
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// Ambil SEMUA santri
$santri_list = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama, nis FROM santri ORDER BY nama ASC"), MYSQLI_ASSOC);

// Ambil data absensi yang sudah diinput untuk tanggal tersebut
$absensi_hari_ini = [];
$statistik = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0, 'Belum' => count($santri_list)];

if (!empty($santri_list)) {
    $absensi_result = mysqli_query($conn, "SELECT * FROM absensi WHERE tanggal = '$tanggal'");
    while ($row = mysqli_fetch_assoc($absensi_result)) {
        $absensi_hari_ini[$row['id_santri']] = $row;
        // Hitung statistik (Status di db Anda sepertinya 'H','S','I','A' atau 'Hadir','Sakit', dsb. Kita asumsikan huruf depan)
        $inisial = strtoupper(substr($row['status'], 0, 1)); 
        if (isset($statistik[$inisial])) {
            $statistik[$inisial]++;
            $statistik['Belum']--;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Kehadiran - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        /* Hilangkan radio circle asli */
        .status-radio input[type="radio"] { display: none; }
        
        /* Desain label radio ketika TERPILIH */
        .status-radio input[value^="H"]:checked + div { background-color: #10b981; color: white; border-color: #10b981; } /* Emerald untuk Hadir */
        .status-radio input[value^="S"]:checked + div { background-color: #3b82f6; color: white; border-color: #3b82f6; } /* Blue untuk Sakit */
        .status-radio input[value^="I"]:checked + div { background-color: #f59e0b; color: white; border-color: #f59e0b; } /* Amber untuk Izin */
        .status-radio input[value^="A"]:checked + div { background-color: #f43f5e; color: white; border-color: #f43f5e; } /* Rose untuk Alfa */
        
        /* Desain hover agar terlihat bisa diklik */
        .status-radio div:hover { filter: brightness(0.95); }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

<?php include 'includes/sidebar.php'; ?>

<div class="ml-64 p-8">
    
    <!-- Header Halaman -->
    <div class="flex justify-between items-end mb-8">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 flex items-center gap-3">
                <div class="p-2 bg-indigo-100 rounded-lg"><i data-lucide="calendar-check" class="w-6 h-6 text-indigo-600"></i></div>
                Input Kehadiran
            </h1>
            <p class="text-sm text-slate-500 mt-2">Pilih tanggal dan catat presensi harian santri secara massal.</p>
        </div>
        
        <!-- Form Filter Tanggal -->
        <form method="GET" class="bg-white border border-slate-200 px-3 py-2 rounded-xl shadow-sm flex items-center gap-2">
            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider pl-1">Tanggal :</label>
            <input type="date" name="tanggal" value="<?= $tanggal ?>" onchange="this.form.submit()" 
                class="bg-transparent text-sm font-semibold text-slate-700 outline-none cursor-pointer">
        </form>
    </div>

    <!-- Notifikasi Sukses -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-3 shadow-sm animate-fade-in">
            <i data-lucide="check-circle" class="w-5 h-5"></i>
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($santri_list)): ?>
        <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-4 rounded-xl mb-6 text-sm flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
            Belum ada data santri. Silakan tambahkan santri di menu <b>Kelola Santri</b> terlebih dahulu.
        </div>
    <?php else: ?>

    <!-- Papan Statistik Kehadiran -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total Santri</p>
            <p class="text-2xl font-black text-slate-800"><?= count($santri_list) ?></p>
        </div>
        <div class="bg-white border border-emerald-200 border-b-4 border-b-emerald-500 rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] font-bold text-emerald-500 uppercase tracking-widest mb-1">Hadir</p>
            <p class="text-2xl font-black text-emerald-600"><?= $statistik['H'] ?></p>
        </div>
        <div class="bg-white border border-blue-200 border-b-4 border-b-blue-500 rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest mb-1">Sakit</p>
            <p class="text-2xl font-black text-blue-600"><?= $statistik['S'] ?></p>
        </div>
        <div class="bg-white border border-amber-200 border-b-4 border-b-amber-500 rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] font-bold text-amber-500 uppercase tracking-widest mb-1">Izin</p>
            <p class="text-2xl font-black text-amber-600"><?= $statistik['I'] ?></p>
        </div>
        <div class="bg-white border border-rose-200 border-b-4 border-b-rose-500 rounded-xl p-4 shadow-sm text-center">
            <p class="text-[10px] font-bold text-rose-500 uppercase tracking-widest mb-1">Alfa</p>
            <p class="text-2xl font-black text-rose-600"><?= $statistik['A'] ?></p>
        </div>
    </div>

    <!-- Form Absensi Massal -->
    <form method="POST" class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-10">
        <input type="hidden" name="tanggal" value="<?= $tanggal ?>">
        
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
            <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                <i data-lucide="users" class="w-4 h-4 text-indigo-600"></i> Daftar Presensi Santri
            </h3>
            <!-- Tombol Simpan Atas (jika data banyak) -->
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-1.5 px-4 rounded-lg transition shadow flex items-center gap-2 text-xs">
                <i data-lucide="save" class="w-3 h-3"></i> Simpan
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-white text-slate-400 text-[11px] uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="py-4 px-6 font-semibold w-10 text-center">No</th>
                        <th class="py-4 px-4 font-semibold">Profil Santri</th>
                        <th class="py-4 px-4 font-semibold text-center w-[340px]">Status Kehadiran</th>
                        <th class="py-4 px-6 font-semibold">Keterangan Opsional</th>
                    </tr>
                </thead>
                <tbody class="text-sm divide-y divide-slate-100">
                    <?php 
                    // Set status DB (sesuaikan dgn ENUM db anda: 'Hadir','Sakit','Izin','Alfa' atau 'H','S','I','A')
                    // Default nilai form kita gunakan lengkap
                    $opsi_status = ['Hadir', 'Sakit', 'Izin', 'Alfa']; 

                    foreach ($santri_list as $index => $s): 
                        // Jika belum ada data di DB, default ke Hadir
                        $status_aktif = $absensi_hari_ini[$s['id']]['status'] ?? 'Hadir';
                        
                        // Perbaikan jika format DB hanya 'H', konversi ke 'Hadir' untuk UI form
                        if ($status_aktif == 'H') $status_aktif = 'Hadir';
                        if ($status_aktif == 'S') $status_aktif = 'Sakit';
                        if ($status_aktif == 'I') $status_aktif = 'Izin';
                        if ($status_aktif == 'A') $status_aktif = 'Alfa';

                        $keterangan_aktif = $absensi_hari_ini[$s['id']]['keterangan'] ?? '';
                    ?>
                    <tr class="hover:bg-slate-50 transition group">
                        <td class="py-3 px-6 text-slate-400 font-medium text-center"><?= $index + 1 ?></td>
                        <td class="py-3 px-4">
                            <p class="font-bold text-slate-800"><?= htmlspecialchars($s['nama']) ?></p>
                            <p class="text-xs text-slate-400">NIS: <?= htmlspecialchars($s['nis'] ?? '-') ?></p>
                        </td>
                        <td class="py-3 px-4">
                            <!-- Toggle Button Status -->
                            <div class="flex items-center justify-center gap-1 bg-slate-100 p-1 rounded-lg border border-slate-200 w-max mx-auto">
                                <?php foreach ($opsi_status as $st): 
                                    $inisial = substr($st, 0, 1);
                                ?>
                                <label class="status-radio cursor-pointer">
                                    <!-- Gunakan inisial huruf pertama sebagai value database (H, S, I, A) -->
                                    <input type="radio" name="status[<?= $s['id'] ?>]" value="<?= $inisial ?>" <?= ($status_aktif === $st || $status_aktif === $inisial) ? 'checked' : '' ?> required>
                                    <div class="px-4 py-1.5 rounded-md text-xs font-bold text-slate-500 transition-colors border border-transparent">
                                        <?= strtoupper($st) ?>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td class="py-3 px-6">
                            <input type="text" name="keterangan[<?= $s['id'] ?>]" 
                                value="<?= htmlspecialchars($keterangan_aktif) ?>" 
                                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition group-hover:border-slate-300" 
                                placeholder="Alasan sakit/izin...">
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="px-6 py-5 bg-slate-50 border-t border-slate-200 flex justify-between items-center">
            <p class="text-xs text-slate-500 font-medium"><i data-lucide="info" class="w-3 h-3 inline"></i> Default status adalah Hadir jika belum pernah diisi.</p>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 px-8 rounded-xl transition shadow-lg shadow-indigo-200 flex items-center gap-2 focus:ring-4 focus:ring-indigo-100">
                <i data-lucide="save" class="w-5 h-5"></i> Simpan Data Presensi
            </button>
        </div>
    </form>
    <?php endif; ?>

</div>

<script>
    // Render icon
    lucide.createIcons();
</script>

</body>
</html>