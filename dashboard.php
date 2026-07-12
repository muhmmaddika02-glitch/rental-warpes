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
$page = preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['page'] ?? 'home');
define('GAMEZONE_ACCESS', true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        flashMessage('Permintaan tidak valid. Coba lagi.', 'danger');
        header('Location: dashboard.php?page=' . $page);
        exit;
    }
}

function renderPagination(int $currentPage, int $totalPages, string $baseUrl): string {
    if ($totalPages <= 1) return '';
    $html = '<nav><ul class="pagination pagination-sm justify-content-center mt-3" style="margin:0">';
    $prev = max(1, $currentPage - 1);
        $html .= '<li class="page-item ' . ($currentPage <= 1 ? 'disabled' : '') . '"><a class="page-link" href="' . $baseUrl . '&p=' . $prev . '">‹</a></li>';
        for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++) {
            $html .= '<li class="page-item ' . ($i === $currentPage ? 'active' : '') . '"><a class="page-link" href="' . $baseUrl . '&p=' . $i . '">' . $i . '</a></li>';
        }
        $next = min($totalPages, $currentPage + 1);
        $html .= '<li class="page-item ' . ($currentPage >= $totalPages ? 'disabled' : '') . '"><a class="page-link" href="' . $baseUrl . '&p=' . $next . '">›</a></li>';
    $html .= '</ul></nav>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GameZone Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css">
    <link rel="stylesheet" href="assets/css/adminlte.css">
    <link rel="stylesheet" href="assets/css/gamezone.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body class="layout-fixed sidebar-expand-lg" style="min-height: 100vh; background: var(--bg-deep);">
    <div class="app-wrapper">
        <!-- Navbar -->
        <nav class="app-header navbar navbar-expand" style="background: rgba(13,2,33,0.8); backdrop-filter: blur(24px); border-bottom: 1px solid rgba(255,79,216,0.12);">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" style="color: var(--text-primary);">
                            <i class="bi bi-list fs-4"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-block">
                        <a href="index.php" class="nav-link" style="color: var(--text-primary); font-weight: 800; font-size: 1.1rem;">
                            <span style="color: var(--neon-pink);">Game</span><span style="color: var(--neon-cyan);">Zone</span>
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item me-2">
                        <span class="nav-link" style="color: var(--text-secondary); font-size: 0.8rem;">
                            <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($userName) ?>
                        </span>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link position-relative" data-bs-toggle="dropdown" href="#" role="button" style="color: var(--text-primary);">
                            <i class="bi bi-bell fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill px-2 py-1" style="background: var(--neon-pink); font-size: 0.6rem; display: none;" id="notif-count">0</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                            <span class="dropdown-item dropdown-header" style="color: var(--text-secondary); font-weight: 600; border-bottom: 1px solid rgba(255,79,216,0.1);" id="notif-header">0 Notifications</span>
                            <div class="dropdown-divider"></div>
                            <a href="dashboard.php?page=notifications" class="dropdown-item dropdown-footer" style="color: var(--neon-pink); font-weight: 600; text-align: center;">See All Notifications →</a>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <?php include __DIR__ . '/templates/sidebar.php'; ?>

        <main class="app-main">
            <div class="app-content-header" style="border-bottom: 1px solid rgba(255,255,255,0.05); background: transparent;">
                <div class="container-fluid">
                    <?php
                    $flash = getFlashMessage();
                    if ($flash): ?>
                        <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show d-flex align-items-center gap-2" role="alert" style="background: <?= $flash['type'] === 'success' ? 'rgba(34,197,94,0.12)' : 'rgba(255,59,107,0.12)' ?>; border-color: <?= $flash['type'] === 'success' ? 'rgba(34,197,94,0.3)' : 'rgba(255,59,107,0.3)' ?>; color: <?= $flash['type'] === 'success' ? '#bbf7d0' : '#fecaca' ?>;">
                            <i class="bi <?= $flash['type'] === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?>"></i>
                            <?= htmlspecialchars($flash['text']) ?>
                            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close" style="filter: invert(0.8) brightness(1.5);"></button>
                        </div>
                    <?php endif; ?>

                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <h3 class="mb-0" style="color: var(--text-primary); font-weight: 800; font-size: 1.75rem; letter-spacing: -0.02em;">
                                <?php
                                $titles = [
                                    'home' => 'Dashboard', 'users' => 'User Management',
                                    'devices' => 'Device Management', 'devices_create' => 'Add Device',
                                    'devices_edit' => 'Edit Device', 'bookings' => 'Booking Management',
                                    'bookings_create' => 'New Booking', 'bookings_view' => 'Booking Details',
                                    'payments' => 'Payment Management', 'payments_verify' => 'Verify Payment',
                                    'tournaments' => 'Tournaments', 'tournaments_create' => 'Create Tournament',
                                    'tournaments_edit' => 'Edit Tournament', 'tournaments_view' => 'Tournament Details',
                                    'tournaments_register' => 'Register for Tournament',
                                    'promotions' => 'Promotions', 'promotions_create' => 'Create Promotion',
                                    'promotions_edit' => 'Edit Promotion', 'promotions_view' => 'Promotion Details',
                                    'reports' => 'Reports', 'settings' => 'System Settings',
                                    'profile' => 'Profile Settings',                                     'notifications' => 'Notifications',
                                    'wallet' => 'My Wallet',
                                ];
                                echo $titles[$page] ?? 'Dashboard';
                                ?>
                            </h3>
                        </div>
                        <div class="col-sm-6">
                            <ol class="breadcrumb float-sm-end mb-0" style="background: transparent; padding: 0.5rem 0;">
                                <li class="breadcrumb-item"><a href="dashboard.php" style="color: var(--neon-pink); text-decoration: none; text-shadow: 0 0 10px rgba(255,79,216,0.2);">Home</a></li>
                                <li class="breadcrumb-item active" style="color: var(--text-muted);"><?= $titles[$page] ?? 'Page' ?></li>
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
                            case $page === 'home': include __DIR__ . '/modules/home.php'; break;
                            case $page === 'users': requireAdmin(); include __DIR__ . '/modules/users/index.php'; break;
                            case $page === 'users_create': requireAdmin(); include __DIR__ . '/modules/users/create.php'; break;
                            case $page === 'users_edit': requireAdmin(); include __DIR__ . '/modules/users/edit.php'; break;
                            case $page === 'users_delete': requireAdmin(); include __DIR__ . '/modules/users/delete.php'; break;
                            case $page === 'devices': include __DIR__ . '/modules/devices/index.php'; break;
                            case $page === 'devices_create': requireStaffOrAdmin(); include __DIR__ . '/modules/devices/create.php'; break;
                            case $page === 'devices_edit': requireStaffOrAdmin(); include __DIR__ . '/modules/devices/edit.php'; break;
                            case $page === 'devices_delete': requireAdmin(); include __DIR__ . '/modules/devices/delete.php'; break;
                            case $page === 'bookings': include __DIR__ . '/modules/bookings/index.php'; break;
                            case $page === 'bookings_create': include __DIR__ . '/modules/bookings/create.php'; break;
                            case $page === 'bookings_view': include __DIR__ . '/modules/bookings/view.php'; break;
                            case $page === 'bookings_cancel': include __DIR__ . '/modules/bookings/cancel.php'; break;
                            case $page === 'payments': requireStaffOrAdmin(); include __DIR__ . '/modules/payments/index.php'; break;
                            case $page === 'payments_verify': requireStaffOrAdmin(); include __DIR__ . '/modules/payments/verify.php'; break;
                            case $page === 'profile': include __DIR__ . '/modules/profile.php'; break;
                            case $page === 'devices_toggle_maintenance':
                                requireStaffOrAdmin();
                                if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: dashboard.php?page=devices'); exit; }
                                $did = (int)($_POST['id']??0);
                                if ($did > 0) {
                                    $st = $pdo->prepare("SELECT status FROM devices WHERE id=?");
                                    $st->execute([$did]);
                                    $d = $st->fetch();
                                    if ($d && in_array($d['status'], ['available', 'maintenance'], true)) {
                                        $newStatus = $d['status'] === 'maintenance' ? 'available' : 'maintenance';
                                        $pdo->prepare("UPDATE devices SET status=? WHERE id=?")->execute([$newStatus, $did]);
                                        flashMessage('Device status set to ' . $newStatus . '.', 'success');
                                    } else {
                                        flashMessage('Device sedang digunakan (booked/playing) dan tidak dapat diubah.', 'danger');
                                    }
                                }
                                header('Location: dashboard.php?page=devices'); exit;
                            case $page === 'tournaments': include __DIR__ . '/modules/tournaments/index.php'; break;
                            case $page === 'tournaments_create': requireStaffOrAdmin(); include __DIR__ . '/modules/tournaments/create.php'; break;
                            case $page === 'tournaments_edit': requireStaffOrAdmin(); include __DIR__ . '/modules/tournaments/edit.php'; break;
                            case $page === 'tournaments_delete': requireAdmin(); include __DIR__ . '/modules/tournaments/delete.php'; break;
                            case $page === 'tournaments_view': include __DIR__ . '/modules/tournaments/view.php'; break;
                            case $page === 'tournaments_register': include __DIR__ . '/modules/tournaments/register.php'; break;
                            case $page === 'tournaments_unregister': include __DIR__ . '/modules/tournaments/unregister.php'; break;
                            case $page === 'promotions': include __DIR__ . '/modules/promotions/index.php'; break;
                            case $page === 'promotions_create': requireAdmin(); include __DIR__ . '/modules/promotions/create.php'; break;
                            case $page === 'promotions_edit': requireAdmin(); include __DIR__ . '/modules/promotions/edit.php'; break;
                            case $page === 'promotions_delete': requireAdmin(); include __DIR__ . '/modules/promotions/delete.php'; break;
                            case $page === 'promotions_view': include __DIR__ . '/modules/promotions/view.php'; break;
                            case $page === 'reports': requireAdmin(); include __DIR__ . '/modules/reports/index.php'; break;
                            case $page === 'settings': requireAdmin(); include __DIR__ . '/modules/settings/index.php'; break;
                            case $page === 'notifications': include __DIR__ . '/modules/notifications.php'; break;
                            case $page === 'wallet': include __DIR__ . '/modules/wallet.php'; break;
                            default: echo '<div class="alert alert-warning">Page not found.</div>';
                        }
                    } catch (Throwable $e) {
                        error_log('Dashboard error: ' . $e->getMessage());
                        echo '<div class="alert alert-danger" style="background: rgba(255,59,107,0.12); border-color: rgba(255,59,107,0.3); color: #fecaca; border-radius: var(--radius-md);">
                            <h5><i class="bi bi-exclamation-octagon me-2"></i>Error</h5>
                            <p class="mb-0">Terjadi kesalahan. Silakan coba lagi atau hubungi administrator.</p>
                        </div>';
                    }
                    ?>
                </div>
            </div>
        </main>

        <footer class="app-footer" style="background: rgba(13,2,33,0.9); border-top: 1px solid rgba(255,79,216,0.08); color: var(--neon-cyan); padding: 1rem 1.5rem;">
            <div class="float-end d-none d-sm-inline" style="color: var(--text-muted); font-family: var(--font-mono); font-size: 0.75rem;">
                <span style="color: var(--neon-pink);">♥</span> Gaming Room Booking System
            </div>
            <strong style="color: var(--text-secondary);">© 2026 GameZone</strong>
            <span style="color: var(--text-muted); font-size: 0.85rem;">All rights reserved.</span>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.min.js"></script>
    <script src="assets/js/adminlte.js"></script>
    <script src="assets/js/gamezone.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wrapper = document.querySelector('.sidebar-wrapper');
            if (wrapper && window.OverlayScrollbarsGlobal?.OverlayScrollbars && window.innerWidth > 992) {
                window.OverlayScrollbarsGlobal.OverlayScrollbars(wrapper, {
                    scrollbars: { theme: 'os-theme-light', autoHide: 'leave', clickScroll: true }
                });
            }
        });
    </script>
    <script>
    function togglePass(id,btn){const i=document.getElementById(id);const isPass=i.type==='password';i.type=isPass?'text':'password';btn.innerHTML=isPass?'<i class="bi bi-eye-slash"></i>':'<i class="bi bi-eye"></i>';}
    </script>
</body>
</html>
