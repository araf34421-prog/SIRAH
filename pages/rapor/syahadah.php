<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/init_rbac.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'], true)) {
    header('Location: login.php');
    exit;
}

$id_santri = filter_input(INPUT_GET, 'id_santri', FILTER_VALIDATE_INT) ?: 0;
$id_tasmi = filter_input(INPUT_GET, 'id_tasmi', FILTER_VALIDATE_INT) ?: 0;
$role = $_SESSION['role'];
$id_user = (int) $_SESSION['user_id'];
$where_binaan = getFilterSantriBinaanSQL($conn, $id_user, $role, 's');
$and_binaan = $where_binaan !== '' ? ' AND ' . preg_replace('/^\s*WHERE\s+/i', '', $where_binaan) : '';

$query = "SELECT s.nama, s.nama_ortu, s.nis, h.tanggal, h.juz, h.pojok, h.surat, h.predikat, h.catatan, u.nama_lengkap AS nama_pengajar
          FROM hafalan h
          JOIN santri s ON s.id = h.id_santri
          LEFT JOIN users u ON u.id = h.id_ustadz
          WHERE h.id = ? AND h.id_santri = ? AND h.jenis = 'tasmi' $and_binaan
          LIMIT 1";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'ii', $id_tasmi, $id_santri);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$syahadah = $result ? mysqli_fetch_assoc($result) : null;
mysqli_stmt_close($stmt);

if (!$syahadah || !in_array($syahadah['predikat'], ['A', 'B'], true)) {
    http_response_code(404);
    exit('Data syahadah tidak ditemukan.');
}

$page_title = "Syahadah Tasmi' - " . htmlspecialchars($syahadah['nama'], ENT_QUOTES, 'UTF-8');
include __DIR__ . '/../../includes/header.php';
?>
<body class="min-h-screen bg-slate-100 p-6 text-slate-800">
    <main class="syahadah mx-auto max-w-5xl rounded-3xl border-8 border-double border-amber-500 bg-white p-10 text-center shadow-xl md:p-16">
        <p class="text-sm font-bold uppercase tracking-[0.35em] text-amber-700">Surau Tahfizh Firdaus</p>
        <h1 class="mt-5 text-4xl font-extrabold tracking-wide text-slate-900">SYAHADAH TASMI'</h1>
        <p class="mt-3 text-slate-500">Diberikan dengan penuh penghargaan kepada</p>
        <h2 class="mt-6 text-4xl font-bold text-indigo-900"><?= htmlspecialchars($syahadah['nama'], ENT_QUOTES, 'UTF-8') ?></h2>
        <p class="mt-2 text-sm text-slate-500">NIS: <?= htmlspecialchars($syahadah['nis'] ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
        <p class="mx-auto mt-8 max-w-3xl leading-8 text-slate-700">
            Atas keberhasilan menyelesaikan ujian tasmi' hafalan Al-Qur'an
            <strong><?= htmlspecialchars($syahadah['surat'] ?: ('Juz ' . ($syahadah['juz'] ?? '-')), ENT_QUOTES, 'UTF-8') ?></strong>
            dengan predikat <strong><?= htmlspecialchars($syahadah['predikat'], ENT_QUOTES, 'UTF-8') ?></strong>.
        </p>
        <?php if (!empty($syahadah['pojok'])): ?>
            <p class="mt-2 text-slate-600">Capaian: <?= htmlspecialchars($syahadah['pojok'], ENT_QUOTES, 'UTF-8') ?> pojok</p>
        <?php endif; ?>
        <p class="mt-8 text-sm text-slate-500">Semoga Allah memberkahi hafalan dan mengamalkannya.</p>
        <div class="mt-14 flex justify-between gap-10 text-sm text-slate-600">
            <div class="flex-1"><p><?= date('d F Y', strtotime($syahadah['tanggal'])) ?></p><p class="mt-16 border-t border-slate-400 pt-2"><?= htmlspecialchars($syahadah['nama_pengajar'] ?? 'Penguji', ENT_QUOTES, 'UTF-8') ?></p><p>Penguji</p></div>
            <div class="flex-1"><p>Surau Tahfizh Firdaus</p><p class="mt-16 border-t border-slate-400 pt-2"><?= htmlspecialchars($syahadah['nama_ortu'] ?? $syahadah['nama'], ENT_QUOTES, 'UTF-8') ?></p><p>Wali Santri</p></div>
        </div>
    </main>
    <div class="no-print mx-auto mt-5 flex max-w-5xl justify-center gap-3">
        <button onclick="window.print()" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white">Cetak Syahadah</button>
        <a href="input_tasmi.php" class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700">Kembali</a>
    </div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
