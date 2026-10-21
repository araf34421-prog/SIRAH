<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

$santri_query = "SELECT id, nama, nis FROM santri ORDER BY nama ASC";
$santri_result = mysqli_query($conn, $santri_query);
$santri_list = mysqli_fetch_all($santri_result, MYSQLI_ASSOC);

$jenis_halaman = 'murajaah'; 
$riwayat_query = "SELECT h.*, s.nama as nama_santri FROM hafalan h JOIN santri s ON h.id_santri = s.id WHERE h.tanggal = CURDATE() AND h.jenis = '$jenis_halaman' ORDER BY h.id DESC";
$riwayat_result = mysqli_query($conn, $riwayat_query);
$riwayat_list = mysqli_fetch_all($riwayat_result, MYSQLI_ASSOC);

// Daftar 114 Surat untuk Autocomplete
$daftar_surat = ["Al-Fatihah","Al-Baqarah","Ali 'Imran","An-Nisa'","Al-Ma'idah","Al-An'am","Al-A'raf","Al-Anfal","At-Taubah","Yunus","Hud","Yusuf","Ar-Ra'd","Ibrahim","Al-Hijr","An-Nahl","Al-Isra'","Al-Kahf","Maryam","Taha","Al-Anbiya'","Al-Hajj","Al-Mu'minun","An-Nur","Al-Furqan","Asy-Syu'ara'","An-Naml","Al-Qasas","Al-'Ankabut","Ar-Rum","Luqman","As-Sajdah","Al-Ahzab","Saba'","Fatir","Yasin","As-Saffat","Sad","Az-Zumar","Gafir","Fussilat","Asy-Syura","Az-Zukhruf","Ad-Dukhan","Al-Jasiyah","Al-Ahqaf","Muhammad","Al-Fath","Al-Hujurat","Qaf","Az-Zariyat","At-Tur","An-Najm","Al-Qamar","Ar-Rahman","Al-Waqi'ah","Al-Hadid","Al-Mujadilah","Al-Hasyr","Al-Mumtahanah","As-Saff","Al-Jumu'ah","Al-Munafiqun","At-Tagabun","At-Talaq","At-Tahrim","Al-Mulk","Al-Qalam","Al-Haqqah","Al-Ma'arij","Nuh","Al-Jinn","Al-Muzzammil","Al-Muddassir","Al-Qiyamah","Al-Insan","Al-Mursalat","An-Naba'","An-Nazi'at","'Abasa","At-Takwir","Al-Infitar","Al-Mutaffifin","Al-Insyiqaq","Al-Buruj","At-Tariq","Al-A'la","Al-Gasyiyah","Al-Fajr","Al-Balad","Asy-Syams","Al-Lail","Ad-Duha","Asy-Syarh","At-Tin","Al-'Alaq","Al-Qadr","Al-Bayyinah","Az-Zalzalah","Al-'Adiyat","Al-Qari'ah","At-Takasur","Al-'Asr","Al-Humazah","Al-Fil","Quraisy","Al-Ma'un","Al-Kausar","Al-Kafirun","An-Nasr","Al-Lahab","Al-Ikhlas","Al-Falaq","An-Nas"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Muraja'ah - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Sembunyikan radio circle bawaan */
        .radio-card input[type="radio"] { display: none; }
        /* Style saat radio card dipilih */
        .radio-card input[type="radio"]:checked + div {
            border-color: currentColor;
            background-color: currentColor;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .radio-card input[type="radio"]:checked + div .text-subtitle { color: rgba(255,255,255,0.8); }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        <!-- Header Halaman -->
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 flex items-center gap-3">
                    <div class="p-2 bg-blue-100 rounded-lg"><i data-lucide="refresh-cw" class="w-6 h-6 text-blue-600"></i></div>
                    Input Muraja'ah
                </h1>
                <p class="text-sm text-slate-500 mt-2">Catat pengulangan hafalan lama (muraja'ah) santri hari ini.</p>
            </div>
            <div class="bg-white border border-slate-200 px-5 py-2.5 rounded-xl shadow-sm text-sm font-medium text-slate-700 flex items-center gap-2">
                <i data-lucide="calendar" class="w-4 h-4 text-blue-500"></i> <?= date('d F Y') ?>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-xl mb-6 text-sm flex items-center gap-3 shadow-sm animate-fade-in">
                <i data-lucide="check-circle" class="w-5 h-5"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($santri_list)): ?>
            <div class="bg-amber-50 border-l-4 border-amber-500 text-amber-800 p-4 rounded-xl mb-6 text-sm flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-5 h-5"></i> Belum ada data santri. Silakan tambah di menu Kelola Santri terlebih dahulu.
            </div>
        <?php else: ?>

        <!-- GRID 2 KOLOM (FORM & INFORMASI) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            
            <!-- FORM INPUT -->
            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                        <i data-lucide="edit-3" class="w-4 h-4 text-blue-600"></i> Formulir Setoran
                    </h3>
                </div>
                
                <form action="proses_input_hafalan.php" method="POST" class="p-6" autocomplete="off">
                    <input type="hidden" name="jenis" value="murajaah">
                    
                    <div class="space-y-6">
                        <!-- Pilihan Santri -->
                        <div>
                            <label class="block text-xs font-bold tracking-wide text-slate-500 uppercase mb-2">Nama Santri</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i data-lucide="user" class="h-4 w-4 text-slate-400"></i>
                                </div>
                                <select name="id_santri" required class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-slate-50 hover:bg-white transition outline-none appearance-none cursor-pointer">
                                    <option value="" disabled selected>-- Ketik / Pilih Santri --</option>
                                    <?php foreach ($santri_list as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?> <?= $s['nis'] ? '('.$s['nis'].')' : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Surat & Ayat -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold tracking-wide text-slate-500 uppercase mb-2">Materi Muraja'ah</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i data-lucide="book-open" class="h-4 w-4 text-slate-400"></i>
                                    </div>
                                    <input type="text" name="surat" list="daftarSurat" required class="block w-full pl-10 pr-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none" placeholder="Cth: Al-Mulk / Juz 30">
                                    <datalist id="daftarSurat">
                                        <?php foreach($daftar_surat as $surah): ?>
                                            <option value="<?= $surah ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold tracking-wide text-slate-500 uppercase mb-2">Rentang Ayat / Halaman</label>
                                <div class="flex items-center gap-2">
                                    <input type="number" name="ayat_awal" min="1" required class="w-full text-center py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Awal">
                                    <span class="text-slate-400 text-sm font-medium">s.d</span>
                                    <input type="number" name="ayat_akhir" min="1" required class="w-full text-center py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Akhir">
                                </div>
                            </div>
                        </div>

                        <!-- Predikat UI Baru (Radio Cards) -->
                        <div>
                            <label class="block text-xs font-bold tracking-wide text-slate-500 uppercase mb-2">Kualitas Kelancaran (Predikat)</label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <label class="radio-card cursor-pointer">
                                    <input type="radio" name="predikat" value="A" required>
                                    <div class="border-2 border-slate-200 text-blue-600 rounded-xl p-4 transition-all duration-200 hover:border-blue-200 hover:bg-blue-50">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="font-bold text-lg">A</span>
                                            <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                                        </div>
                                        <p class="text-[10px] uppercase tracking-wider font-semibold text-subtitle text-slate-500">Lancar Tanpa Bantuan</p>
                                    </div>
                                </label>
                                <label class="radio-card cursor-pointer">
                                    <input type="radio" name="predikat" value="B">
                                    <div class="border-2 border-slate-200 text-amber-500 rounded-xl p-4 transition-all duration-200 hover:border-amber-200 hover:bg-amber-50">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="font-bold text-lg">B</span>
                                            <i data-lucide="help-circle" class="w-5 h-5"></i>
                                        </div>
                                        <p class="text-[10px] uppercase tracking-wider font-semibold text-subtitle text-slate-500">Lancar, Ada Sedikit Lupa</p>
                                    </div>
                                </label>
                                <label class="radio-card cursor-pointer">
                                    <input type="radio" name="predikat" value="C">
                                    <div class="border-2 border-slate-200 text-rose-500 rounded-xl p-4 transition-all duration-200 hover:border-rose-200 hover:bg-rose-50">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="font-bold text-lg">C</span>
                                            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                                        </div>
                                        <p class="text-[10px] uppercase tracking-wider font-semibold text-subtitle text-slate-500">Terbata / Banyak Lupa</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- Catatan -->
                        <div>
                            <label class="block text-xs font-bold tracking-wide text-slate-500 uppercase mb-2">Catatan Penguji (Opsional)</label>
                            <textarea name="catatan" rows="2" class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none resize-none" placeholder="Cth: Perbanyak murojaah di rumah terutama ayat 15-20..."></textarea>
                        </div>
                    </div>

                    <div class="mt-8 flex justify-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-xl transition shadow-lg shadow-blue-200 flex items-center gap-2 focus:ring-4 focus:ring-blue-100">
                            <i data-lucide="save" class="w-5 h-5"></i> Simpan Muraja'ah
                        </button>
                    </div>
                </form>
            </div>

            <!-- SIDE INFO / TIPS -->
            <div class="space-y-6">
                <div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl p-6 text-white shadow-sm">
                    <h4 class="font-semibold mb-2 flex items-center gap-2"><i data-lucide="info" class="w-5 h-5"></i> Pentingnya Muraja'ah</h4>
                    <p class="text-sm text-blue-50 leading-relaxed mb-4">"Menghafal itu mudah, yang sulit adalah menjaganya." Pastikan santri memiliki porsi muraja'ah yang konsisten setiap harinya.</p>
                    <ul class="text-xs text-blue-100 space-y-2">
                        <li class="flex items-start gap-2"><i data-lucide="check" class="w-4 h-4 shrink-0"></i> Nilai A: Hafalan mutqin (kuat), siap melangkah jauh.</li>
                        <li class="flex items-start gap-2"><i data-lucide="check" class="w-4 h-4 shrink-0"></i> Nilai B: Perlu pengulangan mandiri di rumah.</li>
                        <li class="flex items-start gap-2"><i data-lucide="check" class="w-4 h-4 shrink-0"></i> Nilai C: Santri belum boleh menambah ziyadah baru sebelum muraja'ah ini lancar.</li>
                    </ul>
                </div>
            </div>

        </div>
        <?php endif; ?>

        <!-- TABEL RIWAYAT HARI INI -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                    <i data-lucide="clock" class="w-5 h-5 text-slate-400"></i> Riwayat Muraja'ah Hari Ini
                </h3>
                <span class="bg-slate-200 text-slate-600 text-xs font-bold px-3 py-1 rounded-full"><?= count($riwayat_list) ?> Setoran</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-white text-slate-400 text-xs uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-4 px-6 font-semibold">Waktu</th>
                            <th class="py-4 px-6 font-semibold">Nama Santri</th>
                            <th class="py-4 px-6 font-semibold">Materi Muraja'ah</th>
                            <th class="py-4 px-6 font-semibold text-center">Predikat</th>
                            <th class="py-4 px-6 font-semibold">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm text-slate-600 divide-y divide-slate-100">
                        <?php if (empty($riwayat_list)): ?>
                            <tr>
                                <td colspan="5" class="py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <i data-lucide="inbox" class="w-10 h-10 mb-3 opacity-50"></i>
                                        <p>Belum ada santri yang menyetorkan muraja'ah hari ini.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $r): 
                                // Setup Badge Warna
                                if ($r['predikat'] == 'A') {
                                    $badge = 'bg-blue-100 text-blue-700 border-blue-200';
                                } elseif ($r['predikat'] == 'B') {
                                    $badge = 'bg-amber-100 text-amber-700 border-amber-200';
                                } else {
                                    $badge = 'bg-rose-100 text-rose-700 border-rose-200';
                                }
                            ?>
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="py-4 px-6 text-xs text-slate-400 font-medium whitespace-nowrap">
                                        <i data-lucide="clock-3" class="w-3 h-3 inline mr-1"></i> <?= date('H:i', strtotime($r['created_at'])) ?>
                                    </td>
                                    <td class="py-4 px-6 font-bold text-slate-800"><?= htmlspecialchars($r['nama_santri']) ?></td>
                                    <td class="py-4 px-6">
                                        <span class="font-medium text-slate-700"><?= htmlspecialchars($r['surat']) ?></span> 
                                        <span class="text-slate-400 text-xs ml-1">Ayat <?= $r['ayat_awal'] ?>-<?= $r['ayat_akhir'] ?></span>
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg font-bold border <?= $badge ?>">
                                            <?= $r['predikat'] ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-xs text-slate-500 italic max-w-xs truncate" title="<?= htmlspecialchars($r['catatan'] ?? '') ?>">
                                        <?= htmlspecialchars($r['catatan'] ?? '-') ?>
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
        // Render semua icon lucide
        lucide.createIcons();
    </script>
</body>
</html>