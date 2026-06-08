<?php
// Prevent caching of this page
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/dashboard.php");
    exit();
}
?>
<?php
session_start();
/** @var mysqli $koneksi */
require_once '../koneksi.php';

if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = trim($_POST['password']);

    $query = "SELECT u.*, r.name AS role_name 
              FROM users u 
              JOIN roles r ON u.role_id = r.id 
              WHERE u.email = '$email' AND u.is_active = 1";
    
    $result = mysqli_query($koneksi, $query);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        
        if ($password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role_name'];
            $_SESSION['user_department'] = $user['department'];

            require_once '../includes/audit_log.php';
            log_activity($koneksi, 'login', 'User login berhasil');


            header("Location: ../pages/dashboard.php");
            exit();
        } else {
            $error = "Password salah!";
        }
    } else {
        $error = "Email tidak terdaftar!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Login - UAS Pengadaan</title>
    <!-- CSS lainnya -->
    <meta charset="UTF-8">
    <title>Login - Pengadaan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #121212; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-box { width: 400px; }
        .login-logo a { color: #ffffff !important; font-size: 28px; font-weight: bold; }
        .btn-login { background-color: #000000 !important; border-color: #000000 !important; color: #ffffff !important; }
        .btn-login:hover { background-color: #ffffff !important; color: #000000 !important; }
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            z-index: 10;
        }
        .password-toggle:hover { color: #000; }
        .input-group { position: relative; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-logo">
            <a href="#"><i class="fas fa-boxes"></i> UAS Pengadaan</a>
        </div>
        <div class="card">
            <div class="card-body">
                <p class="login-box-msg" style="font-weight: bold;">Masuk ke Akun Anda</p>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="input-group mb-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-envelope"></span></div>
                        </div>
                    </div>
                    
                    <div class="input-group mb-3">
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
                        <div class="input-group-append">
                            <div class="input-group-text"><span class="fas fa-lock"></span></div>
                        </div>
                        <span class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                    
                    <button type="submit" class="btn btn-login btn-block">LOGIN</button>
                </form>

                <div class="mt-3 text-center">
                    <small>Belum punya akun? <a href="register.php" style="color: #000; font-weight: bold;">Daftar di sini</a></small>
                    <br><br>
                    <small class="text-muted">Super Admin: admin@uas.test / admin</small>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>
    <script>
        function togglePassword() {
            var passwordField = document.getElementById('password');
            var toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
    <script>
// Clear browser history to prevent back button after logout
if (window.history && window.history.pushState) {
    window.history.pushState(null, null, window.location.href);
    window.onpopstate = function() {
        // If user tries to go back, redirect to login
        window.history.pushState(null, null, window.location.href);
    };
}
</script>
</body>
</html>