<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'pimpinan', 'ustadz'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = (string) ($_POST['password'] ?? '');
    if (strlen($password) < 12) {
        $_SESSION['error'] = 'Password akun pengajar minimal 12 karakter.';
        header("Location: kelola-ustadz.php");
        exit;
    }
    $role = 'ustadz'; 

    $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username'");
    if (mysqli_num_rows($check) > 0) {
        $_SESSION['error'] = "❌ Username '$username' sudah digunakan! Silakan gunakan username lain.";
        header("Location: kelola-ustadz.php");
        exit;
    }

    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO users (username, password, nama_lengkap, role) 
              VALUES ('$username', '$password_hash', '$nama_lengkap', '$role')";

    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = "✅ Data ustadz/ustadzah berhasil ditambahkan!";
    } else {
        $_SESSION['error'] = "❌ Terjadi kesalahan saat menyimpan data.";
    }

    header("Location: kelola-ustadz.php");
    exit;
}
?>
