<?php
/** @var mysqli $koneksi */
session_start();
require_once '../koneksi.php';

$page_title = 'Tambah Request - UAS Pengadaan';
$error = '';

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'requester'])) {
    header("Location: ../auth/login.php");
    exit();
}

// Ambil semua barang aktif
$barang_query = mysqli_query($koneksi, "SELECT * FROM barang WHERE status = 'active' ORDER BY nama_barang ASC");

// Siapkan array untuk JavaScript
$barang_list = [];
while($b = mysqli_fetch_assoc($barang_query)) {
    $barang_list[] = $b;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $needed_date = $_POST['needed_date'];
    $barang_kode = mysqli_real_escape_string($koneksi, $_POST['barang_kode']);
    $qty = (int)$_POST['qty'];
    $alasan = mysqli_real_escape_string($koneksi, $_POST['alasan']);
    $user_id = $_SESSION['user_id'];
    $dept = $_SESSION['user_department'] ?? '';
    
    if (empty($barang_kode)) {
        $error = "Pilih barang dulu!";
    } else {
        $b = mysqli_query($koneksi, "SELECT * FROM barang WHERE kode_barang = '$barang_kode'");
        $brg = mysqli_fetch_assoc($b);
        
        if ($brg) {
            $harga = $brg['harga_terakhir'];
            $total = $harga * $qty;
            $rand = rand(100, 999);
            $req_num = "PR-" . date('Y') . "-" . $rand;
            
            $q1 = "INSERT INTO purchase_requests (user_id, request_number, title, department, request_date, needed_date, total_estimated_price, status, notes) 
                   VALUES ($user_id, '$req_num', '$judul', '$dept', CURDATE(), '$needed_date', $total, 'draft', '$alasan')";
            
            if (mysqli_query($koneksi, $q1)) {
                $last_id = mysqli_insert_id($koneksi);
                
                $q2 = "INSERT INTO purchase_request_items (purchase_request_id, barang_id, item_name, category, specification, quantity, unit, estimated_price, total_price, justification) 
                       VALUES ($last_id, {$brg['id']}, '$brg[nama_barang]', '$brg[kategori]', '$brg[spesifikasi]', $qty, '$brg[satuan]', $harga, $total, '$alasan')";
                
                if (mysqli_query($koneksi, $q2)) {
                    echo "<script>alert('✅ Request berhasil dibuat!'); window.location='purchase_request.php?msg=success';</script>";
                    exit();
                }
            }
            $error = "Gagal: " . mysqli_error($koneksi);
        } else {
            $error = "Barang tidak ditemukan!";
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1>Buat Purchase Request Baru</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h3 class="card-title">Form Pengajuan Barang</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Judul Request <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control" placeholder="Contoh: Pengadaan Laptop Baru" required>
                        </div>
                        <div class="form-group">
                            <label>Tanggal Dibutuhkan <span class="text-danger">*</span></label>
                            <input type="date" name="needed_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Pilih Barang <small class="text-muted">(Ketik untuk mencari)</small> <span class="text-danger">*</span></label>
                                    <input type="text" list="barang_list" id="barang_input" class="form-control" placeholder="Ketik nama barang untuk mencari..." onchange="onBarangSelect()" required>
                                    <datalist id="barang_list">
                                        <?php foreach($barang_list as $b): ?>
                                            <option value="<?= $b['kode_barang'] ?>" data-nama="<?= htmlspecialchars($b['nama_barang']) ?>" data-harga="<?= $b['harga_terakhir'] ?>" data-satuan="<?= $b['satuan'] ?>">
                                                [<?= $b['kode_barang'] ?>] <?= $b['nama_barang'] ?> - Rp <?= number_format($b['harga_terakhir'], 0, ',', '.') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </datalist>
                                    <input type="hidden" name="barang_kode" id="barang_kode">
                                    <small class="form-text text-muted" id="status_barang">Ketik untuk mencari barang</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Jumlah (Qty) <span class="text-danger">*</span></label>
                                    <input type="number" name="qty" id="qty" class="form-control" min="1" value="1" onchange="hitungTotal()" required>
                                </div>
                            </div>
                        </div>
                        
                        <div id="info_barang" class="alert alert-info" style="display:none;">
                            <h6 class="mb-2"><i class="fas fa-box"></i> Detail Barang:</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Nama:</strong><br>
                                    <span id="info_nama">-</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Harga Satuan:</strong><br>
                                    Rp <span id="info_harga">-</span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Total:</strong><br>
                                    <span class="text-primary font-weight-bold">Rp <span id="info_total">-</span></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Alasan / Justifikasi <span class="text-danger">*</span></label>
                            <textarea name="alasan" class="form-control" rows="3" placeholder="Jelaskan alasan pengadaan barang ini..." required></textarea>
                        </div>
                        
                        <hr>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Simpan Request
                        </button>
                        <a href="purchase_request.php" class="btn btn-secondary btn-lg">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
// Data barang dari PHP
const barangData = <?= json_encode($barang_list) ?>;

let selectedBarang = null;

function onBarangSelect() {
    const input = document.getElementById('barang_input');
    const kode = input.value;
    const statusEl = document.getElementById('status_barang');
    
    // Cari barang berdasarkan kode atau nama
    const barang = barangData.find(b => b.kode_barang === kode || b.nama_barang.toLowerCase().includes(kode.toLowerCase()));
    
    if (barang) {
        selectedBarang = barang;
        document.getElementById('barang_kode').value = barang.kode_barang;
        input.value = '[' + barang.kode_barang + '] ' + barang.nama_barang;
        statusEl.textContent = '✓ Barang terpilih: ' + barang.nama_barang;
        statusEl.className = 'form-text text-success';
        
        // Tampilkan info
        document.getElementById('info_nama').textContent = barang.nama_barang;
        document.getElementById('info_harga').textContent = formatRupiah(barang.harga_terakhir);
        hitungTotal();
        document.getElementById('info_barang').style.display = 'block';
    } else {
        selectedBarang = null;
        document.getElementById('barang_kode').value = '';
        statusEl.textContent = '✗ Barang tidak ditemukan';
        statusEl.className = 'form-text text-danger';
        document.getElementById('info_barang').style.display = 'none';
    }
}

function hitungTotal() {
    if (!selectedBarang) return;
    
    const qty = parseInt(document.getElementById('qty').value) || 1;
    const total = selectedBarang.harga_terakhir * qty;
    
    document.getElementById('info_total').textContent = formatRupiah(total);
}

function formatRupiah(angka) {
    return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Filter datalist saat mengetik
document.getElementById('barang_input').addEventListener('input', function(e) {
    const val = e.target.value.toLowerCase();
    const datalist = document.getElementById('barang_list');
    
    // Show all options when typing
    datalist.innerHTML = '';
    barangData.forEach(function(b) {
        if (val === '' || b.nama_barang.toLowerCase().includes(val) || b.kode_barang.toLowerCase().includes(val)) {
            const option = document.createElement('option');
            option.value = b.kode_barang;
            option.textContent = '[' + b.kode_barang + '] ' + b.nama_barang + ' - Rp ' + formatRupiah(b.harga_terakhir);
            datalist.appendChild(option);
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>