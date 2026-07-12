<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireStaffOrAdmin();

global $pdo;

$page = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
$totalPages = 1;
$payments = [];
$filterStatus = $_GET['status'] ?? 'all';

try {
    $baseSql = "FROM payments p JOIN bookings b ON p.booking_id = b.id JOIN users u ON b.user_id = u.id JOIN devices d ON b.device_id = d.id";
    $params = [];

    if ($filterStatus !== 'all') {
        $where = " WHERE p.payment_status = ?";
        $params[] = $filterStatus;
    } else {
        $where = '';
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) $baseSql $where");
    $countStmt->execute($params);
    $totalRecords = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalRecords / $perPage));

    $stmt = $pdo->prepare("SELECT p.*, b.id as booking_id, b.total_price, b.booking_date, b.booking_status,
                                  u.name as customer_name, d.name as device_name
                           $baseSql $where ORDER BY p.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([...$params, $perPage, $offset]);
    $payments = $stmt->fetchAll();

} catch (PDOException $e) {
    $errorMessage = 'Database error: ' . $e->getMessage();
}
?>
<div class="row mb-3">
    <div class="col-md-6">
        <form method="GET" class="row g-2">
            <input type="hidden" name="page" value="payments">
            <div class="col-md-6">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All Status</option>
                    <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="paid" <?= $filterStatus === 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="failed" <?= $filterStatus === 'failed' ? 'selected' : '' ?>>Failed</option>
                    <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-4">
                <a href="dashboard.php?page=payments" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Device</th>
                        <th>Booking</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($payments)): ?>
                        <tr><td colspan="9" class="text-center py-5">
                            <div class="py-3">
                                <i class="bi bi-credit-card-2-back" style="font-size:3rem;color:rgba(255,255,255,0.12);display:block;margin-bottom:0.75rem;"></i>
                                <p class="text-muted mb-0">Belum ada pembayaran</p>
                            </div>
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>#<?= $payment['id'] ?></td>
                                <td><?= escape($payment['customer_name']) ?></td>
                                <td><?= escape($payment['device_name']) ?></td>
                                <td>#<?= $payment['booking_id'] ?></td>
                                <td><strong><?= formatRupiah($payment['amount']) ?></strong></td>
                                <td><?= getPaymentMethodBadge($payment['payment_method']) ?></td>
                                <td><?= getPaymentStatusBadge($payment['payment_status']) ?></td>
                                <td><?= formatDate($payment['created_at'], 'd M Y') ?></td>
                                <td class="text-end">
                                    <a href="dashboard.php?page=payments_verify&id=<?= $payment['id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                    <?php if ($payment['payment_status'] === 'pending'): ?>
                                        <a href="dashboard.php?page=payments_verify&id=<?= $payment['id'] ?>" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Verify</a>
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

<?= renderPagination($page, $totalPages, 'dashboard.php?page=payments&status=' . urlencode($filterStatus)) ?>
