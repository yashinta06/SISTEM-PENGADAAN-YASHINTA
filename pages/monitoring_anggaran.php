<?php
session_start();
/** @var mysqli $koneksi */
require_once '../koneksi.php';

$page_title = 'Monitoring Anggaran - UAS Pengadaan';

// Bisa diakses Admin dan Manager (untuk monitoring)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header("Location: dashboard.php");
    exit();
}

// Ambil data budgets
$query_budget = "SELECT department, budget_amount FROM budgets";
$result_budget = mysqli_query($koneksi, $query_budget);

// Ambil total pengeluaran real (request yang approved/ordered/completed)
$query_spent = "SELECT department, SUM(total_estimated_price) as spent 
                FROM purchase_requests 
                WHERE status IN ('approved', 'ordered', 'completed') 
                GROUP BY department";
$result_spent = mysqli_query($koneksi, $query_spent);
$data_spent = [];
while($row = mysqli_fetch_assoc($result_spent)) {
    $data_spent[$row['department']] = $row['spent'];
}
?>

<?php include '../includes/header.php'; ?>

<style>
    .budget-card { transition: transform 0.2s; margin-bottom: 20px; }
    .budget-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1><i class="fas fa-chart-pie"></i> Monitoring Anggaran Departemen</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Data pengeluaran dihitung dari Purchase Request berstatus <strong>Approved, Ordered, dan Completed</strong>.
            </div>

            <div class="row">
                <?php 
                $total_budget_all = 0;
                $total_spent_all = 0;
                
                while($b = mysqli_fetch_assoc($result_budget)): 
                    $dept = $b['department'];
                    $budget = $b['budget_amount'];
                    $spent = $data_spent[$dept] ?? 0;
                    $remaining = $budget - $spent;
                    
                    // Hitung persentase
                    $percent = $budget > 0 ? ($spent / $budget) * 100 : 0;
                    if($percent > 100) $percent = 100;
                    
                    // Tentukan warna progress bar
                    if($percent >= 80) $color = 'danger'; // Merah (Bahaya)
                    elseif($percent >= 50) $color = 'warning'; // Kuning (Waspada)
                    else $color = 'success'; // Hijau (Aman)

                    $total_budget_all += $budget;
                    $total_spent_all += $spent;
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card budget-card">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <h3 class="card-title mb-0"><?= htmlspecialchars($dept) ?></h3>
                            <span class="badge badge-<?= $color ?>"><?= number_format($percent, 1) ?>%</span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <small class="text-muted">Terpakai</small>
                                    <h5 class="mb-0 font-weight-bold">Rp <?= number_format($spent, 0, ',', '.') ?></h5>
                                </div>
                                <div class="col-6 text-right">
                                    <small class="text-muted">Sisa Budget</small>
                                    <h5 class="mb-0 font-weight-bold text-<?= $remaining < 0 ? 'danger' : 'success' ?>">
                                        Rp <?= number_format($remaining, 0, ',', '.') ?>
                                    </h5>
                                </div>
                            </div>
                            
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar bg-<?= $color ?>" role="progressbar" style="width: <?= $percent ?>%;" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100">
                                    <?= number_format($percent, 1) ?>%
                                </div>
                            </div>
                            
                            <small class="text-muted mt-2 d-block text-center">
                                Total Alokasi: <strong>Rp <?= number_format($budget, 0, ',', '.') ?></strong>
                            </small>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <!-- Ringkasan Total Perusahaan -->
            <div class="card mt-4">
                <div class="card-header bg-dark text-white"><h3 class="card-title"><i class="fas fa-building"></i> Ringkasan Total Perusahaan</h3></div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <h6 class="text-muted">Total Anggaran</h6>
                            <h3 class="font-weight-bold">Rp <?= number_format($total_budget_all, 0, ',', '.') ?></h3>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Total Terpakai</h6>
                            <h3 class="font-weight-bold text-warning">Rp <?= number_format($total_spent_all, 0, ',', '.') ?></h3>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Sisa Anggaran</h6>
                            <h3 class="font-weight-bold text-success">Rp <?= number_format($total_budget_all - $total_spent_all, 0, ',', '.') ?></h3>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 30px;">
                        <?php $total_percent = $total_budget_all > 0 ? ($total_spent_all / $total_budget_all) * 100 : 0; ?>
                        <div class="progress-bar bg-dark" style="width: <?= $total_percent ?>%"><?= number_format($total_percent, 1) ?>% Terpakai</div>
                    </div>
                </div>
            </div>

        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>