<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

// Hanya admin yang boleh akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Cek apakah ID ada
if (!isset($_GET['id'])) {
    header("Location: manajemen_user.php");
    exit();
}

$id = (int)$_GET['id'];
$page_title = 'Edit User - UAS Pengadaan';

// ==========================================
// PROSES UPDATE USER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($koneksi, $_POST['name']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $role = mysqli_real_escape_string($koneksi, $_POST['role']);
    $department = mysqli_real_escape_string($koneksi, $_POST['department']);
    $password = $_POST['password'];
    
    // Query update - gunakan kolom 'role' bukan 'role_id'
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET name='$name', email='$email', role='$role', department='$department', password='$hashed_password' WHERE id=$id";
    } else {
        $sql = "UPDATE users SET name='$name', email='$email', role='$role', department='$department' WHERE id=$id";
    }
    
    if (mysqli_query($koneksi, $sql)) {
        header("Location: manajemen_user.php?msg=updated");
        exit();
    } else {
        $error = "Gagal mengupdate user: " . mysqli_error($koneksi);
    }
}

// ==========================================
// AMBIL DATA USER
// ==========================================
$user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id = $id"));
if (!$user) {
    header("Location: manajemen_user.php");
    exit();
}
?>

<?php include '../includes/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1><i class="fas fa-user-edit"></i> Edit User</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h3 class="card-title">Form Edit User</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nama Lengkap <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Role <span class="text-danger">*</span></label>
                                    <select name="role" class="form-control" required>
                                        <option value="">-- Pilih Role --</option>
                                        <option value="admin" <?= ($user['role'] ?? '') == 'admin' ? 'selected' : '' ?>>Admin</option>
                                        <option value="manager" <?= ($user['role'] ?? '') == 'manager' ? 'selected' : '' ?>>Manager</option>
                                        <option value="purchasing" <?= ($user['role'] ?? '') == 'purchasing' ? 'selected' : '' ?>>Purchasing</option>
                                        <option value="warehouse" <?= ($user['role'] ?? '') == 'warehouse' ? 'selected' : '' ?>>Warehouse</option>
                                        <option value="requester" <?= ($user['role'] ?? '') == 'requester' ? 'selected' : '' ?>>Requester</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Departemen <span class="text-danger">*</span></label>
                                    <select name="department" class="form-control" required>
                                        <option value="">-- Pilih Departemen --</option>
                                        <option value="IT" <?= ($user['department'] ?? '') == 'IT' ? 'selected' : '' ?>>IT</option>
                                        <option value="HRD" <?= ($user['department'] ?? '') == 'HRD' ? 'selected' : '' ?>>HRD</option>
                                        <option value="Finance" <?= ($user['department'] ?? '') == 'Finance' ? 'selected' : '' ?>>Finance</option>
                                        <option value="Marketing" <?= ($user['department'] ?? '') == 'Marketing' ? 'selected' : '' ?>>Marketing</option>
                                        <option value="Operasional" <?= ($user['department'] ?? '') == 'Operasional' ? 'selected' : '' ?>>Operasional</option>
                                        <option value="General Affair" <?= ($user['department'] ?? '') == 'General Affair' ? 'selected' : '' ?>>General Affair</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Password Baru <small class="text-muted">(Kosongkan jika tidak ingin mengubah)</small></label>
                                    <input type="password" name="password" class="form-control" placeholder="Masukkan password baru">
                                    <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah password</small>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <a href="manajemen_user.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>