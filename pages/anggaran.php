<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$page_title = 'Pengaturan Anggaran - UAS Pengadaan';

// ==========================================
// PROSES SIMPAN ANGGARAN
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_budget'])) {
    foreach ($_POST['budget'] as $dept => $amount) {
        $dept_clean = mysqli_real_escape_string($koneksi, $dept);
        $amount_clean = (float)$amount;
        
        // Cek apakah departemen sudah ada
        $check = mysqli_query($koneksi, "SELECT id FROM department_budgets WHERE department = '$dept_clean'");
        if (mysqli_num_rows($check) > 0) {
            // Update
            mysqli_query($koneksi, "UPDATE department_budgets SET annual_budget = $amount_clean WHERE department = '$dept_clean'");
        } else {
            // Insert
            mysqli_query($koneksi, "INSERT INTO department_budgets (department, annual_budget) VALUES ('$dept_clean', $amount_clean)");
        }
    }
    header("Location: anggaran.php?msg=saved");
    exit();
}

// ==========================================
// AMBIL DATA ANGGARAN
// ==========================================
// Pastikan tabel ada
mysqli_query($koneksi, "CREATE TABLE IF NOT EXISTS department_budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(100) NOT NULL UNIQUE,
    annual_budget DECIMAL(15,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Ambil semua budget
$budget_query = "SELECT * FROM department_budgets ORDER BY department ASC";
$budget_result = mysqli_query($koneksi, $budget_query);
$budgets = [];
while($row = mysqli_fetch_assoc($budget_result)) {
    $budgets[$row['department']] = $row['annual_budget'];
}

// Daftar departemen (dari tabel users)
$dept_query = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department ASC";
$dept_result = mysqli_query($koneksi, $dept_query);
$departments = [];
while($row = mysqli_fetch_assoc($dept_result)) {
    $departments[] = $row['department'];
}

// ==========================================
// HITUNG PENGELUARAN REAL PER DEPARTEMEN
// ==========================================
$spending_query = "SELECT 
                    pr.department,
                    COUNT(*) as total_requests,
                    SUM(pr.total_estimated_price) as total_spent
                   FROM purchase_requests pr
                   WHERE pr.status IN ('approved', 'ordered', 'completed')
                   GROUP BY pr.department";
$spending_result = mysqli_query($koneksi, $spending_query);
$spending = [];
while($row = mysqli_fetch_assoc($spending_result)) {
    $spending[$row['department']] = [
        'total_requests' => $row['total_requests'],
        'total_spent' => $row['total_spent']
    ];
}

// Total keseluruhan
$total_budget = array_sum($budgets);
$total_spent = array_sum(array_column($spending, 'total_spent'));
$total_remaining = $total_budget - $total_spent;
?>

<?php include '../includes/header.php'; ?>

<style>
    .budget-card {
        transition: transform 0.2s;
        border-radius: 10px;
    }
    .budget-card:hover {
        transform: translateY(-3px);
    }
    .progress {
        height: 25px;
        border-radius: 12px;
        background-color: #e9ecef;
    }
    .progress-bar {
        border-radius: 12px;
        font-weight: 600;
        line-height: 25px;
    }
    .budget-safe { background: linear-gradient(135deg, #28a745, #20c997); }
    .budget-warning { background: linear-gradient(135deg, #ffc107, #ff9800); color: #000 !important; }
    .budget-danger { background: linear-gradient(135deg, #dc3545, #c82333); }
    .stat-box {
        border-radius: 10px;
        padding: 20px;
        color: white;
    }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1><i class="fas fa-wallet"></i> Pengaturan Anggaran Departemen</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> Anggaran berhasil disimpan!
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Ringkasan Total -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                        <h6 class="mb-1">Total Anggaran</h6>
                        <h3 class="mb-0">Rp <?= number_format($total_budget, 0, ',', '.') ?></h3>
                        <small>Anggaran semua departemen</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #ffc107, #ff9800); color: #000;">
                        <h6 class="mb-1">Total Terpakai</h6>
                        <h3 class="mb-0">Rp <?= number_format($total_spent, 0, ',', '.') ?></h3>
                        <small><?= $total_budget > 0 ? round(($total_spent/$total_budget)*100, 1) : 0 ?>% dari total anggaran</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-box" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <h6 class="mb-1">Sisa Anggaran</h6>
                        <h3 class="mb-0">Rp <?= number_format($total_remaining, 0, ',', '.') ?></h3>
                        <small><?= $total_budget > 0 ? round(($total_remaining/$total_budget)*100, 1) : 0 ?>% tersisa</small>
                    </div>
                </div>
            </div>

            <!-- Detail per Departemen -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-dark text-white">
                            <h3 class="card-title mb-0"><i class="fas fa-chart-bar"></i> Penggunaan Anggaran per Departemen</h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($departments)): ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-3x mb-2"></i>
                                    <p>Belum ada departemen terdaftar.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach($departments as $dept): 
    $budget = $budgets[$dept] ?? 0;
    $spent = $spending[$dept]['total_spent'] ?? 0;
    $requests = $spending[$dept]['total_requests'] ?? 0;
    $remaining = $budget - $spent;
    
    // Hitung persentase dengan benar
    $percentage = $budget > 0 ? ($spent / $budget) * 100 : ($spent > 0 ? 100 : 0);
    
    // Tentukan warna dan status
    if ($budget == 0 && $spent == 0) {
        $bar_class = 'bg-secondary';
        $status_text = 'Belum Diset';
        $status_badge = 'secondary';
    } elseif ($spent > $budget) {
        // OVER BUDGET!
        $bar_class = 'budget-danger';
        $status_text = 'Over Budget!';
        $status_badge = 'danger';
    } elseif ($percentage >= 90) {
        $bar_class = 'budget-danger';
        $status_text = 'Kritis!';
        $status_badge = 'danger';
    } elseif ($percentage >= 70) {
        $bar_class = 'budget-warning';
        $status_text = 'Hampir Habis';
        $status_badge = 'warning';
    } else {
        $bar_class = 'budget-safe';
        $status_text = 'Aman';
        $status_badge = 'success';
    }
?>
    <div class="budget-card mb-4 p-3" style="background: #f8f9fa;">
        <div class="row align-items-center mb-2">
            <div class="col-md-4">
                <h5 class="mb-0"><strong><?= htmlspecialchars($dept) ?></strong></h5>
                <small class="text-muted"><?= $requests ?> pengajuan disetujui</small>
            </div>
            <div class="col-md-8 text-right">
                <span class="badge badge-<?= $status_badge ?> badge-lg"><?= $status_text ?></span>
            </div>
        </div>
        
        <div class="progress mb-2" style="height: 30px;">
            <div class="progress-bar <?= $bar_class ?>" 
                 role="progressbar" 
                 style="width: <?= min($percentage, 100) ?>%" 
                 aria-valuenow="<?= $percentage ?>" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
                <?= round($percentage, 1) ?>%
            </div>
        </div>
        
        <div class="row text-center">
            <div class="col-md-3">
                <small class="text-muted">Anggaran</small>
                <div><strong>Rp <?= number_format($budget, 0, ',', '.') ?></strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Terpakai</small>
                <div><strong class="<?= $spent > $budget ? 'text-danger' : 'text-warning' ?>">Rp <?= number_format($spent, 0, ',', '.') ?></strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Sisa</small>
                <div><strong class="<?= $remaining < 0 ? 'text-danger' : 'text-success' ?>">Rp <?= number_format($remaining, 0, ',', '.') ?></strong></div>
            </div>
            <div class="col-md-3">
                <small class="text-muted">Status</small>
                <div><span class="badge badge-<?= $status_badge ?>"><?= $status_text ?></span></div>
            </div>
        </div>
        
        <?php if ($spent > $budget && $budget > 0): ?>
            <div class="alert alert-danger mt-3 mb-0">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>PERINGATAN:</strong> Departemen ini sudah melebihi anggaran sebesar 
                <strong>Rp <?= number_format($spent - $budget, 0, ',', '.') ?></strong>!
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Set Anggaran -->
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h3 class="card-title mb-0"><i class="fas fa-edit"></i> Set Limit Anggaran Tahunan</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Departemen</th>
                                    <th>Anggaran (Rp)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($departments as $dept): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($dept) ?></strong></td>
                                        <td>
                                            <input type="number" 
                                                   name="budget[<?= htmlspecialchars($dept) ?>]" 
                                                   class="form-control" 
                                                   value="<?= $budgets[$dept] ?? 0 ?>" 
                                                   min="0" 
                                                   step="1000000"
                                                   placeholder="Masukkan anggaran">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($departments)): ?>
                                    <tr><td colspan="2" class="text-center text-muted">Belum ada departemen. Tambahkan user dengan departemen terlebih dahulu.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <button type="submit" name="save_budget" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Simpan Anggaran
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>