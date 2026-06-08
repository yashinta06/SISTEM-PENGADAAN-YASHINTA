<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

// Hanya admin yang boleh akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Cek ID
if (!isset($_GET['id'])) {
    header("Location: vendor.php");
    exit();
}

$id = (int)$_GET['id'];
$page_title = 'Edit Vendor - UAS Pengadaan';

// Proses update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($koneksi, $_POST['name']);
    $contact_person = mysqli_real_escape_string($koneksi, $_POST['contact_person']);
    $phone = mysqli_real_escape_string($koneksi, $_POST['phone']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $address = mysqli_real_escape_string($koneksi, $_POST['address']);
    $npwp = mysqli_real_escape_string($koneksi, $_POST['npwp']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);
    
    $sql = "UPDATE vendors SET 
            name='$name', 
            contact_person='$contact_person', 
            phone='$phone', 
            email='$email', 
            address='$address', 
            npwp='$npwp', 
            status='$status' 
            WHERE id=$id";
    
    if (mysqli_query($koneksi, $sql)) {
        header("Location: vendor.php?msg=updated");
        exit();
    } else {
        $error = "Gagal update vendor: " . mysqli_error($koneksi);
    }
}

// Ambil data vendor
$vendor = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM vendors WHERE id = $id"));
if (!$vendor) {
    header("Location: vendor.php");
    exit();
}
?>

<?php include '../includes/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1><i class="fas fa-user-edit"></i> Edit Vendor</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h3 class="card-title">Form Edit Vendor</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nama Vendor/Perusahaan <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($vendor['name']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Contact Person <span class="text-danger">*</span></label>
                                    <input type="text" name="contact_person" class="form-control" value="<?= htmlspecialchars($vendor['contact_person']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Telepon <span class="text-danger">*</span></label>
                                    <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($vendor['phone']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($vendor['email']) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Alamat Lengkap <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="3" required><?= htmlspecialchars($vendor['address']) ?></textarea>
                        </div>

                        <div class="row">
                           <div class="col-md-6">
    <div class="form-group">
        <label>NPWP</label>
        <input type="text" name="npwp" class="form-control" value="<?= htmlspecialchars($vendor['npwp'] ?? '') ?>">
    </div>
</div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status <span class="text-danger">*</span></label>
                                    <select name="status" class="form-control" required>
                                        <option value="active" <?= $vendor['status'] == 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $vendor['status'] == 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mt-4">
                            <a href="vendor.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Update Vendor
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>