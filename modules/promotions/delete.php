<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireAdmin();

if (!verifyCsrfToken($_GET['csrf'] ?? null)) {
    flashMessage('Permintaan tidak valid.', 'danger');
    header('Location: dashboard.php?page=promotions');
    exit;
}

global $pdo;

$promotionId = $_GET['id'] ?? null;

if (!$promotionId || !is_numeric($promotionId)) {
    flashMessage('Invalid promotion ID.', 'danger');
    header('Location: dashboard.php?page=promotions');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM promotions WHERE id = ?");
    if ($stmt->execute([$promotionId])) {
        flashMessage('Promotion deleted successfully!', 'success');
    } else {
        flashMessage('Failed to delete promotion.', 'danger');
    }
} catch (PDOException $e) {
    flashMessage('Database error: Cannot delete promotion.', 'danger');
}

header('Location: dashboard.php?page=promotions');
exit;
