<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

$page_title = 'Tambah User - UAS Pengadaan';

if ($_SESSION['user_role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

$error = '';

// Ambil daftar role untuk dropdown
$roles = mysqli_query($koneksi, "SELECT * FROM roles ORDER BY id ASC");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($koneksi, $_POST['name']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $role_id = (int)$_POST['role_id'];
    $department = mysqli_real_escape_string($koneksi, $_POST['department']);
    $password = trim($_POST['password']);

    // Cek email duplikat
    $check = mysqli_query($koneksi, "SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = "Email sudah terdaftar!";
    } else {
        $query = "INSERT INTO users (name, email, role_id, department, password, is_active) 
                  VALUES ('$name', '$email', $role_id, '$department', '$password', 1)";
        
        if (mysqli_query($koneksi, $query)) {
            header("Location: manajemen_user.php?msg=success");
            exit();
        } else {
            $error = "Gagal menyimpan: " . mysqli_error($koneksi);
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1>Tambah User Baru</h1>
        </div>
    </div>
    
    <section class="content">
        <div class="container-fluid">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Form Data User</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" placeholder="Nama karyawan" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" placeholder="email@uas.test" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Jabatan (Role)</label>
                                    <select name="role_id" class="form-control" required>
                                        <option value="">-- Pilih Jabatan --</option>
                                        <?php while($r = mysqli_fetch_assoc($roles)): ?>
                                            <option value="<?= $r['id'] ?>"><?= ucfirst($r['name']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Departemen</label>
                                    <input type="text" name="department" class="form-control" placeholder="Contoh: IT, HRD, Gudang" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Password</label>
                                    <input type="text" name="password" class="form-control" placeholder="Password untuk login" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan User
                        </button>
                        <a href="manajemen_user.php" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>