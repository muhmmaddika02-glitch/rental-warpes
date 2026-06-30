<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }

global $pdo;

$devices = [];
$errorMessage = '';
$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);

$filterType = $_GET['type'] ?? 'all';
$filterStatus = $_GET['status'] ?? 'all';

try {
    $sql = "SELECT * FROM devices WHERE 1=1";
    $params = [];
    
    if ($filterType !== 'all') {
        $sql .= " AND type = ?";
        $params[] = $filterType;
    }
    
    if ($filterStatus !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $filterStatus;
    }
    
    $sql .= " ORDER BY name ASC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $devices = $stmt->fetchAll();
    
    $deviceTypes = $pdo->query("SELECT DISTINCT type FROM devices ORDER BY type")->fetchAll(PDO::FETCH_COLUMN);
    
} catch (PDOException $e) {
    $errorMessage = 'Database error: ' . $e->getMessage();
}
?>

<div class="row mb-3">
    <div class="col-md-8">
        <form method="GET" action="dashboard.php" class="row g-2">
            <input type="hidden" name="page" value="devices">
            <div class="col-md-4">
                <select name="type" class="form-select" onchange="this.form.submit()">
                    <option value="all">All Types</option>
                    <?php foreach ($deviceTypes as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= $filterType === $type ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all">All Status</option>
                    <option value="available" <?= $filterStatus === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="booked" <?= $filterStatus === 'booked' ? 'selected' : '' ?>>Booked</option>
                    <option value="playing" <?= $filterStatus === 'playing' ? 'selected' : '' ?>>Playing</option>
                    <option value="maintenance" <?= $filterStatus === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                </select>
            </div>
            <div class="col-md-4">
                <a href="dashboard.php?page=devices" class="btn btn-secondary">Clear Filter</a>
            </div>
        </form>
    </div>
    <div class="col-md-4 text-end">
        <?php if (isAdmin() || isStaff()): ?>
            <a href="dashboard.php?page=devices_create" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i> Add Device
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($successMessage): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($errorMessage): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
    <?php if (empty($devices)): ?>
        <div class="col-12">
            <div class="alert alert-info text-center py-5">
                <i class="bi bi-inbox display-1 d-block mb-3"></i>
                <h4>No Devices Found</h4>
                <p class="mb-0">There are no gaming devices available at the moment.</p>
                <?php if (isAdmin() || isStaff()): ?>
                    <a href="dashboard.php?page=devices_create" class="btn btn-primary mt-3">Add Your First Device</a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($devices as $device): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card device-card h-100">
                    <?php if ($device['image']): ?>
                        <img src="assets/uploads/<?= htmlspecialchars($device['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($device['name']) ?>" style="height: 200px; object-fit: cover;">
                    <?php else: ?>
                        <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                            <i class="bi bi-controller text-white" style="font-size: 4rem;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="card-title mb-0"><?= htmlspecialchars($device['name']) ?></h5>
                            <?= getDeviceStatusBadge($device['status']) ?>
                        </div>
                        
                        <p class="text-muted small mb-2">
                            <i class="bi bi-tag me-1"></i><?= htmlspecialchars($device['type']) ?>
                        </p>
                        
                        <p class="card-text small text-muted mb-3">
                            <?= htmlspecialchars(substr($device['specification'] ?? 'No specifications', 0, 100)) ?>
                            <?= strlen($device['specification'] ?? '') > 100 ? '...' : '' ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 mb-0 text-primary">
                                <?= formatRupiah($device['price_per_hour']) ?><small class="text-muted">/hour</small>
                            </span>
                            
                            <div class="btn-group">
                                <?php if ($device['status'] === 'available' && isCustomer()): ?>
                                    <a href="dashboard.php?page=bookings_create&device_id=<?= $device['id'] ?>" class="btn btn-sm btn-success">
                                        <i class="bi bi-calendar-plus"></i> Book
                                    </a>
                                <?php endif; ?>
                                
                                <?php if (isAdmin() || isStaff()): ?>
                                    <a href="dashboard.php?page=devices_edit&id=<?= $device['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    
                                    <?php if (isAdmin()): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $device['id'] ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (isAdmin()): ?>
            <div class="modal fade" id="deleteModal<?= $device['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-white">
                            <h5 class="modal-title">Confirm Delete</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this device?</p>
                            <p class="fw-bold"><?= htmlspecialchars($device['name']) ?></p>
                            <p class="text-danger small">This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <a href="dashboard.php?page=devices_delete&id=<?= $device['id'] ?>" class="btn btn-danger">Delete</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php endif; ?>
</div>