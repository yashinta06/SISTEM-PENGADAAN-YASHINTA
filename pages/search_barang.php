<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

header('Content-Type: application/json');

// Check login
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get search query
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($search) < 2) {
    echo json_encode([]);
    exit();
}

$search_clean = mysqli_real_escape_string($koneksi, $search);

// Query database - ambil semua barang aktif
$query = "SELECT kode_barang, nama_barang, harga_terakhir, satuan 
          FROM barang 
          WHERE (nama_barang LIKE '%$search_clean%' OR kode_barang LIKE '%$search_clean%')
          ORDER BY nama_barang ASC 
          LIMIT 20";

$result = mysqli_query($koneksi, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => mysqli_error($koneksi)]);
    exit();
}

$items = [];
while($row = mysqli_fetch_assoc($result)) {
    $items[] = [
        'id' => $row['kode_barang'],
        'text' => '[' . $row['kode_barang'] . '] ' . $row['nama_barang'] . ' - Rp ' . number_format($row['harga_terakhir'], 0, ',', '.'),
        'kode' => $row['kode_barang'],
        'nama' => $row['nama_barang'],
        'harga' => $row['harga_terakhir'],
        'satuan' => $row['satuan']
    ];
}

echo json_encode($items);
?>