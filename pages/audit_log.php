<?php
session_start();
/** @var mysqli $koneksi */
require_once '../koneksi.php';

$page_title = 'Audit Log - UAS Pengadaan';

// Hanya Admin yang boleh akses
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Filter tanggal (opsional)
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-d', strtotime('-7 days'));
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Query log dengan filter
$query = "SELECT * FROM audit_logs 
          WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
          ORDER BY created_at DESC 
          LIMIT 100";
$result = mysqli_query($koneksi, $query);

// Statistik
$total_logs = mysqli_num_rows($result);
$unique_users = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(DISTINCT user_id) as total FROM audit_logs WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'"))['total'];
?>

<?php include '../includes/header.php'; ?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1>Audit Log</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <!-- Statistik -->
            <div class="row">
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-dark"><i class="fas fa-list"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Aktivitas</span>
                            <span class="info-box-number"><?= $total_logs ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-box-icon bg-dark"><i class="fas fa-users"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">User Aktif</span>
                            <span class="info-box-number"><?= $unique_users ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Tanggal -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-filter"></i> Filter Aktivitas</h3>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="form-inline">
                        <div class="form-group mr-3">
                            <label class="mr-2">Dari:</label>
                            <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
                        </div>
                        <div class="form-group mr-3">
                            <label class="mr-2">Sampai:</label>
                            <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="audit_log.php" class="btn btn-secondary ml-2">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </form>
                </div>
            </div>

            <!-- Tabel Log -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-history"></i> Riwayat Aktivitas (100 Terakhir)</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Waktu</th>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Aksi</th>
                                    <th>Deskripsi</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($log = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                                    <td><strong><?= htmlspecialchars($log['user_name']) ?></strong></td>
                                    <td><span class="badge badge-info"><?= ucfirst($log['user_role']) ?></span></td>
                                    <td><span class="badge badge-dark"><?= strtoupper($log['action']) ?></span></td>
                                    <td><?= htmlspecialchars($log['description']) ?></td>
                                    <td><code><?= htmlspecialchars($log['ip_address']) ?></code></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($result) == 0): ?>
                                    <tr><td colspan="6" class="text-center">Tidak ada aktivitas dalam periode ini.</td></tr>
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