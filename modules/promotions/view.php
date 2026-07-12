<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }

global $pdo;

$promotionId = $_GET['id'] ?? null;

if (!$promotionId || !is_numeric($promotionId)) {
    flashMessage('Invalid promotion ID.', 'danger');
    header('Location: dashboard.php?page=promotions');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM promotions WHERE id = ?");
    $stmt->execute([$promotionId]);
    $promotion = $stmt->fetch();
    
    if (!$promotion) {
        flashMessage('Promotion not found.', 'danger');
        header('Location: dashboard.php?page=promotions');
        exit;
    }
    
} catch (PDOException $e) {
    flashMessage('Database error.', 'danger');
    header('Location: dashboard.php?page=promotions');
    exit;
}

$isActive = $promotion['is_active'] && strtotime($promotion['end_date']) > time() && strtotime($promotion['start_date']) <= time();
$isExpired = strtotime($promotion['end_date']) < time();
$isUpcoming = strtotime($promotion['start_date']) > time();
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="card-title mb-0"><i class="bi bi-megaphone me-2"></i><?= escape($promotion['title']) ?></h3>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="alert alert-<?= $isActive ? 'success' : ($isExpired ? 'danger' : 'warning') ?>">
                            <strong>Status:</strong>
                            <?php if ($isActive): ?>
                                <i class="bi bi-check-circle me-1"></i> Active - This promotion is currently available!
                            <?php elseif ($isExpired): ?>
                                <i class="bi bi-x-circle me-1"></i> Expired - This promotion has ended
                            <?php elseif ($isUpcoming): ?>
                                <i class="bi bi-clock me-1"></i> Upcoming - This promotion will start soon
                            <?php else: ?>
                                <i class="bi bi-pause-circle me-1"></i> Inactive
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($promotion['code']): ?>
                <div class="mb-4">
                    <h5><i class="bi bi-tag me-2"></i>Promo Code</h5>
                    <div class="input-group input-group-lg">
                        <input type="text" class="form-control" id="promoCode" value="<?= htmlspecialchars($promotion['code']) ?>" readonly style="font-weight:bold;font-size:1.5rem;letter-spacing:2px;">
                        <button class="btn btn-success" type="button" onclick="copyPromoCode()">
                            <i class="bi bi-clipboard me-1"></i> Copy
                        </button>
                    </div>
                    <small class="text-muted">Use this code when creating a booking to get the discount</small>
                </div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5><i class="bi bi-percent me-2"></i>Discount</h5>
                        <p class="fs-3 text-primary fw-bold">
                            <?php if ($promotion['discount_percentage']): ?>
                                <?= $promotion['discount_percentage'] ?>% OFF
                            <?php else: ?>
                                <?= formatRupiah($promotion['discount_amount']) ?> OFF
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="bi bi-cash-stack me-2"></i>Minimum Booking</h5>
                        <p class="fs-5">
                            <?= $promotion['min_booking_amount'] ? formatRupiah($promotion['min_booking_amount']) : '<span class="text-success">No minimum</span>' ?>
                        </p>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5><i class="bi bi-calendar-event me-2"></i>Valid From</h5>
                        <p><?= formatDate($promotion['start_date'], 'd M Y H:i') ?></p>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="bi bi-calendar-x me-2"></i>Valid Until</h5>
                        <p><?= formatDate($promotion['end_date'], 'd M Y H:i') ?></p>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h5><i class="bi bi-hourglass-split me-2"></i>Usage Limit</h5>
                    <p><?= $promotion['usage_limit'] ? $promotion['usage_limit'] . ' uses' : '<span class="text-success">Unlimited</span>' ?></p>
                </div>
                
                <?php if ($promotion['description']): ?>
                <div class="mb-4">
                    <h5><i class="bi bi-file-text me-2"></i>Description</h5>
                    <p class="text-muted"><?= nl2br(escape($promotion['description'])) ?></p>
                </div>
                <?php endif; ?>
                
                <div class="d-flex gap-2">
                    <a href="dashboard.php?page=promotions" class="btn btn-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Promotions
                    </a>
                    
                    <?php if ($isActive && isCustomer()): ?>
                        <a href="dashboard.php?page=bookings_create&promo=<?= urlencode($promotion['code']) ?>" class="btn btn-success">
                            <i class="bi bi-calendar-plus me-1"></i> Use This Promo
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="bi bi-info-circle me-2"></i>How to Use</h5>
            </div>
            <div class="card-body">
                <ol class="ps-3">
                    <li class="mb-2">Copy the promo code above</li>
                    <li class="mb-2">Go to "New Booking" page</li>
                    <li class="mb-2">Fill in your booking details</li>
                    <li class="mb-2">Enter the promo code in the "Promo Code" field</li>
                    <li class="mb-2">Click "Apply" to see the discount</li>
                    <li>Complete your booking with the discounted price!</li>
                </ol>
                
                <hr>
                
                <h6 class="mb-2"><i class="bi bi-exclamation-triangle text-warning me-1"></i> Important Notes:</h6>
                <ul class="small text-muted ps-3">
                    <li>Promo code is case-sensitive</li>
                    <?php if ($promotion['min_booking_amount']): ?>
                    <li>Minimum booking: <?= formatRupiah($promotion['min_booking_amount']) ?></li>
                    <?php endif; ?>
                    <?php if ($promotion['usage_limit']): ?>
                    <li>Limited to <?= $promotion['usage_limit'] ?> uses</li>
                    <?php endif; ?>
                    <li>Valid only during the specified period</li>
                    <li>Cannot be combined with other promotions</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function copyPromoCode() {
    const codeInput = document.getElementById('promoCode');
    codeInput.select();
    codeInput.setSelectionRange(0, 99999);
    
    navigator.clipboard.writeText(codeInput.value).then(function() {
        alert('Promo code "' + codeInput.value + '" copied to clipboard!');
    }, function() {
        document.execCommand('copy');
        alert('Promo code copied!');
    });
}
</script>
