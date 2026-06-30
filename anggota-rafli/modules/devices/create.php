<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireStaffOrAdmin();

global $pdo;

$errorMessage = '';
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $pricePerHour = trim($_POST['price_per_hour'] ?? '');
    $status = $_POST['status'] ?? 'available';
    $specification = trim($_POST['specification'] ?? '');
    
    $formData = $_POST;
    
    if (empty($name) || empty($type) || empty($pricePerHour)) {
        $errorMessage = 'Name, type, and price per hour are required.';
    } elseif (!is_numeric($pricePerHour) || $pricePerHour <= 0) {
        $errorMessage = 'Price per hour must be a positive number.';
    } else {
        $imagePath = null;
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $fileType = $_FILES['image']['type'];
            $fileSize = $_FILES['image']['size'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $errorMessage = 'Only JPG, JPEG, and PNG images are allowed.';
            } elseif ($fileSize > 5 * 1024 * 1024) {
                $errorMessage = 'Image size must not exceed 5MB.';
            } else {
                $uploadDir = __DIR__ . '/../../assets/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'device_' . time() . '_' . uniqid() . '.' . $extension;
                $uploadPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                    $imagePath = $filename;
                } else {
                    $errorMessage = 'Failed to upload image.';
                }
            }
        }
        
        if (empty($errorMessage)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO devices (name, type, price_per_hour, status, specification, image) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                if ($stmt->execute([$name, $type, $pricePerHour, $status, $specification, $imagePath])) {
                    $_SESSION['success_message'] = 'Device added successfully!';
                    header('Location: dashboard.php?page=devices');
                    exit;
                } else {
                    $errorMessage = 'Failed to add device.';
                }
            } catch (PDOException $e) {
                $errorMessage = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

$deviceTypes = ['PlayStation 5', 'PlayStation 4', 'PC Gaming', 'Nintendo Switch', 'VR Room', 'Xbox Series X', 'Xbox One'];
?>

<div class="row">
    <div class="col-md-8 offset-md-2">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="bi bi-plus-circle me-2"></i>Add New Device</h3>
            </div>
            <div class="card-body">
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMessage) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="name" class="form-label">Device Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required
                               value="<?= htmlspecialchars($formData['name'] ?? '') ?>"
                               placeholder="e.g., PS5 Room 1">
                    </div>
                    
                    <div class="mb-3">
                        <label for="type" class="form-label">Device Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Select Type</option>
                            <?php foreach ($deviceTypes as $deviceType): ?>
                                <option value="<?= htmlspecialchars($deviceType) ?>" 
                                        <?= ($formData['type'] ?? '') === $deviceType ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($deviceType) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Or type a custom device type</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="price_per_hour" class="form-label">Price Per Hour (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="price_per_hour" name="price_per_hour" required
                               value="<?= htmlspecialchars($formData['price_per_hour'] ?? '') ?>"
                               min="0" step="1000" placeholder="25000">
                    </div>
                    
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="available" <?= ($formData['status'] ?? 'available') === 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="maintenance" <?= ($formData['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="specification" class="form-label">Specifications</label>
                        <textarea class="form-control" id="specification" name="specification" rows="4"
                                  placeholder="e.g., Latest Gen Console, 4K TV 55 inch, DualSense Controller x2, Premium Sound System"><?= htmlspecialchars($formData['specification'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image" class="form-label">Device Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/jpg">
                        <small class="form-text text-muted">Max 5MB. Allowed: JPG, JPEG, PNG</small>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="dashboard.php?page=devices" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Device
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>