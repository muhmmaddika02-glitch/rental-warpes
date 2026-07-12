<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireAdmin();

if (!verifyCsrfToken($_GET['csrf'] ?? null)) {
    flashMessage('Permintaan tidak valid.', 'danger');
    header('Location: dashboard.php?page=tournaments');
    exit;
}

global $pdo;

$tournamentId = $_GET['id'] ?? null;

if (!$tournamentId || !is_numeric($tournamentId)) {
    flashMessage('Invalid tournament ID.', 'danger');
    header('Location: dashboard.php?page=tournaments');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM tournaments WHERE id = ?");
    if ($stmt->execute([$tournamentId])) {
        flashMessage('Tournament deleted successfully!', 'success');
    } else {
        flashMessage('Failed to delete tournament.', 'danger');
    }
} catch (PDOException $e) {
    flashMessage('Database error: Cannot delete tournament. It may have associated participants.', 'danger');
}

header('Location: dashboard.php?page=tournaments');
exit;
