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

$isConfirmed = in_array($booking['booking_status'], ['confirmed', 'playing', 'completed']);
?>

<?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">
        <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($successMessage) ?>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
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
                        <h6 class="text-muted text-uppercase small">Customer</h6>
                        <p class="mb-1"><strong><?= escape($booking['customer_name']) ?></strong></p>
                        <p class="mb-1"><?= escape($booking['customer_email']) ?></p>
                        <p class="mb-0"><?= escape($booking['customer_phone'] ?? '-') ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted text-uppercase small">Device</h6>
                        <p class="mb-1"><strong><?= escape($booking['device_name']) ?></strong></p>
                        <p class="mb-0"><?= escape($booking['device_type']) ?></p>
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
                        <h6 class="text-muted text-uppercase small">Total</h6>
                        <p class="mb-0"><strong class="text-primary h5"><?= formatRupiah($booking['total_price']) ?></strong></p>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <h6 class="text-muted text-uppercase small">Timeline</h6>
                        <div class="timeline mt-2">
                            <div class="timeline-item">
                                <small class="text-muted"><?= formatDate($booking['created_at']) ?></small>
                                <p class="mb-0">Booking created</p>
                            </div>
                            <?php if ($isConfirmed): ?>
                            <div class="timeline-item">
                                <small class="text-muted"><?= $booking['paid_at'] ? formatDate($booking['paid_at']) : '-' ?></small>
                                <p class="mb-0">Payment auto-confirmed via <?= strtoupper($booking['payment_method'] ?? '') ?></p>
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
            <div class="card-footer d-flex gap-2">
                <?php if ($booking['booking_status'] === 'confirmed' && isCustomer()): ?>
                    <button onclick="if(confirm('Cancel this booking?')) window.location.href='dashboard.php?page=bookings_cancel&id=<?= $booking['id'] ?>&csrf=<?= generateCsrfToken() ?>'" class="btn btn-outline-danger">
                        <i class="bi bi-x-lg me-1"></i> Cancel Booking
                    </button>
                <?php endif; ?>
                <?php if (in_array($booking['booking_status'], ['completed', 'cancelled']) && isCustomer()): ?>
                    <a href="dashboard.php?page=bookings_create&device_id=<?= $booking['device_id'] ?>" class="btn btn-primary">
                        <i class="bi bi-arrow-repeat me-1"></i> Book Again
                    </a>
                <?php endif; ?>
                <a href="dashboard.php?page=bookings" class="btn btn-secondary ms-auto">
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
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge-neon badge-green">Paid</span>
                        <span class="small text-muted">Auto-confirmed</span>
                    </div>
                    <p class="mb-1"><strong>Method:</strong><br><?= getPaymentMethodBadge($booking['payment_method']) ?></p>
                    <?php if ($booking['transaction_id']): ?>
                        <p class="mb-1"><strong>Transaction ID:</strong><br><small class="font-monospace"><?= escape($booking['transaction_id']) ?></small></p>
                    <?php endif; ?>
                    <p class="mb-0"><strong>Paid at:</strong><br><small><?= formatDate($booking['paid_at'], 'd M Y H:i') ?></small></p>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-credit-card-2-back fs-1 text-muted d-block mb-2"></i>
                        <p class="text-muted">No payment yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isConfirmed): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-qr-code me-2"></i>Entry Ticket</h3>
            </div>
            <div class="card-body text-center">
                <?php
                $qrData = json_encode([
                    'id' => $booking['id'],
                    'device' => $booking['device_name'],
                    'date' => $booking['booking_date'],
                    'time' => $booking['start_time'],
                    'customer' => $booking['customer_name'],
                ]);
                ?>
                <img src="<?= qrImageUrl($qrData, 200) ?>" alt="QR Ticket" class="mb-2" style="border-radius:12px;width:200px;height:200px;">
                <p class="small text-muted mb-0">Tunjukkan QR ini saat check-in</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-file-text me-2"></i>Invoice</h3>
            </div>
            <div class="card-body text-center">
                <?php if ($invoice): ?>
                    <p class="mb-1"><strong><?= escape($invoice['invoice_number']) ?></strong></p>
                    <p class="mb-2"><?= $invoice['payment_status'] === 'paid' ? '<span class="badge bg-success">Paid</span>' : '<span class="badge bg-warning text-dark">Unpaid</span>' ?></p>
                    <a href="modules/invoices/generate.php?id=<?= $booking['id'] ?>" class="btn btn-outline-info btn-sm" target="_blank">
                        <i class="bi bi-file-pdf me-1"></i> Download PDF
                    </a>
                <?php else: ?>
                    <p class="text-muted">Invoice not yet generated</p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($booking['booking_status'] === 'completed'): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-star me-2"></i>Leave a Review</h3>
            </div>
            <div class="card-body">
                <?php
                $reviewStmt = $pdo->prepare("SELECT * FROM reviews WHERE booking_id = ? LIMIT 1");
                $reviewStmt->execute([$id]);
                $existingReview = $reviewStmt->fetch();
                ?>
                <?php if ($existingReview): ?>
                    <div class="text-center">
                        <div class="fs-3 mb-1"><?= str_repeat('⭐', (int)$existingReview['rating']) ?></div>
                        <p class="mb-0"><?= escape($existingReview['comment'] ?? '') ?></p>
                        <small class="text-muted">Reviewed on <?= formatDate($existingReview['created_at'], 'd M Y') ?></small>
                    </div>
                <?php else: ?>
                    <form method="POST" action="modules/reviews/create.php" class="text-center">
                        <?= csrfField() ?>
                        <input type="hidden" name="booking_id" value="<?= $id ?>">
                        <input type="hidden" name="device_id" value="<?= $booking['device_id'] ?>">
                        <div class="mb-2 fs-3" id="starRating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <button type="button" class="border-0 bg-transparent p-0 star-btn" data-value="<?= $i ?>" style="color:#444;font-size:1.8rem;cursor:pointer;transition:color .2s">★</button>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" id="ratingValue" value="5">
                        </div>
                        <textarea name="comment" class="form-control mb-2" rows="2" placeholder="Bagaimana pengalaman kamu?"></textarea>
                        <button type="submit" class="btn btn-primary btn-sm">Submit Review</button>
                    </form>
                    <script>
                    document.querySelectorAll('.star-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const val = parseInt(this.dataset.value);
                            document.getElementById('ratingValue').value = val;
                            document.querySelectorAll('.star-btn').forEach((s, i) => {
                                s.style.color = i < val ? '#FFD700' : '#444';
                            });
                        });
                    });
                    </script>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
