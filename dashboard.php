<?php
declare(strict_types=1);
ob_start();

require_once __DIR__ . '/inc/auth.php';
require_once __DIR__ . '/inc/functions.php';
requireLogin();

$userName = getCurrentUserName();
$userEmail = getCurrentUserEmail();
$userRole = getCurrentUserRole();
$membershipLevel = getCurrentUserMembership();
$points = getCurrentUserPoints();

$page = $_GET['page'] ?? 'home';

define('GAMEZONE_ACCESS', true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameZone Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css">
    <link rel="stylesheet" href="assets/css/adminlte.css">
    <link rel="stylesheet" href="assets/css/gamezone.css">
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <nav class="app-header navbar navbar-expand bg-body">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                            <i class="bi bi-list"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="index.php" class="nav-link">GameZone</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link" data-bs-toggle="dropdown" href="#" role="button">
                            <i class="bi bi-bell"></i>
                            <span class="badge bg-danger rounded-pill navbar-badge" id="notif-count">0</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                            <span class="dropdown-item dropdown-header" id="notif-header">0 Notifications</span>
                            <div class="dropdown-divider"></div>
                            <a href="dashboard.php?page=notifications" class="dropdown-item dropdown-footer">See All Notifications</a>
                        </div>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link" data-bs-toggle="dropdown" href="#" role="button">
                            <i class="bi bi-person-circle"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <span class="dropdown-item dropdown-header">
                                <strong><?= htmlspecialchars($userName) ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($userEmail) ?></small>
                            </span>
                            <div class="dropdown-divider"></div>
                            <span class="dropdown-item">
                                <strong>Role:</strong> 
                                <span class="badge bg-<?= $userRole === 'admin' ? 'danger' : ($userRole === 'staff' ? 'warning text-dark' : 'success') ?>">
                                    <?= ucfirst($userRole) ?>
                                </span>
                            </span>
                            <span class="dropdown-item">
                                <strong>Membership:</strong> <?= getMembershipLevelBadge($membershipLevel) ?>
                            </span>
                            <span class="dropdown-item">
                                <strong>Points:</strong> <?= number_format($points, 0, ',', '.') ?>
                            </span>
                            <div class="dropdown-divider"></div>
                            <a href="dashboard.php?page=profile" class="dropdown-item"><i class="bi bi-gear me-2"></i> Profile Settings</a>
                            <a href="logout.php" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
        
        <?php include __DIR__ . '/templates/sidebar.php'; ?>

        <main class="app-main">
            <div class="app-content-header">
                <div class="container-fluid">
                    <?php
                    $flash = getFlashMessage();
                    if ($flash): ?>
                        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                            <?= htmlspecialchars($flash['text']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-sm-6">
                            <h3 class="mb-0">
                                <?php
                                $titles = [
                                    'home' => 'Dashboard',
                                    'users' => 'User Management',
                                    'devices' => 'Device Management',
                                    'devices_create' => 'Add Device',
                                    'devices_edit' => 'Edit Device',
                                    'bookings' => 'Booking Management',
                                    'bookings_create' => 'New Booking',
                                    'bookings_view' => 'Booking Details',
                                    'payments' => 'Payment Management',
                                    'payments_verify' => 'Verify Payment',
                                    'tournaments' => 'Tournaments',
                                    'promotions' => 'Promotions',
                                    'reports' => 'Reports',
                                    'settings' => 'System Settings',
                                    'profile' => 'Profile Settings',
                                    'notifications' => 'Notifications',
                                ];
                                echo $titles[$page] ?? 'Dashboard';
                                ?>
                            </h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end">
                                <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                                <li class="breadcrumb-item active"><?= $titles[$page] ?? 'Page' ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="app-content">
                <div class="container-fluid">
                    <?php
                    try {
                        switch (true) {
                            case $page === 'home':
                                include __DIR__ . '/modules/home.php';
                                break;
                            
                            case $page === 'users':
                                requireAdmin();
                                include __DIR__ . '/modules/users/index.php';
                                break;
                            
                            case $page === 'users_create':
                                requireAdmin();
                                include __DIR__ . '/modules/users/create.php';
                                break;
                            
                            case $page === 'users_edit':
                                requireAdmin();
                                include __DIR__ . '/modules/users/edit.php';
                                break;
                            
                            case $page === 'users_delete':
                                requireAdmin();
                                include __DIR__ . '/modules/users/delete.php';
                                break;
                            
                            case $page === 'devices':
                                include __DIR__ . '/modules/devices/index.php';
                                break;
                            
                            case $page === 'devices_create':
                                requireStaffOrAdmin();
                                include __DIR__ . '/modules/devices/create.php';
                                break;
                            
                            case $page === 'devices_edit':
                                requireStaffOrAdmin();
                                include __DIR__ . '/modules/devices/edit.php';
                                break;
                            
                            case $page === 'devices_delete':
                                requireAdmin();
                                include __DIR__ . '/modules/devices/delete.php';
                                break;
                            
                            case $page === 'bookings':
                                include __DIR__ . '/modules/bookings/index.php';
                                break;
                            
                            case $page === 'bookings_create':
                                include __DIR__ . '/modules/bookings/create.php';
                                break;
                            
                            case $page === 'bookings_view':
                                include __DIR__ . '/modules/bookings/view.php';
                                break;
                            
                            case $page === 'bookings_cancel':
                                include __DIR__ . '/modules/bookings/cancel.php';
                                break;
                            
                            case $page === 'payments':
                                requireStaffOrAdmin();
                                include __DIR__ . '/modules/payments/index.php';
                                break;
                            
                            case $page === 'payments_verify':
                                requireStaffOrAdmin();
                                include __DIR__ . '/modules/payments/verify.php';
                                break;
                            
                            case $page === 'profile':
                                include __DIR__ . '/modules/profile.php';
                                break;
                            
                            case $page === 'tournaments':
                                requireStaffOrAdmin();
                                include __DIR__ . '/modules/tournaments/index.php';
                                break;
                            
                            case $page === 'promotions':
                                requireAdmin();
                                include __DIR__ . '/modules/promotions/index.php';
                                break;
                            
                            case $page === 'reports':
                                requireAdmin();
                                include __DIR__ . '/modules/reports/index.php';
                                break;
                            
                            case $page === 'settings':
                                requireAdmin();
                                include __DIR__ . '/modules/settings/index.php';
                                break;
                            
                            case $page === 'notifications':
                                include __DIR__ . '/modules/notifications.php';
                                break;
                            
                            default:
                                echo '<div class="alert alert-warning">Page not found.</div>';
                        }
                    } catch (Throwable $e) {
                        echo '<div class="alert alert-danger">
                            <h5><i class="bi bi-exclamation-octagon me-2"></i>Error</h5>
                            <p>' . htmlspecialchars($e->getMessage()) . '</p>
                        </div>';
                    }
                    ?>
                </div>
            </div>
        </main>
        
        <footer class="app-footer">
            <div class="float-end d-none d-sm-inline">Gaming Room Booking System</div>
            <strong>&copy; 2025 GameZone</strong> All rights reserved.
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
    <script src="assets/js/adminlte.js"></script>
    <script>
        const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
        const Default = {
            scrollbarTheme: 'os-theme-light',
            scrollbarAutoHide: 'leave',
            scrollbarClickScroll: true,
        };
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
            const isMobile = window.innerWidth <= 992;
            if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined && !isMobile) {
                OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                    scrollbars: {
                        theme: Default.scrollbarTheme,
                        autoHide: Default.scrollbarAutoHide,
                        clickScroll: Default.scrollbarClickScroll,
                    },
                });
            }
        });
    </script>
</body>
</html>