<?php
session_start();
/** @var mysqli $koneksi */
require_once '../koneksi.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/dashboard.php");
    exit();
}

$error = '';
$success = '';

// Ambil daftar role (kecuali Admin biar aman)
$roles = mysqli_query($koneksi, "SELECT * FROM roles WHERE id != 1 ORDER BY id");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($koneksi, $_POST['name']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $role_id = (int)$_POST['role_id'];
    $department = mysqli_real_escape_string($koneksi, $_POST['department']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok!";
    } else {
        $check = mysqli_query($koneksi, "SELECT id FROM users WHERE email = '$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "Email sudah terdaftar!";
        } else {
            // Simpan password teks biasa sesuai DBeaver
            $query = "INSERT INTO users (name, email, role_id, department, password, is_active) 
                      VALUES ('$name', '$email', $role_id, '$department', '$password', 1)";
            
            if (mysqli_query($koneksi, $query)) {
                $success = "Pendaftaran berhasil! Silakan login.";
                header("refresh:2;url=login.php");
            } else {
                $error = "Gagal: " . mysqli_error($koneksi);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar - UAS Pengadaan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #121212; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px 0; }
        .register-box { width: 500px; }
        .register-logo a { color: #ffffff !important; font-size: 28px; font-weight: bold; }
        .btn-register { background-color: #000000 !important; border-color: #000000 !important; color: #ffffff !important; }
        .btn-register:hover { background-color: #ffffff !important; color: #000000 !important; }
        .password-toggle { cursor: pointer; position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #666; z-index: 10; }
        .password-toggle:hover { color: #000; }
        .input-group { position: relative; }
    </style>
</head>
<body>
    <div class="register-box">
        <div class="register-logo">
            <a href="#"><i class="fas fa-user-plus"></i> Daftar Akun</a>
        </div>
        <div class="card">
            <div class="card-body">
                <p class="login-box-msg" style="font-weight: bold;">Buat Akun Baru</p>

                <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group mb-3">
                        <input type="text" name="name" class="form-control" placeholder="Nama Lengkap" required>
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-user"></span></div></div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-envelope"></span></div></div>
                    </div>
                    <div class="input-group mb-3">
                        <select name="role_id" class="form-control" required>
                            <option value="">-- Pilih Jabatan --</option>
                            <?php while($role = mysqli_fetch_assoc($roles)): ?>
                                <option value="<?= $role['id'] ?>"><?= ucfirst($role['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-briefcase"></span></div></div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="text" name="department" class="form-control" placeholder="Departemen (IT, HRD, dll)" required>
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-building"></span></div></div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                        <span class="password-toggle" onclick="togglePassword('password', 'eye1')"><i class="fas fa-eye" id="eye1"></i></span>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Konfirmasi Password" required>
                        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
                        <span class="password-toggle" onclick="togglePassword('confirm_password', 'eye2')"><i class="fas fa-eye" id="eye2"></i></span>
                    </div>
                    <button type="submit" class="btn btn-register btn-block">DAFTAR</button>
                </form>

                <div class="mt-3 text-center">
                    <small>Sudah punya akun? <a href="login.php" style="color: #000; font-weight: bold;">Login di sini</a></small>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId, eyeId) {
            var field = document.getElementById(fieldId);
            var eye = document.getElementById(eyeId);
            if (field.type === 'password') {
                field.type = 'text';
                eye.classList.remove('fa-eye');
                eye.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                eye.classList.remove('fa-eye-slash');
                eye.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>