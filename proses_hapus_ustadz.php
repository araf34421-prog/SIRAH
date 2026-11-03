<?php
session_start();
require 'config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'pimpinan', 'ustadz'])) {
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