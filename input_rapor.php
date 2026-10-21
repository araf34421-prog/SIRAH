<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

$santri_res = mysqli_query($conn, "SELECT id, nama, nis, jenis_kelamin FROM santri ORDER BY nama ASC");
$santri_list = mysqli_fetch_all($santri_res, MYSQLI_ASSOC);

$id_santri    = isset($_GET['id_santri']) ? (int) $_GET['id_santri'] : ($santri_list[0]['id'] ?? 0);
$juz          = isset($_GET['juz']) ? (int) $_GET['juz'] : 1;
$semester     = isset($_GET['semester']) ? $_GET['semester'] : '2 (Dua)';
$tahun_ajaran = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : date('Y');

$nilai_data = null;
$santri_aktif = null;

if ($id_santri > 0) {
    foreach ($santri_list as $s) {
        if ($s['id'] == $id_santri) {
            $santri_aktif = $s;
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Input Leger Rapor - Surau Tahfizh Firdaus</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include 'includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 flex items-center gap-2">
                    <i data-lucide="table-2" class="w-7 h-7 text-indigo-600"></i> Input Leger Nilai Rapor (20 Pojok)
                </h1>
                <p class="text-sm text-slate-500 mt-1">Input nilai hafalan per pojok/halaman untuk dicetak ke Rapor Santri.</p>
            </div>
            <?php if ($id_santri > 0): ?>
            <a href="rapor_santri.php?id_santri=<?= $id_santri ?>&juz=<?= $juz ?>&semester=<?= urlencode($semester) ?>&tahun_ajaran=<?= urlencode($tahun_ajaran) ?>" 
               class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded-lg transition flex items-center gap-2 text-sm">
                <i data-lucide="printer" class="w-4 h-4"></i> Lihat & Cetak Rapor
            </a>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded mb-6 text-sm flex items-center gap-2">
                <i data-lucide="check-circle" class="w-4 h-4"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <!-- Filter Pilih Santri & Juz -->
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6">
            <form method="GET" action="input_rapor.php" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Pilih Santri</label>
                    <select name="id_santri" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                        <?php foreach ($santri_list as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $s['id'] == $id_santri ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['nama']) ?> (NIS: <?= htmlspecialchars($s['nis'] ?? '-') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Juz</label>
                    <select name="juz" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                        <?php for ($j = 1; $j <= 30; $j++): ?>
                            <option value="<?= $j ?>" <?= $j == $juz ? 'selected' : '' ?>>Juz <?= $j ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Semester</label>
                    <select name="semester" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                        <option value="1 (Satu)" <?= $semester == '1 (Satu)' ? 'selected' : '' ?>>1 (Satu)</option>
                        <option value="2 (Dua)" <?= $semester == '2 (Dua)' ? 'selected' : '' ?>>2 (Dua)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Tahun Pelajaran</label>
                    <input type="text" name="tahun_ajaran" value="<?= htmlspecialchars($tahun_ajaran) ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>
            </form>
        </div>

        <?php if ($id_santri > 0): ?>
        <!-- Form Nilai Dinamis -->
        <form action="proses_input_rapor.php" method="POST" class="bg-white border border-slate-200 rounded-xl shadow-sm p-6">
            <input type="hidden" name="id_santri" value="<?= $id_santri ?>">
            <input type="hidden" name="juz" value="<?= $juz ?>">
            <input type="hidden" name="semester" value="<?= htmlspecialchars($semester) ?>">
            <input type="hidden" name="tahun_ajaran" value="<?= htmlspecialchars($tahun_ajaran) ?>">

            <?php
            // Ambil Data Header & Detail Lama jika ada
            $header_q = mysqli_query($conn, "SELECT * FROM rapor_tahfidz WHERE id_santri = $id_santri AND juz =$juz AND semester = '" . mysqli_real_escape_string($conn,$semester) . "' AND tahun_ajaran = '" . mysqli_real_escape_string($conn,$tahun_ajaran) . "'");
            $header_data = mysqli_fetch_assoc($header_q);
            $detail_data = [];
            if ($header_data) {
                $detail_q = mysqli_query($conn, "SELECT * FROM rapor_tahfidz_detail WHERE id_rapor = {$header_data['id']} ORDER BY urutan ASC");
                $detail_data = mysqli_fetch_all($detail_q, MYSQLI_ASSOC);
            }
            $default_kelas = $header_data['kelas'] ?? (($santri_aktif && $santri_aktif['jenis_kelamin'] == 'P') ? 'Khusus Perempuan' : 'Khusus Laki-Laki');
            $default_judul = $header_data['judul_materi'] ?? "Materi Tahfidz (Juz $juz)";

            if (!function_exists('renderKotakPojok')) {
                function renderKotakPojok($urutan, $materi, $nilai) {
                    return '
                    <div class="pojok-item p-3 bg-slate-50 border border-slate-200 rounded-lg relative group">
                        <button type="button" onclick="hapusPojok(this)" class="absolute top-2 right-2 text-slate-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition"><i data-lucide="x-circle" class="w-5 h-5"></i></button>
                        <label class="block text-xs font-bold text-slate-700 mb-1"><span class="nomor-pojok">'.$urutan.'</span></label>
                        <input type="text" name="materi[]" value="'.htmlspecialchars($materi).'" class="w-full px-2 py-1.5 mb-2 border border-slate-300 rounded text-xs text-slate-700 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="Nama Materi" required>
                        <input type="number" name="nilai[]" value="'.htmlspecialchars((string)$nilai).'" min="0" max="100" placeholder="Nilai" class="w-full px-3 py-1.5 border border-slate-300 rounded text-sm font-semibold text-center focus:ring-2 focus:ring-indigo-500 outline-none">
                    </div>';
                }
            }
            ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 max-w-2xl">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Nama Kelas di Rapor</label>
                    <input type="text" name="kelas" value="<?= htmlspecialchars($default_kelas) ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Judul Kolom Materi (Header Tabel)</label>
                    <input type="text" name="judul_materi" value="<?= htmlspecialchars($default_judul) ?>" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                </div>
            </div>

            <!-- Kontainer Kotak Materi -->
            <div id="pojok-container" class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 gap-4 mb-4">
                <?php 
                // Jika sudah ada data sebelumnya, tampilkan. Jika kosong, tampilkan 1 kotak kosong.
                if (count($detail_data) > 0) {
                    foreach ($detail_data as $index => $d) {
                        echo renderKotakPojok($index + 1, $d['nama_materi'], $d['nilai']);
                    }
                } else {
                    echo renderKotakPojok(1, ($juz == 1 ? 'Al-fatihah + Halaman 2' : 'Halaman 1'), '');
                }
                ?>
            </div>

            <button type="button" onclick="tambahPojok()" class="mb-6 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium py-2 px-4 rounded-lg border border-dashed border-slate-300 text-sm flex items-center gap-2 transition w-full justify-center">
                <i data-lucide="plus" class="w-4 h-4"></i> Tambah Kotak Materi Baru
            </button>

            <div class="flex items-center justify-between border-t border-slate-200 pt-4">
                <div class="text-xs text-slate-500">
                    Gunakan tombol <b class="text-slate-700">Tambah</b> untuk menambah materi, atau klik icon silang (<i data-lucide="x-circle" class="w-3 h-3 inline"></i>) saat diarahkan (hover) ke kotak untuk menghapus materi.
                </div>
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2.5 px-6 rounded-lg transition flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Simpan Nilai Rapor
                </button>
            </div>
        </form>

        <!-- SCRIPT JAVASCRIPT UNTUK DINAMIS -->
        <script>
            function hapusPojok(btn) {
                btn.closest('.pojok-item').remove();
                updateNomorPojok();
            }

            function tambahPojok() {
                const container = document.getElementById('pojok-container');
                const div = document.createElement('div');
                div.className = 'pojok-item p-3 bg-slate-50 border border-slate-200 rounded-lg relative group';
                div.innerHTML = `
                    <button type="button" onclick="hapusPojok(this)" class="absolute top-2 right-2 text-slate-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition"><i data-lucide="x-circle" class="w-5 h-5"></i></button>
                    <label class="block text-xs font-bold text-slate-700 mb-1"><span class="nomor-pojok"></span></label>
                    <input type="text" name="materi[]" class="w-full px-2 py-1.5 mb-2 border border-slate-300 rounded text-xs text-slate-700 focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="Nama Materi" required>
                    <input type="number" name="nilai[]" min="0" max="100" placeholder="Nilai" class="w-full px-3 py-1.5 border border-slate-300 rounded text-sm font-semibold text-center focus:ring-2 focus:ring-indigo-500 outline-none">
                `;
                container.appendChild(div);
                updateNomorPojok();
                lucide.createIcons();
            }

            function updateNomorPojok() {
                const items = document.querySelectorAll('.nomor-pojok');
                items.forEach((item, index) => {
                    item.innerText = index + 1;
                });
            }
        </script>
        <?php endif; ?>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>