<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$page_title = 'Master Barang - UAS Pengadaan';

// ==========================================
// PROSES HAPUS BARANG
// ==========================================
if (isset($_GET['hapus'])) {
    $id = (int)$_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM barang WHERE id = $id");
    header("Location: master_barang.php?msg=deleted");
    exit();
}

// ==========================================
// FILTER & PENCARIAN
// ==========================================
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query WHERE clause
$where_clause = [];
if ($kategori_filter) {
    $kategori_filter = mysqli_real_escape_string($koneksi, $kategori_filter);
    $where_clause[] = "kategori = '$kategori_filter'";
}
if ($search) {
    $search = mysqli_real_escape_string($koneksi, $search);
    $where_clause[] = "(nama_barang LIKE '%$search%' OR kode_barang LIKE '%$search%')";
}

$where_sql = !empty($where_clause) ? "WHERE " . implode(" AND ", $where_clause) : "";

// ==========================================
// QUERY BARANG
// ==========================================
$query = "SELECT * FROM barang $where_sql ORDER BY id DESC";
$result = mysqli_query($koneksi, $query);
$total_barang = mysqli_num_rows($result);

// ==========================================
// AMBIL DAFTAR KATEGORI (untuk filter)
// ==========================================
$kategori_query = "SELECT DISTINCT kategori FROM barang ORDER BY kategori ASC";
$kategori_result = mysqli_query($koneksi, $kategori_query);
$kategori_list = [];
while($row = mysqli_fetch_assoc($kategori_result)) {
    $kategori_list[] = $row['kategori'];
}
?>

<?php include '../includes/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1><i class="fas fa-box"></i> Master Barang</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <?php if (isset($_GET['msg'])): ?>
                <?php if($_GET['msg'] == 'added'): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> Barang berhasil ditambahkan!
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php elseif($_GET['msg'] == 'updated'): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> Barang berhasil diupdate!
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php elseif($_GET['msg'] == 'deleted'): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> Barang berhasil dihapus!
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="card">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0"><i class="fas fa-list"></i> Data Barang</h3>
                    <a href="tambah_barang.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Tambah Barang
                    </a>
                </div>
                <div class="card-body">
                    <!-- Filter Section -->
                    <form method="GET" action="" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Filter Kategori</label>
                                    <select name="kategori" class="form-control">
                                        <option value="">-- Semua Kategori --</option>
                                        <?php foreach($kategori_list as $kat): ?>
                                            <option value="<?= $kat ?>" <?= $kategori_filter == $kat ? 'selected' : '' ?>><?= ucfirst($kat) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label>Cari Barang</label>
                                    <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode barang..." value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <div class="d-flex">
                                        <button type="submit" class="btn btn-dark mr-2">
                                            <i class="fas fa-search"></i> Filter
                                        </button>
                                        <a href="master_barang.php" class="btn btn-secondary">
                                            <i class="fas fa-redo"></i> Reset
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <p class="text-muted mb-3">Total: <strong><?= $total_barang ?></strong> barang</p>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Kode</th>
                                    <th>Nama Barang</th>
                                    <th>Kategori</th>
                                    <th>Harga</th>
                                    <th>Stok</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $no = 1;
                                while($row = mysqli_fetch_assoc($result)): 
                                    $badge = $row['status'] == 'active' ? 'success' : 'secondary';
                                    $kat_badge = 'info';
                                    if($row['kategori'] == 'hardware') $kat_badge = 'primary';
                                    if($row['kategori'] == 'software') $kat_badge = 'warning';
                                    if($row['kategori'] == 'network') $kat_badge = 'danger';
                                    if($row['kategori'] == 'atk') $kat_badge = 'success';
                                    if($row['kategori'] == 'furniture') $kat_badge = 'dark';
                                ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><strong><?= htmlspecialchars($row['kode_barang']) ?></strong></td>
                                    <td><?= htmlspecialchars($row['nama_barang']) ?></td>
                                    <td><span class="badge badge-<?= $kat_badge ?>"><?= ucfirst($row['kategori']) ?></span></td>
                                    <td>Rp <?= number_format($row['harga_terakhir'], 0, ',', '.') ?></td>
                                    <td><?= $row['stok'] ?> <?= htmlspecialchars($row['satuan']) ?></td>
                                    <td><span class="badge badge-<?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
                                    <td>
                                        <a href="edit_barang.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?hapus=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus barang ini?')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if($total_barang == 0): ?>
                                    <tr><td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-2"></i><br>
                                        Tidak ada barang yang ditemukan<?= $kategori_filter || $search ? ' dengan filter ini' : '' ?>.
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