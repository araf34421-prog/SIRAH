<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $id_santri    = (int) $_POST['id_santri'];
    $jenis        = mysqli_real_escape_string($conn, $_POST['jenis']);
    $surat        = mysqli_real_escape_string($conn, $_POST['surat']);
    $ayat_awal    = (int) $_POST['ayat_awal'];
    $ayat_akhir   = (int) $_POST['ayat_akhir'];
    $predikat     = mysqli_real_escape_string($conn, $_POST['predikat']);
    $catatan      = mysqli_real_escape_string($conn, $_POST['catatan']);
    $id_ustadz    = $_SESSION['user_id']; 
    $tanggal      = date('Y-m-d'); 

    
    $jumlah_ayat = ($ayat_akhir >= $ayat_awal) ? ($ayat_akhir - $ayat_awal + 1) : 1;


    $sql = "INSERT INTO hafalan (id_santri, tanggal, jenis, surat, ayat_awal, ayat_akhir, jumlah_ayat, predikat, catatan, id_ustadz) 
            VALUES ($id_santri, '$tanggal', '$jenis', '$surat', $ayat_awal, $ayat_akhir, $jumlah_ayat, '$predikat', '$catatan', $id_ustadz)";


    if (mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "✅ Data hafalan ($jenis) berhasil disimpan!";
    } else {
        $_SESSION['error'] = "❌ Gagal menyimpan data: " . mysqli_error($conn);
    }


    header("Location: input_hafalan.php");
    exit;
} else {
    
    header("Location: input_hafalan.php");
    exit;
}
?>