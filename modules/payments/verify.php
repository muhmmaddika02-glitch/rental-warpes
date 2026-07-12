<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireStaffOrAdmin();

global $pdo;

$id = (int) ($_GET['id'] ?? 0);
$bookingId = (int) ($_GET['booking_id'] ?? 0);

try {
    if ($id > 0) {
        $stmt = $pdo->prepare("
            SELECT p.*, b.user_id, b.device_id, b.booking_date, b.start_time, b.duration_hours, b.total_price, b.booking_status,
                   u.name as customer_name, u.email as customer_email, d.name as device_name, d.type as device_type
            FROM payments p
            JOIN bookings b ON p.booking_id = b.id
            JOIN users u ON b.user_id = u.id
            JOIN devices d ON b.device_id = d.id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
    } elseif ($bookingId > 0) {
        $stmt = $pdo->prepare("
            SELECT b.*, u.name as customer_name, u.email as customer_email, d.name as device_name, d.type as device_type
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN devices d ON b.device_id = d.id
            WHERE b.id = ?
            LIMIT 1
        ");
        $stmt->execute([$bookingId]);
    }

    $data = $stmt->fetch();

    if (!$data) {
        flashMessage('Record not found.', 'danger');
        header('Location: dashboard.php?page=payments');
        exit;
    }
} catch (PDOException $e) {
    flashMessage('Database error.', 'danger');
    header('Location: dashboard.php?page=payments');
    exit;
}

// Manual override: admin/staff can still mark paid/failed
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && isset($_POST['verify'])) {
    $action = $_POST['verify'];
    if (!in_array($action, ['paid', 'failed'], true)) {
        flashMessage('Invalid action.', 'danger');
        header('Location: dashboard.php?page=payments');
        exit;
    }
    if ($data['payment_status'] !== 'pending') {
        flashMessage('Payment already processed.', 'warning');
        header('Location: dashboard.php?page=payments');
        exit;
    }
    try {
        $pdo->beginTransaction();
        if ($action === 'paid') {
            $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'paid', paid_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            $stmt = $pdo->prepare("UPDATE bookings SET booking_status = 'confirmed' WHERE id = ?");
            $stmt->execute([$data['booking_id']]);
            $stmt = $pdo->prepare("UPDATE devices SET status = 'booked' WHERE id = ?");
            $stmt->execute([$data['device_id']]);
            $stmt = $pdo->prepare("UPDATE invoice_details SET payment_status = 'paid' WHERE booking_id = ?");
            $stmt->execute([$data['booking_id']]);
            $pdo->commit();
            addNotification($data['user_id'], 'Payment Verified', 'Your payment for booking #' . $data['booking_id'] . ' has been verified.', 'payment_confirmation');
            flashMessage('Payment verified. Booking confirmed!', 'success');
        } else {
            $stmt = $pdo->prepare("UPDATE payments SET payment_status = 'failed' WHERE id = ?");
            $stmt->execute([$id]);
            $pdo->commit();
            flashMessage('Payment marked as failed.', 'warning');
        }
        header('Location: dashboard.php?page=payments');
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errorMessage = 'Database error: ' . $e->getMessage();
    }
}
?>
<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title mb-0">
                    <?= $id ? '<i class="bi bi-receipt me-2"></i>Payment #' . $id : '<i class="bi bi-credit-card me-2"></i>Process Payment' ?>
                </h3>
                <?php if ($id && $data['payment_status'] === 'paid'): ?>
                    <span class="badge-neon badge-green">Auto-confirmed</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase">Customer</h6>
                        <p class="fw-bold mb-1"><?= escape($data['customer_name']) ?></p>
                        <p class="mb-0"><?= escape($data['customer_email']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted small text-uppercase">Booking</h6>
                        <p class="fw-bold mb-1">#<?= $data['booking_id'] ?? $data['id'] ?></p>
                        <p class="mb-0"><?= escape($data['device_name']) ?> (<?= escape($data['device_type']) ?>)</p>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-4">
                        <h6 class="text-muted small text-uppercase">Date</h6>
                        <p><?= formatDate($data['booking_date'], 'd M Y') ?></p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted small text-uppercase">Time</h6>
                        <p><?= date('H:i', strtotime($data['start_time'])) ?> (<?= $data['duration_hours'] ?>h)</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted small text-uppercase">Amount</h6>
                        <p class="h5" style="color:var(--neon-cyan);"><?= formatRupiah($data['total_price']) ?></p>
                    </div>
                </div>

                <?php if ($id && isset($data['payment_method'])): ?>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <h6 class="text-muted small text-uppercase">Method</h6>
                            <p><?= getPaymentMethodBadge($data['payment_method']) ?></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted small text-uppercase">Status</h6>
                            <p><?= getPaymentStatusBadge($data['payment_status']) ?></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted small text-uppercase">Booking Status</h6>
                            <p><?= getBookingStatusBadge($data['booking_status']) ?></p>
                        </div>
                    </div>

                    <?php if ($data['payment_status'] === 'paid'): ?>
                        <div class="alert alert-success d-flex align-items-center gap-2">
                            <i class="bi bi-check-circle-fill"></i> Payment was processed automatically via auto-confirm system.
                        </div>
                    <?php elseif ($data['payment_status'] === 'pending'): ?>
                        <form method="POST">
                            <?= csrfField() ?>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                <button type="submit" name="verify" value="paid" class="btn btn-success btn-lg px-5" onclick="return confirm('Confirm payment?')">
                                    <i class="bi bi-check-lg me-2"></i>Verify as Paid
                                </button>
                                <button type="submit" name="verify" value="failed" class="btn btn-danger btn-lg px-5" onclick="return confirm('Mark as failed?')">
                                    <i class="bi bi-x-lg me-2"></i>Mark as Failed
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i> Booking ini belum memiliki pembayaran. Customer akan memilih metode saat booking.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
