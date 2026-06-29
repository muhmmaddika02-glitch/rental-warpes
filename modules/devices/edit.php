<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireStaffOrAdmin();

global $pdo;

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['error_message'] = 'Invalid device ID.';
    echo '<script>window.location.href="dashboard.php?page=devices";</script>';
    exit;
}

$device = null;

try {
    $stmt = $pdo->prepare("SELECT * FROM devices WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $device = $stmt->fetch();
    
    if (!$device) {
        $_SESSION['error_message'] = 'Device not found.';
        header('Location: dashboard.php?page=devices');
        exit;
    }
} catch (PDOException $e) {
    $errorMessage = 'Database error: ' . $e->getMessage();
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $pricePerHour = trim($_POST['price_per_hour'] ?? '');
    $status = $_POST['status'] ?? 'available';
    $specification = trim($_POST['specification'] ?? '');
    
    if (empty($name) || empty($type) || empty($pricePerHour)) {
        $errorMessage = 'Name, type, and price per hour are required.';
    } elseif (!is_numeric($pricePerHour) || $pricePerHour <= 0) {
        $errorMessage = 'Price must be a positive number.';
    } else {
        $imagePath = $device['image'];
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
            $fileType = $_FILES['image']['type'];
            $fileSize = $_FILES['image']['size'];
            
            if (!in_array($fileType, $allowedTypes)) {
                $errorMessage = 'Only JPG, JPEG, and PNG images are allowed.';
            } elseif ($fileSize > 5 * 1024 * 1024) {
                $errorMessage = 'Image must not exceed 5MB.';
            } else {
                $uploadDir = __DIR__ . '/../../assets/uploads/';
                $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $filename = 'device_' . time() . '_' . uniqid() . '.' . $extension;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                    if ($device['image'] && file_exists($uploadDir . $device['image'])) {
                        unlink($uploadDir . $device['image']);
                    }
                    $imagePath = $filename;
                }
            }
        }
        
        if (empty($errorMessage)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE devices SET name = ?, type = ?, price_per_hour = ?, status = ?, specification = ?, image = ?
                    WHERE id = ?
                ");

                if ($stmt->execute([$name, $type, $pricePerHour, $status, $specification, $imagePath, $id])) {
                    $_SESSION['success_message'] = 'Device updated successfully!';
                    echo '<script>window.location.href="dashboard.php?page=devices";</script>';
                    exit;
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
                <h3 class="card-title"><i class="bi bi-pencil-square me-2"></i>Edit Device</h3>
            </div>
            <div class="card-body">
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($errorMessage) ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Device Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required
                               value="<?= htmlspecialchars($_POST['name'] ?? $device['name']) ?>"
                               placeholder="e.g., PS5 Room 1">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Device Type <span class="text-danger">*</span></label>
                        <select class="form-select" name="type" required>
                            <option value="">Select Type</option>
                            <?php foreach ($deviceTypes as $dt): ?>
                                <option value="<?= htmlspecialchars($dt) ?>" <?= ($_POST['type'] ?? $device['type']) === $dt ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dt) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Price Per Hour (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="price_per_hour" required
                               value="<?= htmlspecialchars($_POST['price_per_hour'] ?? $device['price_per_hour']) ?>"
                               min="0" step="1000">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="available" <?= ($_POST['status'] ?? $device['status']) === 'available' ? 'selected' : '' ?>>Available</option>
                            <option value="booked" <?= ($_POST['status'] ?? $device['status']) === 'booked' ? 'selected' : '' ?>>Booked</option>
                            <option value="playing" <?= ($_POST['status'] ?? $device['status']) === 'playing' ? 'selected' : '' ?>>Playing</option>
                            <option value="maintenance" <?= ($_POST['status'] ?? $device['status']) === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Specifications</label>
                        <textarea class="form-control" name="specification" rows="4"><?= htmlspecialchars($_POST['specification'] ?? $device['specification'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Image</label>
                        <?php if ($device['image']): ?>
                            <div class="mb-2">
                                <img src="assets/uploads/<?= htmlspecialchars($device['image']) ?>" alt="Device" style="height: 100px; object-fit: cover;" class="border rounded">
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No image uploaded</p>
                        <?php endif; ?>
                        <input type="file" class="form-control" name="image" accept="image/jpeg,image/png,image/jpg">
                        <small class="text-muted">Leave empty to keep current image</small>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="dashboard.php?page=devices" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i> Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Update Device</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>