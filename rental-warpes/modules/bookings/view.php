<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }

global $pdo;

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    flashMessage('Invalid booking ID.', 'danger');
    header('Location: dashboard.php?page=bookings');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT b.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
               d.name as device_name, d.type as device_type, d.specification as device_spec, d.image as device_image,
               p.id as payment_id, p.payment_method, p.payment_status as pay_status, p.transaction_id, p.paid_at
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN devices d ON b.device_id = d.id
        LEFT JOIN payments p ON p.booking_id = b.id
        WHERE b.id = ?
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        flashMessage('Booking not found.', 'danger');
        header('Location: dashboard.php?page=bookings');
        exit;
    }
    
    if (isCustomer() && $booking['user_id'] !== getCurrentUserId()) {
        flashMessage('Access denied.', 'danger');
        header('Location: dashboard.php?page=bookings');
        exit;
    }
    
    $invoiceStmt = $pdo->prepare("SELECT * FROM invoice_details WHERE booking_id = ? LIMIT 1");
    $invoiceStmt->execute([$id]);
    $invoice = $invoiceStmt->fetch();
    
} catch (PDOException $e) {
    flashMessage('Database error.', 'danger');
    header('Location: dashboard.php?page=bookings');
    exit;
}

$successMessage = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
?>

<?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show"><?= htmlspecialchars($successMessage) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Booking #<?= $booking['id'] ?></h3>
                <div class="card-tools"><?= getBookingStatusBadge($booking['booking_status']) ?></div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted text-uppercase small">Customer Details</h6>
                        <p class="mb-1"><strong><?= escape($booking['customer_name']) ?></strong></p>
                        <p class="mb-1"><?= escape($booking['customer_email']) ?></p>
                        <p class="mb-0"><?= escape($booking['customer_phone'] ?? '-') ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted text-uppercase small">Device Details</h6>
                        <p class="mb-1"><strong><?= escape($booking['device_name']) ?></strong></p>
                        <p class="mb-0">Type: <?= escape($booking['device_type']) ?></p>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <h6 class="text-muted text-uppercase small">Date</h6>
                        <p class="mb-0"><strong><?= formatDate($booking['booking_date'], 'd M Y') ?></strong></p>
                    </div>
                    <div class="col-md-3">
                        <h6 class="text-muted text-uppercase small">Time</h6>
                        <p class="mb-0"><strong><?= date('H:i', strtotime($booking['start_time'])) ?></strong></p>
                    </div>
                    <div class="col-md-3">
                        <h6 class="text-muted text-uppercase small">Duration</h6>
                        <p class="mb-0"><strong><?= $booking['duration_hours'] ?> hour<?= $booking['duration_hours'] > 1 ? 's' : '' ?></strong></p>
                    </div>
                    <div class="col-md-3">
                        <h6 class="text-muted text-uppercase small">Total Price</h6>
                        <p class="mb-0"><strong class="text-primary h5"><?= formatRupiah($booking['total_price']) ?></strong></p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-muted text-uppercase small">Booking Timeline</h6>
                        <div class="timeline mt-2">
                            <div class="timeline-item">
                                <small class="text-muted"><?= formatDate($booking['created_at']) ?></small>
                                <p class="mb-0">Booking created</p>
                            </div>
                            <?php if ($booking['booking_status'] === 'confirmed' || $booking['booking_status'] === 'playing' || $booking['booking_status'] === 'completed'): ?>
                            <div class="timeline-item">
                                <small class="text-muted">-</small>
                                <p class="mb-0">Payment verified</p>
                            </div>
                            <?php endif; ?>
                            <?php if ($booking['booking_status'] === 'completed'): ?>
                            <div class="timeline-item">
                                <?php $endTime = date('H:i', strtotime($booking['start_time']) + ($booking['duration_hours'] * 3600)); ?>
                                <p class="mb-0">Session ended at <?= $endTime ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <?php if ($booking['booking_status'] === 'pending' && isCustomer()): ?>
                    <a href="dashboard.php?page=payments&booking_id=<?= $booking['id'] ?>" class="btn btn-primary">
                        <i class="bi bi-credit-card me-1"></i> Pay Now
                    </a>
                    <button onclick="if(confirm('Cancel this booking?')) window.location.href='dashboard.php?page=bookings_cancel&id=<?= $booking['id'] ?>'" class="btn btn-outline-danger">
                        <i class="bi bi-x-lg me-1"></i> Cancel Booking
                    </button>
                <?php endif; ?>
                
                <?php if ($booking['booking_status'] === 'pending' && (isStaff() || isAdmin())): ?>
                    <a href="dashboard.php?page=payments_verify&booking_id=<?= $booking['id'] ?>" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Verify Payment
                    </a>
                <?php endif; ?>
                
                <a href="dashboard.php?page=bookings" class="btn btn-secondary float-end">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-credit-card me-2"></i>Payment</h3>
            </div>
            <div class="card-body">
                <?php if ($booking['payment_id']): ?>
                    <p><strong>Payment Method:</strong><br><?= getPaymentMethodBadge($booking['payment_method']) ?></p>
                    <p><strong>Status:</strong><br><?= getPaymentStatusBadge($booking['pay_status']) ?></p>
                    <?php if ($booking['transaction_id']): ?>
                        <p><strong>Transaction ID:</strong><br><small><?= escape($booking['transaction_id']) ?></small></p>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-credit-card-2-back fs-1 text-muted d-block mb-2"></i>
                        <p class="text-muted">No payment yet</p>
                        <?php if (isCustomer()): ?>
                            <a href="dashboard.php?page=payments&booking_id=<?= $booking['id'] ?>" class="btn btn-primary btn-sm">Pay Now</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-file-text me-2"></i>Invoice</h3>
            </div>
            <div class="card-body text-center">
                <?php if ($invoice): ?>
                    <p>Invoice: <strong><?= escape($invoice['invoice_number']) ?></strong></p>
                    <p>Status: <?= $invoice['payment_status'] === 'paid' ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-warning text-dark">Unpaid</span>' ?></p>
                    <a href="modules/invoices/generate.php?id=<?= $booking['id'] ?>" class="btn btn-outline-primary btn-sm" target="_blank">
                        <i class="bi bi-file-pdf me-1"></i> Download PDF
                    </a>
                <?php else: ?>
                    <p class="text-muted">Invoice not yet generated</p>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($booking['booking_status'] === 'completed'): ?>
        <div class="card">
            <div class="card-body text-center">
                <i class="bi bi-check-circle-fill text-success display-4 mb-2"></i>
                <h5>Session Completed!</h5>
                <p class="text-muted small">Leave a review for this device</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>