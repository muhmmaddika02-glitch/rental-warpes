<?php if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; } ?>
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="index.php" class="brand-link">
            <span class="brand-text fw-light"><b>Game</b>Zone</span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link <?= $page === 'home' ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-speedometer2"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <?php if (isAdmin() || isStaff()): ?>
                <li class="nav-header">MANAGEMENT</li>
                <?php endif; ?>

                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a href="dashboard.php?page=users" class="nav-link <?= strpos($page, 'users') === 0 ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-people"></i>
                        <p>Users</p>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a href="dashboard.php?page=devices" class="nav-link <?= strpos($page, 'device') === 0 ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-controller"></i>
                        <p>Devices</p>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="dashboard.php?page=bookings" class="nav-link <?= strpos($page, 'booking') === 0 ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-calendar-check"></i>
                        <p>Bookings</p>
                    </a>
                </li>

                <?php if (isAdmin() || isStaff()): ?>
                <li class="nav-item">
                    <a href="dashboard.php?page=payments" class="nav-link <?= strpos($page, 'payment') === 0 ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-credit-card"></i>
                        <p>Payments</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (isCustomer()): ?>
                <li class="nav-header">MY ACCOUNT</li>
                <li class="nav-item">
                    <a href="dashboard.php?page=bookings" class="nav-link <?= strpos($page, 'booking') === 0 ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-calendar-check"></i>
                        <p>My Bookings</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="dashboard.php?page=notifications" class="nav-link <?= $page === 'notifications' ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-bell"></i>
                        <p>Notifications</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (isAdmin()): ?>
                <li class="nav-header">CONTENT</li>
                <li class="nav-item">
                    <a href="dashboard.php?page=tournaments" class="nav-link <?= strpos($page, 'tournament') === 0 ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-trophy"></i>
                        <p>Tournaments</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="dashboard.php?page=promotions" class="nav-link <?= strpos($page, 'promotion') === 0 ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-megaphone"></i>
                        <p>Promotions</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="dashboard.php?page=reports" class="nav-link <?= strpos($page, 'report') === 0 ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-file-earmark-bar-graph"></i>
                        <p>Reports</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="dashboard.php?page=settings" class="nav-link <?= strpos($page, 'setting') === 0 ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-gear"></i>
                        <p>Settings</p>
                    </a>
                </li>
                <?php endif; ?>

                <li class="nav-header">ACCOUNT</li>
                <li class="nav-item">
                    <a href="dashboard.php?page=profile" class="nav-link <?= $page === 'profile' ? 'active' : '' ?>">
                        <i class="nav-icon bi bi-person"></i>
                        <p>Profile</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="nav-icon bi bi-box-arrow-right"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>