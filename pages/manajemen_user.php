<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

// Hanya admin yang boleh akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$page_title = 'Manajemen User - UAS Pengadaan';

// ==========================================
// PROSES HAPUS USER
// ==========================================
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    if ($id != $_SESSION['user_id']) { // Tidak bisa hapus diri sendiri
        mysqli_query($koneksi, "DELETE FROM users WHERE id = $id");
        header("Location: manajemen_user.php?msg=deleted");
        exit();
    }
}

// ==========================================
// QUERY SEMUA USER
// ==========================================
$query = "SELECT id, name, email, role, department, created_at FROM users ORDER BY id DESC";
$result = mysqli_query($koneksi, $query);
?>

<?php include '../includes/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1><i class="fas fa-users-cog"></i> Manajemen User</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <?php if (isset($_GET['msg'])): ?>
                <?php if($_GET['msg'] == 'deleted'): ?>
                    <div class="alert alert-success">User berhasil dihapus!</div>
                <?php elseif($_GET['msg'] == 'updated'): ?>
                    <div class="alert alert-success">User berhasil diupdate!</div>
                <?php elseif($_GET['msg'] == 'error'): ?>
                    <div class="alert alert-danger">Gagal memproses user!</div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-users"></i> Daftar User</h3>
                    <a href="tambah_user.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-user-plus"></i> Tambah User
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Departemen</th>
                                    <th>Terdaftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while($row = mysqli_fetch_assoc($result)): 
                                    // PERBAIKAN: Handle role NULL dengan fallback
                                    $role = $row['role'] ?? 'requester';
                                    
                                    $badge = 'secondary';
                                    if($role == 'admin') $badge = 'danger';
                                    if($role == 'manager') $badge = 'warning';
                                    if($role == 'purchasing') $badge = 'info';
                                    if($role == 'warehouse') $badge = 'success';
                                    if($role == 'requester') $badge = 'primary';
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td><span class="badge badge-<?= $badge ?>"><?= ucfirst($role) ?></span></td>
                                    <td><?= htmlspecialchars($row['department'] ?? '-') ?></td>
                                    <td><?= !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : '-' ?></td>
                                    <td>
                                        <a href="edit_user.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm" title="Edit User">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <?php if($row['id'] != $_SESSION['user_id']): ?>
                                            <a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus user ini?')" title="Hapus User">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted" title="Tidak bisa hapus diri sendiri"><i class="fas fa-ban"></i></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($result) == 0): ?>
                                    <tr><td colspan="7" class="text-center text-muted">Belum ada user terdaftar.</td></tr>
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