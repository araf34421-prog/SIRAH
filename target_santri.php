<?php
session_start();
require 'config/database.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) { header("Location: login.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_santri = (int) $_POST['id_santri'];
    $target_ayat = (int) $_POST['target_ayat'];
    $target_surat = mysqli_real_escape_string($conn, $_POST['target_surat']);
    $total_ayat = (int) $_POST['total_ayat'];
    $tgl_mulai = $_POST['tanggal_mulai'];
    $ket = mysqli_real_escape_string($conn, $_POST['keterangan']);
    
    // Hitung estimasi selesai
    $hari_dibutuhkan = ceil($total_ayat / $target_ayat);
    $tgl_selesai = date('Y-m-d', strtotime($tgl_mulai . " + $hari_dibutuhkan days"));

    $cek = mysqli_query($conn, "SELECT id FROM target_hafalan WHERE id_santri = $id_santri");
    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "UPDATE target_hafalan SET target_ayat_per_hari=$target_ayat, target_surat='$target_surat', total_ayat_target=$total_ayat, tanggal_mulai='$tgl_mulai', tanggal_estimasi_selesai='$tgl_selesai', keterangan='$ket' WHERE id_santri = $id_santri");
    } else {
        mysqli_query($conn, "INSERT INTO target_hafalan (id_santri, target_ayat_per_hari, target_surat, total_ayat_target, tanggal_mulai, tanggal_estimasi_selesai, keterangan) VALUES ($id_santri, $target_ayat, '$target_surat', $total_ayat, '$tgl_mulai', '$tgl_selesai', '$ket')");
    }
    $_SESSION['success'] = "Target hafalan berhasil diperbarui!";
    header("Location: target_santri.php"); exit;
}

$santri_list = mysqli_fetch_all(mysqli_query($conn, "SELECT id, nama FROM santri ORDER BY nama ASC"), MYSQLI_ASSOC);
$target_list = mysqli_fetch_all(mysqli_query($conn, "SELECT t.*, s.nama FROM target_hafalan t JOIN santri s ON t.id_santri = s.id ORDER BY s.nama ASC"), MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Target Hafalan</title><script src="https://cdn.tailwindcss.com"></script><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"><style>body{font-family:'Inter',sans-serif;}</style></head>
<body class="bg-slate-50 text-slate-800">
<?php include 'includes/sidebar.php'; ?>
<div class="ml-64 p-8">
    <h1 class="text-2xl font-bold text-slate-900 mb-6">Perencanaan Target Hafalan Individual</h1>
    <?php if (isset($_SESSION['success'])): ?><div class="bg-green-100 text-green-700 p-3 rounded mb-4 text-sm"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div><?php endif; ?>

    <div class="bg-white border border-slate-200 rounded-xl p-6 mb-6 shadow-sm">
        <h3 class="font-semibold text-slate-900 mb-4">Tetapkan Target Baru</h3>
        <form method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Santri</label><select name="id_santri" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 outline-none"><option value="">-- Pilih --</option><?php foreach ($santri_list as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama']) ?></option><?php endforeach; ?></select></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Target Surat / Juz</label><input type="text" name="target_surat" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" placeholder="Cth: Al-Mulk"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Total Ayat Target</label><input type="number" name="total_ayat" id="total_ayat" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" placeholder="Cth: 30"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Kecepatan (Ayat/Hari)</label><input type="number" name="target_ayat" id="target_ayat" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" placeholder="Cth: 3"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Tanggal Mulai</label><input type="date" name="tanggal_mulai" id="tgl_mulai" required value="<?= date('Y-m-d') ?>" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm"></div>
            <div><label class="block text-xs font-medium text-slate-600 mb-1">Estimasi Selesai (Otomatis)</label><input type="text" id="estimasi" readonly class="w-full px-3 py-2 bg-slate-100 border border-slate-300 rounded-lg text-sm text-slate-600" placeholder="Akan dihitung otomatis"></div>
            <div class="md:col-span-3"><label class="block text-xs font-medium text-slate-600 mb-1">Catatan Program</label><input type="text" name="keterangan" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm" placeholder="Cth: Fokus tajwid"></div>
            <div class="md:col-span-3"><button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-6 rounded-lg transition text-sm">Simpan Target</button></div>
        </form>
    </div>

    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-sm">
        <h3 class="font-semibold text-slate-900 mb-4">Daftar Target Aktif Santri</h3>
        <table class="w-full text-left">
            <thead><tr class="border-b border-slate-200"><th class="py-3 px-4 text-xs font-medium text-slate-500 uppercase">Santri</th><th class="py-3 px-4 text-xs font-medium text-slate-500 uppercase">Target</th><th class="py-3 px-4 text-xs font-medium text-slate-500 uppercase">Kecepatan</th><th class="py-3 px-4 text-xs font-medium text-slate-500 uppercase">Estimasi Selesai</th><th class="py-3 px-4 text-xs font-medium text-slate-500 uppercase">Catatan</th></tr></thead>
            <tbody class="text-sm">
                <?php foreach ($target_list as $t): ?>
                <tr class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="py-3 px-4 font-medium"><?= htmlspecialchars($t['nama']) ?></td>
                    <td class="py-3 px-4"><?= htmlspecialchars($t['target_surat']) ?> <span class="text-slate-500 text-xs">(<?= $t['total_ayat_target'] ?> ayat)</span></td>
                    <td class="py-3 px-4"><span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded text-xs font-bold"><?= $t['target_ayat_per_hari'] ?> ayat/hari</span></td>
                    <td class="py-3 px-4 text-slate-600"><?= $t['tanggal_estimasi_selesai'] ? date('d M Y', strtotime($t['tanggal_estimasi_selesai'])) : '-' ?></td>
                    <td class="py-3 px-4 text-slate-500 text-xs"><?= htmlspecialchars($t['keterangan'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    // Script untuk auto-calculate estimasi selesai
    function hitungEstimasi() {
        const total = document.getElementById('total_ayat').value;
        const speed = document.getElementById('target_ayat').value;
        const tglMulai = document.getElementById('tgl_mulai').value;
        if (total && speed && tglMulai) {
            const hari = Math.ceil(total / speed);
            const tglSelesai = new Date(tglMulai);
            tglSelesai.setDate(tglSelesai.getDate() + hari);
            document.getElementById('estimasi').value = tglSelesai.toISOString().split('T')[0] + ` (${hari} hari)`;
        }
    }
    document.getElementById('total_ayat').addEventListener('input', hitungEstimasi);
    document.getElementById('target_ayat').addEventListener('input', hitungEstimasi);
    document.getElementById('tgl_mulai').addEventListener('change', hitungEstimasi);
</script>
</body>
</html>