<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireLogin();

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

if (!isCustomer()) {
    flashMessage('Only customers can unregister from tournaments.', 'danger');
    header('Location: dashboard.php?page=tournaments');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT title, status FROM tournaments WHERE id = ?");
    $stmt->execute([$tournamentId]);
    $tournament = $stmt->fetch();

    if (!$tournament) {
        flashMessage('Tournament not found.', 'danger');
        header('Location: dashboard.php?page=tournaments');
        exit;
    }

    if ($tournament['status'] !== 'registration_open') {
        flashMessage('Unregistration is only allowed while registration is open.', 'warning');
        header('Location: dashboard.php?page=tournaments_view&id=' . $tournamentId);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM participants WHERE tournament_id = ? AND user_id = ?");
    
    if ($stmt->execute([$tournamentId, getCurrentUserId()])) {
        if ($stmt->rowCount() > 0) {
            addNotification(
                getCurrentUserId(),
                'Unregistered from Tournament',
                'You have been unregistered from ' . $tournament['title'] . '.',
                'tournament_announcement'
            );
            
            flashMessage('Successfully unregistered from the tournament.', 'success');
        } else {
            flashMessage('You were not registered for this tournament.', 'info');
        }
    } else {
        flashMessage('Failed to unregister. Please try again.', 'danger');
    }
    
} catch (PDOException $e) {
    flashMessage('Database error: ' . $e->getMessage(), 'danger');
}

header('Location: dashboard.php?page=tournaments_view&id=' . $tournamentId);
exit;
