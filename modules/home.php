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

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
    $stats['total_users'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
    $stats['total_bookings'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT SUM(total_price) as total FROM bookings WHERE booking_status = 'completed'");
    $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM devices WHERE status = 'available'");
    $stats['active_devices'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM payments WHERE payment_status = 'pending'");
    $stats['pending_payments'] = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings WHERE DATE(booking_date) = CURDATE()");
    $stats['today_bookings'] = $stmt->fetch()['total'];
    
    $revenueData = $pdo->query("
        SELECT DATE_FORMAT(b.created_at, '%Y-%m') as month, SUM(b.total_price) as revenue
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
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}
?>

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3><?= number_format($stats['total_users']) ?></h3>
                <p>Total Customers</p>
            </div>
            <div class="small-box-icon"><i class="bi bi-people"></i></div>
            <a href="dashboard.php?page=users" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-arrow-right-circle-fill"></i>
            </a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3><?= number_format($stats['total_bookings']) ?></h3>
                <p>Total Bookings</p>
            </div>
            <div class="small-box-icon"><i class="bi bi-calendar-check"></i></div>
            <a href="dashboard.php?page=bookings" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-arrow-right-circle-fill"></i>
            </a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3><?= formatRupiah($stats['total_revenue']) ?></h3>
                <p>Total Revenue</p>
            </div>
            <div class="small-box-icon"><i class="bi bi-cash-stack"></i></div>
            <a href="dashboard.php?page=reports" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-arrow-right-circle-fill"></i>
            </a>
        </div>
    </div>
    
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3><?= number_format($stats['active_devices']) ?></h3>
                <p>Available Devices</p>
            </div>
            <div class="small-box-icon"><i class="bi bi-controller"></i></div>
            <a href="dashboard.php?page=devices" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
                More info <i class="bi bi-arrow-right-circle-fill"></i>
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-graph-up me-2"></i>Revenue (Last 6 Months)</h3>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-calendar-event me-2"></i>Bookings (Last 7 Days)</h3>
            </div>
            <div class="card-body">
                <canvas id="bookingsChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-clock-history me-2"></i>Recent Bookings</h3>
                <div class="card-tools">
                    <a href="dashboard.php?page=bookings" class="btn btn-sm btn-primary">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Customer</th>
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
                                <td colspan="7" class="text-center text-muted py-4">No bookings yet</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentBookings as $booking): ?>
                                <tr>
                                    <td>#<?= $booking['id'] ?></td>
                                    <td><?= escape($booking['customer_name']) ?></td>
                                    <td><?= escape($booking['device_name']) ?></td>
                                    <td><?= formatDate($booking['booking_date'], 'd M Y') ?></td>
                                    <td><?= date('H:i', strtotime($booking['start_time'])) ?></td>
                                    <td><?= formatRupiah($booking['total_price']) ?></td>
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
                labels: <?= json_encode(array_column($revenueData, 'month')) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode(array_column($revenueData, 'revenue')) ?>,
                    borderColor: 'rgb(124, 58, 237)',
                    backgroundColor: 'rgba(124, 58, 237, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString('id-ID');
                            }
                        }
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
                labels: <?= json_encode(array_map(function($d) { return date('d M', strtotime($d['date'])); }, $bookingsData)) ?>,
                datasets: [{
                    label: 'Bookings',
                    data: <?= json_encode(array_column($bookingsData, 'total')) ?>,
                    backgroundColor: 'rgba(6, 182, 212, 0.8)',
                    borderColor: 'rgb(6, 182, 212)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }
});
</script>