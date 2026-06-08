<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

if ($_SESSION['user_role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

$page_title = 'Edit Barang - UAS Pengadaan';

if (!isset($_GET['id'])) {
    header("Location: master_barang.php");
    exit();
}

$id = (int)$_GET['id'];
$barang = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM barang WHERE id = $id"));

if (!$barang) {
    header("Location: master_barang.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode = mysqli_real_escape_string($koneksi, $_POST['kode_barang']);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama_barang']);
    $kategori = $_POST['kategori'];
    $spesifikasi = mysqli_real_escape_string($koneksi, $_POST['spesifikasi']);
    $satuan = mysqli_real_escape_string($koneksi, $_POST['satuan']);
    $harga = (int)$_POST['harga'];
    $stok = (int)$_POST['stok'];
    $min_stok = (int)$_POST['min_stok'];
    $status = $_POST['status'];

    $query = "UPDATE barang SET kode_barang='$kode', nama_barang='$nama', kategori='$kategori', spesifikasi='$spesifikasi', satuan='$satuan', harga_terakhir=$harga, stok=$stok, min_stok=$min_stok, status='$status' WHERE id=$id";
    
    if (mysqli_query($koneksi, $query)) {
        header("Location: master_barang.php");
        exit();
    } else {
        $error = "Gagal mengupdate: " . mysqli_error($koneksi);
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1>Edit Barang</h1>
        </div>
    </div>
    
    <section class="content">
        <div class="container-fluid">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Update Data Barang: <?= htmlspecialchars($barang['nama_barang']) ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Kode Barang</label>
                                    <input type="text" name="kode_barang" class="form-control" value="<?= htmlspecialchars($barang['kode_barang']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nama Barang</label>
                                    <input type="text" name="nama_barang" class="form-control" value="<?= htmlspecialchars($barang['nama_barang']) ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Kategori</label>
                                    <select name="kategori" class="form-control" required>
                                        <?php 
                                        $kats = ['hardware','software','network','atk','furniture','others'];
                                        foreach($kats as $k): 
                                            $sel = ($k == $barang['kategori']) ? 'selected' : '';
                                        ?>
                                            <option value="<?= $k ?>" <?= $sel ?>><?= ucfirst($k) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Satuan</label>
                                    <input type="text" name="satuan" class="form-control" value="<?= htmlspecialchars($barang['satuan']) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active" <?= ($barang['status']=='active')?'selected':'' ?>>Active</option>
                                        <option value="inactive" <?= ($barang['status']=='inactive')?'selected':'' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Spesifikasi</label>
                            <textarea name="spesifikasi" class="form-control" rows="3"><?= htmlspecialchars($barang['spesifikasi']) ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Harga Terakhir</label>
                                    <input type="number" name="harga" class="form-control" value="<?= $barang['harga_terakhir'] ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Stok Saat Ini</label>
                                    <input type="number" name="stok" class="form-control" value="<?= $barang['stok'] ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Min. Stok</label>
                                    <input type="number" name="min_stok" class="form-control" value="<?= $barang['min_stok'] ?>" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Update
                        </button>
                        <a href="master_barang.php" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>