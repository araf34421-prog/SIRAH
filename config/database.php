<?php
$host     = "localhost";
$username = "aurora";       
$password = "password123";  
$database = "hafalan_santri";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
?>