<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireLogin();

global $pdo;

$errorMessage = '';
$successMessage = '';

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
    $totalPrice = 0;
    
    if ($deviceId <= 0 || empty($bookingDate) || empty($startTime) || $durationHours <= 0) {
        $errorMessage = 'All fields are required.';
    } elseif (strtotime($bookingDate) < strtotime(date('Y-m-d'))) {
        $errorMessage = 'Booking date cannot be in the past.';
    } elseif ($durationHours < 1 || $durationHours > 8) {
        $errorMessage = 'Duration must be between 1 and 8 hours.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT price_per_hour, name FROM devices WHERE id = ? LIMIT 1");
            $stmt->execute([$deviceId]);
            $device = $stmt->fetch();
            
            if (!$device) {
                $errorMessage = 'Device not found.';
            } elseif (!checkDeviceAvailability($deviceId, $bookingDate, $startTime, $durationHours)) {
                $errorMessage = 'Device is not available at the selected time. Please choose another time.';
            } else {
                $totalPrice = calculateBookingPrice($device['price_per_hour'], $durationHours);
                
                $stmt = $pdo->prepare("
                    INSERT INTO bookings (user_id, device_id, booking_date, start_time, duration_hours, total_price, booking_status)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending')
                ");
                
                if ($stmt->execute([getCurrentUserId(), $deviceId, $bookingDate, $startTime, $durationHours, $totalPrice])) {
                    $bookingId = $pdo->lastInsertId();
                    $invoiceNumber = generateInvoiceNumber((int)$bookingId);
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO invoice_details (booking_id, invoice_number, customer_name, customer_email, total_amount, payment_status)
                        VALUES (?, ?, ?, ?, ?, 'unpaid')
                    ");
                    $stmt->execute([$bookingId, $invoiceNumber, getCurrentUserName(), getCurrentUserEmail(), $totalPrice]);
                    
                    addNotification(
                        getCurrentUserId(),
                        'Booking Created',
                        'Your booking for ' . $device['name'] . ' on ' . formatDate($bookingDate, 'd M Y') . ' at ' . $startTime . ' has been created. Please proceed with payment.',
                        'booking_confirmation'
                    );
                    
                    $_SESSION['success_message'] = 'Booking created successfully! Please complete your payment.';
                    header('Location: dashboard.php?page=bookings_view&id=' . $bookingId);
                    exit;
                } else {
                    $errorMessage = 'Failed to create booking.';
                }
            }
        } catch (PDOException $e) {
            $errorMessage = 'Database error: ' . $e->getMessage();
        }
    }
}
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
                    <div class="mb-3">
                        <label class="form-label">Select Device <span class="text-danger">*</span></label>
                        <select class="form-select" name="device_id" id="device_id" required onchange="updatePrice(this)">
                            <option value="">-- Choose Device --</option>
                            <?php $currentType = ''; ?>
                            <?php foreach ($devices as $device): ?>
                                <?php if ($device['type'] !== $currentType): ?>
                                    <?php $currentType = $device['type']; ?>
                                <?php endif; ?>
                                <option value="<?= $device['id'] ?>" 
                                        data-price="<?= $device['price_per_hour'] ?>"
                                        <?= $selectedDeviceId === (int)$device['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($device['name']) ?> - <?= formatRupiah($device['price_per_hour']) ?>/hour
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="deviceInfo" class="mt-2 small text-muted d-none">
                            <span id="devicePrice"></span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Booking Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="booking_date" 
                                   value="<?= htmlspecialchars($_POST['booking_date'] ?? date('Y-m-d', strtotime('+1 day'))) ?>"
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" name="start_time" 
                                   value="<?= htmlspecialchars($_POST['start_time'] ?? '10:00') ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Duration <span class="text-danger">*</span></label>
                            <select class="form-select" name="duration_hours" id="duration_hours" required onchange="updatePrice(document.getElementById('device_id'))">
                                <?php foreach (getBookingDurationOptions() as $hours => $label): ?>
                                    <option value="<?= $hours ?>" <?= ($_POST['duration_hours'] ?? 1) == $hours ? 'selected' : '' ?>>
                                        <?= $label ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Total Price</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control form-control-lg" id="total_price_display" value="0" readonly style="font-weight:bold;font-size:1.2rem;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                        <a href="dashboard.php?page=bookings" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-calendar-check me-1"></i> Create Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-info-circle me-2"></i>Booking Info</h3>
            </div>
            <div class="card-body small">
                <p><i class="bi bi-check-circle text-success me-1"></i> Check device availability before booking</p>
                <p><i class="bi bi-clock text-warning me-1"></i> Booking duration: 1 - 8 hours</p>
                <p><i class="bi bi-calendar text-info me-1"></i> Advance booking: Up to 7 days</p>
                <p><i class="bi bi-cash-stack text-primary me-1"></i> Payment must be completed within 1 hour</p>
                <hr>
                <h6>Membership Discounts</h6>
                <p class="mb-1"><strong>Bronze:</strong> No discount</p>
                <p class="mb-1"><strong>Silver:</strong> 5% discount</p>
                <p class="mb-0"><strong>Gold:</strong> 10% discount</p>
            </div>
        </div>
    </div>
</div>

<script>
function updatePrice(select) {
    const option = select.options[select.selectedIndex];
    const price = option ? parseFloat(option.dataset.price || 0) : 0;
    const duration = parseInt(document.getElementById('duration_hours').value || 1);
    const total = price * duration;
    
    const display = document.getElementById('total_price_display');
    if (display) {
        display.value = total.toLocaleString('id-ID');
    }
    
    const deviceInfo = document.getElementById('deviceInfo');
    const devicePrice = document.getElementById('devicePrice');
    if (price > 0 && deviceInfo && devicePrice) {
        deviceInfo.classList.remove('d-none');
        devicePrice.textContent = 'Rate: Rp ' + price.toLocaleString('id-ID') + ' / hour';
    } else if (deviceInfo) {
        deviceInfo.classList.add('d-none');
    }
}

document.getElementById('device_id').addEventListener('change', function() { updatePrice(this); });
updatePrice(document.getElementById('device_id'));
</script>