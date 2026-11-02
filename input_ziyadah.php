<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

// Pastikan kolom juz & pojok ada agar query tabel riwayat selalu aman
$cek_juz = mysqli_query($conn, "SHOW COLUMNS FROM hafalan LIKE 'juz'");
if ($cek_juz && mysqli_num_rows($cek_juz) === 0) {
    mysqli_query($conn, "ALTER TABLE hafalan ADD COLUMN `juz` INT(11) DEFAULT NULL AFTER `jenis`");
    mysqli_query($conn, "ALTER TABLE hafalan ADD COLUMN `pojok` VARCHAR(30) DEFAULT NULL AFTER `juz`");
}

// Hapus riwayat setoran jika diminta
if (isset($_GET['hapus'])) {
    $id_hapus = (int) $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM hafalan WHERE id = $id_hapus AND jenis = 'ziyadah'");
    $_SESSION['success'] = "Data setoran ziyadah berhasil dihapus.";
    header("Location: input_ziyadah.php");
    exit;
}

// Ambil daftar santri beserta nama lokalnya
$santri_query = "SELECT s.id, s.nama, s.nis, h.nama_halaqah 
                 FROM santri s 
                 LEFT JOIN halaqah h ON s.id_halaqah = h.id 
                 ORDER BY s.nama ASC";
$santri_result = mysqli_query($conn, $santri_query);
$santri_list = mysqli_fetch_all($santri_result, MYSQLI_ASSOC);

// Ambil riwayat ziyadah hari ini
$jenis_halaman = 'ziyadah'; 
$riwayat_query = "SELECT h.*, s.nama as nama_santri, hl.nama_halaqah 
                  FROM hafalan h 
                  JOIN santri s ON h.id_santri = s.id 
                  LEFT JOIN halaqah hl ON s.id_halaqah = hl.id
                  WHERE h.tanggal = CURDATE() AND h.jenis = '$jenis_halaman' 
                  ORDER BY h.id DESC";
$riwayat_result = mysqli_query($conn, $riwayat_query);
$riwayat_list = mysqli_fetch_all($riwayat_result, MYSQLI_ASSOC);

// Hitung ringkasan hari ini
$total_setoran_hari_ini = count($riwayat_list);
$total_ayat_hari_ini = 0;
foreach ($riwayat_list as $r) {
    $total_ayat_hari_ini += (int)($r['jumlah_ayat'] ?? 0);
}

// Daftar 114 Surat untuk Autocomplete
$daftar_surat = ["Al-Fatihah","Al-Baqarah","Ali 'Imran","An-Nisa'","Al-Ma'idah","Al-An'am","Al-A'raf","Al-Anfal","At-Taubah","Yunus","Hud","Yusuf","Ar-Ra'd","Ibrahim","Al-Hijr","An-Nahl","Al-Isra'","Al-Kahf","Maryam","Taha","Al-Anbiya'","Al-Hajj","Al-Mu'minun","An-Nur","Al-Furqan","Asy-Syu'ara'","An-Naml","Al-Qasas","Al-'Ankabut","Ar-Rum","Luqman","As-Sajdah","Al-Ahzab","Saba'","Fatir","Yasin","As-Saffat","Sad","Az-Zumar","Gafir","Fussilat","Asy-Syura","Az-Zukhruf","Ad-Dukhan","Al-Jasiyah","Al-Ahqaf","Muhammad","Al-Fath","Al-Hujurat","Qaf","Az-Zariyat","At-Tur","An-Najm","Al-Qamar","Ar-Rahman","Al-Waqi'ah","Al-Hadid","Al-Mujadilah","Al-Hasyr","Al-Mumtahanah","As-Saff","Al-Jumu'ah","Al-Munafiqun","At-Tagabun","At-Talaq","At-Tahrim","Al-Mulk","Al-Qalam","Al-Haqqah","Al-Ma'arij","Nuh","Al-Jinn","Al-Muzzammil","Al-Muddassir","Al-Qiyamah","Al-Insan","Al-Mursalat","An-Naba'","An-Nazi'at","'Abasa","At-Takwir","Al-Infitar","Al-Mutaffifin","Al-Insyiqaq","Al-Buruj","At-Tariq","Al-A'la","Al-Gasyiyah","Al-Fajr","Al-Balad","Asy-Syams","Al-Lail","Ad-Duha","Asy-Syarh","At-Tin","Al-'Alaq","Al-Qadr","Al-Bayyinah","Az-Zalzalah","Al-'Adiyat","Al-Qari'ah","At-Takasur","Al-'Asr","Al-Humazah","Al-Fil","Quraisy","Al-Ma'un","Al-Kausar","Al-Kafirun","An-Nasr","Al-Lahab","Al-Ikhlas","Al-Falaq","An-Nas"];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Ziyadah - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .predikat-radio input[type="radio"] { display: none; }
        .predikat-radio input[value="A"]:checked + div {
            border-color: #10b981; background-color: #ecfdf5; color: #047857;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.15);
        }
        .predikat-radio input[value="B"]:checked + div {
            border-color: #f59e0b; background-color: #fffbeb; color: #b45309;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }
        .predikat-radio input[value="C"]:checked + div {
            border-color: #f43f5e; background-color: #fff1f2; color: #be123c;
            box-shadow: 0 0 0 3px rgba(244, 63, 94, 0.15);
        }
        .predikat-radio input[type="radio"]:checked + div .check-badge {
            opacity: 1; transform: scale(1);
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- HEADER HALAMAN -->
        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 bg-white border border-slate-200 rounded-xl shadow-sm flex items-center justify-center text-emerald-600">
                    <i data-lucide="book-plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Input Setoran Ziyadah</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Pencatatan tambahan hafalan Al-Qur'an baru santri per Juz, Pojok, dan Surat.</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <!-- Tombol untuk memunculkan kembali Notif Panduan jika sudah disilang -->
                <button type="button" onclick="tampilkanNotifPanduan()" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 px-3.5 py-2 rounded-xl shadow-sm text-xs font-semibold flex items-center gap-1.5 transition">
                    <i data-lucide="help-circle" class="w-4 h-4 text-emerald-600"></i>
                    <span>Panduan Nilai</span>
                </button>

                <div class="bg-white border border-slate-200 px-4 py-2 rounded-xl shadow-sm text-xs font-semibold text-slate-700 flex items-center gap-2">
                    <i data-lucide="calendar" class="w-4 h-4 text-emerald-600"></i>
                    <span><?= date('d F Y') ?></span>
                </div>
            </div>
        </div>

        <!-- ALERT NOTIFIKASI SUKSES / ERROR -->
        <?php if (isset($_SESSION['success'])): ?>
            <div id="alertSuccess" class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl mb-6 text-xs font-medium flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600 shrink-0"></i>
                    <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
                <button type="button" onclick="document.getElementById('alertSuccess').remove()" class="text-emerald-600 hover:text-emerald-900">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div id="alertError" class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl mb-6 text-xs font-medium flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-rose-600 shrink-0"></i>
                    <span><?= $_SESSION['error']; unset($_SESSION['error']); ?></span>
                </div>
                <button type="button" onclick="document.getElementById('alertError').remove()" class="text-rose-600 hover:text-rose-900">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if (empty($santri_list)): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-xl mb-6 text-xs font-medium flex items-center gap-2.5">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                <span>Belum ada data santri. Silakan tambahkan santri di menu <b>Kelola Santri</b> terlebih dahulu.</span>
            </div>
        <?php else: ?>

        <!-- FORMULIR SETORAN ZIYADAH (CLEAN FULL-WIDTH CARD) -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <h2 class="text-sm font-bold text-slate-800">Formulir Setoran Ziyadah Baru</h2>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-500">
                    <span>Total Hari Ini: <strong class="text-slate-800"><?= $total_setoran_hari_ini ?> Setoran</strong></span>
                    <span>•</span>
                    <span>Capaian: <strong class="text-emerald-600"><?= $total_ayat_hari_ini ?> Ayat</strong></span>
                </div>
            </div>

            <form action="proses_input_hafalan.php" method="POST" class="p-6" autocomplete="off">
                <input type="hidden" name="jenis" value="ziyadah">

                <!-- BARIS 1: PILIH SANTRI & TANGGAL -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 mb-5">
                    <div class="md:col-span-8">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Santri <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <select name="id_santri" required class="w-full pl-10 pr-4 py-2.5 bg-slate-50 hover:bg-white focus:bg-white border border-slate-300 focus:border-emerald-500 rounded-xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-emerald-500/15 outline-none transition cursor-pointer">
                                <option value="" disabled selected>-- Pilih Nama Santri --</option>
                                <?php foreach ($santri_list as $s): ?>
                                    <option value="<?= $s['id'] ?>">
                                        <?= htmlspecialchars($s['nama']) ?> 
                                        <?= !empty($s['nis']) ? '(NIS: ' . htmlspecialchars($s['nis']) . ')' : '' ?>
                                        <?= !empty($s['nama_halaqah']) ? ' — [' . htmlspecialchars($s['nama_halaqah']) . ']' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Setoran</label>
                        <div class="relative">
                            <i data-lucide="calendar-days" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required
                                   class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-emerald-500 rounded-xl text-sm font-medium text-slate-800 outline-none transition">
                        </div>
                    </div>
                </div>

                <!-- BARIS 2: JUZ, POJOK, SURAT BARU, DAN RENTANG AYAT -->
                <div class="p-4 bg-slate-50/70 border border-slate-200/80 rounded-xl mb-5">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5">
                            <i data-lucide="book-open" class="w-3.5 h-3.5 text-emerald-600"></i> Detail Materi Hafalan (Juz, Pojok, Surat & Ayat)
                        </span>
                        <span id="badgeJumlahAyat" class="text-[11px] font-bold px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 hidden">
                            0 Ayat
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-4">
                        
                        <!-- Pilihan Juz (1 - 30) -->
                        <div class="lg:col-span-2">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Juz <span class="text-rose-500">*</span></label>
                            <select name="juz" id="inputJuz" onchange="updateInfoHalamanMushaf()" required class="w-full px-3 py-2.5 bg-white border border-slate-300 focus:border-emerald-500 rounded-xl text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-emerald-500/15 outline-none cursor-pointer">
                                <option value="">-- Juz --</option>
                                <?php for ($j = 1; $j <= 30; $j++): ?>
                                    <option value="<?= $j ?>" <?= $j === 30 ? 'selected' : '' ?>>Juz <?= $j ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <!-- Pilihan Pojok (1 - 20) -->
                        <div class="lg:col-span-3">
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-semibold text-slate-600">Pojok / Halaman</label>
                                <span id="infoHalamanMushaf" class="text-[10px] font-medium text-slate-400"></span>
                            </div>
                            <input type="text" name="pojok" id="inputPojok" list="listPojok" oninput="updateInfoHalamanMushaf()" placeholder="Pilih / Ketik Pojok..."
                                   class="w-full px-3 py-2.5 bg-white border border-slate-300 focus:border-emerald-500 rounded-xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-emerald-500/15 outline-none">
                            <datalist id="listPojok">
                                <?php for ($p = 1; $p <= 20; $p++): ?>
                                    <option value="Pojok <?= $p ?>">
                                <?php endfor; ?>
                            </datalist>
                        </div>

                        <!-- Nama Surat -->
                        <div class="lg:col-span-4">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Nama Surat <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <i data-lucide="Scroll" class="w-4 h-4 text-slate-400 absolute left-3 top-3"></i>
                                <input type="text" name="surat" list="daftarSurat" required placeholder="Ketik / pilih nama surat..."
                                       class="w-full pl-9 pr-3 py-2.5 bg-white border border-slate-300 focus:border-emerald-500 rounded-xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-emerald-500/15 outline-none">
                                <datalist id="daftarSurat">
                                    <?php foreach ($daftar_surat as $surah): ?>
                                        <option value="<?= $surah ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                        </div>

                        <!-- Rentang Ayat -->
                        <div class="lg:col-span-3">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Rentang Ayat <span class="text-rose-500">*</span></label>
                            <div class="flex items-center gap-1.5">
                                <input type="number" name="ayat_awal" id="ayatAwal" oninput="hitungRentangAyat()" min="1" required placeholder="Awal"
                                       class="w-full py-2.5 px-2 text-center bg-white border border-slate-300 focus:border-emerald-500 rounded-xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500/15 outline-none">
                                <span class="text-xs font-bold text-slate-400">s/d</span>
                                <input type="number" name="ayat_akhir" id="ayatAkhir" oninput="hitungRentangAyat()" min="1" required placeholder="Akhir"
                                       class="w-full py-2.5 px-2 text-center bg-white border border-slate-300 focus:border-emerald-500 rounded-xl text-sm font-bold text-slate-800 focus:ring-2 focus:ring-emerald-500/15 outline-none">
                            </div>
                        </div>

                    </div>
                </div>

                <!-- BARIS 3: KUALITAS BACAAN (PREDIKAT) & CATATAN PENGUJI -->
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                    
                    <!-- Pilihan Predikat -->
                    <div class="lg:col-span-7">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Predikat Kualitas Bacaan <span class="text-rose-500">*</span></label>
                        <div class="grid grid-cols-3 gap-3">
                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="A" checked required>
                                <div class="border border-slate-200 rounded-xl p-3 bg-white hover:bg-slate-50 transition relative">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-sm font-extrabold">Predikat A</span>
                                        <i data-lucide="check-circle-2" class="check-badge w-4 h-4 opacity-0 scale-75 transition"></i>
                                    </div>
                                    <p class="text-[11px] text-slate-500">Lancar & Tajwid Baik</p>
                                </div>
                            </label>

                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="B">
                                <div class="border border-slate-200 rounded-xl p-3 bg-white hover:bg-slate-50 transition relative">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-sm font-extrabold">Predikat B</span>
                                        <i data-lucide="check-circle-2" class="check-badge w-4 h-4 opacity-0 scale-75 transition"></i>
                                    </div>
                                    <p class="text-[11px] text-slate-500">Lancar, Ada Catatan</p>
                                </div>
                            </label>

                            <label class="predikat-radio cursor-pointer">
                                <input type="radio" name="predikat" value="C">
                                <div class="border border-slate-200 rounded-xl p-3 bg-white hover:bg-slate-50 transition relative">
                                    <div class="flex items-center justify-between mb-0.5">
                                        <span class="text-sm font-extrabold">Predikat C</span>
                                        <i data-lucide="check-circle-2" class="check-badge w-4 h-4 opacity-0 scale-75 transition"></i>
                                    </div>
                                    <p class="text-[11px] text-slate-500">Perlu Mengulang</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Catatan Penguji -->
                    <div class="lg:col-span-5">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Catatan Evaluasi Ustadz/ah (Opsional)</label>
                        <input type="text" name="catatan" placeholder="Cth: Perhatikan dengung & panjang pendek mad..."
                               class="w-full px-3.5 py-4 bg-slate-50 focus:bg-white border border-slate-300 focus:border-emerald-500 rounded-xl text-xs font-medium text-slate-800 focus:ring-2 focus:ring-emerald-500/15 outline-none transition">
                    </div>
                </div>

                <!-- TOMBOL SIMPAN -->
                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">Pastikan Juz, Pojok, Surat, dan Ayat telah sesuai sebelum menyimpan.</span>
                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-semibold py-2.5 px-7 rounded-xl transition shadow-sm flex items-center gap-2 text-xs">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Simpan Setoran Ziyadah</span>
                    </button>
                </div>

            </form>
        </div>
        <?php endif; ?>

        <!-- TABEL RIWAYAT SETORAN HARI INI -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4 bg-slate-50/40">
                <div class="flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-slate-500"></i>
                    <h3 class="text-sm font-bold text-slate-800">Riwayat Setoran Ziyadah Hari Ini</h3>
                </div>
                <div class="relative w-60">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5"></i>
                    <input type="text" id="cariRiwayat" oninput="filterRiwayatHariIni()" placeholder="Cari santri atau surat..." 
                           class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none focus:border-emerald-500">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/70 text-slate-500 text-[11px] font-semibold uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-5 w-24">Waktu</th>
                            <th class="py-3 px-4">Nama Santri</th>
                            <th class="py-3 px-4">Juz & Pojok</th>
                            <th class="py-3 px-4">Surat & Ayat</th>
                            <th class="py-3 px-4 text-center">Jml Ayat</th>
                            <th class="py-3 px-4 text-center">Predikat</th>
                            <th class="py-3 px-4">Catatan</th>
                            <th class="py-3 px-5 text-right w-16">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs text-slate-600 divide-y divide-slate-100">
                        <?php if (empty($riwayat_list)): ?>
                            <tr>
                                <td colspan="8" class="py-12 text-center text-slate-400">
                                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                    <p class="font-medium text-slate-600">Belum ada setoran ziyadah yang tercatat hari ini</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $r): 
                                if ($r['predikat'] === 'A') {
                                    $badge = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                } elseif ($r['predikat'] === 'B') {
                                    $badge = 'bg-amber-50 text-amber-700 border-amber-200';
                                } else {
                                    $badge = 'bg-rose-50 text-rose-700 border-rose-200';
                                }
                            ?>
                            <tr class="baris-riwayat hover:bg-slate-50/80 transition"
                                data-search="<?= strtolower(htmlspecialchars($r['nama_santri'] . ' ' . $r['surat'] . ' ' . ($r['pojok'] ?? ''))) ?>">
                                <td class="py-3.5 px-5 font-mono text-slate-400">
                                    <?= date('H:i', strtotime($r['created_at'])) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($r['nama_santri']) ?></p>
                                    <?php if (!empty($r['nama_halaqah'])): ?>
                                        <span class="text-[10px] text-slate-400"><?= htmlspecialchars($r['nama_halaqah']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="inline-flex items-center gap-1.5">
                                        <?php if (!empty($r['juz'])): ?>
                                            <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 font-bold text-[11px]">Juz <?= (int)$r['juz'] ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($r['pojok'])): ?>
                                            <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-semibold text-[11px]"><?= htmlspecialchars($r['pojok']) ?></span>
                                        <?php endif; ?>
                                        <?php if (empty($r['juz']) && empty($r['pojok'])): ?>
                                            <span class="text-slate-400">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-slate-800">QS. <?= htmlspecialchars($r['surat']) ?></span>
                                    <span class="text-slate-500 ml-1">: <?= $r['ayat_awal'] ?>–<?= $r['ayat_akhir'] ?></span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded"><?= (int)$r['jumlah_ayat'] ?> Ayat</span>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg font-bold border <?= $badge ?>">
                                        <?= htmlspecialchars($r['predikat']) ?>
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-500 italic max-w-xs truncate" title="<?= htmlspecialchars($r['catatan'] ?? '') ?>">
                                    <?= htmlspecialchars($r['catatan'] ?: '-') ?>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <a href="input_ziyadah.php?hapus=<?= $r['id'] ?>" 
                                       onclick="return confirm('Hapus catatan setoran ini?')"
                                       title="Hapus Setoran"
                                       class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg inline-flex transition">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- NOTIFIKASI PANDUAN MENGAMBANG (FLOATING DISMISSIBLE TOAST) -->
    <div id="floatingPanduan" class="fixed bottom-5 right-5 z-50 max-w-sm bg-slate-900 text-white rounded-2xl shadow-2xl border border-slate-700/80 p-4 transition-all duration-300 hidden">
        <div class="flex items-start justify-between gap-3 mb-2">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                </div>
                <h4 class="text-xs font-bold tracking-wide text-white">Panduan Singkat Penilaian Ziyadah</h4>
            </div>
            <button type="button" onclick="tutupNotifPanduan()" title="Tutup Panduan" class="text-slate-400 hover:text-white transition p-0.5">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <ul class="text-[11px] text-slate-300 space-y-1.5 pl-1">
            <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-emerald-400"></span> <b>Predikat A:</b> Lancar, tajwid tepat, lanjut materi.</li>
            <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-amber-400"></span> <b>Predikat B:</b> Lulus dengan catatan perbaikan.</li>
            <li class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-rose-400"></span> <b>Predikat C:</b> Belum lancar, wajib diulang besok.</li>
        </ul>
    </div>

    <script>
        lucide.createIcons();

        // Hitung otomatis rentang jumlah ayat
        function hitungRentangAyat() {
            const awal = parseInt(document.getElementById('ayatAwal').value) || 0;
            const akhir = parseInt(document.getElementById('ayatAkhir').value) || 0;
            const badge = document.getElementById('badgeJumlahAyat');

            if (awal > 0 && akhir >= awal) {
                const total = akhir - awal + 1;
                badge.innerText = total + ' Ayat Dihafal';
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        // Info estimasi halaman mushaf ketika Juz & Pojok dipilih
        function updateInfoHalamanMushaf() {
            const juz = parseInt(document.getElementById('inputJuz').value) || 0;
            const pojokStr = document.getElementById('inputPojok').value || '';
            const match = pojokStr.match(/\d+/);
            const infoEl = document.getElementById('infoHalamanMushaf');

            if (juz > 0 && match) {
                const p = parseInt(match[0]);
                if (p >= 1 && p <= 20) {
                    const halMushaf = (juz - 1) * 20 + p + 1;
                    infoEl.innerText = (juz === 1 && p === 1) ? '(Al-Fatihah + Hal 2)' : '(Hal. ' + halMushaf + ' Mushaf)';
                    return;
                }
            }
            infoEl.innerText = '';
        }

        // Filter pencarian tabel riwayat hari ini
        function filterRiwayatHariIni() {
            const q = document.getElementById('cariRiwayat').value.toLowerCase();
            document.querySelectorAll('.baris-riwayat').forEach(tr => {
                tr.style.display = tr.dataset.search.includes(q) ? '' : 'none';
            });
        }

        // Kontrol Floating Toast Panduan (Bisa Disilang)
        const toastEl = document.getElementById('floatingPanduan');
        if (localStorage.getItem('hide_panduan_ziyadah') !== '1') {
            toastEl.classList.remove('hidden');
        }

        function tutupNotifPanduan() {
            toastEl.classList.add('hidden');
            localStorage.setItem('hide_panduan_ziyadah', '1');
        }

        function tampilkanNotifPanduan() {
            toastEl.classList.remove('hidden');
            localStorage.removeItem('hide_panduan_ziyadah');
        }
    </script>
</body>
</html>