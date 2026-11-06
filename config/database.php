<?php
$host = getenv('SIRAH_DB_HOST') ?: 'localhost';
$username = getenv('SIRAH_DB_USER') ?: 'root';
$password = getenv('SIRAH_DB_PASSWORD');
$password = $password === false ? '' : $password;
$database = getenv('SIRAH_DB_NAME') ?: 'hafalan_santri';
$environment = getenv('SIRAH_ENV') ?: 'development';

if ($environment === 'production' && ($username === 'root' || $password === '')) {
    error_log('SIRAH production database credentials are not configured safely.');
    http_response_code(500);
    exit('Konfigurasi database produksi belum aman.');
}

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    error_log('SIRAH database connection failed: ' . $conn->connect_error);
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi server.');
}

$conn->set_charset("utf8mb4");

?>
