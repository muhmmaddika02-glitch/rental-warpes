<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireAdmin();

global $pdo;

$errorMessage = '';
$promotion = null;
$promotionId = $_GET['id'] ?? null;

if (!$promotionId || !is_numeric($promotionId)) {
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $discountType = $_POST['discount_type'] ?? 'percentage';
    $allowedTypes = ['percentage', 'amount'];
    if (!in_array($discountType, $allowedTypes, true)) { $discountType = 'percentage'; }
    $discountPercentage = trim($_POST['discount_percentage'] ?? '');
    $discountAmount = trim($_POST['discount_amount'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $startDate = trim($_POST['start_date'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $minBookingAmount = trim($_POST['min_booking_amount'] ?? '');
    $usageLimit = trim($_POST['usage_limit'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($title) || empty($startDate) || empty($endDate)) {
        $errorMessage = 'Title, start date, and end date are required.';
    } elseif ($discountType === 'percentage' && (empty($discountPercentage) || !is_numeric($discountPercentage) || $discountPercentage <= 0 || $discountPercentage > 100)) {
        $errorMessage = 'Discount percentage must be between 0 and 100.';
    } elseif ($discountType === 'amount' && (empty($discountAmount) || !is_numeric($discountAmount) || $discountAmount <= 0)) {
        $errorMessage = 'Discount amount must be a positive number.';
    } elseif (!empty($minBookingAmount) && (!is_numeric($minBookingAmount) || $minBookingAmount < 0)) {
        $errorMessage = 'Minimum booking amount must be a non-negative number.';
    } elseif (!empty($usageLimit) && (!is_numeric($usageLimit) || $usageLimit <= 0)) {
        $errorMessage = 'Usage limit must be a positive number.';
    } elseif (strtotime($startDate) === false || strtotime($endDate) === false) {
        $errorMessage = 'Invalid date format.';
    } elseif (strtotime($endDate) <= strtotime($startDate)) {
        $errorMessage = 'End date must be after start date.';
    } else {
        try {
            $discountPercentageValue = $discountType === 'percentage' ? $discountPercentage : null;
            $discountAmountValue = $discountType === 'amount' ? $discountAmount : null;
            $codeValue = !empty($code) ? $code : null;
            $minBookingAmountValue = !empty($minBookingAmount) ? $minBookingAmount : null;
            $usageLimitValue = !empty($usageLimit) ? (int)$usageLimit : null;
            
            $stmt = $pdo->prepare("
                UPDATE promotions 
                SET title = ?, description = ?, discount_percentage = ?, discount_amount = ?, code = ?, 
                    start_date = ?, end_date = ?, min_booking_amount = ?, usage_limit = ?, is_active = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([$title, $description, $discountPercentageValue, $discountAmountValue, $codeValue, $startDate, $endDate, $minBookingAmountValue, $usageLimitValue, $isActive, $promotionId])) {
                flashMessage('Promotion updated successfully!', 'success');
                header('Location: dashboard.php?page=promotions');
                exit;
            } else {
                $errorMessage = 'Failed to update promotion.';
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errorMessage = 'Promotion code already exists. Please use a different code.';
            } else {
                $errorMessage = 'Database error: ' . $e->getMessage();
            }
        }
    }
    
    $promotion = array_merge($promotion, $_POST);
}

$discountType = $promotion['discount_percentage'] ? 'percentage' : 'amount';
?>

<div class="row">
    <div class="col-md-10 offset-md-1">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-pencil me-2"></i>Edit Promotion</h3>
            </div>
            <div class="card-body">
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMessage) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label for="title" class="form-label">Promotion Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" required
                               value="<?= htmlspecialchars($promotion['title'] ?? '') ?>"
                               placeholder="e.g., Summer Sale 2026">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Promotion details..."><?= htmlspecialchars($promotion['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Discount Type <span class="text-danger">*</span></label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="discount_type" id="type_percentage" value="percentage" 
                                       <?= $discountType === 'percentage' ? 'checked' : '' ?> 
                                       onchange="toggleDiscountFields()">
                                <label class="form-check-label" for="type_percentage">Percentage (%)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="discount_type" id="type_amount" value="amount"
                                       <?= $discountType === 'amount' ? 'checked' : '' ?>
                                       onchange="toggleDiscountFields()">
                                <label class="form-check-label" for="type_amount">Fixed Amount (Rp)</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3" id="percentage_field">
                            <label for="discount_percentage" class="form-label">Discount Percentage</label>
                            <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" step="0.01" min="0" max="100"
                                   value="<?= htmlspecialchars($promotion['discount_percentage'] ?? '') ?>"
                                   placeholder="e.g., 20">
                        </div>
                        
                        <div class="col-md-6 mb-3" id="amount_field" style="display:none;">
                            <label for="discount_amount" class="form-label">Discount Amount (Rp)</label>
                            <input type="number" class="form-control" id="discount_amount" name="discount_amount" step="0.01" min="0"
                                   value="<?= htmlspecialchars($promotion['discount_amount'] ?? '') ?>"
                                   placeholder="e.g., 50000">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="code" class="form-label">Promotion Code</label>
                            <input type="text" class="form-control" id="code" name="code" maxlength="50"
                                   value="<?= htmlspecialchars($promotion['code'] ?? '') ?>"
                                   placeholder="e.g., SUMMER2026">
                            <small class="text-muted">Leave empty for auto-generated code</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" required
                                   value="<?= isset($promotion['start_date']) ? date('Y-m-d\TH:i', strtotime($promotion['start_date'])) : '' ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" required
                                   value="<?= isset($promotion['end_date']) ? date('Y-m-d\TH:i', strtotime($promotion['end_date'])) : '' ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="min_booking_amount" class="form-label">Minimum Booking Amount (Rp)</label>
                            <input type="number" class="form-control" id="min_booking_amount" name="min_booking_amount" step="0.01" min="0"
                                   value="<?= htmlspecialchars($promotion['min_booking_amount'] ?? '') ?>"
                                   placeholder="Leave empty for no minimum">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="usage_limit" class="form-label">Usage Limit</label>
                            <input type="number" class="form-control" id="usage_limit" name="usage_limit" min="1"
                                   value="<?= htmlspecialchars($promotion['usage_limit'] ?? '') ?>"
                                   placeholder="Leave empty for unlimited">
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?= ($_POST['is_active'] ?? $promotion['is_active']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_active">Active (promotion is immediately available)</label>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i> Update Promotion
                        </button>
                        <a href="dashboard.php?page=promotions" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDiscountFields() {
    const isPercentage = document.getElementById('type_percentage').checked;
    document.getElementById('percentage_field').style.display = isPercentage ? 'block' : 'none';
    document.getElementById('amount_field').style.display = isPercentage ? 'none' : 'block';
}
document.addEventListener('DOMContentLoaded', function() {
    toggleDiscountFields();
});
</script>
