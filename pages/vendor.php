<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

// Hanya admin dan purchasing yang boleh akses
if (!in_array($_SESSION['user_role'], ['admin', 'purchasing'])) {
    header("Location: ../auth/login.php");
    exit();
}

$page_title = 'Manajemen Vendor - UAS Pengadaan';

// Proses hapus vendor
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM vendors WHERE id = $id");
    header("Location: vendor.php?msg=deleted");
    exit();
}

// Query semua vendor
$query = "SELECT * FROM vendors ORDER BY created_at DESC";
$result = mysqli_query($koneksi, $query);
?>

<?php include '../includes/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1><i class="fas fa-truck"></i> Manajemen Vendor</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <?php if (isset($_GET['msg'])): ?>
                <?php if($_GET['msg'] == 'deleted'): ?>
                    <div class="alert alert-success">Vendor berhasil dihapus!</div>
                <?php elseif($_GET['msg'] == 'added'): ?>
                    <div class="alert alert-success">Vendor berhasil ditambahkan!</div>
                <?php elseif($_GET['msg'] == 'updated'): ?>
                    <div class="alert alert-success">Vendor berhasil diupdate!</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-list"></i> Daftar Vendor</h3>
                    <a href="tambah_vendor.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Tambah Vendor
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Vendor</th>
                                    <th>Contact Person</th>
                                    <th>Telepon</th>
                                    <th>Email</th>
                                    <th>Alamat</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while($row = mysqli_fetch_assoc($result)): 
                                    $badge = $row['status'] == 'active' ? 'success' : 'secondary';
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['contact_person']) ?></td>
                                    <td><?= htmlspecialchars($row['phone']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><?= htmlspecialchars($row['address']) ?></td>
                                    <td><span class="badge badge-<?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
                                    <td>
                                        <a href="edit_vendor.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm" title="Edit Vendor">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus vendor ini?')" title="Hapus Vendor">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($result) == 0): ?>
                                    <tr><td colspan="8" class="text-center text-muted">Belum ada vendor terdaftar.</td></tr>
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