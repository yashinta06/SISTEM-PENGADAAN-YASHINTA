<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

if ($_SESSION['user_role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

$page_title = 'Tambah Barang - UAS Pengadaan';

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

    $query = "INSERT INTO barang (kode_barang, nama_barang, kategori, spesifikasi, satuan, harga_terakhir, stok, min_stok, status) 
              VALUES ('$kode', '$nama', '$kategori', '$spesifikasi', '$satuan', $harga, $stok, $min_stok, '$status')";
    
    if (mysqli_query($koneksi, $query)) {
        header("Location: master_barang.php?msg=added");
        exit();
    } else {
        $error = "Gagal menyimpan: " . mysqli_error($koneksi);
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1>Tambah Barang Baru</h1>
        </div>
    </div>
    
    <section class="content">
        <div class="container-fluid">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Form Data Barang</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="formBarang" autocomplete="off">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Kode Barang <small class="text-muted">(Otomatis terisi saat pilih kategori)</small></label>
                                    <input type="text" name="kode_barang" id="kode_barang" class="form-control" placeholder="Akan terisi otomatis..." readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Nama Barang</label>
                                    <input type="text" name="nama_barang" class="form-control" placeholder="Nama lengkap barang" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Kategori</label>
                                    <select name="kategori" id="kategori" class="form-control" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <option value="hardware">Hardware</option>
                                        <option value="software">Software</option>
                                        <option value="network">Network</option>
                                        <option value="atk">ATK</option>
                                        <option value="furniture">Furniture</option>
                                        <option value="others">Others</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Satuan</label>
                                    <input type="text" name="satuan" class="form-control" placeholder="pcs, unit, rim" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Spesifikasi</label>
                            <textarea name="spesifikasi" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Harga Terakhir</label>
                                    <input type="number" name="harga" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Stok Saat Ini</label>
                                    <input type="number" name="stok" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Min. Stok</label>
                                    <input type="number" name="min_stok" class="form-control" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        <a href="master_barang.php" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    // Clear form saat halaman dibuka
    var form = document.getElementById('formBarang');
    if (form) {
        form.reset();
    }
    document.getElementById('kode_barang').value = '';
    
    // Event listener untuk kategori
    var kategoriSelect = document.getElementById('kategori');
    if (kategoriSelect) {
        kategoriSelect.addEventListener('change', function() {
            var kategori = this.value;
            var kodeField = document.getElementById('kode_barang');
            
            console.log('Kategori dipilih:', kategori);
            
            if (kategori) {
                // Generate kode secara client-side (tanpa AJAX)
                var prefix = kategori.substring(0, 3).toUpperCase();
                
                // Fetch kode terakhir via AJAX
                fetch('generate_kode_barang.php?kategori=' + encodeURIComponent(kategori))
                    .then(response => {
                        console.log('Response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Response data:', data);
                        if (data.success) {
                            kodeField.value = data.kode_baru;
                        } else {
                            // Fallback: generate manual
                            var kodeManual = prefix + '-001';
                            kodeField.value = kodeManual;
                            console.log('Using fallback code:', kodeManual);
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        // Fallback jika error
                        var prefix = kategori.substring(0, 3).toUpperCase();
                        var kodeManual = prefix + '-001';
                        kodeField.value = kodeManual;
                        console.log('Using manual fallback:', kodeManual);
                    });
            } else {
                kodeField.value = '';
            }
        });
    }
});
</script>
