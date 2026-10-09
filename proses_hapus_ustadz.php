<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pimpinan') {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id']; 

    $query = "DELETE FROM users WHERE id = $id AND role = 'ustadz'";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "🗑️ Data ustadz/ustadzah berhasil dihapus.";
    } else {
        $_SESSION['error'] = "❌ Gagal menghapus data.";
    }
}

header("Location: kelola-ustadz.php");
exit;
?>