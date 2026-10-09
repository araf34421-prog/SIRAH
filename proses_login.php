<?php
session_start();
require 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php");
    exit;
}

$username = mysqli_real_escape_string($conn, $_POST['username']);
$password = $_POST['password'];

$query = "SELECT * FROM users WHERE username = '$username'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);

    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id']      = $user['id'];
        $_SESSION['username']     = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role']         = $user['role'];

        header("Location: dashboard.php");
        exit;
    } else {
        $_SESSION['error'] = "❌ Password salah!";
        header("Location: login.php");
        exit;
    }
} else {
    $_SESSION['error'] = "❌ Username tidak ditemukan!";
    header("Location: login.php");
    exit;
}
?>