<?php
session_start();
require 'config/database.php';

// 🔒 Keamanan: Hanya Ustadz dan Pimpinan
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Ambil dan bersihkan data
    $id_santri      = (int) $_POST['id_santri'];
    $target_ayat    = (int) $_POST['target_ayat'];
    $tanggal_mulai  = mysqli_real_escape_string($conn, $_POST['tanggal_mulai']);
    $keterangan     = mysqli_real_escape_string($conn, $_POST['keterangan']);

    // 2. Cek apakah santri ini sudah punya target sebelumnya
    $cek_query = "SELECT id FROM target_hafalan WHERE id_santri = $id_santri ORDER BY id DESC LIMIT 1";
    $cek_result = mysqli_query($conn, $cek_query);

    if (mysqli_num_rows($cek_result) > 0) {
        // SUDAH ADA -> Lakukan UPDATE
        $data = mysqli_fetch_assoc($cek_result);
        $id_target = $data['id'];

        $sql = "UPDATE target_hafalan 
                SET target_ayat_per_hari = $target_ayat, 
                    tanggal_mulai = '$tanggal_mulai', 
                    keterangan = '$keterangan' 
                WHERE id = $id_target";
        
        $pesan = "Target santri berhasil diperbarui!";
    } else {
        // BELUM ADA -> Lakukan INSERT
        $sql = "INSERT INTO target_hafalan (id_santri, tanggal_mulai, target_ayat_per_hari, keterangan) 
                VALUES ($id_santri, '$tanggal_mulai', $target_ayat, '$keterangan')";
        
        $pesan = "Target santri baru berhasil ditetapkan!";
    }

    // 3. Eksekusi Query
    if (mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "✅ $pesan";
    } else {
        $_SESSION['error'] = "❌ Gagal menyimpan target: " . mysqli_error($conn);
    }

    // 4. Kembali ke halaman target
    header("Location: target_santri.php");
    exit;
} else {
    header("Location: target_santri.php");
    exit;
}
?>