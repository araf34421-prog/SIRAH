<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/init_rbac.php';

// Keamanan: Hanya Operator, Ustadz/ah, atau Pimpinan
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'])) { 
    header("Location: login.php"); 
    exit; 
}

$id_ustadz = (int) $_SESSION['user_id'];
$role      = $_SESSION['role'];
$is_pengelola_penuh = in_array($role, ['operator', 'pimpinan']);

// Otomatis tambahkan kolom status_ortu di tabel pr jika belum ada
$cek_kolom_pr = mysqli_query($conn, "SHOW COLUMNS FROM pr LIKE 'status_ortu'");
if ($cek_kolom_pr && mysqli_num_rows($cek_kolom_pr) === 0) {
    mysqli_query($conn, "ALTER TABLE pr ADD COLUMN status_ortu ENUM('Belum','Selesai') DEFAULT 'Belum' AFTER deskripsi");
}

// Hapus PR jika diminta
if (isset($_GET['hapus'])) {
    $id_hapus = (int) $_GET['hapus'];
    mysqli_query($conn, "DELETE FROM pr WHERE id = $id_hapus");
    $_SESSION['success'] = "Data Tugas Rumah (PR) berhasil dihapus.";
    header("Location: input_pr.php");
    exit;
}

// Filter santri khusus binaan Ustadz/Ustadzah (Operator & Pimpinan melihat semua)
$where_binaan = getFilterSantriBinaanSQL($conn, $id_ustadz, $role, 's');
$santri_query = "SELECT s.id, s.nama, s.nis, h.nama_halaqah 
                 FROM santri s 
                 LEFT JOIN halaqah h ON s.id_halaqah = h.id 
                 $where_binaan 
                 ORDER BY s.nama ASC";
$santri_list = mysqli_fetch_all(mysqli_query($conn, $santri_query), MYSQLI_ASSOC);

// Proses Simpan PR Baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['judul'])) {   
    $tanggal   = mysqli_real_escape_string($conn, $_POST['tanggal'] ?? date('Y-m-d'));
    $judul     = mysqli_real_escape_string($conn, trim($_POST['judul']));
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));
    
    if ($_POST['id_santri'] === 'semua_binaan') {
        $jml_kirim = 0;
        foreach ($santri_list as $s) {
            $id_s = (int) $s['id'];
            mysqli_query($conn, "INSERT INTO pr (id_santri, tanggal, judul, deskripsi, id_ustadz) VALUES ($id_s, '$tanggal', '$judul', '$deskripsi', $id_ustadz)");
            $jml_kirim++;
        }
        $_SESSION['success'] = "Tugas Rumah (PR) berhasil dikirimkan secara massal kepada $jml_kirim santri binaan!";
    } else {
        $id_santri = (int) $_POST['id_santri'];
        mysqli_query($conn, "INSERT INTO pr (id_santri, tanggal, judul, deskripsi, id_ustadz) VALUES ($id_santri, '$tanggal', '$judul', '$deskripsi', $id_ustadz)");
        $_SESSION['success'] = "Tugas Rumah (PR) berhasil ditambahkan untuk santri terpilih!";
    }
    
    header("Location: input_pr.php"); 
    exit;
}

// Ambil riwayat PR yang diberikan (khusus santri binaan)
$and_binaan = !empty($where_binaan) ? str_replace('WHERE', 'AND', $where_binaan) : '';
$riwayat_query = "SELECT p.*, s.nama as nama_santri, s.nama_ortu, s.no_hp_ortu, hl.nama_halaqah 
                  FROM pr p 
                  JOIN santri s ON p.id_santri = s.id 
                  LEFT JOIN halaqah hl ON s.id_halaqah = hl.id
                  WHERE 1=1 $and_binaan 
                  ORDER BY p.tanggal DESC, p.id DESC LIMIT 30";
$riwayat_list = mysqli_fetch_all(mysqli_query($conn, $riwayat_query), MYSQLI_ASSOC);

// Hitung ringkasan status PR
$total_pr_tampil = count($riwayat_list);
$pr_sudah_disimak = 0;
foreach ($riwayat_list as $r) {
    if (($r['status_ortu'] ?? 'Belum') === 'Selesai') $pr_sudah_disimak++;
}
?>
<?php
$page_title = "Berikan PR - Surau Tahfizh Firdaus";
include __DIR__ . '/../../includes/header.php';
?>
<body class="bg-slate-50 font-sans text-slate-800">

    <?php include __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="ml-64 p-8">
        
        <!-- HEADER HALAMAN -->
        <div class="flex flex-wrap justify-between items-center gap-4 mb-6">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 bg-white border border-slate-200 rounded-xl shadow-sm flex items-center justify-center text-orange-600">
                    <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Berikan Tugas Rumah (PR)</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Penugasan muraja'ah atau hafalan mandiri di rumah yang terhubung langsung ke Portal Orang Tua.</p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <button type="button" onclick="tampilkanNotifPanduan()" class="bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 px-3.5 py-2 rounded-xl shadow-sm text-xs font-semibold flex items-center gap-1.5 transition">
                    <i data-lucide="help-circle" class="w-4 h-4 text-orange-600"></i>
                    <span>Tips Penugasan</span>
                </button>
                <div class="bg-white border border-slate-200 px-4 py-2 rounded-xl shadow-sm text-xs font-semibold text-slate-700 flex items-center gap-2">
                    <i data-lucide="calendar" class="w-4 h-4 text-orange-500"></i>
                    <span><?= date('d F Y') ?></span>
                </div>
            </div>
        </div>

        <!-- NOTIFIKASI -->
        <?php if (isset($_SESSION['success'])): ?>
            <div id="alertSuccess" class="bg-orange-50 border border-orange-200 text-orange-800 px-4 py-3 rounded-xl mb-6 text-xs font-medium flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-orange-600 shrink-0"></i>
                    <span><?= $_SESSION['success']; unset($_SESSION['success']); ?></span>
                </div>
                <button type="button" onclick="document.getElementById('alertSuccess').remove()" class="text-orange-600 hover:text-orange-900"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>
        <?php endif; ?>

        <?php if (empty($santri_list)): ?>
            <div class="bg-amber-50 border border-amber-200 text-amber-800 p-4 rounded-xl mb-6 text-xs font-medium flex items-center gap-2.5">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-amber-600"></i>
                <span>Belum ada data santri di lokal binaan Anda. Silakan cek penempatan santri di menu <b>Kelas / Lokal Halaqah</b>.</span>
            </div>
        <?php else: ?>

        <!-- FORMULIR PENUGASAN PR (CLEAN FULL-WIDTH CARD) -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                    <h2 class="text-sm font-bold text-slate-800">Formulir Penugasan Rumah (PR) Baru</h2>
                </div>
                <div class="flex items-center gap-3 text-xs text-slate-500">
                    <span>Total Tugas Tercatat: <strong class="text-slate-800"><?= $total_pr_tampil ?> Tugas</strong></span>
                    <span>•</span>
                    <span>Sudah Disimak Ortu: <strong class="text-emerald-600"><?= $pr_sudah_disimak ?> Selesai</strong></span>
                </div>
            </div>

            <form method="POST" class="p-6" autocomplete="off">
                
                <!-- BARIS 1: PILIH SANTRI, TANGGAL, DAN JUDUL TUGAS -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-5 mb-5">
                    <div class="md:col-span-5">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Penerima Tugas (Santri) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i data-lucide="users" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <select name="id_santri" required class="w-full pl-10 pr-4 py-2.5 bg-slate-50 hover:bg-white focus:bg-white border border-slate-300 focus:border-orange-500 rounded-xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-orange-500/15 outline-none transition cursor-pointer">
                                <option value="" disabled selected>-- Pilih Santri Penerima --</option>
                                <option value="semua_binaan" class="font-bold text-orange-600">
                                    ★ Kirim ke Semua Santri Binaan Saya (<?= count($santri_list) ?> Santri)
                                </option>
                                <optgroup label="Pilih Santri Spesifik">
                                    <?php foreach ($santri_list as $s): ?>
                                        <option value="<?= $s['id'] ?>">
                                            <?= htmlspecialchars($s['nama']) ?> 
                                            <?= !empty($s['nis']) ? '(NIS: ' . htmlspecialchars($s['nis']) . ')' : '' ?>
                                            <?= !empty($s['nama_halaqah']) ? ' — [' . htmlspecialchars($s['nama_halaqah']) . ']' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </div>
                    </div>

                    <div class="md:col-span-4">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Judul / Materi Tugas <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <i data-lucide="bookmark" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <input type="text" name="judul" list="presetJudulPR" required placeholder="Cth: Muraja'ah QS. Al-Mulk (Juz 29)"
                                   class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-orange-500 rounded-xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-orange-500/15 outline-none transition">
                            <datalist id="presetJudulPR">
                                <option value="Muraja'ah Hafalan Juz 30">
                                <option value="Persiapan Setoran Ziyadah Besok">
                                <option value="Perbaikan Tajwid & Makharijul Huruf">
                                <option value="Persiapan Ujian Tasmi' 1 Juz">
                            </datalist>
                        </div>
                    </div>

                    <div class="md:col-span-3">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Tanggal Penugasan</label>
                        <div class="relative">
                            <i data-lucide="calendar-days" class="w-4 h-4 text-slate-400 absolute left-3.5 top-3"></i>
                            <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required
                                   class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 focus:bg-white border border-slate-300 focus:border-orange-500 rounded-xl text-sm font-medium text-slate-800 outline-none transition">
                        </div>
                    </div>
                </div>

                <!-- BARIS 2: INSTRUKSI DETAIL UNTUK ORANG TUA DI RUMAH -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Instruksi Detail untuk Disimak Orang Tua di Rumah <span class="text-rose-500">*</span></label>
                    <textarea name="deskripsi" rows="3" required placeholder="Cth: Mohon Ayah/Bunda menyimak pengulangan hafalan Ananda pada Surat An-Naba' ayat 1-20 sebanyak 3 kali sebelum tidur. Besok sore akan diujikan kembali di Surau."
                              class="w-full px-4 py-3 bg-slate-50 focus:bg-white border border-slate-300 focus:border-orange-500 rounded-xl text-xs leading-relaxed font-medium text-slate-800 focus:ring-2 focus:ring-orange-500/15 outline-none transition resize-none"></textarea>
                </div>

                <!-- TOMBOL KIRIM -->
                <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">Tugas akan langsung tampil di Portal Wali Santri dan dapat ditandai selesai oleh orang tua.</span>
                    <button type="submit" class="bg-orange-600 hover:bg-orange-700 active:scale-[0.99] text-white font-semibold py-2.5 px-7 rounded-xl transition shadow-sm flex items-center gap-2 text-xs">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Kirim Tugas Rumah (PR)</span>
                    </button>
                </div>

            </form>
        </div>
        <?php endif; ?>

        <!-- TABEL RIWAYAT PR & STATUS SIMAKAN WALI SANTRI -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4 bg-slate-50/40">
                <div class="flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-slate-500"></i>
                    <h3 class="text-sm font-bold text-slate-800">Daftar Penugasan PR & Status Simakan Orang Tua</h3>
                </div>
                <div class="relative w-64">
                    <i data-lucide="search" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-2.5"></i>
                    <input type="text" id="cariRiwayatPR" oninput="filterRiwayatPR()" placeholder="Cari nama santri atau judul PR..." 
                           class="w-full pl-8 pr-3 py-1.5 bg-white border border-slate-200 rounded-lg text-xs outline-none focus:border-orange-500">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50/70 text-slate-500 text-[11px] font-semibold uppercase tracking-wider border-b border-slate-200">
                        <tr>
                            <th class="py-3.5 px-5 w-28">Tanggal</th>
                            <th class="py-3.5 px-4">Nama Santri</th>
                            <th class="py-3.5 px-4">Judul Tugas</th>
                            <th class="py-3.5 px-4">Instruksi Tugas</th>
                            <th class="py-3.5 px-4 text-center">Status Wali Santri</th>
                            <th class="py-3.5 px-5 text-right w-36">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="text-xs text-slate-600 divide-y divide-slate-100">
                        <?php if (empty($riwayat_list)): ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-400">
                                    <i data-lucide="inbox" class="w-8 h-8 mx-auto mb-2 opacity-40"></i>
                                    <p class="font-medium text-slate-600">Belum ada tugas rumah (PR) yang diberikan</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat_list as $r): 
                                $sudah_disimak = (($r['status_ortu'] ?? 'Belum') === 'Selesai');
                            ?>
                            <tr class="baris-pr hover:bg-slate-50/80 transition"
                                data-search="<?= strtolower(htmlspecialchars($r['nama_santri'] . ' ' . $r['judul'] . ' ' . $r['deskripsi'])) ?>">
                                <td class="py-3.5 px-5 font-medium text-slate-500 whitespace-nowrap">
                                    <?= date('d M Y', strtotime($r['tanggal'])) ?>
                                </td>
                                <td class="py-3.5 px-4">
                                    <p class="font-bold text-slate-900 text-xs"><?= htmlspecialchars($r['nama_santri']) ?></p>
                                    <?php if (!empty($r['nama_halaqah'])): ?>
                                        <span class="text-[10px] text-slate-400"><?= htmlspecialchars($r['nama_halaqah']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-800">
                                    <?= htmlspecialchars($r['judul']) ?>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 leading-relaxed max-w-md">
                                    <?= nl2br(htmlspecialchars($r['deskripsi'])) ?>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <?php if ($sudah_disimak): ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <i data-lucide="check-circle-2" class="w-3.5 h-3.5"></i> Sudah Disimak
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            <i data-lucide="clock" class="w-3.5 h-3.5"></i> Belum Disimak
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3.5 px-5 text-right">
                                    <div class="inline-flex items-center justify-end gap-1">
                                        <?php 
                                        if (function_exists('buatLinkWaPR')) {
                                            $wa_pr = buatLinkWaPR($r['no_hp_ortu'] ?? '', $r['nama_santri'], $r['nama_ortu'] ?? '', $r['tanggal'], $r['judul'], $r['deskripsi'], $_SESSION['nama_lengkap'] ?? 'Ustadz/ah');
                                            if (!empty($wa_pr)): 
                                        ?>
                                            <a href="<?= $wa_pr ?>" target="_blank" title="Kirim Pengingat PR via WhatsApp Ortu"
                                               class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-600 text-emerald-700 hover:text-white border border-emerald-200 rounded-lg inline-flex items-center gap-1 text-[11px] font-bold transition">
                                                <i data-lucide="message-circle" class="w-3.5 h-3.5"></i> WA
                                            </a>
                                        <?php 
                                            endif;
                                        }
                                        ?>
                                        <a href="input_pr.php?hapus=<?= $r['id'] ?>" 
                                           onclick="return confirm('Hapus tugas rumah ini?')"
                                           title="Hapus PR"
                                           class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg inline-flex transition">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- FLOATING TOAST TIPS PENUGASAN (BISA DISILANG) -->
    <div id="floatingPanduan" class="fixed bottom-5 right-5 z-50 max-w-sm bg-slate-900 text-white rounded-2xl shadow-2xl border border-slate-700/80 p-4 transition-all duration-300 hidden">
        <div class="flex items-start justify-between gap-3 mb-2">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-lg bg-orange-500/20 text-orange-400 flex items-center justify-center shrink-0">
                    <i data-lucide="lightbulb" class="w-4 h-4"></i>
                </div>
                <h4 class="text-xs font-bold tracking-wide text-white">Tips Penugasan Rumah (PR)</h4>
            </div>
            <button type="button" onclick="tutupNotifPanduan()" title="Tutup Tips" class="text-slate-400 hover:text-white transition p-0.5">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
        <ul class="text-[11px] text-slate-300 space-y-1.5 pl-1">
            <li>• Pilih <b>Kirim ke Semua Santri Binaan</b> jika tugas berlaku serentak untuk satu lokal.</li>
            <li>• Sebutkan surat, juz/pojok, dan rentang ayat secara spesifik agar orang tua mudah menyimak di rumah.</li>
        </ul>
    </div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>