<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <a href="dashboard.php" class="brand-link"><span class="brand-text pl-3">UAS Pengadaan</span></a>
    <div class="sidebar">
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image"><i class="fas fa-user-circle fa-2x text-white"></i></div>
            <div class="info">
                <a href="#" class="d-block"><?= htmlspecialchars($_SESSION['user_name']) ?></a>
                <small class="text-muted"><?= htmlspecialchars($_SESSION['user_department']) ?></small>
            </div>
        </div>
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i><p>Dashboard</p>
                    </a>
                </li>

                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                <li class="nav-item">
                    <a href="manajemen_user.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'manajemen_user.php' || basename($_SERVER['PHP_SELF']) == 'tambah_user.php' || basename($_SERVER['PHP_SELF']) == 'edit_user.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users"></i><p>Manajemen User</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="master_barang.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['master_barang.php','tambah_barang.php','edit_barang.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-box"></i><p>Master Barang</p>
                    </a>
                </li>
                <!-- Menu Vendor (Admin & Purchasing) -->
                <li class="nav-item">
                    <a href="vendor.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['vendor.php','tambah_vendor.php','edit_vendor.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-truck"></i><p>Vendor</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="audit_log.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'audit_log.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-history"></i><p>Audit Log</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="laporan.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'laporan.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-file-invoice-dollar"></i><p>Laporan</p>
                    </a>
                </li>
                <!-- Menu Anggaran -->
                <li class="nav-item">
                    <a href="anggaran.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'anggaran.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-chart-pie"></i>
                        <p>Anggaran</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array($_SESSION['user_role'], ['admin', 'requester'])): ?>
                <li class="nav-item">
                    <a href="purchase_request.php" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF']), ['purchase_request.php','tambah_request.php']) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-file-alt"></i><p>Purchase Request</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array($_SESSION['user_role'], ['admin', 'manager'])): ?>
                <li class="nav-item">
                    <a href="approval.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'approval.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-check-circle"></i><p>Approval</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array($_SESSION['user_role'], ['admin', 'purchasing'])): ?>
                <li class="nav-item">
                    <a href="purchase_order.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'purchase_order.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-shopping-cart"></i><p>Purchase Order</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (in_array($_SESSION['user_role'], ['admin', 'warehouse'])): ?>
                <li class="nav-item">
                    <a href="barang_masuk.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'barang_masuk.php' ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-warehouse"></i><p>Barang Masuk</p>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
    <a href="logout.php" class="nav-link">
        <i class="nav-icon fas fa-sign-out-alt"></i><p>Logout</p>
    </a>
</li>
            </ul>
        </nav>
    </div>
</aside>