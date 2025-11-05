<?php
$host     = "localhost";
$user     = "root";
$password = "";
$database = "tepi_sawah";

$conn = mysqli_connect($host, $user, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// echo "Koneksi berhasil";
