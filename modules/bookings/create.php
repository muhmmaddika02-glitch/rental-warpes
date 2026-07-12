<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireLogin();

global $pdo;

$errorMessage = '';

try {
    $devices = $pdo->query("SELECT * FROM devices WHERE status = 'available' ORDER BY type, name")->fetchAll();
    $selectedDeviceId = (int) ($_GET['device_id'] ?? ($_POST['device_id'] ?? 0));
} catch (PDOException $e) {
    $errorMessage = 'Database error: ' . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deviceId = (int) ($_POST['device_id'] ?? 0);
    $bookingDate = $_POST['booking_date'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $durationHours = (int) ($_POST['duration_hours'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? '';
    $totalPrice = 0;

    if ($deviceId <= 0 || empty($bookingDate) || empty($startTime) || $durationHours <= 0) {
        $errorMessage = 'All fields are required.';
    } elseif (strtotime($bookingDate . ' ' . $startTime) < time()) {
        $errorMessage = 'Booking time cannot be in the past.';
    } elseif ($durationHours < 1 || $durationHours > 8) {
        $errorMessage = 'Duration must be between 1 and 8 hours.';
    } elseif (!in_array($paymentMethod, ['qris', 'bank_transfer', 'e_wallet'])) {
        $errorMessage = 'Please select a payment method.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT price_per_hour, name FROM devices WHERE id = ? LIMIT 1");
            $stmt->execute([$deviceId]);
            $device = $stmt->fetch();

            if (!$device) {
                $errorMessage = 'Device not found.';
            } else {
                $basePrice = calculateBookingPrice($device['price_per_hour'], $durationHours);
                $memberLevel = getCurrentUserMembership();
                $discountRate = getMembershipDiscountRate($memberLevel);
                $discount = (int) round($basePrice * $discountRate);
                $totalPrice = $basePrice - $discount;

                $pdo->beginTransaction();

                if (!checkDeviceAvailability($deviceId, $bookingDate, $startTime, $durationHours)) {
                    $pdo->rollBack();
                    $errorMessage = 'Device is not available at the selected time. Please choose another time.';
                } else {
                $stmt = $pdo->prepare("
                    INSERT INTO bookings (user_id, device_id, booking_date, start_time, duration_hours, total_price, booking_status)
                    VALUES (?, ?, ?, ?, ?, ?, 'confirmed')
                ");
                $stmt->execute([getCurrentUserId(), $deviceId, $bookingDate, $startTime, $durationHours, $totalPrice]);
                $bookingId = $pdo->lastInsertId();

                $stmt = $pdo->prepare("UPDATE devices SET status = 'booked' WHERE id = ?");
                $stmt->execute([$deviceId]);

                $invoiceNumber = generateInvoiceNumber((int)$bookingId);
                $stmt = $pdo->prepare("
                    INSERT INTO invoice_details (booking_id, invoice_number, customer_name, customer_email, total_amount, payment_status)
                    VALUES (?, ?, ?, ?, ?, 'paid')
                ");
                $stmt->execute([$bookingId, $invoiceNumber, getCurrentUserName(), getCurrentUserEmail(), $totalPrice]);

                $transactionId = 'TXN-' . strtoupper(bin2hex(random_bytes(4)));
                $stmt = $pdo->prepare("
                    INSERT INTO payments (booking_id, payment_method, amount, payment_status, transaction_id, paid_at)
                    VALUES (?, ?, ?, 'paid', ?, NOW())
                ");
                $stmt->execute([$bookingId, $paymentMethod, $totalPrice, $transactionId]);

                $pdo->commit();

                addNotification(
                    getCurrentUserId(),
                    'Booking Confirmed',
                    'Your booking for ' . $device['name'] . ' on ' . formatDate($bookingDate, 'd M Y') . ' at ' . $startTime . ' has been confirmed. Invoice: ' . $invoiceNumber,
                    'booking_confirmation'
                );

                flashMessage('Booking confirmed! Payment via ' . strtoupper($paymentMethod) . ' successful.', 'success');
                header('Location: dashboard.php?page=bookings_view&id=' . $bookingId);
                exit;
                }
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errorMessage = 'Database error: ' . $e->getMessage();
        }
    }
}

$paymentMethods = [
    ['qris', 'QRIS', 'Scan & pay via e-wallet', 'bi-qr-code'],
    ['bank_transfer', 'Bank Transfer', 'BCA / Mandiri / BNI', 'bi-bank'],
    ['e_wallet', 'E-Wallet', 'GoPay / OVO / DANA', 'bi-wallet2'],
];
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-plus-circle me-2"></i>New Booking</h3>
            </div>
            <div class="card-body">
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>

                <form method="POST" id="bookingForm">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">Select Device <span class="text-danger">*</span></label>
                        <select class="form-select" name="device_id" id="device_id" required onchange="updatePrice(this)">
                            <option value="">-- Choose Device --</option>
                            <?php foreach ($devices as $device): ?>
                                <option value="<?= $device['id'] ?>"
                                        data-price="<?= $device['price_per_hour'] ?>"
                                        <?= $selectedDeviceId === (int)$device['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($device['name']) ?> — <?= formatRupiah($device['price_per_hour']) ?>/jam
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Booking Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="booking_date"
                                   value="<?= htmlspecialchars($_POST['booking_date'] ?? date('Y-m-d', strtotime('+1 day'))) ?>"
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="start_time"
                                   value="<?= htmlspecialchars($_POST['start_time'] ?? '10:00') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Duration <span class="text-danger">*</span></label>
                            <select class="form-select" name="duration_hours" id="duration_hours" required onchange="updatePrice(document.getElementById('device_id'))">
                                <?php foreach (getBookingDurationOptions() as $hours => $label): ?>
                                    <option value="<?= $hours ?>" <?= ($_POST['duration_hours'] ?? 1) == $hours ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Payment Method Selection -->
                    <div class="mb-4">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <div class="row g-2" id="paymentMethods">
                            <?php foreach ($paymentMethods as $i => $pm): ?>
                            <div class="col-md-4">
                                <input type="radio" class="btn-check" name="payment_method" id="pm_<?= $pm[0] ?>" value="<?= $pm[0] ?>" autocomplete="off" <?= $i === 0 ? 'checked' : '' ?>>
                                <label class="btn btn-outline-info w-100 py-3 text-center" for="pm_<?= $pm[0] ?>">
                                    <i class="bi <?= $pm[3] ?> fs-4 d-block mb-1"></i>
                                    <span class="fw-bold d-block"><?= $pm[1] ?></span>
                                    <span class="small text-muted"><?= $pm[2] ?></span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mt-4 p-3 rounded-3" style="background:var(--bg-card);border:1px solid var(--border-glass);">
                        <div>
                            <span class="text-muted small">Total Bayar</span>
                            <span class="text-muted small ms-2" id="discount_label"></span>
                            <div class="fs-3 fw-bold" style="color:var(--neon-cyan);">Rp <span id="total_price_display">0</span></div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="dashboard.php?page=bookings" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="bi bi-check2-circle me-1"></i> Bayar Sekarang
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-info-circle me-2"></i>Payment Info</h3>
            </div>
            <div class="card-body small">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge-neon badge-green">Auto-confirm</span>
                    <span class="text-muted">Pembayaran diproses instan</span>
                </div>
                <p><i class="bi bi-check-circle text-success me-1"></i> Booking langsung confirmed setelah bayar</p>
                <p><i class="bi bi-clock text-warning me-1"></i> Durasi: 1 - 8 jam</p>
                <p><i class="bi bi-printer text-info me-1"></i> Invoice otomatis tergenerate</p>
                <hr>
                <h6>Membership Diskon</h6>
                <p class="mb-1"><?= getMembershipDiscountLabel(getCurrentUserMembership()) ?></p>
                <hr>
                <h6>Metode Pembayaran</h6>
                <p class="mb-1"><strong>QRIS:</strong> Scan via GoPay/OVO/DANA/LinkAja</p>
                <p class="mb-1"><strong>Bank Transfer:</strong> BCA / Mandiri / BNI</p>
                <p class="mb-0"><strong>E-Wallet:</strong> GoPay / OVO / DANA</p>
            </div>
        </div>
    </div>
</div>

<script>
const MEMBER_LEVEL = '<?= getCurrentUserMembership() ?>';
const DISCOUNT_RATE = <?= getMembershipDiscountRate(getCurrentUserMembership()) ?>;

function updatePrice(select) {
    const option = select.options[select.selectedIndex];
    const price = option ? parseFloat(option.dataset.price || 0) : 0;
    const duration = parseInt(document.getElementById('duration_hours').value || 1);
    const base = price * duration;
    const discount = Math.round(base * DISCOUNT_RATE);
    const total = base - discount;
    document.getElementById('total_price_display').textContent = total.toLocaleString('id-ID');
    const dl = document.getElementById('discount_label');
    if (discount > 0) {
        dl.textContent = '(Diskon ' + discount.toLocaleString('id-ID') + ')';
        dl.className = 'text-success small ms-2';
    } else {
        dl.textContent = '';
    }
}
document.getElementById('device_id').addEventListener('change', function() { updatePrice(this); });
updatePrice(document.getElementById('device_id'));
</script>
