<?php
/** @var mysqli $koneksi */
require_once '../koneksi.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$page_title = 'Dashboard - UAS Pengadaan';

$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];
$user_dept = $_SESSION['user_department'];
$uid = $_SESSION['user_id'];

// ==========================================
// QUERY UMUM
// ==========================================
$stat_barang = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM barang"))['total'];
$stat_user = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM users"))['total'];
$stat_po = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_orders"))['total'];
$stat_req = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_requests"))['total'];
$stat_pending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_requests WHERE status IN ('draft', 'submitted')"))['total'];
$stat_approved = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_requests WHERE status = 'approved'"))['total'];

$low_stock = mysqli_query($koneksi, "SELECT * FROM barang WHERE stok <= min_stok ORDER BY stok ASC LIMIT 5");
$recent = mysqli_query($koneksi, "SELECT pr.*, u.name as requester_name FROM purchase_requests pr JOIN users u ON pr.user_id = u.id ORDER BY pr.id DESC LIMIT 5");

// ==========================================
// QUERY KHUSUS REQUESTER
// ==========================================
$my_req_total = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_requests WHERE user_id = $uid"))['total'];
$my_req_pending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_requests WHERE user_id = $uid AND status IN ('draft', 'submitted')"))['total'];
$my_req_approved = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_requests WHERE user_id = $uid AND status IN ('approved', 'ordered', 'completed')"))['total'];
$my_req_rejected = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_requests WHERE user_id = $uid AND status = 'rejected'"))['total'];

$my_chart_query = mysqli_query($koneksi, "SELECT status, COUNT(*) as total FROM purchase_requests WHERE user_id = $uid GROUP BY status");
$my_labels = [];
$my_data = [];
while($row = mysqli_fetch_assoc($my_chart_query)) {
    $my_labels[] = ucfirst($row['status']);
    $my_data[] = (int)$row['total'];
}

// ==========================================
// QUERY KHUSUS WAREHOUSE
// ==========================================
$po_pending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_orders WHERE status IN ('pending', 'shipped')"))['total'];
$received_this_week = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_orders WHERE status = 'received' AND YEARWEEK(actual_delivery_date) = YEARWEEK(NOW())"))['total'];
$low_stock_count = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM barang WHERE stok <= min_stok"))['total'];

$query_soon = "SELECT po.po_number, pr.title, po.expected_delivery_date, v.name as vendor 
               FROM purchase_orders po
               JOIN purchase_requests pr ON po.purchase_request_id = pr.id
               LEFT JOIN vendors v ON po.vendor_id = v.id
               WHERE po.status IN ('pending', 'shipped') 
               AND po.expected_delivery_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY)
               ORDER BY po.expected_delivery_date ASC
               LIMIT 5";
$result_soon = mysqli_query($koneksi, $query_soon);

$query_recent_wh = "SELECT po.po_number, pr.title, po.actual_delivery_date
                 FROM purchase_orders po
                 JOIN purchase_requests pr ON po.purchase_request_id = pr.id
                 WHERE po.status = 'received'
                 ORDER BY po.actual_delivery_date DESC
                 LIMIT 5";
$result_recent_wh = mysqli_query($koneksi, $query_recent_wh);

// ==========================================
// QUERY KHUSUS MANAGER
// ==========================================
$mgr_pending = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_requests WHERE status IN ('draft', 'submitted')"))['total'];
$mgr_approved_month = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_requests WHERE status = 'approved' AND MONTH(approved_at) = MONTH(CURRENT_DATE()) AND YEAR(approved_at) = YEAR(CURRENT_DATE())"))['total'];
$mgr_rejected_month = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_requests WHERE status = 'rejected' AND MONTH(approved_at) = MONTH(CURRENT_DATE()) AND YEAR(approved_at) = YEAR(CURRENT_DATE())"))['total'];
$mgr_total_month = $mgr_approved_month + $mgr_rejected_month;
$mgr_approval_rate = $mgr_total_month > 0 ? round(($mgr_approved_month / $mgr_total_month) * 100, 1) : 0;

$mgr_total_value = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(total_estimated_price), 0) as total FROM purchase_requests WHERE status = 'approved' AND MONTH(approved_at) = MONTH(CURRENT_DATE()) AND YEAR(approved_at) = YEAR(CURRENT_DATE())"))['total'];

$query_urgent = "SELECT pr.id, pr.request_number, pr.title, pr.needed_date, pr.total_estimated_price, u.name as requester, pr.department 
                 FROM purchase_requests pr 
                 JOIN users u ON pr.user_id = u.id 
                 WHERE pr.status IN ('draft', 'submitted') AND pr.needed_date <= DATE_ADD(CURDATE(), INTERVAL 3 DAY) 
                 ORDER BY pr.needed_date ASC";
$result_urgent = mysqli_query($koneksi, $query_urgent);
$urgent_count = mysqli_num_rows($result_urgent);

$query_dept = "SELECT department, COUNT(*) as total FROM purchase_requests WHERE status IN ('draft', 'submitted') GROUP BY department ORDER BY total DESC";
$result_dept = mysqli_query($koneksi, $query_dept);
$mgr_dept_labels = [];
$mgr_dept_data = [];
while($row = mysqli_fetch_assoc($result_dept)) {
    $mgr_dept_labels[] = $row['department'];
    $mgr_dept_data[] = (int)$row['total'];
}

$query_timeline = "SELECT pr.id, pr.request_number, pr.title, pr.status, pr.approved_at, u.name as requester, pr.total_estimated_price
                   FROM purchase_requests pr
                   JOIN users u ON pr.user_id = u.id
                   WHERE pr.status IN ('approved', 'rejected') AND pr.approved_by IS NOT NULL
                   ORDER BY pr.approved_at DESC
                   LIMIT 5";
$result_timeline = mysqli_query($koneksi, $query_timeline);

// ==========================================
// QUERY KHUSUS PURCHASING
// ==========================================
$po_total_pur = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_orders"))['total'];
$po_pending_pur = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_orders WHERE status = 'pending'"))['total'];
$po_shipped_pur = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_orders WHERE status = 'shipped'"))['total'];
$po_received_pur = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_orders WHERE status = 'received'"))['total'];
$po_value_month = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COALESCE(SUM(grand_total), 0) as total FROM purchase_orders WHERE MONTH(order_date) = MONTH(CURRENT_DATE()) AND YEAR(order_date) = YEAR(CURRENT_DATE())"))['total'];
$need_po = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_requests WHERE status = 'approved' AND id NOT IN (SELECT purchase_request_id FROM purchase_orders WHERE purchase_request_id IS NOT NULL)"))['total'];
$po_pending_lama = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as total FROM purchase_orders WHERE status = 'pending' AND order_date <= DATE_SUB(CURDATE(), INTERVAL 3 DAY)"))['total'];
?>

<?php include '../includes/header.php'; ?>

<style>
    .welcome-banner {
        background: linear-gradient(135deg, #1a1a1a 0%, #333333 100%);
        color: white;
        border-radius: 12px;
        padding: 25px 30px;
        margin-bottom: 25px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .welcome-banner h2 {
        margin: 0;
        font-weight: 700;
        font-size: 28px;
    }
    .greeting-icon {
        font-size: 50px;
        opacity: 0.3;
        position: absolute;
        right: 30px;
        top: 50%;
        transform: translateY(-50%);
    }
    .stat-card {
        transition: all 0.3s ease;
        border-radius: 10px;
        overflow: hidden;
        border: none;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    .stat-card .card-body {
        padding: 20px;
    }
    .stat-icon {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        color: white;
        margin-bottom: 15px;
    }
    .stat-number {
        font-size: 32px;
        font-weight: 700;
        margin: 0;
        line-height: 1;
    }
    .stat-label {
        font-size: 13px;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 5px;
    }
    .urgent-card {
        border-left: 5px solid #dc3545 !important;
    }
    .urgent-item {
        padding: 15px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s;
    }
    .urgent-item:hover {
        background: #fff8f8;
    }
    .urgent-item:last-child {
        border-bottom: none;
    }
    .deadline-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .deadline-today {
        background: #dc3545;
        color: white;
        animation: pulse 2s infinite;
    }
    .deadline-soon {
        background: #ffc107;
        color: #000;
    }
    .timeline-item {
        padding: 12px 15px;
        border-left: 3px solid #e9ecef;
        margin-left: 10px;
        position: relative;
        cursor: pointer;
        transition: all 0.2s;
    }
    .timeline-item:hover {
        background: #f8f9fa;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 18px;
        width: 13px;
        height: 13px;
        border-radius: 50%;
        background: white;
        border: 3px solid #6c757d;
    }
    .timeline-item.approved::before {
        border-color: #28a745;
        background: #28a745;
    }
    .timeline-item.rejected::before {
        border-color: #dc3545;
        background: #dc3545;
    }
    .quick-action-btn {
        padding: 15px 30px;
        font-size: 16px;
        font-weight: 600;
        border-radius: 8px;
        transition: all 0.3s;
    }
    .quick-action-btn:hover {
        transform: scale(1.05);
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    .rate-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        font-weight: 700;
        margin: 0 auto;
        position: relative;
    }
    .rate-circle::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 50%;
        padding: 8px;
        background: conic-gradient(from 0deg, #28a745 0% var(--rate), #e9ecef var(--rate) 100%);
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        mask-composite: exclude;
    }
    .text-purple {
        color: #6f42c1 !important;
    }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">
                        <?php if ($user_role == 'manager'): ?>
                            <i class="fas fa-user-tie"></i> Dashboard Manager
                        <?php elseif ($user_role == 'warehouse'): ?>
                            <i class="fas fa-warehouse"></i> Dashboard Gudang
                        <?php elseif ($user_role == 'requester'): ?>
                            <i class="fas fa-file-alt"></i> Dashboard Pengajuan Saya
                        <?php elseif ($user_role == 'purchasing'): ?>
                            <i class="fas fa-shopping-cart"></i> Dashboard Purchasing
                        <?php else: ?>
                            <i class="fas fa-tachometer-alt"></i> Dashboard Overview
                        <?php endif; ?>
                    </h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <?php if ($user_role == 'manager'): ?>
                <!-- ========================================== -->
                <!-- DASHBOARD KHUSUS MANAGER -->
                <!-- ========================================== -->
                
                <div class="welcome-banner position-relative">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2>Selamat datang, <?= htmlspecialchars($user_name) ?>! 👋</h2>
                            <p class="mb-0 mt-2" style="opacity: 0.9;">
                                <?php if ($mgr_pending > 0): ?>
                                    Anda memiliki <strong class="text-warning"><?= $mgr_pending ?> request</strong> yang menunggu persetujuan.
                                    <?php if ($urgent_count > 0): ?>
                                        <span class="badge badge-danger ml-2"><?= $urgent_count ?> urgent!</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Tidak ada request yang menunggu. Kerjaan Anda aman hari ini! ✨
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-right d-none d-md-block">
                            <i class="fas fa-clipboard-check greeting-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- Statistik Utama -->
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="approval.php" style="text-decoration: none; color: inherit;">
                            <div class="card stat-card" style="cursor: pointer;">
                                <div class="card-body">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #ffc107, #ff9800);">
                                        <i class="fas fa-hourglass-half"></i>
                                    </div>
                                    <h3 class="stat-number text-warning"><?= $mgr_pending ?></h3>
                                    <p class="stat-label">Menunggu Approval</p>
                                    <?php if ($mgr_pending > 0): ?>
                                        <small class="text-danger"><i class="fas fa-arrow-up"></i> Perlu tindakan</small>
                                    <?php else: ?>
                                        <small class="text-muted"><i class="fas fa-info-circle"></i> Belum ada request</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="purchase_request.php?filter=approved" style="text-decoration: none; color: inherit;">
                            <div class="card stat-card" style="cursor: pointer;">
                                <div class="card-body">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <h3 class="stat-number text-success"><?= $mgr_approved_month ?></h3>
                                    <p class="stat-label">Disetujui Bulan Ini</p>
                                    <?php if ($mgr_approved_month > 0): ?>
                                        <small class="text-success"><i class="fas fa-check"></i> Lihat detail</small>
                                    <?php else: ?>
                                        <small class="text-muted"><i class="fas fa-calendar"></i> <?= date('M Y') ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="purchase_request.php?filter=rejected" style="text-decoration: none; color: inherit;">
                            <div class="card stat-card" style="cursor: pointer;">
                                <div class="card-body">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                                        <i class="fas fa-times-circle"></i>
                                    </div>
                                    <h3 class="stat-number text-danger"><?= $mgr_rejected_month ?></h3>
                                    <p class="stat-label">Ditolak Bulan Ini</p>
                                    <?php if ($mgr_rejected_month > 0): ?>
                                        <small class="text-danger"><i class="fas fa-ban"></i> Lihat detail</small>
                                    <?php else: ?>
                                        <small class="text-muted"><i class="fas fa-ban"></i> Tidak ada</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="laporan.php?filter=approved" style="text-decoration: none; color: inherit;">
                            <div class="card stat-card" style="cursor: pointer;">
                                <div class="card-body">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <h3 class="stat-number text-info" style="font-size: 24px;">Rp <?= number_format($mgr_total_value/1000000, 1) ?>jt</h3>
                                    <p class="stat-label">Nilai Disetujui</p>
                                    <small class="text-info"><i class="fas fa-chart-line"></i> Lihat laporan detail</small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Approval Rate & Chart -->
                <div class="row">
                    <div class="col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-dark text-white">
                                <h3 class="card-title mb-0"><i class="fas fa-percentage"></i> Approval Rate</h3>
                            </div>
                            <div class="card-body text-center">
                                <div class="rate-circle" style="--rate: <?= $mgr_approval_rate ?>%;">
                                    <span class="text-success"><?= $mgr_approval_rate ?>%</span>
                                </div>
                                <h5 class="mt-3 mb-1">Tingkat Persetujuan</h5>
                                <p class="text-muted mb-0">
                                    <?= $mgr_approved_month ?> disetujui dari <?= $mgr_total_month ?> total review
                                </p>
                                <?php if ($mgr_approval_rate >= 80): ?>
                                    <span class="badge badge-success mt-2"><i class="fas fa-thumbs-up"></i> Sangat Baik</span>
                                <?php elseif ($mgr_approval_rate >= 60): ?>
                                    <span class="badge badge-warning mt-2"><i class="fas fa-hand-paper"></i> Cukup</span>
                                <?php elseif ($mgr_total_month > 0): ?>
                                    <span class="badge badge-danger mt-2"><i class="fas fa-exclamation"></i> Perlu Review</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-dark text-white">
                                <h3 class="card-title mb-0"><i class="fas fa-chart-bar"></i> Antrian Request per Departemen</h3>
                            </div>
                            <div class="card-body">
                                <div style="height: 250px;">
                                    <canvas id="managerChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Request Urgent -->
                <div class="row">
                    <div class="col-lg-7 mb-4">
                        <div class="card urgent-card h-100">
                            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0"><i class="fas fa-exclamation-triangle"></i> Request URGENT</h3>
                                <span class="badge badge-light"><?= $urgent_count ?> items</span>
                            </div>
                            <div class="card-body p-0">
                                <?php if($urgent_count > 0): ?>
                                    <?php 
                                    mysqli_data_seek($result_urgent, 0);
                                    while($u = mysqli_fetch_assoc($result_urgent)): 
                                        $days_left = (strtotime($u['needed_date']) - time()) / 86400;
                                        $deadline_class = $days_left <= 0 ? 'deadline-today' : 'deadline-soon';
                                        $deadline_text = $days_left <= 0 ? 'HARI INI!' : ceil($days_left) . ' hari lagi';
                                    ?>
                                        <div class="urgent-item">
                                            <div class="row align-items-center">
                                                <div class="col-md-6">
                                                    <h6 class="mb-1"><strong><?= htmlspecialchars($u['title']) ?></strong></h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-hashtag"></i> <?= $u['request_number'] ?> • 
                                                        <i class="fas fa-user"></i> <?= htmlspecialchars($u['requester']) ?>
                                                    </small>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-building"></i> <?= htmlspecialchars($u['department']) ?> •
                                                        <strong class="text-dark">Rp <?= number_format($u['total_estimated_price'], 0, ',', '.') ?></strong>
                                                    </small>
                                                </div>
                                                <div class="col-md-3 text-center">
                                                    <span class="deadline-badge <?= $deadline_class ?>">
                                                        <i class="fas fa-clock"></i> <?= $deadline_text ?>
                                                    </span>
                                                    <br>
                                                    <small class="text-muted"><?= date('d M Y', strtotime($u['needed_date'])) ?></small>
                                                </div>
                                                <div class="col-md-3 text-right">
                                                    <a href="detail_request.php?id=<?= $u['id'] ?>" class="btn btn-dark btn-sm">
                                                        <i class="fas fa-eye"></i> Review
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                        <h5 class="text-success">Tidak ada request urgent!</h5>
                                        <p class="text-muted mb-0">Semua request dalam kondisi aman. Kerja bagus! 🎉</p>
                                        <a href="approval.php" class="btn btn-outline-secondary mt-3">
                                            <i class="fas fa-clipboard-check"></i> Lihat Semua Request
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline Approval Terbaru -->
                    <div class="col-lg-5 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-dark text-white">
                                <h3 class="card-title mb-0"><i class="fas fa-history"></i> Aktivitas Approval Terbaru</h3>
                            </div>
                            <div class="card-body">
                                <?php if(mysqli_num_rows($result_timeline) > 0): ?>
                                    <?php while($t = mysqli_fetch_assoc($result_timeline)): ?>
                                        <div class="timeline-item <?= $t['status'] ?>" onclick="window.location.href='detail_request.php?id=<?= $t['id'] ?>'">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong><?= htmlspecialchars($t['title']) ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <i class="fas fa-user"></i> <?= htmlspecialchars($t['requester']) ?>
                                                    </small>
                                                </div>
                                                <span class="badge badge-<?= $t['status'] == 'approved' ? 'success' : 'danger' ?>">
                                                    <?= ucfirst($t['status']) ?>
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> <?= date('d/m/Y H:i', strtotime($t['approved_at'])) ?>
                                            </small>
                                        </div>
                                    <?php endwhile; ?>
                                    <div class="text-center mt-3">
                                        <a href="purchase_request.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-list"></i> Lihat Semua
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">Belum ada aktivitas approval</p>
                                        <a href="approval.php" class="btn btn-sm btn-outline-dark mt-2">
                                            <i class="fas fa-clipboard-check"></i> Mulai Approval
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12">
                        <div class="card bg-gradient-dark text-white">
                            <div class="card-body text-center py-4">
                                <h4 class="mb-3"><i class="fas fa-bolt"></i> Aksi Cepat</h4>
                                <a href="approval.php" class="btn btn-warning quick-action-btn m-2">
                                    <i class="fas fa-clipboard-check"></i> Buka Menu Approval
                                </a>
                                <a href="purchase_request.php" class="btn btn-outline-light quick-action-btn m-2">
                                    <i class="fas fa-list"></i> Lihat Semua Request
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($user_role == 'warehouse'): ?>
                <!-- ========================================== -->
                <!-- DASHBOARD KHUSUS WAREHOUSE -->
                <!-- ========================================== -->
                <div class="row">
                    <div class="col-lg-4 col-6">
                        <a href="barang_masuk.php" style="text-decoration: none; color: inherit;">
                            <div class="info-box" style="cursor: pointer;">
                                <span class="info-box-icon bg-warning"><i class="fas fa-truck-loading"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Menunggu Terima</span>
                                    <span class="info-box-number"><?= $po_pending ?></span>
                                    <span class="progress-description">PO Pending</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-4 col-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Diterima Minggu Ini</span>
                                <span class="info-box-number"><?= $received_this_week ?></span>
                                <span class="progress-description">PO Selesai</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-6">
                        <a href="master_barang.php" style="text-decoration: none; color: inherit;">
                            <div class="info-box" style="cursor: pointer;">
                                <span class="info-box-icon bg-danger"><i class="fas fa-exclamation-triangle"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Stok Menipis</span>
                                    <span class="info-box-number"><?= $low_stock_count ?></span>
                                    <span class="progress-description">Perlu Order</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning">
                                <h3 class="card-title"><i class="fas fa-clock"></i> Pengiriman Segera Tiba (3 Hari)</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped mb-0">
                                    <thead><tr><th>No. PO</th><th>Judul</th><th>Vendor</th><th>Estimasi</th></tr></thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($result_soon) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($result_soon)): ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($row['po_number']) ?></strong></td>
                                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                                    <td><?= htmlspecialchars($row['vendor'] ?? '-') ?></td>
                                                    <td><span class="badge badge-warning"><?= date('d/m', strtotime($row['expected_delivery_date'])) ?></span></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center text-muted">Tidak ada pengiriman dalam 3 hari ke depan</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success">
                                <h3 class="card-title"><i class="fas fa-history"></i> Riwayat Penerimaan Terbaru</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped mb-0">
                                    <thead><tr><th>No. PO</th><th>Judul</th><th>Tanggal</th></tr></thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($result_recent_wh) > 0): ?>
                                            <?php while($row = mysqli_fetch_assoc($result_recent_wh)): ?>
                                                <tr>
                                                    <td><strong><?= htmlspecialchars($row['po_number']) ?></strong></td>
                                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                                    <td><span class="badge badge-success"><?= date('d/m/Y', strtotime($row['actual_delivery_date'])) ?></span></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="3" class="text-center text-muted">Belum ada barang yang diterima</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-dark text-white">
                                <h3 class="card-title"><i class="fas fa-bolt"></i> Aksi Cepat</h3>
                            </div>
                            <div class="card-body text-center">
                                <a href="barang_masuk.php" class="btn btn-primary btn-lg mr-3"><i class="fas fa-warehouse"></i> Terima Barang</a>
                                <a href="master_barang.php" class="btn btn-secondary btn-lg"><i class="fas fa-box"></i> Lihat Stok Barang</a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($user_role == 'requester'): ?>
                <!-- ========================================== -->
                <!-- DASHBOARD KHUSUS REQUESTER -->
                <!-- ========================================== -->
                <div class="row mb-3">
                    <div class="col-12 text-right">
                        <a href="tambah_request.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus-circle"></i> Buat Request Baru
                        </a>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-3 col-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-dark"><i class="fas fa-file-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Request Saya</span>
                                <span class="info-box-number"><?= $my_req_total ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Menunggu Persetujuan</span>
                                <span class="info-box-number"><?= $my_req_pending ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-success"><i class="fas fa-check-double"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Disetujui / Selesai</span>
                                <span class="info-box-number"><?= $my_req_approved ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-6">
                        <div class="info-box">
                            <span class="info-box-icon bg-danger"><i class="fas fa-times-circle"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Ditolak</span>
                                <span class="info-box-number"><?= $my_req_rejected ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title"><i class="fas fa-chart-pie"></i> Status Pengajuan Saya</h3></div>
                            <div class="card-body">
                                <div style="height: 250px;">
                                    <canvas id="requesterChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title"><i class="fas fa-history"></i> Riwayat Pengajuan Terbaru</h3></div>
                            <div class="card-body p-0">
                                <?php $my_recent = mysqli_query($koneksi, "SELECT request_number, title, status, request_date FROM purchase_requests WHERE user_id = $uid ORDER BY id DESC LIMIT 5"); ?>
                                <table class="table table-striped mb-0">
                                    <thead><tr><th>No. Request</th><th>Judul</th><th>Status</th><th>Tanggal</th></tr></thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($my_recent) > 0): ?>
                                            <?php while($r = mysqli_fetch_assoc($my_recent)): 
                                                $badge = 'secondary';
                                                if($r['status'] == 'draft') $badge = 'dark';
                                                if($r['status'] == 'submitted') $badge = 'warning';
                                                if($r['status'] == 'approved') $badge = 'success';
                                                if($r['status'] == 'rejected') $badge = 'danger';
                                                if($r['status'] == 'ordered') $badge = 'info';
                                                if($r['status'] == 'completed') $badge = 'primary';
                                            ?>
                                            <tr>
                                                <td><small><?= $r['request_number'] ?></small></td>
                                                <td><strong><?= htmlspecialchars($r['title']) ?></strong></td>
                                                <td><span class="badge badge-<?= $badge ?>"><?= ucfirst($r['status']) ?></span></td>
                                                <td><small><?= date('d/m/Y', strtotime($r['request_date'])) ?></small></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada request. Klik tombol "Buat Request Baru" di atas!</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($user_role == 'purchasing'): ?>
                <!-- ========================================== -->
                <!-- DASHBOARD KHUSUS PURCHASING -->
                <!-- ========================================== -->
                <?php
                $req_need_po = mysqli_query($koneksi, "SELECT pr.id, pr.request_number, pr.title, pr.total_estimated_price, u.name as requester, pr.department 
                                                        FROM purchase_requests pr 
                                                        JOIN users u ON pr.user_id = u.id 
                                                        WHERE pr.status = 'approved' AND pr.id NOT IN (SELECT purchase_request_id FROM purchase_orders WHERE purchase_request_id IS NOT NULL)
                                                        ORDER BY pr.id DESC LIMIT 5");
                
                $po_active = mysqli_query($koneksi, "SELECT po.*, pr.title, pr.request_number, u.name as requester 
                                                       FROM purchase_orders po 
                                                       JOIN purchase_requests pr ON po.purchase_request_id = pr.id 
                                                       JOIN users u ON pr.user_id = u.id 
                                                       WHERE po.status IN ('pending', 'shipped')
                                                       ORDER BY po.order_date ASC LIMIT 8");
                ?>

                <!-- Welcome Banner -->
                <div class="welcome-banner position-relative">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2>Selamat datang, <?= htmlspecialchars($user_name) ?>! 👋</h2>
                            <p class="mb-0 mt-2" style="opacity: 0.9;">
                                <?php if ($need_po > 0): ?>
                                    Ada <strong class="text-warning"><?= $need_po ?> request</strong> yang perlu dibuatkan PO.
                                <?php elseif ($po_pending_pur > 0): ?>
                                    Ada <strong class="text-info"><?= $po_pending_pur ?> PO</strong> menunggu pengiriman vendor.
                                    <?php if ($po_pending_lama > 0): ?>
                                        <span class="badge badge-danger ml-2"><?= $po_pending_lama ?> terlambat!</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    Tidak ada PO yang perlu diproses. Kerjaan Anda aman hari ini! ✨
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-4 text-right d-none d-md-block">
                            <i class="fas fa-shopping-cart greeting-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- Statistik Utama -->
                <div class="row">
                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="purchase_order.php" style="text-decoration: none; color: inherit;">
                            <div class="card stat-card" style="cursor: pointer;">
                                <div class="card-body">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #ffc107, #ff9800);">
                                        <i class="fas fa-hourglass-half"></i>
                                    </div>
                                    <h3 class="stat-number text-warning"><?= $po_pending_pur ?></h3>
                                    <p class="stat-label">PO Pending</p>
                                    <?php if ($po_pending_lama > 0): ?>
                                        <small class="text-danger"><i class="fas fa-exclamation-triangle"></i> <?= $po_pending_lama ?> terlambat</small>
                                    <?php else: ?>
                                        <small class="text-muted"><i class="fas fa-clock"></i> Menunggu vendor</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="purchase_order.php" style="text-decoration: none; color: inherit;">
                            <div class="card stat-card" style="cursor: pointer;">
                                <div class="card-body">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #17a2b8, #138496);">
                                        <i class="fas fa-truck"></i>
                                    </div>
                                    <h3 class="stat-number text-info"><?= $po_shipped_pur ?></h3>
                                    <p class="stat-label">Dalam Pengiriman</p>
                                    <small class="text-info"><i class="fas fa-shipping-fast"></i> On the way</small>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="card stat-card">
                            <div class="card-body">
                                <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3 class="stat-number text-success"><?= $po_received_pur ?></h3>
                                <p class="stat-label">Diterima Gudang</p>
                                <small class="text-success"><i class="fas fa-check"></i> Selesai</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="laporan.php" style="text-decoration: none; color: inherit;">
                            <div class="card stat-card" style="cursor: pointer;">
                                <div class="card-body">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #6f42c1, #5a32a3);">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <h3 class="stat-number text-purple" style="font-size: 24px;">Rp <?= number_format($po_value_month/1000000, 1) ?>jt</h3>
                                    <p class="stat-label">Total PO Bulan Ini</p>
                                    <small class="text-muted"><i class="fas fa-calendar"></i> <?= date('M Y') ?></small>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- PO Perlu Dibuat -->
                    <div class="col-lg-5 mb-4">
                        <div class="card h-100 <?= $need_po > 0 ? 'border-warning' : '' ?>">
                            <div class="card-header <?= $need_po > 0 ? 'bg-warning' : 'bg-dark' ?> text-white d-flex justify-content-between align-items-center">
                                <h3 class="card-title mb-0"><i class="fas fa-exclamation-circle"></i> Perlu Dibuatkan PO</h3>
                                <?php if ($need_po > 0): ?>
                                    <span class="badge badge-light"><?= $need_po ?> request</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-0">
                                <?php if(mysqli_num_rows($req_need_po) > 0): ?>
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr><th>No. Request</th><th>Judul</th><th>Pemohon</th><th>Nilai</th><th>Aksi</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php while($r = mysqli_fetch_assoc($req_need_po)): ?>
                                                <tr>
                                                    <td><small><?= $r['request_number'] ?></small></td>
                                                    <td><strong><?= htmlspecialchars($r['title']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($r['department']) ?></small></td>
                                                    <td><?= htmlspecialchars($r['requester']) ?></td>
                                                    <td><strong>Rp <?= number_format($r['total_estimated_price'], 0, ',', '.') ?></strong></td>
                                                    <td>
                                                        <a href="purchase_order.php?action=create_po&id=<?= $r['id'] ?>" class="btn btn-sm btn-primary" onclick="return confirm('Buat PO untuk request ini?')">
                                                            <i class="fas fa-cart-plus"></i> Buat PO
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                        <h5 class="text-success">Semua request sudah ada PO!</h5>
                                        <p class="text-muted mb-0">Tidak ada request approved yang menunggu PO.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- PO Aktif (Pending & Shipped) -->
                    <div class="col-lg-7 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-dark text-white">
                                <h3 class="card-title mb-0"><i class="fas fa-list"></i> PO Aktif (Pending & Shipped)</h3>
                            </div>
                            <div class="card-body p-0">
                                <?php if(mysqli_num_rows($po_active) > 0): ?>
                                    <table class="table table-striped mb-0">
                                        <thead>
                                            <tr><th>PO Number</th><th>Judul</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php while($p = mysqli_fetch_assoc($po_active)): 
                                                $badge = $p['status'] == 'pending' ? 'warning' : 'info';
                                            ?>
                                                <tr>
                                                    <td><strong><?= $p['po_number'] ?></strong></td>
                                                    <td><small><?= htmlspecialchars($p['title']) ?></small></td>
                                                    <td><small><?= date('d/m', strtotime($p['order_date'])) ?></small></td>
                                                    <td><span class="badge badge-<?= $badge ?>"><?= ucfirst($p['status']) ?></span></td>
                                                    <td>
                                                        <?php if($p['status'] == 'pending'): ?>
                                                            <a href="purchase_order.php?action=shipped&id=<?= $p['id'] ?>" class="btn btn-sm btn-info" onclick="return confirm('Vendor sudah kirim barang ini?')">
                                                                <i class="fas fa-truck"></i> Shipped
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-info"><i class="fas fa-truck-moving"></i> On Delivery</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-2"></i>
                                        <p class="text-muted mb-0">Tidak ada PO aktif</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer text-center">
                                <a href="purchase_order.php" class="btn btn-sm btn-outline-dark"><i class="fas fa-list"></i> Lihat Semua PO</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12">
                        <div class="card bg-gradient-dark text-white">
                            <div class="card-body text-center py-4">
                                <h4 class="mb-3"><i class="fas fa-bolt"></i> Aksi Cepat Purchasing</h4>
                                <a href="purchase_order.php" class="btn btn-primary quick-action-btn m-2">
                                    <i class="fas fa-shopping-cart"></i> Kelola Purchase Order
                                </a>
                                <a href="master_barang.php" class="btn btn-outline-light quick-action-btn m-2">
                                    <i class="fas fa-box"></i> Lihat Stok Barang
                                </a>
                                <a href="laporan.php" class="btn btn-outline-light quick-action-btn m-2">
                                    <i class="fas fa-chart-line"></i> Laporan Pengadaan
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- ========================================== -->
                <!-- DASHBOARD UMUM (Admin) -->
                <!-- ========================================== -->
                <div class="row">
                    <div class="col-lg-3 col-6">
                        <a href="purchase_request.php" style="text-decoration: none; color: inherit;">
                            <div class="info-box" style="cursor: pointer;">
                                <span class="info-box-icon"><i class="fas fa-file-alt"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Request</span>
                                    <span class="info-box-number"><?= $stat_req ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="approval.php" style="text-decoration: none; color: inherit;">
                            <div class="info-box" style="cursor: pointer;">
                                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Menunggu Approve</span>
                                    <span class="info-box-number"><?= $stat_pending ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="purchase_order.php" style="text-decoration: none; color: inherit;">
                            <div class="info-box" style="cursor: pointer;">
                                <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total PO</span>
                                    <span class="info-box-number"><?= $stat_po ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-3 col-6">
                        <a href="master_barang.php" style="text-decoration: none; color: inherit;">
                            <div class="info-box" style="cursor: pointer;">
                                <span class="info-box-icon"><i class="fas fa-box"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Barang</span>
                                    <span class="info-box-number"><?= $stat_barang ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Statistik Pengajuan</h3></div>
                            <div class="card-body">
                                <canvas id="statusChart" style="max-height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card" style="border-color: #dc3545;">
                            <div class="card-header" style="background-color: #dc3545;">
                                <h3 class="card-title"><i class="fas fa-exclamation-triangle"></i> Stok Menipis</h3>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-striped mb-0">
                                    <thead><tr><th>Barang</th><th>Stok</th></tr></thead>
                                    <tbody>
                                        <?php if(mysqli_num_rows($low_stock) > 0): ?>
                                            <?php while($s = mysqli_fetch_assoc($low_stock)): ?>
                                                <tr><td><?= $s['nama_barang'] ?></td><td class="text-danger"><?= $s['stok'] ?></td></tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="2" class="text-center">Stok aman! 👍</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">Aktivitas Terbaru</h3></div>
                            <div class="card-body">
                                <table class="table table-bordered">
                                    <thead><tr><th>No. Request</th><th>Judul</th><th>Pemohon</th><th>Status</th></tr></thead>
                                    <tbody>
                                        <?php while($r = mysqli_fetch_assoc($recent)): ?>
                                            <tr>
                                                <td><?= $r['request_number'] ?></td>
                                                <td><?= $r['title'] ?></td>
                                                <td><?= $r['requester_name'] ?></td>
                                                <td><span class="badge badge-warning"><?= $r['status'] ?></span></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </section>
</div>

<?php include '../includes/footer.php'; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Grafik untuk Dashboard Umum (Admin)
var ctx = document.getElementById('statusChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Total Request', 'Pending', 'Approved', 'Total PO'],
            datasets: [{
                label: 'Jumlah',
                data: [<?= $stat_req ?>, <?= $stat_pending ?>, <?= $stat_approved ?>, <?= $stat_po ?>],
                backgroundColor: ['#000000', '#ffc107', '#28a745', '#17a2b8']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
    });
}

// Grafik untuk Dashboard Requester
var ctxReq = document.getElementById('requesterChart');
if (ctxReq) {
    new Chart(ctxReq, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($my_labels) ?>,
            datasets: [{
                data: <?= json_encode($my_data) ?>,
                backgroundColor: ['#343a40', '#ffc107', '#28a745', '#dc3545', '#17a2b8', '#007bff'],
                borderWidth: 2
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
    });
}

// Grafik untuk Dashboard Manager (Antrian per Departemen)
var ctxMgr = document.getElementById('managerChart');
if (ctxMgr) {
    new Chart(ctxMgr, {
        type: 'bar',
        data: {
            labels: <?= json_encode($mgr_dept_labels) ?>,
            datasets: [{
                label: 'Jumlah Request Pending',
                data: <?= json_encode($mgr_dept_data) ?>,
                backgroundColor: 'rgba(255, 193, 7, 0.8)',
                borderColor: '#ffc107',
                borderWidth: 2,
                borderRadius: 8
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { 
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            }
        }
    });
}
</script>