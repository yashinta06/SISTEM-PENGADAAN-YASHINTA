<?php
include 'koneksi.php';

// Daftar user dan passwordnya
$users = [
    'admin@uas.test' => 'admin',
    'budi@uas.test' => 'budi',
    'anisa@uas.test' => 'anisa',
    'siti@uas.test' => 'siti',
    'andi@uas.test' => 'andi',
    'rina@uas.test' => 'rina'
];

echo "<h2>Reset Password Users</h2>";
echo "<pre>";

foreach ($users as $email => $password) {
    // Generate hash bcrypt yang VALID
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $query = "UPDATE users SET password = '$hash' WHERE email = '$email'";
    
    if (mysqli_query($koneksi, $query)) {
        // Test verifikasi
        $verify = password_verify($password, $hash);
        echo "✅ $email => Password: '$password' (Verifikasi: " . ($verify ? 'BERHASIL' : 'GAGAL') . ")<br>";
    } else {
        echo "❌ Gagal update $email: " . mysqli_error($koneksi) . "<br>";
    }
}

echo "</pre>";
echo "<h3>Selesai! Silakan hapus file ini setelah selesai.</h3>";
echo "<a href='auth/login.php'>Ke Halaman Login</a>";
?>