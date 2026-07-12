<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireAdmin();

if (!verifyCsrfToken($_GET['csrf'] ?? null)) {
    flashMessage('Permintaan tidak valid.', 'danger');
    echo '<script>window.location.href="dashboard.php?page=users";</script>';
    exit;
}

global $pdo;

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    flashMessage('Invalid user ID.', 'danger');
    echo '<script>window.location.href="dashboard.php?page=users";</script>';
    exit;
}

if ($id === getCurrentUserId()) {
    flashMessage('Anda tidak dapat menghapus akun sendiri.', 'danger');
    echo '<script>window.location.href="dashboard.php?page=users";</script>';
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        flashMessage('User not found.', 'danger');
        echo '<script>window.location.href="dashboard.php?page=users";</script>';
        exit;
    }

    if ($user['role'] === 'admin') {
        $countStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        if ((int)$countStmt->fetchColumn() <= 1) {
            flashMessage('Tidak dapat menghapus admin terakhir.', 'danger');
            echo '<script>window.location.href="dashboard.php?page=users";</script>';
            exit;
        }
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    if ($stmt->execute([$id])) {
        flashMessage('User deleted successfully.', 'success');
    }
} catch (PDOException $e) {
    flashMessage('Database error: ' . $e->getMessage(), 'danger');
}

echo '<script>window.location.href="dashboard.php?page=users";</script>';
exit;
