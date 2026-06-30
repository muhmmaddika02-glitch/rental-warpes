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

$paymentMethods = ['qris', 'bank_transfer', 'e_wallet'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$id) {
    $paymentMethod = $_POST['payment_method'] ?? '';
    $amount = $data['total_price'];
    
    if (empty($paymentMethod)) {
        $errorMessage = 'Please select a payment method.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO payments (booking_id, payment_method, amount, payment_status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$data['id'], $paymentMethod, $amount]);
            
            addNotification($data['user_id'], 'Payment Submitted', 'Your payment for booking #' . $data['id'] . ' has been submitted. Waiting for verification.', 'payment_confirmation');
            
            flashMessage('Payment submitted successfully. Waiting for verification.', 'success');
            header('Location: dashboard.php?page=bookings_view&id=' . $data['id']);
            exit;
        } catch (PDOException $e) {
            $errorMessage = 'Database error: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id && isset($_POST['verify'])) {
    $action = $_POST['verify']; // 'paid', 'failed'
    
    try {
        $stmt = $pdo->prepare("UPDATE payments SET payment_status = ?, paid_at = NOW() WHERE id = ?");
        $stmt->execute([$action, $id]);
        
        if ($action === 'paid') {
            $stmt = $pdo->prepare("UPDATE bookings SET booking_status = 'confirmed' WHERE id = ?");
            $stmt->execute([$data['booking_id']]);
            
            $stmt = $pdo->prepare("UPDATE devices SET status = 'booked' WHERE id = ?");
            $stmt->execute([$data['device_id']]);
            
            $stmt = $pdo->prepare("UPDATE invoice_details SET payment_status = 'paid' WHERE booking_id = ?");
            $stmt->execute([$data['booking_id']]);
            
            addNotification($data['user_id'], 'Payment Verified', 'Your payment for booking #' . $data['booking_id'] . ' has been verified. Enjoy your gaming session!', 'payment_confirmation');
            
            flashMessage('Payment verified successfully. Booking confirmed!', 'success');
        } else {
            flashMessage('Payment marked as failed.', 'warning');
        }
        
        header('Location: dashboard.php?page=payments');
        exit;
    } catch (PDOException $e) {
        $errorMessage = 'Database error: ' . $e->getMessage();
    }
}
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <?= $id ? '<i class="bi bi-check-circle me-2"></i>Verify Payment #' . $id : '<i class="bi bi-credit-card me-2"></i>Process Payment' ?>
                </h3>
            </div>
            <div class="card-body">
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">Customer</h6>
                        <p class="fw-bold"><?= escape($data['customer_name']) ?></p>
                        <p><?= escape($data['customer_email']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted">Booking</h6>
                        <p class="fw-bold">#<?= $data['booking_id'] ?? $data['id'] ?></p>
                        <p><?= escape($data['device_name']) ?> (<?= escape($data['device_type']) ?>)</p>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <h6 class="text-muted">Date</h6>
                        <p><?= formatDate($data['booking_date'], 'd M Y') ?></p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted">Time</h6>
                        <p><?= date('H:i', strtotime($data['start_time'])) ?> (<?= $data['duration_hours'] ?>h)</p>
                    </div>
                    <div class="col-md-4">
                        <h6 class="text-muted">Amount</h6>
                        <p class="h5 text-primary"><?= formatRupiah($data['total_price']) ?></p>
                    </div>
                </div>
                
                <?php if ($id && isset($data['payment_method'])): ?>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">Payment Method</h6>
                            <p><?= getPaymentMethodBadge($data['payment_method']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">Current Status</h6>
                            <p><?= getPaymentStatusBadge($data['payment_status']) ?></p>
                        </div>
                    </div>
                    
                    <?php if ($data['payment_status'] === 'pending'): ?>
                        <form method="POST">
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
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select" name="payment_method" required>
                                <option value="">-- Select Payment Method --</option>
                                <option value="qris">QRIS</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="e_wallet">E-Wallet</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="bi bi-send me-2"></i>Submit Payment
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>