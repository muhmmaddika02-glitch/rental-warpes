<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }

global $pdo;

$stats = [
    'total_users' => 0,
    'total_bookings' => 0,
    'total_revenue' => 0,
    'active_devices' => 0,
    'pending_payments' => 0,
    'today_bookings' => 0
];

$revenueData = [];
$bookingsData = [];
$recentBookings = [];

try {
    $userId = getCurrentUserId();
    $userRole = getCurrentUserRole();

    if (isCustomer()) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM bookings WHERE user_id = ?");
        $stmt->execute([$userId]);
        $stats['total_bookings'] = $stmt->fetch()['total'];

        $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) as total FROM bookings WHERE user_id = ? AND booking_status = 'completed'");
        $stmt->execute([$userId]);
        $stats['total_revenue'] = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM devices WHERE status = 'available'");
        $stats['active_devices'] = $stmt->fetch()['total'];

        $points = getCurrentUserPoints();

        $revenueData = $pdo->prepare("
            SELECT DATE_FORMAT(b.created_at, '%Y-%m') as month, COALESCE(SUM(b.total_price), 0) as revenue
            FROM bookings b
            WHERE b.user_id = ? AND b.booking_status = 'completed'
            AND b.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(b.created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $revenueData->execute([$userId]);
        $revenueData = $revenueData->fetchAll();

        $bookingsData = $pdo->prepare("
            SELECT DATE(booking_date) as date, COUNT(*) as total
            FROM bookings
            WHERE user_id = ? AND booking_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(booking_date)
            ORDER BY date ASC
        ");
        $bookingsData->execute([$userId]);
        $bookingsData = $bookingsData->fetchAll();

        $recentBookings = $pdo->prepare("
            SELECT b.*, u.name as customer_name, d.name as device_name
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN devices d ON b.device_id = d.id
            WHERE b.user_id = ?
            ORDER BY b.created_at DESC
            LIMIT 10
        ");
        $recentBookings->execute([$userId]);
        $recentBookings = $recentBookings->fetchAll();
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
        $stats['total_users'] = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
        $stats['total_bookings'] = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) as total FROM bookings WHERE booking_status = 'completed'");
        $stats['total_revenue'] = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM devices WHERE status = 'available'");
        $stats['active_devices'] = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM payments WHERE payment_status = 'pending'");
        $stats['pending_payments'] = $stmt->fetch()['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings WHERE DATE(booking_date) = CURDATE()");
        $stats['today_bookings'] = $stmt->fetch()['total'];

        $revenueData = $pdo->query("
            SELECT DATE_FORMAT(b.created_at, '%Y-%m') as month, COALESCE(SUM(b.total_price), 0) as revenue
            FROM bookings b
            WHERE b.booking_status = 'completed'
            AND b.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(b.created_at, '%Y-%m')
            ORDER BY month ASC
        ")->fetchAll();

        $bookingsData = $pdo->query("
            SELECT DATE(booking_date) as date, COUNT(*) as total
            FROM bookings
            WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY DATE(booking_date)
            ORDER BY date ASC
        ")->fetchAll();

        $recentBookings = $pdo->query("
            SELECT b.*, u.name as customer_name, d.name as device_name
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN devices d ON b.device_id = d.id
            ORDER BY b.created_at DESC
            LIMIT 10
        ")->fetchAll();
    }
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<?php if (isset($error)): ?>
<div class="alert alert-danger" style="background: rgba(255,59,107,0.12); border-color: rgba(255,59,107,0.3); color: #fecaca; border-radius: var(--radius-md);">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>Terjadi kesalahan pada sistem. Silakan coba lagi.
</div>
<?php endif; ?>

<style>
.stat-neon-value { font-size: 2.2rem; font-weight: 800; background: linear-gradient(135deg, var(--neon-pink), var(--neon-cyan)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
.loading-fade { animation: fadeIn 0.6s ease-out both; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
</style>

<div class="row g-4 mb-4 loading-fade" style="animation-delay:0.05s">
    <?php if (isCustomer()): ?>
    <div class="col-lg-3 col-md-6">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <p class="text-muted mb-1 small">My Bookings</p>
                    <h2 class="stat-neon-value mb-0"><?= number_format($stats['total_bookings']) ?></h2>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(6,182,212,0.15);color:var(--neon-cyan);font-size:1.5rem;">
                    <i class="bi bi-calendar-check"></i>
                </div>
            </div>
            <a href="dashboard.php?page=bookings" class="btn btn-outline-info btn-sm mt-auto">View Details →</a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <p class="text-muted mb-1 small">Total Spent</p>
                    <h2 class="stat-neon-value mb-0" style="font-size:1.6rem;"><?= formatRupiah($stats['total_revenue']) ?></h2>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(34,197,94,0.15);color:var(--neon-green);font-size:1.5rem;">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
            <a href="dashboard.php?page=bookings" class="btn btn-outline-info btn-sm mt-auto">View History →</a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <p class="text-muted mb-1 small">Available Devices</p>
                    <h2 class="stat-neon-value mb-0"><?= number_format($stats['active_devices']) ?></h2>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(253,224,71,0.15);color:var(--neon-gold);font-size:1.5rem;">
                    <i class="bi bi-controller"></i>
                </div>
            </div>
            <a href="dashboard.php?page=devices" class="btn btn-outline-info btn-sm mt-auto">Browse Devices →</a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <p class="text-muted mb-1 small">My Points</p>
                    <h2 class="stat-neon-value mb-0"><?= number_format($points ?? 0) ?></h2>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(255,79,216,0.15);color:var(--neon-pink);font-size:1.5rem;">
                    <i class="bi bi-trophy"></i>
                </div>
            </div>
            <a href="dashboard.php?page=profile" class="btn btn-outline-info btn-sm mt-auto">My Profile →</a>
        </div>
    </div>
    <?php else: ?>
    <div class="col-lg-3 col-md-6">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <p class="text-muted mb-1 small">Total Customers</p>
                    <h2 class="stat-neon-value mb-0"><?= number_format($stats['total_users']) ?></h2>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(255,79,216,0.15);color:var(--neon-pink);font-size:1.5rem;">
                    <i class="bi bi-people"></i>
                </div>
            </div>
            <a href="dashboard.php?page=users" class="btn btn-outline-info btn-sm mt-auto">View Details →</a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <p class="text-muted mb-1 small">Total Bookings</p>
                    <h2 class="stat-neon-value mb-0"><?= number_format($stats['total_bookings']) ?></h2>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(6,182,212,0.15);color:var(--neon-cyan);font-size:1.5rem;">
                    <i class="bi bi-calendar-check"></i>
                </div>
            </div>
            <a href="dashboard.php?page=bookings" class="btn btn-outline-info btn-sm mt-auto">View Details →</a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <p class="text-muted mb-1 small">Total Revenue</p>
                    <h2 class="stat-neon-value mb-0" style="font-size:1.6rem;"><?= formatRupiah($stats['total_revenue']) ?></h2>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(34,197,94,0.15);color:var(--neon-green);font-size:1.5rem;">
                    <i class="bi bi-cash-stack"></i>
                </div>
            </div>
            <a href="dashboard.php?page=reports" class="btn btn-outline-info btn-sm mt-auto">View Details →</a>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card p-4 h-100">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <p class="text-muted mb-1 small">Available Devices</p>
                    <h2 class="stat-neon-value mb-0"><?= number_format($stats['active_devices']) ?></h2>
                </div>
                <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:48px;height:48px;background:rgba(253,224,71,0.15);color:var(--neon-gold);font-size:1.5rem;">
                    <i class="bi bi-controller"></i>
                </div>
            </div>
            <a href="dashboard.php?page=devices" class="btn btn-outline-info btn-sm mt-auto">View Details →</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="row g-4 mb-4 loading-fade" style="animation-delay:0.15s">
    <div class="col-lg-6">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0" style="color:var(--text-primary);font-weight:600;">
                    <i class="bi bi-graph-up me-2" style="color:var(--neon-pink);"></i><?= isCustomer() ? 'My Spending' : 'Revenue Trend' ?>
                </h5>
                <span class="text-muted small">Last 6 Months</span>
            </div>
            <div style="position: relative; height: 200px;"><canvas id="revenueChart"></canvas></div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0" style="color:var(--text-primary);font-weight:600;">
                    <i class="bi bi-calendar-event me-2" style="color:var(--neon-cyan);"></i><?= isCustomer() ? 'My Bookings' : 'Booking Activity' ?>
                </h5>
                <span class="text-muted small">Last 7 Days</span>
            </div>
            <div style="position: relative; height: 200px;"><canvas id="bookingsChart"></canvas></div>
        </div>
    </div>
</div>

<div class="row loading-fade" style="animation-delay:0.25s">
    <div class="col-12">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0" style="color:var(--text-primary);font-weight:600;">
                    <i class="bi bi-clock-history me-2" style="color:var(--neon-pink);"></i><?= isCustomer() ? 'My Recent Bookings' : 'Recent Bookings' ?>
                </h5>
                <a href="dashboard.php?page=bookings" class="btn btn-primary btn-sm">View All →</a>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php if (!isCustomer()): ?><th>Customer</th><?php endif; ?>
                            <th>Device</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentBookings)): ?>
                            <tr>
                                <td colspan="<?= isCustomer() ? 6 : 7 ?>" class="text-center py-5" style="color: #958da1;">
                                    <i class="bi bi-inbox" style="font-size: 48px; display: block; margin-bottom: 12px; opacity: 0.3;"></i>
                                    No bookings yet
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentBookings as $booking): ?>
                                <tr>
                                    <td><span style="color: #ff00ff; font-weight: 600;">#<?= $booking['id'] ?></span></td>
                                    <?php if (!isCustomer()): ?><td><?= escape($booking['customer_name']) ?></td><?php endif; ?>
                                    <td><?= escape($booking['device_name']) ?></td>
                                    <td><?= formatDate($booking['booking_date'], 'd M Y') ?></td>
                                    <td><?= $booking['start_time'] ? date('H:i', strtotime($booking['start_time'])) : '-' ?></td>
                                    <td><span style="color: #00ff00; font-weight: 600;"><?= formatRupiah($booking['total_price']) ?></span></td>
                                    <td><?= getBookingStatusBadge($booking['booking_status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($revenueData, 'month'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode(array_column($revenueData, 'revenue'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                    borderColor: '#FF4FD8',
                    backgroundColor: 'rgba(255, 79, 216, 0.15)',
                    tension: 0.4,
                    fill: true,
                    borderWidth: 3,
                    pointRadius: 6,
                    pointBackgroundColor: '#FF4FD8',
                    pointBorderColor: '#0d0221',
                    pointBorderWidth: 2,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(23, 31, 51, 0.95)',
                        titleColor: '#dae2fd',
                        bodyColor: '#ccc3d8',
                        borderColor: '#FF4FD8',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: {
                            color: '#958da1',
                            callback: function(value) {
                                return 'Rp ' + (value / 1000) + 'K';
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#958da1' }
                    }
                }
            }
        });
    }

    const bookingsCtx = document.getElementById('bookingsChart');
    if (bookingsCtx) {
        new Chart(bookingsCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_map(function($d) { return date('d M', strtotime($d['date'])); }, $bookingsData), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                datasets: [{
                    label: '<?= isCustomer() ? 'My Bookings' : 'Bookings' ?>',
                    data: <?= json_encode(array_column($bookingsData, 'total'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                    backgroundColor: 'rgba(6, 182, 212, 0.7)',
                    borderColor: '#06B6D4',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(23, 31, 51, 0.95)',
                        titleColor: '#dae2fd',
                        bodyColor: '#ccc3d8',
                        borderColor: '#06B6D4',
                        borderWidth: 1,
                        padding: 12,
                        displayColors: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: {
                            stepSize: 1,
                            color: '#958da1'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#958da1' },
                        categoryPercentage: 0.7,
                        barPercentage: 0.8
                    }
                }
            }
        });
    }
});
</script>
