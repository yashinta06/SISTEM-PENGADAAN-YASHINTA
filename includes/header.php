<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $page_title ?? 'UAS Pengadaan' ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; }
        .main-header { background-color: #000000 !important; border-bottom: 2px solid #ffffff; }
        .main-sidebar { background-color: #1a1a1a !important; border-right: 2px solid #000000; }
        .nav-sidebar > .nav-item > .nav-link { color: #ffffff; }
        .nav-sidebar > .nav-item > .nav-link:hover { background-color: #ffffff; color: #000000; }
        .nav-sidebar > .nav-item > .nav-link.active { background-color: #000000; color: #ffffff; border: 1px solid #ffffff; }
        .brand-link { border-bottom: 2px solid #ffffff; }
        .brand-text { color: #ffffff !important; font-weight: bold; }
        .card { border: 2px solid #000000; border-radius: 8px; }
        .card-header { background-color: #000000; color: #ffffff; border-bottom: 2px solid #ffffff; font-weight: bold; }
        .btn-primary { background-color: #000000 !important; border-color: #000000 !important; }
        .btn-primary:hover { background-color: #ffffff !important; color: #000000 !important; }
        .btn-danger { background-color: #dc3545 !important; }
        .btn-warning { background-color: #ffc107 !important; color: #000; }
        .btn-info { background-color: #17a2b8 !important; color: #fff; }
        .btn-success { background-color: #28a745 !important; color: #fff; }
        .table thead th { background-color: #000000; color: #ffffff; }
        .info-box { border: 2px solid #000000; border-radius: 8px; }
        .info-box-icon { background-color: #000000 !important; color: #ffffff; }
        .main-footer { background-color: #000000; color: #ffffff; border-top: 2px solid #ffffff; }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <?php include 'navbar.php'; ?>
    <?php include 'sidebar.php'; ?>