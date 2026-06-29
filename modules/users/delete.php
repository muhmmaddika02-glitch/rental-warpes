<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireAdmin();

global $pdo;

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['success_message'] = 'Invalid user ID.';
    echo '<script>window.location.href="dashboard.php?page=users";</script>';
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $_SESSION['success_message'] = 'User not found.';
        echo '<script>window.location.href="dashboard.php?page=users";</script>';
        exit;
    }
    
    if ($user['role'] === 'admin') {
        $_SESSION['success_message'] = 'Cannot delete admin users.';
        echo '<script>window.location.href="dashboard.php?page=users";</script>';
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$id])) {
        $_SESSION['success_message'] = 'User deleted successfully.';
    }
} catch (PDOException $e) {
    $_SESSION['success_message'] = 'Database error: ' . $e->getMessage();
}

echo '<script>window.location.href="dashboard.php?page=users";</script>';
exit;