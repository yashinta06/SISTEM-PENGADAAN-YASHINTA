<?php
session_start();
/** @var mysqli $koneksi */
require_once '../koneksi.php';

$page_title = 'Purchase Request - UAS Pengadaan';

// Cek role
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['admin', 'requester'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_dept = $_SESSION['user_department'] ?? '';

// ==========================================
// PROSES SUBMIT REQUEST (jika ada)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_request'])) {
    $judul = mysqli_real_escape_string($koneksi, $_POST['judul']);
    $needed_date = $_POST['needed_date'];
    $barang_id = (int)$_POST['barang_id'];
    $qty = (int)$_POST['qty'];
    $alasan = mysqli_real_escape_string($koneksi, $_POST['alasan']);
    
    // Ambil data barang
    $b = mysqli_query($koneksi, "SELECT * FROM barang WHERE id = $barang_id");
    $brg = mysqli_fetch_assoc($b);
    
    if ($brg) {
        $harga = $brg['harga_terakhir'];
        $total = $harga * $qty;
        $rand = rand(100, 999);
        $req_num = "PR-" . date('Y') . "-" . $rand;
        
        // Insert ke header
        $q1 = "INSERT INTO purchase_requests (user_id, request_number, title, department, request_date, needed_date, total_estimated_price, status, notes) 
               VALUES ($user_id, '$req_num', '$judul', '$user_dept', CURDATE(), '$needed_date', $total, 'draft', '$alasan')";
        
        if (mysqli_query($koneksi, $q1)) {
            $last_id = mysqli_insert_id($koneksi);
            
            // Insert ke items
            $q2 = "INSERT INTO purchase_request_items (purchase_request_id, barang_id, item_name, category, specification, quantity, unit, estimated_price, total_price, justification) 
                   VALUES ($last_id, $barang_id, '$brg[nama_barang]', '$brg[kategori]', '$brg[spesifikasi]', $qty, '$brg[satuan]', $harga, $total, '$alasan')";
            
            mysqli_query($koneksi, $q2);
            header("Location: purchase_request.php?msg=success");
            exit();
        }
    }
}

// ==========================================
// QUERY REQUEST USER
// ==========================================
if ($user_role == 'admin') {
    // Admin lihat semua request
    $where = "1=1";
} else {
    // Requester hanya lihat request sendiri
    $where = "pr.user_id = $user_id";
}

$query = "SELECT pr.*, u.name as user_name, 
          (SELECT COUNT(*) FROM purchase_request_items WHERE purchase_request_id = pr.id) as total_items
          FROM purchase_requests pr
          LEFT JOIN users u ON pr.user_id = u.id
          WHERE $where
          ORDER BY pr.request_date DESC";

$result = mysqli_query($koneksi, $query);

// Hitung statistik
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft,
                SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
                FROM purchase_requests
                WHERE " . ($user_role == 'admin' ? "1=1" : "user_id = $user_id");
$stats = mysqli_fetch_assoc(mysqli_query($koneksi, $stats_query));
?>

<?php include '../includes/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1><i class="fas fa-file-alt"></i> Purchase Request</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <!-- Success Message -->
            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <strong>Berhasil!</strong> Purchase Request berhasil dibuat dan menunggu approval.
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="info-box bg-primary">
                        <span class="info-box-icon"><i class="fas fa-file-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Request</span>
                            <span class="info-box-number"><?= $stats['total'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-warning">
                        <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Draft</span>
                            <span class="info-box-number"><?= $stats['draft'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-info">
                        <span class="info-box-icon"><i class="fas fa-paper-plane"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Submitted</span>
                            <span class="info-box-number"><?= $stats['submitted'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fas fa-check"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Approved</span>
                            <span class="info-box-number"><?= $stats['approved'] ?? 0 ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Button -->
            <div class="mb-3">
                <a href="tambah_request.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Buat Request Baru
                </a>
            </div>

            <!-- Request List -->
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h3 class="card-title mb-0"><i class="fas fa-list"></i> Daftar Purchase Request</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No. Request</th>
                                    <th>Judul</th>
                                    <th>Tanggal</th>
                                    <th>Items</th>
                                    <th>Total Harga</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while($row = mysqli_fetch_assoc($result)): 
                                    $badge = 'secondary';
                                    if($row['status'] == 'draft') $badge = 'warning';
                                    if($row['status'] == 'submitted') $badge = 'info';
                                    if($row['status'] == 'approved') $badge = 'success';
                                    if($row['status'] == 'rejected') $badge = 'danger';
                                    if($row['status'] == 'ordered') $badge = 'primary';
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($row['request_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= date('d M Y', strtotime($row['request_date'])) ?></td>
                                    <td><?= $row['total_items'] ?> item</td>
                                    <td>Rp <?= number_format($row['total_estimated_price'], 0, ',', '.') ?></td>
                                    <td><span class="badge badge-<?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
                                    <td>
                                        <a href="detail_request.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if($row['status'] == 'draft'): ?>
                                            <a href="edit_request.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus request ini?')" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($result) == 0): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-2"></i><br>
                                        Belum ada purchase request.
                                    </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>