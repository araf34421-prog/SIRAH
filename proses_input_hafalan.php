<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

// Otomatis tambahkan kolom `juz` dan `pojok` di tabel `hafalan` jika belum ada
$cek_juz = mysqli_query($conn, "SHOW COLUMNS FROM hafalan LIKE 'juz'");
if ($cek_juz && mysqli_num_rows($cek_juz) === 0) {
    mysqli_query($conn, "ALTER TABLE hafalan ADD COLUMN `juz` INT(11) DEFAULT NULL AFTER `jenis`");
}
$cek_pojok = mysqli_query($conn, "SHOW COLUMNS FROM hafalan LIKE 'pojok'");
if ($cek_pojok && mysqli_num_rows($cek_pojok) === 0) {
    mysqli_query($conn, "ALTER TABLE hafalan ADD COLUMN `pojok` VARCHAR(30) DEFAULT NULL AFTER `juz`");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_santri  = (int) $_POST['id_santri'];
    $jenis      = mysqli_real_escape_string($conn, $_POST['jenis'] ?? 'ziyadah');
    $juz        = !empty($_POST['juz']) ? (int) $_POST['juz'] : 'NULL';
    $pojok      = !empty($_POST['pojok']) ? "'" . mysqli_real_escape_string($conn, trim($_POST['pojok'])) . "'" : 'NULL';
    $surat      = mysqli_real_escape_string($conn, trim($_POST['surat']));
    $ayat_awal  = (int) ($_POST['ayat_awal'] ?? 1);
    $ayat_akhir = (int) ($_POST['ayat_akhir'] ?? 1);
    $predikat   = mysqli_real_escape_string($conn, $_POST['predikat']);
    $catatan    = mysqli_real_escape_string($conn, trim($_POST['catatan'] ?? ''));
    $id_ustadz  = (int) $_SESSION['user_id'];
    $tanggal    = !empty($_POST['tanggal']) ? mysqli_real_escape_string($conn, $_POST['tanggal']) : date('Y-m-d');

    $jumlah_ayat = ($ayat_akhir >= $ayat_awal) ? ($ayat_akhir - $ayat_awal + 1) : 1;

    $sql = "INSERT INTO hafalan (id_santri, tanggal, jenis, juz, pojok, surat, ayat_awal, ayat_akhir, jumlah_ayat, predikat, catatan, id_ustadz) 
            VALUES ($id_santri, '$tanggal', '$jenis', $juz, $pojok, '$surat', $ayat_awal, $ayat_akhir, $jumlah_ayat, '$predikat', '$catatan', $id_ustadz)";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "Data setoran " . ucfirst($jenis) . " berhasil disimpan ($jumlah_ayat Ayat)!";
    } else {
        $_SESSION['error'] = "Gagal menyimpan data: " . mysqli_error($conn);
    }

    // Redirect kembali ke halaman input yang sesuai
    $map_halaman = [
        'ziyadah'  => 'input_ziyadah.php',
        'murajaah' => 'input_murajaah.php',
        'tasmi'    => 'input_tasmi.php'
    ];
    $tujuan = $map_halaman[$jenis] ?? 'input_ziyadah.php';
    header("Location: $tujuan");
    exit;
} else {
    header("Location: input_ziyadah.php");
    exit;
}
?>