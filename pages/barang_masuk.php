<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

$page_title = 'Barang Masuk - UAS Pengadaan';

// Hanya Admin dan Warehouse yang boleh akses
if (!in_array($_SESSION['user_role'], ['admin', 'warehouse'])) {
    header("Location: dashboard.php");
    exit();
}

// Proses Terima Barang + Auto Update Stok
if (isset($_GET['action']) && $_GET['action'] == 'receive' && isset($_GET['id'])) {
    $po_id = (int)$_GET['id'];
    $actual_date = date('Y-m-d');
    
    // 1. Update status PO menjadi 'received'
    $sql = "UPDATE purchase_orders SET status = 'received', actual_delivery_date = '$actual_date' WHERE id = $po_id";
    
    if (mysqli_query($koneksi, $sql)) {
        // 2. Update status request menjadi 'completed'
        mysqli_query($koneksi, "UPDATE purchase_requests pr 
                               JOIN purchase_orders po ON pr.id = po.purchase_request_id 
                               SET pr.status = 'completed' 
                               WHERE po.id = $po_id");
        
        // 3. AUTO UPDATE STOK BARANG (FITUR BARU)
        // Ambil ID Request dari PO ini
        $req_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT purchase_request_id FROM purchase_orders WHERE id = $po_id"));
        $req_id = $req_data['purchase_request_id'];
        
        // Ambil semua item barang yang ada di request tersebut
        $items = mysqli_query($koneksi, "SELECT barang_id, quantity FROM purchase_request_items WHERE purchase_request_id = $req_id");
        
        // Looping setiap item dan tambahkan stoknya
        while($item = mysqli_fetch_assoc($items)) {
            $barang_id = $item['barang_id'];
            $qty = $item['quantity'];
            
            // Update stok di tabel barang (Tambah stok)
            mysqli_query($koneksi, "UPDATE barang SET stok = stok + $qty WHERE id = $barang_id");
        }
        
        header("Location: barang_masuk.php?msg=success");
        exit();
    } else {
        header("Location: barang_masuk.php?msg=error");
        exit();
    }
}

// Ambil data PO yang statusnya 'pending' atau 'shipped' (belum diterima)
$query = "SELECT po.*, pr.title, pr.request_number, v.name as vendor_name, u.name as staff_name
          FROM purchase_orders po
          JOIN purchase_requests pr ON po.purchase_request_id = pr.id
          LEFT JOIN vendors v ON po.vendor_id = v.id
          LEFT JOIN users u ON po.purchasing_staff_id = u.id
          WHERE po.status IN ('pending', 'shipped')
          ORDER BY po.id DESC";
$result = mysqli_query($koneksi, $query);
?>

<?php include '../includes/header.php'; ?>

<!-- CSS Custom untuk tombol Terima Barang -->
<style>
    .btn-success { background-color: #000000 !important; border-color: #000000 !important; }
    .btn-success:hover { background-color: #ffffff !important; color: #000000 !important; }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1>Barang Masuk</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <?php if (isset($_GET['msg'])): ?>
                <?php if($_GET['msg'] == 'success'): ?>
                    <div class="alert alert-success">Barang berhasil diterima dan <strong>stok otomatis diperbarui!</strong></div>
                <?php elseif($_GET['msg'] == 'error'): ?>
                    <div class="alert alert-danger">Gagal memproses penerimaan barang.</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-truck-loading"></i> Daftar Pengiriman Barang</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>No. PO</th>
                                    <th>Judul Request</th>
                                    <th>Vendor</th>
                                    <th>Tanggal Order</th>
                                    <th>Estimasi Tiba</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while($row = mysqli_fetch_assoc($result)): 
                                    $badge = 'warning';
                                    if($row['status'] == 'shipped') $badge = 'info';
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($row['po_number']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['vendor_name'] ?? '-') ?></td>
                                    <td><?= date('d M Y', strtotime($row['order_date'])) ?></td>
                                    <td><?= $row['expected_delivery_date'] ? date('d M Y', strtotime($row['expected_delivery_date'])) : '-' ?></td>
                                    <td><span class="badge badge-<?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
                                    <td>
                                        <a href="?action=receive&id=<?= $row['id'] ?>" class="btn btn-success btn-sm" onclick="return confirm('Konfirmasi: Barang sudah diterima dengan baik? Stok akan otomatis bertambah.')">
                                            <i class="fas fa-check"></i> Terima Barang
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($result) == 0): ?>
                                    <tr><td colspan="8" class="text-center">Tidak ada pengiriman yang menunggu.</td></tr>
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