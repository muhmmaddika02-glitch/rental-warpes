<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireAdmin();

if (!verifyCsrfToken($_GET['csrf'] ?? null)) {
    flashMessage('Permintaan tidak valid.', 'danger');
    echo '<script>window.location.href="dashboard.php?page=devices";</script>';
    exit;
}

global $pdo;

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    flashMessage('Invalid device ID for deletion.', 'danger');
    echo '<script>window.location.href="dashboard.php?page=devices";</script>';
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT image FROM devices WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $device = $stmt->fetch();

    if (!$device) {
        flashMessage('Device not found.', 'danger');
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
        flashMessage('Device deleted successfully!', 'success');
    } else {
        flashMessage('Failed to delete device.', 'danger');
    }
} catch (PDOException $e) {
    flashMessage('Database error: ' . $e->getMessage(), 'danger');
}

echo '<script>window.location.href="dashboard.php?page=devices";</script>';
exit;
