<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/init_rbac.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_santri     = (int) $_POST['id_santri'];
    if (!santriDalamBinaan($conn, $id_santri, (int) $_SESSION['user_id'], $_SESSION['role'])) {
        http_response_code(403);
        exit('Akses santri ditolak.');
    }
    $id_user_ortu  = (int) $_POST['id_user_ortu'];
    $nama_santri   = mysqli_real_escape_string($conn, trim($_POST['nama_santri']));
    $nis           = mysqli_real_escape_string($conn, trim($_POST['nis']));
    $jk            = mysqli_real_escape_string($conn, $_POST['jenis_kelamin']);
    $tgl_lahir     = mysqli_real_escape_string($conn, $_POST['tanggal_lahir']);
    $alamat        = mysqli_real_escape_string($conn, trim($_POST['alamat']));
    $nama_ortu     = mysqli_real_escape_string($conn, trim($_POST['nama_ortu']));
    $no_hp_ortu    = mysqli_real_escape_string($conn, trim($_POST['no_hp_ortu']));
    $username_ortu = mysqli_real_escape_string($conn, trim($_POST['username_ortu']));
    $password_baru = trim($_POST['password_baru'] ?? '');
    if ($password_baru !== '' && strlen($password_baru) < 12) {
        $_SESSION['error'] = 'Password baru minimal 12 karakter.';
        header("Location: kelola_santri.php");
        exit;
    }
    $id_halaqah    = !empty($_POST['id_halaqah']) ? (int) $_POST['id_halaqah'] : "NULL";

    // 1. Cek duplikasi NIS pada santri lain
    $cek_nis = mysqli_query($conn, "SELECT id FROM santri WHERE nis = '$nis' AND id != $id_santri");
    if (mysqli_num_rows($cek_nis) > 0) {
        $_SESSION['error'] = "NIS '$nis' sudah terdaftar pada santri lain!";
        header("Location: kelola_santri.php");
        exit;
    }

    // 2. Update atau buat akun user orang tua
    if ($id_user_ortu > 0) {
        $cek_user = mysqli_query($conn, "SELECT id FROM users WHERE username = '$username_ortu' AND id != $id_user_ortu");
        if (mysqli_num_rows($cek_user) > 0) {
            $_SESSION['error'] = "Username '$username_ortu' sudah dipakai akun lain!";
            header("Location: kelola_santri.php");
            exit;
        }

        if (!empty($password_baru)) {
            $hash = password_hash($password_baru, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET username = '$username_ortu', nama_lengkap = '$nama_ortu', password = '$hash' WHERE id = $id_user_ortu");
        } else {
            mysqli_query($conn, "UPDATE users SET username = '$username_ortu', nama_lengkap = '$nama_ortu' WHERE id = $id_user_ortu");
        }
    } elseif (!empty($username_ortu)) {
        // Akun baru hanya dibuat dengan password yang ditentukan operator.
        if ($password_baru === '') {
            $_SESSION['error'] = 'Isi password minimal 12 karakter untuk membuat akun orang tua.';
            header("Location: kelola_santri.php");
            exit;
        }
        $hash = password_hash($password_baru, PASSWORD_DEFAULT);
        if (mysqli_query($conn, "INSERT INTO users (username, password, nama_lengkap, role) VALUES ('$username_ortu', '$hash', '$nama_ortu', 'ortu')")) {
            $id_user_ortu = mysqli_insert_id($conn);
        }
    }

    // 3. Update tabel santri
    $id_ortu_sql = $id_user_ortu > 0 ? $id_user_ortu : "NULL";
    $sql_update = "UPDATE santri SET 
                    nama = '$nama_santri',
                    nis = '$nis',
                    jenis_kelamin = '$jk',
                    tanggal_lahir = '$tgl_lahir',
                    alamat = '$alamat',
                    nama_ortu = '$nama_ortu',
                    no_hp_ortu = '$no_hp_ortu',
                    id_user_ortu = $id_ortu_sql,
                    id_halaqah = $id_halaqah
                   WHERE id = $id_santri";

    if (mysqli_query($conn, $sql_update)) {
        $_SESSION['success'] = "Data santri '$nama_santri' berhasil diperbarui!";
    } else {
        $_SESSION['error'] = "Gagal memperbarui data: " . mysqli_error($conn);
    }

    header("Location: kelola_santri.php");
    exit;
}
?>
