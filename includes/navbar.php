<nav class="main-header navbar navbar-expand navbar-dark" style="background-color: #000000 !important; border-bottom: 2px solid #ffffff;">
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="dashboard.php" class="nav-link">Home</a>
        </li>
    </ul>
    <ul class="navbar-nav ml-auto">
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-user-circle fa-2x"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <span class="dropdown-item dropdown-header"><?= htmlspecialchars($_SESSION['user_role']) ?> - <?= htmlspecialchars($_SESSION['user_department']) ?></span>
                <div class="dropdown-divider"></div>
                <a href="logout.php" class="dropdown-item">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </li>
    </ul>
</nav>