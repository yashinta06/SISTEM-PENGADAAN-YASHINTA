<?php
// Konfigurasi Database Laragon
$host = "localhost";
$user = "root";       // Default user Laragon
$pass = "";           // Default password Laragon (kosong)
$db   = "uaspengadaan";

// Melakukan koneksi
$koneksi = mysqli_connect($host, $user, $pass, $db);

// Cek apakah koneksi berhasil
if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}
?>