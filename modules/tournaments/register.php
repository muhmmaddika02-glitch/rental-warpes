<?php
if (!defined('GAMEZONE_ACCESS')) { header('Location: ../dashboard.php'); exit; }
requireLogin();

global $pdo;

$tournamentId = $_GET['id'] ?? null;

if (!$tournamentId || !is_numeric($tournamentId)) {
    flashMessage('Invalid tournament ID.', 'danger');
    header('Location: dashboard.php?page=tournaments');
    exit;
}

if (!isCustomer()) {
    flashMessage('Only customers can register for tournaments.', 'danger');
    header('Location: dashboard.php?page=tournaments');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT t.*, 
               (SELECT COUNT(*) FROM participants WHERE tournament_id = t.id) as total_participants
        FROM tournaments t
        WHERE t.id = ?
    ");
    $stmt->execute([$tournamentId]);
    $tournament = $stmt->fetch();
    
    if (!$tournament) {
        flashMessage('Tournament not found.', 'danger');
        header('Location: dashboard.php?page=tournaments');
        exit;
    }
    
    if ($tournament['status'] !== 'registration_open') {
        flashMessage('Registration is not open for this tournament.', 'warning');
        header('Location: dashboard.php?page=tournaments_view&id=' . $tournamentId);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM participants WHERE tournament_id = ? AND user_id = ?");
    $stmt->execute([$tournamentId, getCurrentUserId()]);
    $isAlreadyRegistered = $stmt->fetch()['count'] > 0;
    
    if ($isAlreadyRegistered) {
        flashMessage('You are already registered for this tournament.', 'info');
        header('Location: dashboard.php?page=tournaments_view&id=' . $tournamentId);
        exit;
    }
    
    if ($tournament['max_participants'] !== null && $tournament['total_participants'] >= $tournament['max_participants']) {
        flashMessage('This tournament is full. Registration closed.', 'warning');
        header('Location: dashboard.php?page=tournaments_view&id=' . $tournamentId);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("
                SELECT t.max_participants,
                       (SELECT COUNT(*) FROM participants p WHERE p.tournament_id = t.id) as cnt
                FROM tournaments t WHERE t.id = ? FOR UPDATE
            ");
            $stmt->execute([$tournamentId]);
            $cap = $stmt->fetch();

            if ($cap['max_participants'] !== null && (int)$cap['cnt'] >= (int)$cap['max_participants']) {
                $pdo->rollBack();
                flashMessage('This tournament is full. Registration closed.', 'warning');
                header('Location: dashboard.php?page=tournaments_view&id=' . $tournamentId);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO participants (tournament_id, user_id) VALUES (?, ?)");
            $stmt->execute([$tournamentId, getCurrentUserId()]);
            $pdo->commit();

            addNotification(
                getCurrentUserId(),
                'Tournament Registration Successful',
                'You have successfully registered for ' . $tournament['title'] . '. Good luck!',
                'tournament_announcement'
            );

            flashMessage('Successfully registered for the tournament!', 'success');
            header('Location: dashboard.php?page=tournaments_view&id=' . $tournamentId);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            flashMessage('Failed to register. Please try again.', 'danger');
        }
    }
    
} catch (PDOException $e) {
    flashMessage('Database error: ' . $e->getMessage(), 'danger');
    header('Location: dashboard.php?page=tournaments');
    exit;
}
?>

<div class="row">
    <div class="col-md-6 offset-md-3">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h3 class="card-title mb-0"><i class="bi bi-person-plus me-2"></i>Register for Tournament</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    You are about to register for this tournament.
                </div>
                
                <h5 class="mb-3"><?= escape($tournament['title']) ?></h5>
                
                <table class="table table-sm">
                    <tr>
                        <td><strong>Game:</strong></td>
                        <td><?= escape($tournament['game_name']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Start Date:</strong></td>
                        <td><?= formatDate($tournament['start_date'], 'd M Y H:i') ?></td>
                    </tr>
                    <tr>
                        <td><strong>Registration Fee:</strong></td>
                        <td><?= $tournament['registration_fee'] > 0 ? formatRupiah($tournament['registration_fee']) : '<span class="badge bg-success">Free</span>' ?></td>
                    </tr>
                    <tr>
                        <td><strong>Prize Pool:</strong></td>
                        <td><?= formatRupiah($tournament['prize_pool']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Participants:</strong></td>
                        <td><?= $tournament['total_participants'] ?><?= $tournament['max_participants'] ? '/' . $tournament['max_participants'] : '' ?></td>
                    </tr>
                </table>
                
                <?php if ($tournament['registration_fee'] > 0): ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <strong>Note:</strong> This tournament requires a registration fee of <?= formatRupiah($tournament['registration_fee']) ?>. 
                    You will need to complete the payment after registration.
                </div>
                <?php endif; ?>
                
                <form method="POST" class="mt-4">
                    <?= csrfField() ?>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle me-1"></i> Confirm Registration
                        </button>
                        <a href="dashboard.php?page=tournaments_view&id=<?= $tournament['id'] ?>" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
