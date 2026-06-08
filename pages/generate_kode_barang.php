<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Terima parameter via GET
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : '';

if ($kategori) {
    $kategori = mysqli_real_escape_string($koneksi, $kategori);
    $prefix = strtoupper(substr($kategori, 0, 3));
    
    // Cari kode terakhir
    $query = "SELECT kode_barang FROM barang WHERE kode_barang LIKE '$prefix-%' ORDER BY kode_barang DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);
    
    $last_number = 0;
    if ($row = mysqli_fetch_assoc($result)) {
        $parts = explode('-', $row['kode_barang']);
        if (isset($parts[1])) {
            $last_number = (int)$parts[1];
        }
    }
    
    $next_number = $last_number + 1;
    $kode_baru = $prefix . '-' . str_pad($next_number, 3, '0', STR_PAD_LEFT);
    
    echo json_encode([
        'success' => true,
        'kode_baru' => $kode_baru
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'No category']);
}
?>