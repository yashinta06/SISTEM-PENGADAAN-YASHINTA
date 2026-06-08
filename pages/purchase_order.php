<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

$page_title = 'Purchase Order - UAS Pengadaan';

// Hanya Admin dan Purchasing yang boleh akses
if (!in_array($_SESSION['user_role'], ['admin', 'purchasing'])) {
    header("Location: dashboard.php");
    exit();
}

// ==========================================
// 1. PROSES BUAT PO BARU
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'create_po' && isset($_GET['id'])) {
    $req_id = (int)$_GET['id'];
    $user_id = $_SESSION['user_id'];
    
    $po_number = "PO-" . date('Y') . "-" . rand(1000, 9999);
    $order_date = date('Y-m-d');
    $expected_date = date('Y-m-d', strtotime('+7 days'));
    
    $req_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT total_estimated_price FROM purchase_requests WHERE id = $req_id"));
    $total_price = $req_data['total_estimated_price'];
    $grand_total = $total_price;

    $sql_po = "INSERT INTO purchase_orders 
               (po_number, purchase_request_id, vendor_id, purchasing_staff_id, order_date, expected_delivery_date, total_price, grand_total, status) 
               VALUES 
               ('$po_number', $req_id, 1, $user_id, '$order_date', '$expected_date', $total_price, $grand_total, 'pending')";
    
    if (mysqli_query($koneksi, $sql_po)) {
        mysqli_query($koneksi, "UPDATE purchase_requests SET status = 'ordered' WHERE id = $req_id");
        header("Location: purchase_order.php?msg=success");
        exit();
    } else {
        header("Location: purchase_order.php?msg=error&err=" . mysqli_error($koneksi));
        exit();
    }
}

// ==========================================
// 2. PROSES KIRIM BARANG (SHIPPED) - BARU!
// ==========================================
if (isset($_GET['action']) && $_GET['action'] == 'shipped' && isset($_GET['id'])) {
    $po_id = (int)$_GET['id'];
    
    // Update status PO menjadi 'shipped'
    $sql = "UPDATE purchase_orders SET status = 'shipped', shipped_at = NOW() WHERE id = $po_id";
    
    if (mysqli_query($koneksi, $sql)) {
        header("Location: purchase_order.php?msg=shipped");
        exit();
    } else {
        header("Location: purchase_order.php?msg=error_shipped");
        exit();
    }
}

// ==========================================
// 3. QUERY DATA
// ==========================================
// Tampilkan request yang approved/ordered dan JOIN dengan PO untuk lihat status pengiriman
$query = "SELECT pr.*, u.name AS requester_name, po.id AS po_id, po.po_number, po.order_date, po.status as po_status, po.expected_delivery_date
          FROM purchase_requests pr 
          JOIN users u ON pr.user_id = u.id 
          LEFT JOIN purchase_orders po ON pr.id = po.purchase_request_id
          WHERE pr.status IN ('approved', 'ordered')
          ORDER BY pr.id DESC";
$result = mysqli_query($koneksi, $query);
?>

<?php include '../includes/header.php'; ?>

<style>
    .btn-shipped { background-color: #17a2b8 !important; border-color: #17a2b8 !important; color: white; }
    .btn-shipped:hover { background-color: #138496 !important; color: white; }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1><i class="fas fa-shopping-cart"></i> Purchase Order</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <!-- Notifikasi -->
            <?php if (isset($_GET['msg'])): ?>
                <?php if($_GET['msg'] == 'success'): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> Purchase Order berhasil dibuat! Menunggu vendor mengirim barang.
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php elseif($_GET['msg'] == 'shipped'): ?>
                    <div class="alert alert-info alert-dismissible fade show">
                        <i class="fas fa-truck"></i> Status PO berhasil diubah menjadi <strong>Shipped</strong> (Barang sedang dalam perjalanan).
                        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                    </div>
                <?php elseif($_GET['msg'] == 'error' || $_GET['msg'] == 'error_shipped'): ?>
                    <div class="alert alert-danger">Gagal memproses Purchase Order.</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h3 class="card-title"><i class="fas fa-file-invoice"></i> Daftar Order Pengadaan</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No. Request</th>
                                    <th>Judul Request</th>
                                    <th>Pemohon</th>
                                    <th>No. PO</th>
                                    <th>Tanggal Order</th>
                                    <th>Total Harga</th>
                                    <th>Status PO</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while($row = mysqli_fetch_assoc($result)): 
                                    $badge_req = 'secondary';
                                    if($row['status'] == 'approved') $badge_req = 'success';
                                    if($row['status'] == 'ordered') $badge_req = 'primary';
                                    
                                    $po_status = $row['po_status'] ?? '-';
                                    $badge_po = 'secondary';
                                    if($po_status == 'pending') $badge_po = 'warning';
                                    if($po_status == 'shipped') $badge_po = 'info';
                                    if($po_status == 'received') $badge_po = 'success';
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($row['request_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['requester_name']) ?></td>
                                    <td>
                                        <?php if($row['po_number']): ?>
                                            <span class="badge badge-dark"><?= $row['po_number'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $row['order_date'] ? date('d M Y', strtotime($row['order_date'])) : '-' ?></td>
                                    <td>Rp <?= number_format($row['total_estimated_price'], 0, ',', '.') ?></td>
                                    <td>
                                        <?php if($row['po_status']): ?>
                                            <span class="badge badge-<?= $badge_po ?>"><?= ucfirst($po_status) ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-<?= $badge_req ?>"><?= ucfirst($row['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == 'approved' && !$row['po_number']): ?>
                                            <!-- Belum ada PO, tombol Buat PO -->
                                            <a href="?action=create_po&id=<?= $row['id'] ?>" class="btn btn-primary btn-sm" onclick="return confirm('Proses request ini menjadi Purchase Order?')">
                                                <i class="fas fa-cart-plus"></i> Buat PO
                                            </a>
                                        <?php elseif ($row['po_status'] == 'pending'): ?>
                                            <!-- PO Sudah dibuat, status pending. Tombol Kirim Barang -->
                                            <a href="?action=shipped&id=<?= $row['po_id'] ?>" class="btn btn-shipped btn-sm" onclick="return confirm('Konfirmasi: Apakah Vendor sudah mengirim barang ini?')">
                                                <i class="fas fa-truck"></i> Kirim (Shipped)
                                            </a>
                                        <?php elseif ($row['po_status'] == 'shipped'): ?>
                                            <!-- Barang sedang dikirim, menunggu Gudang menerima -->
                                            <span class="text-info"><i class="fas fa-truck-moving"></i> Dalam Perjalanan</span>
                                        <?php elseif ($row['po_status'] == 'received'): ?>
                                            <!-- Barang sudah diterima Gudang -->
                                            <span class="text-success"><i class="fas fa-check-circle"></i> Diterima Gudang</span>
                                        <?php else: ?>
                                            <a href="detail_request.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i> Lihat
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($result) == 0): ?>
                                    <tr><td colspan="9" class="text-center text-muted py-4">Belum ada request yang di-approve.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Legenda Status -->
            <div class="card mt-4">
                <div class="card-header"><h5 class="card-title"><i class="fas fa-info-circle"></i> Alur Status PO</h5></div>
                <div class="card-body">
                    <p class="mb-0">
                        <span class="badge badge-warning">Pending</span> PO dibuat, menunggu Vendor mengirim. ➡️ 
                        <span class="badge badge-info">Shipped</span> Vendor sedang mengirim barang. ➡️ 
                        <span class="badge badge-success">Received</span> Gudang sudah menerima barang.
                    </p>
                </div>
            </div>

        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>