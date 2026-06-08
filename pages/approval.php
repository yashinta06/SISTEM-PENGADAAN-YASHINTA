<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

$page_title = 'Approval - UAS Pengadaan';

// Hanya Manager yang boleh akses halaman ini
if ($_SESSION['user_role'] != 'manager') {
    // Admin dan role lain tetap bisa akses tapi read-only
}

$is_manager = ($_SESSION['user_role'] == 'manager');

// Proses Approve/Reject (HANYA untuk Manager)
if ($is_manager && isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    $approver_id = $_SESSION['user_id'];

    if ($action == 'approve') {
        $sql = "UPDATE purchase_requests SET status = 'approved', approved_by = $approver_id, approved_at = NOW() WHERE id = $id";
        mysqli_query($koneksi, $sql);
        header("Location: approval.php?msg=approved");
        exit();
    } elseif ($action == 'reject') {
        $reason = isset($_GET['reason']) ? mysqli_real_escape_string($koneksi, $_GET['reason']) : 'Tidak ada alasan';
        $sql = "UPDATE purchase_requests SET status = 'rejected', rejection_reason = '$reason', approved_by = $approver_id, approved_at = NOW() WHERE id = $id";
        mysqli_query($koneksi, $sql);
        header("Location: approval.php?msg=rejected");
        exit();
    }
}

// Ambil data request
$query = "SELECT pr.*, u.name AS requester_name 
          FROM purchase_requests pr 
          JOIN users u ON pr.user_id = u.id 
          ORDER BY pr.id DESC";
$result = mysqli_query($koneksi, $query);
?>

<?php include '../includes/header.php'; ?>

<!-- CSS Custom untuk tombol Approve/Reject -->
<style>
    .btn-approve { background-color: #000000 !important; border-color: #000000 !important; color: #fff; }
    .btn-approve:hover { background-color: #ffffff !important; color: #000000 !important; }
    .btn-reject { background-color: #dc3545 !important; color: #fff; }
    .btn-reject:hover { background-color: #c82333 !important; color: #fff; }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1>Approval Purchase Request</h1>
            <?php if (!$is_manager): ?>
                <p class="text-muted"><i class="fas fa-info-circle"></i> Mode Monitoring (Read Only)</p>
            <?php endif; ?>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_GET['msg']) && $is_manager): ?>
                <?php if($_GET['msg'] == 'approved'): ?>
                    <div class="alert alert-success">Request berhasil disetujui!</div>
                <?php elseif($_GET['msg'] == 'rejected'): ?>
                    <div class="alert alert-danger">Request berhasil ditolak!</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-6">
                            <h3 class="card-title"><i class="fas fa-clipboard-check"></i> Daftar Pengajuan</h3>
                        </div>
                        <div class="col-md-6 text-right">
                            <?php if ($is_manager): ?>
                                <span class="badge badge-warning">Manager Mode</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Monitoring Mode</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No. Request</th>
                                    <th>Judul</th>
                                    <th>Pemohon</th>
                                    <th>Total Estimasi</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while($row = mysqli_fetch_assoc($result)): 
                                    $badge = 'secondary';
                                    if($row['status'] == 'submitted' || $row['status'] == 'draft') $badge = 'warning';
                                    if($row['status'] == 'approved') $badge = 'success';
                                    if($row['status'] == 'rejected') $badge = 'danger';
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($row['request_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['requester_name']) ?></td>
                                    <td>Rp <?= number_format($row['total_estimated_price'], 0, ',', '.') ?></td>
                                    <td><span class="badge badge-<?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
<td>
    <a href="detail_request.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm mr-1" title="Lihat Detail Barang">
        <i class="fas fa-eye"></i> Detail
    </a>
    <?php if ($is_manager && ($row['status'] == 'draft' || $row['status'] == 'submitted')): ?>
        <a href="?action=approve&id=<?= $row['id'] ?>" class="btn btn-approve btn-sm mr-1" onclick="return confirm('Yakin ingin menyetujui request ini?')">
            <i class="fas fa-check"></i> Approve
        </a>
        <a href="javascript:void(0);" onclick="rejectRequest(<?= $row['id'] ?>)" class="btn btn-reject btn-sm">
            <i class="fas fa-times"></i> Reject
        </a>
    <?php else: ?>
        <?php if (!$is_manager): ?>
            <span class="text-muted"><i class="fas fa-eye"></i> View Only</span>
        <?php else: ?>
            <span class="text-muted">Selesai</span>
        <?php endif; ?>
    <?php endif; ?>
</td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($result) == 0): ?>
                                    <tr><td colspan="7" class="text-center">Tidak ada data request.</td></tr>
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

<!-- Script untuk Reject dengan Reason -->
<script>
function rejectRequest(id) {
    var reason = prompt("Masukkan alasan penolakan:");
    if (reason != null && reason != "") {
        window.location.href = "?action=reject&id=" + id + "&reason=" + encodeURIComponent(reason);
    }
}
</script>