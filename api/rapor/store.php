<?php
require_once __DIR__ . '/../../includes/auth.php';
startAppSession();
require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/init_rbac.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['operator', 'ustadz', 'pimpinan'])) {
    header("Location: login.php");
    exit;
}

// Pastikan tabel rapor_tahfidz & rapor_tahfidz_detail beserta kolom tambahannya tersedia otomatis
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `rapor_tahfidz` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_santri` int(11) NOT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `judul_materi` varchar(100) DEFAULT 'Materi Tahfidz',
  `semester` varchar(20) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `juz` int(11) NOT NULL,
  `id_ustadz` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `rapor_tahfidz_detail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_rapor` int(11) NOT NULL,
  `urutan` int(11) NOT NULL,
  `nama_materi` varchar(100) NOT NULL,
  `nilai` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Tambahkan kolom ekstra khusus Rapor Juz 30 jika belum ada
$extra_cols = [
    "nilai_tahsin" => "INT DEFAULT NULL",
    "naik_juz"     => "VARCHAR(30) DEFAULT '-'",
    "remedi_juz"   => "VARCHAR(30) DEFAULT '-'",
    "ujian_juz_30" => "INT DEFAULT NULL",
    "ujian_juz_1"  => "INT DEFAULT NULL",
    "ujian_juz_2"  => "INT DEFAULT NULL",
    "ujian_juz_3"  => "INT DEFAULT NULL",
    "ujian_juz_4"  => "INT DEFAULT NULL",
    "akhlak"       => "VARCHAR(5) DEFAULT 'A'",
    "kerajinan"    => "VARCHAR(5) DEFAULT 'B'",
    "kebersihan"   => "VARCHAR(5) DEFAULT 'A'",
    "kerapian"     => "VARCHAR(5) DEFAULT 'A'",
    "sakit"        => "INT DEFAULT NULL",
    "izin"         => "INT DEFAULT NULL",
    "alfa"         => "INT DEFAULT NULL",
    "catatan_ortu" => "TEXT DEFAULT NULL",
    "tempat_cetak" => "VARCHAR(100) DEFAULT 'Jalikur Patanangan'",
    "tanggal_cetak"=> "VARCHAR(50) DEFAULT NULL",
    "wali_kelas"   => "VARCHAR(100) DEFAULT NULL",
    "kepala_stq"   => "VARCHAR(100) DEFAULT 'Ust. Aipda Syaiful Hendra'"
];

foreach ($extra_cols as $col_name => $col_def) {
    $cek_c = mysqli_query($conn, "SHOW COLUMNS FROM rapor_tahfidz LIKE '$col_name'");
    if ($cek_c && mysqli_num_rows($cek_c) === 0) {
        mysqli_query($conn, "ALTER TABLE rapor_tahfidz ADD COLUMN `$col_name` $col_def");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!santriDalamBinaan($conn, (int) ($_POST['id_santri'] ?? 0), (int) $_SESSION['user_id'], $_SESSION['role'])) {
        http_response_code(403);
        exit('Akses santri ditolak.');
    }
    $id_santri    = (int) $_POST['id_santri'];
    $kelas        = mysqli_real_escape_string($conn, trim($_POST['kelas']));
    $judul_materi = mysqli_real_escape_string($conn, trim($_POST['judul_materi']));
    $semester     = mysqli_real_escape_string($conn, trim($_POST['semester']));
    $tahun_ajaran = mysqli_real_escape_string($conn, trim($_POST['tahun_ajaran']));
    $juz          = (int) $_POST['juz'];
    $id_ustadz    = (int) $_SESSION['user_id'];

    // Data tambahan (terutama untuk Rapor Juz 30)
    $nilai_tahsin = ($_POST['nilai_tahsin'] ?? '') !== '' ? (int)$_POST['nilai_tahsin'] : 'NULL';
    $naik_juz     = mysqli_real_escape_string($conn, trim($_POST['naik_juz'] ?? '-'));
    $remedi_juz   = mysqli_real_escape_string($conn, trim($_POST['remedi_juz'] ?? '-'));
    $ujian_30     = ($_POST['ujian_juz_30'] ?? '') !== '' ? (int)$_POST['ujian_juz_30'] : 'NULL';
    $ujian_1      = ($_POST['ujian_juz_1'] ?? '') !== '' ? (int)$_POST['ujian_juz_1'] : 'NULL';
    $ujian_2      = ($_POST['ujian_juz_2'] ?? '') !== '' ? (int)$_POST['ujian_juz_2'] : 'NULL';
    $ujian_3      = ($_POST['ujian_juz_3'] ?? '') !== '' ? (int)$_POST['ujian_juz_3'] : 'NULL';
    $ujian_4      = ($_POST['ujian_juz_4'] ?? '') !== '' ? (int)$_POST['ujian_juz_4'] : 'NULL';
    $akhlak       = mysqli_real_escape_string($conn, $_POST['akhlak'] ?? 'A');
    $kerajinan    = mysqli_real_escape_string($conn, $_POST['kerajinan'] ?? 'B');
    $kebersihan   = mysqli_real_escape_string($conn, $_POST['kebersihan'] ?? 'A');
    $kerapian     = mysqli_real_escape_string($conn, $_POST['kerapian'] ?? 'A');
    $sakit        = ($_POST['sakit'] ?? '') !== '' ? (int)$_POST['sakit'] : 'NULL';
    $izin         = ($_POST['izin'] ?? '') !== '' ? (int)$_POST['izin'] : 'NULL';
    $alfa         = ($_POST['alfa'] ?? '') !== '' ? (int)$_POST['alfa'] : 'NULL';
    $catatan_ortu = mysqli_real_escape_string($conn, trim($_POST['catatan_ortu'] ?? ''));
    $tempat_cetak = mysqli_real_escape_string($conn, trim($_POST['tempat_cetak'] ?? 'Jalikur Patanangan'));
    $tanggal_cetak= mysqli_real_escape_string($conn, trim($_POST['tanggal_cetak'] ?? date('d F Y')));
    $wali_kelas   = mysqli_real_escape_string($conn, trim($_POST['wali_kelas'] ?? ''));
    $kepala_stq   = mysqli_real_escape_string($conn, trim($_POST['kepala_stq'] ?? 'Ust. Aipda Syaiful Hendra'));

    // Cek apakah Rapor Header untuk santri & juz ini sudah ada
    $cek_query = "SELECT id FROM rapor_tahfidz WHERE id_santri = $id_santri AND juz = $juz AND semester = '$semester' AND tahun_ajaran = '$tahun_ajaran'";
    $cek_result = mysqli_query($conn, $cek_query);

    if (mysqli_num_rows($cek_result) > 0) {
        $row = mysqli_fetch_assoc($cek_result);
        $id_rapor = (int) $row['id'];
        $sql_upd = "UPDATE rapor_tahfidz SET 
                        kelas = '$kelas',
                        judul_materi = '$judul_materi',
                        id_ustadz = $id_ustadz,
                        nilai_tahsin = $nilai_tahsin,
                        naik_juz = '$naik_juz',
                        remedi_juz = '$remedi_juz',
                        ujian_juz_30 = $ujian_30,
                        ujian_juz_1 = $ujian_1,
                        ujian_juz_2 = $ujian_2,
                        ujian_juz_3 = $ujian_3,
                        ujian_juz_4 = $ujian_4,
                        akhlak = '$akhlak',
                        kerajinan = '$kerajinan',
                        kebersihan = '$kebersihan',
                        kerapian = '$kerapian',
                        sakit = $sakit,
                        izin = $izin,
                        alfa = $alfa,
                        catatan_ortu = '$catatan_ortu',
                        tempat_cetak = '$tempat_cetak',
                        tanggal_cetak = '$tanggal_cetak',
                        wali_kelas = '$wali_kelas',
                        kepala_stq = '$kepala_stq'
                    WHERE id = $id_rapor";
        mysqli_query($conn, $sql_upd);
        mysqli_query($conn, "DELETE FROM rapor_tahfidz_detail WHERE id_rapor = $id_rapor");
    } else {
        $sql_ins = "INSERT INTO rapor_tahfidz (
                        id_santri, kelas, judul_materi, semester, tahun_ajaran, juz, id_ustadz,
                        nilai_tahsin, naik_juz, remedi_juz, ujian_juz_30, ujian_juz_1, ujian_juz_2, ujian_juz_3, ujian_juz_4,
                        akhlak, kerajinan, kebersihan, kerapian, sakit, izin, alfa, catatan_ortu, tempat_cetak, tanggal_cetak, wali_kelas, kepala_stq
                    ) VALUES (
                        $id_santri, '$kelas', '$judul_materi', '$semester', '$tahun_ajaran', $juz, $id_ustadz,
                        $nilai_tahsin, '$naik_juz', '$remedi_juz', $ujian_30, $ujian_1, $ujian_2, $ujian_3, $ujian_4,
                        '$akhlak', '$kerajinan', '$kebersihan', '$kerapian', $sakit, $izin, $alfa, '$catatan_ortu', '$tempat_cetak', '$tanggal_cetak', '$wali_kelas', '$kepala_stq'
                    )";
        mysqli_query($conn, $sql_ins);
        $id_rapor = mysqli_insert_id($conn);
    }

    // Simpan Detail Materi & Nilai
    $urutan = 1;
    if (isset($_POST['materi']) && isset($_POST['nilai'])) {
        $materi_arr = $_POST['materi'];
        $nilai_arr  = $_POST['nilai'];

        for ($i = 0; $i < count($materi_arr); $i++) {
            $nama_materi = mysqli_real_escape_string($conn, trim($materi_arr[$i]));
            $nilai_raw   = trim((string)($nilai_arr[$i] ?? ''));
            $nilai       = ($nilai_raw !== '') ? max(0, min(100, (int)$nilai_raw)) : 'NULL';
            
            if (!empty($nama_materi)) {
                mysqli_query($conn, "INSERT INTO rapor_tahfidz_detail (id_rapor, urutan, nama_materi, nilai) 
                                     VALUES ($id_rapor, $urutan, '$nama_materi', $nilai)");
                $urutan++;
            }
        }
    }

    $_SESSION['success'] = "Data Leger Rapor Juz $juz berhasil disimpan (" . ($urutan - 1) . " baris materi)!";
    header("Location: input_rapor.php?id_santri=$id_santri&juz=$juz&semester=" . urlencode($semester) . "&tahun_ajaran=" . urlencode($tahun_ajaran));
    exit;
}
?>
