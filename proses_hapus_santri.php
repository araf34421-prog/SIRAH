<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id_santri = (int) $_GET['id'];

   
    $query_cari = "SELECT id_user_ortu FROM santri WHERE id = $id_santri";
    $result = mysqli_query($conn, $query_cari);
    $data = mysqli_fetch_assoc($result);
    $id_user_ortu = $data['id_user_ortu'];

 
    if (mysqli_query($conn, "DELETE FROM santri WHERE id = $id_santri")) {
        
    
        if ($id_user_ortu) {
            mysqli_query($conn, "DELETE FROM users WHERE id = $id_user_ortu AND role = 'ortu'");
        }
        
        $_SESSION['success'] = "🗑️ Data santri dan akun ortu berhasil dihapus.";
    } else {
        $_SESSION['error'] = "❌ Gagal menghapus data santri.";
    }
}

header("Location: kelola_santri.php");
exit;
?>