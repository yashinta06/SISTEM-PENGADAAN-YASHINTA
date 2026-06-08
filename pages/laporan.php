<?php
session_start();
/** @var mysqli $koneksi */
require_once '../koneksi.php';

$page_title = 'Laporan Pengadaan - UAS Pengadaan';

// Admin dan Manager boleh akses
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header("Location: dashboard.php");
    exit();
}

// ==========================================
// HANDLE FILTER DARI DASHBOARD
// ==========================================
$filter_status = "";
$filter_title = "Semua Pengajuan";
$filter_where = "WHERE pr.status IN ('approved', 'ordered', 'completed')";

if (isset($_GET['filter']) && $_GET['filter'] == 'approved') {
    $filter_status = "AND pr.status = 'approved'";
    $filter_title = "Pengajuan Disetujui";
    $filter_where = "WHERE pr.status = 'approved'";
}

// ==========================================
// STATISTIK UTAMA
// ==========================================
$total_request = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_requests pr $filter_where $filter_status"))['total'];
$total_pengeluaran = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(total_estimated_price) as total FROM purchase_requests pr $filter_where $filter_status"))['total'] ?? 0;
$total_barang = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT SUM(quantity) as total FROM purchase_request_items pri JOIN purchase_requests pr ON pri.purchase_request_id = pr.id $filter_where $filter_status"))['total'] ?? 0;
$rata_rata = $total_request > 0 ? $total_pengeluaran / $total_request : 0;

// ==========================================
// DATA UNTUK GRAFIK 1: Trend Bulanan (Line Chart)
// ==========================================
$query_bulanan = "SELECT 
                    DATE_FORMAT(request_date, '%Y-%m') as bulan,
                    COUNT(*) as jumlah,
                    SUM(total_estimated_price) as total
                  FROM purchase_requests pr
                  $filter_where $filter_status
                    AND request_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                  GROUP BY DATE_FORMAT(request_date, '%Y-%m')
                  ORDER BY bulan ASC";
$result_bulanan = mysqli_query($koneksi, $query_bulanan);
$labels_bulan = [];
$data_jumlah = [];
$data_total = [];
while($row = mysqli_fetch_assoc($result_bulanan)) {
    $labels_bulan[] = date('M Y', strtotime($row['bulan'] . '-01'));
    $data_jumlah[] = (int)$row['jumlah'];
    $data_total[] = (float)$row['total'];
}

// ==========================================
// DATA UNTUK GRAFIK 2: Status Request (Pie Chart)
// ==========================================
$query_status = "SELECT status, COUNT(*) as total FROM purchase_requests pr $filter_where $filter_status GROUP BY status";
$result_status = mysqli_query($koneksi, $query_status);
$labels_status = [];
$data_status = [];
$warna_status = [];
$warna_map = [
    'draft' => '#6c757d',
    'submitted' => '#ffc107',
    'approved' => '#28a745',
    'rejected' => '#dc3545',
    'ordered' => '#17a2b8',
    'completed' => '#000000'
];
while($row = mysqli_fetch_assoc($result_status)) {
    $labels_status[] = ucfirst($row['status']);
    $data_status[] = (int)$row['total'];
    $warna_status[] = $warna_map[$row['status']] ?? '#6c757d';
}

// ==========================================
// DATA UNTUK GRAFIK 3: Pengeluaran per Kategori (Bar Chart)
// ==========================================
$query_kategori = "SELECT 
                    pri.category,
                    SUM(pri.total_price) as total
                   FROM purchase_request_items pri
                   JOIN purchase_requests pr ON pri.purchase_request_id = pr.id
                   $filter_where $filter_status
                   GROUP BY pri.category
                   ORDER BY total DESC";
$result_kategori = mysqli_query($koneksi, $query_kategori);
$labels_kategori = [];
$data_kategori = [];
while($row = mysqli_fetch_assoc($result_kategori)) {
    $labels_kategori[] = ucfirst($row['category']);
    $data_kategori[] = (float)$row['total'];
}

// ==========================================
// DATA UNTUK GRAFIK 4: Top 5 Departemen (Horizontal Bar)
// ==========================================
$query_dept = "SELECT 
                pr.department,
                COUNT(*) as jumlah,
                SUM(pr.total_estimated_price) as total
               FROM purchase_requests pr
               $filter_where $filter_status
               GROUP BY pr.department
               ORDER BY total DESC
               LIMIT 5";
$result_dept = mysqli_query($koneksi, $query_dept);
$labels_dept = [];
$data_dept_jumlah = [];
$data_dept_total = [];
while($row = mysqli_fetch_assoc($result_dept)) {
    $labels_dept[] = $row['department'];
    $data_dept_jumlah[] = (int)$row['jumlah'];
    $data_dept_total[] = (float)$row['total'];
}

// ==========================================
// DETAIL TABEL
// ==========================================
$query_detail = "SELECT pr.request_number, pr.title, pr.request_date, u.name as requester, pr.department, pr.total_estimated_price, pr.status 
                 FROM purchase_requests pr 
                 JOIN users u ON pr.user_id = u.id 
                 $filter_where $filter_status
                 ORDER BY pr.request_date DESC";
$result_detail = mysqli_query($koneksi, $query_detail);
?>

<?php include '../includes/header.php'; ?>

<style>
    @media print {
        .main-sidebar, .main-header, .no-print { display: none !important; }
        .content-wrapper { margin-left: 0 !important; }
        body { background-color: #fff; }
        .card { border: 1px solid #ddd !important; page-break-inside: avoid; }
    }
    .chart-container { position: relative; height: 300px; }
    .info-box-stat { transition: transform 0.2s; cursor: pointer; }
        .chart-container { position: relative; height: 300px; }
    .info-box-stat { 
        transition: all 0.2s; 
        cursor: pointer;
        border-radius: 8px;
        overflow: hidden;
    }
    .info-box-stat:hover { 
        transform: scale(1.05);
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }
    .filter-alert {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        border-radius: 8px;
        padding: 15px 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .filter-alert .badge {
        background: white;
        color: #17a2b8;
        padding: 5px 10px;
        font-size: 14px;
    }
    .filter-alert {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        border-radius: 8px;
        padding: 15px 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><i class="fas fa-chart-line"></i> Laporan & Analitik Pengadaan</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right no-print">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Laporan</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            
                        <!-- Filter Info Alert -->
            <?php if (isset($_GET['filter']) && $_GET['filter'] == 'approved'): ?>
                <div class="filter-alert d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas fa-filter fa-2x mr-3"></i>
                        <strong>Filter Aktif:</strong> <?= $filter_title ?>
                        <a href="purchase_request.php?filter=approved" class="badge ml-2" style="text-decoration: none;">
                            <i class="fas fa-external-link-alt"></i> <?= $total_request ?> data
                        </a>
                    </div>
                    <div>
                        <a href="purchase_request.php?filter=approved" class="btn btn-light btn-sm mr-2">
                            <i class="fas fa-list"></i> Lihat Detail
                        </a>
                        <a href="laporan.php" class="btn btn-light btn-sm">
                            <i class="fas fa-times"></i> Reset Filter
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tombol Aksi -->
            <div class="row mb-3 no-print">
                <div class="col-12 text-right">
                    <button onclick="window.print()" class="btn btn-primary btn-lg">
                        <i class="fas fa-print"></i> Print Laporan
                    </button>
                    <a href="export_laporan.php<?= isset($_GET['filter']) ? '?filter=' . $_GET['filter'] : '' ?>" class="btn btn-success btn-lg ml-2">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                </div>
            </div>

                       <!-- STATISTIK UTAMA -->
            <div class="row">
                <div class="col-lg-3 col-6">
                    <a href="purchase_request.php?filter=approved" style="text-decoration: none; color: inherit;">
                        <div class="info-box info-box-stat">
                            <span class="info-box-icon bg-dark elevation-1"><i class="fas fa-file-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Pengajuan</span>
                                <span class="info-box-number"><?= $total_request ?></span>
                                <span class="progress-description"><i class="fas fa-external-link-alt"></i> Lihat detail</span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="info-box info-box-stat">
                        <span class="info-box-icon bg-dark elevation-1"><i class="fas fa-boxes"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Total Barang</span>
                            <span class="info-box-number"><?= number_format($total_barang) ?></span>
                            <span class="progress-description">Unit Dibeli</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <a href="purchase_order.php" style="text-decoration: none; color: inherit;">
                        <div class="info-box info-box-stat">
                            <span class="info-box-icon bg-dark elevation-1"><i class="fas fa-money-bill-wave"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Pengeluaran</span>
                                <span class="info-box-number">Rp <?= number_format($total_pengeluaran/1000000, 1) ?>jt</span>
                                <span class="progress-description"><i class="fas fa-external-link-alt"></i> Lihat PO</span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="info-box info-box-stat">
                        <span class="info-box-icon bg-dark elevation-1"><i class="fas fa-calculator"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Rata-rata/Request</span>
                            <span class="info-box-number">Rp <?= number_format($rata_rata/1000, 0) ?>rb</span>
                            <span class="progress-description">Per Pengajuan</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRAFIK UTAMA -->
            <div class="row">
                <!-- Line Chart: Trend Bulanan -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-line"></i> Trend Pengajuan 6 Bulan Terakhir</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pie Chart: Status -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-chart-pie"></i> Distribusi Status</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRAFIK PENDUKUNG -->
            <div class="row">
                <!-- Bar Chart: Per Kategori -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-tags"></i> Pengeluaran per Kategori</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="kategoriChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Horizontal Bar: Top Departemen -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-building"></i> Top 5 Departemen</h3>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="deptChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABEL DETAIL -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-table"></i> Detail Riwayat Pengadaan</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>No. Request</th>
                                    <th>Tanggal</th>
                                    <th>Judul</th>
                                    <th>Pemohon</th>
                                    <th>Departemen</th>
                                    <th>Total (Rp)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($result_detail)): 
                                    $badge = 'success';
                                    if($row['status'] == 'ordered') $badge = 'info';
                                    if($row['status'] == 'completed') $badge = 'dark';
                                ?>
                                <tr>
                                    <td><strong><?= $row['request_number'] ?></strong></td>
                                    <td><?= date('d/m/Y', strtotime($row['request_date'])) ?></td>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['requester']) ?></td>
                                    <td><span class="badge badge-secondary"><?= htmlspecialchars($row['department']) ?></span></td>
                                    <td>Rp <?= number_format($row['total_estimated_price'], 0, ',', '.') ?></td>
                                    <td><span class="badge badge-<?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if(mysqli_num_rows($result_detail) == 0): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-3x mb-2"></i><br>
                                        Belum ada data pengadaan yang diproses<?= isset($_GET['filter']) ? ' dengan filter ini' : '' ?>.
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// GRAFIK 1: Trend Bulanan (Line Chart)
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labels_bulan) ?>,
        datasets: [{
            label: 'Jumlah Request',
            data: <?= json_encode($data_jumlah) ?>,
            borderColor: '#000000',
            backgroundColor: 'rgba(0,0,0,0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: true } },
        scales: { y: { beginAtZero: true } }
    }
});

// GRAFIK 2: Status (Pie Chart)
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($labels_status) ?>,
        datasets: [{
            data: <?= json_encode($data_status) ?>,
            backgroundColor: <?= json_encode($warna_status) ?>,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    }
});

// GRAFIK 3: Kategori (Bar Chart)
new Chart(document.getElementById('kategoriChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels_kategori) ?>,
        datasets: [{
            label: 'Total Pengeluaran (Rp)',
            data: <?= json_encode($data_kategori) ?>,
            backgroundColor: '#000000',
            borderColor: '#ffffff',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});

// GRAFIK 4: Departemen (Horizontal Bar)
new Chart(document.getElementById('deptChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels_dept) ?>,
        datasets: [{
            label: 'Total Pengeluaran (Rp)',
            data: <?= json_encode($data_dept_total) ?>,
            backgroundColor: '#333333',
            borderColor: '#ffffff',
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true } }
    }
});
</script>