<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/init_rbac.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id_santri = (int) $_POST['id'];

   
    $where_binaan = getFilterSantriBinaanSQL($conn, (int) $_SESSION['user_id'], $_SESSION['role'], 's');
    $and_binaan = $where_binaan !== '' ? ' AND ' . preg_replace('/^\s*WHERE\s+/i', '', $where_binaan) : '';
    $query_cari = "SELECT s.id_user_ortu FROM santri s WHERE s.id = $id_santri $and_binaan LIMIT 1";
    $result = mysqli_query($conn, $query_cari);
    $data = $result ? mysqli_fetch_assoc($result) : null;

    if (!$data) {
        $_SESSION['error'] = 'Santri tidak ditemukan atau berada di luar kewenangan Anda.';
        header('Location: kelola_santri.php');
        exit;
    }

    $id_user_ortu = (int) ($data['id_user_ortu'] ?? 0);

 
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
