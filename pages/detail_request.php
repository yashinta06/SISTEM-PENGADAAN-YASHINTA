<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

$page_title = 'Detail Request - UAS Pengadaan';

// Hanya Admin dan Manager yang boleh akses
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header("Location: dashboard.php");
    exit();
}

// Cek apakah ID ada
if (!isset($_GET['id'])) {
    header("Location: approval.php");
    exit();
}

$id = (int)$_GET['id'];

// ==========================================
// PROSES APPROVE / REJECT (HANYA MANAGER)
// ==========================================
if ($_SESSION['user_role'] == 'manager' && isset($_GET['action'])) {
    $action = $_GET['action'];
    $approver_id = $_SESSION['user_id'];

    if ($action == 'approve') {
        mysqli_query($koneksi, "UPDATE purchase_requests SET status = 'approved', approved_by = $approver_id, approved_at = NOW() WHERE id = $id");
        header("Location: approval.php?msg=approved");
        exit();
    } elseif ($action == 'reject') {
        $reason = isset($_GET['reason']) ? mysqli_real_escape_string($koneksi, $_GET['reason']) : 'Tidak ada alasan';
        mysqli_query($koneksi, "UPDATE purchase_requests SET status = 'rejected', rejection_reason = '$reason', approved_by = $approver_id, approved_at = NOW() WHERE id = $id");
        header("Location: approval.php?msg=rejected");
        exit();
    }
}

// ==========================================
// QUERY DATA
// ==========================================
// 1. Ambil Header Request
$req = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT pr.*, u.name as requester_name, u.department 
                                                  FROM purchase_requests pr 
                                                  JOIN users u ON pr.user_id = u.id 
                                                  WHERE pr.id = $id"));
if (!$req) {
    header("Location: approval.php");
    exit();
}

// 2. Ambil Items Barang
$items = mysqli_query($koneksi, "SELECT * FROM purchase_request_items WHERE purchase_request_id = $id");

// 3. Ambil nama Approver (jika sudah di-approve/reject)
$approver_name = '-';
if ($req['approved_by']) {
    $app = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT name FROM users WHERE id = {$req['approved_by']}"));
    $approver_name = $app['name'] ?? '-';
}
?>

<?php include '../includes/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1><i class="fas fa-file-invoice"></i> Detail Request: <?= htmlspecialchars($req['request_number']) ?></h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <!-- Info Header Request -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h3 class="card-title">Informasi Pengajuan</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr><td width="150"><strong>Judul Request</strong></td><td>: <?= htmlspecialchars($req['title']) ?></td></tr>
                                        <tr><td><strong>Pemohon</strong></td><td>: <?= htmlspecialchars($req['requester_name']) ?></td></tr>
                                        <tr><td><strong>Departemen</strong></td><td>: <?= htmlspecialchars($req['department']) ?></td></tr>
                                        <tr><td><strong>Tanggal Dibuat</strong></td><td>: <?= date('d M Y', strtotime($req['request_date'])) ?></td></tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm table-borderless">
                                        <tr><td width="150"><strong>Tanggal Dibutuhkan</strong></td><td>: <span class="badge badge-warning"><?= date('d M Y', strtotime($req['needed_date'])) ?></span></td></tr>
                                        <tr><td><strong>Total Estimasi</strong></td><td>: <strong>Rp <?= number_format($req['total_estimated_price'], 0, ',', '.') ?></strong></td></tr>
                                        <tr><td><strong>Status Saat Ini</strong></td><td>: <span class="badge badge-info"><?= ucfirst($req['status']) ?></span></td></tr>
                                        <tr><td><strong>Di-review Oleh</strong></td><td>: <?= $approver_name ?></td></tr>
                                    </table>
                                </div>
                            </div>
                            
                            <?php if ($req['notes']): ?>
                                <hr>
                                <strong><i class="fas fa-comment-dots"></i> Alasan / Justifikasi Requester:</strong>
                                <p class="bg-light p-3 rounded border"><?= htmlspecialchars($req['notes']) ?></p>
                            <?php endif; ?>

                            <?php if ($req['status'] == 'rejected' && $req['rejection_reason']): ?>
                                <div class="alert alert-danger">
                                    <strong><i class="fas fa-times-circle"></i> Alasan Penolakan:</strong> <?= htmlspecialchars($req['rejection_reason']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Items Barang -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-boxes"></i> Rincian Barang yang Diminta</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Barang</th>
                                            <th>Kategori</th>
                                            <th>Spesifikasi</th>
                                            <th>Qty</th>
                                            <th>Harga Satuan</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        while($item = mysqli_fetch_assoc($items)): 
                                        ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><strong><?= htmlspecialchars($item['item_name']) ?></strong></td>
                                            <td><span class="badge badge-secondary"><?= ucfirst($item['category']) ?></span></td>
                                            <td><small><?= htmlspecialchars($item['specification'] ?? '-') ?></small></td>
                                            <td><?= $item['quantity'] ?> <?= $item['unit'] ?></td>
                                            <td>Rp <?= number_format($item['estimated_price'], 0, ',', '.') ?></td>
                                            <td><strong>Rp <?= number_format($item['total_price'], 0, ',', '.') ?></strong></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi (Hanya untuk Manager & Status Draft/Submitted) -->
            <?php if ($_SESSION['user_role'] == 'manager' && in_array($req['status'], ['draft', 'submitted'])): ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <a href="?action=approve&id=<?= $id ?>" class="btn btn-success btn-lg mr-3" onclick="return confirm('Yakin ingin MENYETUJUI request ini?')">
                                <i class="fas fa-check-circle"></i> Approve Request
                            </a>
                            <button onclick="rejectReq(<?= $id ?>)" class="btn btn-danger btn-lg">
                                <i class="fas fa-times-circle"></i> Reject Request
                            </button>
                            <a href="approval.php" class="btn btn-secondary btn-lg ml-3">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="text-center mb-5">
                <a href="approval.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali ke Approval</a>
            </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>

<script>
function rejectReq(id) {
    var reason = prompt("Masukkan alasan penolakan (Wajib):");
    if (reason != null && reason != "") {
        window.location.href = "?action=reject&id=" + id + "&reason=" + encodeURIComponent(reason);
    } else {
        alert("Alasan penolakan wajib diisi!");
    }
}
</script>