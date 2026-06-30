<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireAdmin();

global $pdo;

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['success_message'] = 'Invalid device ID for deletion.';
    echo '<script>window.location.href="dashboard.php?page=devices";</script>';
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT image FROM devices WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $device = $stmt->fetch();
    
    if (!$device) {
        $_SESSION['success_message'] = 'Device not found.';
        echo '<script>window.location.href="dashboard.php?page=devices";</script>';
        exit;
    }
    
    if ($device['image']) {
        $imagePath = __DIR__ . '/../../assets/uploads/' . $device['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM devices WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success_message'] = 'Device deleted successfully!';
    } else {
        $_SESSION['success_message'] = 'Failed to delete device.';
    }
} catch (PDOException $e) {
    $_SESSION['success_message'] = 'Database error: ' . $e->getMessage();
}

echo '<script>window.location.href="dashboard.php?page=devices";</script>';
exit;