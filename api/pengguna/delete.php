<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = (int) $_POST['id']; 

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