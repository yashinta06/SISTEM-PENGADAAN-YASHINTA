<?php
session_start();
/** @var mysqli $koneksi */
require_once '../koneksi.php';

// Cek login admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Set Header untuk download file CSV (Bisa dibuka di Excel)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=Laporan_Pengadaan_' . date('Y-m-d') . '.csv');

// Buka output stream
$output = fopen('php://output', 'w');

// Tulis Header Kolom (BOM untuk support karakter khusus di Excel)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($output, ['No. Request', 'Tanggal', 'Judul Pengajuan', 'Pemohon', 'Total Estimasi (Rp)', 'Status']);

// Query Data
$query = "SELECT pr.request_number, pr.request_date, pr.title, u.name as requester, pr.total_estimated_price, pr.status 
          FROM purchase_requests pr 
          JOIN users u ON pr.user_id = u.id 
          WHERE pr.status IN ('approved', 'ordered', 'completed')
          ORDER BY pr.request_date DESC";
$result = mysqli_query($koneksi, $query);

// Tulis Data ke CSV
while ($row = mysqli_fetch_assoc($result)) {
    fputcsv($output, [
        $row['request_number'],
        date('d/m/Y', strtotime($row['request_date'])),
        $row['title'],
        $row['requester'],
        $row['total_estimated_price'],
        ucfirst($row['status'])
    ]);
}

fclose($output);
exit();
?>