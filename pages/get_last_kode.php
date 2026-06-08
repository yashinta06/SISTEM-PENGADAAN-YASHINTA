<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

// Hanya admin yang boleh akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if (isset($_GET['kategori'])) {
    $kategori = mysqli_real_escape_string($koneksi, $_GET['kategori']);
    $prefix = substr($kategori, 0, 3);
    
    // Cari kode terakhir dengan prefix yang sama
    $query = "SELECT kode_barang FROM barang WHERE kode_barang LIKE '$prefix-%' ORDER BY kode_barang DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);
    
    $last_number = 0;
    if ($row = mysqli_fetch_assoc($result)) {
        // Extract angka dari kode (misal: ATK-005 -> 5)
        $parts = explode('-', $row['kode_barang']);
        if (isset($parts[1])) {
            $last_number = (int)$parts[1];
        }
    }
    
    echo json_encode(['last_number' => $last_number]);
} else {
    echo json_encode(['error' => 'Kategori tidak ada']);
}
?>