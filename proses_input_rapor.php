<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_santri    = (int) $_POST['id_santri'];
    $kelas        = mysqli_real_escape_string($conn, trim($_POST['kelas']));
    $judul_materi = mysqli_real_escape_string($conn, trim($_POST['judul_materi']));
    $semester     = mysqli_real_escape_string($conn, trim($_POST['semester']));
    $tahun_ajaran = mysqli_real_escape_string($conn, trim($_POST['tahun_ajaran']));
    $juz          = (int) $_POST['juz'];
    $id_ustadz    = (int) $_SESSION['user_id'];

    // 1. Cek apakah Rapor Header sudah ada
    $cek_query = "SELECT id FROM rapor_tahfidz WHERE id_santri = $id_santri AND juz = $juz AND semester = '$semester' AND tahun_ajaran = '$tahun_ajaran'";
    $cek_result = mysqli_query($conn, $cek_query);

    if (mysqli_num_rows($cek_result) > 0) {
        // Jika ada, Update Header & Hapus Detail Lama
        $row = mysqli_fetch_assoc($cek_result);
        $id_rapor = $row['id'];
        mysqli_query($conn, "UPDATE rapor_tahfidz SET kelas = '$kelas', judul_materi = '$judul_materi', id_ustadz = $id_ustadz WHERE id = $id_rapor");
        mysqli_query($conn, "DELETE FROM rapor_tahfidz_detail WHERE id_rapor = $id_rapor"); // Reset detail
    } else {
        // Jika belum, Insert Header Baru
        mysqli_query($conn, "INSERT INTO rapor_tahfidz (id_santri, kelas, judul_materi, semester, tahun_ajaran, juz, id_ustadz) 
                             VALUES ($id_santri, '$kelas', '$judul_materi', '$semester', '$tahun_ajaran', $juz, $id_ustadz)");
        $id_rapor = mysqli_insert_id($conn);
    }

    // 2. Insert Detail Materi & Nilai (Dinamis sesuai jumlah kotak)
    if (isset($_POST['materi']) && isset($_POST['nilai'])) {
        $materi_arr = $_POST['materi'];
        $nilai_arr  = $_POST['nilai'];
        $urutan = 1;

        for ($i = 0; $i < count($materi_arr); $i++) {
            $nama_materi = mysqli_real_escape_string($conn, trim($materi_arr[$i]));
            $nilai = $nilai_arr[$i] !== '' ? (int) $nilai_arr[$i] : 'NULL';
            
            if (!empty($nama_materi)) {
                mysqli_query($conn, "INSERT INTO rapor_tahfidz_detail (id_rapor, urutan, nama_materi, nilai) 
                                     VALUES ($id_rapor, $urutan, '$nama_materi', $nilai)");
                $urutan++;
            }
        }
    }

    $_SESSION['success'] = "Data nilai rapor berhasil disimpan dengan " . (isset($urutan) ? $urutan-1 : 0) . " materi!";
    header("Location: input_rapor.php?id_santri=$id_santri&juz=$juz&semester=" . urlencode($semester) . "&tahun_ajaran=" . urlencode($tahun_ajaran));
    exit;
}
?>