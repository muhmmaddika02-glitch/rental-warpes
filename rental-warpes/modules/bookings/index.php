<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }

global $pdo;

$filterStatus = $_GET['status'] ?? 'all';

try {
    $sql = "SELECT b.*, u.name as customer_name, u.email as customer_email, d.name as device_name, d.type as device_type
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN devices d ON b.device_id = d.id";
    
    $params = [];
    $conditions = [];
    
    if (isCustomer()) {
        $conditions[] = "b.user_id = ?";
        $params[] = getCurrentUserId();
    }
    
    if ($filterStatus !== 'all') {
        $conditions[] = "b.booking_status = ?";
        $params[] = $filterStatus;
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY b.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $errorMessage = 'Database error: ' . $e->getMessage();
}
?>

<div class="row mb-3">
    <div class="col-md-8">
        <form method="GET" class="row g-2">
            <input type="hidden" name="page" value="bookings">
            <div class="col-md-4">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= $filterStatus === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="playing" <?= $filterStatus === 'playing' ? 'selected' : '' ?>>Playing</option>
                    <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-4">
                <a href="dashboard.php?page=bookings" class="btn btn-secondary">Clear Filter</a>
            </div>
        </form>
    </div>
    <div class="col-md-4 text-end">
        <a href="dashboard.php?page=bookings_create" class="btn btn-success">
            <i class="bi bi-plus-circle me-1"></i> New Booking
        </a>
    </div>
</div>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <?php if (!isCustomer()): ?><th>Customer</th><?php endif; ?>
                        <th>Device</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Duration</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="<?= isCustomer() ? 8 : 9 ?>" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                                No bookings found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>#<?= $booking['id'] ?></td>
                                <?php if (!isCustomer()): ?>
                                    <td>
                                        <strong><?= escape($booking['customer_name']) ?></strong>
                                        <br><small class="text-muted"><?= escape($booking['customer_email']) ?></small>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <strong><?= escape($booking['device_name']) ?></strong>
                                    <br><small class="text-muted"><?= escape($booking['device_type']) ?></small>
                                </td>
                                <td><?= formatDate($booking['booking_date'], 'd M Y') ?></td>
                                <td><?= date('H:i', strtotime($booking['start_time'])) ?></td>
                                <td><?= $booking['duration_hours'] ?> hour<?= $booking['duration_hours'] > 1 ? 's' : '' ?></td>
                                <td><strong><?= formatRupiah($booking['total_price']) ?></strong></td>
                                <td><?= getBookingStatusBadge($booking['booking_status']) ?></td>
                                <td class="text-end">
                                    <a href="dashboard.php?page=bookings_view&id=<?= $booking['id'] ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    
                                    <?php if ($booking['booking_status'] === 'pending' && (isAdmin() || isStaff())): ?>
                                        <a href="dashboard.php?page=bookings_view&id=<?= $booking['id'] ?>" class="btn btn-sm btn-success">
                                            <i class="bi bi-check-lg"></i> Confirm
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($booking['booking_status'] === 'pending' && (isCustomer() || isAdmin())): ?>
                                        <button type="button" class="btn btn-sm btn-danger" 
                                                onclick="if(confirm('Cancel this booking?')) window.location.href='dashboard.php?page=bookings_cancel&id=<?= $booking['id'] ?>&csrf=<?= generateCsrfToken() ?>'">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($booking['booking_status'] === 'confirmed' && (isStaff() || isAdmin())): ?>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                onclick="if(confirm('Mark as Playing?')) { 
                                                    fetch('api/bookings-api.php?action=update_status&id=<?= $booking['id'] ?>&status=playing&csrf=<?= generateCsrfToken() ?>')
                                                    .then(() => location.reload());
                                                }">
                                            <i class="bi bi-play-fill"></i> Playing
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if ($booking['booking_status'] === 'playing' && (isStaff() || isAdmin())): ?>
                                        <button type="button" class="btn btn-sm btn-success" 
                                                onclick="if(confirm('Mark as Completed?')) { 
                                                    fetch('api/bookings-api.php?action=update_status&id=<?= $booking['id'] ?>&status=completed&csrf=<?= generateCsrfToken() ?>')
                                                    .then(() => location.reload());
                                                }">
                                            <i class="bi bi-check-lg"></i> Complete
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>