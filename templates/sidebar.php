<?php if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; } ?>
<aside class="app-sidebar" style="background: rgba(13,2,33,0.85); backdrop-filter: blur(24px); border-right: 1px solid rgba(255,79,216,0.2); box-shadow: 5px 0 40px rgba(255,79,216,0.08);">
  <div class="sidebar-brand-modern" style="padding: 1.5rem 1.25rem; border-bottom: 1px solid rgba(255,79,216,0.15);">
    <a href="index.php" style="text-decoration: none; font-size: 1.5rem; font-weight: 900;">
      <span style="color: var(--neon-pink);">Game</span><span style="color: var(--neon-cyan);">Zone</span>
    </a>
  </div>
  <div class="sidebar-wrapper" style="padding: 0.75rem;">
    <nav>
      <ul class="nav sidebar-menu flex-column" role="menu" style="list-style: none; padding: 0; margin: 0;">

        <li class="nav-item" style="margin-bottom: 2px;">
          <a href="dashboard.php" class="nav-neon <?= $page === 'home' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> <span>Dashboard</span>
          </a>
        </li>

        <?php if (isAdmin() || isStaff()): ?>
        <li style="padding: 1.25rem 1rem 0.5rem; font-size: 0.65rem; font-weight: 700; color: #67e8f9; letter-spacing: 0.15em; text-transform: uppercase;">Management</li>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
        <li class="nav-item" style="margin-bottom: 2px;">
          <a href="dashboard.php?page=users" class="nav-neon <?= strpos($page, 'users') === 0 ? 'active' : '' ?>">
            <i class="bi bi-people"></i> <span>Users</span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (isAdmin() || isStaff()): ?>
        <li class="nav-item" style="margin-bottom: 2px;">
          <a href="dashboard.php?page=devices" class="nav-neon <?= strpos($page, 'device') === 0 ? 'active' : '' ?>">
            <i class="bi bi-controller"></i> <span>Devices</span>
          </a>
        </li>
        <li class="nav-item" style="margin-bottom: 2px;">
          <a href="dashboard.php?page=bookings" class="nav-neon <?= strpos($page, 'booking') === 0 ? 'active' : '' ?>">
            <i class="bi bi-calendar-check"></i> <span>Bookings</span>
          </a>
        </li>
        <li class="nav-item" style="margin-bottom: 2px;">
          <a href="dashboard.php?page=payments" class="nav-neon <?= strpos($page, 'payment') === 0 ? 'active' : '' ?>">
            <i class="bi bi-credit-card"></i> <span>Payments</span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (isCustomer()): ?>
        <li style="padding: 1.25rem 1rem 0.5rem; font-size: 0.65rem; font-weight: 700; color: var(--neon-cyan); letter-spacing: 0.15em; text-transform: uppercase; color: #67e8f9;">My Account</li>
        <li class="nav-item" style="margin-bottom: 2px;">
          <a href="dashboard.php?page=bookings" class="nav-neon <?= strpos($page, 'booking') === 0 ? 'active' : '' ?>">
            <i class="bi bi-calendar-check"></i> <span>My Bookings</span>
          </a>
        </li>
        <li class="nav-item" style="margin-bottom: 2px;">
          <a href="dashboard.php?page=notifications" class="nav-neon <?= $page === 'notifications' ? 'active' : '' ?>">
            <i class="bi bi-bell"></i> <span>Notifications</span>
          </a>
        </li>
        <li class="nav-item" style="margin-bottom: 2px;">
          <a href="dashboard.php?page=wallet" class="nav-neon <?= $page === 'wallet' ? 'active' : '' ?>">
            <i class="bi bi-wallet2"></i> <span>Wallet</span>
          </a>
        </li>
        <?php endif; ?>

        <li style="padding: 1.25rem 1rem 0.5rem; font-size: 0.65rem; font-weight: 700; color: var(--neon-cyan); letter-spacing: 0.15em; text-transform: uppercase; color: #67e8f9;">Content</li>
        <li class="nav-item" style="margin-bottom: 2px;">
          <a href="dashboard.php?page=tournaments" class="nav-neon <?= strpos($page, 'tournament') === 0 ? 'active' : '' ?>">
            <i class="bi bi-trophy"></i> <span>Tournaments</span>
          </a>
        </li>
        <li class="nav-item" style="margin-bottom: 2px;">
          <a href="dashboard.php?page=promotions" class="nav-neon <?= strpos($page, 'promotion') === 0 ? 'active' : '' ?>">
            <i class="bi bi-megaphone"></i> <span>Promotions</span>
          </a>
        </li>

        <?php if (isAdmin()): ?>
        <li style="padding: 1.25rem 1rem 0.5rem; font-size: 0.65rem; font-weight: 700; color: var(--neon-cyan); letter-spacing: 0.15em; text-transform: uppercase; color: #67e8f9;">Administration</li>
        <li class="nav-item" style="margin-bottom: 2px;">
          <a href="dashboard.php?page=reports" class="nav-neon <?= strpos($page, 'report') === 0 ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-bar-graph"></i> <span>Reports</span>
          </a>
        </li>
        <li class="nav-item" style="margin-bottom: 2px;">
          <a href="dashboard.php?page=settings" class="nav-neon <?= strpos($page, 'setting') === 0 ? 'active' : '' ?>">
            <i class="bi bi-gear"></i> <span>Settings</span>
          </a>
        </li>
        <?php endif; ?>

        <li style="padding: 1.25rem 1rem 0.5rem; font-size: 0.65rem; font-weight: 700; color: var(--neon-cyan); letter-spacing: 0.15em; text-transform: uppercase; color: #67e8f9;">General</li>
        <li class="nav-item" style="margin-bottom: 2px;">
          <a href="dashboard.php?page=profile" class="nav-neon <?= $page === 'profile' ? 'active' : '' ?>">
            <i class="bi bi-person"></i> <span>Profile</span>
          </a>
        </li>
        <li class="nav-item" style="margin-bottom: 2px;">
          <a href="logout.php" class="nav-neon" style="color: #fb7185;">
            <i class="bi bi-box-arrow-right"></i> <span>Logout</span>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>
