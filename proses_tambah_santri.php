<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_santri    = mysqli_real_escape_string($conn, $_POST['nama_santri']);
    $nis            = mysqli_real_escape_string($conn, $_POST['nis']);
    $jk             = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $tgl_lahir      = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $alamat         = mysqli_real_escape_string($conn, $_POST['alamat']);
    $nama_ortu      = mysqli_real_escape_string($conn, $_POST['nama_ortu']);
    $no_hp_ortu     = mysqli_real_escape_string($conn, $_POST['no_hp_ortu']);
    $username_ortu  = mysqli_real_escape_string($conn, $_POST['username_ortu']);
    $password_ortu  = $_POST['password_ortu'];

    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username_ortu'");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['error'] = "❌ Username orang tua '$username_ortu' sudah digunakan!";
        header("Location: kelola-santri.php");
        exit;
    }
    $hash_ortu = password_hash($password_ortu, PASSWORD_DEFAULT);

    $sql_user = "INSERT INTO users (username, password, nama_lengkap, role) 
                 VALUES ('$username_ortu', '$hash_ortu', '$nama_ortu', 'ortu')";
    
    if (mysqli_query($conn, $sql_user)) {
    
        $id_user_ortu = mysqli_insert_id($conn);

        $sql_santri = "INSERT INTO santri (nama, nis, jenis_kelamin, tanggal_lahir, alamat, nama_ortu, no_hp_ortu, id_user_ortu) 
                       VALUES ('$nama_santri', '$nis', '$jk', '$tgl_lahir', '$alamat', '$nama_ortu', '$no_hp_ortu', $id_user_ortu)";
        
        if (mysqli_query($conn, $sql_santri)) {
            $_SESSION['success'] = "✅ Santri '$nama_santri' dan akun Ortu berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "❌ Gagal simpan data santri: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['error'] = "❌ Gagal membuat akun ortu: " . mysqli_error($conn);
    }

    header("Location: kelola_santri.php");
    exit;
}
?>