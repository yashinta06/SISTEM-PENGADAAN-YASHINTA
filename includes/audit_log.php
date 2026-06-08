<?php
/**
 * Fungsi untuk mencatat aktivitas user ke tabel audit_logs
 * 
 * @param mysqli $koneksi Koneksi database
 * @param string $action Jenis aksi (login, create, update, delete, dll)
 * @param string $description Deskripsi detail aksi
 */
function log_activity($koneksi, $action, $description = '') {
    // Cek apakah user sudah login
    if (!isset($_SESSION['user_id'])) {
        return; // Jangan log kalau belum login
    }
    
    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_name'] ?? 'Unknown';
    $user_role = $_SESSION['user_role'] ?? 'Unknown';
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    // Escape data untuk keamanan
    $action = mysqli_real_escape_string($koneksi, $action);
    $description = mysqli_real_escape_string($koneksi, $description);
    $user_name = mysqli_real_escape_string($koneksi, $user_name);
    $user_role = mysqli_real_escape_string($koneksi, $user_role);
    $ip_address = mysqli_real_escape_string($koneksi, $ip_address);
    
    // Insert ke tabel audit_logs
    $query = "INSERT INTO audit_logs (user_id, user_name, user_role, action, description, ip_address) 
              VALUES ($user_id, '$user_name', '$user_role', '$action', '$description', '$ip_address')";
    
    mysqli_query($koneksi, $query);
}
?>