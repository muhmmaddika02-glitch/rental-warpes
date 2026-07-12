<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireAdmin();

global $pdo;

$reportPeriod = $_GET['period'] ?? 'monthly';
$revenueData = [];
$topDevices = [];
$bookingStats = [];

try {
    if ($reportPeriod === 'monthly') {
        $stmt = $pdo->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m') as period, COUNT(*) as bookings, SUM(total_price) as revenue
            FROM bookings WHERE booking_status = 'completed'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY period ASC
        ");
        $revenueData = $stmt->fetchAll();
    } else {
        $stmt = $pdo->query("
            SELECT DATE_FORMAT(created_at, '%Y-%m-%d') as period, COUNT(*) as bookings, SUM(total_price) as revenue
            FROM bookings WHERE booking_status = 'completed'
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m-%d')
            ORDER BY period ASC
        ");
        $revenueData = $stmt->fetchAll();
    }
    
    $stmt = $pdo->query("
        SELECT d.name, COUNT(b.id) as total_bookings, SUM(b.total_price) as total_revenue
        FROM devices d
        LEFT JOIN bookings b ON d.id = b.device_id AND b.booking_status = 'completed'
        GROUP BY d.id, d.name
        ORDER BY total_bookings DESC
        LIMIT 10
    ");
    $topDevices = $stmt->fetchAll();
    
    $totalRevenue = array_sum(array_column($revenueData, 'revenue'));
    $totalBookings = array_sum(array_column($revenueData, 'bookings'));
    $avgBookingValue = $totalBookings > 0 ? $totalRevenue / $totalBookings : 0;
    
} catch (PDOException $e) {
    $errorMessage = 'Database error.';
}
?>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<div class="row mb-3">
    <div class="col-md-6">
        <form method="GET" class="row g-2">
            <input type="hidden" name="page" value="reports">
            <div class="col-md-6">
                <select name="period" class="form-select" onchange="this.form.submit()">
                    <option value="monthly" <?= $reportPeriod === 'monthly' ? 'selected' : '' ?>>Monthly (12 months)</option>
                    <option value="daily" <?= $reportPeriod === 'daily' ? 'selected' : '' ?>>Daily (30 days)</option>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="small-box text-bg-info">
            <div class="inner">
                <h3><?= formatRupiah($totalRevenue) ?></h3>
                <p>Total Revenue</p>
            </div>
            <div class="small-box-icon"><i class="bi bi-cash-stack"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3><?= $totalBookings ?></h3>
                <p>Total Bookings</p>
            </div>
            <div class="small-box-icon"><i class="bi bi-calendar-check"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3><?= formatRupiah($avgBookingValue) ?></h3>
                <p>Avg. Booking Value</p>
            </div>
            <div class="small-box-icon"><i class="bi bi-graph-up-arrow"></i></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Revenue Trend</h3></div>
            <div class="card-body">
                <canvas id="revenueChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Top Devices</h3></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Device</th><th>Bookings</th><th>Revenue</th></tr></thead>
                    <tbody>
                        <?php foreach ($topDevices as $d): ?>
                            <tr>
                                <td><?= escape($d['name']) ?></td>
                                <td><?= $d['total_bookings'] ?></td>
                                <td><?= formatRupiah($d['total_revenue']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_map(function($r) { return $r['period']; }, $revenueData)) ?>,
            datasets: [
                {
                    label: 'Revenue',
                    data: <?= json_encode(array_column($revenueData, 'revenue')) ?>,
                    borderColor: '#ff00ff',
                    backgroundColor: 'rgba(255,0,255,0.1)',
                    tension: 0.4,
                    yAxisID: 'y'
                },
                {
                    label: 'Bookings',
                    data: <?= json_encode(array_column($revenueData, 'bookings')) ?>,
                    borderColor: '#00ffff',
                    backgroundColor: 'rgba(0,255,255,0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, ticks: { callback: v => 'Rp ' + v.toLocaleString('id-ID') } },
                y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, ticks: { stepSize: 1 } }
            }
        }
    });
});
</script>