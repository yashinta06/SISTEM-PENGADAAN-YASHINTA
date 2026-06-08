<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';

header('Content-Type: application/json');

if (isset($_POST['kategori'])) {
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori']);
    
    // Ambil barang terakhir dari kategori tersebut
    $query = "SELECT * FROM barang WHERE kategori = '$kategori' ORDER BY id DESC LIMIT 1";
    $result = mysqli_query($koneksi, $query);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $data = mysqli_fetch_assoc($result);
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Tidak ada data barang untuk kategori ini'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Kategori tidak valid'
    ]);
}
?>